import 'dart:io';

import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/models/user_model.dart';
import 'package:a3lnha/data/services/user_service.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/widgets/shared/app_network_image.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_button.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:dotted_border/dotted_border.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:image_picker/image_picker.dart';

/// تعديل بيانات النشاط التجاري للحساب الموثّق.
class EditBusinessProfilePage extends StatefulWidget {
  const EditBusinessProfilePage({super.key});

  @override
  State<EditBusinessProfilePage> createState() => _EditBusinessProfilePageState();
}

class _EditBusinessProfilePageState extends State<EditBusinessProfilePage> {
  final _businessName = TextEditingController();
  final _businessType = TextEditingController();
  final _businessOwner = TextEditingController();
  final _businessAddress = TextEditingController();
  final _businessPhone = TextEditingController();
  final _instagramUrl = TextEditingController();
  final _facebookUrl = TextEditingController();
  final _websiteUrl = TextEditingController();

  UserModel? _user;
  bool _loading = true;
  bool _saving = false;
  XFile? _storefrontImage;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _businessName.dispose();
    _businessType.dispose();
    _businessOwner.dispose();
    _businessAddress.dispose();
    _businessPhone.dispose();
    _instagramUrl.dispose();
    _facebookUrl.dispose();
    _websiteUrl.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    final user = await UserService.getUser();
    if (!mounted) return;
    setState(() {
      _user = user;
      _loading = false;
      if (user != null) {
        _businessName.text = user.businessName ?? '';
        _businessType.text = user.businessType ?? '';
        _businessOwner.text = user.businessOwner ?? '';
        _businessAddress.text = user.businessAddress ?? '';
        _businessPhone.text = user.businessPhone ?? '';
        _instagramUrl.text = user.instagramUrl ?? '';
        _facebookUrl.text = user.facebookUrl ?? '';
        _websiteUrl.text = user.websiteUrl ?? '';
      }
    });
  }

  Future<void> _pickStorefront() async {
    final picker = ImagePicker();
    final file = await picker.pickImage(
      source: ImageSource.gallery,
      maxWidth: 1600,
      maxHeight: 1600,
      imageQuality: 85,
    );
    if (file != null && mounted) setState(() => _storefrontImage = file);
  }

  Future<void> _save() async {
    if (_businessName.text.trim().isEmpty) {
      showToast(message: AppLocale.tr('field_required').replaceAll('%s', AppLocale.tr('business_name')));
      return;
    }
    if (_businessType.text.trim().isEmpty) {
      showToast(message: AppLocale.tr('field_required').replaceAll('%s', AppLocale.tr('business_type')));
      return;
    }
    if (_businessOwner.text.trim().isEmpty) {
      showToast(message: AppLocale.tr('field_required').replaceAll('%s', AppLocale.tr('verification_responsible_person')));
      return;
    }
    if (_businessAddress.text.trim().isEmpty) {
      showToast(message: AppLocale.tr('field_required').replaceAll('%s', AppLocale.tr('business_address')));
      return;
    }
    if (_businessPhone.text.trim().isEmpty) {
      showToast(message: AppLocale.tr('field_required').replaceAll('%s', AppLocale.tr('business_phone')));
      return;
    }

    setState(() => _saving = true);
    final res = await UserService.updateVerifiedBusinessProfile(
      businessName: _businessName.text.trim(),
      businessType: _businessType.text.trim(),
      businessOwner: _businessOwner.text.trim(),
      businessAddress: _businessAddress.text.trim(),
      businessPhone: _businessPhone.text.trim(),
      instagramUrl: _instagramUrl.text.trim().isEmpty ? null : _instagramUrl.text.trim(),
      facebookUrl: _facebookUrl.text.trim().isEmpty ? null : _facebookUrl.text.trim(),
      websiteUrl: _websiteUrl.text.trim().isEmpty ? null : _websiteUrl.text.trim(),
      storefrontImageFile: _storefrontImage,
    );
    if (!mounted) return;
    setState(() => _saving = false);
    final ok = res['success'] == true;
    showToast(
      message: ok
          ? (res['message'] as String? ?? AppLocale.tr('business_data_updated'))
          : (res['message'] as String? ?? AppLocale.tr('error_loading')),
    );
    if (ok) {
      if (res['user'] != null) {
        setState(() {
          _user = res['user'] as UserModel;
          _storefrontImage = null;
        });
      }
      context.pop();
    }
  }

  Widget _field(String label, TextEditingController c, {String? hint}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: TextStyle(fontSize: 12.sp, fontWeight: FontWeight.w500)),
        SizedBox(height: 8.h),
        TextFormField(
          controller: c,
          keyboardType: TextInputType.text,
          maxLines: label == AppLocale.tr('business_address') ? 3 : 1,
          decoration: InputDecoration(
            hintText: hint ?? AppLocale.tr('add_your_link'),
            filled: true,
            fillColor: Colors.grey.shade50,
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8.r),
              borderSide: BorderSide(color: Colors.grey.shade300),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8.r),
              borderSide: BorderSide(color: Colors.grey.shade300),
            ),
            contentPadding: EdgeInsets.symmetric(horizontal: 12.w, vertical: 12.h),
          ),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return Scaffold(
        appBar: CustomAppbar(title: AppLocale.tr('edit_verified_business')),
        body: Center(child: CircularProgressIndicator(color: AppColors.darkBlue, strokeWidth: 2)),
      );
    }

    if (_user != null && !_user!.isVerified) {
      return Scaffold(
        appBar: CustomAppbar(title: AppLocale.tr('edit_verified_business')),
        body: Center(
          child: Padding(
            padding: EdgeInsets.all(24.w),
            child: Text(
              AppLocale.tr('account_not_verified'),
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 14.sp, color: Colors.grey[700]),
            ),
          ),
        ),
      );
    }

    return Scaffold(
      appBar: CustomAppbar(title: AppLocale.tr('edit_verified_business')),
      body: SingleChildScrollView(
        padding: EdgeInsets.all(16.w),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _field(AppLocale.tr('business_name'), _businessName),
            SizedBox(height: 16.h),
            _field(AppLocale.tr('business_type'), _businessType),
            SizedBox(height: 16.h),
            _field(AppLocale.tr('verification_responsible_person'), _businessOwner),
            SizedBox(height: 16.h),
            _field(AppLocale.tr('business_address'), _businessAddress),
            SizedBox(height: 16.h),
            _field(AppLocale.tr('business_phone'), _businessPhone, hint: AppLocale.tr('business_phone_hint')),
            SizedBox(height: 20.h),
            Text(
              '${AppLocale.tr('instagram')} (${AppLocale.tr('optional')})',
              style: TextStyle(fontSize: 12.sp, fontWeight: FontWeight.w500),
            ),
            SizedBox(height: 8.h),
            _field(AppLocale.tr('instagram'), _instagramUrl, hint: AppLocale.tr('add_your_link')),
            SizedBox(height: 16.h),
            Text(
              '${AppLocale.tr('facebook')} (${AppLocale.tr('optional')})',
              style: TextStyle(fontSize: 12.sp, fontWeight: FontWeight.w500),
            ),
            SizedBox(height: 8.h),
            _field(AppLocale.tr('facebook'), _facebookUrl, hint: AppLocale.tr('add_your_link')),
            SizedBox(height: 16.h),
            Text(
              '${AppLocale.tr('website')} (${AppLocale.tr('optional')})',
              style: TextStyle(fontSize: 12.sp, fontWeight: FontWeight.w500),
            ),
            SizedBox(height: 8.h),
            _field(AppLocale.tr('website'), _websiteUrl, hint: AppLocale.tr('add_your_link')),
            SizedBox(height: 24.h),
            Text(
              '${AppLocale.tr('storefront_photo_title')} (${AppLocale.tr('optional')})',
              style: TextStyle(fontSize: 14.sp, fontWeight: FontWeight.w600),
            ),
            SizedBox(height: 6.h),
            Text(
              AppLocale.tr('storefront_photo_hint'),
              style: TextStyle(fontSize: 12.sp, color: Colors.grey[600]),
            ),
            SizedBox(height: 12.h),
            GestureDetector(
              onTap: _pickStorefront,
              child: DottedBorder(
                options: RoundedRectDottedBorderOptions(
                  strokeWidth: 1,
                  color: AppColors.darkBlue,
                  dashPattern: const [10, 5],
                  radius: Radius.circular(8.r),
                ),
                child: Container(
                  width: double.infinity,
                  height: 120.h,
                  alignment: Alignment.center,
                  child: _storefrontImage != null
                      ? ClipRRect(
                          borderRadius: BorderRadius.circular(8.r),
                          child: Image.file(
                            File(_storefrontImage!.path),
                            width: double.infinity,
                            height: double.infinity,
                            fit: BoxFit.cover,
                          ),
                        )
                      : (_user?.storefrontImage != null && _user!.storefrontImage!.isNotEmpty)
                          ? ClipRRect(
                              borderRadius: BorderRadius.circular(8.r),
                              child: CachedUrlImage(
                                imageUrl: _user!.storefrontImage!,
                                height: 120.h,
                                width: double.infinity,
                                fit: BoxFit.contain,
                                errorBuilder: (_, __) => Icon(Icons.image_not_supported, color: Colors.grey),
                              ),
                            )
                          : Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.add_photo_alternate, color: AppColors.darkBlue, size: 32.sp),
                                SizedBox(height: 8.h),
                                Text(
                                  AppLocale.tr('pick_document'),
                                  style: TextStyle(fontSize: 13.sp, color: Colors.grey[700]),
                                ),
                              ],
                            ),
                ),
              ),
            ),
            SizedBox(height: 8.h),
            Text(
              '${AppLocale.tr('verification_documents_formats')}. ${AppLocale.tr('verification_documents_max_size')}',
              style: TextStyle(fontSize: 11.sp, color: Colors.grey[600]),
            ),
            SizedBox(height: 28.h),
            CustomButton(
              text: _saving ? AppLocale.tr('sending') : AppLocale.tr('save'),
              onTap: _saving ? () {} : _save,
              backgroundColor: AppColors.darkBlue,
              textColor: Colors.white,
            ),
            SizedBox(height: 24.h),
          ],
        ),
      ),
    );
  }
}
