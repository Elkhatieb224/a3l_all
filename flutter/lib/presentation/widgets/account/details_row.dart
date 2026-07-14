import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class DetailsRow extends StatelessWidget {
  final String title;
  final String value;
  final String? imageUrl;
  final IconData? icon;

  const DetailsRow({
    super.key,
    required this.title,
    required this.value,
    this.imageUrl,
    this.icon,
  }) : assert(imageUrl != null || icon != null, 'Provide either imageUrl or icon');

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        Expanded(
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              if (icon != null)
                Icon(icon!, size: 14.sp, color: Colors.grey[700])
              else
                Image.asset(imageUrl!, width: 10.w, height: 10.h),
              SizedBox(width: 8.w),
              Flexible(
                child: Text(
                  title,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    height: 1,
                    color: Colors.black,
                    fontSize: 10.sp,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ),
            ],
          ),
        ),
        SizedBox(width: 8.w),
        SizedBox(
          width: 60.w, // نفس العرض لكل الأرقام/التواريخ مع منع النزول لسطر جديد
          child: Text(
            value,
            textAlign: TextAlign.center,
            maxLines: 1,
            overflow: TextOverflow.clip,
            style: TextStyle(
              height: 1.2,
              color: Colors.black,
              fontSize: 10.sp,
              fontWeight: FontWeight.w500,
            ),
          ),
        ),
      ],
    );
  }
}
