import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/notifications/fcm_service.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/services/auth_service.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/auth/login_page.dart';
import 'package:a3lnha/presentation/pages/home/home_page.dart';
import 'package:a3lnha/presentation/pages/legal/privacy_page.dart';
import 'package:a3lnha/presentation/pages/legal/terms_page.dart';
import 'package:a3lnha/presentation/widgets/auth/text_form_with_label.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_button.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class RegisterPage extends StatefulWidget {
  RegisterPage({super.key});

  @override
  State<RegisterPage> createState() => _RegisterPageState();
}

class _RegisterPageState extends State<RegisterPage> {
  final TextEditingController nameController = TextEditingController();
  final TextEditingController emailController = TextEditingController();
  final TextEditingController phoneController = TextEditingController();
  final TextEditingController passwordController = TextEditingController();
  final TextEditingController passwordConfirmationController =
      TextEditingController();
  String _phoneCountryCode = 'SY';
  bool _isLoading = false;
  bool _obscurePassword = true;
  bool _obscurePasswordConfirm = true;

  String get _selectedDialCode => _phoneCountryCode == 'TR' ? '+90' : '+963';

  String? _validatePhone() {
    final raw = phoneController.text.replaceAll(RegExp(r'[^\d]'), '');
    if (raw.isEmpty) return null;
    if (_phoneCountryCode == 'TR') {
      if (raw.length != 10 || raw.startsWith('0')) {
        return AppLocale.tr('phone_error_tr_length');
      }
      return null;
    }
    if (raw.length != 9 || raw.startsWith('0')) {
      return AppLocale.tr('phone_error_sy_length');
    }
    return null;
  }

  void _onPhoneCountryChanged(String code) {
    if (_phoneCountryCode == code) return;
    setState(() => _phoneCountryCode = code);
    final maxLen = code == 'TR' ? 10 : 9;
    final digits = phoneController.text.replaceAll(RegExp(r'[^\d]'), '');
    final trimmed = digits.length > maxLen ? digits.substring(0, maxLen) : digits;
    phoneController.value = TextEditingValue(
      text: trimmed,
      selection: TextSelection.collapsed(offset: trimmed.length),
    );
  }

  Future<void> _register() async {
    final name = nameController.text.trim();
    final email = emailController.text.trim();
    final phoneDigits = phoneController.text.replaceAll(RegExp(r'[^\d]'), '');
    final password = passwordController.text;
    final passwordConfirmation = passwordConfirmationController.text;

    if (name.isEmpty) {
      showToast(message: AppLocale.tr('name_required'));
      return;
    }
    if (email.isEmpty) {
      showToast(message: AppLocale.tr('email_required'));
      return;
    }
    if (password.isEmpty) {
      showToast(message: AppLocale.tr('password_required'));
      return;
    }
    if (password.length < 8) {
      showToast(message: AppLocale.tr('password_min'));
      return;
    }
    if (password != passwordConfirmation) {
      showToast(message: AppLocale.tr('password_mismatch'));
      return;
    }
    final phoneError = _validatePhone();
    if (phoneError != null) {
      showToast(message: phoneError);
      return;
    }
    final phone =
        phoneDigits.isEmpty ? null : '$_selectedDialCode$phoneDigits';

    setState(() => _isLoading = true);

    final fcmToken = await FcmService.getToken();
    final result = await AuthService.register(
      name: name,
      email: email,
      password: password,
      passwordConfirmation: passwordConfirmation,
      phone: phone,
      countryCode: _phoneCountryCode,
      fcmToken: fcmToken,
    );

    if (!mounted) return;
    setState(() => _isLoading = false);

    if (result.success) {
      if (fcmToken == null) await FcmService.refreshAndSendToken();
      if (!mounted) return;
      context.pushAndRemoveUntil(const HomePage());
      showToast(message: result.message ?? AppLocale.tr('register_success'));
    } else {
      String? errorMsg = result.message;
      // أولوية العرض: رسالة الـ API نفسها، ثم أول خطأ حقلي عند غياب الرسالة.
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
      showToast(message: errorMsg ?? AppLocale.tr('register_failed'));
    }
  }

  @override
  void dispose() {
    nameController.dispose();
    emailController.dispose();
    phoneController.dispose();
    passwordController.dispose();
    passwordConfirmationController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          AppLocale.tr('register'),
          style: TextStyle(
            color: Colors.black,
            fontSize: 16.sp,
            fontWeight: FontWeight.w500,
          ),
        ),
        leading: IconButton(
          icon: Image.asset("assets/images/Item Left.png"),
          onPressed: () {
            context.pop();
          },
        ),
      ),
      body: SingleChildScrollView(
        child: Padding(
          padding: EdgeInsets.all(20.h),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              // RegisterMethodWidget(
              //   methodName: AppLocale.tr('google'),
              //   imagePath: 'assets/images/google.png',
              // ),
              // SizedBox(height: 15.h),
              // RegisterMethodWidget(
              //   methodName: AppLocale.tr('facebook'),
              //   imagePath: 'assets/images/facebook.png',
              // ),
              // SizedBox(height: 15.h),

              // RegisterMethodWidget(
              //   methodName: AppLocale.tr('apple'),
              //   imagePath: 'assets/images/apple.png',
              // ),
              // SizedBox(height: 20.h),

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
                  //     AppLocale.tr('or'),
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
              TextFormWithLabel(
                hintText: AppLocale.tr('your_name'),
                controller: nameController,
                keyboardType: TextInputType.name,
                obscureText: false,
                labelText: AppLocale.tr('name'),
              ),
              SizedBox(height: 15.h),

              TextFormWithLabel(
                hintText: "your@email.com",
                controller: emailController,
                keyboardType: TextInputType.emailAddress,
                obscureText: false,
                labelText: AppLocale.tr('email'),
              ),
              SizedBox(height: 15.h),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    AppLocale.tr('phone'),
                    style: TextStyle(
                      color: Colors.black,
                      fontSize: 14.sp,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                  SizedBox(height: 6.h),
                  TextFormField(
                    controller: phoneController,
                    keyboardType: TextInputType.phone,
                    inputFormatters: [
                      FilteringTextInputFormatter.digitsOnly,
                      LengthLimitingTextInputFormatter(
                        _phoneCountryCode == 'TR' ? 10 : 9,
                      ),
                    ],
                    onChanged: (_) => setState(() {}),
                    decoration: InputDecoration(
                      hintText: _phoneCountryCode == 'TR' ? '5XXXXXXXXX' : '9XXXXXXXX',
                      filled: true,
                      fillColor: Colors.grey.shade100,
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(10.r),
                        borderSide: BorderSide.none,
                      ),
                      prefixIcon: Padding(
                        padding: EdgeInsetsDirectional.only(start: 6.w),
                        child: DropdownButtonHideUnderline(
                          child: DropdownButton<String>(
                            value: _phoneCountryCode,
                            icon: const Icon(Icons.keyboard_arrow_down_rounded),
                            items: [
                              DropdownMenuItem(
                                value: 'SY',
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    const _SyrianGreenFlagIcon(),
                                    SizedBox(width: 6.w),
                                    const Text('+963'),
                                  ],
                                ),
                              ),
                              DropdownMenuItem(
                                value: 'TR',
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    const Text('🇹🇷'),
                                    SizedBox(width: 6.w),
                                    const Text('+90'),
                                  ],
                                ),
                              ),
                            ],
                            onChanged: (val) {
                              if (val != null) _onPhoneCountryChanged(val);
                            },
                          ),
                        ),
                      ),
                      prefixIconConstraints: BoxConstraints(minWidth: 95.w),
                    ),
                  ),
                ],
              ),
              SizedBox(height: 15.h),

              TextFormWithLabel(
                hintText: AppLocale.tr('password'),
                controller: passwordController,
                keyboardType: TextInputType.visiblePassword,
                obscureText: _obscurePassword,
                labelText: AppLocale.tr('password'),
                suffixIcon: IconButton(
                  onPressed: () => setState(() => _obscurePassword = !_obscurePassword),
                  icon: Icon(
                    _obscurePassword ? Icons.visibility_off_outlined : Icons.visibility_outlined,
                    size: 22.sp,
                    color: Colors.grey.shade600,
                  ),
                ),
              ),
              SizedBox(height: 15.h),
              TextFormWithLabel(
                hintText: AppLocale.tr('password_confirm'),
                controller: passwordConfirmationController,
                keyboardType: TextInputType.visiblePassword,
                obscureText: _obscurePasswordConfirm,
                labelText: AppLocale.tr('password_confirm'),
                suffixIcon: IconButton(
                  onPressed: () => setState(() => _obscurePasswordConfirm = !_obscurePasswordConfirm),
                  icon: Icon(
                    _obscurePasswordConfirm ? Icons.visibility_off_outlined : Icons.visibility_outlined,
                    size: 22.sp,
                    color: Colors.grey.shade600,
                  ),
                ),
              ),
              SizedBox(height: 30.h),
              CustomButton(
                text: _isLoading ? AppLocale.tr('creating_account') : AppLocale.tr('register'),
                onTap: _isLoading ? () {} : _register,
              ),
              SizedBox(height: 20.h),
              Text(
                AppLocale.tr('agree_by_creating'),
                style: TextStyle(
                  fontSize: 12.sp,
                  color: Colors.black,
                  fontWeight: FontWeight.w500,
                ),
              ),
              Wrap(
                alignment: WrapAlignment.center,
                crossAxisAlignment: WrapCrossAlignment.center,
                children: [
                  GestureDetector(
                    onTap: () {
                      context.push(const PrivacyPage());
                    },
                    child: Text(
                      AppLocale.tr('privacy_policy'),
                      style: TextStyle(
                        color: AppColors.lightBlue,
                        fontSize: 12.sp,
                        fontWeight: FontWeight.w500,
                        decoration: TextDecoration.underline,
                      ),
                    ),
                  ),
                  Text(
                    ' ${AppLocale.tr('and')} ',
                    style: TextStyle(
                      color: Colors.black,
                      fontSize: 12.sp,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                  GestureDetector(
                    onTap: () {
                      context.push(const TermsPage());
                    },
                    child: Text(
                      AppLocale.tr('terms_conditions'),
                      style: TextStyle(
                        color: AppColors.lightBlue,
                        fontSize: 12.sp,
                        fontWeight: FontWeight.w500,
                        decoration: TextDecoration.underline,
                      ),
                    ),
                  ),
                ],
              ),
              SizedBox(height: 20.h),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  GestureDetector(
                    onTap: () {
                      context.pushAndRemoveUntil(LoginPage());
                    },
                    child: Text(
                      AppLocale.tr('login'),
                      style: TextStyle(
                        color: AppColors.lightBlue,
                        fontSize: 12.sp,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ),
                  Text(
                    AppLocale.tr('already_have_account'),
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
    );
  }
}

class RegisterMethodWidget extends StatelessWidget {
  final String methodName;
  final String imagePath;
  const RegisterMethodWidget({
    super.key,
    required this.methodName,
    required this.imagePath,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      height: 40.h,
      decoration: BoxDecoration(
        color: Colors.grey[200],
        borderRadius: BorderRadius.circular(8.r),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Text(
            '${AppLocale.tr('connect_with')} $methodName',
            style: TextStyle(fontSize: 14.sp, fontWeight: FontWeight.w600),
          ),
          SizedBox(width: 6.w),
          Image.asset(imagePath, height: 20.h, width: 20.w),
        ],
      ),
    );
  }
}

class _SyrianGreenFlagIcon extends StatelessWidget {
  const _SyrianGreenFlagIcon();

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 18,
      height: 12,
      decoration: BoxDecoration(
        border: Border.all(color: Colors.black12, width: 0.6),
        borderRadius: BorderRadius.circular(1.5),
      ),
      clipBehavior: Clip.hardEdge,
      child: Column(
        children: [
          Expanded(child: Container(color: const Color(0xFF1FA34A))),
          Expanded(
            child: Container(
              color: Colors.white,
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                children: const [
                  Icon(Icons.star, size: 5, color: Color(0xFFCC2E3B)),
                  Icon(Icons.star, size: 5, color: Color(0xFFCC2E3B)),
                  Icon(Icons.star, size: 5, color: Color(0xFFCC2E3B)),
                ],
              ),
            ),
          ),
          Expanded(child: Container(color: Colors.black)),
        ],
      ),
    );
  }
}
