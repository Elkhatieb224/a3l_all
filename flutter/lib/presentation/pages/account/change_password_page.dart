import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/data/services/user_service.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/account/my_products_deals_page.dart';
import 'package:a3lnha/presentation/widgets/auth/text_form_with_label.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_button.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class ChangePasswordPage extends StatefulWidget {
  const ChangePasswordPage({super.key});

  @override
  State<ChangePasswordPage> createState() => _ChangePasswordPageState();
}

class _ChangePasswordPageState extends State<ChangePasswordPage> {
  final TextEditingController _oldController = TextEditingController();
  final TextEditingController _newController = TextEditingController();
  final TextEditingController _confirmController = TextEditingController();
  bool _loading = false;
  bool _obscureOld = true;
  bool _obscureNew = true;
  bool _obscureConfirm = true;

  @override
  void dispose() {
    _oldController.dispose();
    _newController.dispose();
    _confirmController.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    final old = _oldController.text.trim();
    final newPwd = _newController.text.trim();
    final confirm = _confirmController.text.trim();
    if (old.isEmpty) {
      showToast(message: AppLocale.tr('current_password_required'));
      return;
    }
    if (newPwd.length < 8) {
      showToast(message: AppLocale.tr('password_min'));
      return;
    }
    if (newPwd != confirm) {
      showToast(message: AppLocale.tr('password_mismatch'));
      return;
    }
    setState(() => _loading = true);
    final res = await UserService.updatePassword(
      currentPassword: old,
      password: newPwd,
      passwordConfirmation: confirm,
    );
    if (mounted) {
      setState(() => _loading = false);
      showToast(message: res['message'] as String? ?? '');
      if (res['success'] == true) {
        context.pop();
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: AppLocale.tr('change_password')),
      body: SingleChildScrollView(
              child: Container(
                margin: EdgeInsets.symmetric(horizontal: 20.w, vertical: 10.h),
                padding: EdgeInsets.all(16.h),
                width: MediaQuery.sizeOf(context).width,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(12.r),
                  color: Colors.white,
                ),
                child: Column(
                  children: [
                    TextFormWithLabel(
                      hintText: "*****************",
                      controller: _oldController,
                      keyboardType: TextInputType.visiblePassword,
                      obscureText: _obscureOld,
                      labelText: AppLocale.tr('old_password'),
                      suffixIcon: IconButton(
                        onPressed: () => setState(() => _obscureOld = !_obscureOld),
                        icon: Icon(
                          _obscureOld ? Icons.visibility_off_outlined : Icons.visibility_outlined,
                          size: 22.sp,
                          color: Colors.grey.shade600,
                        ),
                      ),
                    ),
                    SizedBox(height: 15.h),
                    TextFormWithLabel(
                      hintText: "*****************",
                      controller: _newController,
                      keyboardType: TextInputType.visiblePassword,
                      obscureText: _obscureNew,
                      labelText: AppLocale.tr('new_password'),
                      suffixIcon: IconButton(
                        onPressed: () => setState(() => _obscureNew = !_obscureNew),
                        icon: Icon(
                          _obscureNew ? Icons.visibility_off_outlined : Icons.visibility_outlined,
                          size: 22.sp,
                          color: Colors.grey.shade600,
                        ),
                      ),
                    ),
                    SizedBox(height: 15.h),
                    TextFormWithLabel(
                      hintText: "*****************",
                      controller: _confirmController,
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
                    SizedBox(height: 50.h),
                    CustomButton(
                      text: _loading ? AppLocale.tr('saving') : AppLocale.tr('save'),
                      onTap: _loading ? () {} : _save,
                    ),
                  ],
                ),
                ),
              ),
    );
  }
}
