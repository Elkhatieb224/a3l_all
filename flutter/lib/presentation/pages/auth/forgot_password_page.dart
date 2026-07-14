import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/data/services/auth_service.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/auth/verify_reset_code_page.dart';
import 'package:a3lnha/presentation/widgets/auth/text_form_with_label.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_button.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class ForgotPasswordPage extends StatefulWidget {
  const ForgotPasswordPage({super.key});

  @override
  State<ForgotPasswordPage> createState() => _ForgotPasswordPageState();
}

class _ForgotPasswordPageState extends State<ForgotPasswordPage> {
  final TextEditingController emailController = TextEditingController();
  bool _isLoading = false;

  Future<void> _sendCode() async {
    final email = emailController.text.trim();

    if (email.isEmpty) {
      showToast(message: AppLocale.tr('email_required'));
      return;
    }

    setState(() => _isLoading = true);

    try {
      final result = await AuthService.sendPasswordResetCode(email: email);

      if (!mounted) return;

      if (result.success) {
        showToast(message: result.message ?? AppLocale.tr('password_reset_code_sent'));
        context.push(VerifyResetCodePage(email: email));
      } else {
        showToast(message: result.message ?? AppLocale.tr('code_send_failed'));
      }
    } catch (e) {
      if (mounted) {
        showToast(message: AppLocale.tr('code_send_failed'));
      }
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  @override
  void dispose() {
    emailController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          AppLocale.tr('forgot_password_title'),
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
                AppLocale.tr('password_reset_request_message'),
                style: TextStyle(
                  fontSize: 14.sp,
                  color: Colors.grey[700],
                  fontWeight: FontWeight.w400,
                ),
                textAlign: TextAlign.center,
              ),
              SizedBox(height: 30.h),
              TextFormWithLabel(
                hintText: 'your@email.com',
                controller: emailController,
                keyboardType: TextInputType.emailAddress,
                obscureText: false,
                labelText: AppLocale.tr('email'),
              ),
              SizedBox(height: 40.h),
              CustomButton(
                text: _isLoading ? AppLocale.tr('sending') : AppLocale.tr('send_code'),
                onTap: _isLoading ? () {} : _sendCode,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
