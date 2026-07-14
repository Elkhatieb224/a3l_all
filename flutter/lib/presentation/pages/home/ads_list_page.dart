import 'dart:math' as math;

import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/storage/token_storage.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/models/ad_model.dart';
import 'package:a3lnha/data/models/category_model.dart';
import 'package:a3lnha/data/models/subcategory_model.dart';
import 'package:a3lnha/data/services/ad_service.dart';
import 'package:a3lnha/data/services/category_service.dart';
import 'package:a3lnha/data/services/saved_search_service.dart';
import 'package:a3lnha/helpers/custom_fields_resolver.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/helpers/numeral_helper.dart';
import 'package:a3lnha/presentation/pages/home/ad_details_page.dart';
import 'package:a3lnha/presentation/pages/auth/login_page.dart';
import 'package:a3lnha/presentation/widgets/ad_list_location_label.dart';
import 'package:a3lnha/presentation/widgets/ad_status_badge_icon.dart';
import 'package:a3lnha/presentation/widgets/ads_results_map.dart';
import 'package:a3lnha/presentation/widgets/shared/app_network_image.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_button.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_text_form_field.dart';
import 'package:a3lnha/presentation/widgets/shared/favorite_icon_button.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class AdsListPage extends StatefulWidget {
  final String title;
  final int? categoryId;
  final int? subcategoryId;
  final bool featured;
  final bool urgent;

  const AdsListPage({
    super.key,
    required this.title,
    this.categoryId,
    this.subcategoryId,
    this.featured = false,
    this.urgent = false,
  });

  @override
  State<AdsListPage> createState() => _AdsListPageState();
}

const List<String> _sortByValues = [
  'date_desc',
  'date_asc',
  'price_asc',
  'price_desc',
];

class _AdsListPageState extends State<AdsListPage> {
  final List<AdModel> _ads = [];
  final TextEditingController _minController = TextEditingController();
  final TextEditingController _maxController = TextEditingController();
  List<CategoryModel> _categories = [];
  CategoryModel? _currentCategory;
  /// الفئة الفرعية المعروضة ضمن شجرة القسم (categoryId + subcategoryId)
  SubcategoryModel? _listLeafSubcategory;
  /// فئة فرعية جُلبت مباشرة (مثلاً من مسار الإعلان بلا categoryId)
  SubcategoryModel? _standaloneListSubcategory;
  bool _loading = true;
  int _currentPage = 1;
  int _lastPage = 1;
  int _total = 0;
  bool _loadingMore = false;
  int? _selectedFilterIndex = 0;
  String? _selectedAppearance = 'list';
  int _mapReloadToken = 0;
  String? _selectedCategoryName;

  DateTime _adPublishedAtOrMin(AdModel ad) {
    final raw = ad.publishedAt?.trim();
    if (raw == null || raw.isEmpty) return DateTime.fromMillisecondsSinceEpoch(0);
    return DateTime.tryParse(raw) ?? DateTime.fromMillisecondsSinceEpoch(0);
  }

  num _adSortablePrice(AdModel ad) {
    if (ad.price != null) return ad.price!;
    final fromFormatted = NumeralHelper.parseFormattedAmount(ad.displayPriceForUi ?? '');
    if (fromFormatted != null) return fromFormatted;
    return -1;
  }

  void _sortAdsInMemory(List<AdModel> ads) {
    switch (_sortByValue) {
      case 'price_asc':
        ads.sort((a, b) {
          final av = _adSortablePrice(a);
          final bv = _adSortablePrice(b);
          if (av == bv) return b.id.compareTo(a.id);
          return av.compareTo(bv);
        });
        break;
      case 'price_desc':
        ads.sort((a, b) {
          final av = _adSortablePrice(a);
          final bv = _adSortablePrice(b);
          if (av == bv) return b.id.compareTo(a.id);
          return bv.compareTo(av);
        });
        break;
      case 'date_asc':
        ads.sort((a, b) {
          final av = _adPublishedAtOrMin(a);
          final bv = _adPublishedAtOrMin(b);
          if (av == bv) return b.id.compareTo(a.id);
          return av.compareTo(bv);
        });
        break;
      case 'date_desc':
      default:
        ads.sort((a, b) {
          final av = _adPublishedAtOrMin(a);
          final bv = _adPublishedAtOrMin(b);
          if (av == bv) return b.id.compareTo(a.id);
          return bv.compareTo(av);
        });
        break;
    }
  }

  final Map<String, TextEditingController> _customMinControllers = {};
  final Map<String, TextEditingController> _customMaxControllers = {};
  final Map<String, String?> _customSelectValues = {};
  final Map<String, bool> _customCheckboxValues = {};
  final Map<String, DateTime?> _customDateAfterValues = {};

  int? get _filterCategoryId {
    if (widget.categoryId != null) return widget.categoryId;
    if (_selectedCategoryName == null || _selectedCategoryName!.isEmpty || _categories.isEmpty) return null;
    final found = _categories.where(
      (c) => c.name == _selectedCategoryName || c.nameAr == _selectedCategoryName,
    );
    return found.isEmpty ? null : found.first.id;
  }

  String? get _sortByValue {
    if (_selectedFilterIndex == null || _selectedFilterIndex! < 0 || _selectedFilterIndex! >= _sortByValues.length) return null;
    return _sortByValues[_selectedFilterIndex!];
  }

  @override
  void initState() {
    super.initState();
    if (widget.categoryId != null) {
      CategoryService.getCategory(widget.categoryId!).then((cat) async {
        if (!mounted) return;
        SubcategoryModel? leaf;
        if (widget.subcategoryId != null && cat != null) {
          leaf = _findSubcategoryInTree(
            cat.subcategories ?? const [],
            widget.subcategoryId!,
          );
        }
        if (leaf == null && widget.subcategoryId != null) {
          leaf = await CategoryService.getSubcategory(widget.subcategoryId!);
        }
        if (!mounted) return;
        setState(() {
          _currentCategory = cat;
          _listLeafSubcategory = leaf;
        });
      });
    } else if (widget.subcategoryId != null) {
      CategoryService.getSubcategory(widget.subcategoryId!).then((sub) async {
        if (!mounted) return;
        CategoryModel? parent;
        if (sub != null &&
            (sub.customFields == null || sub.customFields!.isEmpty)) {
          parent = await CategoryService.getCategory(sub.categoryId);
        }
        if (!mounted) return;
        setState(() {
          _standaloneListSubcategory = sub;
          if (parent != null) _currentCategory = parent;
        });
      });
    }
    if (widget.categoryId == null) {
      CategoryService.getCategories().then((list) {
        if (mounted) setState(() => _categories = list);
      });
    }
    _loadAds();
  }

  SubcategoryModel? _findSubcategoryInTree(
    Iterable<SubcategoryModel> nodes,
    int targetId,
  ) {
    for (final node in nodes) {
      if (node.id == targetId) return node;
      final found = _findSubcategoryInTree(node.children ?? const [], targetId);
      if (found != null) return found;
    }
    return null;
  }

  @override
  void dispose() {
    _minController.dispose();
    _maxController.dispose();
    for (final c in _customMinControllers.values) {
      c.dispose();
    }
    for (final c in _customMaxControllers.values) {
      c.dispose();
    }
    super.dispose();
  }

  /// قسم يوفّر حقول الفلترة: إما المختار من القائمة أو قسم الصفحة
  CategoryModel? get _categoryForFieldSchema {
    if (widget.categoryId != null) return _currentCategory;
    final id = _filterCategoryId;
    if (id == null) return null;
    for (final c in _categories) {
      if (c.id == id) return c;
    }
    return null;
  }

  List<Map<String, dynamic>> get _resolvedSchemaFields {
    List<Map<String, dynamic>> toList(List<Map<String, dynamic>>? raw) =>
        (raw ?? const <Map<String, dynamic>>[])
            .map((e) => Map<String, dynamic>.from(e))
            .toList();

    final sub = _listLeafSubcategory ?? _standaloneListSubcategory;
    List<Map<String, dynamic>> schema;
    if (sub != null) {
      schema = toList(sub.resolvedCustomFields);
      if (schema.isEmpty) {
        schema = CustomFieldsResolver.resolveForLeaf(
          leaf: sub,
          subcategoryById: {sub.id: sub},
          category: _categoryForFieldSchema,
        );
      }
    } else {
      schema = toList(_categoryForFieldSchema?.customFields);
    }

    return schema;
  }

  String? get _priceFieldId =>
      CustomFieldsResolver.resolvePrimaryPriceFieldId(_resolvedSchemaFields);

  List<Map<String, dynamic>> get _filterableCustomFields =>
      CustomFieldsResolver.filterableFields(_resolvedSchemaFields);

  void _syncPriceFilterControllersFromCustom() {
    final priceId = _priceFieldId;
    if (priceId == null) return;
    final minFromCustom = _customMinControllers[priceId]?.text.trim() ?? '';
    final maxFromCustom = _customMaxControllers[priceId]?.text.trim() ?? '';
    if (_minController.text.trim().isEmpty && minFromCustom.isNotEmpty) {
      _minController.text = minFromCustom;
    }
    if (_maxController.text.trim().isEmpty && maxFromCustom.isNotEmpty) {
      _maxController.text = maxFromCustom;
    }
    if (minFromCustom.isNotEmpty) {
      _customMinControllers[priceId]?.text = _minController.text;
    }
    if (maxFromCustom.isNotEmpty) {
      _customMaxControllers[priceId]?.text = _maxController.text;
    }
  }

  Future<void> _ensureFilterSchemaLoaded({bool preferFresh = false}) async {
    if (widget.categoryId != null) {
      var cat = await CategoryService.getCategory(
        widget.categoryId!,
        forceRefresh: preferFresh,
      );
      if (!preferFresh &&
          (cat?.customFields == null || cat!.customFields!.isEmpty)) {
        cat = await CategoryService.getCategory(widget.categoryId!, forceRefresh: true);
      }
      SubcategoryModel? leaf = _listLeafSubcategory;
      if (widget.subcategoryId != null) {
        leaf = _findSubcategoryInTree(cat?.subcategories ?? const [], widget.subcategoryId!);
        leaf ??= await CategoryService.getSubcategory(widget.subcategoryId!);
      }
      if (!mounted) return;
      setState(() {
        _currentCategory = cat;
        _listLeafSubcategory = leaf;
      });
      return;
    }

    if (widget.subcategoryId != null) {
      var sub = await CategoryService.getSubcategory(widget.subcategoryId!);
      CategoryModel? parent;
      if (sub != null) {
        parent = await CategoryService.getCategory(
          sub.categoryId,
          forceRefresh: preferFresh || (sub.customFields == null || sub.customFields!.isEmpty),
        );
      }
      if (!mounted) return;
      setState(() {
        _standaloneListSubcategory = sub;
        if (parent != null) _currentCategory = parent;
      });
    }
  }

  Map<String, dynamic> _buildCustomFiltersPayload() {
    final Map<String, dynamic> out = {};
    for (final field in _filterableCustomFields) {
      final String id = field['id']?.toString() ?? '';
      if (id.isEmpty) continue;
      final String type = field['type']?.toString() ?? 'text';

      if (type == 'number') {
        final minCtrl = _customMinControllers[id];
        final maxCtrl = _customMaxControllers[id];
        final minText = minCtrl?.text.trim() ?? '';
        final maxText = maxCtrl?.text.trim() ?? '';
        if (minText.isNotEmpty) {
          out['cf_${id}_min'] = minText;
        }
        if (maxText.isNotEmpty) {
          out['cf_${id}_max'] = maxText;
        }
      } else if (type == 'select') {
        final v = _customSelectValues[id];
        if (v != null && v.isNotEmpty) {
          out['cf_$id'] = v;
        }
      } else if (type == 'checkbox') {
        final v = _customCheckboxValues[id] ?? false;
        if (v) {
          out['cf_$id'] = '1';
        }
      } else if (type == 'date') {
        final d = _customDateAfterValues[id];
        if (d != null) {
          out['cf_${id}_after'] =
              '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';
        }
      }
    }
    return out;
  }

  Future<void> _loadAds({bool loadMore = false}) async {
    if (loadMore) {
      if (_currentPage >= _lastPage) return;
      setState(() => _loadingMore = true);
    } else {
      setState(() => _loading = true);
    }

    final response = await AdService.getAds(
      categoryId: _filterCategoryId ?? widget.categoryId,
      subcategoryId: widget.subcategoryId,
      page: loadMore ? _currentPage + 1 : 1,
      perPage: 20,
      featured: widget.featured ? true : null,
      urgent: widget.urgent ? true : null,
      sortBy: _sortByValue,
      minPrice: num.tryParse(_minController.text.trim()),
      maxPrice: num.tryParse(_maxController.text.trim()),
      customFilters: _buildCustomFiltersPayload(),
      forceRefresh: !loadMore &&
          (widget.categoryId != null ||
              widget.subcategoryId != null ||
              widget.featured ||
              widget.urgent),
    );

    if (!mounted) return;

    setState(() {
      if (loadMore) {
        _ads.addAll(response.ads);
        _sortAdsInMemory(_ads);
        _currentPage = response.currentPage;
        _loadingMore = false;
      } else {
        _ads.clear();
        _ads.addAll(response.ads);
        _sortAdsInMemory(_ads);
        _currentPage = response.currentPage;
        _lastPage = response.lastPage;
        _total = response.total;
        _loading = false;
      }
    });
  }

  void _applyFilter() {
    Navigator.pop(context);
    _loadAds();
  }

  void _applySort() {
    Navigator.pop(context);
    _loadAds();
  }

  void _applyAppearance() {
    Navigator.pop(context);
    setState(() {
      if (_selectedAppearance == 'map') {
        _mapReloadToken++;
      }
    });
  }

  Map<String, dynamic> _buildFiltersForSaving() {
    return {
      'search': '',
      'category_id': _filterCategoryId ?? widget.categoryId,
      'subcategory_id': widget.subcategoryId,
      'min_price': _minController.text.trim().isEmpty ? null : _minController.text.trim(),
      'max_price': _maxController.text.trim().isEmpty ? null : _maxController.text.trim(),
      'custom_filters': _buildCustomFiltersPayload(),
      'sort_by': _sortByValue,
    };
  }

  Future<void> _saveCurrentListingFilters() async {
    if (!TokenStorage.hasToken()) {
      context.push(LoginPage());
      return;
    }
    final msg = await SavedSearchService.saveSearch(
      name: widget.title,
      filters: _buildFiltersForSaving(),
    );
    if (!mounted) return;
    showToast(message: msg ?? AppLocale.tr('saved_search_saved'));
  }

  void _openFilterSheet(BuildContext context) {
    _ensureFilterSchemaLoaded(preferFresh: true).then((_) {
      if (!mounted) return;
      _syncPriceFilterControllersFromCustom();
      showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) {
        return StatefulBuilder(
          builder: (ctx, setModalState) {
            return Padding(
              padding: EdgeInsets.only(
                left: 20.w,
                right: 20.w,
                top: 20.w,
                bottom: 20.w + MediaQuery.of(ctx).viewInsets.bottom,
              ),
              child: Container(
                constraints: BoxConstraints(
                  maxHeight: MediaQuery.of(ctx).size.height * 0.85,
                ),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Text(
                      AppLocale.tr('filter'),
                      style: TextStyle(
                        color: Colors.black,
                        fontSize: 20.sp,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    SizedBox(height: 20.h),
                    Flexible(
                      child: SingleChildScrollView(
                        physics: const BouncingScrollPhysics(),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            if (widget.categoryId == null) ...[
                              Text(
                                AppLocale.tr('category_filter'),
                                style: TextStyle(
                                  color: Colors.black,
                                  fontSize: 12.sp,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                              SizedBox(height: 8.h),
                              DropdownButtonFormField<String>(
                                value: _selectedCategoryName,
                                decoration: InputDecoration(
                                  enabledBorder: OutlineInputBorder(
                                    borderRadius: BorderRadius.circular(12.r),
                                    borderSide: BorderSide(
                                      color: Colors.grey.withOpacity(0.4),
                                    ),
                                  ),
                                  contentPadding: EdgeInsets.symmetric(horizontal: 16.w),
                                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12.r)),
                                ),
                                items: [
                                  DropdownMenuItem<String>(
                                    value: null,
                                    child: Text(AppLocale.tr('all_categories')),
                                  ),
                                  ..._categories.map((c) => DropdownMenuItem<String>(
                                        value: c.name,
                                        child: Text(c.displayName),
                                      )),
                                ],
                                onChanged: (value) {
                                  setModalState(() => _selectedCategoryName = value);
                                  setState(() => _selectedCategoryName = value);
                                },
                              ),
                              SizedBox(height: 20.h),
                            ],
                            Text(
                              CustomFieldsResolver.resolvePriceFilterLabel(
                                _resolvedSchemaFields,
                                locale: AppLocale.current,
                              ),
                              style: TextStyle(
                                color: Colors.black,
                                fontSize: 12.sp,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                            SizedBox(height: 8.h),
                            Row(
                              children: [
                                Expanded(
                                  child: CustomTextFormField(
                                    hintText: AppLocale.tr('at_least'),
                                    controller: _minController,
                                    keyboardType: TextInputType.number,
                                    obscureText: false,
                                    inputFormatters: [EnglishOnlyNumberInputFormatter()],
                                  ),
                                ),
                                SizedBox(width: 16.w),
                                Expanded(
                                  child: CustomTextFormField(
                                    hintText: AppLocale.tr('at_most'),
                                    controller: _maxController,
                                    keyboardType: TextInputType.number,
                                    obscureText: false,
                                    inputFormatters: [EnglishOnlyNumberInputFormatter()],
                                  ),
                                ),
                              ],
                            ),
                            if (_filterableCustomFields.isNotEmpty) ...[
                              SizedBox(height: 20.h),
                              Text(
                                AppLocale.tr('additional_details'),
                                style: TextStyle(
                                  color: Colors.black,
                                  fontSize: 12.sp,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                              SizedBox(height: 8.h),
                              ..._filterableCustomFields.map((field) {
                      final String id = field['id']?.toString() ?? '';
                      final String type = field['type']?.toString() ?? 'text';
                      final label = (field['label'] is Map
                              ? (field['label']['ar'] ??
                                  field['label']['en'] ??
                                  field['label']['tr'])
                              : null) ??
                          id;

                      if (type == 'number') {
                        _customMinControllers.putIfAbsent(
                            id, () => TextEditingController());
                        _customMaxControllers.putIfAbsent(
                            id, () => TextEditingController());
                        final minCtrl = _customMinControllers[id]!;
                        final maxCtrl = _customMaxControllers[id]!;
                        return Padding(
                          padding: EdgeInsets.only(bottom: 12.h),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                label,
                                style: TextStyle(
                                  fontSize: 11.sp,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                              SizedBox(height: 6.h),
                              Row(
                                children: [
                                  Expanded(
                                    child: CustomTextFormField(
                                      hintText: AppLocale.tr('at_least'),
                                      controller: minCtrl,
                                      keyboardType: TextInputType.number,
                                      obscureText: false,
                                      inputFormatters: [EnglishOnlyNumberInputFormatter()],
                                    ),
                                  ),
                                  SizedBox(width: 12.w),
                                  Expanded(
                                    child: CustomTextFormField(
                                      hintText: AppLocale.tr('at_most'),
                                      controller: maxCtrl,
                                      keyboardType: TextInputType.number,
                                      obscureText: false,
                                      inputFormatters: [EnglishOnlyNumberInputFormatter()],
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        );
                      } else if (type == 'select') {
                        final options = (field['options'] as List?)
                                ?.whereType<Map>()
                                .toList() ??
                            const [];
                        if (options.isEmpty) return const SizedBox.shrink();
                        _customSelectValues.putIfAbsent(id, () => null);
                        final currentValue = _customSelectValues[id];
                        return Padding(
                          padding: EdgeInsets.only(bottom: 12.h),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                label,
                                style: TextStyle(
                                  fontSize: 11.sp,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                              SizedBox(height: 6.h),
                              DropdownButtonFormField<String>(
                                value: currentValue,
                                decoration: InputDecoration(
                                  enabledBorder: OutlineInputBorder(
                                    borderRadius: BorderRadius.circular(12.r),
                                    borderSide: BorderSide(
                                      color: Colors.grey.withOpacity(0.4),
                                    ),
                                  ),
                                  contentPadding: EdgeInsets.symmetric(
                                      horizontal: 16.w, vertical: 10.h),
                                  border: OutlineInputBorder(
                                      borderRadius: BorderRadius.circular(12.r)),
                                ),
                                items: [
                                  DropdownMenuItem<String>(
                                    value: null,
                                    child: Text(AppLocale.tr('select_option')),
                                  ),
                                  ...options.map(
                                    (opt) => DropdownMenuItem<String>(
                                      value: opt['id']?.toString() ??
                                          opt['ar']?.toString() ??
                                          opt['en']?.toString() ??
                                          opt['tr']?.toString(),
                                      child: Text(
                                        opt['ar']?.toString() ??
                                            opt['en']?.toString() ??
                                            opt['tr']?.toString() ??
                                            '',
                                      ),
                                    ),
                                  ),
                                ],
                                onChanged: (value) {
                                  setModalState(
                                      () => _customSelectValues[id] = value);
                                  setState(
                                      () => _customSelectValues[id] = value);
                                },
                              ),
                            ],
                          ),
                        );
                      } else if (type == 'checkbox') {
                        final current = _customCheckboxValues[id] ?? false;
                        return CheckboxListTile(
                          contentPadding: EdgeInsets.zero,
                          title: Text(
                            label,
                            style: TextStyle(fontSize: 12.sp),
                          ),
                          value: current,
                          onChanged: (v) {
                            setModalState(
                                () => _customCheckboxValues[id] = v ?? false);
                            setState(
                                () => _customCheckboxValues[id] = v ?? false);
                          },
                        );
                      } else if (type == 'date') {
                        _customDateAfterValues.putIfAbsent(id, () => null);
                        final selected = _customDateAfterValues[id];
                        final dateText = selected != null
                            ? '${selected.year.toString().padLeft(4, '0')}-${selected.month.toString().padLeft(2, '0')}-${selected.day.toString().padLeft(2, '0')}'
                            : AppLocale.tr('filter_expires_after');
                        return Padding(
                          padding: EdgeInsets.only(bottom: 12.h),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                label,
                                style: TextStyle(
                                  fontSize: 11.sp,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                              SizedBox(height: 6.h),
                              InkWell(
                                onTap: () async {
                                  final now = DateTime.now();
                                  final picked = await showDatePicker(
                                    context: ctx,
                                    initialDate: selected ?? now,
                                    firstDate: DateTime(now.year - 10),
                                    lastDate: DateTime(now.year + 30),
                                  );
                                  if (picked != null) {
                                    setModalState(
                                        () => _customDateAfterValues[id] = picked);
                                    setState(
                                        () => _customDateAfterValues[id] = picked);
                                  }
                                },
                                child: Container(
                                  width: double.infinity,
                                  padding: EdgeInsets.symmetric(
                                      horizontal: 16.w, vertical: 12.h),
                                  decoration: BoxDecoration(
                                    border: Border.all(
                                        color: Colors.grey.withOpacity(0.4)),
                                    borderRadius: BorderRadius.circular(12.r),
                                  ),
                                  child: Row(
                                    children: [
                                      Icon(Icons.calendar_today,
                                          size: 18.sp, color: Colors.grey[700]),
                                      SizedBox(width: 8.w),
                                      Expanded(
                                        child: Text(
                                          dateText,
                                          style: TextStyle(fontSize: 12.sp),
                                        ),
                                      ),
                                      if (selected != null)
                                        GestureDetector(
                                          onTap: () {
                                            setModalState(
                                                () => _customDateAfterValues[id] = null);
                                            setState(
                                                () => _customDateAfterValues[id] = null);
                                          },
                                          child: Icon(Icons.close,
                                              size: 18.sp, color: Colors.grey),
                                        ),
                                    ],
                                  ),
                                ),
                              ),
                            ],
                          ),
                        );
                      }

                      return const SizedBox.shrink();
                    }),
                            ],
                          ],
                        ),
                      ),
                    ),
                    SizedBox(height: 24.h),
                    Row(
                      children: [
                        Expanded(
                          child: CustomButton(
                            text: AppLocale.tr('clear_filter'),
                            onTap: () {
                              _minController.clear();
                              _maxController.clear();
                              for (final c in _customMinControllers.values) {
                                c.clear();
                              }
                              for (final c in _customMaxControllers.values) {
                                c.clear();
                              }
                              _customSelectValues.clear();
                              _customCheckboxValues.clear();
                              _customDateAfterValues.clear();
                              setState(() => _selectedCategoryName = null);
                              setModalState(() => _selectedCategoryName = null);
                              Navigator.pop(context);
                              _loadAds();
                            },
                            backgroundColor: Colors.grey[200],
                          ),
                        ),
                        SizedBox(width: 12.w),
                        Expanded(
                          child: CustomButton(
                            text: AppLocale.tr('apply_filter'),
                            onTap: _applyFilter,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
      );
    });
  }

  void _openSortSheet(BuildContext context) {
    final sortLabels = [
      AppLocale.tr('sort_date_newest'),
      AppLocale.tr('sort_date_oldest'),
      AppLocale.tr('sort_price_low'),
      AppLocale.tr('sort_price_high'),
    ];
    showModalBottomSheet(
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      context: context,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (ctx, setModalState) {
            return SafeArea(
              child: Padding(
                padding: EdgeInsets.only(
                  left: 20.w,
                  right: 20.w,
                  top: 20.w,
                  bottom: 20.w + MediaQuery.of(ctx).viewInsets.bottom,
                ),
                child: ConstrainedBox(
                  constraints: BoxConstraints(
                    maxHeight: MediaQuery.of(ctx).size.height * 0.8,
                  ),
                  child: SingleChildScrollView(
                    physics: const BouncingScrollPhysics(),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Text(
                          AppLocale.tr('sort_by'),
                          style: TextStyle(
                            color: Colors.black,
                            fontSize: 20.sp,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        SizedBox(height: 20.h),
                        ...List.generate(sortLabels.length, (index) {
                          return Padding(
                            padding: EdgeInsets.only(bottom: 8.h),
                            child: InkWell(
                              onTap: () {
                                setModalState(() => _selectedFilterIndex = index);
                                setState(() => _selectedFilterIndex = index);
                              },
                              child: Container(
                                padding: EdgeInsets.symmetric(vertical: 12.h, horizontal: 16.w),
                                decoration: BoxDecoration(
                                  color: _selectedFilterIndex == index
                                      ? AppColors.darkBlue.withOpacity(0.15)
                                      : Colors.grey[200],
                                  borderRadius: BorderRadius.circular(8.r),
                                ),
                                child: Row(
                                  children: [
                                    Icon(
                                      _selectedFilterIndex == index
                                          ? Icons.radio_button_checked
                                          : Icons.radio_button_off,
                                      color: _selectedFilterIndex == index
                                          ? AppColors.darkBlue
                                          : Colors.grey[600],
                                      size: 22.sp,
                                    ),
                                    SizedBox(width: 12.w),
                                    Expanded(
                                      child: Text(
                                        sortLabels[index],
                                        style: TextStyle(
                                          fontSize: 14.sp,
                                          fontWeight: _selectedFilterIndex == index
                                              ? FontWeight.w600
                                              : FontWeight.w500,
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          );
                        }),
                        SizedBox(height: 20.h),
                        CustomButton(
                          text: AppLocale.tr('apply_filter'),
                          onTap: _applySort,
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            );
          },
        );
      },
    );
  }

  void _openAppearanceSheet(BuildContext context) {
    showModalBottomSheet(
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      context: context,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (ctx, setModalState) {
            return Padding(
              padding: EdgeInsets.all(15.h),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    AppLocale.tr('appearance'),
                    style: TextStyle(
                      color: Colors.black,
                      fontSize: 20.sp,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  SizedBox(height: 30.h),
                  Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      RadioListTile<String>(
                        title: Text(AppLocale.tr('on_map')),
                        value: 'map',
                        groupValue: _selectedAppearance,
                        onChanged: (value) {
                          setModalState(() => _selectedAppearance = value);
                          setState(() => _selectedAppearance = value);
                        },
                      ),
                      RadioListTile<String>(
                        title: Text(AppLocale.tr('list_view')),
                        value: 'list',
                        groupValue: _selectedAppearance,
                        onChanged: (value) {
                          setModalState(() => _selectedAppearance = value);
                          setState(() => _selectedAppearance = value);
                        },
                      ),
                    ],
                  ),
                  SizedBox(height: 24.h),
                  CustomButton(
                    text: AppLocale.tr('apply_filter'),
                    onTap: _applyAppearance,
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: widget.title),
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: EdgeInsets.symmetric(horizontal: 20.w, vertical: 16.h),
            width: double.infinity,
            color: AppColors.darkBlue,
            child: Row(
              children: [
                GestureDetector(
                  onTap: () => _openFilterSheet(context),
                  child: Container(
                    padding: EdgeInsets.symmetric(horizontal: 10.w, vertical: 5.h),
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(8.r),
                      color: Colors.white.withOpacity(0.2),
                    ),
                    child: Center(
                      child: Image.asset(
                        'assets/images/sliders-horizontal.png',
                        width: 20.w,
                        height: 25.h,
                        errorBuilder: (_, __, ___) => Icon(Icons.tune, color: Colors.white, size: 22.sp),
                      ),
                    ),
                  ),
                ),
                SizedBox(width: 10.w),
                _FilterChip(
                  title: AppLocale.tr('sort_by'),
                  onTap: () => _openSortSheet(context),
                ),
                SizedBox(width: 10.w),
                _FilterChip(
                  title: AppLocale.tr('appearance'),
                  onTap: () => _openAppearanceSheet(context),
                ),
                const Spacer(),
                GestureDetector(
                  onTap: _saveCurrentListingFilters,
                  child: Container(
                    padding: EdgeInsets.symmetric(horizontal: 10.w, vertical: 5.h),
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(8.r),
                      color: Colors.white,
                    ),
                    child: Text(
                      AppLocale.tr('save_search'),
                      style: TextStyle(
                        color: AppColors.darkBlue,
                        fontSize: 12.sp,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
          Padding(
            padding: EdgeInsets.symmetric(horizontal: 20.w, vertical: 12.h),
            child: Text(
              AppLocale.tr('results_available').replaceAll('%s', '$_total'),
              style: TextStyle(
                color: Colors.black,
                fontSize: 14.sp,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
          Expanded(
            child: _loading
                ? Center(
                    child: CircularProgressIndicator(color: AppColors.darkBlue),
                  )
                : _ads.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.inbox_outlined, size: 64.sp, color: Colors.grey),
                            SizedBox(height: 16.h),
                            Text(
                              AppLocale.tr('no_ads'),
                              style: TextStyle(
                                fontSize: 16.sp,
                                color: Colors.grey[600],
                              ),
                            ),
                          ],
                        ),
                      )
                    : _selectedAppearance == 'map'
                        ? Column(
                            children: [
                              Expanded(
                                child: AdsResultsMap(
                                  key: ValueKey('ads_map_$_mapReloadToken'),
                                  ads: _ads,
                                  focusUserLocationOnInit: true,
                                ),
                              ),
                              if (_loadingMore)
                                Padding(
                                  padding: EdgeInsets.symmetric(vertical: 8.h),
                                  child: Center(
                                    child: SizedBox(
                                      width: 28.w,
                                      height: 28.w,
                                      child: CircularProgressIndicator(
                                        color: AppColors.darkBlue,
                                        strokeWidth: 2,
                                      ),
                                    ),
                                  ),
                                )
                              else if (_currentPage < _lastPage)
                                Padding(
                                  padding: EdgeInsets.only(bottom: 12.h),
                                  child: Center(
                                    child: TextButton(
                                      onPressed: () => _loadAds(loadMore: true),
                                      child: Text(AppLocale.tr('load_more')),
                                    ),
                                  ),
                                ),
                            ],
                          )
                        : RefreshIndicator(
                            onRefresh: () => _loadAds(),
                            child: ListView.builder(
                              physics: const BouncingScrollPhysics(parent: AlwaysScrollableScrollPhysics()),
                              padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 12.h),
                              itemCount: _ads.length + (_loadingMore ? 1 : 0) + (_currentPage < _lastPage && !_loadingMore ? 1 : 0),
                              itemBuilder: (context, index) {
                                if (index == _ads.length) {
                                  if (_loadingMore) {
                                    return Center(
                                      child: Padding(
                                        padding: EdgeInsets.all(16.w),
                                        child: CircularProgressIndicator(color: AppColors.darkBlue),
                                      ),
                                    );
                                  }
                                  return Padding(
                                    padding: EdgeInsets.all(16.w),
                                    child: Center(
                                      child: TextButton(
                                        onPressed: () => _loadAds(loadMore: true),
                                        child: Text(AppLocale.tr('load_more')),
                                      ),
                                    ),
                                  );
                                }
                                return _AdCard(
                                  ad: _ads[index],
                                  onTap: () => context.push(AdDetailsPage(adUid: _ads[index].uid)),
                                );
                              },
                            ),
                          ),
          ),
        ],
      ),
    );
  }
}

class _FilterChip extends StatelessWidget {
  final String title;
  final VoidCallback onTap;

  const _FilterChip({required this.title, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: EdgeInsets.symmetric(horizontal: 10.w, vertical: 5.h),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(8.r),
          color: Colors.white.withOpacity(0.2),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.arrow_drop_down, color: Colors.white, size: 20.sp),
            SizedBox(width: 4.w),
            Text(
              title,
              style: TextStyle(
                color: Colors.white,
                fontSize: 12.sp,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _AdCard extends StatelessWidget {
  final AdModel ad;
  final VoidCallback onTap;

  const _AdCard({required this.ad, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
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
                        ? ListAdThumbnailImage(
                            imageUrl: ad.imageUrl!,
                            width: 75.w,
                            maxHeight: 75.w,
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
                  top: -4.h,
                  right: -4.w,
                  child: FavoriteIconButton(
                    adUid: ad.uid,
                    initialIsFavorite: ad.isFavorite,
                    size: 18.sp,
                    backgroundColor: Colors.white,
                  ),
                ),
              ],
            ),
            SizedBox(width: 12.w),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
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
                          iconSize: 14,
                          style: TextStyle(
                            fontSize: 12.sp,
                            color: Colors.grey[600],
                          ),
                          iconColor: Colors.grey[600],
                        ),
                      ),
                      if (ad.displayPriceForUi != null)
                        Text(
                          ad.displayPriceForUi!,
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
      width: 80.w,
      height: math.max(80.h, 80.w * 2.2),
      color: Colors.grey[200],
      child: Icon(Icons.image_not_supported, color: Colors.grey[400]),
    );
  }
}
