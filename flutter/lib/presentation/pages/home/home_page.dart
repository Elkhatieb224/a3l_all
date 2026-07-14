import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/performance/startup_warmup.dart';
import 'package:a3lnha/core/storage/token_storage.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/presentation/widgets/shared/warning_confirm_dialog.dart';
import 'package:a3lnha/presentation/widgets/shared/app_network_image.dart';
import 'package:a3lnha/presentation/widgets/shared/favorite_icon_button.dart';
import 'package:a3lnha/data/models/ad_model.dart';
import 'package:a3lnha/data/models/category_model.dart';
import 'package:a3lnha/data/models/subcategory_model.dart';
import 'package:a3lnha/data/services/ad_service.dart';
import 'package:a3lnha/data/services/category_service.dart';
import 'package:a3lnha/data/services/home_service.dart';
import 'package:a3lnha/data/services/notification_service.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/account/favourite_ads_page.dart';
import 'package:a3lnha/presentation/pages/home/ads_list_page.dart';
import 'package:a3lnha/presentation/pages/account/help_page.dart';
import 'package:a3lnha/presentation/pages/account/my_account_page.dart';
import 'package:a3lnha/presentation/pages/auth/login_page.dart';
import 'package:a3lnha/presentation/pages/home/info_about_app_page.dart';
import 'package:a3lnha/presentation/pages/legal/privacy_page.dart';
import 'package:a3lnha/presentation/pages/legal/terms_page.dart';
import 'package:a3lnha/presentation/pages/home/ad_details_page.dart';
import 'package:a3lnha/presentation/pages/home/category_page.dart';
import 'package:a3lnha/presentation/pages/home/notification_page.dart';
import 'package:a3lnha/presentation/pages/home/post_ad_stepper_page.dart';
import 'package:a3lnha/presentation/widgets/ad_list_location_label.dart';
import 'package:a3lnha/presentation/widgets/ad_status_badge_icon.dart';
import 'package:a3lnha/presentation/pages/search/search_page.dart';
import 'package:a3lnha/presentation/pages/search/search_results_page.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_button.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:hexcolor/hexcolor.dart';

class HomePage extends StatefulWidget {
  /// بيانات جُلبت أثناء السبلاش لتفادي طلب `/home` ثانٍ عند أول بناء.
  final HomeData? bootstrapHome;

  const HomePage({super.key, this.bootstrapHome});

  @override
  State<HomePage> createState() => _HomePageState();
}

const _categoryColors = [
  'FFCA57', 'FF7D7D', '84FF9B', '4A90E2', '7B61FF',
  '2ECC71', 'FFA500', 'E74C3C', '1ABC9C', 'F1C40F',
];

class _HomePageState extends State<HomePage> with TickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<Offset> _drawerSlide;
  final TextEditingController _searchController = TextEditingController();
  final FocusNode _searchFocusNode = FocusNode();
  List<CategoryModel> _categories = [];
  bool _categoriesLoading = true;
  List<AdModel> _featuredAds = [];
  List<AdModel> _urgentAds = [];
  List<AdModel> _latestAds = [];
  bool _featuredLoading = true;
  bool _urgentLoading = true;
  bool _latestLoading = true;
  int _unreadNotificationCount = 0;
  bool _warmupScheduled = false;

  CategoryModel _withSubcategories(CategoryModel cat, List<SubcategoryModel> subcats) {
    return CategoryModel(
      id: cat.id,
      name: cat.name,
      nameAr: cat.nameAr,
      nameEn: cat.nameEn,
      nameTr: cat.nameTr,
      icon: cat.icon,
      image: cat.image,
      isActive: cat.isActive,
      subcategoriesCount: cat.subcategoriesCount,
      adsCount: cat.adsCount,
      subcategories: subcats,
      customFields: cat.customFields,
      adImagesMode: cat.adImagesMode,
      adImagesMax: cat.adImagesMax,
      adGalleryPaths: cat.adGalleryPaths,
      adGalleryUrls: cat.adGalleryUrls,
    );
  }

  Future<void> _ensureCategorySubcategoriesLoaded() async {
    if (_categories.isEmpty) return;
    var changed = false;
    final updated = <CategoryModel>[];
    for (final cat in _categories) {
      var subcats = cat.subcategories ?? const <SubcategoryModel>[];
      if (subcats.isEmpty) {
        final fetched = await CategoryService.getSubcategories(
          cat.id,
          forceRefresh: true,
        );
        if (fetched.isNotEmpty) {
          subcats = fetched;
          changed = true;
        }
      }
      updated.add(_withSubcategories(cat, subcats));
    }
    if (!mounted || !changed) return;
    setState(() => _categories = updated);
    await CategoryService.seedCategoriesCache(_categories);
  }

  @override
  void initState() {
    super.initState();
    // عند جلب /home من السبلاش لا نعرض كاشاً قديماً (إعلانات/فئات) قبل بيانات bootstrap.
    _applyCacheToState(
      skipHomeAdRows: widget.bootstrapHome != null,
      skipCategoryCache: widget.bootstrapHome != null,
    );
    Future<void>.microtask(_ensureCategorySubcategoriesLoaded);
    if (widget.bootstrapHome != null) {
      _applyBootstrapHome(widget.bootstrapHome!);
    } else {
      _loadHomePrimary();
    }
    if (TokenStorage.hasToken()) _loadUnreadNotificationCount();
    _controller = AnimationController(
      vsync: this,
      duration: Duration(milliseconds: 300),
    );

    _drawerSlide = Tween<Offset>(
      begin: Offset(2.0, 0.0), // completely offscreen to the left
      end: Offset(0.0, 0.0), // fully visible
    ).animate(CurvedAnimation(parent: _controller, curve: Curves.easeInOut));
  }

  void _applyCacheToState({
    bool skipHomeAdRows = false,
    bool skipCategoryCache = false,
  }) {
    var updated = false;
    if (!skipCategoryCache) {
      final cachedCategories = CategoryService.getCachedCategories();
      if (cachedCategories != null && cachedCategories.isNotEmpty) {
        _categories = cachedCategories;
        _categoriesLoading = false;
        updated = true;
      }
    }
    if (!skipHomeAdRows) {
      final cachedFeatured = AdService.getCachedFeaturedAds();
      final cachedLatest = AdService.getCachedLatestAds();
      if (cachedFeatured != null && cachedFeatured.ads.isNotEmpty) {
        _featuredAds = cachedFeatured.ads;
        _featuredLoading = false;
        updated = true;
      }
      if (cachedLatest != null && cachedLatest.ads.isNotEmpty) {
        _latestAds = cachedLatest.ads;
        _latestLoading = false;
        updated = true;
      }
      final cachedUrgent = AdService.getCachedUrgentAds();
      if (cachedUrgent != null && cachedUrgent.ads.isNotEmpty) {
        _urgentAds = cachedUrgent.ads;
        _urgentLoading = false;
        updated = true;
      }
    }
    if (updated) {
      setState(() {});
      _scheduleWarmupAfterVisible();
    }
  }

  void _scheduleWarmupAfterVisible() {
    if (_warmupScheduled) return;
    _warmupScheduled = true;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      StartupWarmup.runAfterHomeVisible();
    });
  }

  void _applyBootstrapHome(HomeData h) {
    setState(() {
      if (h.categories.isNotEmpty || _categories.isEmpty) {
        _categories = h.categories;
      }
      if (h.featuredAds.isNotEmpty || _featuredAds.isEmpty) {
        _featuredAds = h.featuredAds;
      }
      if (h.urgentAds.isNotEmpty || _urgentAds.isEmpty) {
        _urgentAds = h.urgentAds;
      }
      if (h.latestAds.isNotEmpty || _latestAds.isEmpty) {
        _latestAds = h.latestAds;
      }
      _categoriesLoading = false;
      _featuredLoading = false;
      _urgentLoading = false;
      _latestLoading = false;
    });
    Future<void>.microtask(_ensureCategorySubcategoriesLoaded);
    _scheduleWarmupAfterVisible();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Future<void>.microtask(() async {
        try {
          await Future.wait<void>([
            CategoryService.seedCategoriesCache(_categories),
            AdService.seedHomeSnapshots(
              featuredAds: _featuredAds,
              urgentAds: _urgentAds,
              latestAds: _latestAds,
            ),
          ]);
        } catch (_) {}
      });
    });
  }

  Future<void> _loadHomePrimary() async {
    try {
      final home = await HomeService.getHome();
      if (mounted) {
        setState(() {
          if (home.categories.isNotEmpty || _categories.isEmpty) _categories = home.categories;
          if (home.featuredAds.isNotEmpty || _featuredAds.isEmpty) _featuredAds = home.featuredAds;
          if (home.urgentAds.isNotEmpty || _urgentAds.isEmpty) _urgentAds = home.urgentAds;
          if (home.latestAds.isNotEmpty || _latestAds.isEmpty) _latestAds = home.latestAds;
          _categoriesLoading = false;
          _featuredLoading = false;
          _urgentLoading = false;
          _latestLoading = false;
        });
        Future<void>.microtask(_ensureCategorySubcategoriesLoaded);
        _scheduleWarmupAfterVisible();

        // بعد أن يرى المستخدم البيانات، نبدأ حفظها في الكاش بهدوء (بدون حجب الـ UI)
        WidgetsBinding.instance.addPostFrameCallback((_) {
          Future.microtask(() async {
            await Future.wait<void>([
              CategoryService.seedCategoriesCache(_categories),
              AdService.seedHomeSnapshots(
                featuredAds: _featuredAds,
                urgentAds: _urgentAds,
                latestAds: _latestAds,
              ),
            ]);
          });
        });
      }
    } catch (_) {
      if (mounted) {
        setState(() {
          _categoriesLoading = false;
          _featuredLoading = false;
          _urgentLoading = false;
          _latestLoading = false;
        });
      }
    }
  }

  Future<void> _loadUnreadNotificationCount() async {
    if (!TokenStorage.hasToken()) return;
    final count = await NotificationService.getUnreadCount();
    if (mounted) setState(() => _unreadNotificationCount = count);
  }

  void _toggleDrawer() {
    if (_controller.isCompleted) {
      _controller.reverse();
    } else {
      _controller.forward();
    }
  }

  void _dismissKeyboard() {
    final focus = FocusManager.instance.primaryFocus;
    if (focus != null && !focus.hasPrimaryFocus) {
      focus.unfocus();
      return;
    }
    FocusManager.instance.primaryFocus?.unfocus();
  }

  void _performSearch() {
    _dismissKeyboard();
    final query = _searchController.text.trim();
    if (kDebugMode) {
      debugPrint('[HomeSearch] _performSearch query="$query" length=${query.length}');
    }
    if (query.isEmpty) {
      context.push(SearchPage());
      return;
    }
    if (query.length < AdService.minSearchLength) {
      showToast(message: AppLocale.tr('search_min_chars'));
      return;
    }
    if (kDebugMode) {
      debugPrint('[HomeSearch] navigating to SearchResultsPage query="$query"');
    }
    context.push(SearchResultsPage(initialQuery: query));
  }

  @override
  void dispose() {
    _searchFocusNode.dispose();
    _searchController.dispose();
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (bool didPop, dynamic result) async {
        if (didPop) return;
        final shouldExit = await WarningConfirmDialog.show(
          context,
          title: AppLocale.tr('exit_confirm_title'),
          message: AppLocale.tr('exit_app'),
          confirmText: AppLocale.tr('exit_confirm_ok'),
          cancelText: AppLocale.tr('back'),
        );
        if (shouldExit && context.mounted) {
          SystemNavigator.pop();
        }
      },
      child: Scaffold(
        backgroundColor: HexColor('F5F6F7'),
        appBar: AppBar(
          backgroundColor: AppColors.darkBlue,
          elevation: 0,
          scrolledUnderElevation: 0,
          automaticallyImplyLeading: false,
          leadingWidth: 0,
          leading: const SizedBox.shrink(),
          titleSpacing: 0,
          title: Directionality(
            textDirection: TextDirection.ltr,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                IconButton(
                  padding: EdgeInsets.zero,
                  visualDensity: VisualDensity.compact,
                  constraints: BoxConstraints.tight(Size(48.w, kToolbarHeight)),
                  onPressed: () {
                    if (TokenStorage.hasToken()) {
                      context.push(MyAccountPage());
                    } else {
                      context.push(LoginPage());
                    }
                  },
                  icon: Icon(
                    Icons.person_outline,
                    color: Colors.white,
                    size: 26.sp,
                  ),
                ),
                IconButton(
                  padding: EdgeInsets.zero,
                  visualDensity: VisualDensity.compact,
                  constraints: BoxConstraints.tight(Size(48.w, kToolbarHeight)),
                  onPressed: () async {
                    await context.push(NotificationPage());
                    if (mounted) _loadUnreadNotificationCount();
                  },
                  icon: Stack(
                    clipBehavior: Clip.none,
                    alignment: Alignment.center,
                    children: [
                      Icon(
                        Icons.notifications_outlined,
                        color: Colors.white,
                        size: 26.sp,
                      ),
                      if (_unreadNotificationCount > 0)
                        Positioned(
                          top: -2,
                          right: -2,
                          child: Container(
                            padding: EdgeInsets.symmetric(
                              horizontal: _unreadNotificationCount > 9 ? 3.w : 4.w,
                              vertical: 2.h,
                            ),
                            constraints: BoxConstraints(
                              minWidth: 16.w,
                              minHeight: 16.w,
                            ),
                            decoration: BoxDecoration(
                              color: Colors.red,
                              shape: BoxShape.circle,
                              border: Border.all(color: AppColors.darkBlue, width: 1.2),
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.black26,
                                  blurRadius: 2,
                                  offset: const Offset(0, 1),
                                ),
                              ],
                            ),
                            child: Center(
                              child: Text(
                                _unreadNotificationCount > 99
                                    ? '99+'
                                    : '$_unreadNotificationCount',
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 9.sp,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ),
                          ),
                        ),
                    ],
                  ),
                ),
                GestureDetector(
                  behavior: HitTestBehavior.opaque,
                  onTap: () {
                    if (TokenStorage.hasToken()) {
                      context.push(PostAdStepperPage(title: AppLocale.tr('create_ad')));
                    } else {
                      context.push(LoginPage());
                    }
                  },
                  child: Padding(
                    padding: EdgeInsets.symmetric(horizontal: 4.w, vertical: 8.h),
                    child: Image.asset(
                      "assets/images/مايك 1.png",
                      width: 55.w,
                      height: 44.h,
                    ),
                  ),
                ),
                IconButton(
                  padding: EdgeInsets.zero,
                  visualDensity: VisualDensity.compact,
                  constraints: BoxConstraints.tight(Size(48.w, kToolbarHeight)),
                  onPressed: () {
                    context.push(HelpPage());
                  },
                  icon: Icon(
                    Icons.help_outline,
                    color: Colors.white,
                    size: 26.sp,
                  ),
                ),
                IconButton(
                  padding: EdgeInsets.zero,
                  visualDensity: VisualDensity.compact,
                  constraints: BoxConstraints.tight(Size(48.w, kToolbarHeight)),
                  onPressed: _toggleDrawer,
                  icon: Icon(
                    Icons.menu,
                    color: Colors.white,
                    size: 26.sp,
                  ),
                ),
              ],
            ),
          ),
        ),

        body: Listener(
          behavior: HitTestBehavior.translucent,
          onPointerDown: (_) => _dismissKeyboard(),
          child: Stack(
            children: [
            Positioned.fill(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Padding(
                    padding: EdgeInsets.fromLTRB(0, 0, 0, 10.h),
                    child: Stack(
                      children: [
                        Positioned(
                          top: 0,
                          left: 0,
                          right: 0,
                          height: 135.h,
                          child: Container(
                            decoration: BoxDecoration(
                              color: AppColors.darkBlue,
                            ),
                          ),
                        ),
                        Container(
                          margin: EdgeInsets.only(top: 6.h, left: 20.w, right: 20.w),
                          width: double.infinity,
                          decoration: BoxDecoration(
                            color: Colors.white,
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withOpacity(0.1),
                                blurRadius: 10,
                                offset: Offset(0, 4),
                              ),
                            ],
                            borderRadius: BorderRadius.circular(24.r),
                          ),
                          child: Padding(
                            padding: EdgeInsets.fromLTRB(20.w, 16.h, 20.w, 20.h),
                            child: Column(
                              mainAxisSize: MainAxisSize.min,
                              crossAxisAlignment: CrossAxisAlignment.stretch,
                              children: [
                                TextFormField(
                                  controller: _searchController,
                                  focusNode: _searchFocusNode,
                                  onTapOutside: (_) => _dismissKeyboard(),
                                  decoration: InputDecoration(
                                    prefixIcon: Image.asset(
                                      "assets/images/Icon Left.png",
                                      width: 20.w,
                                      height: 20.h,
                                    ),
                                    hintText: AppLocale.tr('search_placeholder'),
                                    hintStyle: TextStyle(
                                      fontSize: 14.sp,
                                      color: Colors.grey,
                                    ),
                                    border: OutlineInputBorder(
                                      borderRadius: BorderRadius.circular(8.r),
                                      borderSide: BorderSide(
                                        color: Colors.grey.shade300,
                                      ),
                                    ),
                                    contentPadding: EdgeInsets.symmetric(
                                      horizontal: 16.w,
                                      vertical: 0.h,
                                    ),
                                  ),
                                  onFieldSubmitted: (_) => _performSearch(),
                                  onEditingComplete: _dismissKeyboard,
                                  textInputAction: TextInputAction.search,
                                ),
                                SizedBox(height: 12.h),
                                CustomButton(
                                  text: AppLocale.tr('search'),
                                  onTap: _performSearch,
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  Expanded(
                    child: Container(
                      constraints: BoxConstraints(
                        minHeight: MediaQuery.sizeOf(context).height * 0.4,
                      ),
                      color: HexColor('F5F6F7'),
                      child: Padding(
                        padding: EdgeInsets.symmetric(
                          horizontal: 20.w,
                          vertical: 0.h,
                        ),
                        child: SingleChildScrollView(
                          physics: BouncingScrollPhysics(),
                          keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
                          child: Column(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                            _categoriesLoading
                                ? Padding(
                                    padding: EdgeInsets.all(32.w),
                                    child: Center(
                                      child: CircularProgressIndicator(
                                        color: AppColors.darkBlue,
                                      ),
                                    ),
                                  )
                                : _categories.isEmpty
                                    ? Padding(
                                        padding: EdgeInsets.all(32.w),
                                        child: Center(
                                          child: Text(
                                            AppLocale.tr('no_categories'),
                                            style: TextStyle(
                                              fontSize: 14.sp,
                                              color: Colors.grey,
                                            ),
                                          ),
                                        ),
                                      )
                                    : ListView.builder(
                                        shrinkWrap: true,
                                        physics: BouncingScrollPhysics(),
                                        keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
                                        itemCount: _categories.length,
                                        itemBuilder: (context, index) {
                                          final cat = _categories[index];
                                          final subNames = cat.subcategories
                                                  ?.map((s) => s.displayName)
                                                  .join('، ') ??
                                              '';
                                          final subTitle = subNames.isNotEmpty
                                              ? subNames
                                              : AppLocale.tr('no_subcategories');
                                          return DrawerRow(
                                            isPostAd: false,
                                            categoryId: cat.id,
                                            title: cat.displayName,
                                            subTitle: subTitle,
                                            imageUrl: cat.displayIconUrl,
                                            backgroundColor:
                                                _categoryColors[
                                                    index % _categoryColors.length],
                                          );
                                        },
                                      ),
                            SeeMoreWidget(
                              title: AppLocale.tr('featured_ads'),
                              iconWidget: AdStatusBadgeIcon.featured(size: 20.sp),
                              iconBgColor: Colors.amber.shade100,
                              titleColor: AppColors.darkBlue,
                              onSeeAllTap: () => context.push(
                                AdsListPage(
                                  title: AppLocale.tr('featured_ads'),
                                  featured: true,
                                ),
                              ),
                            ),
                            SizedBox(height: 10.h),
                            _featuredLoading
                                ? SizedBox(
                                    height: 200.h,
                                    child: Center(
                                      child: CircularProgressIndicator(
                                        color: AppColors.darkBlue,
                                      ),
                                    ),
                                  )
                                : _featuredAds.isEmpty
                                    ? SizedBox(
                                        height: 80.h,
                                        child: Center(
                                          child: Text(
                                            AppLocale.tr('no_featured_ads'),
                                            style: TextStyle(
                                              fontSize: 14.sp,
                                              color: Colors.grey,
                                            ),
                                          ),
                                        ),
                                      )
                                    : SizedBox(
                                        height: 192.h,
                                        child: LayoutBuilder(
                                          builder: (context, constraints) {
                                            const spacing = 12.0;
                                            return ListView.separated(
                                              scrollDirection: Axis.horizontal,
                                              padding: EdgeInsets.zero,
                                              itemCount: _featuredAds.length,
                                              separatorBuilder: (_, __) =>
                                                  const SizedBox(width: spacing),
                                              itemBuilder: (context, index) {
                                                return SizedBox(
                                                  width: 132.w,
                                                  child: SpecialAdWidget(
                                                    ad: _featuredAds[index],
                                                    showFeaturedBadge: true,
                                                  ),
                                                );
                                              },
                                            );
                                          },
                                        ),
                                      ),
                            SizedBox(height: 18.h),
                            SeeMoreWidget(
                              title: AppLocale.tr('urgent_ads_label'),
                              iconWidget: AdStatusBadgeIcon.urgent(size: 20.sp),
                              iconBgColor: Colors.red.shade100,
                              titleColor: Colors.red.shade700,
                              onSeeAllTap: () => context.push(
                                AdsListPage(
                                  title: AppLocale.tr('urgent_ads_label'),
                                  urgent: true,
                                ),
                              ),
                            ),
                            SizedBox(height: 10.h),
                            _urgentLoading
                                ? SizedBox(
                                    height: 200.h,
                                    child: Center(
                                      child: CircularProgressIndicator(
                                        color: AppColors.darkBlue,
                                      ),
                                    ),
                                  )
                                : _urgentAds.isEmpty
                                    ? SizedBox(
                                        height: 80.h,
                                        child: Center(
                                          child: Text(
                                            AppLocale.tr('no_urgent_ads'),
                                            style: TextStyle(
                                              fontSize: 14.sp,
                                              color: Colors.grey,
                                            ),
                                          ),
                                        ),
                                      )
                                    : SizedBox(
                                        height: 192.h,
                                        child: LayoutBuilder(
                                          builder: (context, constraints) {
                                            const spacing = 12.0;
                                            return ListView.separated(
                                              scrollDirection: Axis.horizontal,
                                              padding: EdgeInsets.zero,
                                              itemCount: _urgentAds.length,
                                              separatorBuilder: (_, __) =>
                                                  const SizedBox(width: spacing),
                                              itemBuilder: (context, index) {
                                                return SizedBox(
                                                  width: 132.w,
                                                  child: SpecialAdWidget(
                                                    ad: _urgentAds[index],
                                                    showUrgentBadge: true,
                                                  ),
                                                );
                                              },
                                            );
                                          },
                                        ),
                                      ),
                            SeeMoreWidget(
                              title: AppLocale.tr('latest_ads'),
                              onSeeAllTap: () => context.push(
                                AdsListPage(title: AppLocale.tr('latest_ads')),
                              ),
                            ),
                            _latestLoading
                                ? SizedBox(
                                    height: 150.h,
                                    child: Center(
                                      child: CircularProgressIndicator(
                                        color: AppColors.darkBlue,
                                      ),
                                    ),
                                  )
                                : _latestAds.isEmpty
                                    ? SizedBox(
                                        height: 80.h,
                                        child: Center(
                                          child: Text(
                                            AppLocale.tr('no_ads'),
                                            style: TextStyle(
                                              fontSize: 14.sp,
                                              color: Colors.grey,
                                            ),
                                          ),
                                        ),
                                      )
                                    : ListView.builder(
                                        itemBuilder: (context, index) {
                                          return NearbyAdWidget(
                                            ad: _latestAds[index],
                                          );
                                        },
                                        itemCount: _latestAds.length,
                                        shrinkWrap: true,
                                        physics: NeverScrollableScrollPhysics(),
                                      ),
                            // TODO: تفعيل لاحقاً
                            // SeeMoreWidget(title: "إعلانات قريبة من موقعك"),
                            // ListView.builder(
                            //   itemBuilder: (context, index) {
                            //     return NearbyAdWidget();
                            //   },
                            //   itemCount: 2,
                            //   shrinkWrap: true,
                            //   physics: NeverScrollableScrollPhysics(),
                            // ),
                            // SeeMoreWidget(title: "أخر اعلانات قمت بزيارتها"),
                            // ListView.builder(
                            //   itemBuilder: (context, index) {
                            //     return NearbyAdWidget();
                            //   },
                            //   itemCount: 2,
                            //   shrinkWrap: true,
                            //   physics: NeverScrollableScrollPhysics(),
                            // ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
              MyDrawerWidget(
                drawerSlide: _drawerSlide,
                categories: _categories,
                categoriesLoading: _categoriesLoading,
                onDismiss: () => _controller.reverse(),
              ),
            ],
          ),
        ),
        floatingActionButton: FloatingActionButton(
          onPressed: () {
            if (TokenStorage.hasToken()) {
              context.push(PostAdStepperPage(title: AppLocale.tr('create_ad')));
            } else {
              context.push(LoginPage());
            }
          },
          backgroundColor: AppColors.darkBlue,
          child: Icon(Icons.add, color: Colors.white, size: 28.sp),
        ),
        floatingActionButtonLocation: FloatingActionButtonLocation.endFloat,
      ),
    );
  }
}

class MyDrawerWidget extends StatelessWidget {
  const MyDrawerWidget({
    super.key,
    required Animation<Offset> drawerSlide,
    required List<CategoryModel> categories,
    required bool categoriesLoading,
    required VoidCallback onDismiss,
  })  : _drawerSlide = drawerSlide,
        _categories = categories,
        _categoriesLoading = categoriesLoading,
        _onDismiss = onDismiss;

  final Animation<Offset> _drawerSlide;
  final List<CategoryModel> _categories;
  final bool _categoriesLoading;
  final VoidCallback _onDismiss;

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _drawerSlide,
      builder: (context, child) {
        final isOpen = _drawerSlide.value.dx < 2.0;
        if (!isOpen) {
          return Positioned.fill(child: const SizedBox.shrink());
        }
        return Positioned(
          top: kToolbarHeight - 65.h,
          bottom: 0,
          left: 0,
          right: 0,
          child: GestureDetector(
            behavior: HitTestBehavior.opaque,
            onTap: _onDismiss,
            child: Align(
              alignment: Alignment.centerRight,
              child: SizedBox(
                width: MediaQuery.sizeOf(context).width / 1.2,
                child: SlideTransition(
                  position: _drawerSlide,
                  child: Material(
                    elevation: 8,
                    color: Colors.white,
                    child: Column(
                      children: [
                        Container(
                          padding: EdgeInsets.all(16.w),
                          width: double.infinity,
                          color: AppColors.darkBlue,
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                AppLocale.tr('aalenha'),
                                style: TextStyle(
                                  fontSize: 16.sp,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.white,
                                ),
                              ),
                              SizedBox(height: 10.h),
                              DrawerHeaderRow(
                                title: TokenStorage.hasToken()
                                    ? AppLocale.tr('my_account')
                                    : AppLocale.tr('login'),
                                imagePath: "assets/images/Cart.png",
                                onTap: () {
                                  FocusManager.instance.primaryFocus?.unfocus();
                                  if (TokenStorage.hasToken()) {
                                    context.push(MyAccountPage());
                                  } else {
                                    context.push(LoginPage());
                                  }
                                },
                              ),
                              SizedBox(height: 12.h),
                              DrawerHeaderRow(
                                title: AppLocale.tr('post_ad'),
                                imagePath: "assets/images/مايك 1.png",
                                onTap: () {
                                  FocusManager.instance.primaryFocus?.unfocus();
                                  if (TokenStorage.hasToken()) {
                                    context.push(PostAdStepperPage(title: AppLocale.tr('create_ad')));
                                  } else {
                                    context.push(LoginPage());
                                  }
                                },
                              ),
                              SizedBox(height: 12.h),
                              DrawerHeaderRow(
                                title: AppLocale.tr('app_info'),
                                imagePath: "assets/images/ix_support.png",
                                onTap: () {
                                  FocusManager.instance.primaryFocus?.unfocus();
                                  context.push(InfoAboutAppPage());
                                },
                              ),
                              DrawerHeaderRow(
                                title: AppLocale.tr('terms_conditions'),
                                imagePath: "assets/images/ix_support.png",
                                onTap: () {
                                  FocusManager.instance.primaryFocus?.unfocus();
                                  context.push(TermsPage());
                                },
                              ),
                              DrawerHeaderRow(
                                title: AppLocale.tr('privacy_policy'),
                                imagePath: "assets/images/ix_support.png",
                                onTap: () {
                                  FocusManager.instance.primaryFocus?.unfocus();
                                  context.push(PrivacyPage());
                                },
                              ),
                            ],
                          ),
                        ),
                        DrawerContent(
                          categories: _categories,
                          categoriesLoading: _categoriesLoading,
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ),
        );
      },
    );
  }
}

class DrawerContent extends StatelessWidget {
  final bool isPostAd;
  final List<CategoryModel> categories;
  final bool categoriesLoading;

  const DrawerContent({
    super.key,
    this.isPostAd = false,
    this.categories = const [],
    this.categoriesLoading = false,
  });

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Padding(
        padding: EdgeInsets.symmetric(horizontal: 15.w),
        child: categoriesLoading
            ? Center(
                child: Padding(
                  padding: EdgeInsets.all(32.w),
                  child: CircularProgressIndicator(color: AppColors.darkBlue),
                ),
              )
            : categories.isEmpty
                ? Center(
                    child: Text(
                      AppLocale.tr('no_categories'),
                      style: TextStyle(fontSize: 14.sp, color: Colors.grey),
                    ),
                  )
                : ListView.builder(
                    physics: BouncingScrollPhysics(),
                    keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
                    itemCount: categories.length,
                    itemBuilder: (context, index) {
                      final cat = categories[index];
                      final subNames = cat.subcategories
                              ?.map((s) => s.displayName)
                              .join('، ') ??
                          '';
                      final subTitle =
                          subNames.isNotEmpty ? subNames : AppLocale.tr('no_subcategories');
                      return DrawerRow(
                        isPostAd: isPostAd,
                        categoryId: cat.id,
                        title: cat.displayName,
                        subTitle: subTitle,
                        imageUrl: cat.displayIconUrl,
                        backgroundColor:
                            _categoryColors[index % _categoryColors.length],
                      );
                    },
                  ),
      ),
    );
  }
}

class DrawerRow extends StatelessWidget {
  final String title;
  final String subTitle;
  final String? imageUrl;
  final String backgroundColor;
  final bool isPostAd;
  final int? categoryId;

  const DrawerRow({
    super.key,
    required this.title,
    required this.subTitle,
    this.imageUrl,
    required this.backgroundColor,
    this.isPostAd = false,
    this.categoryId,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: () {
        FocusManager.instance.primaryFocus?.unfocus();
        if (isPostAd) {
          context.push(PostAdStepperPage(
            title: title,
            initialCategoryId: categoryId,
            initialCategoryName: title,
          ));
        } else if (categoryId != null) {
          context.push(CategoryPage(
            categoryId: categoryId!,
            categoryName: title,
          ));
        }
      },
      child: Padding(
        padding: EdgeInsets.symmetric(vertical: 10.h),
        child: Column(
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Row(
                    children: [
                      CircleAvatar(
                        radius: 20.r,
                        backgroundColor: HexColor(backgroundColor),
                        child: imageUrl != null &&
                                imageUrl!.trim().isNotEmpty &&
                                (imageUrl!.startsWith('http://') ||
                                    imageUrl!.startsWith('https://'))
                            ? ClipRRect(
                                borderRadius: BorderRadius.circular(20.r),
                                child: Padding(
                                  padding: EdgeInsets.all(4.r),
                                  child: CachedUrlImage(
                                    imageUrl: imageUrl!,
                                    width: 40.w,
                                    height: 40.h,
                                    fit: BoxFit.contain,
                                    errorBuilder: (_, __) => Icon(
                                      Icons.folder_outlined,
                                      size: 24.sp,
                                      color: Colors.white,
                                    ),
                                  ),
                                ),
                              )
                            : Icon(
                                Icons.folder_outlined,
                                size: 24.sp,
                                color: Colors.white,
                              ),
                      ),
                      SizedBox(width: 20.w),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              title,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(
                                fontSize: 12.sp,
                                fontWeight: FontWeight.w500,
                                color: Colors.black,
                              ),
                            ),
                            SizedBox(height: 5.h),
                            Text(
                              subTitle,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(
                                fontSize: 10.sp,
                                fontWeight: FontWeight.w400,
                                color: Colors.grey,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
                Icon(
                  Icons.arrow_forward_ios,
                  color: AppColors.darkBlue,
                  size: 12.sp,
                ),
              ],
            ),
            SizedBox(height: 15.w),
            Container(
              width: double.infinity,
              height: 1.5.h,
              // ignore: deprecated_member_use
              color: Colors.grey.withOpacity(0.5),
            ),
          ],
        ),
      ),
    );
  }
}

class DrawerHeaderRow extends StatelessWidget {
  final String title;
  final String imagePath;
  final VoidCallback onTap;
  const DrawerHeaderRow({
    super.key,
    required this.title,
    required this.imagePath,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: onTap,
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            children: [
              Image.asset(imagePath, width: 40.w, height: 40.h),
              SizedBox(width: 10.w),
              Text(
                title,
                style: TextStyle(
                  fontSize: 12.sp,
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
                ),
              ),
            ],
          ),
          IconButton(
            onPressed: onTap,
            icon: Icon(
              Icons.arrow_forward_ios,
              color: Colors.white,
              size: 16.sp,
            ),
          ),
        ],
      ),
    );
  }
}

class NearbyAdWidget extends StatelessWidget {
  final AdModel ad;

  const NearbyAdWidget({super.key, required this.ad});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: () {
        context.push(AdDetailsPage(adUid: ad.uid));
      },
      child: Container(
        padding: EdgeInsets.all(16.w),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(8.r),
        ),
        margin: EdgeInsets.only(bottom: 16.h),
        child: Row(
          children: [
            Stack(
              clipBehavior: Clip.none,
              children: [
                Container(
                  margin: EdgeInsets.only(left: 10.w),
                  clipBehavior: Clip.antiAlias,
                  decoration: BoxDecoration(
                    color: Colors.grey.shade200,
                    borderRadius: BorderRadius.circular(8.r),
                  ),
                  child: ad.imageUrl != null && ad.imageUrl!.trim().isNotEmpty
                      ? ListAdThumbnailImage(
                          imageUrl: ad.imageUrl!,
                          width: 75.w,
                          maxHeight: 75.w,
                        )
                      : AppNetworkImage(
                          imageUrl: null,
                          width: 75.w,
                          height: 75.w,
                          borderRadius: BorderRadius.zero,
                        ),
                ),
                if (ad.isFeatured)
                  Positioned(
                    top: 2.h,
                    left: 10.w,
                    child: AdStatusBadgeIcon.featured(size: 22.sp),
                  ),
              ],
            ),
            Expanded(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.start,
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
                          iconSize: 16,
                          style: TextStyle(fontSize: 12.sp, color: Colors.grey),
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

class LocationWidget extends StatelessWidget {
  final bool isTime;
  final String? location;

  const LocationWidget({
    super.key,
    this.isTime = false,
    this.location,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(
          isTime ? Icons.access_time : Icons.location_on,
          size: 16.sp,
          color: Colors.grey[600],
        ),
        SizedBox(width: 4.w),
        Expanded(
          child: Text(
            isTime ? "منذ ساعة" : (location ?? "—"),
            style: TextStyle(fontSize: 12.sp, color: Colors.grey),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ),
      ],
    );
  }
}

class SpecialAdWidget extends StatefulWidget {
  final AdModel ad;
  /// عرض علامة التميز (تاج) للإعلان المميز
  final bool showFeaturedBadge;
  /// عرض علامة العاجل للإعلان العاجل
  final bool showUrgentBadge;

  const SpecialAdWidget({
    super.key,
    required this.ad,
    this.showFeaturedBadge = false,
    this.showUrgentBadge = false,
  });

  @override
  State<SpecialAdWidget> createState() => _SpecialAdWidgetState();
}

class _SpecialAdWidgetState extends State<SpecialAdWidget> {
  @override
  Widget build(BuildContext context) {
    final ad = widget.ad;
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: () {
        context.push(AdDetailsPage(adUid: ad.uid));
      },
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
                    child: AppNetworkImage(
                      imageUrl: ad.imageUrl,
                      width: 132.w,
                      height: 92.h,
                      fit: BoxFit.contain,
                      borderRadius: BorderRadius.zero,
                    ),
                  ),
                ),
                if (widget.showFeaturedBadge || ad.isFeatured)
                  Positioned(
                    top: 4.h,
                    left: 4.w,
                    child: AdStatusBadgeIcon.featured(size: 20.sp),
                  ),
                if (widget.showUrgentBadge || ad.isUrgent)
                  Positioned(
                    top: 4.h,
                    left: (widget.showFeaturedBadge || ad.isFeatured) ? 26.w : 4.w,
                    child: AdStatusBadgeIcon.urgent(size: 20.sp),
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
    );
  }
}

class SeeMoreWidget extends StatelessWidget {
  final String title;
  final IconData? icon;
  final Widget? iconWidget;
  final Color? iconBgColor;
  final Color? iconColor;
  final Color? titleColor;
  final VoidCallback? onSeeAllTap;

  const SeeMoreWidget({
    super.key,
    required this.title,
    this.icon,
    this.iconWidget,
    this.iconBgColor,
    this.iconColor,
    this.titleColor,
    this.onSeeAllTap,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Expanded(
          child: Row(
            children: [
              if (iconWidget != null) ...[
                SizedBox(
                  width: 24.w,
                  height: 24.w,
                  child: Center(child: iconWidget),
                ),
                SizedBox(width: 8.w),
              ] else if (icon != null) ...[
                Container(
                  width: 34.w,
                  height: 34.w,
                  decoration: BoxDecoration(
                    color: iconBgColor ?? Colors.grey.shade200,
                    shape: BoxShape.circle,
                  ),
                  child: Icon(
                    icon,
                    size: 20.sp,
                    color: iconColor ?? AppColors.darkBlue,
                  ),
                ),
                SizedBox(width: 8.w),
              ],
              Expanded(
                child: Text(
                  title,
                  style: TextStyle(
                    fontSize: 18.sp,
                    fontWeight: FontWeight.w700,
                    color: titleColor ?? Colors.black,
                  ),
                ),
              ),
            ],
          ),
        ),
        GestureDetector(
          behavior: HitTestBehavior.opaque,
          onTap: onSeeAllTap ??
              () => context.push(FavouriteAdsPage(title: title)),
          child: Row(
            children: [
              Text(
                AppLocale.tr('see_all'),
                style: TextStyle(color: AppColors.darkBlue, fontSize: 12.sp),
              ),
              SizedBox(width: 5.w),
              Image.asset(
                "assets/images/arrow-right-01.png",
                width: 16.w,
                height: 16.h,
              ),
            ],
          ),
        ),
      ],
    );
  }
}
