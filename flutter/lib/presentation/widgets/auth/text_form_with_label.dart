import 'package:a3lnha/presentation/widgets/shared/custom_text_form_field.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class TextFormWithLabel extends StatelessWidget {
  const TextFormWithLabel({
    super.key,
    required this.hintText,
    required this.controller,
    required this.keyboardType,
    required this.obscureText,
    this.suffixIcon,
    required this.labelText,
    this.forgetPasswordButton,
    this.maxlines = 1,
    this.autofillHints,
    this.inputFormatters,
  });
  final String labelText;
  final String hintText;
  final TextEditingController controller;
  final TextInputType keyboardType;
  final bool obscureText;
  final Widget? suffixIcon;
  final Widget? forgetPasswordButton;
  final int maxlines;
  final Iterable<String>? autofillHints;
  final List<TextInputFormatter>? inputFormatters;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              labelText,
              style: TextStyle(fontSize: 12.sp, fontWeight: FontWeight.w500),
            ),
            forgetPasswordButton ?? SizedBox(),
          ],
        ),
        SizedBox(height: 8.h),
        CustomTextFormField(
          maxLines: maxlines,
          hintText: hintText,
          controller: controller,
          keyboardType: keyboardType,
          obscureText: obscureText,
          suffixIcon: suffixIcon,
          autofillHints: autofillHints,
          inputFormatters: inputFormatters,
        ),
      ],
    );
  }
}
