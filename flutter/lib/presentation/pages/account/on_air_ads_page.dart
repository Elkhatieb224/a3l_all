import 'dart:math' as math;

import 'package:a3lnha/core/data/location_translations.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/data/models/ad_model.dart';
import 'package:a3lnha/presentation/widgets/shared/favorite_icon_button.dart';
import 'package:a3lnha/data/services/ad_service.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/home/ad_details_page.dart';
import 'package:a3lnha/presentation/pages/home/home_page.dart';
import 'package:a3lnha/presentation/widgets/account/details_row.dart';
import 'package:a3lnha/presentation/widgets/ad_status_badge_icon.dart';
import 'package:a3lnha/presentation/widgets/shared/app_network_image.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:hexcolor/hexcolor.dart';

class OnAirAdsPage extends StatefulWidget {
  const OnAirAdsPage({super.key});

  @override
  State<OnAirAdsPage> createState() => _OnAirAdsPageState();
}

class _OnAirAdsPageState extends State<OnAirAdsPage> {
  List<AdModel> _ads = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _loadAds();
  }

  Future<void> _loadAds() async {
    setState(() => _loading = true);
    final res = await AdService.getMyAds(status: 'active');
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
      appBar: CustomAppbar(title: AppLocale.tr('on_air')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _ads.isEmpty
              ? Center(
                  child: Text(
                    AppLocale.tr('no_published_ads'),
                    style: TextStyle(fontSize: 14.sp, color: Colors.grey[600]),
                  ),
                )
              : ListView.separated(
                  itemBuilder: (context, index) {
                    return AccountAdWidget(ad: _ads[index]);
                  },
                  separatorBuilder: (BuildContext context, int index) {
                    return Divider();
                  },
                  itemCount: _ads.length,
                ),
    );
  }
}

class AccountAdWidget extends StatelessWidget {
  final AdModel? ad;
  final bool isFavourite;
  final VoidCallback? onRemoveFromFavorites;
  /// عند false (مثلاً في الدردشة عندما المستخدم ليس صاحب الإعلان) لا تُعرض عدد الرسائل وعدد المفضلة.
  final bool showMessagesAndFavoritesCount;
  /// عند false (مثلاً داخل الدردشة) لا نعرض عدد المشاهدات.
  final bool showViewsCount;

  const AccountAdWidget({
    super.key,
    this.ad,
    this.isFavourite = false,
    this.onRemoveFromFavorites,
    this.showMessagesAndFavoritesCount = true,
    this.showViewsCount = true,
  });

  String _formatDate(String? dateStr, {String fallback = '—'}) {
    if (dateStr == null || dateStr.isEmpty) return fallback;
    try {
      final dt = DateTime.tryParse(dateStr);
      if (dt != null) return '${dt.day}/${dt.month}/${dt.year}';
    } catch (_) {}
    return dateStr;
  }

  String _location(AdModel? a) {
    if (a == null) return '—';
    final locale = AppLocale.current;
    final parts = <String>[];
    void addSeg(String? raw) {
      if (raw == null) return;
      final t = raw.trim();
      if (t.isEmpty) return;
      final seg = LocationTranslations.segmentForUi(locale, t);
      if (seg.isNotEmpty) parts.add(seg);
    }

    addSeg(a.locationCountry);
    final st = a.locationState?.trim();
    if (st != null && st.isNotEmpty) {
      addSeg(a.locationState);
    } else {
      addSeg(a.locationCity);
    }
    return parts.isEmpty ? '—' : parts.join(' - ');
  }

  @override
  Widget build(BuildContext context) {
    // للتوافق مع الصفحات غير المربوطة (favourite_ads, chat)
    if (ad == null) {
      return _buildPlaceholderCard(context);
    }
    final a = ad!;
    return GestureDetector(
      onTap: () {
        context.push(AdDetailsPage(adUid: a.uid, useMyAdApi: !isFavourite));
      },
      child: Padding(
        padding: EdgeInsets.symmetric(horizontal: 20.w, vertical: 10.h),
        child: Row(
          children: [
            Stack(
              clipBehavior: Clip.none,
              children: [
                ClipRRect(
                  borderRadius: BorderRadius.circular(8.r),
                  child: ColoredBox(
                    color: Colors.grey[200]!,
                    child: a.imageUrl != null
                        ? ListAdThumbnailImage(
                            imageUrl: a.imageUrl!,
                            width: 75.w,
                            maxHeight: 75.w,
                            errorBuilder: (_, __) => _imagePlaceholder(context),
                          )
                        : _imagePlaceholder(context),
                  ),
                ),
                if (a.isFeatured)
                  Positioned(
                    top: 2.h,
                    left: 2.w,
                    child: AdStatusBadgeIcon.featured(size: 18.sp),
                  ),
              ],
            ),
            SizedBox(width: 10.w),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(a.title, style: TextStyle(fontSize: 14.sp)),
                  SizedBox(height: 8.h),
                  LocationWidget(location: _location(a)),
                  SizedBox(height: 5.h),
                  DetailsRow(
                    title: AppLocale.tr('ad_publish_date'),
                    value: _formatDate(a.publishedAt),
                    icon: Icons.calendar_today,
                  ),
                  if (a.isOwner &&
                      a.featuredUntil != null &&
                      a.featuredUntil!.trim().isNotEmpty)
                    DetailsRow(
                      title: AppLocale.tr('promotion_featured_until'),
                      value: _formatDate(a.featuredUntil),
                      icon: Icons.workspace_premium_outlined,
                    ),
                  if (a.isOwner &&
                      a.urgentUntil != null &&
                      a.urgentUntil!.trim().isNotEmpty)
                    DetailsRow(
                      title: AppLocale.tr('promotion_urgent_until'),
                      value: _formatDate(a.urgentUntil),
                      icon: Icons.bolt_outlined,
                    ),
                  if (showViewsCount)
                    DetailsRow(
                      title: AppLocale.tr('views_count'),
                      value: '${a.viewsCount}',
                      icon: Icons.visibility,
                    ),
                  if (showMessagesAndFavoritesCount) ...[
                    DetailsRow(
                      title: AppLocale.tr('messages_count'),
                      value: '${a.messagesCount}',
                      icon: Icons.chat_bubble_outline,
                    ),
                    DetailsRow(
                      title: AppLocale.tr('favorites_count'),
                      value: '${a.favoritesCount}',
                      icon: Icons.star_outline,
                    ),
                  ],
                ],
              ),
            ),
            Column(
              children: [
                if (isFavourite && onRemoveFromFavorites != null)
                  GestureDetector(
                    onTap: () => onRemoveFromFavorites!(),
                    child: Padding(
                      padding: EdgeInsets.only(bottom: 6.h),
                      child: Icon(Icons.favorite, color: Colors.red[400], size: 24.sp),
                    ),
                  )
                else if (isFavourite && onRemoveFromFavorites == null && ad != null)
                  Padding(
                    padding: EdgeInsets.only(bottom: 6.h),
                    child: FavoriteIconButton(
                      adUid: a.uid,
                      initialIsFavorite: a.isFavorite,
                      size: 22.sp,
                    ),
                  )
                else if (!isFavourite)
                  Container(
                    padding: EdgeInsets.all(5.h),
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(4.r),
                      color: HexColor("26A69A"),
                    ),
                    child: Text(
                      AppLocale.tr('published'),
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 10.sp,
                      ),
                    ),
                  ),
                SizedBox(height: 10.h),
                Text(
                  a.displayPriceForUi ?? '—',
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

  Widget _buildPlaceholderCard(BuildContext context) {
    return Padding(
      padding: EdgeInsets.symmetric(horizontal: 20.w, vertical: 10.h),
      child: Row(
        children: [
          Container(
            width: 70.w,
            height: 70.h,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(8.r),
              color: Colors.grey[200],
            ),
          ),
          SizedBox(width: 10.w),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text("—", style: TextStyle(fontSize: 14.sp)),
                SizedBox(height: 8.h),
                const LocationWidget(),
                SizedBox(height: 5.h),
                DetailsRow(title: AppLocale.tr('ad_publish_date'), value: '—', icon: Icons.calendar_today),
                DetailsRow(title: AppLocale.tr('views_count'), value: '—', icon: Icons.visibility),
                DetailsRow(title: AppLocale.tr('messages_count'), value: '—', icon: Icons.chat_bubble_outline),
                DetailsRow(title: AppLocale.tr('favorites_count'), value: '—', icon: Icons.star_outline),
              ],
            ),
          ),
          Column(
            children: [
              if (!isFavourite)
                Container(
                  padding: EdgeInsets.all(5.h),
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(4.r),
                    color: HexColor("26A69A"),
                  ),
                  child: Text(AppLocale.tr('published'), style: TextStyle(color: Colors.white, fontSize: 10.sp)),
                ),
              SizedBox(height: 10.h),
              Text("—", style: TextStyle(fontSize: 12.sp, color: AppColors.darkBlue, fontWeight: FontWeight.w600)),
            ],
          ),
        ],
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
