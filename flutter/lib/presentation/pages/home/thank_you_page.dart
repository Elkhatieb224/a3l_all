import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/account/not_published_ads_page.dart';
import 'package:a3lnha/presentation/pages/home/home_page.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_button.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class ThankYouPage extends StatelessWidget {
  const ThankYouPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Padding(
        padding: EdgeInsets.symmetric(horizontal: 20.w),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Center(
              child: Container(
                padding: EdgeInsets.symmetric(vertical: 20.h, horizontal: 20.w),
                width: double.infinity,
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12.r),
                ),
                child: Column(
                  children: [
                    Image.asset(
                      "assets/images/Clip path group.png",
                      filterQuality: FilterQuality.high,
                      isAntiAlias: true,
                      fit: BoxFit.contain,
                      errorBuilder: (_, __, ___) => const SizedBox.shrink(),
                    ),

                    Text(
                      AppLocale.tr('thank_you'),
                      style: TextStyle(
                        fontSize: 20.sp,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    SizedBox(height: 5.h),
                    Text(
                      AppLocale.tr('ad_pending_review'),
                      style: TextStyle(
                        fontSize: 14.sp,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                    SizedBox(height: 10.h),
                    Text(
                      AppLocale.tr('ad_pending_public_hint'),
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 12.sp,
                        color: Colors.grey[700],
                        height: 1.35,
                      ),
                    ),
                    SizedBox(height: 20.h),

                    CustomButton(
                      text: AppLocale.tr('home'),
                      onTap: () {
                        context.pushAndRemoveUntil(HomePage());
                      },
                    ),
                    SizedBox(height: 12.h),
                    SizedBox(
                      width: double.infinity,
                      height: 45.h,
                      child: OutlinedButton(
                        onPressed: () {
                          context.push(NotPublishedAdsPage());
                        },
                        style: OutlinedButton.styleFrom(
                          side: BorderSide(color: AppColors.darkBlue),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(8.r),
                          ),
                        ),
                        child: Text(
                          AppLocale.tr('pending_ads_list'),
                          style: TextStyle(
                            fontSize: 14.sp,
                            fontWeight: FontWeight.w600,
                            color: AppColors.darkBlue,
                          ),
                        ),
                      ),
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
