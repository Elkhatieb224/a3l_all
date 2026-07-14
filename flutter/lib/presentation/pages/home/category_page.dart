import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/models/ad_model.dart';
import 'package:a3lnha/data/models/subcategory_model.dart';
import 'package:a3lnha/data/services/ad_service.dart';
import 'package:a3lnha/data/services/category_service.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/home/ad_details_page.dart';
import 'package:a3lnha/presentation/pages/home/ads_list_page.dart';
import 'package:a3lnha/presentation/pages/home/subcategory_page.dart';
import 'package:a3lnha/presentation/widgets/ad_list_location_label.dart';
import 'package:a3lnha/presentation/widgets/ad_status_badge_icon.dart';
import 'package:a3lnha/presentation/widgets/shared/app_network_image.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/favorite_icon_button.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class CategoryPage extends StatefulWidget {
  final int categoryId;
  final String categoryName;

  const CategoryPage({
    super.key,
    required this.categoryId,
    required this.categoryName,
  });

  @override
  State<CategoryPage> createState() => _CategoryPageState();
}

class _CategoryPageState extends State<CategoryPage> {
  List<SubcategoryModel> _subcategories = [];
  List<AdModel> _sectionAds = [];
  bool _loading = true;
  bool _adsLoading = true;

  @override
  void initState() {
    super.initState();
    _loadSubcategories();
    _loadSectionAds();
  }

  int _totalAdsCountFromSubcategories() {
    return _subcategories.fold<int>(0, (sum, s) => sum + s.adsCount);
  }

  /// إذا كانت أعداد الفرعية تشير لوجود إعلانات والمعاينة فارغة، نعيد الطلب دون كاش محلي.
  Future<void> _retrySectionAdsIfCountsMismatch() async {
    if (!mounted || _adsLoading || _sectionAds.isNotEmpty) return;
    final total = _totalAdsCountFromSubcategories();
    if (total < 1) return;
    final res = await AdService.getAds(
      categoryId: widget.categoryId,
      perPage: 8,
      forceRefresh: true,
    );
    if (mounted && res.ads.isNotEmpty) {
      setState(() => _sectionAds = res.ads);
    }
  }

  Future<void> _loadSectionAds() async {
    final res = await AdService.getAds(
      categoryId: widget.categoryId,
      perPage: 8,
      forceRefresh: true,
    );
    if (mounted) {
      setState(() {
        _sectionAds = res.ads;
        _adsLoading = false;
      });
      await _retrySectionAdsIfCountsMismatch();
    }
  }

  Future<void> _loadSubcategories() async {
    final list = await CategoryService.getSubcategories(
      widget.categoryId,
      forceRefresh: true,
    );
    if (mounted) {
      setState(() {
        _subcategories = list;
        _loading = false;
      });
      await _retrySectionAdsIfCountsMismatch();
      // عند عدم وجود أقسام فرعية نعرض قائمة الإعلانات مباشرة
      if (list.isEmpty) {
        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (!mounted) return;
          context.pushReplacement(
            AdsListPage(
              title: '${AppLocale.tr('all_ads_of')} "${widget.categoryName}"',
              categoryId: widget.categoryId,
            ),
          );
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: AppLocale.tr('choose_category')),
      body: Padding(
        padding: EdgeInsets.fromLTRB(16.w, 8.h, 16.w, 16.h),
        child: Container(
          padding: EdgeInsets.fromLTRB(16.w, 10.h, 16.w, 16.h),
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
              : Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    _SectionAdsPreview(
                      ads: _sectionAds,
                      loading: _adsLoading,
                      sectionName: widget.categoryName,
                      onSeeAll: () => context.push(AdsListPage(
                        title: '${AppLocale.tr('all_ads_of')} "${widget.categoryName}"',
                        categoryId: widget.categoryId,
                      )),
                    ),
                    SizedBox(height: 16.h),
                    Expanded(
                      child: _subcategories.isEmpty
                          ? Center(
                              child: Text(
                                AppLocale.tr('no_subcategories_in_category'),
                                style: TextStyle(
                                  fontSize: 14.sp,
                                  color: Colors.grey,
                                ),
                              ),
                            )
                          : ListView(
                              children: [
                                CategoryItem(
                                  title: '${AppLocale.tr('all_ads_of')} "${widget.categoryName}"',
                                  isAll: true,
                                  appBarTitle: widget.categoryName,
                                  categoryId: widget.categoryId,
                                  adsCount: _subcategories.fold<int>(
                                    0,
                                    (sum, s) => sum + s.adsCount,
                                  ),
                                ),
                                ..._subcategories.map(
                                  (sub) => CategoryItem(
                                    title: sub.name,
                                    isAll: false,
                                    appBarTitle: widget.categoryName,
                                    categoryId: widget.categoryId,
                                    subcategoryId: sub.id,
                                    subcategory: sub,
                                    adsCount: sub.adsCount,
                                  ),
                                ),
                              ],
                            ),
                    ),
                  ],
                ),
        ),
      ),
    );
  }
}

class CategoryItem extends StatelessWidget {
  final bool isAll;
  final String title;
  final String appBarTitle;
  final int categoryId;
  final int? subcategoryId;
  final SubcategoryModel? subcategory;
  final int adsCount;

  const CategoryItem({
    super.key,
    this.isAll = false,
    required this.title,
    required this.appBarTitle,
    required this.categoryId,
    this.subcategoryId,
    this.subcategory,
    this.adsCount = 0,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: () {
        if (isAll) {
          context.push(AdsListPage(
            title: '${AppLocale.tr('all_ads_of')} "$appBarTitle"',
            categoryId: categoryId,
          ));
        } else if (subcategoryId != null) {
          final hasChildren = subcategory?.children?.isNotEmpty ?? false;
          if (hasChildren) {
            context.push(SubcategoryPage(
              categoryId: categoryId,
              categoryName: appBarTitle,
              subcategoryId: subcategoryId!,
              subcategoryName: title,
              subtreeAdsCount: subcategory?.adsCount,
            ));
          } else {
            context.push(AdsListPage(
              title: title,
              categoryId: categoryId,
              subcategoryId: subcategoryId,
            ));
          }
        }
      },
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
                            child: (subcategory?.icon != null &&
                                    subcategory!.icon!.trim().isNotEmpty)
                                ? AppNetworkImage(
                                    imageUrl: subcategory!.icon,
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
                    "($adsCount)",
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
        SizedBox(height: 8.h),
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
            height: 192.h,
            child: ListView.builder(
              scrollDirection: Axis.horizontal,
              // بدون أي فراغ إضافي قبل أول بطاقة.
              padding: EdgeInsets.zero,
              itemCount: ads.length,
              itemBuilder: (context, index) {
                final ad = ads[index];
                return GestureDetector(
                  onTap: () => context.push(AdDetailsPage(adUid: ad.uid)),
                  child: Container(
                    width: 132.w,
                    // المسافة بين البطاقات فقط، بدون فراغ قبل أول إعلان.
                    margin: EdgeInsetsDirectional.only(start: index == 0 ? 0 : 12.w),
                    child: Container(
                      height: 184.h,
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(8.r),
                        border: Border.all(color: Colors.grey.shade300, width: 0.8),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Stack(
                            clipBehavior: Clip.hardEdge,
                            children: [
                              ClipRRect(
                                borderRadius: BorderRadius.vertical(top: Radius.circular(8.r)),
                                child: ColoredBox(
                                  color: Colors.grey[100]!,
                                  child: ad.imageUrl != null
                                      ? ListAdThumbnailImage(
                                          imageUrl: ad.imageUrl!,
                                          width: 132.w,
                                          maxHeight: 92.h,
                                          errorBuilder: (_, __) => _placeholder(),
                                        )
                                      : _placeholder(),
                                ),
                              ),
                              if (ad.isFeatured)
                                Positioned(
                                  top: 4.h,
                                  left: 4.w,
                                  child: AdStatusBadgeIcon.featured(size: 20.sp),
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
                          Expanded(
                            child: Padding(
                              padding: EdgeInsets.fromLTRB(8.w, 5.h, 8.w, 5.h),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  SizedBox(
                                    height: 48.h,
                                    child: Align(
                                      alignment: Alignment.topRight,
                                      child: Text(
                                        ad.title,
                                        style: TextStyle(
                                          fontSize: 12.sp,
                                          fontWeight: FontWeight.bold,
                                          color: Colors.black,
                                          height: 1.2,
                                        ),
                                        maxLines: 2,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ),
                                  ),
                                  SizedBox(height: 2.h),
                                  if (ad.hasLocationForList)
                                    AdListLocationLabel(
                                      key: ValueKey('${ad.uid}_loc'),
                                      ad: ad,
                                      iconSize: 9,
                                      gap: 2,
                                      style: TextStyle(
                                        fontSize: 9.5.sp,
                                        color: Colors.grey[650],
                                        height: 1.15,
                                      ),
                                      iconColor: Colors.grey[650],
                                      maxLines: 2,
                                    ),
                                  const Spacer(),
                                  if (ad.displayPriceForUi != null)
                                    Text(
                                      ad.displayPriceForUi!,
                                      style: TextStyle(
                                        fontSize: 11.5.sp,
                                        color: AppColors.darkBlue,
                                        fontWeight: FontWeight.bold,
                                      ),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                ],
                              ),
                            ),
                          ),
                        ],
                      ),
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
      width: 132.w,
      height: 92.h,
      color: Colors.grey[200],
      child: Icon(Icons.image_not_supported, color: Colors.grey[400], size: 32),
    );
  }
}
