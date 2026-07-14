import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/data/services/ad_service.dart';
import 'package:a3lnha/core/locale/locale_storage.dart';
import 'package:a3lnha/core/network/api_constants.dart';
import 'package:a3lnha/core/notifications/fcm_service.dart';
import 'package:a3lnha/core/storage/token_storage.dart';
import 'package:a3lnha/data/services/package_service.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/models/user_model.dart';
import 'package:a3lnha/data/services/auth_service.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/account/edit_business_profile_page.dart';
import 'package:a3lnha/presentation/pages/account/edit_profile_page.dart';
import 'package:a3lnha/presentation/pages/account/favourite_ads_page.dart';
import 'package:a3lnha/presentation/pages/account/favourite_sellers_page.dart';
import 'package:a3lnha/presentation/pages/account/blocked_users_page.dart';
import 'package:a3lnha/presentation/pages/account/coming_soon_page.dart';
import 'package:a3lnha/presentation/pages/account/help_page.dart';
import 'package:a3lnha/presentation/pages/account/messages_page.dart';
import 'package:a3lnha/presentation/pages/account/my_products_deals_page.dart';
import 'package:a3lnha/presentation/pages/account/saved_searches_page.dart';
import 'package:a3lnha/presentation/pages/account/not_published_ads_page.dart';
import 'package:a3lnha/presentation/pages/account/on_air_ads_page.dart';
import 'package:a3lnha/presentation/pages/account/suspended_ads_page.dart';
import 'package:a3lnha/presentation/pages/home/info_about_app_page.dart';
import 'package:a3lnha/presentation/pages/home/post_ad_stepper_page.dart';
import 'package:a3lnha/presentation/pages/payement/hewala_page.dart';
import 'package:a3lnha/presentation/pages/account/permissions_page.dart';
import 'package:a3lnha/presentation/pages/account/problems_page.dart';
import 'package:a3lnha/presentation/pages/account/reports_page.dart';
import 'package:a3lnha/presentation/pages/account/share_profile_page.dart';
import 'package:a3lnha/presentation/pages/account/verification_page.dart';
import 'package:a3lnha/presentation/pages/home/home_page.dart';
import 'package:a3lnha/presentation/pages/payement/my_wallet_page.dart';
import 'package:a3lnha/presentation/pages/payement/quta_pages.dart';
import 'package:a3lnha/presentation/widgets/shared/app_network_image.dart';
import 'package:a3lnha/presentation/widgets/shared/current_plan_card.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:hexcolor/hexcolor.dart';

class MyAccountPage extends StatefulWidget {
  const MyAccountPage({super.key});

  @override
  State<MyAccountPage> createState() => _MyAccountPageState();
}

class _MyAccountPageState extends State<MyAccountPage> {
  UserModel? _user;
  bool _loadingUser = true;
  int _onAirCount = 0;
  int _notPublishedCount = 0;
  int _suspendedCount = 0;
  CurrentPlanInfo? _currentPlan;
  bool _loadingPlan = false;

  @override
  void initState() {
    super.initState();
    _loadUser();
    _loadAdCounts();
  }

  Future<void> _loadCurrentPlan() async {
    if (!TokenStorage.hasToken()) return;
    if (mounted) setState(() => _loadingPlan = true);
    try {
      final res = await PackageService.getPackages(forceRefresh: true);
      if (mounted) {
        setState(() {
          _currentPlan = res.currentPlan;
          _loadingPlan = false;
        });
      }
    } catch (_) {
      if (mounted) {
        setState(() => _loadingPlan = false);
        showToast(message: AppLocale.tr('plan_load_failed'));
      }
    }
  }

  Future<void> _loadAdCounts({bool forceRefresh = false}) async {
    final activeRes = await AdService.getMyAds(status: 'active', perPage: 1, forceRefresh: forceRefresh);
    final pendingRes = await AdService.getMyAds(status: 'pending', perPage: 1, forceRefresh: forceRefresh);
    final suspendedRes = await AdService.getMyAds(status: 'suspended', perPage: 1, forceRefresh: forceRefresh);
    if (mounted) {
      setState(() {
        _onAirCount = activeRes.total;
        _notPublishedCount = pendingRes.total;
        _suspendedCount = suspendedRes.total;
      });
    }
  }

  Future<void> _loadUser({bool forceRefresh = false}) async {
    final result = await AuthService.getMe(forceRefresh: forceRefresh);
    if (mounted) {
      setState(() {
        _user = result.user;
        _loadingUser = false;
      });
      // تحديث FCM token عند فتح الحساب (لتأكيد وصول الإشعارات)
      if (result.user != null) {
        FcmService.refreshAndSendToken();
        // تحميل تفاصيل الباقة بعد التأكد من تسجيل الدخول ونجاح /me
        _loadCurrentPlan();
      }
    }
  }

  void _selectLanguage(BuildContext dialogContext, String locale) async {
    if (AppLocale.current == locale) {
      Navigator.pop(dialogContext);
      return;
    }
    final navigator = Navigator.of(context);
    Navigator.pop(dialogContext);
    await AppLocale.setLocale(locale);
    if (!mounted) return;
    navigator.pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => const HomePage()),
      (route) => false,
    );
  }

  Future<void> _handleLogout() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20.r)),
        title: Text(AppLocale.tr('logout')),
        content: Text(AppLocale.tr('confirm_logout')),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(AppLocale.tr('cancel')),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(AppLocale.tr('logout')),
          ),
        ],
      ),
    );

    if (confirm != true || !mounted) return;

    final result = await AuthService.logout();
    if (!mounted) return;

    if (result.success) {
            // بعد تسجيل الخروج نعود للرئيسية كزائر
      context.pushAndRemoveUntil(const HomePage());
      showToast(message: result.message ?? AppLocale.tr('logout_success'));
    } else {
      showToast(message: result.message ?? AppLocale.tr('logout_failed'));
    }
  }

  Future<void> _shareMyProfile() async {
    final user = _user;
    if (user == null) return;
    final slug = (user.slug ?? '').trim();
    if (slug.isEmpty) {
      showToast(message: AppLocale.tr('failed'));
      return;
    }
    final origin = ApiConstants.webOrigin;
    final path = '/seller/$slug';
    final link = origin.endsWith('/')
        ? '$origin${path.substring(1)}'
        : '$origin$path';
    if (!mounted) return;
    await context.push(
      ShareProfilePage(
        userName: _accountDisplayName(user),
        profileUrl: link,
      ),
    );
  }

  String _accountDisplayName(UserModel? user) {
    if (user == null) return AppLocale.tr('my_account');
    final rawName = user.name.trim();
    final businessName = (user.businessName ?? '').trim();
    final businessOwner = (user.businessOwner ?? '').trim();

    // في بعض الحسابات الموثقة قد يصل الاسم مساويًا لاسم النشاط؛
    // نُفضّل اسم صاحب النشاط إن كان متوفّرًا.
    if (user.isVerified &&
        rawName.isNotEmpty &&
        businessName.isNotEmpty &&
        rawName.toLowerCase() == businessName.toLowerCase() &&
        businessOwner.isNotEmpty) {
      return businessOwner;
    }

    return rawName.isNotEmpty ? rawName : AppLocale.tr('my_account');
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: AppLocale.tr('my_account')),
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            height: MediaQuery.sizeOf(context).height / 4.5,
            child: Stack(
              children: [
                Container(
                  padding: EdgeInsets.symmetric(
                    horizontal: 20.w,
                    vertical: 60.h,
                  ),
                  width: double.infinity,
                  height: MediaQuery.sizeOf(context).height / 7,
                  color: AppColors.darkBlue,
                ),
                Positioned(
                  left: 20.w,
                  right: 20.w,
                  top: 30.h,
                  child: Container(
                    width: double.infinity,
                    height: MediaQuery.sizeOf(context).height / 5.5,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      boxShadow: [
                        BoxShadow(
                          // ignore: deprecated_member_use
                          color: Colors.black.withOpacity(0.1),
                          blurRadius: 10,
                          offset: Offset(0, 4), // changes position of shadow
                        ),
                      ],
                      borderRadius: BorderRadius.circular(12.r),
                    ),
                    child: Padding(
                      padding: EdgeInsets.symmetric(
                        horizontal: 20.0.w,
                        vertical: 16.h,
                      ),
                      child: Row(
                        children: [
                          AvatarWithFallback(
                            imageUrl: _user?.avatar,
                            radius: 35.r,
                            fallbackLetter: (_user?.name.isNotEmpty == true ? _user!.name[0] : '?').toUpperCase(),
                            fontSize: 24.sp,
                          ),
                          SizedBox(width: 16.w),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                if (_loadingUser)
                                  CircularProgressIndicator(color: AppColors.darkBlue, strokeWidth: 2)
                                else ...[
                                  Row(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      Flexible(
                                        child: Text(
                                          _accountDisplayName(_user),
                                          maxLines: 1,
                                          overflow: TextOverflow.ellipsis,
                                          style: TextStyle(
                                            fontSize: 18.sp,
                                            fontWeight: FontWeight.w500,
                                          ),
                                        ),
                                      ),
                                      if (_user?.isVerified == true) ...[
                                        SizedBox(width: 6.w),
                                        Icon(
                                          Icons.verified,
                                          color: Colors.blue,
                                          size: 18.sp,
                                        ),
                                      ],
                                    ],
                                  ),
                                  Text(
                                    _user?.email ?? "—",
                                    style: TextStyle(
                                      fontSize: 14.sp,
                                      fontWeight: FontWeight.w400,
                                      color: Colors.grey,
                                    ),
                                  ),
                                  SizedBox(height: 5.h),
                                  GestureDetector(
                                    behavior: HitTestBehavior.opaque,
                                    onTap: () {
                                      context.push(EditProfilePage()).then((_) => _loadUser(forceRefresh: true));
                                    },
                                    child: SizedBox(
                                      width: double.infinity,
                                      child: Row(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          Text(
                                            AppLocale.tr('edit_profile'),
                                            style: TextStyle(
                                              fontSize: 12.sp,
                                              fontWeight: FontWeight.w500,
                                              color: HexColor("030712"),
                                            ),
                                          ),
                                          SizedBox(width: 7.w),
                                          Image.asset(
                                            "assets/images/edit.png",
                                            width: 24.w,
                                            height: 24.h,
                                          ),
                                        ],
                                      ),
                                    ),
                                  ),
                                  SizedBox(height: 6.h),
                                  GestureDetector(
                                    behavior: HitTestBehavior.opaque,
                                    onTap: _shareMyProfile,
                                    child: Row(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        Text(
                                          AppLocale.tr('share'),
                                          style: TextStyle(
                                            fontSize: 12.sp,
                                            fontWeight: FontWeight.w500,
                                            color: AppColors.darkBlue,
                                          ),
                                        ),
                                        SizedBox(width: 7.w),
                                        Icon(
                                          Icons.ios_share,
                                          size: 18.sp,
                                          color: AppColors.darkBlue,
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
          Expanded(
            child: SingleChildScrollView(
              physics: BouncingScrollPhysics(),
              child: Padding(
                padding: EdgeInsets.symmetric(vertical: 10.h, horizontal: 20.w),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (TokenStorage.hasToken()) ...[
                      Text(
                        AppLocale.tr('your_current_plan'),
                        style: TextStyle(
                          fontSize: 12.sp,
                          fontWeight: FontWeight.w600,
                          color: HexColor("030712"),
                        ),
                      ),
                      SizedBox(height: 10.h),
                      if (_loadingPlan)
                        Center(
                          child: Padding(
                            padding: EdgeInsets.symmetric(vertical: 20.h),
                            child: SizedBox(
                              width: 28.w,
                              height: 28.h,
                              child: CircularProgressIndicator(strokeWidth: 2, color: AppColors.darkBlue),
                            ),
                          ),
                        )
                      else if (_currentPlan != null)
                        CurrentPlanCard(
                          plan: _currentPlan!,
                          onCreateAd: () => context.push(PostAdStepperPage(title: AppLocale.tr('add_ad'))),
                        )
                      else
                        GestureDetector(
                          onTap: _loadCurrentPlan,
                          child: Container(
                            padding: EdgeInsets.symmetric(vertical: 16.h, horizontal: 16.w),
                            decoration: BoxDecoration(
                              color: Colors.grey.shade100,
                              borderRadius: BorderRadius.circular(12.r),
                              border: Border.all(color: Colors.grey.shade300),
                            ),
                            child: Row(
                              children: [
                                Icon(Icons.refresh, color: AppColors.darkBlue, size: 22.sp),
                                SizedBox(width: 12.w),
                                Expanded(
                                  child: Text(
                                    AppLocale.tr('tap_to_load_plan'),
                                    style: TextStyle(fontSize: 13.sp, color: Colors.grey.shade700),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      if (!_loadingUser && _user?.isVerified == true) ...[
                        SizedBox(height: 20.h),
                        Text(
                          AppLocale.tr('verified_business_section'),
                          style: TextStyle(
                            fontSize: 12.sp,
                            fontWeight: FontWeight.w600,
                            color: HexColor("030712"),
                          ),
                        ),
                        SizedBox(height: 10.h),
                        ProfileContainer(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Padding(
                                padding: EdgeInsets.fromLTRB(0, 12.h, 0, 4.h),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    if (_user?.businessName != null && _user!.businessName!.trim().isNotEmpty)
                                      Text(
                                        _user!.businessName!,
                                        style: TextStyle(
                                          fontSize: 14.sp,
                                          fontWeight: FontWeight.w600,
                                          color: HexColor("030712"),
                                        ),
                                      )
                                    else
                                      Text(
                                        AppLocale.tr('edit_verified_business'),
                                        style: TextStyle(fontSize: 13.sp, color: Colors.grey[600]),
                                      ),
                                    if (_user?.businessType != null && _user!.businessType!.trim().isNotEmpty) ...[
                                      SizedBox(height: 6.h),
                                      Text(
                                        _user!.businessType!,
                                        style: TextStyle(fontSize: 12.sp, color: Colors.grey[700]),
                                        maxLines: 2,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ],
                                    if (_user?.businessOwner != null && _user!.businessOwner!.trim().isNotEmpty) ...[
                                      SizedBox(height: 4.h),
                                      Text(
                                        _user!.businessOwner!,
                                        style: TextStyle(fontSize: 11.sp, color: Colors.grey[600]),
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ],
                                    if (_user?.businessAddress != null && _user!.businessAddress!.trim().isNotEmpty) ...[
                                      SizedBox(height: 4.h),
                                      Row(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Icon(Icons.location_on_outlined, size: 14.sp, color: Colors.grey[600]),
                                          SizedBox(width: 4.w),
                                          Expanded(
                                            child: Text(
                                              _user!.businessAddress!,
                                              style: TextStyle(fontSize: 11.sp, color: Colors.grey[600]),
                                              maxLines: 2,
                                              overflow: TextOverflow.ellipsis,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ],
                                    if (_user?.businessPhone != null && _user!.businessPhone!.trim().isNotEmpty) ...[
                                      SizedBox(height: 4.h),
                                      Row(
                                        children: [
                                          Icon(Icons.phone_outlined, size: 14.sp, color: Colors.grey[600]),
                                          SizedBox(width: 4.w),
                                          Expanded(
                                            child: Text(
                                              _user!.businessPhone!,
                                              style: TextStyle(fontSize: 11.sp, color: Colors.grey[600]),
                                            ),
                                          ),
                                        ],
                                      ),
                                    ],
                                  ],
                                ),
                              ),
                              Divider(height: 10.h, thickness: 1.5.h),
                              ProfileRow(
                                title: AppLocale.tr('edit_verified_business'),
                                onTap: () {
                                  context
                                      .push(const EditBusinessProfilePage())
                                      .then((_) => _loadUser(forceRefresh: true));
                                },
                              ),
                            ],
                          ),
                        ),
                      ],
                      SizedBox(height: 20.h),
                    ],
                    Text(
                      AppLocale.tr('ad_management'),
                      style: TextStyle(
                        fontSize: 12.sp,
                        fontWeight: FontWeight.w600,
                        color: HexColor("030712"),
                      ),
                    ),
                    SizedBox(height: 15.w),
                    ProfileContainer(
                      child: Column(
                        children: [
                          ProfileRow(
                            isWithNumber: true,
                            count: _onAirCount,
                            title: AppLocale.tr('on_air'),
                            onTap: () {
                              context.push(OnAirAdsPage()).then((_) => _loadAdCounts(forceRefresh: true));
                            },
                          ),
                          Divider(height: 10.h, thickness: 1.5.h),
                          ProfileRow(
                            isWithNumber: true,
                            count: _suspendedCount,
                            title: AppLocale.tr('suspended'),
                            onTap: () {
                              context.push(SuspendedAdsPage()).then((_) => _loadAdCounts(forceRefresh: true));
                            },
                          ),
                          Divider(height: 10.h, thickness: 1.5.h),
                          ProfileRow(
                            isWithNumber: true,
                            count: _notPublishedCount,
                            title: AppLocale.tr('not_published'),
                            onTap: () {
                              context.push(NotPublishedAdsPage()).then((_) => _loadAdCounts(forceRefresh: true));
                            },
                          ),
                        ],
                      ),
                    ),
                    SizedBox(height: 15.h),
                    Text(
                      AppLocale.tr('messages_info'),
                      style: TextStyle(
                        fontSize: 12.sp,
                        fontWeight: FontWeight.w600,
                        color: HexColor("030712"),
                      ),
                    ),
                    SizedBox(height: 15.h),

                    ProfileContainer(
                      child: Column(
                        children: [
                          ProfileRow(
                            title: AppLocale.tr('messages'),
                            onTap: () {
                              context.push(MessagesPage());
                            },
                          ),
                          Divider(height: 10.h, thickness: 1.5.h),
                          ProfileRow(
                            title: AppLocale.tr('permissions'),
                            onTap: () {
                              context.push(PermissionsPage());
                            },
                          ),
                        ],
                      ),
                    ),
                    SizedBox(height: 15.h),
                    Text(
                      AppLocale.tr('negotiation_requests'),
                      style: TextStyle(
                        fontSize: 12.sp,
                        fontWeight: FontWeight.w600,
                        color: HexColor("030712"),
                      ),
                    ),
                    SizedBox(height: 15.h),
                    ProfileContainer(
                      child: ProfileRow(
                        title: AppLocale.tr('sent_received_offers'),
                        onTap: () {
                          context.push(MyProductsDealsPage());
                        },
                      ),
                    ),
                    SizedBox(height: 15.h),
                    ProfileContainer(
                      child: ProfileRow(
                        title: AppLocale.tr('saved_searches'),
                        onTap: () {
                          context.push(const SavedSearchesPage());
                        },
                      ),
                    ),
                    SizedBox(height: 15.h),
                    Text(
                      AppLocale.tr('favorites'),
                      style: TextStyle(
                        fontSize: 12.sp,
                        fontWeight: FontWeight.w600,
                        color: HexColor("030712"),
                      ),
                    ),
                    SizedBox(height: 15.h),
                    ProfileContainer(
                      child: ProfileRow(
                        title: AppLocale.tr('favorite_ads'),
                        onTap: () {
                          context.push(
                            FavouriteAdsPage(title: AppLocale.tr('favorite_ads')),
                          );
                        },
                      ),
                    ),
                    SizedBox(height: 15.h),
                    ProfileContainer(
                      child: ProfileRow(
                        title: AppLocale.tr('favorite_sellers'),
                        onTap: () {
                          context.push(FavouriteSellersPage());
                        },
                      ),
                    ),
                    SizedBox(height: 15.h),
                    Text(
                      AppLocale.tr('my_account'),
                      style: TextStyle(
                        fontSize: 12.sp,
                        fontWeight: FontWeight.w600,
                        color: HexColor("030712"),
                      ),
                    ),
                    SizedBox(height: 15.h),
                    ProfileContainer(
                      child: Column(
                        children: [
                          ProfileRow(
                            title: AppLocale.tr('account_info'),
                            onTap: () {
                              context.push(EditProfilePage()).then((_) => _loadUser(forceRefresh: true));
                            },
                          ),
                          Divider(height: 10.h, thickness: 1.5.h),
                          ProfileRow(
                            title: AppLocale.tr('identity_verification'),
                            onTap: () {
                              context.push(VerificationPage());
                            },
                          ),
                          Divider(height: 10.h, thickness: 1.5.h),
                          ProfileRow(
                            title: AppLocale.tr('blocked_users'),
                            onTap: () {
                              context.push(BlockedUsersPage());
                            },
                          ),
                          Divider(height: 10.h, thickness: 1.5.h),
                          ProfileRow(
                            title: AppLocale.tr('my_reports'),
                            onTap: () {
                              context.push(ReportsPage());
                            },
                          ),
                        ],
                      ),
                    ),
                    SizedBox(height: 15.h),

                    ProfileContainer(
                      child: ProfileRow(
                        title: AppLocale.tr('payment_gateway'),
                        onTap: () {
                          context.push(ComingSoonPage(title: AppLocale.tr('payment_gateway')));
                        },
                      ),
                    ),
                    SizedBox(height: 15.h),
                    ProfileContainer(
                      child: ProfileRow(
                        title: AppLocale.tr('transfer'),
                        onTap: () {
                          context.push(const HewalaPage());
                        },
                      ),
                    ),
                    SizedBox(height: 15.h),
                    ProfileContainer(
                      child: ProfileRow(
                        title: AppLocale.tr('packages'),
                        onTap: () async {
                          await context.push(QutaPages());
                          if (mounted) _loadCurrentPlan();
                        },
                      ),
                    ),
                    SizedBox(height: 15.h),
                    ProfileContainer(
                      child: ProfileRow(
                        title: AppLocale.tr('wallet'),
                        onTap: () {
                          context.push(const MyWalletPage());
                        },
                      ),
                    ),
                    SizedBox(height: 15.h),
                    ProfileContainer(
                      child: ProfileRow(
                        title: AppLocale.tr('help'),
                        onTap: () {
                          context.push(HelpPage());
                        },
                      ),
                    ),
                    SizedBox(height: 15.h),
                    ProfileContainer(
                      child: ProfileRow(
                        title: AppLocale.tr('report_problem'),
                        onTap: () {
                          context.push(ProblemsPage());
                        },
                      ),
                    ),
                    // SizedBox(height: 15.h),
                    // ProfileContainer(
                    //   child: ProfileRow(
                    //     title: AppLocale.tr('notifications'),
                    //     onTap: () {
                    //       context.push(NotificationPage());
                    //     },
                    //   ),
                    // ),
                    SizedBox(height: 15.h),
                    ProfileContainer(
                      child: ProfileRow(
                        title: AppLocale.tr('choose_language'),
                        onTap: () {
                          showDialog(
                            context: context,
                            builder: (BuildContext ctx) {
                              return Dialog(
                                backgroundColor: Colors.white,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(24.r),
                                ),
                                child: Padding(
                                  padding: EdgeInsets.all(35.h),
                                  child: Column(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      InDialogButton(
                                        imagePath: "assets/images/arabic.png",
                                        title: AppLocale.tr('language_arabic'),
                                        backgroundColor: AppLocale.current == LocaleStorage.ar
                                            ? AppColors.darkBlue.withValues(alpha: 0.1)
                                            : Colors.white,
                                        textColor: AppColors.darkBlue,
                                        onTap: () => _selectLanguage(ctx, LocaleStorage.ar),
                                      ),
                                      SizedBox(height: 25.h),
                                      InDialogButton(
                                        imagePath: "assets/images/turkey.png",
                                        title: AppLocale.tr('language_turkish'),
                                        backgroundColor: AppLocale.current == LocaleStorage.tr
                                            ? AppColors.darkBlue.withValues(alpha: 0.1)
                                            : Colors.white,
                                        textColor: AppColors.darkBlue,
                                        onTap: () => _selectLanguage(ctx, LocaleStorage.tr),
                                      ),
                                      SizedBox(height: 25.h),
                                      InDialogButton(
                                        imagePath: "assets/images/en.png",
                                        title: AppLocale.tr('language_english'),
                                        backgroundColor: AppLocale.current == LocaleStorage.en
                                            ? AppColors.darkBlue.withValues(alpha: 0.1)
                                            : Colors.white,
                                        textColor: AppColors.darkBlue,
                                        onTap: () => _selectLanguage(ctx, LocaleStorage.en),
                                      ),
                                    ],
                                  ),
                                ),
                              );
                            },
                          );
                        },
                      ),
                    ),
                    SizedBox(height: 15.h),
                    ProfileContainer(
                      child: ProfileRow(
                        title: AppLocale.tr('app_info'),
                        onTap: () {
                          context.push(InfoAboutAppPage());
                        },
                      ),
                    ),
                    SizedBox(height: 15.h),
                    Center(
                      child: TextButton.icon(
                        onPressed: _handleLogout,
                        label: Text(AppLocale.tr('logout')),
                        icon: Image.asset(
                          "assets/images/logout-03.png",
                          width: 20.w,
                          height: 20.h,
                        ),
                        style: TextButton.styleFrom(
                          foregroundColor: Colors.red,
                          textStyle: TextStyle(fontSize: 14.sp),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class ProfileContainer extends StatelessWidget {
  final Widget child;
  const ProfileContainer({super.key, required this.child});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.symmetric(vertical: 2.h, horizontal: 15.w),
      width: double.infinity,
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            // ignore: deprecated_member_use
            color: Colors.black.withOpacity(0.1),
            blurRadius: 10,
            spreadRadius: 0,
            offset: Offset(0, 2), // changes position of shadow
          ),
        ],
        borderRadius: BorderRadius.circular(12.r),
      ),
      child: child,
    );
  }
}

class ProfileRow extends StatelessWidget {
  final bool isWithNumber;
  final int? count;
  final String title;
  final VoidCallback onTap;
  const ProfileRow({
    super.key,
    this.isWithNumber = false,
    this.count,
    required this.title,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: onTap,
      child: Padding(
        padding: EdgeInsets.symmetric(vertical: 10.h),
        child: SizedBox(
          width: double.infinity,
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                title,
                style: TextStyle(fontSize: 12.sp, fontWeight: FontWeight.w400),
              ),
              isWithNumber
                  ? Text('(${count ?? 0})')
                  : Icon(Icons.arrow_forward_ios, size: 16.sp),
            ],
          ),
        ),
      ),
    );
  }
}
