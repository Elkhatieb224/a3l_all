import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:flutter/foundation.dart' show Factory;
import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';
import 'package:geocoding/geocoding.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';

/// خريطة مضمّنة تظهر بشكل دائم في النموذج — النقر عليها يحدّث الموقع (Google Maps)
class InlineMapPicker extends StatefulWidget {
  final double? initialLat;
  final double? initialLng;
  final void Function(double lat, double lng, String address) onLocationSelected;
  final double height;

  const InlineMapPicker({
    super.key,
    this.initialLat,
    this.initialLng,
    required this.onLocationSelected,
    this.height = 320,
  });

  @override
  State<InlineMapPicker> createState() => _InlineMapPickerState();
}

class _InlineMapPickerState extends State<InlineMapPicker> {
  static const double _defaultLat = 33.5138;
  static const double _defaultLng = 36.2765;

  late double _lat;
  late double _lng;
  bool _loadingAddress = false;
  GoogleMapController? _mapController;

  @override
  void initState() {
    super.initState();
    _lat = widget.initialLat ?? _defaultLat;
    _lng = widget.initialLng ?? _defaultLng;
  }

  @override
  void didUpdateWidget(InlineMapPicker oldWidget) {
    super.didUpdateWidget(oldWidget);
    final newLat = widget.initialLat;
    final newLng = widget.initialLng;
    if (newLat != null && newLng != null) {
      // Update marker + move camera when parent changes coordinates.
      final changed = newLat != _lat || newLng != _lng;
      if (changed) {
        setState(() {
          _lat = newLat;
          _lng = newLng;
        });
        _mapController?.animateCamera(
          CameraUpdate.newLatLng(LatLng(_lat, _lng)),
        );
      }
    }
  }

  Future<void> _onMapTap(LatLng point) async {
    setState(() {
      _lat = point.latitude;
      _lng = point.longitude;
      _loadingAddress = true;
    });
    try {
      final placemarks = await placemarkFromCoordinates(point.latitude, point.longitude);
      String address = '${point.latitude}, ${point.longitude}';
      if (placemarks.isNotEmpty) {
        final p = placemarks.first;
        final parts = [
          p.street,
          p.subLocality,
          p.locality,
          p.administrativeArea,
          p.country,
        ].where((e) => e != null && e.toString().trim().isNotEmpty).toList();
        address = parts.join(', ');
      }
      if (mounted) {
        setState(() => _loadingAddress = false);
        widget.onLocationSelected(_lat, _lng, address);
      }
    } catch (_) {
      if (mounted) {
        setState(() => _loadingAddress = false);
        widget.onLocationSelected(_lat, _lng, '$_lat, $_lng');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final marker = Marker(
      markerId: const MarkerId('inline_pick'),
      position: LatLng(_lat, _lng),
      icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueAzure),
    );

    return SizedBox(
      height: widget.height,
      child: ClipRRect(
        borderRadius: BorderRadius.circular(12),
        child: Stack(
          children: [
            GoogleMap(
              initialCameraPosition: CameraPosition(
                target: LatLng(_lat, _lng),
                zoom: 14,
              ),
              onMapCreated: (c) => _mapController = c,
              onTap: _onMapTap,
              markers: {marker},
              myLocationButtonEnabled: false,
              zoomControlsEnabled: true,
              mapToolbarEnabled: false,
              compassEnabled: true,
              rotateGesturesEnabled: true,
              scrollGesturesEnabled: true,
              tiltGesturesEnabled: true,
              zoomGesturesEnabled: true,
              /// يضمن استلام الخريطة للإيماءات داخل [ListView]/[SingleChildScrollView].
              gestureRecognizers: <Factory<OneSequenceGestureRecognizer>>{
                Factory<OneSequenceGestureRecognizer>(EagerGestureRecognizer.new),
              },
            ),
            if (_loadingAddress)
              Positioned.fill(
                child: Container(
                  color: Colors.black26,
                  child: const Center(
                    child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

/// نتيجة اختيار الموقع من الخريطة
class MapLocationResult {
  final double latitude;
  final double longitude;
  final String address;

  const MapLocationResult({
    required this.latitude,
    required this.longitude,
    required this.address,
  });
}

/// شاشة اختيار الموقع على الخريطة (Google Maps)
class MapLocationPickerScreen extends StatefulWidget {
  final double? initialLat;
  final double? initialLng;
  final String? initialAddress;

  const MapLocationPickerScreen({
    super.key,
    this.initialLat,
    this.initialLng,
    this.initialAddress,
  });

  @override
  State<MapLocationPickerScreen> createState() => _MapLocationPickerScreenState();
}

class _MapLocationPickerScreenState extends State<MapLocationPickerScreen> {
  late double _lat;
  late double _lng;
  String _address = '';
  bool _loadingAddress = false;

  static const double _defaultLat = 33.5138;
  static const double _defaultLng = 36.2765;

  @override
  void initState() {
    super.initState();
    _lat = widget.initialLat ?? _defaultLat;
    _lng = widget.initialLng ?? _defaultLng;
    _address = widget.initialAddress ?? '';
    if (widget.initialLat != null && widget.initialLng != null && _address.isEmpty) {
      _reverseGeocode(_lat, _lng);
    }
  }

  Future<void> _reverseGeocode(double lat, double lng) async {
    setState(() => _loadingAddress = true);
    try {
      final placemarks = await placemarkFromCoordinates(lat, lng);
      if (mounted && placemarks.isNotEmpty) {
        final p = placemarks.first;
        final parts = [
          p.street,
          p.subLocality,
          p.locality,
          p.administrativeArea,
          p.country,
        ].where((e) => e != null && e.toString().trim().isNotEmpty).toList();
        setState(() {
          _address = parts.join(', ');
          _loadingAddress = false;
        });
      } else if (mounted) {
        setState(() {
          _address = '$lat, $lng';
          _loadingAddress = false;
        });
      }
    } catch (_) {
      if (mounted) {
        setState(() {
          _address = '$_lat, $_lng';
          _loadingAddress = false;
        });
      }
    }
  }

  void _onMapTap(LatLng point) {
    setState(() {
      _lat = point.latitude;
      _lng = point.longitude;
    });
    _reverseGeocode(_lat, _lng);
  }

  void _confirm() {
    Navigator.of(context).pop(MapLocationResult(
      latitude: _lat,
      longitude: _lng,
      address: _address,
    ));
  }

  @override
  Widget build(BuildContext context) {
    final marker = Marker(
      markerId: const MarkerId('picker_pick'),
      position: LatLng(_lat, _lng),
      icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueAzure),
    );

    return Scaffold(
      appBar: AppBar(
        title: Text(AppLocale.tr('select_location_on_map')),
        backgroundColor: AppColors.darkBlue,
        foregroundColor: Colors.white,
        actions: [
          TextButton(
            onPressed: _confirm,
            child: Text(
              AppLocale.tr('confirm'),
              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600),
            ),
          ),
        ],
      ),
      body: Column(
        children: [
          Expanded(
            child: GoogleMap(
              initialCameraPosition: CameraPosition(
                target: LatLng(_lat, _lng),
                zoom: 14,
              ),
              onTap: _onMapTap,
              markers: {marker},
              myLocationButtonEnabled: false,
              zoomControlsEnabled: true,
              mapToolbarEnabled: false,
              compassEnabled: true,
              rotateGesturesEnabled: true,
              scrollGesturesEnabled: true,
              tiltGesturesEnabled: true,
              zoomGesturesEnabled: true,
            ),
          ),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(16),
            color: Colors.white,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              mainAxisSize: MainAxisSize.min,
              children: [
                if (_loadingAddress)
                  const Center(child: Padding(
                    padding: EdgeInsets.all(8),
                    child: CircularProgressIndicator(strokeWidth: 2),
                  ))
                else
                  Text(
                    _address.isEmpty ? '$_lat, $_lng' : _address,
                    style: TextStyle(fontSize: 14, color: Colors.grey[800]),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                const SizedBox(height: 8),
                Text(
                  AppLocale.tr('tap_on_map_to_change'),
                  style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
