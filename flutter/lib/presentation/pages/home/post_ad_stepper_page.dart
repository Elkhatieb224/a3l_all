import 'dart:async';

import 'package:a3lnha/core/data/currency_helper.dart';
import 'package:a3lnha/core/data/reverse_geocoding_service.dart';
import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/storage/token_storage.dart';
import 'package:a3lnha/presentation/pages/auth/login_page.dart';
import 'package:a3lnha/presentation/widgets/shared/warning_confirm_dialog.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/models/ad_images_effective.dart';
import 'package:a3lnha/data/models/category_model.dart';
import 'package:a3lnha/data/models/subcategory_model.dart';
import 'package:a3lnha/data/services/ad_service.dart';
import 'package:a3lnha/data/services/category_service.dart';
import 'package:a3lnha/data/services/region_service.dart';
import 'package:a3lnha/helpers/custom_fields_resolver.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/helpers/numeral_helper.dart';
import 'package:a3lnha/presentation/pages/home/thank_you_page.dart';
import 'package:a3lnha/presentation/pages/payement/quta_pages.dart';
import 'package:a3lnha/core/support/car_body_map_support.dart';
import 'package:a3lnha/presentation/widgets/car_body_map_widget.dart';
import 'package:a3lnha/presentation/widgets/auth/text_form_with_label.dart';
import 'package:a3lnha/presentation/widgets/map_location_picker.dart';
import 'package:a3lnha/presentation/widgets/shared/app_network_image.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_button.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:dotted_border/dotted_border.dart';

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:geolocator/geolocator.dart';
import 'package:geocoding/geocoding.dart';
import 'package:hexcolor/hexcolor.dart';
import 'package:image_picker/image_picker.dart';

/// ارتفاع خريطة اختيار الموقع داخل النموذج (نسبة من الشاشة مع حدود).
double _inlineMapPickerHeight(BuildContext context) {
  final h = MediaQuery.sizeOf(context).height;
  // على الويب كانت الخريطة أكبر (h-64). هنا نرفع النسبة لتكون أوضح وأسهل للتحريك/التكبير.
  return (h * 0.52).clamp(320.0, 640.0);
}

/// بيانات إنشاء الإعلان - مطابق لتدفق الموقع
class CreateAdData {
  int? categoryId;
  String? categoryName;
  int? subcategoryId;
  List<Map<String, String>> subcategoryPath = [];
  String title = '';
  String description = '';
  String? price;
  String currency = 'SYP';
  String locationCountry = 'SY';
  /// طريقة الإدخال: يدوي (من القائمة) أو خريطة — يطابق الـ API.
  String locationInputMethod = 'manual';
  String? locationStateCode;
  String? locationCityCode;
  String? locationDistrictCode;
  String locationState = '';
  String locationCity = '';
  String locationDistrict = '';
  String locationAddress = '';
  double? latitude;
  double? longitude;
  Map<String, dynamic> customFields = {};
  List<XFile> images = [];
  /// فيديو اختياري واحد مع الإعلان.
  XFile? video;
  List<Map<String, dynamic>>? categoryCustomFields;
  CategoryModel? categoryModelForAds;
  SubcategoryModel? leafSubcategoryForAds;
  /// مسار تخزين الصورة عند وضع معرض لوحة التحكم
  String? selectedGalleryPath;
}

class PostAdStepperPage extends StatefulWidget {
  final String title;
  final int? initialCategoryId;
  final int? initialSubcategoryId;
  final String? initialCategoryName;
  final List<Map<String, String>>? initialSubcategoryPath;

  const PostAdStepperPage({
    super.key,
    required this.title,
    this.initialCategoryId,
    this.initialSubcategoryId,
    this.initialCategoryName,
    this.initialSubcategoryPath,
  });

  @override
  State<PostAdStepperPage> createState() => _PostAdStepperPageState();
}

class _PostAdStepperPageState extends State<PostAdStepperPage> {
  late final PageController _controller;
  late int _currentIndex;
  final CreateAdData _data = CreateAdData();
  bool _isPublishing = false;

  @override
  void initState() {
    super.initState();
    int startIndex = 0;
    if (widget.initialCategoryId != null) {
      _data.categoryId = widget.initialCategoryId;
      _data.categoryName = widget.initialCategoryName ?? '';
      if (widget.initialSubcategoryId != null &&
          (widget.initialSubcategoryPath?.isNotEmpty ?? false)) {
        _data.subcategoryId = widget.initialSubcategoryId;
        _data.subcategoryPath = List.from(widget.initialSubcategoryPath!);
        startIndex = 2;
      } else {
        startIndex = 1;
      }
      WidgetsBinding.instance.addPostFrameCallback((_) async {
        if (!mounted) return;
        final cat = await CategoryService.getCategory(
          widget.initialCategoryId!,
          forceRefresh: true,
        );
        SubcategoryModel? leaf;
        if (widget.initialSubcategoryId != null) {
          leaf = await CategoryService.getSubcategory(widget.initialSubcategoryId!);
        }
        if (mounted && cat != null) {
          List<Map<String, dynamic>>? fields;
          if (leaf != null) {
            fields = leaf.resolvedCustomFields;
            if (fields == null || fields.isEmpty) {
              fields = CustomFieldsResolver.resolveForLeaf(
                leaf: leaf,
                subcategoryById: {leaf.id: leaf},
                category: cat,
              );
            }
          }
          setState(() {
            _data.categoryCustomFields =
                (fields != null && fields.isNotEmpty) ? fields : cat.customFields;
            _data.categoryModelForAds = cat;
            _data.leafSubcategoryForAds = leaf;
          });
        }
      });
    }
    _controller = PageController(initialPage: startIndex);
    _currentIndex = startIndex;
  }

  List<String> get _steps => [
    AppLocale.tr('step_category'),
    AppLocale.tr('step_subcategory'),
    AppLocale.tr('step_details'),
    AppLocale.tr('step_location'),
    AppLocale.tr('step_images'),
  ];

  void _next() async {
    if (_currentIndex < _steps.length - 1) {
      if (_currentIndex == 0) {
        if (_data.categoryId == null) {
          showToast(message: AppLocale.tr('choose_category_first_message'));
          return;
        }
      }
      if (_currentIndex == 1) {
        if (_data.subcategoryId == null) {
          showToast(message: AppLocale.tr('choose_category_first'));
          return;
        }
      }
      if (_currentIndex == 2) {
        _saveDetailsFromStep();
        if (_data.title.trim().isEmpty) {
          showToast(message: AppLocale.tr('ad_title_required'));
          return;
        }
        if (_data.description.trim().isEmpty) {
          showToast(message: AppLocale.tr('ad_description_required'));
          return;
        }
        final requiredError = _validateRequiredCustomFields();
        if (requiredError != null) {
          showToast(message: requiredError);
          return;
        }
      }
      if (_currentIndex == 3) {
        await _locationKey.currentState?.waitForPendingGeocode();
        if (!mounted) return;
        _saveLocationFromStep();
        final locErr = _validateLocationStep();
        if (locErr != null) {
          showToast(message: locErr);
          return;
        }
      }
      // قبل الانتقال لخطوة التفاصيل: تأكد من تحميل الحقول المخصصة إن كانت فارغة
      if (_currentIndex == 1 &&
          (_data.categoryCustomFields == null || _data.categoryCustomFields!.isEmpty) &&
          _data.categoryId != null) {
        final cat = await CategoryService.getCategory(
          _data.categoryId!,
          forceRefresh: true,
        );
        if (mounted && cat != null) {
          _data.categoryCustomFields = cat.customFields;
          _data.categoryModelForAds = cat;
        }
      }
      if (!mounted) return;
      setState(() => _currentIndex++);
      _controller.animateToPage(
        _currentIndex,
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeInOut,
      );
    }
  }

  void _back() {
    if (_currentIndex > 0) {
      setState(() => _currentIndex--);
      _controller.animateToPage(
        _currentIndex,
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeInOut,
      );
    }
  }

  void _saveDetailsFromStep() {
    if (_detailsKey.currentState != null) {
      _detailsKey.currentState!.saveToData(_data);
    }
  }

  final GlobalKey<_DetailsStepState> _detailsKey = GlobalKey();
  final GlobalKey<_LocationStepState> _locationKey = GlobalKey();

  void _saveLocationFromStep() {
    if (_locationKey.currentState != null) {
      _locationKey.currentState!.saveToData(_data);
    }
  }

  String? _validateLocationStep() {
    final c = _data.locationCountry.trim().toUpperCase();
    if (c != 'SY' && c != 'TR') {
      return AppLocale.tr('ad_location_country_required');
    }
    // Requirement: هذه الخطوة خريطة فقط — يجب وجود إحداثيات + اختيار مستوى المحافظة/المدينة.
    if (_data.latitude == null || _data.longitude == null) {
      return AppLocale.tr('ad_location_map_required');
    }
    if (_data.locationStateCode == null ||
        _data.locationStateCode!.isEmpty ||
        _data.locationCityCode == null ||
        _data.locationCityCode!.isEmpty) {
      return AppLocale.tr('ad_location_map_levels_required');
    }
    return null;
  }

  String? _validateRequiredCustomFields() {
    final fields = _data.categoryCustomFields ?? [];
    for (final f in fields) {
      if (f['is_active'] == false) continue;
      if (f['required'] != true) continue;
      final id = (f['id'] ?? '').toString();
      final val = _data.customFields[id];
      final type = f['type'] ?? 'text';

      // حقل الموقع: الاعتماد فقط على وجود خط العرض والطول (مثل الموقع)
      if (type == 'location') {
        final lat = (val is Map) ? (val['latitude'] ?? val['lat']) : null;
        final lng = (val is Map) ? (val['longitude'] ?? val['lng']) : null;
        final hasValidCoords = lat != null && lng != null &&
            lat.toString().trim().isNotEmpty && lng.toString().trim().isNotEmpty &&
            num.tryParse(lat.toString()) != null && num.tryParse(lng.toString()) != null;
        if (!hasValidCoords) {
          final label = f['label'];
          String labelStr = id;
          if (label is Map) {
            labelStr = (label['ar'] ?? label['en'] ?? label['tr'] ?? id).toString();
          } else if (label != null) {
            labelStr = label.toString();
          }
          return AppLocale.tr('field_required_location').replaceAll('%s', labelStr);
        }
        continue;
      }

      // باقي الحقول: التحقق من القيمة (لا نستخدم 'value' لحقل الموقع)
      final isEmpty = val == null ||
          (val is String && val.trim().isEmpty) ||
          (val is Map && (val['value'] == null || val['value'].toString().trim().isEmpty));
      if (isEmpty) {
        final label = f['label'];
        String labelStr = id;
        if (label is Map) {
          labelStr = (label['ar'] ?? label['en'] ?? label['tr'] ?? id).toString();
        } else if (label != null) {
          labelStr = label.toString();
        }
        return AppLocale.tr('field_required').replaceAll('%s', labelStr);
      }
    }
    return null;
  }

  Future<void> _handleBackOrExit() async {
    final confirm = await WarningConfirmDialog.show(
      context,
      title: AppLocale.tr('delete_account_warning'),
      message: AppLocale.tr('closing_ad'),
      confirmText: AppLocale.tr('close_ad'),
      cancelText: 'الرجوع',
      confirmOutline: true,
    );
    if (confirm && mounted) context.pop();
  }

  /// يسمح فقط بالانتقال للخطوات السابقة أو الحالية (لا قفز لخطوة لاحقة قبل إتمام الحالية).
  void _goToStep(int index) {
    if (index > _currentIndex) return;
    setState(() => _currentIndex = index);
    _controller.animateToPage(
      index,
      duration: const Duration(milliseconds: 300),
      curve: Curves.easeInOut,
    );
  }

  Widget _buildStepIndicator() {
    return Container(
      width: double.infinity,
      padding: EdgeInsets.symmetric(vertical: 14.h, horizontal: 8.w),
      color: AppColors.darkBlue,
      child: SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        child: Row(
          children: List.generate(_steps.length, (index) {
            final isSelected = index == _currentIndex;
            final isPastOrCurrent = index <= _currentIndex;
            return Padding(
              padding: EdgeInsets.only(right: index < _steps.length - 1 ? 12.w : 0),
              child: GestureDetector(
                onTap: isPastOrCurrent ? () => _goToStep(index) : null,
                child: Opacity(
                  opacity: isPastOrCurrent ? 1.0 : 0.6,
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      CircleAvatar(
                        radius: isSelected ? 16.r : 14.r,
                        backgroundColor:
                            isSelected ? Colors.white : Colors.white24,
                        child: Text(
                          '${index + 1}',
                          style: TextStyle(
                            color: isSelected
                                ? AppColors.darkBlue
                                : Colors.white70,
                            fontSize: 12.sp,
                            fontWeight:
                                isSelected ? FontWeight.bold : FontWeight.w500,
                          ),
                        ),
                      ),
                      SizedBox(width: 8.w),
                      Text(
                        _steps[index],
                        style: TextStyle(
                          fontSize: 11.sp,
                          color: isSelected ? Colors.white : Colors.white70,
                          fontWeight:
                              isSelected ? FontWeight.w600 : FontWeight.w500,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            );
          }),
        ),
      ),
    );
  }

  void _showFreeAdsLimitDialog() {
    if (!mounted) return;
    showDialog<void>(
      context: context,
      barrierDismissible: true,
      builder: (ctx) => AlertDialog(
        title: Text(AppLocale.tr('free_ads_limit_title')),
        content: Text(
          AppLocale.tr('free_ads_limit_body'),
          style: const TextStyle(height: 1.35),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: Text(AppLocale.tr('cancel')),
          ),
          FilledButton(
            onPressed: () {
              Navigator.of(ctx).pop();
              context.push(const QutaPages());
            },
            child: Text(AppLocale.tr('subscribe_package_cta')),
          ),
        ],
      ),
    );
  }

  Future<void> _publish() async {
    if (_isPublishing) return;

    if (!TokenStorage.hasToken()) {
      showToast(message: AppLocale.tr('login_required'));
      if (mounted) context.push(LoginPage());
      return;
    }

    _saveDetailsFromStep();
    await _locationKey.currentState?.waitForPendingGeocode();
    if (!mounted) return;
    _saveLocationFromStep();
    if (_data.title.trim().isEmpty) {
      showToast(message: AppLocale.tr('ad_title_required'));
      return;
    }
    if (_data.description.trim().isEmpty) {
      showToast(message: AppLocale.tr('ad_description_required'));
      return;
    }
    if (_data.categoryId == null || _data.subcategoryId == null) {
      showToast(message: AppLocale.tr('choose_category_first'));
      return;
    }
    final requiredError = _validateRequiredCustomFields();
    if (requiredError != null) {
      showToast(message: requiredError);
      return;
    }
    final locErr = _validateLocationStep();
    if (locErr != null) {
      showToast(message: locErr);
      return;
    }
    final imgCfg = AdImagesEffective.resolve(
      _data.categoryModelForAds,
      _data.leafSubcategoryForAds,
    );
    if (imgCfg.isAdminGallery) {
      final p = _data.selectedGalleryPath?.trim() ?? '';
      if (p.isEmpty) {
        showToast(message: AppLocale.tr('gallery_pick_required'));
        return;
      }
    } else {
      if (_data.images.isEmpty) {
        showToast(message: AppLocale.tr('ad_images_required'));
        return;
      }
    }

    setState(() => _isPublishing = true);
    showToast(message: AppLocale.tr('publishing_ad'));

    try {
      final result = await AdService.createAd(
        categoryId: _data.categoryId!,
        subcategoryId: _data.subcategoryId!,
        title: _data.title.trim(),
        description: _data.description.trim(),
        price: _data.price != null && _data.price!.isNotEmpty
            ? num.tryParse(_data.price!)
            : null,
        currency: _data.currency,
        locationCountry: _data.locationCountry.isNotEmpty ? _data.locationCountry : null,
        locationInputMethod: _data.locationInputMethod,
        locationStateCode: _data.locationStateCode,
        locationCityCode: _data.locationCityCode,
        locationDistrictCode: _data.locationDistrictCode,
        locationState: _data.locationState.isNotEmpty ? _data.locationState : null,
        locationCity: _data.locationCity.isNotEmpty ? _data.locationCity : null,
        locationDistrict:
            _data.locationDistrict.isNotEmpty ? _data.locationDistrict : null,
        locationAddress: _data.locationAddress.isNotEmpty ? _data.locationAddress : null,
        latitude: _data.latitude,
        longitude: _data.longitude,
        customFields:
            _data.customFields.isNotEmpty ? _data.customFields : null,
        imageFiles: imgCfg.isAdminGallery ? null : _data.images,
        galleryImagePath:
            imgCfg.isAdminGallery ? _data.selectedGalleryPath : null,
        videoFile: _data.video,
      );

      if (!mounted) return;

      if (result.success) {
        showToast(message: result.message);
        context.pushAndRemoveUntil(ThankYouPage());
      } else if (result.shouldOfferPackages) {
        _showFreeAdsLimitDialog();
      } else {
        final isUnauth =
            result.message.toLowerCase().contains('unauthenticated');
        if (isUnauth && mounted) {
          showToast(message: AppLocale.tr('session_expired_login_again'));
          context.push(LoginPage());
        } else {
          showToast(
            message: result.message.isEmpty
                ? AppLocale.tr('ad_created_failed')
                : result.message,
          );
        }
      }
    } finally {
      if (mounted) setState(() => _isPublishing = false);
    }
  }

  Widget _buildBottomNav() {
    final isLastStep = _currentIndex == _steps.length - 1;
    return Padding(
      padding: const EdgeInsets.all(16),
      child: Row(
        children: [
          Expanded(
            child: CustomButton(
              text: isLastStep
                  ? (_isPublishing ? AppLocale.tr('publishing_ad') : AppLocale.tr('submit_ad'))
                  : AppLocale.tr('next'),
              onTap: isLastStep
                  ? (_isPublishing ? () {} : _publish)
                  : _next,
            ),
          ),
          const SizedBox(width: 10),
          _currentIndex > 0
              ? Expanded(
                  child: GestureDetector(
                    onTap: _back,
                    child: Container(
                      width: double.infinity,
                      height: 45.h,
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(8.r),
                        color: AppColors.darkBlue,
                      ),
                      child: Center(
                        child: Text(
                          AppLocale.tr('previous'),
                          style: TextStyle(
                            fontSize: 14.sp,
                            fontWeight: FontWeight.w600,
                            color: Colors.white,
                          ),
                        ),
                      ),
                    ),
                  ),
                )
              : Expanded(
                  child: GestureDetector(
                    onTap: _handleBackOrExit,
                    child: Container(
                      width: double.infinity,
                      height: 45.h,
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(8.r),
                        color: Colors.white,
                      ),
                      child: Center(
                        child: Text(
                          "إلغاء",
                          style: TextStyle(
                            fontSize: 14.sp,
                            fontWeight: FontWeight.w600,
                            color: Colors.grey[400],
                          ),
                        ),
                      ),
                    ),
                  ),
                ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, result) async {
        if (didPop) return;
        await _handleBackOrExit();
      },
      child: Scaffold(
        appBar: CustomAppbar(
          title: widget.title,
          onBackPressed: _handleBackOrExit,
        ),
        body: Column(
        children: [
          _buildStepIndicator(),
          Divider(height: 1, color: Colors.grey.shade300),
          Expanded(
            child: PageView(
              controller: _controller,
              physics: const NeverScrollableScrollPhysics(),
              children: [
                _CategoryStep(data: _data, onNext: _next),
                _SubcategoryStep(data: _data, onNext: _next),
                _DetailsStep(key: _detailsKey, data: _data, onNext: _next),
                _LocationStep(key: _locationKey, data: _data),
                _ImagesStep(data: _data),
              ],
            ),
          ),
          _buildBottomNav(),
        ],
      ),
    ));
  }
}

class _CategoryStep extends StatefulWidget {
  final CreateAdData data;
  final VoidCallback onNext;

  const _CategoryStep({required this.data, required this.onNext});

  @override
  State<_CategoryStep> createState() => _CategoryStepState();
}

class _CategoryStepState extends State<_CategoryStep> {
  List<CategoryModel> _categories = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    // `/home` يملأ الكاش بفئات بدون custom_fields؛ نجبر الجلب الكامل لإنشاء الإعلان
    final list = await CategoryService.getCategories(forceRefresh: true);
    if (mounted) {
      setState(() {
        _categories = list;
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return Center(
        child: CircularProgressIndicator(color: AppColors.darkBlue),
      );
    }
    return ListView.builder(
      padding: EdgeInsets.all(16.w),
      itemCount: _categories.length,
      itemBuilder: (context, i) {
        final c = _categories[i];
        return Card(
          elevation: 2,
          shadowColor: Colors.black26,
          margin: EdgeInsets.only(bottom: 12.h),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(8.r),
          ),
          child: ListTile(
            contentPadding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 8.h),
            leading: SizedBox(
              width: 48.w,
              height: 48.h,
              child: c.icon != null
                  ? CachedUrlImage(
                      imageUrl: c.icon!,
                      fit: BoxFit.contain,
                      errorBuilder: (_, __) => Icon(
                        Icons.folder,
                        color: AppColors.darkBlue,
                        size: 28.sp,
                      ),
                    )
                  : Icon(Icons.folder, color: AppColors.darkBlue, size: 28.sp),
            ),
            title: Text(
              c.displayName,
              style: TextStyle(
                fontSize: 15.sp,
                fontWeight: FontWeight.w500,
              ),
            ),
            trailing: Icon(Icons.chevron_left, size: 22.sp, color: Colors.grey.shade600),
            onTap: () {
              widget.data.categoryId = c.id;
              widget.data.categoryName = c.name;
              widget.data.categoryCustomFields = c.customFields;
              widget.data.categoryModelForAds = c;
              widget.data.subcategoryId = null;
              widget.data.subcategoryPath.clear();
              widget.data.leafSubcategoryForAds = null;
              widget.data.selectedGalleryPath = null;
              widget.data.images = [];
              widget.onNext();
            },
          ),
        );
      },
    );
  }
}

class _SubcategoryStep extends StatefulWidget {
  final CreateAdData data;
  final VoidCallback onNext;

  const _SubcategoryStep({required this.data, required this.onNext});

  @override
  State<_SubcategoryStep> createState() => _SubcategoryStepState();
}

class _SubcategoryStepState extends State<_SubcategoryStep> {
  List<SubcategoryModel> _items = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    if (widget.data.categoryId == null) return;
    List<SubcategoryModel> list;
    final path = widget.data.subcategoryPath;
    if (path.isEmpty) {
      list = await CategoryService.getSubcategories(widget.data.categoryId!);
    } else {
      // عند الرجوع من خطوة التفاصيل وكان آخر اختيار "leaf"؛
      // نحمّل مستوى الأب بدل محاولة تحميل أبناء leaf (تكون فارغة).
      final selectedLeaf = widget.data.subcategoryId != null;
      String? levelParentId;
      if (selectedLeaf) {
        levelParentId = path.length > 1 ? path[path.length - 2]['id'] : null;
      } else {
        levelParentId = path.last['id'];
      }
      if (levelParentId == null || levelParentId.isEmpty) {
        list = await CategoryService.getSubcategories(widget.data.categoryId!);
      } else {
        list = await CategoryService.getSubcategoryChildren(int.parse(levelParentId));
      }
    }
    if (mounted) {
      setState(() {
        _items = list;
        _loading = false;
      });
    }
  }

  Future<void> _select(SubcategoryModel sub) async {
    widget.data.subcategoryPath.add({'id': '${sub.id}', 'name': sub.displayName});
    setState(() => _loading = true);
    final children = await CategoryService.getSubcategoryChildren(sub.id);
    if (mounted) {
      setState(() => _loading = false);
      if (children.isNotEmpty) {
        setState(() => _items = children);
      } else {
        widget.data.subcategoryId = sub.id;
        widget.data.leafSubcategoryForAds = sub;
        widget.data.selectedGalleryPath = null;
        widget.data.images = [];
        final full = await CategoryService.getSubcategory(sub.id);
        final leaf = full ?? sub;
        widget.data.leafSubcategoryForAds = leaf;
        var fields = leaf.resolvedCustomFields;
        if (fields == null || fields.isEmpty) {
          fields = CustomFieldsResolver.resolveForLeaf(
            leaf: leaf,
            subcategoryById: {for (final s in _items) s.id: s, leaf.id: leaf},
            category: widget.data.categoryModelForAds,
          );
        }
        if (fields.isNotEmpty) {
          widget.data.categoryCustomFields = fields;
        }
        widget.onNext();
      }
    }
  }

  void _goBack() {
    if (widget.data.subcategoryPath.isEmpty) return;
    widget.data.subcategoryPath.removeLast();
    setState(() => _loading = true);
    _load();
  }

  @override
  Widget build(BuildContext context) {
    if (widget.data.categoryId == null) {
      return Center(child: Text(AppLocale.tr('choose_category_first_message')));
    }
    if (_loading) {
      return Center(
        child: CircularProgressIndicator(color: AppColors.darkBlue),
      );
    }
    return Column(
      children: [
        if (widget.data.subcategoryPath.isNotEmpty)
          Padding(
            padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 8.h),
            child: Row(
              children: [
                TextButton.icon(
                  onPressed: _goBack,
                  icon: Icon(Icons.arrow_back, size: 18.sp),
                  label: Text(AppLocale.tr('back')),
                ),
                Expanded(
                  child: Text(
                    widget.data.subcategoryPath.map((e) => e['name']).join(' > '),
                    style: TextStyle(fontSize: 12.sp),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
          ),
        Expanded(
          child: ListView.builder(
            padding: EdgeInsets.all(16.w),
            itemCount: _items.length,
            itemBuilder: (context, i) {
              final s = _items[i];
              return Card(
                margin: EdgeInsets.only(bottom: 12.h),
                child: ListTile(
                  contentPadding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 8.h),
                  leading: SizedBox(
                    width: 48.w,
                    height: 48.h,
                    child: s.icon != null
                        ? CachedUrlImage(
                            imageUrl: s.icon!,
                            fit: BoxFit.contain,
                            errorBuilder: (_, __) => Icon(
                              Icons.category,
                              color: AppColors.darkBlue,
                              size: 28.sp,
                            ),
                          )
                        : Icon(Icons.category, color: AppColors.darkBlue, size: 28.sp),
                  ),
                  title: Text(s.displayName),
                  trailing: Icon(Icons.arrow_forward_ios, size: 14.sp),
                  onTap: () => _select(s),
                ),
              );
            },
          ),
        ),
      ],
    );
  }
}

class _DetailsStep extends StatefulWidget {
  final CreateAdData data;
  final VoidCallback onNext;

  const _DetailsStep({super.key, required this.data, required this.onNext});

  @override
  State<_DetailsStep> createState() => _DetailsStepState();
}

class _DetailsStepState extends State<_DetailsStep> {
  final Map<String, TextEditingController> _customControllers = {};
  final Map<String, bool> _customCheckboxes = {};
  final Map<String, String> _customCurrency = {}; // fieldId -> currency code for number+currency
  final Map<String, bool> _customTbd = {}; // fieldId -> true when "يُحدَّد لاحقًا" is chosen for number+currency
  final Map<String, Map<String, dynamic>> _customCarBodyMaps = {};

  @override
  void initState() {
    super.initState();
    _initCustomFields();
  }

  @override
  void didUpdateWidget(covariant _DetailsStep oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.data.categoryCustomFields != widget.data.categoryCustomFields &&
        (widget.data.categoryCustomFields?.isNotEmpty ?? false)) {
      for (final c in _customControllers.values) {
        c.dispose();
      }
      _customControllers.clear();
      _customCheckboxes.clear();
      _customCurrency.clear();
      _customTbd.clear();
      _customCarBodyMaps.clear();
      _initCustomFields();
      if (mounted) setState(() {});
    }
  }

  void _initCustomFields() {
    final fields = _getActiveCustomFields();
    for (final f in fields) {
      final id = (f['id'] ?? 'field_${fields.indexOf(f)}').toString();
      final type = f['type'] ?? 'text';
      if (type == 'car_body_map') {
        _customCarBodyMaps[id] = CarBodyMapSupport.normalizeValue(widget.data.customFields[id]);
      } else if (type == 'location') {
        final val = widget.data.customFields[id];
        Map<String, dynamic> locMap = {};
        if (val is Map) {
          locMap = Map<String, dynamic>.from(val);
        }
        _customControllers[id] = TextEditingController(
          text: (locMap['address'] ?? '').toString(),
        );
        _customControllers['${id}_lat'] = TextEditingController(
          text: (locMap['latitude'] ?? locMap['lat'] ?? '').toString(),
        );
        _customControllers['${id}_lng'] = TextEditingController(
          text: (locMap['longitude'] ?? locMap['lng'] ?? '').toString(),
        );
      } else if (type == 'number' && (f['show_currency'] == true)) {
        final val = widget.data.customFields[id];
        final isTbd = val is Map && (val['tbd'] == true || val['tbd'] == '1');
        if (isTbd) {
          _customTbd[id] = true;
          _customControllers[id] = TextEditingController(text: '');
          _customCurrency[id] = 'SYP';
        } else if (val is Map) {
          final raw = (val['value'] ?? '').toString();
          final numVal = num.tryParse(raw);
          _customControllers[id] = TextEditingController(
            text: numVal != null ? NumeralHelper.formatWithThousands(numVal) : raw,
          );
          _customCurrency[id] = (val['currency'] ?? 'SYP').toString();
        } else {
          final raw = val?.toString() ?? '';
          final numVal = num.tryParse(raw);
          _customControllers[id] = TextEditingController(
            text: numVal != null ? NumeralHelper.formatWithThousands(numVal) : raw,
          );
          _customCurrency[id] = 'SYP';
        }
      } else if (type != 'checkbox' && type != 'car_body_map') {
        final val = widget.data.customFields[id];
        final raw = val?.toString() ?? '';
        final numVal = type == 'number' ? num.tryParse(raw) : null;
        _customControllers[id] = TextEditingController(
          text: (type == 'number' && numVal != null) ? NumeralHelper.formatWithThousands(numVal) : raw,
        );
      } else {
        _customCheckboxes[id] = widget.data.customFields[id] == true ||
            widget.data.customFields[id] == '1';
      }
    }
  }


  /// الحقول المخصصة: تظهر كل الحقول ما لم يكن is_active == false صراحة
  List<Map<String, dynamic>> _getActiveCustomFields() {
    final list = widget.data.categoryCustomFields ?? [];
    return list.where((f) => f['is_active'] != false).toList();
  }

  String _getFieldLabel(Map<String, dynamic> field) {
    final label = field['label'];
    if (label == null) return (field['id'] ?? '').toString();
    if (label is Map) {
      final locale = AppLocale.current;
      return (label[locale] ?? label['ar'] ?? label['en'] ?? label['tr'] ?? '')
          .toString();
    }
    return label.toString();
  }

  String _getOptionLabel(dynamic option) {
    if (option == null) return '';
    if (option is Map) {
      final locale = AppLocale.current;
      return (option[locale] ?? option['ar'] ?? option['en'] ?? option['tr'] ?? '')
          .toString();
    }
    return option.toString();
  }

  Widget _buildCustomField(Map<String, dynamic> field) {
    final id = (field['id'] ?? 'field_').toString();
    final type = field['type'] ?? 'text';
    final label = _getFieldLabel(field);
    final isRequired = field['required'] == true;
    final labelText = isRequired ? '$label *' : label;

    if (type == 'textarea') {
      final c = _customControllers[id];
      if (c == null) return const SizedBox.shrink();
      return Padding(
        padding: EdgeInsets.only(bottom: 12.h),
        child: TextFormWithLabel(
          hintText: label,
          controller: c,
          labelText: labelText,
          maxlines: 4,
          keyboardType: TextInputType.multiline,
          obscureText: false,
        ),
      );
    }
    if (type == 'number') {
      final c = _customControllers[id];
      if (c == null) return const SizedBox.shrink();
      final showCurrency = field['show_currency'] == true;
      final allowTbd = field['allow_tbd'] == true;
      final isTbd = _customTbd[id] == true;
      if (showCurrency) {
        final currency = _customCurrency[id] ?? 'SYP';
        return Padding(
          padding: EdgeInsets.only(bottom: 12.h),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(labelText, style: TextStyle(fontSize: 14.sp, fontWeight: FontWeight.w500)),
              SizedBox(height: 8.h),
              if (allowTbd)
                Padding(
                  padding: EdgeInsets.only(bottom: 8.h),
                  child: CheckboxListTile(
                    value: isTbd,
                    onChanged: (v) {
                      setState(() {
                        _customTbd[id] = v ?? false;
                        if (v == true) c.clear();
                      });
                    },
                    title: Text(
                      AppLocale.tr('price_tbd'),
                      style: TextStyle(fontSize: 13.sp),
                    ),
                    contentPadding: EdgeInsets.zero,
                    controlAffinity: ListTileControlAffinity.leading,
                    dense: true,
                  ),
                ),
              Container(
                decoration: BoxDecoration(
                  border: Border.all(color: Colors.grey.shade400),
                  borderRadius: BorderRadius.circular(10.r),
                ),
                child: Row(
                  children: [
                    Expanded(
                      flex: 2,
                      child: TextFormField(
                        controller: c,
                        keyboardType: const TextInputType.numberWithOptions(decimal: true),
                        inputFormatters: [ThousandSeparatorInputFormatter(allowDecimal: true)],
                        decoration: InputDecoration(
                          hintText: label,
                          hintStyle: TextStyle(fontSize: 14.sp, color: Colors.grey),
                          border: InputBorder.none,
                          contentPadding: EdgeInsets.symmetric(horizontal: 14.w, vertical: 14.h),
                          filled: false,
                        ),
                        style: TextStyle(fontSize: 14.sp),
                      ),
                    ),
                    Container(
                      width: 1,
                      height: 36.h,
                      color: Colors.grey.shade300,
                    ),
                    Expanded(
                      child: DropdownButtonHideUnderline(
                        child: DropdownButtonFormField<String>(
                          value: currency,
                          decoration: const InputDecoration(
                            border: InputBorder.none,
                            contentPadding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          ),
                          isExpanded: true,
                          items: [
                            DropdownMenuItem(value: 'SYP', child: Text(CurrencyHelper.symbol('SYP'), style: TextStyle(fontSize: 14.sp))),
                            DropdownMenuItem(value: 'TRY', child: Text(CurrencyHelper.symbol('TRY'), style: TextStyle(fontSize: 14.sp))),
                            DropdownMenuItem(value: 'USD', child: Text(CurrencyHelper.symbol('USD'), style: TextStyle(fontSize: 14.sp))),
                            DropdownMenuItem(value: 'EUR', child: Text(CurrencyHelper.symbol('EUR'), style: TextStyle(fontSize: 14.sp))),
                          ],
                          onChanged: (v) {
                            if (v != null) setState(() => _customCurrency[id] = v);
                          },
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        );
      }
      return Padding(
        padding: EdgeInsets.only(bottom: 12.h),
        child: TextFormWithLabel(
          hintText: label,
          controller: c,
          labelText: labelText,
          keyboardType: const TextInputType.numberWithOptions(decimal: true),
          obscureText: false,
          inputFormatters: [ThousandSeparatorInputFormatter(allowDecimal: true)],
        ),
      );
    }
    if (type == 'select') {
      final options = (field['options'] as List?) ?? [];
      String? currentVal;
      final c = _customControllers[id];
      if (c != null && c.text.isNotEmpty) currentVal = c.text;
      final validValues = options.map((opt) {
        final v = opt is Map
            ? (opt['ar'] ?? opt['en'] ?? opt['tr'] ?? '').toString()
            : opt.toString();
        return v;
      }).toList();
      if (currentVal != null && currentVal.isNotEmpty && !validValues.contains(currentVal)) {
        currentVal = null;
      }
      return Padding(
        padding: EdgeInsets.only(bottom: 12.h),
        child: DropdownButtonFormField<String?>(
          value: currentVal,
          decoration: InputDecoration(
            labelText: labelText,
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(8.r)),
          ),
          items: [
            DropdownMenuItem<String?>(value: null, child: Text(AppLocale.tr('select_option'))),
            ...options.map((opt) {
              final val = opt is Map
                  ? (opt['ar'] ?? opt['en'] ?? opt['tr'] ?? '').toString()
                  : opt.toString();
              return DropdownMenuItem<String?>(
                value: val.isEmpty ? null : val,
                child: Text(_getOptionLabel(opt)),
              );
            }),
          ],
          onChanged: (v) {
            final ctrl = _customControllers[id];
            if (ctrl != null) ctrl.text = v ?? '';
          },
        ),
      );
    }
    if (type == 'checkbox') {
      final checked = _customCheckboxes[id] ?? false;
      return Padding(
        padding: EdgeInsets.only(bottom: 12.h),
        child: Row(
          children: [
            Checkbox(
              value: checked,
              onChanged: (v) {
                setState(() => _customCheckboxes[id] = v ?? false);
              },
              activeColor: AppColors.darkBlue,
            ),
            Expanded(child: Text(labelText, style: TextStyle(fontSize: 14.sp))),
          ],
        ),
      );
    }
    if (type == 'date') {
      final c = _customControllers[id];
      if (c == null) return const SizedBox.shrink();
      return Padding(
        padding: EdgeInsets.only(bottom: 12.h),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(labelText, style: TextStyle(fontSize: 14.sp, fontWeight: FontWeight.w500)),
            SizedBox(height: 8.h),
            InkWell(
              onTap: () async {
                final now = DateTime.now();
                DateTime? initial;
                if (c.text.trim().isNotEmpty) {
                  final parts = c.text.trim().split('-');
                  if (parts.length == 3) {
                    initial = DateTime.tryParse(c.text.trim());
                  }
                }
                final picked = await showDatePicker(
                  context: context,
                  initialDate: initial ?? now,
                  firstDate: DateTime(now.year - 10),
                  lastDate: DateTime(now.year + 30),
                );
                if (picked != null) {
                  c.text =
                      '${picked.year.toString().padLeft(4, '0')}-${picked.month.toString().padLeft(2, '0')}-${picked.day.toString().padLeft(2, '0')}';
                  if (mounted) setState(() {});
                }
              },
              child: Container(
                width: double.infinity,
                padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 14.h),
                decoration: BoxDecoration(
                  border: Border.all(color: Colors.grey.shade400),
                  borderRadius: BorderRadius.circular(10.r),
                ),
                child: Row(
                  children: [
                    Icon(Icons.calendar_today, size: 20.sp, color: AppColors.darkBlue),
                    SizedBox(width: 8.w),
                    Expanded(
                      child: Text(
                        c.text.trim().isEmpty ? label : c.text.trim(),
                        style: TextStyle(
                          fontSize: 14.sp,
                          color: c.text.trim().isEmpty ? Colors.grey : Colors.black,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      );
    }
    if (type == 'car_body_map') {
      return CarBodyMapWidget(
        label: labelText,
        initialValue: _customCarBodyMaps[id],
        onChanged: (value) {
          setState(() => _customCarBodyMaps[id] = value);
        },
      );
    }
    if (type == 'location') {
      final addrC = _customControllers[id];
      final latC = _customControllers['${id}_lat'];
      final lngC = _customControllers['${id}_lng'];
      if (addrC == null || latC == null || lngC == null) return const SizedBox.shrink();
      final hasLocation = latC.text.trim().isNotEmpty && lngC.text.trim().isNotEmpty;
      return Padding(
        padding: EdgeInsets.only(bottom: 12.h),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(labelText, style: TextStyle(fontSize: 14.sp, fontWeight: FontWeight.w500)),
            SizedBox(height: 8.h),
            OutlinedButton.icon(
              onPressed: () => _pickMyLocation(id),
              icon: Icon(Icons.my_location, size: 20.sp, color: AppColors.darkBlue),
              label: Text(AppLocale.tr('use_my_location')),
              style: OutlinedButton.styleFrom(
                foregroundColor: AppColors.darkBlue,
                side: BorderSide(color: AppColors.darkBlue),
              ),
            ),
            SizedBox(height: 12.h),
            InlineMapPicker(
              initialLat: double.tryParse(latC.text.trim()),
              initialLng: double.tryParse(lngC.text.trim()),
              height: _inlineMapPickerHeight(context),
              onLocationSelected: (lat, lng, address) {
                latC.text = lat.toString();
                lngC.text = lng.toString();
                addrC.text = address;
                if (mounted) setState(() {});
              },
            ),
            SizedBox(height: 8.h),
            Text(
              hasLocation
                  ? '${AppLocale.tr('location_determined')}${addrC.text.trim().isNotEmpty ? ': ${addrC.text}' : ' (${latC.text}, ${lngC.text})'}'
                  : AppLocale.tr('location_not_determined'),
              style: TextStyle(fontSize: 12.sp, color: Colors.grey[600]),
            ),
            SizedBox(height: 8.h),
            TextFormWithLabel(
              hintText: AppLocale.tr('address_hint'),
              controller: addrC,
              labelText: AppLocale.tr('address_hint'),
              keyboardType: TextInputType.streetAddress,
              obscureText: false,
            ),
          ],
        ),
      );
    }
    // text (default)
    final c = _customControllers[id];
    if (c == null) return const SizedBox.shrink();
    return Padding(
      padding: EdgeInsets.only(bottom: 12.h),
      child: TextFormWithLabel(
        hintText: label,
        controller: c,
        labelText: labelText,
        keyboardType: TextInputType.text,
        obscureText: false,
      ),
    );
  }

  Future<void> _pickMyLocation(String fieldId) async {
    final latC = _customControllers['${fieldId}_lat'];
    final lngC = _customControllers['${fieldId}_lng'];
    final addrC = _customControllers[fieldId];
    if (latC == null || lngC == null || addrC == null) return;
    final serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      showToast(message: AppLocale.tr('location_error'));
      return;
    }
    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }
    if (permission == LocationPermission.denied || permission == LocationPermission.deniedForever) {
      showToast(message: AppLocale.tr('location_permission_denied'));
      return;
    }
    if (!mounted) return;
    showToast(message: AppLocale.tr('getting_location'));
    try {
      final position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.medium,
      );
      latC.text = position.latitude.toString();
      lngC.text = position.longitude.toString();
      try {
        final placemarks = await placemarkFromCoordinates(position.latitude, position.longitude);
        if (placemarks.isNotEmpty) {
          final p = placemarks.first;
          final parts = [
            p.street,
            p.subLocality,
            p.locality,
            p.administrativeArea,
            p.country,
          ].where((e) => e != null && e.toString().trim().isNotEmpty).toList();
          addrC.text = parts.join(', ');
        }
      } catch (_) {}
      if (mounted) setState(() {});
      showToast(message: AppLocale.tr('location_determined'));
    } catch (e) {
      showToast(message: AppLocale.tr('location_error'));
    }
  }

  @override
  void dispose() {
    for (final c in _customControllers.values) {
      c.dispose();
    }
    super.dispose();
  }

  void saveToData(CreateAdData data) {
    data.customFields.clear();
    for (final f in _getActiveCustomFields()) {
      final id = (f['id'] ?? 'field_${_getActiveCustomFields().indexOf(f)}').toString();
      final type = f['type'] ?? 'text';
      if (type == 'checkbox') {
        final checked = _customCheckboxes[id] ?? false;
        data.customFields[id] = checked ? 1 : 0;
      } else if (type == 'car_body_map') {
        data.customFields[id] =
            _customCarBodyMaps[id] ?? CarBodyMapSupport.normalizeValue(null);
      } else if (type == 'location') {
        final latC = _customControllers['${id}_lat'];
        final lngC = _customControllers['${id}_lng'];
        final addrC = _customControllers[id];
        final lat = (latC?.text.trim().isEmpty ?? true) ? null : num.tryParse(latC!.text);
        final lng = (lngC?.text.trim().isEmpty ?? true) ? null : num.tryParse(lngC!.text);
        data.customFields[id] = {
          'latitude': lat,
          'longitude': lng,
          'address': (addrC != null ? addrC.text.trim() : ''),
        };
      } else {
        final c = _customControllers[id];
        if (type == 'number' && (f['show_currency'] == true) && (_customTbd[id] == true)) {
          data.customFields[id] = {'tbd': true};
        } else if (c != null && c.text.trim().isNotEmpty) {
          if (type == 'number' && (f['show_currency'] == true)) {
            data.customFields[id] = {
              'value': NumeralHelper.parseFormattedAmount(c.text) ?? num.tryParse(c.text) ?? c.text,
              'currency': _customCurrency[id] ?? 'SYP',
            };
          } else if (type == 'number') {
            data.customFields[id] = NumeralHelper.parseFormattedAmount(c.text) ?? num.tryParse(c.text) ?? c.text;
          } else {
            data.customFields[id] = c.text;
          }
        }
      }
    }
    // استخراج الحقول الأساسية من custom_fields للـ API
    data.title = _strFromCustom(data.customFields, 'title');
    data.description = _strFromCustom(data.customFields, 'description');
    data.price = _strFromCustom(data.customFields, 'price');
    if (data.price?.isEmpty == true) data.price = null;
    final currency = _strFromCustom(data.customFields, 'currency');
    if (currency.isNotEmpty) data.currency = currency;
    // حقول عنوان الإعلان تُملأ من خطوة «الموقع» وليس من الحقول المخصصة.
  }

  String _strFromCustom(Map<String, dynamic> cf, String id) {
    final v = cf[id];
    if (v == null) return '';
    if (v is Map) {
      if (v['tbd'] == true) return '';
      final inner = v['value'];
      if (inner != null) return inner.toString().trim();
      return '';
    }
    return v.toString().trim();
  }

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      physics: const BouncingScrollPhysics(),
      padding: EdgeInsets.all(16.w),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (widget.data.categoryName != null)
            Container(
              padding: EdgeInsets.all(12.w),
              decoration: BoxDecoration(
                color: Colors.grey[100],
                borderRadius: BorderRadius.circular(8.r),
              ),
              child: Text(
                '${widget.data.subcategoryPath.map((e) => e['name']).join(' > ')} ${widget.data.categoryName ?? ''}',
                style: TextStyle(fontSize: 12.sp),
              ),
            ),
          SizedBox(height: 16.h),
          if (_getActiveCustomFields().isEmpty)
            Center(
              child: Padding(
                padding: EdgeInsets.all(24.w),
                child: Text(
                  AppLocale.tr('no_custom_fields'),
                  textAlign: TextAlign.center,
                  style: TextStyle(fontSize: 14.sp, color: Colors.grey[600]),
                ),
              ),
            )
          else
            ..._getActiveCustomFields().map((field) => _buildCustomField(field)),
        ],
      ),
    );
  }
}

class _LocationStep extends StatefulWidget {
  final CreateAdData data;

  const _LocationStep({super.key, required this.data});

  @override
  State<_LocationStep> createState() => _LocationStepState();
}

class _LocationStepState extends State<_LocationStep> {
  String _method = 'map';
  String _country = 'SY';
  List<RegionStateNode> _states = [];
  bool _loadingStates = false;
  int _loadGen = 0;
  Future<void>? _pendingGeocode;
  Timer? _manualFwdDebounce;
  int _manualFwdSeq = 0;

  String? _stateCode;
  String? _cityCode;
  String? _districtCode;

  double? _mapLat;
  double? _mapLng;

  late final TextEditingController _streetCtrl;

  RegionStateNode? _stateByCode(String? code) {
    if (code == null || code.isEmpty) return null;
    for (final s in _states) {
      if (s.code == code) return s;
    }
    return null;
  }

  RegionCity? _cityByCode(RegionStateNode? st, String? code) {
    if (st == null || code == null || code.isEmpty) return null;
    for (final c in st.cities) {
      if (c.code == code) return c;
    }
    return null;
  }

  RegionDistrict? _districtByCode(RegionCity? ct, String? code) {
    if (ct == null || code == null || code.isEmpty) return null;
    for (final d in ct.districts) {
      if (d.code == code) return d;
    }
    return null;
  }

  @override
  void initState() {
    super.initState();
    final d = widget.data;
    // Requirement: لا يوجد اختيار "قائمة" — هذه الخطوة تعمل كخريطة فقط دائماً.
    _method = 'map';
    _country = d.locationCountry.isNotEmpty ? d.locationCountry : 'SY';
    _stateCode = d.locationStateCode;
    _cityCode = d.locationCityCode;
    _districtCode = d.locationDistrictCode;
    _mapLat = d.latitude;
    _mapLng = d.longitude;
    _streetCtrl = TextEditingController(text: d.locationAddress);
    _streetCtrl.addListener(_scheduleManualForwardGeocode);
    WidgetsBinding.instance.addPostFrameCallback((_) => _loadStates());
  }

  @override
  void dispose() {
    _manualFwdDebounce?.cancel();
    _streetCtrl.removeListener(_scheduleManualForwardGeocode);
    _streetCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadStates({bool forceRefresh = false}) async {
    final gen = ++_loadGen;
    if (mounted) setState(() => _loadingStates = true);
    final list = await RegionService.fetchStates(_country, forceRefresh: forceRefresh);
    if (!mounted || gen != _loadGen) return;
    setState(() {
      _states = list;
      _loadingStates = false;
    });
    _syncSelectionFromCodes();
  }

  /// يضمن وجود شجرة المحافظات قبل مطابقة الدبوس (تجنّب السباق مع أول تحميل بعد فتح الخطوة).
  Future<void> _ensureStatesLoadedForGeocode() async {
    if (_states.isNotEmpty) return;
    await _loadStates(forceRefresh: false);
  }

  /// انتظار انتهاء عكس الترميز الجغرافي قبل حفظ الخطوة (تجنّب «التالي» السريع بلا أكواد).
  Future<void> waitForPendingGeocode() async {
    final f = _pendingGeocode;
    if (f != null) {
      try {
        await f;
      } catch (_) {}
    }
  }


  void _syncSelectionFromCodes() {
    if (!mounted) return;
    setState(() {
      final st = _stateByCode(_stateCode);
      if (st == null) {
        _stateCode = null;
        _cityCode = null;
        _districtCode = null;
        return;
      }
      final ct = _cityByCode(st, _cityCode);
      if (ct == null) {
        _cityCode = null;
        _districtCode = null;
        return;
      }
      if (_districtByCode(ct, _districtCode) == null) {
        _districtCode = null;
      }
    });
  }

  Future<void> _onCountryChanged(String? v) async {
    if (v == null) return;
    final centerLat = v == 'TR' ? 41.0082 : 33.5138;
    final centerLng = v == 'TR' ? 28.9784 : 36.2765;
    setState(() {
      _country = v;
      _stateCode = null;
      _cityCode = null;
      _districtCode = null;
      _mapLat = centerLat;
      _mapLng = centerLng;
    });
    await _loadStates(forceRefresh: true);
    _scheduleManualForwardGeocode();
  }

  String _manualForwardQuery() {
    final st = _stateByCode(_stateCode);
    final ct = _cityByCode(st, _cityCode);
    final di = _districtByCode(ct, _districtCode);
    final street = _streetCtrl.text.trim();
    final countryName = _country == 'TR' ? 'Turkey' : (_country == 'SY' ? 'Syria' : _country);
    final parts = <String>[
      if (street.isNotEmpty) street,
      if (di != null && di.name.trim().isNotEmpty) di.name.trim(),
      if (ct != null && ct.name.trim().isNotEmpty) ct.name.trim(),
      if (st != null && st.name.trim().isNotEmpty) st.name.trim(),
      if (countryName.trim().isNotEmpty) countryName.trim(),
    ];
    return parts.join(', ');
  }

  void _scheduleManualForwardGeocode() {
    if (!mounted) return;
    // حتى في وضع الخريطة نريد تحديث الدبوس تلقائياً حسب اختيارات القوائم.
    _manualFwdDebounce?.cancel();
    _manualFwdDebounce = Timer(const Duration(milliseconds: 650), _manualForwardGeocodeNow);
  }

  Future<void> _manualForwardGeocodeNow() async {
    if (!mounted) return;
    // Requirement: update map even when only governorate/state is selected,
    // then refine on city, then refine on neighborhood.
    if (_stateCode == null) return;
    final mySeq = ++_manualFwdSeq;
    // 1) Prefer backend coords from geo_divisions by selected codes (most accurate for list selections).
    try {
      final coords = await RegionService.coordsForCodes(
        country: _country,
        stateCode: _stateCode,
        cityCode: _cityCode,
        districtCode: _districtCode,
      );
      if (!mounted || mySeq != _manualFwdSeq) return;
      if (coords != null) {
        setState(() {
          _mapLat = coords.lat;
          _mapLng = coords.lng;
        });
        return;
      }
    } catch (_) {
      // ignore: fallback below
    }

    // 2) Fallback: forward geocode address string (may be less accurate / may fail on web).
    final q = _manualForwardQuery();
    if (q.trim().isEmpty) return;
    try {
      final results = await locationFromAddress(q);
      if (!mounted || mySeq != _manualFwdSeq) return;
      if (results.isEmpty) return;
      final first = results.first;
      if (!first.latitude.isFinite || !first.longitude.isFinite) return;
      setState(() {
        _mapLat = first.latitude;
        _mapLng = first.longitude;
      });
    } catch (_) {
      // ignore: keep last known coords
    }
  }

  /// تطبيع خشن للمطابقة مع نتائج الجيوكودينج (تركي/يونيكود) دون الاعتماد على locale الجهاز.
  String _normalizeForMatch(String input) {
    if (input.isEmpty) return input;
    var s = input.trim().replaceAll('\u0130', 'i').replaceAll('\u0131', 'i');
    s = s.toLowerCase();
    return s
        .replaceAll('ş', 's')
        .replaceAll('ğ', 'g')
        .replaceAll('ü', 'u')
        .replaceAll('ö', 'o')
        .replaceAll('ç', 'c')
        .replaceAll(RegExp(r'[\u0300-\u036f]'), '');
  }

  bool _strMatch(String a, String b) {
    if (a.isEmpty || b.isEmpty) return false;
    final x = _normalizeForMatch(a);
    final y = _normalizeForMatch(b);
    if (x.isEmpty || y.isEmpty) return false;
    return x == y || x.contains(y) || y.contains(x);
  }

  bool _matchesCatalogLabels(List<String> labels, List<String> needles) {
    if (labels.isEmpty || needles.isEmpty) return false;
    for (final nm in labels) {
      if (nm.isEmpty) continue;
      for (final n in needles) {
        if (n.isNotEmpty && _strMatch(nm, n)) return true;
      }
    }
    return false;
  }

  /// توسيع سلاسل العنوان إلى كلمات (مثل «Rif Dimashq Governorate» → rif, dimashq, …).
  List<String> _expandedNeedles(Placemark p) {
    final raw = <String>[
      (p.administrativeArea ?? '').trim(),
      (p.subAdministrativeArea ?? '').trim(),
      (p.locality ?? '').trim(),
      (p.subLocality ?? '').trim(),
      (p.thoroughfare ?? '').trim(),
      (p.street ?? '').trim(),
      (p.name ?? '').trim(),
      (p.country ?? '').trim(),
    ].where((e) => e.isNotEmpty).toList();

    final out = <String>{};
    for (final s in raw) {
      out.add(s);
      for (final part in s.split(RegExp(r'[\s,/\-_.]+'))) {
        final t = part.trim();
        if (t.length >= 3) out.add(t);
      }
    }
    return out.toList();
  }

  /// على الويب غالباً لا يملأ [Placemark] المحافظة/المدينة رغم أن خرائط Google تعرض العنوان.
  bool _placemarksWeakForCatalog(List<Placemark> marks) {
    for (final m in marks.take(4)) {
      if ((m.administrativeArea ?? '').trim().isNotEmpty) return false;
      if ((m.locality ?? '').trim().isNotEmpty) return false;
      if ((m.subAdministrativeArea ?? '').trim().isNotEmpty) return false;
    }
    return true;
  }

  int _scoreLabelsAgainstNeedles(List<String> labels, List<String> needles) {
    var sc = 0;
    for (final lab in labels) {
      if (lab.isEmpty) continue;
      for (final n in needles) {
        if (n.isNotEmpty && _strMatch(lab, n)) sc += 3;
      }
    }
    return sc;
  }

  /// يطابق مكوّنات العنوان مع أكواد الكتالوج (أسماء عربي/إنجليزي/تركي من الـ API).
  void _matchPlacemarkToSelection(
    Placemark p, {
    List<String>? mergedNeedles,
    bool mapAutoFillDistrict = false,
  }) {
    final admin1 = (p.administrativeArea ?? '').trim();
    final needles = mergedNeedles ?? _expandedNeedles(p);

    RegionStateNode? st;
    for (final s in _states) {
      if (_matchesCatalogLabels(s.matchNames, needles)) {
        st = s;
        break;
      }
    }
    if (st == null && admin1.isNotEmpty) {
      for (final s in _states) {
        if (_matchesCatalogLabels(s.matchNames, [admin1])) {
          st = s;
          break;
        }
      }
    }
    if (st == null) {
      if (kDebugMode) {
        debugPrint(
          '[AdLocation] no state match country=$_country catalogStates=${_states.length} '
          'needles=${needles.length} sample=${needles.take(30).join(' | ')}',
        );
      }
      return;
    }

    final locality = (p.locality ?? '').trim();
    final subAdmin = (p.subAdministrativeArea ?? '').trim();
    final extraLocal = <String>[
      if (locality.isNotEmpty) locality,
      if (subAdmin.isNotEmpty) subAdmin,
    ];

    RegionCity? ct;
    for (final c in st.cities) {
      if (_matchesCatalogLabels(c.matchNames, needles)) {
        ct = c;
        break;
      }
    }
    if (ct == null) {
      for (final c in st.cities) {
        if (_matchesCatalogLabels(c.matchNames, extraLocal)) {
          ct = c;
          break;
        }
      }
    }
    if (ct == null && locality.isNotEmpty) {
      for (final c in st.cities) {
        if (_matchesCatalogLabels(c.matchNames, [locality])) {
          ct = c;
          break;
        }
      }
    }
    if (ct == null && subAdmin.isNotEmpty) {
      for (final c in st.cities) {
        if (_matchesCatalogLabels(c.matchNames, [subAdmin])) {
          ct = c;
          break;
        }
      }
    }
    if (ct == null) {
      var best = 0;
      RegionCity? bestC;
      for (final c in st.cities) {
        final sc = _scoreLabelsAgainstNeedles(c.matchNames, needles);
        if (sc > best) {
          best = sc;
          bestC = c;
        }
      }
      if (best > 0) ct = bestC;
    }
    if (ct == null && st.cities.length == 1) ct = st.cities.first;

    final neighborhood = (p.subLocality ?? '').trim();

    RegionDistrict? di;
    if (ct != null && _country == 'SY') {
      var bdAll = 0;
      RegionCity? cityForDist;
      RegionDistrict? bestAcross;
      for (final c in st.cities) {
        for (final d in c.districts) {
          final sc = _scoreLabelsAgainstNeedles(d.matchNames, needles);
          if (sc > bdAll) {
            bdAll = sc;
            bestAcross = d;
            cityForDist = c;
          }
        }
      }
      if (bdAll > 0 && cityForDist != null && bestAcross != null) {
        ct = cityForDist;
        di = bestAcross;
      } else {
        for (final d in ct.districts) {
          if (_matchesCatalogLabels(d.matchNames, needles)) {
            di = d;
            break;
          }
        }
        if (di == null && neighborhood.isNotEmpty) {
          for (final d in ct.districts) {
            if (_matchesCatalogLabels(d.matchNames, [neighborhood])) {
              di = d;
              break;
            }
          }
        }
        if (di == null) {
          var bd = 0;
          RegionDistrict? bestD;
          for (final d in ct.districts) {
            final sc = _scoreLabelsAgainstNeedles(d.matchNames, needles);
            if (sc > bd) {
              bd = sc;
              bestD = d;
            }
          }
          if (bd > 0) di = bestD;
        }
      }
    } else if (ct != null) {
      for (final d in ct.districts) {
        if (_matchesCatalogLabels(d.matchNames, needles)) {
          di = d;
          break;
        }
      }
      if (di == null && neighborhood.isNotEmpty) {
        for (final d in ct.districts) {
          if (_matchesCatalogLabels(d.matchNames, [neighborhood])) {
            di = d;
            break;
          }
        }
      }
      if (di == null) {
        var bd = 0;
        RegionDistrict? bestD;
        for (final d in ct.districts) {
          final sc = _scoreLabelsAgainstNeedles(d.matchNames, needles);
          if (sc > bd) {
            bd = sc;
            bestD = d;
          }
        }
        if (bd > 0) di = bestD;
      }
      if (di == null && ct.districts.length == 1) di = ct.districts.first;
      if (di == null && mapAutoFillDistrict && ct.districts.isNotEmpty) {
        di = ct.districts.first;
      }
    }

    _stateCode = st.code;
    _cityCode = ct?.code;
    _districtCode = di?.code;

    if (kDebugMode) {
      if (ct == null) {
        debugPrint(
          '[AdLocation] state ok but no city: state=${st.code} cities=${st.cities.length} '
          'needlesSample=${needles.take(24).join(' | ')}',
        );
      }
      debugPrint(
        '[AdLocation] result state=${st.code} city=${ct?.code} district=${di?.code} '
        '(mapAutoDistrict=$mapAutoFillDistrict)',
      );
    }
  }

  String? _inferIsoFromCountryName(String? country) {
    if (country == null || country.isEmpty) return null;
    final c = country.toLowerCase();
    if (c.contains('türkiye') ||
        c.contains('turkiye') ||
        c.contains('turkey') ||
        c.contains('türkei')) {
      return 'TR';
    }
    if (c.contains('syria') || c.contains('سوريا') || c.contains('syrie')) {
      return 'SY';
    }
    return null;
  }

  String? _inferIsoFromMarks(List<Placemark> marks) {
    for (final p in marks) {
      final iso = (p.isoCountryCode ?? '').toUpperCase();
      if (iso == 'SY' || iso == 'TR') return iso;
    }
    for (final p in marks) {
      final inf = _inferIsoFromCountryName(p.country);
      if (inf != null) return inf;
    }
    return null;
  }

  Future<void> _applyPlacemark(double lat, double lng) {
    final run = _applyPlacemarkImpl(lat, lng);
    _pendingGeocode = run;
    run.whenComplete(() {
      if (identical(_pendingGeocode, run)) {
        _pendingGeocode = null;
      }
    });
    return run;
  }

  Future<void> _applyPlacemarkImpl(double lat, double lng) async {
    try {
      await _ensureStatesLoadedForGeocode();
      if (!mounted) return;
      if (_states.isEmpty) {
        if (kDebugMode) {
          debugPrint(
            '[AdLocation] skip geocode match: empty catalog (check GET /regions/$_country)',
          );
        }
        return;
      }

      final marks = await placemarkFromCoordinates(lat, lng);
      if (!mounted || marks.isEmpty) {
        if (kDebugMode) {
          debugPrint('[AdLocation] placemarkFromCoordinates: empty');
        }
        return;
      }

      final m0 = marks.first;
      if (kDebugMode) {
        debugPrint(
          '[AdLocation] placemark[0] admin=${m0.administrativeArea} subAdmin=${m0.subAdministrativeArea} '
          'locality=${m0.locality} subLoc=${m0.subLocality} name=${m0.name} '
          'country=${m0.country} iso=${m0.isoCountryCode} weakPlacemark=${_placemarksWeakForCatalog(marks)} kIsWeb=$kIsWeb',
        );
      }

      final merged = <String>{};
      for (final m in marks.take(5)) {
        merged.addAll(_expandedNeedles(m));
      }
      if (kIsWeb || _placemarksWeakForCatalog(marks)) {
        final extra = await ReverseGeocodingService.nominatimMatchNeedles(lat, lng);
        merged.addAll(extra);
        if (kDebugMode) {
          debugPrint(
            '[AdLocation] extraNeedles count=${extra.length} sample=${extra.take(20).join(' | ')}',
          );
        }
      }
      var p = marks.first;
      for (final m in marks) {
        if ((m.locality ?? '').trim().isNotEmpty ||
            (m.subAdministrativeArea ?? '').trim().isNotEmpty) {
          p = m;
          break;
        }
      }
      final iso = _inferIsoFromMarks(marks);
      if (iso != null && (iso == 'SY' || iso == 'TR') && iso != _country) {
        setState(() {
          _country = iso;
          _stateCode = null;
          _cityCode = null;
          _districtCode = null;
        });
        await _loadStates(forceRefresh: true);
      }
      if (!mounted) return;
      final mapFill = _method == 'map';
      setState(() => _matchPlacemarkToSelection(
            p,
            mergedNeedles: merged.toList(),
            mapAutoFillDistrict: mapFill,
          ));

      if (_method == 'map' &&
          mounted &&
          (_stateCode == null || _cityCode == null)) {
        final discovered = await RegionService.discoverMapSelection(
          country: _country,
          lat: lat,
          lng: lng,
          primary: <String, String?>{
            'administrative_area': p.administrativeArea,
            'sub_administrative_area': p.subAdministrativeArea,
            'locality': p.locality,
            'sub_locality': p.subLocality,
          },
          needles: merged.toList(),
        );
        if (discovered != null && mounted) {
          RegionService.invalidateCountry(_country);
          await _loadStates(forceRefresh: true);
          if (!mounted) return;
          setState(() {
            _stateCode = discovered['location_state_code']?.toString();
            _cityCode = discovered['location_city_code']?.toString();
            final dc = discovered['location_district_code'];
            _districtCode =
                (dc == null || dc.toString().trim().isEmpty)
                    ? null
                    : dc.toString();
          });
          if (kDebugMode) {
            debugPrint(
              '[AdLocation] discover-map source=${discovered['source']} '
              'state=$_stateCode city=$_cityCode dist=$_districtCode',
            );
          }
        }
      }
    } catch (e, trace) {
      if (kDebugMode) {
        debugPrint('[AdLocation] _applyPlacemarkImpl error: $e\n$trace');
      }
    }
  }

  void saveToData(CreateAdData data) {
    data.locationInputMethod = _method;
    data.locationCountry = _country;
    data.locationAddress = _streetCtrl.text.trim();
    final st = _stateByCode(_stateCode);
    final ct = _cityByCode(st, _cityCode);
    final di = _districtByCode(ct, _districtCode);
    data.locationStateCode = st?.code;
    data.locationCityCode = ct?.code;
    data.locationDistrictCode = di?.code;
    data.locationState = st?.name ?? '';
    data.locationCity = ct?.name ?? '';
    data.locationDistrict = di?.name ?? '';
    // Even in "manual" mode we may auto-geocode to provide a map pin.
    data.latitude = _mapLat;
    data.longitude = _mapLng;
  }

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      physics: const BouncingScrollPhysics(),
      padding: EdgeInsets.all(16.w),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            AppLocale.tr('step_location'),
            style: TextStyle(fontSize: 16.sp, fontWeight: FontWeight.w700),
          ),
          SizedBox(height: 16.h),
          DropdownButtonFormField<String>(
            value: _country,
            decoration: InputDecoration(
              labelText: AppLocale.tr('location_country'),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(8.r)),
            ),
            items: [
              DropdownMenuItem(value: 'SY', child: Text(AppLocale.tr('country_sy'))),
              DropdownMenuItem(value: 'TR', child: Text(AppLocale.tr('country_tr'))),
            ],
            onChanged: (v) => _onCountryChanged(v),
          ),
          SizedBox(height: 16.h),
          if (_loadingStates)
            Padding(
              padding: EdgeInsets.symmetric(vertical: 24.h),
              child: Center(child: CircularProgressIndicator(color: AppColors.darkBlue)),
            )
          else if (_states.isEmpty)
            Padding(
              padding: EdgeInsets.symmetric(vertical: 12.h),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Text(
                    AppLocale.tr('ad_location_regions_server_empty'),
                    textAlign: TextAlign.center,
                    style: TextStyle(fontSize: 13.sp, color: Colors.grey[800], height: 1.35),
                  ),
                  SizedBox(height: 12.h),
                  TextButton(
                    onPressed: () => _loadStates(forceRefresh: true),
                    child: Text(AppLocale.tr('try_again')),
                  ),
                ],
              ),
            )
          else ...[
            DropdownButtonFormField<String>(
              value: _stateCode != null &&
                      _states.any((s) => s.code == _stateCode)
                  ? _stateCode
                  : null,
              decoration: InputDecoration(
                labelText: AppLocale.tr('location_state'),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(8.r)),
              ),
              items: _states
                  .map((s) => DropdownMenuItem(value: s.code, child: Text(s.name)))
                  .toList(),
              onChanged: (code) {
                setState(() {
                  _stateCode = code;
                  _cityCode = null;
                  _districtCode = null;
                });
                _scheduleManualForwardGeocode();
              },
            ),
            SizedBox(height: 12.h),
            DropdownButtonFormField<String>(
              value: _cityCode != null &&
                      (_stateByCode(_stateCode)?.cities.any((c) => c.code == _cityCode) ??
                          false)
                  ? _cityCode
                  : null,
              decoration: InputDecoration(
                labelText: AppLocale.tr('location_city_level'),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(8.r)),
              ),
              items: (_stateByCode(_stateCode)?.cities ?? [])
                  .map((c) => DropdownMenuItem(value: c.code, child: Text(c.name)))
                  .toList(),
              onChanged: _stateByCode(_stateCode) == null
                  ? null
                  : (code) {
                      setState(() {
                        _cityCode = code;
                        _districtCode = null;
                      });
                      _scheduleManualForwardGeocode();
                    },
            ),
            // مطابق للويب: في وضع الخريطة "الحي" اختياري، لكن نُظهره حتى يستطيع المستخدم اختياره يدويًا.
            SizedBox(height: 12.h),
            DropdownButtonFormField<String>(
              value: _districtCode != null &&
                      (_cityByCode(_stateByCode(_stateCode), _cityCode)
                              ?.districts
                              .any((d) => d.code == _districtCode) ??
                          false)
                  ? _districtCode
                  : null,
              decoration: InputDecoration(
                labelText: AppLocale.tr('location_district_level'),
                helperText: AppLocale.tr('select_neighborhood_sy_optional'),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(8.r)),
              ),
              items: (_cityByCode(_stateByCode(_stateCode), _cityCode)?.districts ?? [])
                  .map((d) => DropdownMenuItem(value: d.code, child: Text(d.name)))
                  .toList(),
              onChanged: _cityByCode(_stateByCode(_stateCode), _cityCode) == null
                  ? null
                  : (code) {
                      setState(() => _districtCode = code);
                      _scheduleManualForwardGeocode();
                    },
            ),
          ],
          SizedBox(height: 16.h),
          OutlinedButton.icon(
            onPressed: () async {
              final serviceEnabled = await Geolocator.isLocationServiceEnabled();
              if (!serviceEnabled) {
                showToast(message: AppLocale.tr('location_error'));
                return;
              }
              var permission = await Geolocator.checkPermission();
              if (permission == LocationPermission.denied) {
                permission = await Geolocator.requestPermission();
              }
              if (permission == LocationPermission.denied ||
                  permission == LocationPermission.deniedForever) {
                showToast(message: AppLocale.tr('location_permission_denied'));
                return;
              }
              final pos = await Geolocator.getCurrentPosition(
                desiredAccuracy: LocationAccuracy.medium,
              );
              if (!mounted) return;
              setState(() {
                _mapLat = pos.latitude;
                _mapLng = pos.longitude;
              });
              await _applyPlacemark(pos.latitude, pos.longitude);
            },
            icon: Icon(Icons.my_location, size: 20.sp, color: AppColors.darkBlue),
            label: Text(AppLocale.tr('use_my_location')),
          ),
          SizedBox(height: 12.h),
          Builder(
            builder: (context) {
              final lat = _mapLat ?? (_country == 'TR' ? 41.0082 : 33.5138);
              final lng = _mapLng ?? (_country == 'TR' ? 28.9784 : 36.2765);
              return InlineMapPicker(
                key: ValueKey<String>('map_$_country'),
                initialLat: lat,
                initialLng: lng,
                height: _inlineMapPickerHeight(context),
                onLocationSelected: (newLat, newLng, address) {
                  setState(() {
                    _mapLat = newLat;
                    _mapLng = newLng;
                  });
                  _applyPlacemark(newLat, newLng);
                },
              );
            },
          ),
          SizedBox(height: 16.h),
          TextFormWithLabel(
            hintText: AppLocale.tr('location_street'),
            controller: _streetCtrl,
            labelText: AppLocale.tr('location_street'),
            keyboardType: TextInputType.streetAddress,
            obscureText: false,
          ),
        ],
      ),
    );
  }
}

class _ImagesStep extends StatefulWidget {
  final CreateAdData data;

  const _ImagesStep({required this.data});

  @override
  State<_ImagesStep> createState() => _ImagesStepState();
}

class _ImagesStepState extends State<_ImagesStep> {
  final ImagePicker _picker = ImagePicker();

  Future<void> _pickImages() async {
    try {
      final cfg = AdImagesEffective.resolve(
        widget.data.categoryModelForAds,
        widget.data.leafSubcategoryForAds,
      );
      final maxImages = cfg.maxImages;
      final files = await _picker.pickMultiImage();
      if (files.isNotEmpty && mounted) {
        final remaining = maxImages - widget.data.images.length;
        if (remaining <= 0) {
          showToast(
            message: AppLocale.tr('ad_images_limit_reached')
                .replaceAll('{max}', '$maxImages'),
          );
          return;
        }
        final toAdd = files.take(remaining).toList();
        setState(() {
          widget.data.selectedGalleryPath = null;
          widget.data.images.addAll(toAdd);
        });
        if (files.length > toAdd.length) {
          showToast(
            message: AppLocale.tr('ad_images_limit_reached')
                .replaceAll('{max}', '$maxImages'),
          );
        }
      }
    } catch (e) {
      if (mounted) showToast(message: AppLocale.tr('pick_images_failed'));
    }
  }

  void _remove(int index) {
    setState(() {
      widget.data.images.removeAt(index);
    });
  }

  void _move(int from, int to) {
    if (to < 0 || to >= widget.data.images.length) return;
    setState(() {
      final x = widget.data.images.removeAt(from);
      widget.data.images.insert(to, x);
    });
  }

  void _setPrimary(int index) {
    if (index <= 0) return;
    setState(() {
      final x = widget.data.images.removeAt(index);
      widget.data.images.insert(0, x);
    });
  }

  Future<void> _pickVideo() async {
    try {
      final f = await _picker.pickVideo(source: ImageSource.gallery);
      if (f != null && mounted) {
        setState(() => widget.data.video = f);
      }
    } catch (_) {
      if (mounted) showToast(message: AppLocale.tr('pick_video_failed'));
    }
  }

  void _clearVideo() {
    setState(() => widget.data.video = null);
  }

  Widget _buildOptionalVideoBlock() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Divider(height: 28.h),
        Text(
          AppLocale.tr('ad_video_optional_step'),
          style: TextStyle(fontSize: 15.sp, fontWeight: FontWeight.w600),
        ),
        SizedBox(height: 10.h),
        Row(
          children: [
            Expanded(
              child: OutlinedButton.icon(
                onPressed: _pickVideo,
                icon: Icon(Icons.videocam_outlined, size: 20.sp),
                label: Text(AppLocale.tr('pick_video'), style: TextStyle(fontSize: 14.sp)),
              ),
            ),
            if (widget.data.video != null) ...[
              SizedBox(width: 10.w),
              IconButton(
                onPressed: _clearVideo,
                tooltip: AppLocale.tr('remove_video'),
                icon: Icon(Icons.close, color: Colors.red[700]),
              ),
            ],
          ],
        ),
        if (widget.data.video != null)
          Padding(
            padding: EdgeInsets.only(top: 8.h),
            child: Text(
              widget.data.video!.name,
              style: TextStyle(fontSize: 12.sp, color: Colors.grey[700]),
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
          ),
      ],
    );
  }

  Widget _buildGalleryMode(AdImagesEffective cfg) {
    if (cfg.galleryPaths.isEmpty) {
      return Padding(
        padding: EdgeInsets.all(24.w),
        child: Text(
          AppLocale.tr('gallery_not_configured'),
          style: TextStyle(fontSize: 14.sp, color: Colors.red[700]),
          textAlign: TextAlign.center,
        ),
      );
    }
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(
          AppLocale.tr('upload_ad_images'),
          style: TextStyle(fontSize: 16.sp, fontWeight: FontWeight.w600),
        ),
        SizedBox(height: 8.h),
        Text(
          AppLocale.tr('gallery_pick_one_hint'),
          style: TextStyle(fontSize: 12.sp, color: Colors.grey[700]),
        ),
        SizedBox(height: 16.h),
        GridView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: 2,
            mainAxisSpacing: 12.h,
            crossAxisSpacing: 12.w,
            childAspectRatio: 0.85,
          ),
          itemCount: cfg.galleryPaths.length,
          itemBuilder: (context, i) {
            final path = cfg.galleryPaths[i];
            final url = i < cfg.galleryUrls.length && cfg.galleryUrls[i].isNotEmpty
                ? cfg.galleryUrls[i]
                : '';
            final selected = widget.data.selectedGalleryPath == path;
            return Material(
              color: Colors.transparent,
              child: InkWell(
                onTap: () {
                  setState(() {
                    widget.data.selectedGalleryPath = path;
                    widget.data.images = [];
                  });
                },
                borderRadius: BorderRadius.circular(12.r),
                child: Ink(
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(12.r),
                    border: Border.all(
                      color: selected ? AppColors.darkBlue : Colors.grey.shade300,
                      width: selected ? 3 : 1,
                    ),
                    color: Colors.grey.shade50,
                  ),
                  child: Padding(
                    padding: EdgeInsets.all(8.w),
                    child: url.isNotEmpty
                        ? CachedUrlImage(
                            imageUrl: url,
                            fit: BoxFit.contain,
                            errorBuilder: (_, __) => Icon(Icons.broken_image, size: 40.sp),
                          )
                        : Icon(Icons.image, size: 40.sp),
                  ),
                ),
              ),
            );
          },
        ),
      ],
    );
  }

  Widget _buildUploadMode() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(
          AppLocale.tr('upload_ad_images'),
          style: TextStyle(fontSize: 16.sp, fontWeight: FontWeight.w600),
        ),
        SizedBox(height: 8.h),
        Text(
          AppLocale.tr('reorder_images_hint_app'),
          style: TextStyle(fontSize: 12.sp, color: Colors.grey[700]),
        ),
        SizedBox(height: 12.h),
        GestureDetector(
          onTap: _pickImages,
          child: DottedBorder(
            options: RoundedRectDottedBorderOptions(
              strokeWidth: 1,
              color: AppColors.darkBlue,
              dashPattern: const [10, 5],
              radius: Radius.circular(8.r),
            ),
            child: Container(
              width: double.infinity,
              height: 120.h,
              alignment: Alignment.center,
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  CircleAvatar(
                    radius: 30.r,
                    backgroundColor: HexColor("00CBFF").withValues(alpha: 0.1),
                    child: CircleAvatar(
                      radius: 26.r,
                      backgroundColor: AppColors.darkBlue,
                      child: Icon(Icons.cloud_upload, color: Colors.white, size: 28.sp),
                    ),
                  ),
                  SizedBox(height: 12.h),
                  Text(
                    AppLocale.tr('tap_to_pick_images'),
                    style: TextStyle(fontSize: 14.sp, fontWeight: FontWeight.w500),
                  ),
                ],
              ),
            ),
          ),
        ),
        if (widget.data.images.isNotEmpty) ...[
          SizedBox(height: 16.h),
          Text(AppLocale.tr('selected_images_label'), style: TextStyle(fontSize: 14.sp)),
          SizedBox(height: 8.h),
          ...List.generate(widget.data.images.length, (i) {
            final f = widget.data.images[i];
            return Padding(
              padding: EdgeInsets.only(bottom: 10.h),
              child: Container(
                decoration: BoxDecoration(
                  border: Border.all(color: Colors.grey.shade300),
                  borderRadius: BorderRadius.circular(10.r),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    ListTile(
                      contentPadding: EdgeInsets.symmetric(horizontal: 12.w, vertical: 4.h),
                      leading: SizedBox(
                        width: 72.w,
                        height: 72.h,
                        child: ClipRRect(
                          borderRadius: BorderRadius.circular(8.r),
                          child: FutureBuilder<Uint8List>(
                            future: f.readAsBytes(),
                            builder: (_, snap) {
                              if (snap.hasData && snap.data!.isNotEmpty) {
                                return Image.memory(
                                  snap.data!,
                                  fit: BoxFit.contain,
                                  width: 72.w,
                                  height: 72.h,
                                );
                              }
                              return ColoredBox(
                                color: Colors.grey[200]!,
                                child: Icon(Icons.image),
                              );
                            },
                          ),
                        ),
                      ),
                      title: i == 0
                          ? Chip(
                              label: Text(
                                AppLocale.tr('image_primary_badge'),
                                style: TextStyle(fontSize: 11.sp),
                              ),
                              padding: EdgeInsets.zero,
                              materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                            )
                          : TextButton(
                              onPressed: () => _setPrimary(i),
                              child: Text(AppLocale.tr('set_as_primary_image'), style: TextStyle(fontSize: 12.sp)),
                            ),
                      trailing: IconButton(
                        icon: Icon(Icons.delete_outline, color: Colors.red[600]),
                        onPressed: () => _remove(i),
                      ),
                    ),
                    Padding(
                      padding: EdgeInsets.fromLTRB(12.w, 0, 12.w, 8.h),
                      child: Row(
                        children: [
                          IconButton(
                            icon: const Icon(Icons.arrow_upward),
                            onPressed: i > 0 ? () => _move(i, i - 1) : null,
                          ),
                          IconButton(
                            icon: const Icon(Icons.arrow_downward),
                            onPressed: i < widget.data.images.length - 1 ? () => _move(i, i + 1) : null,
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            );
          }),
        ],
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    final cfg = AdImagesEffective.resolve(
      widget.data.categoryModelForAds,
      widget.data.leafSubcategoryForAds,
    );

    return SingleChildScrollView(
      physics: const BouncingScrollPhysics(),
      padding: EdgeInsets.all(16.w),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          if (cfg.isAdminGallery) _buildGalleryMode(cfg) else _buildUploadMode(),
          _buildOptionalVideoBlock(),
        ],
      ),
    );
  }
}
