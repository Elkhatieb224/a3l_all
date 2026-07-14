import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/services/app_info_service.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/account/problems_page.dart';
import 'package:a3lnha/presentation/pages/legal/privacy_page.dart';
import 'package:a3lnha/presentation/pages/legal/terms_page.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:url_launcher/url_launcher.dart';

class InfoAboutAppPage extends StatefulWidget {
  const InfoAboutAppPage({super.key});

  @override
  State<InfoAboutAppPage> createState() => _InfoAboutAppPageState();
}

class _InfoAboutAppPageState extends State<InfoAboutAppPage> {
  AppInfoModel? _appInfo;
  bool _loading = true;
  ({double lat, double lng})? _mapPreviewPoint;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final info = await AppInfoService.getAppInfo();
    if (mounted) {
      setState(() {
        _appInfo = info;
        _mapPreviewPoint = _extractLatLngFromMapUrl(info?.mapLocationUrl ?? '');
        _loading = false;
      });
    }
  }

  Future<void> _openMapLocation() async {
    final rawUrl = _appInfo?.mapLocationUrl.trim() ?? '';
    if (rawUrl.isEmpty) return;
    final uri = Uri.tryParse(rawUrl);
    if (uri == null) return;
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  ({double lat, double lng})? _extractLatLngFromMapUrl(String raw) {
    final url = raw.trim();
    if (url.isEmpty) return null;

    // Example: .../@33.5138,36.2765,17z
    final atMatch = RegExp(r'@(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)')
        .firstMatch(url);
    if (atMatch != null) {
      final lat = double.tryParse(atMatch.group(1) ?? '');
      final lng = double.tryParse(atMatch.group(2) ?? '');
      if (lat != null && lng != null) return (lat: lat, lng: lng);
    }

    // Example: ...?query=33.5138,36.2765
    final uri = Uri.tryParse(url);
    if (uri != null) {
      final query = uri.queryParameters['query'] ?? uri.queryParameters['q'];
      if (query != null && query.contains(',')) {
        final parts = query.split(',');
        if (parts.length >= 2) {
          final lat = double.tryParse(parts[0].trim());
          final lng = double.tryParse(parts[1].trim());
          if (lat != null && lng != null) return (lat: lat, lng: lng);
        }
      }
    }

    // Example: ...!3d33.5138!4d36.2765
    final exifLike = RegExp(r'!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)')
        .firstMatch(url);
    if (exifLike != null) {
      final lat = double.tryParse(exifLike.group(1) ?? '');
      final lng = double.tryParse(exifLike.group(2) ?? '');
      if (lat != null && lng != null) return (lat: lat, lng: lng);
    }

    return null;
  }

  Widget _buildMapLocationCard() {
    final point = _mapPreviewPoint;
    return Padding(
      padding: EdgeInsets.symmetric(horizontal: 16.w),
      child: GestureDetector(
        onTap: _openMapLocation,
        child: Container(
          width: double.infinity,
          height: 170.h,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(12.r),
            color: Colors.grey[100],
            border: Border.all(color: Colors.grey[300]!),
          ),
          clipBehavior: Clip.hardEdge,
          child: Stack(
            children: [
              if (point != null)
                GoogleMap(
                  initialCameraPosition: CameraPosition(
                    target: LatLng(point.lat, point.lng),
                    zoom: 14,
                  ),
                  markers: {
                    Marker(
                      markerId: const MarkerId('hq'),
                      position: LatLng(point.lat, point.lng),
                    ),
                  },
                  zoomControlsEnabled: false,
                  mapToolbarEnabled: false,
                  myLocationButtonEnabled: false,
                  liteModeEnabled: true,
                  onTap: (_) => _openMapLocation(),
                )
              else
                Container(
                  color: Colors.grey.shade200,
                  alignment: Alignment.center,
                  child: Icon(
                    Icons.map_outlined,
                    size: 44.sp,
                    color: Colors.grey[600],
                  ),
                ),
              Positioned.fill(
                child: IgnorePointer(
                  ignoring: true,
                  child: Align(
                    alignment: Alignment.bottomCenter,
                    child: Container(
                      width: double.infinity,
                      padding: EdgeInsets.symmetric(horizontal: 12.w, vertical: 10.h),
                      color: Colors.black.withValues(alpha: 0.35),
                      child: Row(
                        children: [
                          Icon(Icons.location_on, color: Colors.white, size: 18.sp),
                          SizedBox(width: 6.w),
                          Expanded(
                            child: Text(
                              AppLocale.tr('open_in_google_maps'),
                              style: TextStyle(
                                color: Colors.white,
                                fontSize: 12.sp,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                          Icon(Icons.open_in_new, color: Colors.white, size: 16.sp),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: AppLocale.tr('app_info')),
      body: _loading
          ? Center(child: CircularProgressIndicator(color: AppColors.darkBlue))
          : RefreshIndicator(
              onRefresh: _load,
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: EdgeInsets.only(bottom: 40.h),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if ((_appInfo?.mapLocationUrl.trim().isNotEmpty ?? false))
                      _buildMapLocationCard(),
                    SizedBox(height: 20.h),
                    _item('${AppLocale.tr('establishment_name')}:', _appInfo?.establishmentName ?? '—', AppColors.darkBlue),
                    _item('${AppLocale.tr('commercial_name')}:', _appInfo?.commercialName ?? '—', AppColors.darkBlue),
                    _item('${AppLocale.tr('responsible_person')}:', _appInfo?.responsiblePerson ?? '—', Colors.grey),
                    _item('${AppLocale.tr('commercial_registration_number')}:', _appInfo?.commercialRegistrationNumber ?? '—', Colors.grey),
                    _item('${AppLocale.tr('official_email')}:', _appInfo?.officialEmail ?? '—', Colors.grey),
                    _item('${AppLocale.tr('mersis_number')}:', _appInfo?.mersisNumber ?? '—', Colors.grey),
                    _item('${AppLocale.tr('main_office')}:', _appInfo?.mainOffice ?? '—', Colors.grey),
                    _item('${AppLocale.tr('call_center')}:', _appInfo?.callCenter ?? '—', Colors.grey),
                    AboutAppItem(
                      title: AppLocale.tr('support_center'),
                      value: _appInfo?.supportCenter ?? AppLocale.tr('go_to_reports_help'),
                      valueColor: AppColors.darkBlue,
                      isClicked: true,
                      onTap: () => context.push(ProblemsPage()),
                    ),
                    AboutAppItem(
                      title: AppLocale.tr('terms_conditions'),
                      value: '',
                      valueColor: AppColors.darkBlue,
                      isClicked: true,
                      onTap: () => context.push(TermsPage()),
                    ),
                    AboutAppItem(
                      title: AppLocale.tr('privacy_policy'),
                      value: '',
                      valueColor: AppColors.darkBlue,
                      isClicked: true,
                      onTap: () => context.push(PrivacyPage()),
                    ),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _item(String title, String value, Color valueColor) {
    return AboutAppItem(
      title: title,
      value: value.isEmpty ? '—' : value,
      valueColor: valueColor,
    );
  }
}

class AboutAppItem extends StatelessWidget {
  final String title;
  final String value;
  final Color valueColor;
  final bool isClicked;
  final VoidCallback? onTap;

  const AboutAppItem({
    super.key,
    required this.title,
    required this.value,
    required this.valueColor,
    this.isClicked = false,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.symmetric(horizontal: 20.w),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: TextStyle(fontSize: 14.sp, fontWeight: FontWeight.w400),
              ),
              SizedBox(width: 12.w),
              Expanded(
                child: GestureDetector(
                  onTap: isClicked ? onTap : null,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Text(
                        value.isEmpty ? '—' : value,
                        style: TextStyle(
                          fontSize: 12.sp,
                          fontWeight: FontWeight.w600,
                          color: valueColor,
                        ),
                        textAlign: TextAlign.end,
                        softWrap: true,
                      ),
                      if (isClicked) ...[
                        SizedBox(height: 4.h),
                        Icon(
                          Icons.arrow_forward_ios,
                          color: AppColors.darkBlue,
                          size: 12.sp,
                        ),
                      ],
                    ],
                  ),
                ),
              ),
            ],
          ),
          Divider(thickness: 1.5, height: 35.h),
        ],
      ),
    );
  }
}
