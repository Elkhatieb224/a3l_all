import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/network/app_init.dart';
import 'package:a3lnha/core/notifications/fcm_service.dart';
import 'package:a3lnha/core/navigation/app_navigator_key.dart';
import 'package:a3lnha/core/styles/themes.dart';
import 'package:a3lnha/presentation/pages/splash/splash_screen.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  await AppInit.init();

  await FcmService.init();

  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return ValueListenableBuilder<String>(
      valueListenable: AppLocale.localeNotifier,
      builder: (context, locale, _) {
        return ScreenUtilInit(
          designSize: const Size(375, 812),
          minTextAdapt: true,
          splitScreenMode: true,
          builder: (context, child) {
            return MaterialApp(
              navigatorKey: navigatorKey,
              locale: AppLocale.locale,
              theme: Themes.lightTheme,
              // ignore: deprecated_member_use
              useInheritedMediaQuery: true,
              debugShowCheckedModeBanner: false,
              builder: (context, child) {
                return Directionality(
                  textDirection: AppLocale.textDirection,
                  child: SafeArea(
                    top: false,
                    left: false,
                    right: false,
                    bottom: true,
                    child: child!,
                  ),
                );
              },
              home: child,
            );
          },
          child: const SplashScreen(),
        );
      },
    );
  }
}
