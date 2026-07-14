import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:hexcolor/hexcolor.dart';

class Themes {
  static ThemeData lightTheme = ThemeData(
    useMaterial3: false,
    primaryColor: HexColor("#1159a4"),
    fontFamily: "Cairo",
    materialTapTargetSize: MaterialTapTargetSize.padded,
    splashColor: HexColor("#1159a4").withValues(alpha: 0.3),
    highlightColor: HexColor("#1159a4").withValues(alpha: 0.1),
    splashFactory: InkRipple.splashFactory,
    // bottomNavigationBarTheme: BottomNavigationBarThemeData(
    //   showUnselectedLabels: true,
    //   backgroundColor: Colors.white,
    //   selectedItemColor: HexColor("#1159a4"),
    //   unselectedItemColor: Colors.grey,
    //   selectedLabelStyle: TextStyle(
    //     fontFamily: "Cairo",
    //     fontSize: 14.sp,
    //     fontWeight: FontWeight.w800,
    //   ),
    //   unselectedLabelStyle: TextStyle(
    //     fontFamily: "Cairo",
    //     fontSize: 12.sp,
    //     fontWeight: FontWeight.w600,
    //   ),
    // ),
    iconButtonTheme: IconButtonThemeData(
      style: IconButton.styleFrom(
        minimumSize: const Size(48, 48),
      ),
    ),
    appBarTheme: AppBarTheme(
      iconTheme: const IconThemeData(color: Colors.black),
      systemOverlayStyle: SystemUiOverlayStyle.light.copyWith(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: Brightness.light,
      ),
      backgroundColor: Colors.transparent,
      titleTextStyle: TextStyle(
        color: Colors.black,
        fontFamily: "Cairo",
        fontSize: 16.sp,
        fontWeight: FontWeight.w500,
      ),
      elevation: 0,
      centerTitle: true,
    ),
  );
}
