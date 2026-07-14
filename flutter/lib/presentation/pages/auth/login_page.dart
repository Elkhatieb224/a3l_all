import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/performance/startup_warmup.dart';
import 'package:a3lnha/core/notifications/fcm_service.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/services/auth_service.dart';
import 'package:a3lnha/helpers/cache_helper.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/auth/forgot_password_page.dart';
import 'package:a3lnha/presentation/pages/auth/register_page.dart';
import 'package:a3lnha/presentation/pages/home/home_page.dart';
import 'package:a3lnha/presentation/widgets/auth/text_form_with_label.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_button.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class LoginPage extends StatefulWidget {
  LoginPage({super.key});

  @override
  State<LoginPage> createState() => _LoginPageState();
}

const String _savedEmailKey = 'saved_login_email';
const String _savedPasswordKey = 'saved_login_password';

class _LoginPageState extends State<LoginPage> {
  final TextEditingController emailController = TextEditingController();
  final TextEditingController passwordController = TextEditingController();
  bool _isLoading = false;
  bool _rememberMe = true;
  bool _obscurePassword = true;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _loadSavedCredentials();
    emailController.addListener(_clearError);
    passwordController.addListener(_clearError);
  }

  void _clearError() {
    if (_errorMessage != null && mounted) {
      setState(() => _errorMessage = null);
    }
  }

  Future<void> _loadSavedCredentials() async {
    try {
      final email = CacheHelper.getData(key: _savedEmailKey);
      final password = CacheHelper.getData(key: _savedPasswordKey);
      if (!mounted) return;
      var updated = false;
      if (email is String && email.isNotEmpty) {
        emailController.text = email;
        _rememberMe = true;
        updated = true;
      }
      if (password is String && password.isNotEmpty) {
        passwordController.text = password;
        updated = true;
      }
      if (updated) setState(() {});
    } catch (_) {}
  }

  Future<void> _saveCredentials(String email, String password) async {
    try {
      await CacheHelper.saveData(key: _savedEmailKey, value: email);
      await CacheHelper.saveData(key: _savedPasswordKey, value: password);
    } catch (_) {}
  }

  Future<void> _clearSavedCredentials() async {
    try {
      await CacheHelper.removeData(key: _savedEmailKey);
      await CacheHelper.removeData(key: _savedPasswordKey);
    } catch (_) {}
  }

  Future<void> _login() async {
    final email = emailController.text.trim();
    final password = passwordController.text;

    if (email.isEmpty) {
      setState(() => _errorMessage = AppLocale.tr('email_required'));
      showToast(message: AppLocale.tr('email_required'));
      return;
    }
    if (password.isEmpty) {
      setState(() => _errorMessage = AppLocale.tr('password_required'));
      showToast(message: AppLocale.tr('password_required'));
      return;
    }

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      String? fcmToken;
      try {
        fcmToken = await FcmService.getToken().timeout(
          const Duration(seconds: 5),
          onTimeout: () => null,
        );
      } catch (_) {
        fcmToken = null;
      }

      final result = await AuthService.login(
        email: email,
        password: password,
        fcmToken: fcmToken,
      );

      if (!mounted) return;

      if (result.success) {
        if (_rememberMe) {
          await _saveCredentials(email, password);
        } else {
          await _clearSavedCredentials();
        }
        try {
          if (fcmToken == null) await FcmService.refreshAndSendToken();
        } catch (_) {}
        StartupWarmup.runAfterLogin();
        if (!mounted) return;
        context.pushAndRemoveUntil(const HomePage());
        showToast(message: AppLocale.tr('login_success'));
      } else {
        String? errorMsg = result.message;
        if ((errorMsg == null || errorMsg.trim().isEmpty) &&
            result.errors != null &&
            result.errors!.isNotEmpty) {
          final firstError = result.errors!.values.first;
          if (firstError is List && firstError.isNotEmpty) {
            errorMsg = firstError.first.toString();
          } else if (firstError != null) {
            errorMsg = firstError.toString();
          }
        }
        // السيرفر يرجع الرسالة مترجمة حسب هيدر اللغة؛ نظهرها مباشرة
        final msg = (errorMsg != null && errorMsg.trim().isNotEmpty)
            ? errorMsg
            : AppLocale.tr('login_failed');
        setState(() => _errorMessage = msg);
        showToast(message: msg);
      }
    } catch (e, st) {
      if (mounted) {
        final msg = AppLocale.tr('login_failed');
        setState(() => _errorMessage = msg);
        showToast(message: msg);
      }
      if (kDebugMode) {
        debugPrint('Login error: $e\n$st');
      }
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  @override
  void dispose() {
    emailController.removeListener(_clearError);
    passwordController.removeListener(_clearError);
    emailController.dispose();
    passwordController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          AppLocale.tr('login'),
          style: TextStyle(
            color: Colors.black,
            fontSize: 16.sp,
            fontWeight: FontWeight.w500,
          ),
        ),
      ),

      body: AutofillGroup(
        child: SingleChildScrollView(
          child: Padding(
            padding: EdgeInsets.all(20.h),
            child: Column(
              children: [
                TextFormWithLabel(
                  hintText: 'your@email.com',
                  controller: emailController,
                  keyboardType: TextInputType.emailAddress,
                  obscureText: false,
                  labelText: AppLocale.tr('email'),
                  autofillHints: const [AutofillHints.email],
                ),
                SizedBox(height: 30.h),
                TextFormWithLabel(
                  hintText: AppLocale.tr('password'),
                  controller: passwordController,
                  keyboardType: TextInputType.visiblePassword,
                  obscureText: _obscurePassword,
                  labelText: AppLocale.tr('password'),
                  autofillHints: const [AutofillHints.password],
                  forgetPasswordButton: GestureDetector(
                    onTap: () => context.push(const ForgotPasswordPage()),
                    child: Text(
                      AppLocale.tr('forgot_password'),
                      style: TextStyle(
                        color: AppColors.lightBlue,
                        fontSize: 12.sp,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ),
                  suffixIcon: IconButton(
                    onPressed: () => setState(() => _obscurePassword = !_obscurePassword),
                    icon: Icon(
                      _obscurePassword ? Icons.visibility_off_outlined : Icons.visibility_outlined,
                      size: 22.sp,
                      color: Colors.grey.shade600,
                    ),
                  ),
                ),
                SizedBox(height: 16.h),
                Row(
                  children: [
                    SizedBox(
                      width: 24.w,
                      height: 24.w,
                      child: Checkbox(
                        value: _rememberMe,
                        onChanged: (v) => setState(() => _rememberMe = v ?? true),
                        activeColor: AppColors.darkBlue,
                        materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                      ),
                    ),
                    SizedBox(width: 8.w),
                    GestureDetector(
                      onTap: () => setState(() => _rememberMe = !_rememberMe),
                      child: Text(
                        AppLocale.tr('remember_me'),
                        style: TextStyle(
                          fontSize: 13.sp,
                          fontWeight: FontWeight.w500,
                          color: Colors.black87,
                        ),
                      ),
                    ),
                  ],
                ),
                SizedBox(height: 40.h),
              CustomButton(
                text: _isLoading ? AppLocale.tr('logging_in') : AppLocale.tr('login'),
                onTap: _isLoading ? () {} : _login,
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
              SizedBox(height: 25.h),
              Row(
                children: [
                  Expanded(
                    child: Container(
                      width: double.infinity,
                      color: Colors.grey[300],
                      height: 1.h,
                    ),
                  ),
                  // Padding(
                  //   padding: EdgeInsets.symmetric(horizontal: 8.0.w),
                  //   child: Text(
                  //     AppLocale.tr('or_continue_with'),
                  //     style: TextStyle(
                  //       fontSize: 10.sp,
                  //       color: Colors.grey[500],
                  //       fontWeight: FontWeight.w500,
                  //     ),
                  //   ),
                  // ),
                  Expanded(
                    child: Container(
                      width: double.infinity,
                      color: Colors.grey[300],
                      height: 1.h,
                    ),
                  ),
                ],
              ),
              SizedBox(height: 30.h),
              // Row(
              //   mainAxisAlignment: MainAxisAlignment.center,
              //   children: [
              //     SocialMehtodIcon(imagePath: "assets/images/google.png"),
              //     SizedBox(width: 16.w),
              //     SocialMehtodIcon(imagePath: "assets/images/facebook.png"),
              //     SizedBox(width: 16.w),
              //     SocialMehtodIcon(imagePath: "assets/images/apple.png"),
              //   ],
              // ),
              SizedBox(height: 25.h),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  GestureDetector(
                    onTap: () {
                      context.push(RegisterPage());
                    },
                    child: Text(
                      AppLocale.tr('register'),
                      style: TextStyle(
                        color: AppColors.lightBlue,
                        fontSize: 12.sp,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ),
                  SizedBox(width: 5.w),
                  Text(
                    AppLocale.tr('not_have_account'),
                    style: TextStyle(
                      color: Colors.black,
                      fontSize: 12.sp,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    ),
    );
  }
}

class SocialMehtodIcon extends StatelessWidget {
  final String imagePath;
  const SocialMehtodIcon({super.key, required this.imagePath});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 50.w,
      height: 50.w,
      decoration: BoxDecoration(
        color: Colors.grey[200],
        borderRadius: BorderRadius.circular(8.r),
      ),
      child: Center(
        child: Image.asset(imagePath, height: 24.h, width: 24.w),
      ),
    );
  }
}
