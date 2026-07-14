import 'dart:math' as math;

import 'package:a3lnha/core/data/location_translations.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/models/ad_model.dart';
import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/data/services/ad_service.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/home/ad_details_page.dart';
import 'package:a3lnha/presentation/pages/home/home_page.dart';
import 'package:a3lnha/presentation/widgets/account/details_row.dart';
import 'package:a3lnha/presentation/widgets/shared/app_network_image.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class NotPublishedAdsPage extends StatefulWidget {
  const NotPublishedAdsPage({super.key});

  @override
  State<NotPublishedAdsPage> createState() => _NotPublishedAdsPageState();
}

class _NotPublishedAdsPageState extends State<NotPublishedAdsPage> {
  List<AdModel> _ads = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _loadAds();
  }

  Future<void> _loadAds() async {
    setState(() => _loading = true);
    final res = await AdService.getMyAds(status: 'pending');
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
      appBar: CustomAppbar(title: AppLocale.tr('not_published')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _ads.isEmpty
              ? Center(
                  child: Text(
                    AppLocale.tr('no_pending_ads'),
                    style: TextStyle(fontSize: 14.sp, color: Colors.grey[600]),
                  ),
                )
              : ListView.separated(
                  physics: BouncingScrollPhysics(),
                  itemBuilder: (context, index) {
                    return NotPublishedAdWidget(ad: _ads[index]);
                  },
                  separatorBuilder: (BuildContext context, int index) {
                    return Divider();
                  },
                  itemCount: _ads.length,
                ),
    );
  }
}

class NotPublishedAdWidget extends StatelessWidget {
  final AdModel ad;

  const NotPublishedAdWidget({super.key, required this.ad});

  String _formatDate(String? dateStr) {
    if (dateStr == null || dateStr.isEmpty) return '—';
    try {
      final dt = DateTime.tryParse(dateStr);
      if (dt != null) return '${dt.day}/${dt.month}/${dt.year}';
    } catch (_) {}
    return dateStr;
  }

  /// دولة + محافظة فقط (بدون عنوان شارع طويل) لتجنّب تجاوز العرض في البطاقة.
  String _location() {
    final locale = AppLocale.current;
    final parts = <String>[];
    void addSeg(String? raw) {
      if (raw == null) return;
      final t = raw.trim();
      if (t.isEmpty) return;
      final seg = LocationTranslations.segmentForUi(locale, t);
      if (seg.isNotEmpty) parts.add(seg);
    }

    addSeg(ad.locationCountry);
    final st = ad.locationState?.trim();
    if (st != null && st.isNotEmpty) {
      addSeg(ad.locationState);
    } else {
      addSeg(ad.locationCity);
    }
    return parts.isEmpty ? '—' : parts.join(' - ');
  }

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () {
        context.push(AdDetailsPage(adUid: ad.uid, useMyAdApi: true));
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
                  LocationWidget(location: _location()),
                  SizedBox(height: 5.h),
                  DetailsRow(
                    title: AppLocale.tr('ad_publish_date'),
                    value: _formatDate(ad.publishedAt),
                    icon: Icons.calendar_today,
                  ),
                  DetailsRow(
                    title: AppLocale.tr('views_count'),
                    value: '${ad.viewsCount}',
                    icon: Icons.visibility,
                  ),
                  DetailsRow(
                    title: AppLocale.tr('messages_count'),
                    value: '${ad.messagesCount}',
                    icon: Icons.chat_bubble_outline,
                  ),
                  DetailsRow(
                    title: AppLocale.tr('favorites_count'),
                    value: '${ad.favoritesCount}',
                    icon: Icons.star_outline,
                  ),
                ],
              ),
            ),
            Column(
              mainAxisAlignment: MainAxisAlignment.start,
              children: [
                Container(
                  padding: EdgeInsets.all(5.h),
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(4.r),
                    color: Colors.grey[400],
                  ),
                  child: Text(
                    AppLocale.tr('not_published'),
                    style: TextStyle(color: Colors.white, fontSize: 10.sp),
                  ),
                ),
                SizedBox(height: 10.h),
                Text(
                  ad.displayPriceForUi ?? '—',
                  style: TextStyle(
                    fontSize: 12.sp,
                    color: AppColors.darkBlue,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _imagePlaceholder(BuildContext context) {
    return Container(
      width: 70.w,
      height: math.max(70.h, 70.w * 2.2),
      color: Colors.grey[200],
      child: Icon(Icons.image_not_supported, color: Colors.grey[400], size: 28.sp),
    );
  }
}
