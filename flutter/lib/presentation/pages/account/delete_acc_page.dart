import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/data/services/user_service.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/account/my_products_deals_page.dart';
import 'package:a3lnha/presentation/pages/auth/login_page.dart';
import 'package:a3lnha/presentation/widgets/auth/text_form_with_label.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_button.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class DeleteAccPage extends StatefulWidget {
  const DeleteAccPage({super.key});

  @override
  State<DeleteAccPage> createState() => _DeleteAccPageState();
}

class _DeleteAccPageState extends State<DeleteAccPage> {
  final TextEditingController _passwordController = TextEditingController();
  bool _confirmDelete = false;
  bool _loading = false;
  bool _obscurePassword = true;

  @override
  void dispose() {
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _deleteAccount() async {
    final pwd = _passwordController.text.trim();
    if (pwd.isEmpty) {
      showToast(message: AppLocale.tr('delete_account_password'));
      return;
    }
    if (!_confirmDelete) {
      showToast(message: AppLocale.tr('confirm_delete_account'));
      return;
    }
    setState(() => _loading = true);
    final res = await UserService.cancelAccount(password: pwd, confirm: true);
    if (mounted) {
      setState(() => _loading = false);
      final apiMessage = res['message'] as String? ?? '';
      if (res['success'] == true) {
        showToast(message: AppLocale.tr('account_cancellation_scheduled'));
        context.pushAndRemoveUntil(LoginPage());
      } else {
        final isAlreadyScheduled = apiMessage.toLowerCase().contains('already scheduled') || apiMessage.contains('مُجدول');
        showToast(message: isAlreadyScheduled ? AppLocale.tr('account_already_scheduled_deletion') : (apiMessage.isNotEmpty ? apiMessage : AppLocale.tr('confirm_delete_account')));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: AppLocale.tr('delete_account')),
      body: Container(
              margin: EdgeInsets.symmetric(horizontal: 20.w, vertical: 10.h),
              padding: EdgeInsets.all(15.w),
              width: double.infinity,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(12.r),
                color: Colors.white,
              ),
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      AppLocale.tr('confirm_delete_password_hint'),
                      style: TextStyle(
                        color: Colors.black,
                        fontWeight: FontWeight.w600,
                        fontSize: 14.sp,
                      ),
                    ),
                    SizedBox(height: 15.h),
                    TextFormWithLabel(
                      hintText: "****************",
                      controller: _passwordController,
                      keyboardType: TextInputType.visiblePassword,
                      obscureText: _obscurePassword,
                      labelText: AppLocale.tr('password_label'),
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
                    Row(
                      children: [
                        Checkbox(
                          value: _confirmDelete,
                          onChanged: (v) => setState(() => _confirmDelete = v ?? false),
                        ),
                        Expanded(
                          child: GestureDetector(
                            onTap: () => setState(() => _confirmDelete = !_confirmDelete),
                            child: Text(
                              AppLocale.tr('confirm_delete_account_checkbox'),
                              style: TextStyle(fontSize: 14.sp),
                            ),
                          ),
                        ),
                      ],
                    ),
                    SizedBox(height: 50.h),
                    CustomButton(
                      text: _loading ? AppLocale.tr('deleting') : AppLocale.tr('delete_account_button'),
                      onTap: _loading ? () {} : _deleteAccount,
                      textColor: Colors.white,
                      backgroundColor: Colors.red,
                    ),
                  ],
                ),
                ),
              ),
    );
  }
}
