import 'package:a3lnha/core/styles/colors.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

/// حوار تأكيد بنفس تصميم تنبيه حذف الحساب
class WarningConfirmDialog extends StatelessWidget {
  final String title;
  final String message;
  final String confirmText;
  final String cancelText;
  final VoidCallback onConfirm;
  final Color? confirmColor;
  /// زر التأكيد بتصميم outline (أبيض مع حدود)
  final bool confirmOutline;

  const WarningConfirmDialog({
    super.key,
    required this.title,
    required this.message,
    required this.confirmText,
    this.cancelText = 'الرجوع',
    required this.onConfirm,
    this.confirmColor,
    this.confirmOutline = false,
  });

  /// عرض الحوار وإرجاع true عند التأكيد، false عند الإلغاء
  static Future<bool> show(
    BuildContext context, {
    required String title,
    required String message,
    required String confirmText,
    String cancelText = 'الرجوع',
    Color? confirmColor,
    bool confirmOutline = false,
  }) async {
    final result = await showDialog<bool>(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => WarningConfirmDialog(
        title: title,
        message: message,
        confirmText: confirmText,
        cancelText: cancelText,
        confirmColor: confirmColor ?? Colors.red,
        confirmOutline: confirmOutline,
        onConfirm: () => Navigator.of(ctx).pop(true),
      ),
    );
    return result ?? false;
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Directionality(
        textDirection: TextDirection.rtl,
        child: Stack(
          clipBehavior: Clip.none,
          alignment: Alignment.topCenter,
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 60, 16, 16),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    title,
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 18,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  SizedBox(height: 10.h),
                  Text(
                    message,
                    style: TextStyle(fontSize: 14.sp),
                    textAlign: TextAlign.center,
                  ),
                  SizedBox(height: 20.h),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      GestureDetector(
                        onTap: onConfirm,
                        child: Container(
                          height: 60.h,
                          width: 105.h,
                          decoration: BoxDecoration(
                            color: confirmOutline ? Colors.white : (confirmColor ?? Colors.red),
                            border: confirmOutline ? Border.all(color: AppColors.yellow, width: 2) : null,
                            borderRadius: BorderRadius.circular(16.r),
                          ),
                          child: Center(
                            child: Text(
                              confirmText,
                              style: TextStyle(
                                color: confirmOutline ? Colors.black : Colors.white,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                        ),
                      ),
                      GestureDetector(
                        onTap: () => Navigator.of(context).pop(false),
                        child: Container(
                          height: 60.h,
                          width: 105.h,
                          decoration: BoxDecoration(
                            color: AppColors.yellow,
                            borderRadius: BorderRadius.circular(16.r),
                          ),
                          child: Center(
                            child: Text(
                              cancelText,
                              style: const TextStyle(
                                color: Colors.black,
                                fontWeight: FontWeight.w600,
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
            Positioned(
              top: -30,
              child: CircleAvatar(
                radius: 30,
                backgroundColor: Colors.white,
                child: Icon(
                  Icons.warning_amber_rounded,
                  color: Colors.orange,
                  size: 40.sp,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
