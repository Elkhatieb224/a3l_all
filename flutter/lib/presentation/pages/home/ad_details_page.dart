import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:a3lnha/core/cache/app_image_cache.dart';
import 'package:a3lnha/core/data/currency_helper.dart';
import 'package:a3lnha/core/data/reverse_geocoding_service.dart';
import 'package:a3lnha/core/data/location_translations.dart';
import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/network/api_constants.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/core/storage/token_storage.dart';
import 'package:a3lnha/data/models/ad_model.dart';
import 'package:a3lnha/data/models/category_model.dart';
import 'package:a3lnha/helpers/custom_fields_resolver.dart';
import 'package:a3lnha/data/services/ad_service.dart';
import 'package:a3lnha/data/services/category_service.dart';
import 'package:a3lnha/data/services/negotiation_service.dart';
import 'package:a3lnha/data/services/report_service.dart';
import 'package:a3lnha/data/services/seller_service.dart';
import 'package:a3lnha/presentation/widgets/ad_list_location_label.dart';
import 'package:a3lnha/presentation/widgets/ad_status_badge_icon.dart';
import 'package:a3lnha/presentation/widgets/shared/favorite_icon_button.dart';
import 'package:a3lnha/presentation/widgets/shared/full_screen_image_viewer.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/helpers/numeral_helper.dart';
import 'package:a3lnha/presentation/pages/account/chat_page.dart';
import 'package:a3lnha/presentation/pages/home/ads_list_page.dart';
import 'package:a3lnha/presentation/pages/home/edit_ad_page.dart';
import 'package:a3lnha/presentation/pages/auth/login_page.dart';
import 'package:a3lnha/presentation/pages/payement/quta_pages.dart';
import 'package:a3lnha/presentation/pages/account/my_account_page.dart';
import 'package:a3lnha/presentation/pages/account/my_products_deals_page.dart';
import 'package:a3lnha/presentation/pages/account/seller_profile_page.dart';
import 'package:a3lnha/presentation/widgets/shared/app_network_image.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_button.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/foundation.dart' show Factory, kIsWeb;
import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:flutter_cache_manager/flutter_cache_manager.dart';
import 'package:geocoding/geocoding.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:share_plus/share_plus.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:video_player/video_player.dart';

/// دمج حقول القسم والفئة الفرعية (الأخيرة تبدّل بالمعرّف عند التعارض).
List<Map<String, dynamic>> _mergeCategorySubcategoryFieldDefinitions(
  List<Map<String, dynamic>> categoryFields,
  List<Map<String, dynamic>>? subcategoryFields,
) {
  final byId = <String, Map<String, dynamic>>{};
  void absorb(List<Map<String, dynamic>> list) {
    for (final raw in list) {
      final f = Map<String, dynamic>.from(raw);
      final id = (f['id'] ?? f['key'] ?? '').toString().trim();
      if (id.isEmpty) continue;
      byId[id] = f;
    }
  }

  absorb(categoryFields);
  if (subcategoryFields != null && subcategoryFields.isNotEmpty) {
    absorb(subcategoryFields);
  }
  return byId.values.toList();
}

List<Map<String, dynamic>>? _fieldDefinitionsFromAdPayload(AdModel ad) {
  List<Map<String, dynamic>> normalize(dynamic raw) {
    if (raw is! List) return const [];
    return raw
        .whereType<Map>()
        .map((e) => Map<String, dynamic>.from(e))
        .toList();
  }

  final catFields = normalize(ad.category?['custom_fields']);
  final subFields = normalize(ad.subcategory?['custom_fields']);
  if (catFields.isEmpty && subFields.isEmpty) return null;
  return _mergeCategorySubcategoryFieldDefinitions(
    catFields,
    subFields.isEmpty ? null : subFields,
  );
}

Future<List<Map<String, dynamic>>?> _loadMergedFieldDefinitions(AdModel ad) async {
  final rawCat = ad.category?['id'];
  if (rawCat == null) return null;
  final categoryId = rawCat is int ? rawCat : int.tryParse(rawCat.toString());
  if (categoryId == null) return null;

  Future<CategoryModel?> loadCat(bool refresh) =>
      CategoryService.getCategory(categoryId, forceRefresh: refresh);

  var cat = await loadCat(false);
  if (cat?.customFields == null || cat!.customFields!.isEmpty) {
    cat = await loadCat(true);
  }

  final catFields = cat?.customFields
          ?.map((e) => Map<String, dynamic>.from(e))
          .toList() ??
      <Map<String, dynamic>>[];

  List<Map<String, dynamic>>? subFields;
  final rawSub = ad.subcategory?['id'];
  if (rawSub != null) {
    final subId = rawSub is int ? rawSub : int.tryParse(rawSub.toString());
    if (subId != null) {
      final sub = await CategoryService.getSubcategory(subId);
      if (sub != null) {
        final resolved = sub.resolvedCustomFields;
        if (resolved != null && resolved.isNotEmpty) {
          return resolved;
        }
        final fromChain = CustomFieldsResolver.resolveForLeaf(
          leaf: sub,
          subcategoryById: {sub.id: sub},
          category: cat,
        );
        if (fromChain.isNotEmpty) {
          return fromChain;
        }
        if (sub.customFields != null && sub.customFields!.isNotEmpty) {
          subFields = sub.customFields!.map((e) => Map<String, dynamic>.from(e)).toList();
        }
      }
    }
  }

  if (catFields.isEmpty && (subFields == null || subFields.isEmpty)) {
    return null;
  }
  return _mergeCategorySubcategoryFieldDefinitions(catFields, subFields);
}

String _localizedCustomFieldLabel(dynamic label, String locale) {
  if (label == null) return '';
  if (label is String) {
    final t = label.trim();
    if (t.startsWith('{') && t.endsWith('}')) {
      try {
        final d = jsonDecode(t);
        return _localizedCustomFieldLabel(d, locale);
      } catch (_) {}
    }
    return t;
  }
  if (label is Map) {
    final m = label.map((k, v) => MapEntry(k.toString(), v));
    final v = m[locale] ?? m['ar'] ?? m['en'] ?? m['tr'];
    if (v != null && v.toString().trim().isNotEmpty) {
      return v.toString();
    }
  }
  return '';
}

bool _customFieldDefMatchesKey(Map<String, dynamic> f, String key) {
  final k = key.trim();
  if (k.isEmpty) return false;
  for (final raw in [f['id'], f['key'], f['field_key'], f['name']]) {
    if (raw == null) continue;
    if (raw.toString().trim() == k) return true;
  }
  return false;
}

class AdDetailsPage extends StatefulWidget {
  final String? adUid;
  final bool useMyAdApi;

  const AdDetailsPage({super.key, this.adUid, this.useMyAdApi = false});

  @override
  State<AdDetailsPage> createState() => _AdDetailsPageState();
}

class _AdDetailsPageState extends State<AdDetailsPage> {
  final PageController _pageController = PageController();
  final TextEditingController _newPriceController = TextEditingController();

  AdModel? _ad;
  List<AdModel> _relatedAds = [];
  PromoteActions? _promoteActions;
  bool _loading = true;
  String? _error;
  int _selectedTab = 1;
  int _currentImageIndex = 0;

  @override
  void initState() {
    super.initState();
    if (widget.adUid != null) {
      _loadAdDetails();
    } else {
      setState(() {
        _loading = false;
        _error = AppLocale.tr('ad_not_found');
      });
    }
  }

  Future<void> _loadAdDetails({bool forceRefresh = false}) async {
    if (widget.adUid == null) return;

    setState(() {
      _loading = true;
      _error = null;
    });

    final response = widget.useMyAdApi
        ? await AdService.getMyAdDetails(widget.adUid!)
        : await AdService.getAdDetails(widget.adUid!, forceRefresh: forceRefresh);

    if (!mounted) return;

    if (response != null) {
      final ad = response.ad;
      if (!mounted) return;
      setState(() {
        _ad = ad;
        _relatedAds = response.relatedAds;
        _promoteActions = response.promoteActions;
        _loading = false;
      });
      final sellerSlug = ad.user?['slug'] as String?;
      if (sellerSlug != null && sellerSlug.trim().isNotEmpty && !widget.useMyAdApi) {
        SellerService.getSellerProfile(sellerSlug).then((_) {});
      }
    } else {
      setState(() {
        _loading = false;
        _error = AppLocale.tr('ad_load_failed');
      });
    }
  }

  Future<void> _shareAd(AdModel ad) async {
    final origin = ApiConstants.webOrigin;
    final path = '/ads/${ad.uid}';
    final url = origin.endsWith('/') ? '$origin${path.substring(1)}' : '$origin$path';
    final text = ad.title.trim().isEmpty ? url : '${ad.title.trim()}\n$url';
    final subject = ad.title.trim().isEmpty ? null : ad.title.trim();

    // Prefer sharing ad image with title/link, fallback to text-only if unavailable.
    final primaryImage = (ad.imageUrl ?? '').trim();
    if (primaryImage.isNotEmpty && !kIsWeb) {
      try {
        final file = await DefaultCacheManager().getSingleFile(primaryImage);
        final tempDir = await Directory.systemTemp.createTemp('ad_share_');
        final ext = file.path.contains('.') ? file.path.split('.').last : 'jpg';
        final out = File('${tempDir.path}/ad_${ad.uid}.$ext');
        final sharedFile = await file.copy(out.path);
        await Share.shareXFiles([XFile(sharedFile.path)], text: text, subject: subject);
        return;
      } catch (_) {}
    }

    await Share.share(text, subject: subject);
  }

  void _showReportDialog(AdModel ad) {
    showDialog(
      context: context,
      builder: (ctx) => _ReportAdDialog(
        adId: ad.id,
        onSuccess: () => Navigator.pop(ctx),
      ),
    );
  }

  void _showNewPriceDialog(BuildContext context) {
    final ad = _ad;
    if (ad == null) return;
    if (!TokenStorage.hasToken()) {
      showToast(message: AppLocale.tr('login_to_negotiate'));
      return;
    }
    _newPriceController.clear();
    showDialog(
      context: context,
      builder: (ctx) => _NegotiatePriceDialog(
        adUid: ad.uid,
        adCurrency: ad.currency ?? 'SYP',
        priceController: _newPriceController,
        onSuccess: () {
          Navigator.pop(ctx);
          showToast(message: AppLocale.tr('negotiation_sent'));
          context.push(const MyProductsDealsPage());
        },
      ),
    );
  }

  @override
  void dispose() {
    _pageController.dispose();
    _newPriceController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return Scaffold(
        appBar: CustomAppbar(title: AppLocale.tr('ad_details')),
        body: Center(
          child: CircularProgressIndicator(color: AppColors.darkBlue),
        ),
      );
    }

    if (_error != null || _ad == null) {
      return Scaffold(
        appBar: CustomAppbar(title: AppLocale.tr('ad_details')),
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.error_outline, size: 64.sp, color: Colors.grey),
              SizedBox(height: 16.h),
              Text(
                _error ?? AppLocale.tr('ad_not_available'),
                style: TextStyle(fontSize: 16.sp, color: Colors.grey[600]),
                textAlign: TextAlign.center,
              ),
              SizedBox(height: 24.h),
              CustomButton(
                text: AppLocale.tr('back'),
                onTap: () => Navigator.pop(context),
              ),
            ],
          ),
        ),
      );
    }

    final ad = _ad!;
    final headerCategoryBreadcrumb = _headerCategoryBreadcrumbIfAny(context, ad);

    return Scaffold(
      appBar: CustomAppbar(
        title: ad.title,
        // تصغير خط عنوان الإعلان في الأعلى لتجنب أخذ مساحة كبيرة مع العناوين الطويلة.
        titleFontSize: 15.sp,
      ),
      body: SingleChildScrollView(
        physics: const BouncingScrollPhysics(),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (_isOwner(ad) && ad.status == 'pending')
              Container(
                width: double.infinity,
                padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 12.h),
                margin: EdgeInsets.symmetric(horizontal: 20.w, vertical: 8.h),
                decoration: BoxDecoration(
                  color: Colors.orange.shade50,
                  borderRadius: BorderRadius.circular(12.r),
                ),
                child: Row(
                  children: [
                    Icon(Icons.schedule, color: Colors.orange.shade700, size: 22.sp),
                    SizedBox(width: 10.w),
                    Expanded(
                      child: Text(
                        AppLocale.tr('ad_pending_edit_review'),
                        style: TextStyle(
                          fontSize: 13.sp,
                          color: Colors.orange.shade900,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            _buildImageSlider(ad),
            Padding(
              padding: EdgeInsets.only(left: 20.w, right: 20.w, top: 8.h, bottom: 4.h),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (ad.user != null)
                    _SellerInfo(user: ad.user!, isOwner: _isOwner(ad)),
                  SizedBox(height: 4.h),
                  if (ad.isFeatured)
                    Padding(
                      padding: EdgeInsets.only(bottom: 4.h),
                      child: Container(
                        padding: EdgeInsets.symmetric(horizontal: 12.w, vertical: 8.h),
                        decoration: BoxDecoration(
                          color: Colors.amber.shade700,
                          borderRadius: BorderRadius.circular(20.r),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black26,
                              blurRadius: 4,
                              offset: const Offset(0, 1),
                            ),
                          ],
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            AdStatusBadgeIcon.featured(size: 20.sp),
                            SizedBox(width: 6.w),
                            Text(
                              AppLocale.tr('featured_ads'),
                              style: TextStyle(
                                color: Colors.white,
                                fontSize: 13.sp,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  if (headerCategoryBreadcrumb != null) ...[
                    SizedBox(height: 2.h),
                    headerCategoryBreadcrumb,
                  ],
                  SizedBox(height: 8.h),
                  if (!_isOwner(ad) && ad.price != null && ad.canNegotiatePrice)
                    CustomButton(
                      text: AppLocale.tr('negotiate_price'),
                      onTap: () => _showNewPriceDialog(context),
                      backgroundColor: AppColors.darkBlue,
                      textColor: Colors.white,
                    ),
                  if (!_isOwner(ad) && ad.price != null && ad.canNegotiatePrice)
                    SizedBox(height: 8.h),
                  SizedBox(height: 6.h),
                  Row(
                    children: [
                      Expanded(
                        child: Padding(
                          padding: EdgeInsets.only(left: 4.w),
                          child: _TabButton(
                            title: AppLocale.tr('ad_details'),
                            isSelected: _selectedTab == 1,
                            onTap: () => setState(() => _selectedTab = 1),
                          ),
                        ),
                      ),
                      SizedBox(width: 8.w),
                      Expanded(
                        child: _TabButton(
                          title: AppLocale.tr('description'),
                          isSelected: _selectedTab == 2,
                          onTap: () => setState(() => _selectedTab = 2),
                        ),
                      ),
                      SizedBox(width: 8.w),
                      Expanded(
                        child: Padding(
                          padding: EdgeInsets.only(right: 4.w),
                          child: _TabButton(
                            title: AppLocale.tr('location'),
                            isSelected: _selectedTab == 3,
                            onTap: () => setState(() => _selectedTab = 3),
                          ),
                        ),
                      ),
                    ],
                  ),
                  SizedBox(height: 10.h),
                  if (_selectedTab == 1)
                    _DetailsTab(
                      key: ObjectKey(ad),
                      ad: ad,
                    )
                  else if (_selectedTab == 2)
                    _DescriptionTab(ad: ad)
                  else
                    _LocationTab(ad: ad),
                  SizedBox(height: 24.h),
                  if (_isOwner(ad)) ...[
                    CustomButton(
                      text: AppLocale.tr('edit_ad'),
                      onTap: () => _openEditAd(ad),
                      backgroundColor: AppColors.darkBlue,
                      textColor: Colors.white,
                    ),
                    if (ad.status == 'suspended')
                      Padding(
                        padding: EdgeInsets.only(top: 12.h),
                        child: CustomButton(
                          text: AppLocale.tr('unsuspend_ad'),
                          onTap: () => _unsuspendAd(ad),
                          backgroundColor: Colors.green,
                          textColor: Colors.white,
                        ),
                      )
                    else if (ad.status == 'active' || ad.status == 'pending')
                      Padding(
                        padding: EdgeInsets.only(top: 12.h),
                        child: CustomButton(
                          text: AppLocale.tr('suspend_ad'),
                          onTap: () => _suspendAd(ad),
                          backgroundColor: Colors.orange,
                          textColor: Colors.white,
                        ),
                      ),
                    if (ad.status == 'active' && _promoteActions != null) ...[
                      SizedBox(height: 12.h),
                      _PromoteActionsSection(
                        promote: _promoteActions!,
                        onAddFeatured: () => _setFeatured(ad, remove: false),
                        onRemoveFeatured: () => _setFeatured(ad, remove: true),
                        onAddUrgent: () => _setUrgent(ad, remove: false),
                        onRemoveUrgent: () => _setUrgent(ad, remove: true),
                      ),
                    ],
                    Padding(
                      padding: EdgeInsets.only(top: 12.h),
                      child: OutlinedButton(
                        onPressed: () => _deleteAd(ad),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: Colors.red,
                          side: BorderSide(color: Colors.red),
                        ),
                        child: Text(AppLocale.tr('delete_ad')),
                      ),
                    ),
                  ] else ...[
                    _ContactRow(ad: ad),
                    if (TokenStorage.hasToken()) ...[
                    SizedBox(height: 12.h),
                    GestureDetector(
                      onTap: () => _showReportDialog(ad),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.flag_outlined, size: 18.sp, color: Colors.red),
                          SizedBox(width: 6.w),
                          Text(
                            AppLocale.tr('report_about_ad'),
                            style: TextStyle(
                              fontSize: 14.sp,
                              color: Colors.red,
                              decoration: TextDecoration.underline,
                            ),
                          ),
                        ],
                      ),
                    ),
                    ],
                  ],
                  if (_relatedAds.isNotEmpty) ...[
                    SizedBox(height: 24.h),
                    Text(
                      AppLocale.tr('similar_ads'),
                      style: TextStyle(
                        fontSize: 16.sp,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    SizedBox(height: 12.h),
                    ..._relatedAds.take(4).map(
                          (related) => _RelatedAdCard(
                            ad: related,
                            onTap: () {
                              Navigator.pushReplacement(
                                context,
                                MaterialPageRoute(
                                  builder: (_) => AdDetailsPage(
                                    adUid: related.uid,
                                  ),
                                ),
                              );
                            },
                          ),
                        ),
                  ],
                  SizedBox(height: 24.h),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  /// مسار الفئة تحت عنوان الإعلان (بدل تكرار السعر في الهيدر).
  Widget? _headerCategoryBreadcrumbIfAny(BuildContext context, AdModel ad) {
    final segments = ad.resolvedCategoryPathSegments;
    if (segments.isNotEmpty) {
      return SizedBox(
        width: double.infinity,
        child: Wrap(
          alignment: WrapAlignment.center,
          spacing: 0,
          runSpacing: 2.h,
          crossAxisAlignment: WrapCrossAlignment.center,
          children: [
            for (int i = 0; i < segments.length; i++) ...[
              if (i > 0)
                Text(
                  ' › ',
                  style: TextStyle(
                    color: Colors.grey[500],
                    fontSize: 13.sp,
                    height: 1.2,
                  ),
                ),
              if (segments[i].isTappable)
                TextButton(
                  onPressed: () =>
                      _openCategoryPathSegment(context, segments[i]),
                  style: TextButton.styleFrom(
                    padding: EdgeInsets.zero,
                    minimumSize: Size.zero,
                    tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                    foregroundColor: AppColors.darkBlue,
                  ),
                  child: Text(
                    segments[i].name,
                    style: TextStyle(
                      fontSize: 13.sp,
                      fontWeight: FontWeight.w600,
                      decoration: TextDecoration.underline,
                      decorationColor: AppColors.darkBlue,
                    ),
                  ),
                )
              else
                Text(
                  segments[i].name,
                  style: TextStyle(
                    color: Colors.grey[800],
                    fontSize: 13.sp,
                    fontWeight: FontWeight.w500,
                  ),
                ),
            ],
          ],
        ),
      );
    }
    final path = ad.categoryPath;
    if (path != null && path.isNotEmpty) {
      return SizedBox(
        width: double.infinity,
        child: Text(
          path.join(' > '),
          style: TextStyle(
            color: AppColors.darkBlue,
            fontSize: 14.sp,
            fontWeight: FontWeight.w600,
          ),
          textAlign: TextAlign.center,
        ),
      );
    }
    final cat = ad.category?['name'] as String?;
    final sub = ad.subcategory?['name'] as String?;
    final parts = <String>[];
    if (cat != null && cat.isNotEmpty) parts.add(cat);
    if (sub != null && sub.isNotEmpty) parts.add(sub);
    if (parts.isEmpty) return null;
    return SizedBox(
      width: double.infinity,
      child: Text(
        parts.join(' › '),
        style: TextStyle(
          color: AppColors.darkBlue,
          fontSize: 14.sp,
          fontWeight: FontWeight.w600,
        ),
        textAlign: TextAlign.center,
      ),
    );
  }

  Widget _buildImageSlider(AdModel ad) {
    final images = ad.images;
    final videoUrl = ad.videoUrl?.trim();
    final videoUrlNonEmpty = (videoUrl != null && videoUrl.isNotEmpty) ? videoUrl : null;

    final Widget? videoActionButton = videoUrlNonEmpty != null
        ? Material(
            color: AppColors.darkBlue,
            elevation: 3,
            borderRadius: BorderRadius.circular(999),
            child: InkWell(
              borderRadius: BorderRadius.circular(999),
              onTap: () => _openAdVideo(videoUrlNonEmpty),
              child: Padding(
                padding: EdgeInsets.symmetric(horizontal: 8.w, vertical: 5.h),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      width: 18.r,
                      height: 18.r,
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.2),
                        shape: BoxShape.circle,
                      ),
                      alignment: Alignment.center,
                      child: Icon(
                        Icons.play_arrow_rounded,
                        color: Colors.white,
                        size: 12.sp,
                      ),
                    ),
                    SizedBox(width: 6.w),
                    Text(
                      AppLocale.tr('watch_ad_video'),
                      style: TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.w700,
                        fontSize: 10.sp,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          )
        : null;

    if (images.isEmpty) {
      return Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Stack(
            children: [
              Container(
                width: double.infinity,
                height: 200.h,
                color: Colors.grey[200],
                child: Icon(
                  Icons.image_not_supported,
                  size: 64.sp,
                  color: Colors.grey[400],
                ),
              ),
              if (!_isOwner(ad))
                Positioned(
                  top: 12.h,
                  left: 12.w,
                  child: FavoriteIconButton(
                    adUid: ad.uid,
                    initialIsFavorite: ad.isFavorite,
                    size: 24.sp,
                  ),
                ),
              Positioned(
                top: 12.h,
                right: 12.w,
                child: _shareOverlayButton(ad),
              ),
            ],
          ),
          if (videoActionButton != null)
            Padding(
              padding: EdgeInsets.only(top: 8.h, left: 16.w),
              child: videoActionButton,
            ),
        ],
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Stack(
          children: [
            SizedBox(
              height: 250.h,
              child: Column(
                children: [
                  Expanded(
                    child: PageView.builder(
                      controller: _pageController,
                      itemCount: images.length,
                      onPageChanged: (i) => setState(() => _currentImageIndex = i),
                      itemBuilder: (context, index) {
                        return GestureDetector(
                          onTap: () => FullScreenImageViewer.show(
                            context,
                            imageUrls: images,
                            initialIndex: index,
                          ),
                          child: Semantics(
                            label: AppLocale.tr('tap_to_view_fullscreen'),
                            button: true,
                            child: Stack(
                              fit: StackFit.expand,
                              children: [
                                Container(
                                  margin: EdgeInsets.fromLTRB(16.w, 6.h, 16.w, 4.h),
                                  decoration: BoxDecoration(
                                    color: Colors.grey[100],
                                    borderRadius: BorderRadius.circular(12.r),
                                  ),
                                  clipBehavior: Clip.antiAlias,
                                  child: CachedUrlImage(
                                    imageUrl: images[index],
                                    fit: BoxFit.contain,
                                    width: double.infinity,
                                    height: double.infinity,
                                  ),
                                ),
                                if (images.length > 1)
                                  Positioned(
                                    bottom: 16.h,
                                    right: 24.w,
                                    child: Container(
                                      padding: EdgeInsets.symmetric(horizontal: 10.w, vertical: 6.h),
                                      decoration: BoxDecoration(
                                        color: Colors.black54,
                                        borderRadius: BorderRadius.circular(8.r),
                                      ),
                                      child: Text(
                                        '${_currentImageIndex + 1}/${images.length}',
                                        style: TextStyle(
                                          color: Colors.white,
                                          fontSize: 12.sp,
                                          fontWeight: FontWeight.w800,
                                          height: 1.1,
                                        ),
                                      ),
                                    ),
                                  ),
                              ],
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                  SizedBox(height: 6.h),
                ],
              ),
            ),
            if (!_isOwner(ad))
              Positioned(
                top: 12.h,
                left: 12.w,
                child: FavoriteIconButton(
                  adUid: ad.uid,
                  initialIsFavorite: ad.isFavorite,
                  size: 24.sp,
                ),
              ),
            Positioned(
              top: 12.h,
              right: 12.w,
              child: _shareOverlayButton(ad),
            ),
          ],
        ),
        if (videoActionButton != null)
          Padding(
            padding: EdgeInsets.only(top: 6.h, left: 16.w),
            child: videoActionButton,
          ),
      ],
    );
  }

  void _openAdVideo(String url) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.black,
      builder: (ctx) {
        final top = MediaQuery.paddingOf(ctx).top;
        return Padding(
          padding: EdgeInsets.only(top: top),
          child: SizedBox(
            height: MediaQuery.sizeOf(ctx).height * 0.92,
            child: _AdFullVideoSheet(url: url),
          ),
        );
      },
    );
  }

  Widget _shareOverlayButton(AdModel ad) {
    return Material(
      color: Colors.white,
      elevation: 2,
      borderRadius: BorderRadius.circular(999),
      child: InkWell(
        borderRadius: BorderRadius.circular(999),
        onTap: () => _shareAd(ad),
        child: Padding(
          padding: EdgeInsets.all(8.w),
          child: Icon(
            Icons.share_outlined,
            size: 20.sp,
            color: AppColors.darkBlue,
          ),
        ),
      ),
    );
  }

  bool _isOwner(AdModel ad) => widget.useMyAdApi || (ad.isOwner);

  Future<void> _openEditAd(AdModel ad) async {
    if (!TokenStorage.hasToken()) {
      context.push(LoginPage());
      return;
    }
    context.push(EditAdPage(adUid: ad.uid)).then((_) {
      if (mounted) _loadAdDetails(forceRefresh: true);
    });
  }

  Future<void> _suspendAd(AdModel ad) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(AppLocale.tr('suspend_ad')),
        content: Text(AppLocale.tr('confirm_suspend_ad')),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: Text(MaterialLocalizations.of(ctx).cancelButtonLabel)),
          TextButton(onPressed: () => Navigator.pop(ctx, true), child: Text(MaterialLocalizations.of(ctx).okButtonLabel)),
        ],
      ),
    );
    if (confirm != true || !mounted) return;
    final result = await AdService.suspendAd(ad.uid);
    if (!mounted) return;
    showToast(message: result['message'] as String? ?? AppLocale.tr('suspend_ad'));
    if (result['success'] == true) _loadAdDetails(forceRefresh: true);
  }

  Future<void> _unsuspendAd(AdModel ad) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(AppLocale.tr('unsuspend_ad')),
        content: Text(AppLocale.tr('confirm_unsuspend_ad')),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: Text(MaterialLocalizations.of(ctx).cancelButtonLabel)),
          TextButton(onPressed: () => Navigator.pop(ctx, true), child: Text(MaterialLocalizations.of(ctx).okButtonLabel)),
        ],
      ),
    );
    if (confirm != true || !mounted) return;
    final result = await AdService.unsuspendAd(ad.uid);
    if (!mounted) return;
    if (result['redirect_to'] == 'packages') {
      showToast(message: AppLocale.tr('unsuspend_limit_redirect'));
      context.push(QutaPages());
      return;
    }
    showToast(message: result['message'] as String? ?? AppLocale.tr('unsuspend_ad'));
    if (result['success'] == true) _loadAdDetails(forceRefresh: true);
  }

  Future<void> _setFeatured(AdModel ad, {required bool remove}) async {
    if (remove) {
      final confirm = await showDialog<bool>(
        context: context,
        builder: (ctx) => AlertDialog(
          title: Text(AppLocale.tr('remove_featured')),
          content: Text(AppLocale.tr('confirm_remove_featured')),
          actions: [
            TextButton(onPressed: () => Navigator.pop(ctx, false), child: Text(MaterialLocalizations.of(ctx).cancelButtonLabel)),
            TextButton(onPressed: () => Navigator.pop(ctx, true), child: Text(MaterialLocalizations.of(ctx).okButtonLabel)),
          ],
        ),
      );
      if (confirm != true || !mounted) return;
    }
    final result = await AdService.setFeatured(ad.uid);
    if (!mounted) return;
    showToast(message: result['message'] as String? ?? '');
    if (result['success'] == true) _loadAdDetails(forceRefresh: true);
  }

  Future<void> _setUrgent(AdModel ad, {required bool remove}) async {
    if (remove) {
      final confirm = await showDialog<bool>(
        context: context,
        builder: (ctx) => AlertDialog(
          title: Text(AppLocale.tr('remove_urgent')),
          content: Text(AppLocale.tr('confirm_remove_urgent')),
          actions: [
            TextButton(onPressed: () => Navigator.pop(ctx, false), child: Text(MaterialLocalizations.of(ctx).cancelButtonLabel)),
            TextButton(onPressed: () => Navigator.pop(ctx, true), child: Text(MaterialLocalizations.of(ctx).okButtonLabel)),
          ],
        ),
      );
      if (confirm != true || !mounted) return;
    }
    final result = await AdService.setUrgent(ad.uid);
    if (!mounted) return;
    showToast(message: result['message'] as String? ?? '');
    if (result['success'] == true) _loadAdDetails(forceRefresh: true);
  }

  Future<void> _deleteAd(AdModel ad) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(AppLocale.tr('delete_ad')),
        content: Text(AppLocale.tr('confirm_delete_ad')),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: Text(MaterialLocalizations.of(ctx).cancelButtonLabel)),
          TextButton(onPressed: () => Navigator.pop(ctx, true), child: Text(MaterialLocalizations.of(ctx).okButtonLabel)),
        ],
      ),
    );
    if (confirm != true || !mounted) return;
    final result = await AdService.deleteAd(ad.uid);
    if (!mounted) return;
    showToast(message: result['message'] as String? ?? AppLocale.tr('delete_ad'));
    if (result['success'] == true) Navigator.of(context).pop();
  }
}

class _PromoteActionsSection extends StatelessWidget {
  final PromoteActions promote;
  final VoidCallback? onAddFeatured;
  final VoidCallback? onRemoveFeatured;
  final VoidCallback? onAddUrgent;
  final VoidCallback? onRemoveUrgent;

  const _PromoteActionsSection({
    required this.promote,
    this.onAddFeatured,
    this.onRemoveFeatured,
    this.onAddUrgent,
    this.onRemoveUrgent,
  });

  @override
  Widget build(BuildContext context) {
    final showFeaturedAddDisabled = !promote.canAddFeatured && !promote.canRemoveFeatured;
    final showUrgentAddDisabled = !promote.canAddUrgent && !promote.canRemoveUrgent;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (promote.canAddFeatured || promote.canRemoveFeatured || showFeaturedAddDisabled) ...[
          if (promote.canAddFeatured)
            Padding(
              padding: EdgeInsets.only(bottom: 8.h),
              child: OutlinedButton.icon(
                onPressed: onAddFeatured,
                icon: AdStatusBadgeIcon.featured(size: 18.sp),
                label: Text(
                  '${AppLocale.tr('add_featured')} (${promote.remainingFeatured})',
                  style: TextStyle(fontSize: 14.sp),
                ),
                style: OutlinedButton.styleFrom(
                  foregroundColor: Colors.amber.shade700,
                  side: BorderSide(color: Colors.amber.shade700),
                ),
              ),
            ),
          if (showFeaturedAddDisabled)
            Padding(
              padding: EdgeInsets.only(bottom: 8.h),
              child: OutlinedButton.icon(
                onPressed: null,
                icon: AdStatusBadgeIcon.featured(size: 18.sp),
                label: Text(
                  '${AppLocale.tr('add_featured')} - ${AppLocale.tr('no_promote_quota')}',
                  style: TextStyle(fontSize: 14.sp, color: Colors.grey),
                ),
                style: OutlinedButton.styleFrom(
                  foregroundColor: Colors.grey,
                  side: BorderSide(color: Colors.grey.shade400),
                ),
              ),
            ),
          if (promote.canRemoveFeatured)
            Padding(
              padding: EdgeInsets.only(bottom: 8.h),
              child: OutlinedButton.icon(
                onPressed: onRemoveFeatured,
                icon: Icon(Icons.star_outline, size: 18.sp, color: Colors.grey.shade700),
                label: Text(AppLocale.tr('remove_featured'), style: TextStyle(fontSize: 14.sp)),
                style: OutlinedButton.styleFrom(
                  foregroundColor: Colors.grey.shade700,
                  side: BorderSide(color: Colors.grey.shade700),
                ),
              ),
            ),
        ],
        if (promote.canAddUrgent || promote.canRemoveUrgent || showUrgentAddDisabled) ...[
          if (promote.canAddUrgent)
            Padding(
              padding: EdgeInsets.only(bottom: 8.h),
              child: OutlinedButton.icon(
                onPressed: onAddUrgent,
                icon: AdStatusBadgeIcon.urgent(size: 18.sp),
                label: Text(
                  '${AppLocale.tr('add_urgent')} (${promote.remainingUrgent})',
                  style: TextStyle(fontSize: 14.sp),
                ),
                style: OutlinedButton.styleFrom(
                  foregroundColor: Colors.orange.shade700,
                  side: BorderSide(color: Colors.orange.shade700),
                ),
              ),
            ),
          if (showUrgentAddDisabled)
            Padding(
              padding: EdgeInsets.only(bottom: 8.h),
              child: OutlinedButton.icon(
                onPressed: null,
                icon: AdStatusBadgeIcon.urgent(size: 18.sp),
                label: Text(
                  '${AppLocale.tr('add_urgent')} - ${AppLocale.tr('no_promote_quota')}',
                  style: TextStyle(fontSize: 14.sp, color: Colors.grey),
                ),
                style: OutlinedButton.styleFrom(
                  foregroundColor: Colors.grey,
                  side: BorderSide(color: Colors.grey.shade400),
                ),
              ),
            ),
          if (promote.canRemoveUrgent)
            Padding(
              padding: EdgeInsets.only(bottom: 8.h),
              child: OutlinedButton.icon(
                onPressed: onRemoveUrgent,
                icon: AdStatusBadgeIcon.urgent(size: 18.sp),
                label: Text(AppLocale.tr('remove_urgent'), style: TextStyle(fontSize: 14.sp)),
                style: OutlinedButton.styleFrom(
                  foregroundColor: Colors.grey.shade700,
                  side: BorderSide(color: Colors.grey.shade700),
                ),
              ),
            ),
        ],
      ],
    );
  }
}

class _SellerInfo extends StatelessWidget {
  final Map<String, dynamic> user;
  final bool isOwner;

  const _SellerInfo({required this.user, this.isOwner = false});

  @override
  Widget build(BuildContext context) {
    final name = user['name'] as String? ?? '—';
    final avatar = user['avatar'] as String?;
    final slug = user['slug'] as String?;

    return GestureDetector(
      onTap: () {
        if (isOwner) {
          context.push(MyAccountPage());
          return;
        }
        if (slug != null && slug.isNotEmpty) {
          context.push(SellerProfilePage(sellerSlug: slug));
        }
      },
      child: Row(
        children: [
          CircleAvatar(
            radius: 24.r,
            backgroundColor: Colors.grey[300],
            backgroundImage: avatar != null
                ? CachedNetworkImageProvider(
                    avatar,
                    cacheManager: AppImageCache.instance,
                  )
                : null,
            child: avatar == null
                ? Text(
                    name.isNotEmpty ? name[0].toUpperCase() : '?',
                    style: TextStyle(fontSize: 18.sp, color: AppColors.darkBlue),
                  )
                : null,
          ),
          SizedBox(width: 14.w),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        name,
                        style: TextStyle(
                          fontSize: 14.sp,
                          fontWeight: FontWeight.w600,
                          height: 1.2,
                        ),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    if (user['is_verified'] == true) ...[
                      SizedBox(width: 4.w),
                      Icon(Icons.verified, size: 16.sp, color: Colors.blue),
                    ],
                  ],
                ),
                SizedBox(height: 8.h),
                Text(
                  isOwner ? AppLocale.tr('my_profile') : AppLocale.tr('view_profile'),
                  style: TextStyle(
                    fontSize: 12.sp,
                    color: AppColors.lightBlue,
                    height: 1.2,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
          ),
          Icon(Icons.arrow_forward_ios, size: 14.sp, color: Colors.grey),
        ],
      ),
    );
  }
}

class _TabButton extends StatelessWidget {
  final String title;
  final bool isSelected;
  final VoidCallback onTap;

  const _TabButton({
    required this.title,
    required this.isSelected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: double.infinity,
        padding: EdgeInsets.symmetric(horizontal: 8.w, vertical: 12.h),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(10.r),
          color: isSelected ? AppColors.darkBlue : Colors.grey[300],
        ),
        alignment: Alignment.center,
        child: Text(
          title,
          style: TextStyle(
            fontSize: 12.sp,
            fontWeight: FontWeight.w600,
            color: isSelected ? Colors.white : Colors.black87,
          ),
          textAlign: TextAlign.center,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
      ),
    );
  }
}

class _DetailsTab extends StatefulWidget {
  final AdModel ad;

  const _DetailsTab({super.key, required this.ad});

  @override
  State<_DetailsTab> createState() => _DetailsTabState();
}

class _DetailsTabState extends State<_DetailsTab> {
  List<Map<String, dynamic>>? _fieldDefinitions;

  @override
  void initState() {
    super.initState();
    _fieldDefinitions = _fieldDefinitionsFromAdPayload(widget.ad);
    _loadFieldDefinitions();
  }

  @override
  void didUpdateWidget(covariant _DetailsTab oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.ad.uid != widget.ad.uid ||
        oldWidget.ad.category != widget.ad.category ||
        oldWidget.ad.subcategory != widget.ad.subcategory) {
      _fieldDefinitions = _fieldDefinitionsFromAdPayload(widget.ad);
      _loadFieldDefinitions();
    }
  }

  Future<void> _loadFieldDefinitions() async {
    final merged = await _loadMergedFieldDefinitions(widget.ad);
    if (!mounted) return;
    setState(() {
      _fieldDefinitions = merged;
    });
  }

  String _getFieldLabel(String key) {
    if (_fieldDefinitions == null) return key;
    for (final f in _fieldDefinitions!) {
      final map = Map<String, dynamic>.from(f);
      if (!_customFieldDefMatchesKey(map, key)) continue;
      final localized =
          _localizedCustomFieldLabel(map['label'], AppLocale.current);
      if (localized.isNotEmpty) return localized;
      break;
    }
    return key;
  }

  String? _detailStr(dynamic v) {
    if (v == null) return null;
    final s = v.toString().trim();
    return s.isEmpty ? null : s;
  }

  /// خريطة بإحداثيات فقط (بدون نص منطقة) — نجلب العنوان من الخدمة الجغرافية.
  ({double lat, double lng})? _coordinateOnlyPairForGeocode(dynamic val) {
    if (val is! Map) return null;
    final m = Map<String, dynamic>.from(val);
    final addrRaw = m['address'];
    if (addrRaw != null && addrRaw.toString().trim().isNotEmpty) return null;

    final numVal = m['value'];
    final hasCurrency =
        m.containsKey('currency') && m['currency'] != null && m['currency'].toString().trim().isNotEmpty;
    if (numVal != null && hasCurrency) return null;

    final fromCf = LocationTranslations.formatCountryProvinceDistrict(
      AppLocale.current,
      country: _detailStr(m['country'] ?? m['location_country']),
      city: _detailStr(m['city'] ?? m['location_city'] ?? m['province']),
      district: _detailStr(m['district'] ?? m['location_district'] ?? m['area']),
    );
    if (fromCf.isNotEmpty) return null;

    final fromAd = LocationTranslations.formatCountryProvinceDistrict(
      AppLocale.current,
      country: widget.ad.locationCountry,
      city: widget.ad.locationCity,
      district: widget.ad.locationDistrict,
    );
    if (fromAd.isNotEmpty && widget.ad.effectiveMapPosition == null) return null;

    final lat = m['latitude'] ?? m['lat'];
    final lng = m['longitude'] ?? m['lng'];
    if (lat == null || lng == null) return null;
    final la = double.tryParse(lat.toString().trim().replaceAll(',', '.'));
    final ln = double.tryParse(lng.toString().trim().replaceAll(',', '.'));
    if (la == null || ln == null || !la.isFinite || !ln.isFinite) return null;

    return (lat: la, lng: ln);
  }

  bool _isLikelyPriceCustomFieldKey(String key) {
    final k = key.toLowerCase().trim();
    if (k == 'price' || k.endsWith('_price')) return true;
    if (k.contains('price') || k.contains('fiyat')) return true;
    return false;
  }

  bool _isSalaryCustomFieldKey(String key) {
    final k = key.toLowerCase().trim();
    if (k == 'salary') return true;
    if (k.endsWith('_salary')) return true;
    return false;
  }

  String _formatCustomValue(dynamic val) {
    if (val == null) return '—';
    if (val is Map) {
      final m = Map<String, dynamic>.from(val);
      final addrRaw = m['address'];
      if (addrRaw != null && addrRaw.toString().trim().isNotEmpty) {
        return addrRaw.toString().trim();
      }
      final numVal = m['value'];
      final currency = m['currency'] as String?;
      final isTbd = m['tbd'] == true || m['tbd'] == '1';
      final isEmptyNum = numVal == null || numVal.toString().trim().isEmpty;
      if (isTbd || (isEmptyNum && (currency != null || m.containsKey('currency')))) {
        return AppLocale.tr('price_tbd');
      }
      if (numVal != null && (currency != null || m.containsKey('currency'))) {
        final n = numVal is num ? numVal : NumeralHelper.parseAmount(numVal.toString());
        final cur = currency ?? (m['currency'] as String?);
        if (n != null && cur != null && cur.isNotEmpty) {
          return CurrencyHelper.formatPrice(n, cur);
        }
      }
      final lat = m['latitude'] ?? m['lat'];
      final lng = m['longitude'] ?? m['lng'];
      if (lat != null && lng != null) {
        final locale = AppLocale.current;
        final fromCf = LocationTranslations.formatCountryProvinceDistrict(
          locale,
          country: _detailStr(m['country'] ?? m['location_country']),
          city: _detailStr(m['city'] ?? m['location_city'] ?? m['province']),
          district: _detailStr(m['district'] ?? m['location_district'] ?? m['area']),
        );
        if (fromCf.isNotEmpty) return fromCf;
        final fromAd = LocationTranslations.formatCountryProvinceDistrict(
          locale,
          country: widget.ad.locationCountry,
          city: widget.ad.locationCity,
          district: widget.ad.locationDistrict,
        );
        if (fromAd.isNotEmpty) return fromAd;
        if (widget.ad.effectiveMapPosition != null) {
          return AppLocale.tr('location_on_map_short');
        }
        return AppLocale.tr('no_location');
      }
      return m.toString();
    }
    return val.toString();
  }

  @override
  Widget build(BuildContext context) {
    final ad = widget.ad;
    final customFields = ad.customFields ?? {};

    final salaryUi = ad.displaySalaryForUi;
    final priceUi = ad.displayPriceForUi?.trim();
    final hasSalary = salaryUi != null && salaryUi.isNotEmpty;
    final priceDiffersFromSalary =
        hasSalary && priceUi != null && priceUi.isNotEmpty && priceUi != salaryUi;

    final rows = <Widget>[
      if (hasSalary)
        _DetailRow(
          title: AppLocale.tr('salary'),
          value: salaryUi,
          valueStyle: TextStyle(
            color: AppColors.darkBlue,
            fontWeight: FontWeight.w700,
            fontSize: 14.sp,
          ),
        ),
      if (!hasSalary && priceUi != null && priceUi.isNotEmpty)
        _DetailRow(
          title: AppLocale.tr('price'),
          value: priceUi,
          valueStyle: TextStyle(
            color: AppColors.darkBlue,
            fontWeight: FontWeight.w700,
            fontSize: 14.sp,
          ),
        ),
      if (priceDiffersFromSalary)
        _DetailRow(
          title: AppLocale.tr('price'),
          value: priceUi,
          valueStyle: TextStyle(
            color: AppColors.darkBlue,
            fontWeight: FontWeight.w700,
            fontSize: 14.sp,
          ),
        ),
      _DetailRow(title: AppLocale.tr('ad_uid'), value: ad.uid),
      _DetailRow(title: AppLocale.tr('views_count'), value: '${ad.viewsCount}'),
    ];

    for (final entry in customFields.entries) {
      if (entry.key == 'title' || entry.key == 'description') continue;
      if (_isLikelyPriceCustomFieldKey(entry.key)) continue;
      if (hasSalary && _isSalaryCustomFieldKey(entry.key)) continue;
      final title = _getFieldLabel(entry.key);
      final coords = _coordinateOnlyPairForGeocode(entry.value);
      if (coords != null) {
        rows.add(
          _DetailRowGeocodedLocation(
            title: title,
            lat: coords.lat,
            lng: coords.lng,
          ),
        );
        continue;
      }
      final value = _formatCustomValue(entry.value);
      rows.add(_DetailRow(title: title, value: value));
    }

    return Column(children: rows);
  }
}

class _DescriptionTab extends StatelessWidget {
  final AdModel ad;

  const _DescriptionTab({required this.ad});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          "الوصف",
          style: TextStyle(fontSize: 16.sp, fontWeight: FontWeight.w600),
        ),
        SizedBox(height: 10.h),
        Text(
          ad.description ?? AppLocale.tr('no_description'),
          style: TextStyle(
            color: Colors.grey[600],
            fontSize: 14.sp,
          ),
        ),
      ],
    );
  }
}

Future<void> _openAdLocationInGoogleMaps(BuildContext context, AdModel ad) async {
  final pos = ad.effectiveMapPosition;
  if (pos == null) return;
  final lat = pos.lat;
  final lng = pos.lng;
  final uri = Uri.parse(
    'https://www.google.com/maps/search/?api=1&query=$lat,$lng',
  );
  try {
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } else if (context.mounted) {
      showToast(message: AppLocale.tr('maps_link_failed'));
    }
  } catch (_) {
    if (context.mounted) {
      showToast(message: AppLocale.tr('maps_link_failed'));
    }
  }
}

/// فتح تطبيق الخرائط لوضع المسار إلى إحداثيات الإعلان (مثل «كيف أصل»).
Future<void> _openMapDirectionsToAd(BuildContext context, AdModel ad) async {
  final pos = ad.effectiveMapPosition;
  if (pos == null) return;
  final lat = pos.lat;
  final lng = pos.lng;
  final uri = Uri.parse(
    'https://www.google.com/maps/dir/?api=1&destination=$lat,$lng',
  );
  try {
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } else if (context.mounted) {
      showToast(message: AppLocale.tr('maps_link_failed'));
    }
  } catch (_) {
    if (context.mounted) {
      showToast(message: AppLocale.tr('maps_link_failed'));
    }
  }
}

class _LocationTab extends StatefulWidget {
  final AdModel ad;

  const _LocationTab({required this.ad});

  @override
  State<_LocationTab> createState() => _LocationTabState();
}

class _LocationTabState extends State<_LocationTab> {
  String? _geocoded;
  bool _geocodeLoading = false;
  LatLng? _fallbackPos;
  bool _fallbackLoading = false;
  GoogleMapController? _mapController;
  MapType _mapType = MapType.normal;

  @override
  void initState() {
    super.initState();
    _maybeGeocode();
  }

  @override
  void dispose() {
    _mapController?.dispose();
    super.dispose();
  }

  @override
  void didUpdateWidget(covariant _LocationTab oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.ad != widget.ad ||
        oldWidget.ad.effectiveMapPosition != widget.ad.effectiveMapPosition) {
      _geocoded = null;
      _maybeGeocode();
    }
  }

  Widget _mapTypeOverlayControl() {
    return Material(
      elevation: 3,
      color: Colors.white,
      borderRadius: BorderRadius.circular(999),
      child: Padding(
        padding: EdgeInsets.symmetric(horizontal: 6.w, vertical: 4.h),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            _mapTypeChip(
              label: AppLocale.tr('map_layer_normal'),
              selected: _mapType == MapType.normal,
              onTap: () => setState(() => _mapType = MapType.normal),
            ),
            SizedBox(width: 6.w),
            _mapTypeChip(
              label: AppLocale.tr('map_layer_satellite'),
              selected: _mapType == MapType.hybrid,
              onTap: () => setState(() => _mapType = MapType.hybrid),
            ),
          ],
        ),
      ),
    );
  }

  Widget _mapTypeChip({
    required String label,
    required bool selected,
    required VoidCallback onTap,
  }) {
    final bg = selected ? AppColors.darkBlue : Colors.transparent;
    final fg = selected ? Colors.white : Colors.black87;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(999),
      child: Container(
        padding: EdgeInsets.symmetric(horizontal: 10.w, vertical: 6.h),
        decoration: BoxDecoration(
          color: bg,
          borderRadius: BorderRadius.circular(999),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 12.sp,
            fontWeight: FontWeight.w600,
            color: fg,
          ),
        ),
      ),
    );
  }

  Future<void> _maybeGeocode() async {
    final ad = widget.ad;
    final pos = ad.effectiveMapPosition;
    if (pos == null) {
      final line = ad.staticLocationDisplayLine.trim();
      if (mounted) {
        setState(() {
          _geocodeLoading = false;
          _geocoded = null;
          _fallbackLoading = false;
          _fallbackPos = null;
        });
      }
      if (line.isEmpty) return;
      if (!mounted) return;
      setState(() => _fallbackLoading = true);
      try {
        final res = await locationFromAddress(line);
        if (!mounted) return;
        if (res.isNotEmpty &&
            res.first.latitude.isFinite &&
            res.first.longitude.isFinite) {
          setState(() {
            _fallbackPos = LatLng(res.first.latitude, res.first.longitude);
            _fallbackLoading = false;
          });
        } else {
          setState(() {
            _fallbackPos = null;
            _fallbackLoading = false;
          });
        }
      } catch (_) {
        if (!mounted) return;
        setState(() {
          _fallbackPos = null;
          _fallbackLoading = false;
        });
      }
      return;
    }
    // موقع محدد على الخريطة: نجهل الحقول الثابتة ونعتمد على العنوان من الإحداثيات المحفوظة.
    if (!mounted) return;
    setState(() {
      _geocodeLoading = true;
      _geocoded = null;
      _fallbackLoading = false;
      _fallbackPos = null;
    });
    final line = await ReverseGeocodingService.humanReadableFromCoordinates(
      pos.lat,
      pos.lng,
      languageCode: AppLocale.current,
    );
    if (!mounted) return;
    setState(() {
      _geocodeLoading = false;
      _geocoded = line;
    });
  }

  Future<void> _openInGoogleMapsForLatLng(double lat, double lng) async {
    final uri = Uri.parse('https://www.google.com/maps/search/?api=1&query=$lat,$lng');
    try {
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      } else if (mounted) {
        showToast(message: AppLocale.tr('maps_link_failed'));
      }
    } catch (_) {
      if (mounted) showToast(message: AppLocale.tr('maps_link_failed'));
    }
  }

  Future<void> _openDirectionsForLatLng(double lat, double lng) async {
    final uri = Uri.parse('https://www.google.com/maps/dir/?api=1&destination=$lat,$lng');
    try {
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      } else if (mounted) {
        showToast(message: AppLocale.tr('maps_link_failed'));
      }
    } catch (_) {
      if (mounted) showToast(message: AppLocale.tr('maps_link_failed'));
    }
  }

  @override
  Widget build(BuildContext context) {
    final ad = widget.ad;
    final staticLine = ad.staticLocationDisplayLine;
    final mapPos = ad.effectiveMapPosition;
    final usedMapPos = mapPos ?? (_fallbackPos != null ? (lat: _fallbackPos!.latitude, lng: _fallbackPos!.longitude) : null);

    String locationText;
    if (usedMapPos != null) {
      if (_geocodeLoading) {
        locationText = AppLocale.tr('resolving_location');
      } else if (_fallbackLoading) {
        locationText = AppLocale.tr('resolving_location');
      } else {
        locationText = (_geocoded != null && _geocoded!.isNotEmpty)
            ? _geocoded!
            : (staticLine.isNotEmpty
                ? staticLine
                : AppLocale.tr('location_on_map_short'));
      }
    } else if (staticLine.isNotEmpty) {
      locationText = staticLine;
    } else {
      locationText = AppLocale.tr('no_location');
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          AppLocale.tr('location'),
          style: TextStyle(fontSize: 16.sp, fontWeight: FontWeight.w600),
        ),
        if (usedMapPos != null) ...[
          ClipRRect(
            borderRadius: BorderRadius.circular(8.r),
            child: SizedBox(
              // خريطة أكبر "ملي الشاشة" داخل التبويب (مع حد أعلى حتى لا تدفع باقي المحتوى بعيداً جداً).
              height: (MediaQuery.sizeOf(context).height * 0.68).clamp(420.0, 720.0),
              width: double.infinity,
              child: Stack(
                fit: StackFit.expand,
                children: [
                  GoogleMap(
                    mapType: _mapType,
                    initialCameraPosition: CameraPosition(
                      target: LatLng(usedMapPos.lat, usedMapPos.lng),
                      zoom: 15,
                    ),
                    gestureRecognizers: <Factory<OneSequenceGestureRecognizer>>{
                      Factory<OneSequenceGestureRecognizer>(
                        () => EagerGestureRecognizer(),
                      ),
                    },
                    onMapCreated: (c) async {
                      _mapController = c;
                    },
                    markers: {
                      Marker(
                        markerId: const MarkerId('ad_location'),
                        position: LatLng(usedMapPos.lat, usedMapPos.lng),
                        anchor: const Offset(0.5, 1.0),
                        // استخدام دبوس Google الافتراضي بدل "سعر" داخل الدبوس.
                        icon: BitmapDescriptor.defaultMarkerWithHue(
                          BitmapDescriptor.hueRed,
                        ),
                      ),
                    },
                    scrollGesturesEnabled: true,
                    zoomGesturesEnabled: true,
                    rotateGesturesEnabled: true,
                    tiltGesturesEnabled: true,
                    myLocationButtonEnabled: true,
                    zoomControlsEnabled: true,
                    mapToolbarEnabled: true,
                    compassEnabled: true,
                    liteModeEnabled: false,
                  ),
                  // اختيار "خريطة / قمر صناعي" فوق الخريطة مثل Google Maps.
                  Positioned.directional(
                    textDirection: Directionality.of(context),
                    top: 10.r,
                    end: 10.r,
                    child: _mapTypeOverlayControl(),
                  ),
                  Positioned.directional(
                    textDirection: Directionality.of(context),
                    end: 10.r,
                    bottom: 10.r,
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Tooltip(
                          message: AppLocale.tr('map_directions'),
                          child: Material(
                            elevation: 4,
                            shape: const CircleBorder(),
                            color: AppColors.darkBlue,
                            clipBehavior: Clip.antiAlias,
                            child: InkWell(
                              onTap: () => _openDirectionsForLatLng(
                                usedMapPos.lat,
                                usedMapPos.lng,
                              ),
                              child: SizedBox(
                                width: 52.r,
                                height: 52.r,
                                child: Icon(
                                  Icons.navigation_rounded,
                                  color: Colors.white,
                                  size: 26.sp,
                                ),
                              ),
                            ),
                          ),
                        ),
                        SizedBox(height: 10.h),
                        Tooltip(
                          message: AppLocale.tr('open_in_google_maps'),
                          child: Material(
                            elevation: 3,
                            shape: const CircleBorder(),
                            color: Colors.white,
                            clipBehavior: Clip.antiAlias,
                            child: InkWell(
                              onTap: () => _openInGoogleMapsForLatLng(
                                usedMapPos.lat,
                                usedMapPos.lng,
                              ),
                              child: SizedBox(
                                width: 46.r,
                                height: 46.r,
                                child: Icon(
                                  Icons.map_outlined,
                                  color: AppColors.darkBlue,
                                  size: 22.sp,
                                ),
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
        SizedBox(height: 10.h),
        Text(
          locationText,
          style: TextStyle(fontSize: 14.sp, color: Colors.grey[600]),
        ),
      ],
    );
  }
}

void _openCategoryPathSegment(BuildContext context, CategoryPathSegment segment) {
  if (segment.id <= 0) return;
  final listTitle = '${AppLocale.tr('all_ads_of')} "${segment.name}"';
  if (segment.isCategory) {
    context.push(AdsListPage(title: listTitle, categoryId: segment.id));
  } else {
    context.push(AdsListPage(title: listTitle, subcategoryId: segment.id));
  }
}

class _DetailRow extends StatelessWidget {
  final String title;
  final String value;
  final TextStyle? valueStyle;

  const _DetailRow({
    required this.title,
    required this.value,
    this.valueStyle,
  });

  @override
  Widget build(BuildContext context) {
    final defaultValueStyle = TextStyle(
      color: Colors.grey[900],
      fontSize: 13.sp,
    );
    return Padding(
      padding: EdgeInsets.symmetric(vertical: 4.h),
      child: Column(
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Flexible(
                flex: 2,
                child: Align(
                  alignment: AlignmentDirectional.centerStart,
                  child: Text(
                    title,
                    textAlign: TextAlign.start,
                    style: TextStyle(color: Colors.grey[600], fontSize: 13.sp),
                  ),
                ),
              ),
              SizedBox(width: 8.w),
              Expanded(
                flex: 3,
                child: Align(
                  alignment: AlignmentDirectional.centerEnd,
                  child: Text(
                    value,
                    textAlign: TextAlign.end,
                    softWrap: true,
                    style: valueStyle ?? defaultValueStyle,
                  ),
                ),
              ),
            ],
          ),
          Divider(thickness: 1, height: 1),
        ],
      ),
    );
  }
}

class _DetailRowGeocodedLocation extends StatefulWidget {
  final String title;
  final double lat;
  final double lng;

  const _DetailRowGeocodedLocation({
    required this.title,
    required this.lat,
    required this.lng,
  });

  @override
  State<_DetailRowGeocodedLocation> createState() => _DetailRowGeocodedLocationState();
}

class _DetailRowGeocodedLocationState extends State<_DetailRowGeocodedLocation> {
  String? _resolved;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    ReverseGeocodingService.humanReadableFromCoordinates(
      widget.lat,
      widget.lng,
      languageCode: AppLocale.current,
    ).then((s) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _resolved = s;
      });
    });
  }

  @override
  Widget build(BuildContext context) {
    final value = _loading
        ? AppLocale.tr('resolving_location')
        : ((_resolved != null && _resolved!.isNotEmpty)
            ? _resolved!
            : AppLocale.tr('location_on_map_short'));
    return _DetailRow(title: widget.title, value: value);
  }
}

class _ContactRow extends StatelessWidget {
  final AdModel ad;

  const _ContactRow({required this.ad});

  String? _normalizeDialablePhone(dynamic rawPhone, dynamic rawCountryCode) {
    final phone = (rawPhone ?? '').toString().trim();
    if (phone.isEmpty) return null;

    var cleaned = phone.replaceAll(RegExp(r'[^0-9+]'), '');
    if (cleaned.isEmpty) return null;
    if (cleaned.startsWith('+')) {
      final digits = cleaned.replaceAll(RegExp(r'[^0-9]'), '');
      return digits.isEmpty ? null : '+$digits';
    }

    final countryCode = (rawCountryCode ?? '').toString().trim();
    final ccDigits = countryCode.replaceAll(RegExp(r'[^0-9]'), '');
    if (ccDigits.isNotEmpty) {
      final trimmedLocal = cleaned.replaceFirst(RegExp(r'^0+'), '');
      if (trimmedLocal.startsWith(ccDigits)) {
        return '+$trimmedLocal';
      }
      return '+$ccDigits$trimmedLocal';
    }

    return cleaned;
  }

  Future<void> _callSeller(BuildContext context, Map<String, dynamic>? user) async {
    final phone = _normalizeDialablePhone(user?['phone'], user?['country_code']);
    if (phone == null || phone.isEmpty) {
      showToast(message: AppLocale.tr('phone_not_available'));
      return;
    }

    final uri = Uri.parse('tel:$phone');
    try {
      final ok = await launchUrl(uri, mode: LaunchMode.externalApplication);
      if (!ok) {
        showToast(message: AppLocale.tr('failed'));
      }
    } catch (_) {
      showToast(message: AppLocale.tr('failed'));
    }
  }

  @override
  Widget build(BuildContext context) {
    final user = ad.user;
    final phone = _normalizeDialablePhone(user?['phone'], user?['country_code']);

    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        GestureDetector(
          onTap: () => _callSeller(context, user),
          child: Container(
            width: 160.w,
            height: 40.h,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(8.r),
              color: AppColors.darkBlue,
            ),
            child: Center(
              child: Text(
                AppLocale.tr('call'),
                style: TextStyle(color: Colors.white, fontSize: 14.sp),
              ),
            ),
          ),
        ),
        GestureDetector(
          onTap: () {
            if (!TokenStorage.hasToken()) {
              context.push(LoginPage());
              return;
            }
            context.push(ChatPage(adUid: ad.uid, sellerName: ad.user?['name'] as String?));
          },
          child: Container(
            width: 160.w,
            height: 40.h,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(8.r),
              color: AppColors.yellow,
            ),
            child: Center(
              child: Text(
                AppLocale.tr('send_message'),
                style: TextStyle(color: Colors.black, fontSize: 14.sp),
              ),
            ),
          ),
        ),
      ],
    );
  }
}

class _RelatedAdCard extends StatelessWidget {
  final AdModel ad;
  final VoidCallback onTap;

  const _RelatedAdCard({required this.ad, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: EdgeInsets.all(12.w),
        margin: EdgeInsets.only(bottom: 12.h),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(8.r),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.06),
              blurRadius: 8,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Row(
          children: [
            Stack(
              clipBehavior: Clip.none,
              children: [
                ClipRRect(
                  borderRadius: BorderRadius.circular(8.r),
                  child: ColoredBox(
                    color: Colors.grey[100]!,
                    child: ad.imageUrl != null
                        ? CachedUrlImage(
                            imageUrl: ad.imageUrl!,
                            width: 70.w,
                            height: 70.h,
                            fit: BoxFit.contain,
                            errorBuilder: (_, __) => _placeholder(),
                          )
                        : _placeholder(),
                  ),
                ),
                if (ad.isFeatured)
                  Positioned(
                    top: 2.h,
                    left: 2.w,
                    child: AdStatusBadgeIcon.featured(size: 18.sp),
                  ),
              ],
            ),
            SizedBox(width: 12.w),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.start,
                children: [
                  Text(
                    ad.title,
                    style: TextStyle(
                      fontSize: 14.sp,
                      fontWeight: FontWeight.bold,
                      color: Colors.black,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  SizedBox(height: 6.h),
                  Row(
                    children: [
                      Expanded(
                        child: AdListLocationLabel(
                          key: ValueKey('${ad.uid}_loc'),
                          ad: ad,
                          iconSize: 16,
                          style: TextStyle(
                            fontSize: 12.sp,
                            color: Colors.grey,
                          ),
                        ),
                      ),
                      if (ad.displayPriceOrSalaryForUi != null)
                        Text(
                          ad.displayPriceOrSalaryForUi!,
                          style: TextStyle(
                            fontSize: 15.sp,
                            color: AppColors.darkBlue,
                            fontWeight: FontWeight.bold,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _placeholder() {
    return Container(
      width: 70.w,
      height: 70.h,
      color: Colors.grey[200],
      child: Icon(Icons.image_not_supported, color: Colors.grey[400]),
    );
  }
}

class _NegotiatePriceDialog extends StatefulWidget {
  final String adUid;
  final String adCurrency;
  final TextEditingController priceController;
  final VoidCallback onSuccess;

  const _NegotiatePriceDialog({
    required this.adUid,
    required this.adCurrency,
    required this.priceController,
    required this.onSuccess,
  });

  @override
  State<_NegotiatePriceDialog> createState() => _NegotiatePriceDialogState();
}

class _NegotiatePriceDialogState extends State<_NegotiatePriceDialog> {
  bool _sending = false;
  late TextEditingController _messageController;

  @override
  void initState() {
    super.initState();
    _messageController = TextEditingController();
  }

  @override
  void dispose() {
    _messageController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final priceStr = widget.priceController.text.trim();
    if (priceStr.isEmpty) {
      showToast(message: AppLocale.tr('enter_proposed_price'));
      return;
    }
    final price = NumeralHelper.parseFormattedAmount(priceStr) ?? num.tryParse(priceStr);
    if (price == null || price < 0) {
      showToast(message: AppLocale.tr('enter_valid_price'));
      return;
    }
    setState(() => _sending = true);
    final res = await NegotiationService.store(
      adUid: widget.adUid,
      offeredPrice: price,
      currency: widget.adCurrency,
      message: _messageController.text.trim().isEmpty
          ? null
          : _messageController.text.trim(),
    );
    if (!mounted) return;
    setState(() => _sending = false);
    showToast(message: res['message'] as String? ?? '');
    if (res['success'] == true) widget.onSuccess();
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20.r)),
      child: Padding(
        padding: EdgeInsets.all(20.w),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(
              AppLocale.tr('negotiate_price'),
              style: TextStyle(fontSize: 18.sp, fontWeight: FontWeight.bold),
              textAlign: TextAlign.center,
            ),
            SizedBox(height: 15.h),
            Text(
              AppLocale.tr('negotiate_hint'),
              style: TextStyle(color: Colors.grey[600], fontSize: 14.sp),
              textAlign: TextAlign.center,
            ),
            SizedBox(height: 20.h),
            Row(
              children: [
                Expanded(
                  flex: 3,
                  child: TextFormField(
                    controller: widget.priceController,
                    keyboardType: TextInputType.number,
                    inputFormatters: [ThousandSeparatorInputFormatter(allowDecimal: true)],
                    decoration: InputDecoration(
                      labelText: AppLocale.tr('proposed_price'),
                      hintText: '0',
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(8.r),
                      ),
                    ),
                  ),
                ),
                SizedBox(width: 12.w),
                Expanded(
                  child: TextFormField(
                    initialValue: CurrencyHelper.symbol(widget.adCurrency),
                    readOnly: true,
                    textAlign: TextAlign.center,
                    decoration: InputDecoration(
                      labelText: AppLocale.tr('currency'),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(8.r),
                      ),
                    ),
                  ),
                ),
              ],
            ),
            SizedBox(height: 16.h),
            TextFormField(
              controller: _messageController,
              maxLines: 3,
              decoration: InputDecoration(
                labelText: AppLocale.tr('message_optional'),
                hintText: AppLocale.tr('add_message_to_seller'),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(8.r),
                ),
              ),
            ),
            SizedBox(height: 24.h),
            Row(
              children: [
                Expanded(
                  child: CustomButton(
                    text: _sending ? AppLocale.tr('sending') : AppLocale.tr('send'),
                    onTap: _sending ? () {} : _submit,
                    backgroundColor: AppColors.darkBlue,
                    textColor: Colors.white,
                  ),
                ),
                SizedBox(width: 16.w),
                Expanded(
                  child: CustomButton(
                    text: AppLocale.tr('back'),
                    onTap: () => Navigator.pop(context),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _ReportAdDialog extends StatefulWidget {
  final int adId;
  final VoidCallback onSuccess;

  const _ReportAdDialog({required this.adId, required this.onSuccess});

  @override
  State<_ReportAdDialog> createState() => _ReportAdDialogState();
}

class _ReportAdDialogState extends State<_ReportAdDialog> {
  final _reasonController = TextEditingController();
  String _type = 'spam';
  bool _sending = false;

  static const _typeKeys = ['spam', 'fraud', 'inappropriate', 'duplicate', 'other'];

  String _typeLabel(String type) {
    const keys = {
      'spam': 'type_spam',
      'fraud': 'type_fraud',
      'inappropriate': 'type_inappropriate',
      'duplicate': 'type_duplicate',
      'other': 'type_other',
    };
    final k = keys[type];
    return k != null ? AppLocale.tr(k) : type;
  }

  @override
  void dispose() {
    _reasonController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final reason = _reasonController.text.trim();
    if (reason.isEmpty) {
      showToast(message: AppLocale.tr('enter_report_reason_short'));
      return;
    }
    setState(() => _sending = true);
    final res = await ReportService.reportAd(
      adId: widget.adId,
      type: _type,
      reason: reason,
    );
    if (!mounted) return;
    setState(() => _sending = false);
    showToast(message: res['message'] as String? ?? '');
    if (res['success'] == true) widget.onSuccess();
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20.r)),
      child: Padding(
        padding: EdgeInsets.all(20.w),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(AppLocale.tr('report_about_ad'),
                style: TextStyle(fontSize: 18.sp, fontWeight: FontWeight.bold)),
            SizedBox(height: 16.h),
            DropdownButtonFormField<String>(
              value: _type,
              decoration: InputDecoration(
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(8.r)),
              ),
              items: _typeKeys
                  .map((k) => DropdownMenuItem(value: k, child: Text(_typeLabel(k))))
                  .toList(),
              onChanged: (v) => setState(() => _type = v ?? _type),
            ),
            SizedBox(height: 12.h),
            TextFormField(
              controller: _reasonController,
              maxLines: 4,
              decoration: InputDecoration(
                labelText: AppLocale.tr('reason'),
                hintText: AppLocale.tr('report_reason_ad_hint'),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(8.r)),
              ),
            ),
            SizedBox(height: 20.h),
            Row(
              children: [
                Expanded(
                  child: CustomButton(
                    text: _sending ? AppLocale.tr('sending') : AppLocale.tr('send'),
                    onTap: _sending ? () {} : _submit,
                    backgroundColor: Colors.red,
                    textColor: Colors.white,
                  ),
                ),
                SizedBox(width: 12.w),
                Expanded(
                  child: CustomButton(
                    text: AppLocale.tr('cancel'),
                    onTap: () => Navigator.pop(context),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _AdFullVideoSheet extends StatefulWidget {
  final String url;

  const _AdFullVideoSheet({required this.url});

  @override
  State<_AdFullVideoSheet> createState() => _AdFullVideoSheetState();
}

class _AdFullVideoSheetState extends State<_AdFullVideoSheet> {
  late final VideoPlayerController _controller;
  Future<void>? _init;
  bool _isScrubbing = false;
  bool _resumeAfterScrub = false;
  double? _scrubValueMs;

  void _onVideoUpdate() {
    if (mounted) setState(() {});
  }

  String _twoDigits(int n) => n.toString().padLeft(2, '0');

  String _formatDuration(Duration d) {
    final totalSeconds = d.inSeconds;
    if (totalSeconds <= 0) return '0:00';
    final h = d.inHours;
    final m = d.inMinutes.remainder(60);
    final s = d.inSeconds.remainder(60);
    if (h > 0) return '$h:${_twoDigits(m)}:${_twoDigits(s)}';
    return '$m:${_twoDigits(s)}';
  }

  Duration _effectiveDuration() {
    final value = _controller.value;
    Duration effective = value.duration;
    if (value.position > effective) effective = value.position;
    if (value.buffered.isNotEmpty && value.buffered.last.end > effective) {
      effective = value.buffered.last.end;
    }
    return effective > Duration.zero ? effective : const Duration(milliseconds: 1);
  }

  Future<void> _safeSeekTo(Duration target) async {
    final value = _controller.value;
    final wasPlaying = value.isPlaying;
    final duration = _effectiveDuration();
    final clamped = target < Duration.zero
        ? Duration.zero
        : (target > duration ? duration : target);
    if (wasPlaying) {
      await _controller.pause();
    }
    await _controller.seekTo(clamped);
    if (wasPlaying) {
      await _controller.play();
    }
    if (mounted) setState(() {});
  }

  @override
  void initState() {
    super.initState();
    _controller = VideoPlayerController.networkUrl(Uri.parse(widget.url));
    _init = _controller.initialize().then((_) {
      if (!mounted) return;
      _controller.addListener(_onVideoUpdate);
      setState(() {});
    });
  }

  @override
  void dispose() {
    _controller.removeListener(_onVideoUpdate);
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Align(
          alignment: AlignmentDirectional.centerEnd,
          child: IconButton(
            onPressed: () => Navigator.pop(context),
            icon: const Icon(Icons.close, color: Colors.white),
          ),
        ),
        Expanded(
          child: FutureBuilder<void>(
            future: _init,
            builder: (context, snap) {
              if (snap.connectionState != ConnectionState.done) {
                return const Center(
                  child: CircularProgressIndicator(color: Colors.white),
                );
              }
              if (snap.hasError) {
                return Center(
                  child: Text(
                    AppLocale.tr('pick_video_failed'),
                    style: const TextStyle(color: Colors.white70),
                    textAlign: TextAlign.center,
                  ),
                );
              }
              return Center(
                child: AspectRatio(
                  aspectRatio: _controller.value.aspectRatio == 0
                      ? 16 / 9
                      : _controller.value.aspectRatio,
                  child: VideoPlayer(_controller),
                ),
              );
            },
          ),
        ),
        if (_controller.value.isInitialized) ...[
          Padding(
            padding: EdgeInsets.symmetric(horizontal: 14.w),
            child: Builder(
              builder: (context) {
                final duration = _effectiveDuration();
                final totalMs = duration.inMilliseconds <= 0
                    ? 1.0
                    : duration.inMilliseconds.toDouble();
                final currentMs = _isScrubbing
                    ? (_scrubValueMs ?? 0)
                    : _controller.value.position.inMilliseconds.toDouble();
                final sliderValue = currentMs.clamp(0.0, totalMs);

                return SliderTheme(
                  data: SliderTheme.of(context).copyWith(
                    trackHeight: 4.h,
                    thumbShape: RoundSliderThumbShape(
                      enabledThumbRadius: 7.r,
                    ),
                    overlayShape: RoundSliderOverlayShape(
                      overlayRadius: 12.r,
                    ),
                    activeTrackColor: AppColors.lightBlue,
                    inactiveTrackColor: Colors.white12,
                    thumbColor: AppColors.lightBlue,
                    overlayColor: AppColors.lightBlue.withValues(alpha: 0.2),
                  ),
                  child: Slider(
                    min: 0,
                    max: totalMs,
                    value: sliderValue,
                    onChangeStart: (v) async {
                      _isScrubbing = true;
                      _scrubValueMs = v;
                      _resumeAfterScrub = _controller.value.isPlaying;
                      if (_resumeAfterScrub) {
                        await _controller.pause();
                      }
                      if (mounted) setState(() {});
                    },
                    onChanged: (v) {
                      _isScrubbing = true;
                      _scrubValueMs = v;
                      if (mounted) setState(() {});
                    },
                    onChangeEnd: (v) async {
                      final target = Duration(milliseconds: v.round());
                      if (_resumeAfterScrub) {
                        await _safeSeekTo(target);
                      } else {
                        await _controller.seekTo(target);
                      }
                      _isScrubbing = false;
                      _resumeAfterScrub = false;
                      _scrubValueMs = null;
                      if (mounted) setState(() {});
                    },
                  ),
                );
              },
            ),
          ),
          Padding(
            padding: EdgeInsets.symmetric(horizontal: 14.w),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(6.r),
              child: LinearProgressIndicator(
                value: _controller.value.duration.inMilliseconds <= 0
                    ? 0.0
                    : (_controller.value.buffered.isNotEmpty
                          ? (_controller.value.buffered.last.end.inMilliseconds /
                                _controller.value.duration.inMilliseconds)
                          : 0)
                        .clamp(0.0, 1.0)
                        .toDouble(),
                minHeight: 2.h,
                valueColor: const AlwaysStoppedAnimation<Color>(Colors.white24),
                backgroundColor: Colors.transparent,
              ),
            ),
          ),
          Padding(
            padding: EdgeInsets.fromLTRB(14.w, 8.h, 14.w, 6.h),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  '${_formatDuration(_controller.value.position)} / ${_formatDuration(_effectiveDuration())}',
                  style: TextStyle(color: Colors.white70, fontSize: 12.sp, fontWeight: FontWeight.w600),
                ),
                Icon(
                  _controller.value.isPlaying ? Icons.volume_up_rounded : Icons.play_arrow_rounded,
                  color: Colors.transparent,
                  size: 18.sp,
                ),
              ],
            ),
          ),
          Padding(
            padding: EdgeInsets.only(bottom: 10.h),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                IconButton(
                  iconSize: 34,
                  color: Colors.white,
                  onPressed: () async {
                    final pos = _controller.value.position;
                    final target = pos - const Duration(seconds: 10);
                    await _safeSeekTo(target);
                  },
                  icon: const Icon(Icons.replay_10_rounded),
                ),
                SizedBox(width: 10.w),
                IconButton(
                  iconSize: 56,
                  color: Colors.white,
                  onPressed: () async {
                    if (_controller.value.isPlaying) {
                      await _controller.pause();
                    } else {
                      await _controller.play();
                    }
                    setState(() {});
                  },
                  icon: Icon(
                    _controller.value.isPlaying
                        ? Icons.pause_circle_filled
                        : Icons.play_circle_filled,
                  ),
                ),
                SizedBox(width: 10.w),
                IconButton(
                  iconSize: 34,
                  color: Colors.white,
                  onPressed: () async {
                    final pos = _controller.value.position;
                    final target = pos + const Duration(seconds: 10);
                    await _safeSeekTo(target);
                  },
                  icon: const Icon(Icons.forward_10_rounded),
                ),
              ],
            ),
          ),
        ] else
          SizedBox(height: 16.h),
      ],
    );
  }
}
