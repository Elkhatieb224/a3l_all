import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/auth/reset_password_page.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_button.dart';
import 'package:flutter/material.dart';
import 'package:flutter_otp_text_field/flutter_otp_text_field.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class VerifyResetCodePage extends StatefulWidget {
  final String email;

  const VerifyResetCodePage({super.key, required this.email});

  @override
  State<VerifyResetCodePage> createState() => _VerifyResetCodePageState();
}

class _VerifyResetCodePageState extends State<VerifyResetCodePage> {
  String _code = '';
  String? _errorMessage;
  bool _isSubmitting = false;

  void _verifyAndContinue() {
    if (_code.length != 6) {
      setState(() {
        _errorMessage = AppLocale.tr('verify_code_required');
      });
      return;
    }
    setState(() {
      _errorMessage = null;
      _isSubmitting = true;
    });
    // التحقق الفعلي من الرمز يتم عند استدعاء resetPassword في الصفحة التالية.
    context.push(ResetPasswordPage(email: widget.email, code: _code)).whenComplete(() {
      if (mounted) {
        setState(() {
          _isSubmitting = false;
        });
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          AppLocale.tr('password_reset_code_title'),
          style: TextStyle(
            color: Colors.black,
            fontSize: 16.sp,
            fontWeight: FontWeight.w500,
          ),
        ),
      ),
      body: SingleChildScrollView(
        child: Padding(
          padding: EdgeInsets.all(20.h),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              SizedBox(height: 20.h),
              Text(
                '${AppLocale.tr('password_reset_code_message')}\n${widget.email}',
                style: TextStyle(
                  fontSize: 14.sp,
                  color: Colors.grey[700],
                  fontWeight: FontWeight.w400,
                ),
                textAlign: TextAlign.center,
              ),
              SizedBox(height: 30.h),
              Directionality(
                textDirection: TextDirection.ltr,
                child: OtpTextField(
                  numberOfFields: 6,
                  fieldHeight: 50.h,
                  borderColor: Colors.grey[300]!,
                  focusedBorderColor: AppColors.darkBlue,
                  showFieldAsBox: true,
                  borderRadius: BorderRadius.circular(12.r),
                  fieldWidth: 45.w,
                  keyboardType: TextInputType.number,
                  onCodeChanged: (value) {
                    setState(() {
                      _code = value;
                      if (_errorMessage != null && value.length <= 6) {
                        _errorMessage = null;
                      }
                    });
                  },
                  onSubmit: (value) {
                    setState(() => _code = value);
                    if (value.length == 6) _verifyAndContinue();
                  },
                ),
              ),
              if (_errorMessage != null && _errorMessage!.isNotEmpty) ...[
                SizedBox(height: 12.h),
                Text(
                  _errorMessage!,
                  style: TextStyle(
                    fontSize: 13.sp,
                    color: Colors.red.shade700,
                    fontWeight: FontWeight.w500,
                  ),
                  textAlign: TextAlign.center,
                ),
              ],
              SizedBox(height: 40.h),
              CustomButton(
                text: _isSubmitting ? AppLocale.tr('loading') : AppLocale.tr('confirm'),
                onTap: (_code.length == 6 && !_isSubmitting) ? _verifyAndContinue : () {},
              ),
            ],
          ),
        ),
      ),
    );
  }
}
