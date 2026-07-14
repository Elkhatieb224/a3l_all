import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

// ignore: must_be_immutable
class CustomAppbar extends StatelessWidget implements PreferredSizeWidget {
  final String title;
  final bool isChat;
  VoidCallback? onTap;
  /// حجم خط العنوان (اختياري) لتخصيص شريط التطبيق حسب الشاشة.
  final double? titleFontSize;
  /// عدد أسطر العنوان (افتراضي 1 لتجنب تضخيم ارتفاع الـ AppBar).
  final int titleMaxLines;
  /// عند التحديد، يُستدعى بدل context.pop() عند الضغط على زر الرجوع
  final VoidCallback? onBackPressed;
  /// في الدردشة: عند التحديد يظهر زر القائمة (إبلاغ/حظر) على اليمين، وسهم العودة يرجع للخلف
  final VoidCallback? onChatMenuTap;
  /// عند التحديد (وليس وضع الدردشة)، تُستخدم كأزرار يمين شريط التطبيق بدل السهم الافتراضي
  final List<Widget>? actions;
  CustomAppbar({
    super.key,
    required this.title,
    this.isChat = false,
    this.onTap,
    this.titleFontSize,
    this.titleMaxLines = 1,
    this.onBackPressed,
    this.onChatMenuTap,
    this.actions,
  });

  @override
  Size get preferredSize => Size.fromHeight(kToolbarHeight);

  @override
  Widget build(BuildContext context) {
    final inChatWithMenu = isChat && onChatMenuTap != null;
    return AppBar(
      backgroundColor: AppColors.darkBlue,
      title: Text(
        title,
        style: TextStyle(
          color: Colors.white,
          fontSize: titleFontSize ?? 18,
          fontWeight: FontWeight.w600,
        ),
        maxLines: titleMaxLines,
        overflow: TextOverflow.ellipsis,
      ),
      leading: IconButton(
        onPressed: () {
          if (inChatWithMenu) {
            context.pop();
            return;
          }
          if (onBackPressed != null && !isChat) {
            onBackPressed!();
            return;
          }
          if (onTap != null) {
            onTap!();
            return;
          }
          context.pop();
        },
        icon: Icon(Icons.arrow_back_ios, color: Colors.white, size: 20.sp),
      ),
      actions: actions != null && !isChat
          ? actions!
          : [
              IconButton(
                onPressed: () {
                  if (inChatWithMenu) {
                    onChatMenuTap!();
                    return;
                  }
                  if (isChat) return;
                  if (onBackPressed != null) {
                    onBackPressed!();
                  } else {
                    context.pop();
                  }
                },
                icon: inChatWithMenu
                    ? Icon(Icons.more_vert, color: Colors.white, size: 24.sp)
                    : (isChat
                        ? Image.asset("assets/images/call.png", width: 32.w, height: 32.h)
                        : Icon(Icons.arrow_forward_ios, color: Colors.white, size: 18.sp)),
              ),
            ],
    );
  }
}
