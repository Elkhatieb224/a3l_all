import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/services/user_service.dart';
import 'package:a3lnha/presentation/widgets/auth/text_form_with_label.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_button.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:flutter/material.dart';
import 'package:flutter_otp_text_field/flutter_otp_text_field.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

/// تغيير البريد الإلكتروني: إدخال البريد الجديد ثم التحقق بالرمز المُرسل إليه.
class ChangeEmailPage extends StatefulWidget {
  const ChangeEmailPage({super.key});

  @override
  State<ChangeEmailPage> createState() => _ChangeEmailPageState();
}

class _ChangeEmailPageState extends State<ChangeEmailPage> {
  final TextEditingController _emailController = TextEditingController();
  String _pendingEmail = '';
  String _code = '';
  bool _sending = false;
  bool _verifying = false;

  @override
  void dispose() {
    _emailController.dispose();
    super.dispose();
  }

  Future<void> _sendCode() async {
    final email = _emailController.text.trim();
    if (email.isEmpty) {
      showToast(message: AppLocale.tr('email_required'));
      return;
    }
    setState(() => _sending = true);
    final res = await UserService.requestEmailChange(newEmail: email);
    if (!mounted) return;
    setState(() => _sending = false);
    showToast(message: res['message'] as String? ?? '');
    if (res['success'] == true) {
      setState(() => _pendingEmail = email);
    }
  }

  Future<void> _verifyAndChange() async {
    if (_code.length != 6) {
      showToast(message: AppLocale.tr('verify_code_required'));
      return;
    }
    setState(() => _verifying = true);
    final res = await UserService.verifyEmailChangeCode(code: _code);
    if (!mounted) return;
    setState(() => _verifying = false);
    showToast(message: res['message'] as String? ?? '');
    if (res['success'] == true) {
      if (mounted) Navigator.of(context).maybePop(true);
    }
  }

  @override
  Widget build(BuildContext context) {
    final showCodeStep = _pendingEmail.isNotEmpty;

    return Scaffold(
      appBar: CustomAppbar(title: AppLocale.tr('change_my_email')),
      body: SingleChildScrollView(
        padding: EdgeInsets.all(20.w),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            if (!showCodeStep) ...[
              TextFormWithLabel(
                labelText: AppLocale.tr('new_email'),
                hintText: 'your@email.com',
                controller: _emailController,
                keyboardType: TextInputType.emailAddress,
                obscureText: false,
              ),
              SizedBox(height: 24.h),
              CustomButton(
                text: _sending ? AppLocale.tr('resending') : AppLocale.tr('send_code'),
                onTap: _sending ? () {} : _sendCode,
              ),
            ] else ...[
              Text(
                '${AppLocale.tr('change_email_verify_message')} $_pendingEmail',
                style: TextStyle(fontSize: 14.sp, fontWeight: FontWeight.w500),
                textAlign: TextAlign.center,
              ),
              SizedBox(height: 24.h),
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
              SizedBox(height: 24.h),
              CustomButton(
                text: _verifying ? AppLocale.tr('verifying') : AppLocale.tr('confirm'),
                onTap: _verifying ? () {} : _verifyAndChange,
              ),
              SizedBox(height: 16.h),
              TextButton(
                onPressed: () => setState(() => _pendingEmail = ''),
                child: Text(AppLocale.tr('back')),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
