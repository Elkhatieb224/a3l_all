import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/data/models/ad_model.dart';
import 'package:a3lnha/data/services/ad_service.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/home/ad_details_page.dart';
import 'package:a3lnha/presentation/widgets/shared/app_network_image.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class SuspendedAdsPage extends StatefulWidget {
  const SuspendedAdsPage({super.key});

  @override
  State<SuspendedAdsPage> createState() => _SuspendedAdsPageState();
}

class _SuspendedAdsPageState extends State<SuspendedAdsPage> {
  List<AdModel> _ads = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _loadAds();
  }

  Future<void> _loadAds() async {
    setState(() => _loading = true);
    final res = await AdService.getMyAds(status: 'suspended');
    if (mounted) {
      setState(() {
        _ads = res.ads;
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: AppLocale.tr('suspended')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _ads.isEmpty
              ? Center(
                  child: Text(
                    AppLocale.tr('no_suspended_ads'),
                    style: TextStyle(fontSize: 14.sp, color: Colors.grey[600]),
                  ),
                )
              : ListView.separated(
                  physics: const BouncingScrollPhysics(),
                  itemBuilder: (context, index) {
                    return _SuspendedAdWidget(
                      ad: _ads[index],
                      onRefresh: _loadAds,
                    );
                  },
                  separatorBuilder: (_, __) => const Divider(),
                  itemCount: _ads.length,
                ),
    );
  }
}

class _SuspendedAdWidget extends StatelessWidget {
  final AdModel ad;
  final VoidCallback? onRefresh;

  const _SuspendedAdWidget({required this.ad, this.onRefresh});

  String _formatDate(String? dateStr) {
    if (dateStr == null || dateStr.isEmpty) return '—';
    try {
      final dt = DateTime.tryParse(dateStr);
      if (dt != null) return '${dt.day}/${dt.month}/${dt.year}';
    } catch (_) {}
    return dateStr;
  }

  String _location() {
    final parts = [
      ad.locationCity,
      ad.locationDistrict,
      ad.locationAddress,
    ].where((x) => x != null && x.toString().trim().isNotEmpty);
    return parts.isEmpty ? '—' : parts.join('، ');
  }

  Widget _imagePlaceholder(BuildContext context) {
    return Container(
      width: 75.w,
      height: 75.w,
      color: Colors.grey[300],
      child: Icon(Icons.image_not_supported, size: 28.sp, color: Colors.grey[500]),
    );
  }

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () {
        context.push(AdDetailsPage(adUid: ad.uid, useMyAdApi: true)).then((_) {
          onRefresh?.call();
        });
      },
      child: Padding(
        padding: EdgeInsets.symmetric(horizontal: 20.w, vertical: 10.h),
        child: Row(
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(8.r),
              child: ColoredBox(
                color: Colors.grey[200]!,
                child: ad.imageUrl != null
                    ? ListAdThumbnailImage(
                        imageUrl: ad.imageUrl!,
                        width: 75.w,
                        maxHeight: 75.w,
                        errorBuilder: (_, __) => _imagePlaceholder(context),
                      )
                    : _imagePlaceholder(context),
              ),
            ),
            SizedBox(width: 10.w),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(ad.title, style: TextStyle(fontSize: 14.sp)),
                  SizedBox(height: 2.h),
                  Text(
                    _location(),
                    style: TextStyle(fontSize: 12.sp, color: Colors.grey[600]),
                  ),
                  SizedBox(height: 5.h),
                  Text(
                    _formatDate(ad.publishedAt),
                    style: TextStyle(fontSize: 12.sp, color: Colors.grey[600]),
                  ),
                  SizedBox(height: 4.h),
                  Text(
                    AppLocale.tr('suspended'),
                    style: TextStyle(
                      fontSize: 12.sp,
                      color: Colors.orange[700],
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
            ),
            Icon(Icons.chevron_right, color: Colors.grey[400], size: 24.sp),
          ],
        ),
      ),
    );
  }
}
