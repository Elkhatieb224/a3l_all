import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/services/user_service.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/home/home_page.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_button.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:flutter_otp_text_field/flutter_otp_text_field.dart';

class VertifyEmail extends StatefulWidget {
  final String? email;

  const VertifyEmail({super.key, this.email});

  @override
  State<VertifyEmail> createState() => _VertifyEmailState();
}

class _VertifyEmailState extends State<VertifyEmail> {
  String _code = '';
  String? _userEmail;
  bool _loading = true;
  bool _verifying = false;
  bool _sending = false;

  @override
  void initState() {
    super.initState();
    _loadUser();
  }

  Future<void> _loadUser() async {
    final user = await UserService.getUser();
    if (mounted) {
      setState(() {
        _userEmail = widget.email ?? user?.email;
        _loading = false;
      });
    }
  }

  Future<void> _verify() async {
    if (_code.length != 6) {
      showToast(message: AppLocale.tr('verify_code_required'));
      return;
    }
    setState(() => _verifying = true);
    final res = await UserService.verifyEmailCode(code: _code);
    if (!mounted) return;
    setState(() => _verifying = false);
    showToast(message: res['message'] as String? ?? '');
    if (res['success'] == true) {
      context.pushAndRemoveUntil(HomePage());
    }
  }

  Future<void> _resend() async {
    setState(() => _sending = true);
    final res = await UserService.sendEmailVerificationCode();
    if (!mounted) return;
    setState(() => _sending = false);
    showToast(message: res['message'] as String? ?? '');
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(AppLocale.tr('verify_email')),
        leading: IconButton(
          icon: Image.asset("assets/images/Item Left.png"),
          onPressed: () => context.pop(),
        ),
      ),
      body: _loading
          ? Center(child: CircularProgressIndicator(color: AppColors.darkBlue))
          : SingleChildScrollView(
              child: Padding(
                padding: EdgeInsets.all(20.h),
                child: Column(
                  children: [
                    Text(
                      '${AppLocale.tr('verify_code_sent_to')} ${_userEmail ?? AppLocale.tr('your_email')}',
                      style: TextStyle(fontSize: 14.sp, fontWeight: FontWeight.w500),
                      textAlign: TextAlign.center,
                    ),
                    SizedBox(height: 25.h),
                    OtpTextField(
                      numberOfFields: 6,
                      fieldHeight: 50.h,
                      borderColor: Colors.grey[300]!,
                      focusedBorderColor: AppColors.darkBlue,
                      showFieldAsBox: true,
                      borderRadius: BorderRadius.circular(12.r),
                      fieldWidth: 45.w,
                      onCodeChanged: (value) => _code = value,
                      onSubmit: (value) => _code = value,
                    ),
                    SizedBox(height: 32.h),
                    CustomButton(
                      text: _verifying ? AppLocale.tr('verifying') : AppLocale.tr('confirm'),
                      onTap: _verifying ? () {} : _verify,
                      backgroundColor: AppColors.darkBlue,
                      textColor: Colors.white,
                    ),
                    SizedBox(height: 25.h),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(AppLocale.tr('didnt_receive_code')),
                        TextButton(
                          onPressed: _sending ? null : _resend,
                          child: Text(
                            _sending ? AppLocale.tr('resending') : AppLocale.tr('resend'),
                            style: TextStyle(color: AppColors.lightBlue),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
    );
  }
}
