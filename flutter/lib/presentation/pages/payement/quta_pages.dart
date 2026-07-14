import 'package:a3lnha/core/data/currency_helper.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/core/storage/token_storage.dart';
import 'package:a3lnha/data/models/package_model.dart';
import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/data/services/package_service.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/auth/login_page.dart';
import 'package:a3lnha/presentation/pages/home/post_ad_stepper_page.dart';
import 'package:a3lnha/presentation/pages/payement/hewala_page.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/current_plan_card.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class QutaPages extends StatefulWidget {
  const QutaPages({super.key});

  @override
  State<QutaPages> createState() => _QutaPagesState();
}

class _QutaPagesState extends State<QutaPages> {
  List<PackageModel> _packages = [];
  List<CreditBatchInfo> _creditBatches = [];
  bool _loading = true;

  CurrentPlanInfo? _currentPlan;

  @override
  void initState() {
    super.initState();
    final cached = PackageService.getCachedPackages();
    if (cached != null && cached.packages.isNotEmpty) {
      _packages = cached.packages;
      _creditBatches = cached.creditBatches;
      _currentPlan = cached.currentPlan;
      _loading = false;
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) setState(() {});
      });
    }
    _loadPackages();
  }

  Future<void> _loadPackages() async {
    final hadData = _packages.isNotEmpty;
    if (!hadData && mounted) setState(() => _loading = true);
    try {
      // جلب بيانات جديدة من الخادم عند فتح الصفحة لضمان ظهور الباقات المفعلة في الموقع
      final res = await PackageService.getPackages(forceRefresh: true);
      if (mounted) {
        setState(() {
          _packages = res.packages;
          _creditBatches = res.creditBatches;
          _currentPlan = res.currentPlan;
          _loading = false;
        });
      }
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _requestPackage(PackageModel pkg) async {
    final res = await PackageService.requestPackage(pkg.id);
    if (mounted) {
      showToast(message: res['message'] as String? ?? '');
      if (res['success'] != true && res['action'] == 'add_balance') {
        await context.push(const HewalaPage());
      }
      if (res['success'] == true) _loadPackages();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: AppLocale.tr('packages')),
      body: _loading
          ? Center(child: CircularProgressIndicator(color: AppColors.darkBlue))
          : RefreshIndicator(
              onRefresh: _loadPackages,
              child: SingleChildScrollView(
                physics: const BouncingScrollPhysics(),
                padding: EdgeInsets.all(16.w),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    if (TokenStorage.hasToken() && _currentPlan != null) ...[
                      CurrentPlanCard(
                        plan: _currentPlan!,
                        onCreateAd: () => context.push(PostAdStepperPage(title: AppLocale.tr('add_ad'))),
                      ),
                      if (_creditBatches.isNotEmpty) ...[
                        SizedBox(height: 10.h),
                        Container(
                          padding: EdgeInsets.all(12.w),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(10.r),
                            border: Border.all(color: AppColors.darkBlue.withValues(alpha: 0.2)),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              Text(
                                AppLocale.tr('credit_activation_balance'),
                                style: TextStyle(
                                  fontSize: 14.sp,
                                  fontWeight: FontWeight.w700,
                                  color: AppColors.darkBlue,
                                ),
                              ),
                              SizedBox(height: 8.h),
                              ..._creditBatches.map((b) => Padding(
                                    padding: EdgeInsets.only(bottom: 8.h),
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.stretch,
                                      children: [
                                        Text(
                                          b.packageName,
                                          style: TextStyle(fontSize: 13.sp, fontWeight: FontWeight.w600),
                                        ),
                                        SizedBox(height: 4.h),
                                        Text(
                                          '${AppLocale.tr('featured_ads')}: ${b.featuredCreditsRemaining} — ${AppLocale.tr('urgent_ads_label')}: ${b.urgentCreditsRemaining}',
                                          style: TextStyle(fontSize: 12.sp, color: Colors.grey[700]),
                                        ),
                                      ],
                                    ),
                                  )),
                            ],
                          ),
                        ),
                      ],
                      SizedBox(height: 20.h),
                    ],
                    if (!TokenStorage.hasToken())
                      Padding(
                        padding: EdgeInsets.symmetric(vertical: 12.h),
                        child: Center(
                          child: Text(
                            AppLocale.tr('login_to_see_plan'),
                            style: TextStyle(fontSize: 14.sp, color: Colors.grey[600]),
                          ),
                        ),
                      ),
                    if (!TokenStorage.hasToken()) SizedBox(height: 12.h),
                    Text(
                      AppLocale.tr('available_packages'),
                      style: TextStyle(
                        fontSize: 18.sp,
                        fontWeight: FontWeight.w600,
                        color: AppColors.darkBlue,
                      ),
                    ),
                    SizedBox(height: 12.h),
                    if (_packages.isEmpty)
                      Container(
                        padding: EdgeInsets.all(32.w),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(12.r),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withValues(alpha: 0.06),
                              blurRadius: 8,
                              offset: const Offset(0, 2),
                            ),
                          ],
                        ),
                        child: Column(
                          children: [
                            Icon(Icons.inventory_2_outlined,
                                size: 48.sp, color: Colors.grey[400]),
                            SizedBox(height: 16.h),
                            Text(
                              AppLocale.tr('no_packages_available'),
                              style: TextStyle(
                                fontSize: 16.sp,
                                color: Colors.grey[600],
                              ),
                            ),
                          ],
                        ),
                      )
                    else
                      ..._packages.map((pkg) => _PackageCard(
                            package: pkg,
                            isLoggedIn: TokenStorage.hasToken(),
                            onSubscribe: () => _requestPackage(pkg),
                            onLogin: () => context.push(LoginPage()),
                            onAddBalance: () => context.push(const HewalaPage()),
                          )),
                  ],
                ),
              ),
            ),
    );
  }
}

class _PackageCard extends StatelessWidget {
  final PackageModel package;
  final bool isLoggedIn;
  final VoidCallback onSubscribe;
  final VoidCallback onLogin;
  final VoidCallback onAddBalance;

  const _PackageCard({
    required this.package,
    required this.isLoggedIn,
    required this.onSubscribe,
    required this.onLogin,
    required this.onAddBalance,
  });

  List<String> _buildFeatures() {
    final list = <String>[];
    list.add(AppLocale.tr('unlimited_ads'));
    if (package.featuredAds && package.featuredAdsLimit > 0) {
      list.add(
        '${package.featuredAdsLimit} × ${package.featuredDurationDays} ${AppLocale.tr('days_unit')} — ${AppLocale.tr('featured_ads')}',
      );
    }
    if (package.urgentAds && package.urgentAdsLimit > 0) {
      list.add(
        '${package.urgentAdsLimit} × ${package.urgentDurationDays} ${AppLocale.tr('days_unit')} — ${AppLocale.tr('urgent_ads_label')}',
      );
    }
    if (package.prioritySupport) list.add(AppLocale.tr('priority_support'));
    if (package.homepageDisplay) list.add(AppLocale.tr('homepage_display'));
    if (package.features.isNotEmpty) list.addAll(package.features);
    return list;
  }

  @override
  Widget build(BuildContext context) {
    final features = _buildFeatures();
    final canActivateNow = package.canActivateNow;
    return Container(
      margin: EdgeInsets.only(bottom: 16.h),
      padding: EdgeInsets.all(16.w),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12.r),
        border: Border.all(
          color: AppColors.darkBlue.withValues(alpha: 0.2),
          width: 1,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.06),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            package.name,
            style: TextStyle(
              fontSize: 20.sp,
              fontWeight: FontWeight.bold,
              color: AppColors.darkBlue,
            ),
            textAlign: TextAlign.center,
          ),
          if (package.description != null && package.description!.isNotEmpty) ...[
            SizedBox(height: 8.h),
            Text(
              package.description!,
              style: TextStyle(fontSize: 14.sp, color: Colors.grey[600]),
              textAlign: TextAlign.center,
            ),
          ],
          SizedBox(height: 16.h),
          Container(
            padding: EdgeInsets.symmetric(vertical: 12.h),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [
                  AppColors.darkBlue,
                  AppColors.darkBlue.withValues(alpha: 0.8),
                ],
                begin: Alignment.centerLeft,
                end: Alignment.centerRight,
              ),
              borderRadius: BorderRadius.circular(8.r),
            ),
            child: Column(
              children: [
                Text(
                  package.formattedPrice ?? CurrencyHelper.formatPrice(package.price, package.currency),
                  style: TextStyle(
                    fontSize: 24.sp,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
                Text(
                  AppLocale.tr('package_price_activation_hint'),
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 11.sp,
                    color: Colors.white70,
                  ),
                ),
              ],
            ),
          ),
          SizedBox(height: 16.h),
          ...features.map(
            (f) => Padding(
              padding: EdgeInsets.symmetric(vertical: 4.h),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  Text(f, style: TextStyle(fontSize: 14.sp)),
                  SizedBox(width: 8.w),
                  Icon(Icons.check_circle, color: Colors.green, size: 20.sp),
                ],
              ),
            ),
          ),
          SizedBox(height: 16.h),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.darkBlue,
                foregroundColor: Colors.white,
                padding: EdgeInsets.symmetric(vertical: 14.h),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(8.r),
                ),
              ),
              onPressed: !isLoggedIn
                  ? onLogin
                  : (canActivateNow ? onSubscribe : onAddBalance),
              child: Text(
                !isLoggedIn
                    ? AppLocale.tr('login_to_request')
                    : (canActivateNow
                        ? AppLocale.tr('activate_now')
                        : AppLocale.tr('add_balance_to_activate')),
                style: TextStyle(fontSize: 16.sp),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
