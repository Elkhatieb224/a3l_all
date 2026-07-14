import 'package:a3lnha/core/styles/colors.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class CustomButton extends StatelessWidget {
  final String text;
  final VoidCallback onTap;
  final Color backgroundColor;
  final Color textColor;
  CustomButton({
    super.key,
    required this.text,
    required this.onTap,
    Color? backgroundColor,
    this.textColor = Colors.black,
  }) : backgroundColor = backgroundColor ?? AppColors.yellow;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: backgroundColor,
      borderRadius: BorderRadius.circular(8.r),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(8.r),
        splashColor: textColor.withValues(alpha: 0.2),
        highlightColor: textColor.withValues(alpha: 0.1),
        child: Container(
          width: double.infinity,
          height: 45.h,
          alignment: Alignment.center,
          child: Text(
            text,
            style: TextStyle(
              color: textColor,
              fontSize: 14.sp,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
      ),
    );
  }
}
