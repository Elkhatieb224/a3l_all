import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/performance/startup_warmup.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/services/home_service.dart';
import 'package:a3lnha/presentation/pages/home/home_page.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  @override
  void initState() {
    super.initState();
    _checkAuthAndNavigate();
  }

  Future<void> _checkAuthAndNavigate() async {
    const minSplashDuration = Duration(milliseconds: 1200);

    // أولوية: إكمال جلب الرئيسية (`/home`) قبل الدخول؛ بالتوازي مع حد أدنى لمدة السبلاش.
    late HomeData bootstrapHome;
    await Future.wait<void>([
      StartupWarmup.loadHomeDuringSplash().then((h) => bootstrapHome = h),
      Future<void>.delayed(minSplashDuration),
    ]);

    if (!mounted) return;

    StartupWarmup.scheduleSecondaryPrewarm();

    Navigator.pushReplacement(
      context,
      MaterialPageRoute(
        builder: (_) => HomePage(bootstrapHome: bootstrapHome),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        width: double.infinity,
        height: double.infinity,
        color: AppColors.darkBlue,
        child: Stack(
          children: [
            Center(
              child: Image.asset(
                "assets/images/logo.png",
                width: 150.w,
                height: 160.h,
              ),
            ),
            Positioned(
              bottom: 50.h,
              left: 0,
              right: 0,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  Text(
                    AppLocale.tr('app_name'),
                    style: TextStyle(
                      color: AppColors.yellow,
                      fontSize: 20.sp,
                      fontWeight: FontWeight.bold,
                      height: 2,
                    ),
                  ),
                  Text(
                    AppLocale.tr('tagline'),
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 15.sp,
                      fontWeight: FontWeight.bold,
                    ),
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
