import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

/// صفحة تعرض رسالة "قريباً ستكون متاحة" للميزات غير المكتملة بعد
class ComingSoonPage extends StatelessWidget {
  /// عنوان الصفحة في شريط التطبيق (اختياري)
  final String? title;

  const ComingSoonPage({super.key, this.title});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: title ?? AppLocale.tr('coming_soon')),
      body: Center(
        child: Padding(
          padding: EdgeInsets.symmetric(horizontal: 32.w),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                Icons.construction_rounded,
                size: 80.sp,
                color: AppColors.darkBlue.withOpacity(0.6),
              ),
              SizedBox(height: 24.h),
              Text(
                AppLocale.tr('coming_soon'),
                style: TextStyle(
                  fontSize: 22.sp,
                  fontWeight: FontWeight.bold,
                  color: AppColors.darkBlue,
                ),
                textAlign: TextAlign.center,
              ),
              SizedBox(height: 12.h),
              Text(
                AppLocale.tr('coming_soon_message'),
                style: TextStyle(
                  fontSize: 16.sp,
                  color: Colors.grey[600],
                ),
                textAlign: TextAlign.center,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
