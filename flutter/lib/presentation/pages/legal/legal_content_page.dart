import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class LegalContentPage extends StatelessWidget {
  const LegalContentPage({
    super.key,
    required this.title,
    required this.subtitle,
    required this.content,
  });

  final String title;
  final String subtitle;
  final String? content;

  String _stripHtml(String html) {
    return html
        .replaceAll(RegExp(r'<br\s*/?>'), '\n')
        .replaceAll(RegExp(r'</p>\s*<p>'), '\n\n')
        .replaceAll(RegExp(r'</?p>'), '\n')
        .replaceAll(RegExp(r'</?h[1-6]>'), '\n')
        .replaceAll(RegExp(r'<[^>]*>'), ' ')
        .replaceAll(RegExp(r'&nbsp;'), ' ')
        .replaceAll(RegExp(r'&amp;'), '&')
        .replaceAll(RegExp(r'&lt;'), '<')
        .replaceAll(RegExp(r'&gt;'), '>')
        .replaceAll(RegExp(r'&quot;'), '"')
        .replaceAll(RegExp(r'\n\s*\n\s*\n'), '\n\n')
        .replaceAll(RegExp(r'\s+'), ' ')
        .trim();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: title),
      body: Column(
        children: [
          Container(
            width: double.infinity,
            padding: EdgeInsets.symmetric(horizontal: 20.w, vertical: 24.h),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [
                  AppColors.darkBlue,
                  AppColors.darkBlue.withValues(alpha: 0.85),
                ],
                begin: Alignment.centerLeft,
                end: Alignment.centerRight,
              ),
            ),
            child: Column(
              children: [
                Text(
                  title,
                  style: TextStyle(
                    fontSize: 22.sp,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                  textAlign: TextAlign.center,
                ),
                SizedBox(height: 8.h),
                Text(
                  subtitle,
                  style: TextStyle(
                    fontSize: 14.sp,
                    color: Colors.white70,
                  ),
                  textAlign: TextAlign.center,
                ),
              ],
            ),
          ),
          Expanded(
            child: content != null && content!.trim().isNotEmpty
                ? SingleChildScrollView(
                    physics: const BouncingScrollPhysics(),
                    padding: EdgeInsets.all(20.w),
                    child: SelectableText(
                      _stripHtml(content!),
                      style: TextStyle(
                        fontSize: 15.sp,
                        height: 1.8,
                        color: Colors.grey[800],
                      ),
                    ),
                  )
                : Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          Icons.description_outlined,
                          size: 64.sp,
                          color: Colors.grey[300],
                        ),
                        SizedBox(height: 16.h),
                        Text(
                          AppLocale.tr('no_content'),
                          style: TextStyle(
                            fontSize: 16.sp,
                            color: Colors.grey[500],
                          ),
                        ),
                      ],
                    ),
                  ),
          ),
        ],
      ),
    );
  }
}
