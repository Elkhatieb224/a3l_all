import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class CustomTextFormField extends StatelessWidget {
  final String hintText;
  final TextEditingController controller;
  final TextInputType keyboardType;
  final bool obscureText;
  final Widget? suffixIcon;
  final int maxLines;
  final Widget? prefixIcon;
  final void Function(String)? onFieldSubmitted;
  final Iterable<String>? autofillHints;
  final List<TextInputFormatter>? inputFormatters;
  final FocusNode? focusNode;
  const CustomTextFormField({
    super.key,
    required this.hintText,
    required this.controller,
    required this.keyboardType,
    required this.obscureText,
    this.suffixIcon,
    this.maxLines = 1,
    this.prefixIcon,
    this.onFieldSubmitted,
    this.autofillHints,
    this.inputFormatters,
    this.focusNode,
  });

  @override
  Widget build(BuildContext context) {
    return TextFormField(
      focusNode: focusNode,
      maxLines: maxLines,
      controller: controller,
      keyboardType: keyboardType,
      obscureText: obscureText,
      inputFormatters: inputFormatters,
      onFieldSubmitted: onFieldSubmitted,
      autofillHints: autofillHints?.toList(),
      style: TextStyle(fontSize: 14.sp, fontWeight: FontWeight.w400),
      decoration: InputDecoration(
        hintText: hintText,
        contentPadding: EdgeInsets.symmetric(vertical: 12.h, horizontal: 12.w),
        hintStyle: TextStyle(fontSize: 14.sp, color: Colors.grey),
        fillColor: Colors.transparent,
        filled: true,
        suffixIcon: suffixIcon,
        prefixIcon: prefixIcon,
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8.r),
          borderSide: BorderSide(
            // ignore: deprecated_member_use
            color: Colors.grey.withOpacity(0.5),
            width: 1.1.w,
          ),
        ),

        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8.r),
          borderSide: BorderSide(
            // ignore: deprecated_member_use
            color: Colors.grey.withOpacity(0.5),
            width: 1.1.w,
          ),
        ),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8.r),
          borderSide: BorderSide(color: Colors.grey, width: 0.5.w),
        ),
      ),
    );
  }
}
