import 'dart:async';

import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/storage/token_storage.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/models/ad_model.dart';
import 'package:a3lnha/data/models/category_model.dart';
import 'package:a3lnha/data/services/ad_service.dart';
import 'package:a3lnha/data/services/category_service.dart';
import 'package:a3lnha/data/services/saved_search_service.dart';
import 'package:a3lnha/helpers/custom_fields_resolver.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/helpers/localized_name.dart';
import 'package:a3lnha/helpers/numeral_helper.dart';
import 'package:a3lnha/presentation/pages/home/ad_details_page.dart';
import 'package:a3lnha/presentation/widgets/ad_list_location_label.dart';
import 'package:a3lnha/presentation/widgets/ad_status_badge_icon.dart';
import 'package:a3lnha/presentation/widgets/ads_results_map.dart';
import 'package:a3lnha/presentation/widgets/shared/app_network_image.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_button.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_text_form_field.dart';
import 'package:a3lnha/presentation/widgets/shared/favorite_icon_button.dart';
import 'package:a3lnha/presentation/pages/search/search_results_page.dart';
import 'package:a3lnha/presentation/widgets/search_category_suggestions_panel.dart';
import 'package:a3lnha/presentation/pages/auth/login_page.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class SearchPage extends StatefulWidget {
  final String? initialSearchQuery;
  final int? initialCategoryId;
  final String? initialCategoryName;
  /// عند الفتح من نتائج الأقسام: تضييق الإعلانات على فئة فرعية محددة.
  final int? initialSubcategoryId;

  const SearchPage({
    super.key,
    this.initialSearchQuery,
    this.initialCategoryId,
    this.initialCategoryName,
    this.initialSubcategoryId,
  });

  @override
  State<SearchPage> createState() => _SearchPageState();
}

class _SearchPageState extends State<SearchPage> {
  final TextEditingController searchController = TextEditingController();
  final TextEditingController minController = TextEditingController();
  final TextEditingController maxController = TextEditingController();

  List<AdModel> _ads = [];
  List<CategoryModel> _categories = [];
  CategoryModel? _currentCategory;
  bool _loading = false;
  int _total = 0;
  int _currentPage = 1;
  int _lastPage = 1;
  bool _loadingMore = false;
  bool _hasSearched = false;

  /// أثناء الكتابة: قائمة منسدلة تحت حقل البحث
  List<SearchCategoryItem> _dropdownSuggestions = [];
  bool _loadingDropdownSuggestions = false;
  int _dropdownSuggestGen = 0;
  Timer? _suggestDebounce;

  final FocusNode _searchFocus = FocusNode(debugLabel: 'searchPageQuery');
  final LayerLink _searchFieldLayerLink = LayerLink();
  final GlobalKey _searchFieldKey = GlobalKey();
  OverlayEntry? _dropdownOverlayEntry;

  /// فلاتر الحقول المخصصة للقسم الحالي
  final Map<String, TextEditingController> _customMinControllers = {};
  final Map<String, TextEditingController> _customMaxControllers = {};
  final Map<String, String?> _customSelectValues = {};
  final Map<String, bool> _customCheckboxValues = {};
  final Map<String, DateTime?> _customDateAfterValues = {};

  /// قيم الفرز المرسلة للـ API: date_desc, date_asc, price_asc, price_desc
  static const List<String> _sortByValues = [
    'date_desc',
    'date_asc',
    'price_asc',
    'price_desc',
  ];

  int? selectedfilterIndex;
  /// `list` أو `map` — العرض كقائمة أو على الخريطة
  String? selectedApperance = 'list';
  String? selectedtype;
  /// عند اختيار اقتراح «فئة فرعية» من البحث: تمرير subcategory_id لـ API مع category_id.
  int? _filterSubcategoryId;

  @override
  void initState() {
    super.initState();
    if (widget.initialSearchQuery != null && widget.initialSearchQuery!.isNotEmpty) {
      searchController.text = widget.initialSearchQuery!;
    }
    if (widget.initialCategoryName != null && widget.initialCategoryName!.isNotEmpty) {
      selectedtype = widget.initialCategoryName;
    }
    _loadCategories().then((_) {
      if (widget.initialCategoryId != null && _categories.isNotEmpty) {
        final match = _categories.where((c) => c.id == widget.initialCategoryId).toList();
        if (match.isNotEmpty) {
          // يجب أن يطابق `value` في Dropdown (حقل name) حتى يُحدَّد القسم بشكل صحيح
          selectedtype = match.first.name.isNotEmpty
              ? match.first.name
              : match.first.displayName;
        }
      }
      if (widget.initialSubcategoryId != null && widget.initialSubcategoryId! > 0) {
        _filterSubcategoryId = widget.initialSubcategoryId;
      }
      if (mounted) setState(() {});
      _loadCategoryForFilters();
      // تنفيذ البحث تلقائياً إذا كانت هناك كلمة بحث أولية أو فئة محددة
      if ((widget.initialSearchQuery != null && widget.initialSearchQuery!.isNotEmpty) ||
          widget.initialCategoryId != null) {
        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (!mounted) return;
          final keepSub = widget.initialSubcategoryId != null && widget.initialSubcategoryId! > 0;
          _loadAds(preserveSubcategory: keepSub);
        });
      }
    });

    searchController.addListener(_onSearchTextChanged);
    _searchFocus.addListener(_onSearchFocusChanged);
  }

  void _onSearchFocusChanged() {
    if (!_searchFocus.hasFocus) {
      Future.delayed(const Duration(milliseconds: 240), () {
        if (!mounted || _searchFocus.hasFocus) return;
        setState(() {
          _dropdownSuggestions = [];
          _loadingDropdownSuggestions = false;
        });
        _scheduleSyncDropdownOverlay();
      });
    }
    if (mounted) setState(() {});
    _scheduleSyncDropdownOverlay();
  }

  void _removeDropdownOverlay() {
    _dropdownOverlayEntry?.remove();
    _dropdownOverlayEntry = null;
  }

  void _scheduleSyncDropdownOverlay() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      _removeDropdownOverlay();

      final q = searchController.text.trim();
      final show = _searchFocus.hasFocus &&
          q.length >= AdService.minSearchLength &&
          (_loadingDropdownSuggestions || _dropdownSuggestions.isNotEmpty);

      if (!show) return;

      final overlay = Overlay.maybeOf(context, rootOverlay: true);
      if (overlay == null) return;

      var fieldWidth = MediaQuery.sizeOf(context).width - 40.w - 8.w - 56.w;
      final rb = _searchFieldKey.currentContext?.findRenderObject();
      if (rb is RenderBox && rb.hasSize) {
        fieldWidth = rb.size.width;
      }

      _dropdownOverlayEntry = OverlayEntry(
        builder: (ctx) {
          final rtl = Directionality.of(ctx) == TextDirection.rtl;
          return CompositedTransformFollower(
            link: _searchFieldLayerLink,
            showWhenUnlinked: false,
            offset: Offset(0, 6.h),
            targetAnchor: rtl ? Alignment.bottomRight : Alignment.bottomLeft,
            followerAnchor: rtl ? Alignment.topRight : Alignment.topLeft,
            child: SizedBox(
              width: fieldWidth,
              child: Material(
                elevation: 16,
                shadowColor: Colors.black45,
                borderRadius: BorderRadius.circular(12.r),
                clipBehavior: Clip.antiAlias,
                color: Colors.white,
                child: SearchCategorySuggestionsPanel(
                  loading: _loadingDropdownSuggestions,
                  items: _dropdownSuggestions,
                  onPick: (it) {
                    _removeDropdownOverlay();
                    _onSuggestionTap(it);
                  },
                  sectionTitle: AppLocale.tr('category_filter'),
                  displayName: _suggestionDisplayName,
                  maxListHeight: 280.h,
                  dropdownChrome: true,
                  onDropdownSaveSearch: _onDropdownSaveSearchTapped,
                ),
              ),
            ),
          );
        },
      );
      overlay.insert(_dropdownOverlayEntry!);
    });
  }

  void _onDropdownSaveSearchTapped() {
    _saveCurrentSearch();
  }

  Map<String, dynamic> _buildFiltersForSaving() {
    final custom = _buildCustomFiltersPayload();
    return {
      'search': searchController.text.trim(),
      'category_id': _effectiveCategoryId,
      'subcategory_id': _filterSubcategoryId,
      'min_price': minController.text.trim().isEmpty ? null : minController.text.trim(),
      'max_price': maxController.text.trim().isEmpty ? null : maxController.text.trim(),
      'custom_filters': custom,
    };
  }

  Future<void> _saveCurrentSearch() async {
    if (!TokenStorage.hasToken()) {
      context.push(LoginPage());
      return;
    }
    final message = await SavedSearchService.saveSearch(
      filters: _buildFiltersForSaving(),
    );
    showToast(message: message ?? AppLocale.tr('saved_search_saved'));
  }

  void _onSearchTextChanged() {
    final q = searchController.text.trim();
    _suggestDebounce?.cancel();
    _suggestDebounce = Timer(const Duration(milliseconds: 260), () {
      _loadDropdownSuggestions(q);
    });
  }

  Future<void> _loadDropdownSuggestions(String query) async {
    final q = query.trim();
    final gen = ++_dropdownSuggestGen;
    if (q.length < AdService.minSearchLength) {
      if (mounted) {
        setState(() {
          _loadingDropdownSuggestions = false;
          _dropdownSuggestions = [];
        });
        _scheduleSyncDropdownOverlay();
      }
      return;
    }
    if (mounted) setState(() => _loadingDropdownSuggestions = true);
    _scheduleSyncDropdownOverlay();
    try {
      final res = await AdService.getSearchCategories(q);
      if (!mounted || gen != _dropdownSuggestGen) return;
      setState(() {
        _loadingDropdownSuggestions = false;
        _dropdownSuggestions = res.data;
      });
      _scheduleSyncDropdownOverlay();
    } catch (_) {
      if (!mounted || gen != _dropdownSuggestGen) return;
      setState(() {
        _loadingDropdownSuggestions = false;
        _dropdownSuggestions = [];
      });
      _scheduleSyncDropdownOverlay();
    }
  }

  /// معرّف الفئة المطبَّق في البحث والفلاتر (من اختيار المستخدم، وليس فقط القيمة الأولية).
  int? get _effectiveCategoryId => _categoryId;

  /// تحميل تفاصيل الفئة (بما فيها الحقول المخصصة) لعرض الفلاتر حسب القسم.
  /// [preferFresh]: عند فتح ورقة الفلتر نجلب من الـ API لأن كاش الأقسام قد لا يحوي `custom_fields`.
  Future<void> _loadCategoryForFilters({bool preferFresh = false}) async {
    final id = _effectiveCategoryId;
    if (id == null) {
      if (mounted) setState(() => _currentCategory = null);
      return;
    }
    var cat = await CategoryService.getCategory(id, forceRefresh: preferFresh);
    if (cat != null &&
        !preferFresh &&
        (cat.customFields == null || cat.customFields!.isEmpty)) {
      cat = await CategoryService.getCategory(id, forceRefresh: true);
    }
    if (mounted) setState(() => _currentCategory = cat);
  }

  List<Map<String, dynamic>> get _resolvedSchemaFields {
    final fields = _currentCategory?.customFields;
    if (fields == null) return const [];
    return fields.map((e) => Map<String, dynamic>.from(e)).toList();
  }

  List<Map<String, dynamic>> get _filterableCustomFields =>
      CustomFieldsResolver.filterableFields(_resolvedSchemaFields);

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
        if (minText.isNotEmpty) out['cf_${id}_min'] = minText;
        if (maxText.isNotEmpty) out['cf_${id}_max'] = maxText;
      } else if (type == 'select') {
        final v = _customSelectValues[id];
        if (v != null && v.isNotEmpty) out['cf_$id'] = v;
      } else if (type == 'checkbox') {
        if (_customCheckboxValues[id] ?? false) out['cf_$id'] = '1';
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

  @override
  void dispose() {
    _removeDropdownOverlay();
    _searchFocus.removeListener(_onSearchFocusChanged);
    _searchFocus.dispose();
    searchController.removeListener(_onSearchTextChanged);
    searchController.dispose();
    _suggestDebounce?.cancel();
    minController.dispose();
    maxController.dispose();
    for (final c in _customMinControllers.values) c.dispose();
    for (final c in _customMaxControllers.values) c.dispose();
    super.dispose();
  }

  int? get _categoryId {
    if (selectedtype == null || selectedtype!.isEmpty || _categories.isEmpty) {
      return null;
    }
    final t = selectedtype!.trim();
    for (final c in _categories) {
      if (t == c.name ||
          (c.nameAr != null && t == c.nameAr) ||
          (c.nameEn != null && t == c.nameEn) ||
          (c.nameTr != null && t == c.nameTr) ||
          t == c.displayName) {
        return c.id;
      }
    }
    return null;
  }

  String? get _sortByValue {
    if (selectedfilterIndex == null || selectedfilterIndex! < 0 || selectedfilterIndex! >= _sortByValues.length) return null;
    return _sortByValues[selectedfilterIndex!];
  }

  Future<void> _loadCategories() async {
    final list = await CategoryService.getCategories();
    if (mounted) setState(() => _categories = list);
  }

  Future<void> _loadAds({bool loadMore = false, bool preserveSubcategory = false}) async {
    final searchQuery = searchController.text.trim();
    if (!loadMore && searchQuery.isNotEmpty && searchQuery.length < AdService.minSearchLength) {
      showToast(message: AppLocale.tr('search_min_chars'));
      return;
    }
    final categoryIdForSearch = _effectiveCategoryId;
    if (!loadMore &&
        searchQuery.isNotEmpty &&
        searchQuery.length >= AdService.minSearchLength &&
        categoryIdForSearch == null &&
        _filterSubcategoryId == null) {
      if (!mounted) return;
      _removeDropdownOverlay();
      _searchFocus.unfocus();
      await context.push<void>(SearchResultsPage(initialQuery: searchQuery));
      if (!mounted) return;
      return;
    }
    if (!loadMore &&
        searchQuery.isNotEmpty &&
        _filterSubcategoryId != null &&
        !preserveSubcategory) {
      setState(() => _filterSubcategoryId = null);
    }
    if (loadMore) {
      if (_currentPage >= _lastPage) return;
      setState(() => _loadingMore = true);
    } else {
      _searchFocus.unfocus();
      setState(() {
        _loading = true;
        _hasSearched = true;
      });
    }
    final minP = num.tryParse(minController.text.trim());
    final maxP = num.tryParse(maxController.text.trim());
    final effectiveCategoryId = _effectiveCategoryId;
    final customFilters = _buildCustomFiltersPayload();

    final response = await AdService.getAds(
      search: searchQuery.isEmpty ? null : searchQuery,
      categoryId: effectiveCategoryId,
      subcategoryId: _filterSubcategoryId,
      minPrice: minP,
      maxPrice: maxP,
      page: loadMore ? _currentPage + 1 : 1,
      perPage: 20,
      sortBy: _sortByValue,
      customFilters: customFilters.isEmpty ? null : customFilters,
    );

    if (!mounted) return;
    setState(() {
      if (loadMore) {
        _ads.addAll(response.ads);
        _currentPage = response.currentPage;
        _loadingMore = false;
      } else {
        _ads.clear();
        _ads.addAll(response.ads);
        _currentPage = response.currentPage;
        _lastPage = response.lastPage;
        _total = response.total;
        _loading = false;
      }
    });
  }

  String _suggestionDisplayName(SearchCategoryItem item) {
    return getLocalizedName(
      nameAr: item.nameAr,
      nameEn: item.nameEn,
      nameTr: item.nameTr,
      defaultName: item.name,
    );
  }

  Future<void> _onSuggestionTap(SearchCategoryItem item) async {
    // قسم رئيسي (أو فرعي): نبقي كلمة البحث ونضيّق النتائج على هذا القسم — نفس منطق نتائج البحث من الرئيسية.
    final match = _categories.where((c) => c.id == item.categoryId).toList();
    _removeDropdownOverlay();
    _searchFocus.unfocus();
    setState(() {
      selectedtype = match.isNotEmpty ? (match.first.name.isNotEmpty ? match.first.name : match.first.displayName) : item.name;
      _filterSubcategoryId = item.kind == 'subcategory' ? item.subcategoryId : null;
      _dropdownSuggestions = [];
      _hasSearched = true;
    });
    await _loadCategoryForFilters(preferFresh: false);
    await _loadAds(preserveSubcategory: item.kind == 'subcategory' && item.subcategoryId != null);
  }

  void _applyFilter() {
    Navigator.pop(context);
    _loadAds();
  }

  void _applyAppearance() {
    Navigator.pop(context);
    setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: AppLocale.tr('search_results')),
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: EdgeInsets.symmetric(horizontal: 20.w, vertical: 20.h),
            width: double.infinity,
            color: AppColors.darkBlue,
            child: Column(
              children: [
                Row(
                  children: [
                    Expanded(
                      child: CompositedTransformTarget(
                        key: _searchFieldKey,
                        link: _searchFieldLayerLink,
                        child: CustomTextFormField(
                          hintText: AppLocale.tr('search_in_ads'),
                          controller: searchController,
                          keyboardType: TextInputType.text,
                          obscureText: false,
                          prefixIcon: Icon(Icons.search, color: Colors.grey),
                          onFieldSubmitted: (_) => _loadAds(),
                          focusNode: _searchFocus,
                        ),
                      ),
                    ),
                    SizedBox(width: 8.w),
                    GestureDetector(
                      onTap: () => _loadAds(),
                      child: Container(
                        padding: EdgeInsets.symmetric(
                            horizontal: 16.w, vertical: 12.h),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(8.r),
                        ),
                        child: Icon(Icons.search, color: AppColors.darkBlue),
                      ),
                    ),
                  ],
                ),
                SizedBox(height: 15.h),
                Row(
                  children: [
                    GestureDetector(
                      onTap: () {
                        _openFilterSheet(context);
                      },
                      child: Container(
                        padding: EdgeInsets.symmetric(
                          horizontal: 10.w,
                          vertical: 5.h,
                        ),
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(8.r),
                          // ignore: deprecated_member_use
                          color: Colors.white.withOpacity(0.2),
                        ),
                        child: Center(
                          child: Image.asset(
                            "assets/images/sliders-horizontal.png",
                            width: 20.w,
                            height: 25.h,
                          ),
                        ),
                      ),
                    ),
                    SizedBox(width: 10.w),
                    FilterWidget(
                      title: AppLocale.tr('sort_by'),
                      onTap: () {
                        _openFilterBySheet(context);
                      },
                    ),
                    SizedBox(width: 10.w),
                    FilterWidget(
                      title: AppLocale.tr('appearance'),
                      onTap: () {
                        _openApperanceSheet(context);
                      },
                    ),
                    Spacer(),
                    GestureDetector(
                      onTap: _saveCurrentSearch,
                      child: Container(
                        width: 75.w,
                        height: 35.h,
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(6.r),
                          color: Colors.white,
                        ),
                        child: Center(
                          child: Text(
                            AppLocale.tr('save_search'),
                            style: TextStyle(
                              color: AppColors.darkBlue,
                              fontSize: 12.sp,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          SizedBox(height: 20.h),
          Padding(
            padding: EdgeInsets.symmetric(horizontal: 20.w),
            child: Text(
              _hasSearched
                  ? AppLocale.tr('results_available').replaceAll('%s', '$_total')
                  : AppLocale.tr('enter_search_hint'),
              style: TextStyle(
                color: Colors.black,
                fontSize: 16.sp,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
          SizedBox(height: 20.h),
          Expanded(
            child: _loading
                ? Center(
                    child: CircularProgressIndicator(color: AppColors.darkBlue),
                  )
                : !_hasSearched
                    ? Center(
                        child: Text(
                          AppLocale.tr('search_in_ads_hint'),
                          style: TextStyle(
                            fontSize: 16.sp,
                            color: Colors.grey[600],
                          ),
                        ),
                      )
                    : _ads.isEmpty
                        ? Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.search_off,
                                    size: 64.sp, color: Colors.grey),
                                SizedBox(height: 16.h),
                                Text(
                                  AppLocale.tr('no_results'),
                                  style: TextStyle(
                                    fontSize: 16.sp,
                                    color: Colors.grey[600],
                                  ),
                                ),
                              ],
                            ),
                          )
                        : selectedApperance == 'map'
                            ? Column(
                                children: [
                                  Expanded(child: AdsResultsMap(ads: _ads)),
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
                                  physics: BouncingScrollPhysics(
                                      parent: AlwaysScrollableScrollPhysics()),
                                  padding: EdgeInsets.symmetric(
                                      horizontal: 16.w, vertical: 8.h),
                                  itemCount: _ads.length +
                                      (_loadingMore ? 1 : 0) +
                                      (_currentPage < _lastPage && !_loadingMore
                                          ? 1
                                          : 0),
                                  itemBuilder: (context, index) {
                                    if (index == _ads.length) {
                                      if (_loadingMore) {
                                        return Center(
                                          child: Padding(
                                            padding: EdgeInsets.all(16.w),
                                            child: CircularProgressIndicator(
                                                color: AppColors.darkBlue),
                                          ),
                                        );
                                      }
                                      return Padding(
                                        padding: EdgeInsets.all(16.w),
                                        child: Center(
                                          child: TextButton(
                                            onPressed: () =>
                                                _loadAds(loadMore: true),
                                            child: Text(AppLocale.tr('load_more')),
                                          ),
                                        ),
                                      );
                                    }
                                    final ad = _ads[index];
                                    return _SearchAdCard(
                                      ad: ad,
                                      onTap: () => context.push(
                                          AdDetailsPage(adUid: ad.uid)),
                                    );
                                  },
                                ),
                              ),
          ),
        ],
      ),
    );
  }

  void _openFilterSheet(BuildContext context) {
    _loadCategoryForFilters(preferFresh: true).then((_) {
      if (!mounted) return;
      _showFilterSheetContent(context);
    });
  }

  void _showFilterSheetContent(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) {
        return StatefulBuilder(
          builder: (ctx, setModalState) {
            final hasCustomFilters = _filterableCustomFields.isNotEmpty;
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
                              value: selectedtype,
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
                                setModalState(() => selectedtype = value);
                                setState(() {
                                  selectedtype = value;
                                  _filterSubcategoryId = null;
                                });
                                _loadCategoryForFilters().then((_) {
                                  if (mounted) {
                                    setModalState(() {});
                                    setState(() {});
                                  }
                                });
                              },
                            ),
                            SizedBox(height: 20.h),
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
                                    controller: minController,
                                    keyboardType: TextInputType.number,
                                    obscureText: false,
                                  ),
                                ),
                                SizedBox(width: 16.w),
                                Expanded(
                                  child: CustomTextFormField(
                                    hintText: AppLocale.tr('at_most'),
                                    controller: maxController,
                                    keyboardType: TextInputType.number,
                                    obscureText: false,
                                  ),
                                ),
                              ],
                            ),
                            if (hasCustomFilters) ...[
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
                                        Text(label, style: TextStyle(fontSize: 11.sp, fontWeight: FontWeight.w600)),
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
                                }
                                if (type == 'select') {
                                  final options = (field['options'] as List?)
                                          ?.whereType<Map>()
                                          .toList() ??
                                      [];
                                  if (options.isEmpty) return const SizedBox.shrink();
                                  _customSelectValues.putIfAbsent(id, () => null);
                                  final currentValue = _customSelectValues[id];
                                  return Padding(
                                    padding: EdgeInsets.only(bottom: 12.h),
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(label, style: TextStyle(fontSize: 11.sp, fontWeight: FontWeight.w600)),
                                        SizedBox(height: 6.h),
                                        DropdownButtonFormField<String>(
                                          value: currentValue,
                                          decoration: InputDecoration(
                                            enabledBorder: OutlineInputBorder(
                                              borderRadius: BorderRadius.circular(12.r),
                                              borderSide: BorderSide(color: Colors.grey.withOpacity(0.4)),
                                            ),
                                            contentPadding: EdgeInsets.symmetric(
                                                horizontal: 16.w, vertical: 10.h),
                                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12.r)),
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
                                          onChanged: (v) {
                                            setModalState(() => _customSelectValues[id] = v);
                                            setState(() => _customSelectValues[id] = v);
                                          },
                                        ),
                                      ],
                                    ),
                                  );
                                }
                                if (type == 'checkbox') {
                                  _customCheckboxValues.putIfAbsent(id, () => false);
                                  final checked = _customCheckboxValues[id] ?? false;
                                  return Padding(
                                    padding: EdgeInsets.only(bottom: 12.h),
                                    child: InkWell(
                                      onTap: () {
                                        setModalState(() => _customCheckboxValues[id] = !checked);
                                        setState(() => _customCheckboxValues[id] = !checked);
                                      },
                                      child: Row(
                                        children: [
                                          Checkbox(
                                            value: checked,
                                            onChanged: (v) {
                                              setModalState(() => _customCheckboxValues[id] = v ?? false);
                                              setState(() => _customCheckboxValues[id] = v ?? false);
                                            },
                                          ),
                                          Expanded(child: Text(label, style: TextStyle(fontSize: 12.sp))),
                                        ],
                                      ),
                                    ),
                                  );
                                }
                                if (type == 'date') {
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
                                        Text(label, style: TextStyle(fontSize: 11.sp, fontWeight: FontWeight.w600)),
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
                                              setModalState(() => _customDateAfterValues[id] = picked);
                                              setState(() => _customDateAfterValues[id] = picked);
                                            }
                                          },
                                          child: Container(
                                            width: double.infinity,
                                            padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 12.h),
                                            decoration: BoxDecoration(
                                              border: Border.all(color: Colors.grey.withOpacity(0.4)),
                                              borderRadius: BorderRadius.circular(12.r),
                                            ),
                                            child: Row(
                                              children: [
                                                Icon(Icons.calendar_today, size: 18.sp, color: Colors.grey[700]),
                                                SizedBox(width: 8.w),
                                                Expanded(child: Text(dateText, style: TextStyle(fontSize: 12.sp))),
                                                if (selected != null)
                                                  GestureDetector(
                                                    onTap: () {
                                                      setModalState(() => _customDateAfterValues[id] = null);
                                                      setState(() => _customDateAfterValues[id] = null);
                                                    },
                                                    child: Icon(Icons.close, size: 18.sp, color: Colors.grey),
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
                            SizedBox(height: 24.h),
                            Row(
                              children: [
                                Expanded(
                                  child: CustomButton(
                                    text: AppLocale.tr('clear_filter'),
                                    onTap: () {
                                      minController.clear();
                                      maxController.clear();
                                      for (final c in _customMinControllers.values) c.clear();
                                      for (final c in _customMaxControllers.values) c.clear();
                                      setState(() {
                                        selectedtype = null;
                                        _filterSubcategoryId = null;
                                        _customSelectValues.clear();
                                        _customDateAfterValues.clear();
                                        for (final k in _customCheckboxValues.keys.toList()) {
                                          _customCheckboxValues[k] = false;
                                        }
                                      });
                                      setModalState(() {
                                        selectedtype = null;
                                        _customSelectValues.clear();
                                        _customDateAfterValues.clear();
                                        for (final k in _customCheckboxValues.keys.toList()) {
                                          _customCheckboxValues[k] = false;
                                        }
                                      });
                                      Navigator.pop(ctx);
                                      _loadCategoryForFilters();
                                      _loadAds();
                                    },
                                    backgroundColor: Colors.grey[200],
                                  ),
                                ),
                                SizedBox(width: 12.w),
                                Expanded(
                                  child: CustomButton(
                                    text: AppLocale.tr('apply_filter'),
                                    onTap: () => _applyFilter(),
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );
  }

  void _applySort() {
    Navigator.pop(context);
    setState(() {});
    _loadAds();
  }

  void _openFilterBySheet(BuildContext context) {
    final sortLabels = [
      AppLocale.tr('sort_date_newest'),
      AppLocale.tr('sort_date_oldest'),
      AppLocale.tr('sort_price_low'),
      AppLocale.tr('sort_price_high'),
    ];
    showModalBottomSheet(
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            return Padding(
              padding: EdgeInsets.all(20.w),
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
                          setModalState(() => selectedfilterIndex = index);
                          setState(() => selectedfilterIndex = index);
                        },
                        child: Container(
                          padding: EdgeInsets.symmetric(vertical: 12.h, horizontal: 16.w),
                          decoration: BoxDecoration(
                            color: selectedfilterIndex == index
                                ? AppColors.darkBlue.withOpacity(0.15)
                                : Colors.grey[200],
                            borderRadius: BorderRadius.circular(8.r),
                          ),
                          child: Row(
                            children: [
                              Icon(
                                selectedfilterIndex == index
                                    ? Icons.radio_button_checked
                                    : Icons.radio_button_off,
                                color: selectedfilterIndex == index
                                    ? AppColors.darkBlue
                                    : Colors.grey[600],
                                size: 22.sp,
                              ),
                              SizedBox(width: 12.w),
                              Text(
                                sortLabels[index],
                                style: TextStyle(
                                  fontSize: 14.sp,
                                  fontWeight: selectedfilterIndex == index
                                      ? FontWeight.w600
                                      : FontWeight.w500,
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
                    onTap: () => _applySort(),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  void _openApperanceSheet(BuildContext context) {
    showModalBottomSheet(
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setModalState) {
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
                        groupValue: selectedApperance,
                        onChanged: (value) {
                          setModalState(() {
                            selectedApperance = value;
                          });
                          setState(() {
                            selectedApperance = value;
                          });
                        },
                      ),
                      RadioListTile<String>(
                        title: Text(AppLocale.tr('list_view')),
                        value: 'list',
                        groupValue: selectedApperance,
                        onChanged: (value) {
                          setModalState(() {
                            selectedApperance = value;
                          });
                          setState(() {
                            selectedApperance = value;
                          });
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
}

class _SearchAdCard extends StatelessWidget {
  final AdModel ad;
  final VoidCallback onTap;

  const _SearchAdCard({required this.ad, required this.onTap});

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
                    color: Colors.grey.shade200,
                    child: (ad.imageUrl != null &&
                            ad.imageUrl!.trim().isNotEmpty)
                        ? ListAdThumbnailImage(
                            imageUrl: ad.imageUrl!,
                            width: 75.w,
                            maxHeight: 75.w,
                            errorBuilder: (_, __) => AppNetworkImage(
                              imageUrl: null,
                              width: 75.w,
                              height: 75.w,
                              borderRadius: BorderRadius.zero,
                            ),
                          )
                        : AppNetworkImage(
                            imageUrl: null,
                            width: 75.w,
                            height: 75.w,
                            borderRadius: BorderRadius.zero,
                          ),
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

}

class FilterWidget extends StatelessWidget {
  final String title;
  final VoidCallback onTap;
  const FilterWidget({super.key, required this.title, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: EdgeInsets.symmetric(horizontal: 10.w, vertical: 5.h),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(8.r),
          // ignore: deprecated_member_use
          color: Colors.white.withOpacity(0.2),
        ),
        child: Row(
          children: [
            Icon(Icons.arrow_drop_down, color: Colors.white),
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

