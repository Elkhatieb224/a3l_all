import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/data/services/auth_service.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/auth/login_page.dart';
import 'package:a3lnha/presentation/widgets/auth/text_form_with_label.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_button.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class ResetPasswordPage extends StatefulWidget {
  final String email;
  final String code;

  const ResetPasswordPage({super.key, required this.email, required this.code});

  @override
  State<ResetPasswordPage> createState() => _ResetPasswordPageState();
}

class _ResetPasswordPageState extends State<ResetPasswordPage> {
  final TextEditingController passwordController = TextEditingController();
  final TextEditingController confirmPasswordController = TextEditingController();
  bool _isLoading = false;
  bool _obscurePassword = true;
  bool _obscureConfirm = true;

  Future<void> _resetPassword() async {
    final password = passwordController.text;
    final confirmPassword = confirmPasswordController.text;

    if (password.isEmpty) {
      showToast(message: AppLocale.tr('password_required'));
      return;
    }
    if (password.length < 8) {
      showToast(message: AppLocale.tr('password_min'));
      return;
    }
    if (password != confirmPassword) {
      showToast(message: AppLocale.tr('password_mismatch'));
      return;
    }

    setState(() => _isLoading = true);

    try {
      final result = await AuthService.resetPassword(
        email: widget.email,
        code: widget.code,
        password: password,
        passwordConfirmation: confirmPassword,
      );

      if (!mounted) return;

      if (result.success) {
        showToast(message: result.message ?? AppLocale.tr('password_reset_success'));
        context.pushAndRemoveUntil(LoginPage());
      } else {
        showToast(message: result.message ?? AppLocale.tr('password_change_failed'));
      }
    } catch (e) {
      if (mounted) {
        showToast(message: AppLocale.tr('password_change_failed'));
      }
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  @override
  void dispose() {
    passwordController.dispose();
    confirmPasswordController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          AppLocale.tr('password_reset_new_title'),
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
                AppLocale.tr('password_reset_new_message'),
                style: TextStyle(
                  fontSize: 14.sp,
                  color: Colors.grey[700],
                  fontWeight: FontWeight.w400,
                ),
                textAlign: TextAlign.center,
              ),
              SizedBox(height: 30.h),
              TextFormWithLabel(
                hintText: AppLocale.tr('password'),
                controller: passwordController,
                keyboardType: TextInputType.visiblePassword,
                obscureText: _obscurePassword,
                labelText: AppLocale.tr('new_password'),
                suffixIcon: IconButton(
                  onPressed: () => setState(() => _obscurePassword = !_obscurePassword),
                  icon: Icon(
                    _obscurePassword ? Icons.visibility_off_outlined : Icons.visibility_outlined,
                    size: 22.sp,
                    color: Colors.grey.shade600,
                  ),
                ),
              ),
              SizedBox(height: 20.h),
              TextFormWithLabel(
                hintText: AppLocale.tr('confirm_new_password'),
                controller: confirmPasswordController,
                keyboardType: TextInputType.visiblePassword,
                obscureText: _obscureConfirm,
                labelText: AppLocale.tr('confirm_new_password'),
                suffixIcon: IconButton(
                  onPressed: () => setState(() => _obscureConfirm = !_obscureConfirm),
                  icon: Icon(
                    _obscureConfirm ? Icons.visibility_off_outlined : Icons.visibility_outlined,
                    size: 22.sp,
                    color: Colors.grey.shade600,
                  ),
                ),
              ),
              SizedBox(height: 40.h),
              CustomButton(
                text: _isLoading ? AppLocale.tr('resetting') : AppLocale.tr('reset_password'),
                onTap: _isLoading ? () {} : _resetPassword,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
