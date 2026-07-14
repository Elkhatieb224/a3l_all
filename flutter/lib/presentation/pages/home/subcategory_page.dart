import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/data/models/ad_model.dart';
import 'package:a3lnha/data/models/subcategory_model.dart';
import 'package:a3lnha/data/services/ad_service.dart';
import 'package:a3lnha/data/services/category_service.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/home/ad_details_page.dart';
import 'package:a3lnha/presentation/pages/home/ads_list_page.dart';
import 'package:a3lnha/presentation/widgets/ad_list_location_label.dart';
import 'package:a3lnha/presentation/widgets/shared/app_network_image.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/favorite_icon_button.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';


class SubcategoryPage extends StatefulWidget {
  final int categoryId;
  final String categoryName;
  final int subcategoryId;
  final String subcategoryName;
  /// إجمالي الإعلانات في هذه الفرعية وجميع فروعها (من API) — لعرض العدد الصحيح وليس فقط مجموع الأبناء المباشرين.
  final int? subtreeAdsCount;

  const SubcategoryPage({
    super.key,
    required this.categoryId,
    required this.categoryName,
    required this.subcategoryId,
    required this.subcategoryName,
    this.subtreeAdsCount,
  });

  @override
  State<SubcategoryPage> createState() => _SubcategoryPageState();
}

class _SubcategoryPageState extends State<SubcategoryPage> {
  List<SubcategoryModel> _children = [];
  List<AdModel> _sectionAds = [];
  bool _loading = true;
  bool _adsLoading = true;

  @override
  void initState() {
    super.initState();
    _loadChildren();
    _loadSectionAds();
  }

  Future<void> _loadSectionAds() async {
    final res = await AdService.getAds(
      categoryId: widget.categoryId,
      subcategoryId: widget.subcategoryId,
      perPage: 8,
      forceRefresh: true,
    );
    if (mounted) {
      setState(() {
        _sectionAds = res.ads;
        _adsLoading = false;
      });
      await _retrySectionAdsWithFreshNetwork();
    }
  }

  /// إعادة طلب دون كاش عندما تكون القائمة فارغة (كاش قديم أو تعارض مفاتيح).
  Future<void> _retrySectionAdsWithFreshNetwork() async {
    if (!mounted || _sectionAds.isNotEmpty) return;
    final boosted = await AdService.getAds(
      categoryId: widget.categoryId,
      subcategoryId: widget.subcategoryId,
      perPage: 8,
      forceRefresh: true,
    );
    if (mounted && boosted.ads.isNotEmpty) {
      setState(() => _sectionAds = boosted.ads);
    }
  }

  /// بعد جلب الأبناء: إن وُجدت أعداد تشير لوجود إعلانات والمعاينة ما زالت فارغة، إعادة طلب القائمة.
  Future<void> _retryPreviewIfSubtreeShouldHaveAds() async {
    if (!mounted || _sectionAds.isNotEmpty) return;
    if (_rollupHintCount < 1) return;
    final boosted = await AdService.getAds(
      categoryId: widget.categoryId,
      subcategoryId: widget.subcategoryId,
      perPage: 8,
      forceRefresh: true,
    );
    if (mounted && boosted.ads.isNotEmpty) {
      setState(() => _sectionAds = boosted.ads);
    }
  }

  int get _rollupHintCount {
    final hint = widget.subtreeAdsCount;
    if (hint != null && hint > 0) return hint;
    return _children.fold<int>(0, (sum, c) => sum + c.adsCount);
  }

  Future<void> _loadChildren() async {
    final list = await CategoryService.getSubcategoryChildren(
      widget.subcategoryId,
      forceRefresh: true,
    );
    if (mounted) {
      setState(() {
        _children = list;
        _loading = false;
      });
      await _retryPreviewIfSubtreeShouldHaveAds();
      // عند الوصول لآخر مستوى (لا يوجد أقسام فرعية) نعرض قائمة الإعلانات مباشرة
      if (list.isEmpty) {
        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (!mounted) return;
          context.pushReplacement(
            AdsListPage(
              title: widget.subcategoryName,
              categoryId: widget.categoryId,
              subcategoryId: widget.subcategoryId,
            ),
          );
        });
      }
    }
  }

  void _navigateToAds() {
    context.push(AdsListPage(
      title: '${AppLocale.tr('all_ads_of')} "${widget.subcategoryName}"',
      categoryId: widget.categoryId,
      subcategoryId: widget.subcategoryId,
    ));
  }

  void _navigateToSubcategory(SubcategoryModel child) {
    context.push(SubcategoryPage(
      categoryId: widget.categoryId,
      categoryName: widget.categoryName,
      subcategoryId: child.id,
      subcategoryName: child.name,
      subtreeAdsCount: child.adsCount,
    ));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: widget.subcategoryName),
      body: Padding(
        padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 16.h),
        child: Container(
          padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 16.h),
          width: double.infinity,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(12.r),
            color: Colors.white,
          ),
          child: _loading
              ? Center(
                  child: Padding(
                    padding: EdgeInsets.all(32.w),
                    child: CircularProgressIndicator(color: AppColors.darkBlue),
                  ),
                )
              : SingleChildScrollView(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      _SectionAdsPreview(
                      ads: _sectionAds,
                      loading: _adsLoading,
                      sectionName: widget.subcategoryName,
                      onSeeAll: () => _navigateToAds(),
                    ),
                    SizedBox(height: 16.h),
                    _SubcategoryItem(
                      title: '${AppLocale.tr('all_ads_of')} "${widget.subcategoryName}"',
                      isAll: true,
                      adsCount: _rollupHintCount,
                      onTap: () => _navigateToAds(),
                    ),
                    if (_children.isNotEmpty) ...[
                      Text(
                        AppLocale.tr('subcategories'),
                        style: TextStyle(
                          fontSize: 14.sp,
                          fontWeight: FontWeight.w600,
                          color: AppColors.darkBlue,
                        ),
                      ),
                      SizedBox(height: 12.h),
                      ..._children.map(
                        (child) => _SubcategoryItem(
                          title: child.name,
                          adsCount: child.adsCount,
                          iconUrl: child.icon,
                          onTap: () => _navigateToSubcategory(child),
                        ),
                      ),
                    ],
                    ],
                  ),
                ),
        ),
      ),
    );
  }
}

class _SectionAdsPreview extends StatelessWidget {
  final List<AdModel> ads;
  final bool loading;
  final String sectionName;
  final VoidCallback onSeeAll;

  const _SectionAdsPreview({
    required this.ads,
    required this.loading,
    required this.sectionName,
    required this.onSeeAll,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              '${AppLocale.tr('all_ads_of')} "$sectionName"',
              style: TextStyle(
                fontSize: 16.sp,
                fontWeight: FontWeight.w600,
                color: AppColors.darkBlue,
              ),
            ),
            // أظهر جميع الإعلانات - معطّل حسب الطلب
            // GestureDetector(
            //   behavior: HitTestBehavior.opaque,
            //   onTap: onSeeAll,
            //   child: Text(
            //     AppLocale.tr('show_all_ads_in_section'),
            //     style: TextStyle(
            //       color: AppColors.lightBlue,
            //       fontSize: 13.sp,
            //       fontWeight: FontWeight.w500,
            //     ),
            //   ),
            // ),
          ],
        ),
        SizedBox(height: 12.h),
        if (loading)
          SizedBox(
            height: 120.h,
            child: Center(
              child: CircularProgressIndicator(color: AppColors.darkBlue, strokeWidth: 2),
            ),
          )
        else if (ads.isEmpty)
          SizedBox(
            height: 60.h,
            child: Center(
              child: Text(
                AppLocale.tr('no_ads'),
                style: TextStyle(fontSize: 14.sp, color: Colors.grey),
              ),
            ),
          )
        else
          SizedBox(
            height: 178.h,
            child: ListView.builder(
              scrollDirection: Axis.horizontal,
              padding: EdgeInsets.symmetric(horizontal: 16.w),
              itemCount: ads.length,
              itemBuilder: (context, index) {
                final ad = ads[index];
                return GestureDetector(
                  onTap: () => context.push(AdDetailsPage(adUid: ad.uid)),
                  child: Container(
                    width: 120.w,
                    margin: EdgeInsets.only(right: 12.w),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Stack(
                          clipBehavior: Clip.none,
                          children: [
                            ClipRRect(
                              borderRadius: BorderRadius.circular(8.r),
                              child: ColoredBox(
                                color: Colors.grey[100]!,
                                child: ad.imageUrl != null
                                    ? ListAdThumbnailImage(
                                        imageUrl: ad.imageUrl!,
                                        width: 120.w,
                                        maxHeight: 90.h,
                                        errorBuilder: (_, __) => _placeholder(),
                                      )
                                    : _placeholder(),
                              ),
                            ),
                            Positioned(
                              top: 4.h,
                              right: 4.w,
                              child: FavoriteIconButton(
                                adUid: ad.uid,
                                initialIsFavorite: ad.isFavorite,
                                size: 18.sp,
                                backgroundColor: Colors.white,
                              ),
                            ),
                          ],
                        ),
                        SizedBox(height: 6.h),
                        Text(
                          ad.title,
                          style: TextStyle(
                            fontSize: 12.sp,
                            fontWeight: FontWeight.bold,
                            color: Colors.black,
                          ),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                        if (ad.hasLocationForList) ...[
                          SizedBox(height: 2.h),
                          AdListLocationLabel(
                            key: ValueKey('${ad.uid}_loc'),
                            ad: ad,
                            iconSize: 10,
                            gap: 2,
                            style: TextStyle(fontSize: 10.sp, color: Colors.grey[600]),
                            iconColor: Colors.grey[600],
                          ),
                        ],
                        if (ad.displayPriceForUi != null)
                          Text(
                            ad.displayPriceForUi!,
                            style: TextStyle(
                              fontSize: 12.sp,
                              color: AppColors.darkBlue,
                              fontWeight: FontWeight.bold,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                      ],
                    ),
                  ),
                );
              },
            ),
          ),
      ],
    );
  }

  Widget _placeholder() {
    return Container(
      width: 120.w,
      height: 90.h,
      color: Colors.grey[200],
      child: Icon(Icons.image_not_supported, color: Colors.grey[400], size: 32),
    );
  }
}

class _SubcategoryItem extends StatelessWidget {
  final String title;
  final bool isAll;
  final int adsCount;
  final String? iconUrl;
  final VoidCallback onTap;

  const _SubcategoryItem({
    required this.title,
    this.isAll = false,
    this.adsCount = 0,
    this.iconUrl,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: onTap,
      child: Column(
        mainAxisAlignment: MainAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(
                child: Row(
                  children: [
                    if (!isAll)
                      Padding(
                        padding: EdgeInsetsDirectional.only(end: 8.w),
                        child: ClipRRect(
                          borderRadius: BorderRadius.circular(6.r),
                          child: SizedBox(
                            width: 24.w,
                            height: 24.w,
                            child: (iconUrl != null && iconUrl!.trim().isNotEmpty)
                                ? AppNetworkImage(
                                    imageUrl: iconUrl,
                                    width: 24.w,
                                    height: 24.w,
                                    fit: BoxFit.contain,
                                  )
                                : Container(
                                    color: Colors.grey[200],
                                    child: Icon(
                                      Icons.folder_outlined,
                                      color: Colors.grey[600],
                                      size: 16.sp,
                                    ),
                                  ),
                          ),
                        ),
                      ),
                    Expanded(
                      child: Text(
                        title,
                        textAlign: TextAlign.start,
                        style: TextStyle(
                          color: isAll ? AppColors.lightBlue : Colors.black,
                          fontSize: 12.sp,
                          fontWeight: isAll ? FontWeight.w600 : FontWeight.w400,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              Row(
                children: [
                  Icon(
                    Icons.arrow_back_ios,
                    color: AppColors.lightBlue,
                    size: 13.sp,
                  ),
                  SizedBox(width: 10.w),
                  Text(
                    '($adsCount)',
                    style: TextStyle(
                      color: Colors.grey[500],
                      fontSize: 10.sp,
                      fontWeight: FontWeight.w400,
                    ),
                  ),
                ],
              ),
            ],
          ),
          Divider(thickness: 1.5, height: 50.h),
        ],
      ),
    );
  }
}
