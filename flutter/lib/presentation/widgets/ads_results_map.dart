import 'dart:async';
import 'dart:math' as math;

import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/core/storage/visited_ads_storage.dart';
import 'package:a3lnha/data/models/ad_model.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/home/ad_details_page.dart';
import 'package:a3lnha/presentation/widgets/ad_status_badge_icon.dart';
import 'package:a3lnha/presentation/widgets/map_labeled_marker_icon.dart';
import 'package:a3lnha/presentation/widgets/shared/app_network_image.dart';
import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';

double _mapHaversineKm(
  double lat1,
  double lng1,
  double lat2,
  double lng2,
) {
  const earthKm = 6371.0;
  final rLat = lat2 - lat1;
  final rLng = lng2 - lng1;
  final a = math.pow(math.sin(rLat * math.pi / 360), 2) +
      math.cos(lat1 * math.pi / 180) *
          math.cos(lat2 * math.pi / 180) *
          math.pow(math.sin(rLng * math.pi / 360), 2);
  final c = 2 * math.atan2(math.sqrt(a), math.sqrt(1 - a));
  return earthKm * c;
}

/// إحداثيات الخريطة: أعمدة الإعلان أو حقول مخصّصة (نفس منطق القوائم).
({double lat, double lng})? _mapAdPos(AdModel a) {
  final p = a.effectiveMapPosition;
  if (p == null || !p.lat.isFinite || !p.lng.isFinite) return null;
  return p;
}

LatLngBounds _mapBoundsAroundKm(LatLng center, double radiusKm) {
  const kmPerDegLat = 111.0;
  final lat = center.latitude;
  final cosLat = math.cos(lat * math.pi / 180).clamp(0.2, 1.0);
  final kmPerDegLng = kmPerDegLat * cosLat;
  final dLat = radiusKm / kmPerDegLat;
  final dLng = radiusKm / kmPerDegLng;
  return LatLngBounds(
    southwest: LatLng(lat - dLat, center.longitude - dLng),
    northeast: LatLng(lat + dLat, center.longitude + dLng),
  );
}

/// مركز إعلان يحقق أكبر عدد من الجيران ضمن [radiusKm] (يتفادى وسط «فارغ» بين مدينتين).
LatLng _mapPickDensestClusterCenter(List<AdModel> geo, double radiusKm) {
  if (geo.isEmpty) {
    return const LatLng(0, 0);
  }
  if (geo.length == 1) {
    final p = _mapAdPos(geo.first)!;
    return LatLng(p.lat, p.lng);
  }
  var bestIdx = 0;
  var bestCount = -1;
  var bestAvgDist = double.infinity;
  for (var i = 0; i < geo.length; i++) {
    final cp = _mapAdPos(geo[i])!;
    final clat = cp.lat;
    final clng = cp.lng;
    var cnt = 0;
    var sumD = 0.0;
    for (final a in geo) {
      final ap = _mapAdPos(a)!;
      final d = _mapHaversineKm(clat, clng, ap.lat, ap.lng);
      if (d <= radiusKm) {
        cnt++;
        sumD += d;
      }
    }
    final avg = cnt > 0 ? sumD / cnt : double.infinity;
    if (cnt > bestCount || (cnt == bestCount && avg < bestAvgDist)) {
      bestCount = cnt;
      bestAvgDist = avg;
      bestIdx = i;
    }
  }
  final bp = _mapAdPos(geo[bestIdx])!;
  return LatLng(bp.lat, bp.lng);
}

class AdsResultsMap extends StatefulWidget {
  final List<AdModel> ads;
  /// When true, try centering the map on the user's current location on first open.
  final bool focusUserLocationOnInit;

  const AdsResultsMap({
    super.key,
    required this.ads,
    this.focusUserLocationOnInit = false,
  });

  /// نصف قطر أول عرض للخريطة (كم). عند تعدد المدن نركّز أكبر تجمع ضمن هذه الدائرة.
  static const double mapClusterRadiusKm = 20.0;

  /// نقطة أول إطار قبل اكتمال الرسوم — يجب أن تقع داخل منطقة فيها إعلانات.
  static LatLng initialCameraTarget(List<AdModel> ads) {
    final geo = AdsResultsMap.withCoordinates(ads);
    if (geo.isEmpty) {
      return const LatLng(33.5138, 36.2764);
    }
    if (geo.length == 1) {
      final p = _mapAdPos(geo.first)!;
      return LatLng(p.lat, p.lng);
    }
    return _mapPickDensestClusterCenter(geo, mapClusterRadiusKm);
  }

  static List<AdModel> withCoordinates(List<AdModel> ads) {
    return ads.where((a) => _mapAdPos(a) != null).toList();
  }

  @override
  State<AdsResultsMap> createState() => _AdsResultsMapState();
}

class _MapClusterBucket {
  final String key;
  final List<AdModel> ads;
  final LatLng center;

  const _MapClusterBucket({
    required this.key,
    required this.ads,
    required this.center,
  });
}

class _AdsResultsMapState extends State<AdsResultsMap> {
  GoogleMapController? _controller;
  Set<Marker> _markers = {};
  AdModel? _selectedAd;
  Map<String, int> _visited = const {};
  /// يتبع تكبير الخريطة (مثل تفاصيل الإعلان).
  double _markerZoomScale = mapMarkerZoomScaleFromZoom(12);
  double _currentZoom = 12.0;
  Timer? _zoomDebounce;
  bool _didTryUserCenter = false;
  LatLng? _initialUserTarget;

  @override
  void dispose() {
    _zoomDebounce?.cancel();
    super.dispose();
  }

  void _scheduleMarkerZoomRebuild(double zoom) {
    _currentZoom = zoom;
    _zoomDebounce?.cancel();
    _zoomDebounce = Timer(const Duration(milliseconds: 160), () {
      if (!mounted) return;
      final s = mapMarkerZoomScaleFromZoom(zoom);
      if ((s - _markerZoomScale).abs() < 0.04 && zoom >= 8.0) return;
      _markerZoomScale = s;
      _rebuildMarkers(fitCamera: false);
    });
  }

  bool _sameGeoAds(List<AdModel> a, List<AdModel> b) {
    if (a.length != b.length) return false;
    for (var i = 0; i < a.length; i++) {
      final pa = a[i].effectiveMapPosition;
      final pb = b[i].effectiveMapPosition;
      if (a[i].uid != b[i].uid ||
          a[i].title != b[i].title ||
          pa?.lat != pb?.lat ||
          pa?.lng != pb?.lng ||
          a[i].price != b[i].price ||
          a[i].formattedPrice != b[i].formattedPrice ||
          a[i].isFeatured != b[i].isFeatured ||
          a[i].isUrgent != b[i].isUrgent) {
        return false;
      }
    }
    return true;
  }

  String _priceLabel(AdModel ad) {
    final p = ad.displayPriceOrSalaryForUi ?? ad.displayPriceForUi;
    if (p != null && p.trim().isNotEmpty) return p.trim();
    return '—';
  }

  List<_MapClusterBucket> _buildClusterBuckets(List<AdModel> geo, double zoom) {
    if (geo.isEmpty) return const [];
    // عند الزوم أوت: نجمع النقاط القريبة مكانياً بدل تراكبها بعرض مضلل.
    final cellDeg = zoom <= 5
        ? 2.0
        : zoom <= 6
            ? 1.0
            : zoom <= 7
                ? 0.5
                : 0.25;
    final buckets = <String, List<AdModel>>{};
    for (final ad in geo) {
      final p = _mapAdPos(ad)!;
      final gx = (p.lat / cellDeg).floor();
      final gy = (p.lng / cellDeg).floor();
      final key = '$gx:$gy';
      buckets.putIfAbsent(key, () => <AdModel>[]).add(ad);
    }
    return buckets.entries.map((e) {
      var lat = 0.0;
      var lng = 0.0;
      for (final ad in e.value) {
        final p = _mapAdPos(ad)!;
        lat += p.lat;
        lng += p.lng;
      }
      return _MapClusterBucket(
        key: e.key,
        ads: e.value,
        center: LatLng(lat / e.value.length, lng / e.value.length),
      );
    }).toList();
  }

  Future<void> _rebuildMarkers({bool fitCamera = true}) async {
    if (!mounted) return;
    final geo = AdsResultsMap.withCoordinates(widget.ads);
    if (geo.isEmpty) {
      if (mounted) {
        setState(() {
          _markers = {};
          _selectedAd = null;
        });
      }
      return;
    }

    final textDir = Directionality.of(context);
    final dpr = MediaQuery.devicePixelRatioOf(context);
    final next = <Marker>{};
    final useClustering = _currentZoom < 8.0;
    final buckets = useClustering
        ? _buildClusterBuckets(geo, _currentZoom)
        : geo.map((ad) {
            final p = _mapAdPos(ad)!;
            return _MapClusterBucket(
              key: ad.uid,
              ads: [ad],
              center: LatLng(p.lat, p.lng),
            );
          }).toList();

    for (final bucket in buckets) {
      if (bucket.ads.length > 1) {
        try {
          final icon = await MapLabeledMarkerIcon.buildPriceTag(
            priceText: '${bucket.ads.length}',
            textDirection: textDir,
            pixelRatio: dpr,
            zoomScale: (_markerZoomScale * 1.05).clamp(1.0, 2.0),
          );
          if (!mounted) return;
          next.add(
            Marker(
              markerId: MarkerId('cluster_${bucket.key}'),
              position: bucket.center,
              anchor: const Offset(0.5, 1.0),
              icon: icon,
              infoWindow: const InfoWindow(),
              onTap: () async {
                setState(() => _selectedAd = null);
                final c = _controller;
                if (c == null) return;
                final targetZoom = (_currentZoom + 1.8).clamp(8.0, 16.0);
                try {
                  await c.animateCamera(
                    CameraUpdate.newLatLngZoom(bucket.center, targetZoom),
                  );
                } catch (_) {}
              },
            ),
          );
        } catch (_) {
          if (!mounted) return;
          next.add(
            Marker(
              markerId: MarkerId('cluster_${bucket.key}'),
              position: bucket.center,
              icon: BitmapDescriptor.defaultMarkerWithHue(
                BitmapDescriptor.hueAzure,
              ),
              infoWindow: InfoWindow(title: '${bucket.ads.length}'),
              onTap: () async {
                setState(() => _selectedAd = null);
                final c = _controller;
                if (c == null) return;
                final targetZoom = (_currentZoom + 1.8).clamp(8.0, 16.0);
                try {
                  await c.animateCamera(
                    CameraUpdate.newLatLngZoom(bucket.center, targetZoom),
                  );
                } catch (_) {}
              },
            ),
          );
        }
        continue;
      }

      final ad = bucket.ads.first;
      final lat = bucket.center.latitude;
      final lng = bucket.center.longitude;
      try {
        final isVisited = _visited.containsKey(ad.uid);
        final icon = await MapLabeledMarkerIcon.buildPriceTag(
          priceText: _priceLabel(ad),
          featured: ad.isFeatured || ad.isUrgent,
          visited: isVisited && !(ad.isFeatured || ad.isUrgent),
          textDirection: textDir,
          pixelRatio: dpr,
          zoomScale: _markerZoomScale,
        );
        if (!mounted) return;
        next.add(
          Marker(
            markerId: MarkerId(ad.uid),
            position: LatLng(lat, lng),
            anchor: const Offset(0.5, 1.0),
            icon: icon,
            infoWindow: const InfoWindow(),
            onTap: () {
              setState(() => _selectedAd = ad);
            },
          ),
        );
      } catch (_) {
        if (!mounted) return;
        next.add(
          Marker(
            markerId: MarkerId(ad.uid),
            position: LatLng(lat, lng),
            icon: BitmapDescriptor.defaultMarkerWithHue(
              (ad.isFeatured || ad.isUrgent)
                  ? BitmapDescriptor.hueRed
                  : BitmapDescriptor.hueAzure,
            ),
            infoWindow: InfoWindow(title: _priceLabel(ad)),
            onTap: () {
              setState(() => _selectedAd = ad);
            },
          ),
        );
      }
    }

    if (!mounted) return;
    setState(() => _markers = next);
    if (fitCamera) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) _fitCamera(geo);
      });
    }
  }

  @override
  void initState() {
    super.initState();
    if (widget.focusUserLocationOnInit) {
      unawaited(_primeInitialUserTarget());
    }
    WidgetsBinding.instance.addPostFrameCallback((_) async {
      final v = await VisitedAdsStorage.loadAndPrune();
      if (!mounted) return;
      setState(() => _visited = v);
      await _rebuildMarkers();
    });
  }

  Future<void> _primeInitialUserTarget() async {
    final p = await _resolveUserLatLng();
    if (!mounted || p == null) return;
    setState(() => _initialUserTarget = p);
    final c = _controller;
    if (c != null) {
      try {
        await c.animateCamera(CameraUpdate.newLatLngZoom(p, 12.8));
      } catch (_) {}
    }
  }

  Future<void> _onMapCreated(GoogleMapController controller) async {
    _controller = controller;
    final geo = AdsResultsMap.withCoordinates(widget.ads);
    if (geo.isEmpty) return;
    WidgetsBinding.instance.addPostFrameCallback((_) async {
      if (!mounted || _controller == null) return;
      if (widget.focusUserLocationOnInit && !_didTryUserCenter) {
        _didTryUserCenter = true;
        final centered = await _tryCenterOnUser();
        if (!mounted || _controller == null) return;
        if (!centered && _initialUserTarget != null) {
          try {
            await _controller!.animateCamera(
              CameraUpdate.newLatLngZoom(_initialUserTarget!, 12.8),
            );
          } catch (_) {}
        }
      } else {
        await _fitCamera(geo);
      }
      if (!mounted || _controller == null) return;
      try {
        final z = await _controller!.getZoomLevel();
        if (!mounted) return;
        final s = mapMarkerZoomScaleFromZoom(z);
        if ((s - _markerZoomScale).abs() > 0.05) {
          _markerZoomScale = s;
          await _rebuildMarkers(fitCamera: false);
        }
      } catch (_) {}
    });
  }

  Future<LatLng?> _resolveUserLatLng() async {
    try {
      final serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) return null;

      var perm = await Geolocator.checkPermission();
      if (perm == LocationPermission.denied) {
        perm = await Geolocator.requestPermission();
      }
      if (perm == LocationPermission.denied ||
          perm == LocationPermission.deniedForever) {
        return null;
      }

      // Always prefer the live, current GPS fix (real location now),
      // not account profile location and not stale cached location.
      Position? pos;
      try {
        pos = await Geolocator.getCurrentPosition(
          desiredAccuracy: LocationAccuracy.bestForNavigation,
          timeLimit: const Duration(seconds: 10),
        );
      } catch (_) {
        // Fallback only if realtime acquisition fails.
        pos = await Geolocator.getLastKnownPosition();
      }
      if (pos == null) return null;
      if (!pos.latitude.isFinite || !pos.longitude.isFinite) return null;
      return LatLng(pos.latitude, pos.longitude);
    } catch (_) {
      return null;
    }
  }

  Future<bool> _tryCenterOnUser() async {
    final c = _controller;
    if (c == null) return false;
    final target = await _resolveUserLatLng();
    if (target == null) return false;
    try {
      await c.animateCamera(CameraUpdate.newLatLngZoom(target, 12.8));
      return true;
    } catch (_) {
      return false;
    }
  }

  Future<void> _fitCamera(List<AdModel> geo) async {
    final c = _controller;
    if (c == null || geo.isEmpty) return;

    if (geo.length == 1) {
      final p = _mapAdPos(geo.first)!;
      final center = LatLng(p.lat, p.lng);
      try {
        await c.animateCamera(
          CameraUpdate.newLatLngBounds(
            _mapBoundsAroundKm(center, 3),
            56,
          ),
        );
      } catch (_) {
        await c.animateCamera(
          CameraUpdate.newLatLngZoom(center, 14),
        );
      }
      return;
    }

    double sumLat = 0;
    double sumLng = 0;
    for (final a in geo) {
      final p = _mapAdPos(a)!;
      sumLat += p.lat;
      sumLng += p.lng;
    }
    final center = LatLng(sumLat / geo.length, sumLng / geo.length);

    var maxKm = 0.0;
    for (final a in geo) {
      final p = _mapAdPos(a)!;
      final d = _mapHaversineKm(
        center.latitude,
        center.longitude,
        p.lat,
        p.lng,
      );
      if (d > maxKm) maxKm = d;
    }

    if (maxKm <= AdsResultsMap.mapClusterRadiusKm) {
      final p0 = _mapAdPos(geo.first)!;
      double minLat = p0.lat;
      double maxLat = p0.lat;
      double minLng = p0.lng;
      double maxLng = p0.lng;
      for (final a in geo) {
        final p = _mapAdPos(a)!;
        minLat = math.min(minLat, p.lat);
        maxLat = math.max(maxLat, p.lat);
        minLng = math.min(minLng, p.lng);
        maxLng = math.max(maxLng, p.lng);
      }
      if ((maxLat - minLat).abs() < 1e-5 &&
          (maxLng - minLng).abs() < 1e-5) {
        await c.animateCamera(
          CameraUpdate.newLatLngZoom(LatLng(minLat, minLng), 14),
        );
        return;
      }
      final bounds = LatLngBounds(
        southwest: LatLng(minLat, minLng),
        northeast: LatLng(maxLat, maxLng),
      );
      try {
        await c.animateCamera(CameraUpdate.newLatLngBounds(bounds, 56));
      } catch (_) {
        await c.animateCamera(
          CameraUpdate.newLatLngZoom(center, 12),
        );
      }
      return;
    }

    // تجمعات بعيدة: لا نستخدم الوسط الهندسي (قد يقع في صحراء). نختار أكبر كتلة ضمن 20 كم.
    final clusterCenter =
        _mapPickDensestClusterCenter(geo, AdsResultsMap.mapClusterRadiusKm);
    final inRange = geo
        .where(
          (a) {
            final p = _mapAdPos(a)!;
            return _mapHaversineKm(
                  clusterCenter.latitude,
                  clusterCenter.longitude,
                  p.lat,
                  p.lng,
                ) <=
                AdsResultsMap.mapClusterRadiusKm;
          },
        )
        .toList();

    if (inRange.length == 1) {
      final pp = _mapAdPos(inRange.first)!;
      final p = LatLng(pp.lat, pp.lng);
      try {
        await c.animateCamera(
          CameraUpdate.newLatLngBounds(_mapBoundsAroundKm(p, 3), 56),
        );
      } catch (_) {
        await c.animateCamera(CameraUpdate.newLatLngZoom(p, 14));
      }
      return;
    }

    final pFirst = _mapAdPos(inRange.first)!;
    double minLat = pFirst.lat;
    double maxLat = pFirst.lat;
    double minLng = pFirst.lng;
    double maxLng = pFirst.lng;
    for (final a in inRange) {
      final p = _mapAdPos(a)!;
      minLat = math.min(minLat, p.lat);
      maxLat = math.max(maxLat, p.lat);
      minLng = math.min(minLng, p.lng);
      maxLng = math.max(maxLng, p.lng);
    }
    if ((maxLat - minLat).abs() < 1e-5 && (maxLng - minLng).abs() < 1e-5) {
      await c.animateCamera(
        CameraUpdate.newLatLngZoom(LatLng(minLat, minLng), 14),
      );
      return;
    }
    final bounds = LatLngBounds(
      southwest: LatLng(minLat, minLng),
      northeast: LatLng(maxLat, maxLng),
    );
    try {
      await c.animateCamera(CameraUpdate.newLatLngBounds(bounds, 56));
    } catch (_) {
      await c.animateCamera(
        CameraUpdate.newLatLngZoom(clusterCenter, 12),
      );
    }
  }

  @override
  void didUpdateWidget(covariant AdsResultsMap oldWidget) {
    super.didUpdateWidget(oldWidget);
    final oldGeo = AdsResultsMap.withCoordinates(oldWidget.ads);
    final newGeo = AdsResultsMap.withCoordinates(widget.ads);
    if (!_sameGeoAds(oldGeo, newGeo)) {
      final sel = _selectedAd?.uid;
      _rebuildMarkers().then((_) {
        if (!mounted || sel == null) return;
        final still = newGeo.where((a) => a.uid == sel);
        if (still.isEmpty) {
          setState(() => _selectedAd = null);
        } else {
          setState(() => _selectedAd = still.first);
        }
      });
    }
  }

  void _openDetails(AdModel ad) {
    context.push(AdDetailsPage(adUid: ad.uid));
  }

  Future<void> _openDetailsAndMarkVisited(AdModel ad) async {
    await VisitedAdsStorage.markVisited(ad.uid);
    if (mounted) {
      final next = Map<String, int>.from(_visited);
      next[ad.uid] = DateTime.now().millisecondsSinceEpoch;
      setState(() => _visited = next);
    }
    _openDetails(ad);
    // إعادة رسم العلامات لتحديث اللون فوراً.
    unawaited(_rebuildMarkers(fitCamera: false));
  }

  @override
  Widget build(BuildContext context) {
    final geo = AdsResultsMap.withCoordinates(widget.ads);
    if (geo.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.map_outlined, size: 56, color: Colors.grey[500]),
              const SizedBox(height: 16),
              Text(
                AppLocale.tr('map_no_location_ads'),
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 15,
                  color: Colors.grey[700],
                  height: 1.4,
                ),
              ),
            ],
          ),
        ),
      );
    }

    final initial = widget.focusUserLocationOnInit
        ? (_initialUserTarget ?? const LatLng(33.5138, 36.2764))
        : AdsResultsMap.initialCameraTarget(widget.ads);
    final selected = _selectedAd;
    final previewH = selected != null ? 118.0 : 0.0;

    return Stack(
      fit: StackFit.expand,
      clipBehavior: Clip.none,
      children: [
        GoogleMap(
          initialCameraPosition: CameraPosition(target: initial, zoom: 12),
          onMapCreated: _onMapCreated,
          onCameraMove: (CameraPosition pos) {
            _scheduleMarkerZoomRebuild(pos.zoom);
          },
          markers: _markers,
          mapToolbarEnabled: false,
          myLocationEnabled: true,
          myLocationButtonEnabled: true,
          zoomControlsEnabled: true,
          compassEnabled: true,
          padding: EdgeInsets.only(bottom: previewH),
          onTap: (_) {
            if (_selectedAd != null) {
              setState(() => _selectedAd = null);
            }
          },
        ),
        if (selected != null)
          Positioned(
            left: 0,
            right: 0,
            bottom: 0,
            child: Material(
              elevation: 12,
              color: Theme.of(context).colorScheme.surface,
              child: SafeArea(
                top: false,
                child: InkWell(
                  onTap: () {
                    _openDetailsAndMarkVisited(selected);
                  },
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(12, 10, 12, 10),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        ClipRRect(
                          borderRadius: BorderRadius.circular(8),
                          child: SizedBox(
                            width: 92,
                            height: 92,
                            child: AppNetworkImage(
                              imageUrl: selected.imageUrl,
                              width: 92,
                              height: 92,
                              fit: BoxFit.cover,
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Text(
                                selected.title,
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                  fontWeight: FontWeight.w600,
                                  fontSize: 14,
                                  height: 1.25,
                                ),
                              ),
                              if (selected.isFeatured || selected.isUrgent) ...[
                                const SizedBox(height: 4),
                                Row(
                                  children: [
                                    AdStatusBadgeIcon.featured(size: 16),
                                    const SizedBox(width: 4),
                                    Text(
                                      selected.isFeatured
                                          ? AppLocale.tr('featured_ads_label')
                                          : AppLocale.tr('urgent_ads_label'),
                                      style: TextStyle(
                                        fontSize: 11,
                                        color: Colors.grey.shade800,
                                        fontWeight: FontWeight.w600,
                                      ),
                                    ),
                                  ],
                                ),
                              ],
                              const SizedBox(height: 6),
                              Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Icon(
                                    Icons.place_outlined,
                                    size: 16,
                                    color: Colors.grey.shade600,
                                  ),
                                  const SizedBox(width: 4),
                                  Expanded(
                                    child: Text(
                                      selected.staticLocationDisplayLine
                                              .trim()
                                              .isNotEmpty
                                          ? selected.staticLocationDisplayLine
                                          : '—',
                                      maxLines: 2,
                                      overflow: TextOverflow.ellipsis,
                                      style: TextStyle(
                                        fontSize: 12,
                                        color: Colors.grey.shade700,
                                        height: 1.2,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 6),
                              Align(
                                alignment: AlignmentDirectional.centerEnd,
                                child: Text(
                                  _priceLabel(selected),
                                  style: TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.w800,
                                    color: AppColors.darkBlue,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                        Icon(
                          Directionality.of(context) == TextDirection.rtl
                              ? Icons.chevron_left
                              : Icons.chevron_right,
                          color: Colors.grey.shade400,
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ),
      ],
    );
  }
}
