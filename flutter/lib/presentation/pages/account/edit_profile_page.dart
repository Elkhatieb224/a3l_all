// ignore_for_file: deprecated_member_use

import 'dart:typed_data';

import 'package:a3lnha/core/cache/app_image_cache.dart';
import 'package:a3lnha/core/data/location_translations.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/models/user_model.dart';
import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/data/services/region_service.dart';
import 'package:a3lnha/data/services/user_service.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/helpers/image_orientation_helper.dart';
import 'package:a3lnha/presentation/pages/account/change_email_page.dart';
import 'package:a3lnha/presentation/pages/account/change_password_page.dart';
import 'package:a3lnha/presentation/pages/account/delete_acc_page.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_button.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:a3lnha/presentation/widgets/shared/warning_confirm_dialog.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:hexcolor/hexcolor.dart';
import 'package:image_picker/image_picker.dart';

class EditProfilePage extends StatefulWidget {
  EditProfilePage({super.key});

  @override
  State<EditProfilePage> createState() => _EditProfilePageState();
}

class _EditProfilePageState extends State<EditProfilePage> {
  TextEditingController nameController = TextEditingController();
  TextEditingController phoneController = TextEditingController();
  TextEditingController addressController = TextEditingController();
  TextEditingController bioController = TextEditingController();

  UserModel? _user;
  bool _loading = true;
  bool _saving = false;
  /// صورة مختارة بعد تصحيح اتجاه EXIF (JPEG) للمعاينة والرفع.
  Uint8List? _pickedAvatarJpeg;
  String? _fullPhoneNumber;
  /// رمز دولة الهاتف المختارة في حقل الهاتف (TR أو SY) للتحقق من طول الرقم
  String _phoneCountryCode = 'SY';
  List<RegionStateNode> _regionStates = [];
  bool _regionsLoading = false;

  @override
  void initState() {
    super.initState();
    _loadUser();
  }

  Future<void> _loadUser() async {
    final user = await UserService.getUser();
    if (mounted) {
      setState(() {
        _user = user;
        _loading = false;
        if (user != null) {
          nameController.text = user.name;
          final fullPhone = user.phone ?? '';
          final digits = fullPhone.replaceAll(RegExp(r'[^\d+]'), '');
          if (digits.startsWith('+90') || digits.startsWith('90')) {
            _phoneCountryCode = 'TR';
          } else if (digits.startsWith('+963') || digits.startsWith('963')) {
            _phoneCountryCode = 'SY';
          } else if (user.countryCode == 'TR') {
            _phoneCountryCode = 'TR';
          } else {
            _phoneCountryCode = 'SY';
          }
          _fullPhoneNumber = user.phone;
          final parsed = _parsePhoneForCountry(user.phone, _phoneCountryCode);
          phoneController.text = parsed.replaceAll(RegExp(r'[^\d]'), '');
          addressController.text = user.locationCity ?? '';
          bioController.text = user.bio ?? '';
          selectedCountry = user.locationCountry;
          selectedGovernorate = user.locationCity;
          selectedDistrict = user.locationDistrict;
        }
      });
      if (user != null) {
        _loadRegionCatalog(user.locationCountry, keepCurrent: true);
      }
    }
  }

  /// استخراج الرقم الوطني من الرقم الكامل حسب الدولة
  String _parsePhoneForCountry(String? fullPhone, String? countryCode) {
    if (fullPhone == null || fullPhone.isEmpty) return '';
    final digits = fullPhone.replaceAll(RegExp(r'[^\d]'), '');
    if (countryCode == 'TR' && (digits.startsWith('90') && digits.length > 10)) {
      return digits.substring(2); // تركيا +90
    }
    if ((countryCode == 'SY' || countryCode == null) && (digits.startsWith('963') && digits.length > 9)) {
      return digits.substring(3); // سوريا +963
    }
    return fullPhone;
  }

  Future<void> _pickImage() async {
    final picker = ImagePicker();
    final source = await showModalBottomSheet<ImageSource>(
      context: context,
      builder: (ctx) => SafeArea(
        child: Wrap(
          children: [
            ListTile(
              leading: const Icon(Icons.camera_alt),
              title: const Text('الكاميرا'),
              onTap: () => Navigator.pop(ctx, ImageSource.camera),
            ),
            ListTile(
              leading: const Icon(Icons.photo_library),
              title: const Text('المعرض'),
              onTap: () => Navigator.pop(ctx, ImageSource.gallery),
            ),
          ],
        ),
      ),
    );
    if (source == null || !mounted) return;
    final file = await picker.pickImage(source: source, maxWidth: 1024, maxHeight: 1024, imageQuality: 85);
    if (file == null || !mounted) return;
    final raw = await file.readAsBytes();
    final normalized = normalizeImageForDisplayAndUpload(raw);
    if (!mounted) return;
    setState(() => _pickedAvatarJpeg = normalized ?? raw);
  }

  /// التحقق من رقم الهاتف حسب الدولة: تركيا 10 أرقام، سوريا 9 أرقام (بدون صفر في البداية)
  String? _validatePhone() {
    final raw = phoneController.text.replaceAll(RegExp(r'[^\d]'), '');
    if (raw.isEmpty) return null;
    if (_phoneCountryCode == 'TR') {
      if (raw.length != 10) return AppLocale.tr('phone_error_tr_length');
      if (raw.startsWith('0')) return AppLocale.tr('phone_error_tr_length');
    } else {
      if (raw.length != 9) return AppLocale.tr('phone_error_sy_length');
      if (raw.startsWith('0')) return AppLocale.tr('phone_error_sy_length');
    }
    return null;
  }

  String get _selectedDialCode => _phoneCountryCode == 'TR' ? '+90' : '+963';

  void _onPhoneCountryChanged(String countryCode) {
    if (_phoneCountryCode == countryCode) return;
    setState(() => _phoneCountryCode = countryCode);
    final maxLen = countryCode == 'TR' ? 10 : 9;
    final digits = phoneController.text.replaceAll(RegExp(r'[^\d]'), '');
    final trimmed = digits.length > maxLen ? digits.substring(0, maxLen) : digits;
    phoneController.value = TextEditingValue(
      text: trimmed,
      selection: TextSelection.collapsed(offset: trimmed.length),
    );
    _fullPhoneNumber = '$_selectedDialCode$trimmed';
  }

  Future<void> _saveProfile() async {
    final phoneError = _validatePhone();
    if (phoneError != null) {
      showToast(message: phoneError);
      return;
    }
    setState(() => _saving = true);
    final phoneToSave = _fullPhoneNumber?.trim();
    final res = await UserService.updateProfile(
      name: nameController.text.trim().isEmpty ? null : nameController.text.trim(),
      phone: phoneToSave != null && phoneToSave.isNotEmpty ? phoneToSave : null,
      countryCode: _phoneCountryCode,
      bio: bioController.text.trim().isEmpty ? null : bioController.text.trim(),
      locationCountry: selectedCountry,
      locationCity: selectedGovernorate,
      locationDistrict: selectedDistrict,
      avatarBytes: _pickedAvatarJpeg,
    );
    if (mounted) {
      setState(() {
        _saving = false;
        if (res['success'] == true && res['user'] != null) {
          _user = res['user'] as UserModel;
          _pickedAvatarJpeg = null;
        }
      });
      final msg = res['message'] as String?;
      showToast(message: (msg != null && msg.trim().isNotEmpty) ? msg : AppLocale.tr('profile_updated_success'));
      if (res['success'] == true) context.pop();
    }
  }

  void showDeleteAccountDialog(BuildContext context) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => WarningConfirmDialog(
        title: AppLocale.tr('delete_account_warning'),
        message: AppLocale.tr('deleting_account'),
        confirmText: AppLocale.tr('delete_account'),
        cancelText: 'الرجوع',
        confirmColor: Colors.red,
        onConfirm: () {
          Navigator.of(ctx).pop();
          context.push(DeleteAccPage());
        },
      ),
    );
  }

  String? selectedCountry;
  String? selectedGovernorate;
  String? selectedDistrict;

  /// تجنّب خطأ Dropdown: القيمة يجب أن تكون ضمن items وإلا نرجع null
  String? _safeDropdownValue(String? value, List<String> items) {
    if (value == null || value.isEmpty) return null;
    return items.contains(value) ? value : null;
  }

  RegionStateNode? get _selectedStateNode {
    final stateName = selectedGovernorate;
    if (stateName == null || stateName.isEmpty) return null;
    for (final state in _regionStates) {
      if (state.name == stateName) return state;
    }
    return null;
  }

  List<String> get _stateNames => _regionStates.map((s) => s.name).toList();

  List<String> get _cityNames {
    final state = _selectedStateNode;
    if (state == null) return const [];
    return state.cities.map((c) => c.name).toList();
  }

  Future<void> _loadRegionCatalog(String? country, {bool keepCurrent = true}) async {
    final cc = (country ?? '').toUpperCase();
    if (cc != 'SY' && cc != 'TR') {
      if (!mounted) return;
      setState(() {
        _regionStates = [];
        if (!keepCurrent) {
          selectedGovernorate = null;
          selectedDistrict = null;
        }
      });
      return;
    }

    if (mounted) setState(() => _regionsLoading = true);
    final states = await RegionService.fetchStates(cc);
    if (!mounted) return;
    setState(() {
      _regionStates = states;
      _regionsLoading = false;
      if (!keepCurrent) {
        selectedGovernorate = null;
        selectedDistrict = null;
      } else {
        if (!_stateNames.contains(selectedGovernorate)) {
          selectedGovernorate = null;
          selectedDistrict = null;
        } else if (!_cityNames.contains(selectedDistrict)) {
          selectedDistrict = null;
        }
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: AppLocale.tr('personal_profile')),
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            height: MediaQuery.sizeOf(context).height / 3.3,
            child: Stack(
              children: [
                Container(
                  padding: EdgeInsets.symmetric(
                    horizontal: 20.w,
                    vertical: 60.h,
                  ),
                  width: double.infinity,
                  height: MediaQuery.sizeOf(context).height / 7,
                  color: AppColors.darkBlue,
                ),
                Positioned(
                  left: 20.w,
                  right: 20.w,
                  top: 30.h,
                  child: Container(
                    width: double.infinity,
                    padding: EdgeInsets.symmetric(vertical: 20.h),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withOpacity(0.1),
                          blurRadius: 10,
                          offset: Offset(0, 4), // changes position of shadow
                        ),
                      ],
                      borderRadius: BorderRadius.circular(12.r),
                    ),
                    child: Padding(
                      padding: EdgeInsets.symmetric(
                        horizontal: 20.0.w,
                        vertical: 16.h,
                      ),
                      child: Column(
                        children: [
                          GestureDetector(
                            onTap: _pickImage,
                            child: Stack(
                              alignment: Alignment.bottomRight,
                              children: [
                                CircleAvatar(
                                  radius: 35.r,
                                  backgroundColor: Colors.grey[300],
                                  backgroundImage: _pickedAvatarJpeg != null
                                      ? MemoryImage(_pickedAvatarJpeg!)
                                      : (_user?.avatar != null && _user!.avatar!.isNotEmpty
                                          ? CachedNetworkImageProvider(
                                              _user!.avatar!,
                                              cacheManager: AppImageCache.instance,
                                            )
                                          : null),
                                  child: _pickedAvatarJpeg == null &&
                                          (_user?.avatar == null || _user!.avatar!.isEmpty)
                                      ? Text((_user?.name.isNotEmpty == true ? _user!.name[0] : '?').toUpperCase(),
                                          style: TextStyle(fontSize: 24.sp, color: AppColors.darkBlue, fontWeight: FontWeight.bold))
                                      : null,
                                ),
                                CircleAvatar(
                                  radius: 12.r,
                                  backgroundColor: Colors.white,
                                  child: Image.asset(
                                    "assets/images/edit.png",
                                    width: 24.w,
                                    height: 24.h,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          SizedBox(height: 10.h),
                          if (_loading)
                            CircularProgressIndicator(color: AppColors.darkBlue, strokeWidth: 2)
                          else ...[
                            Text(
                              _user?.name ?? "اسم المستخدم",
                              style: TextStyle(fontSize: 18.sp, fontWeight: FontWeight.w600),
                            ),
                            Text(
                              _user?.email ?? "—",
                              style: TextStyle(fontSize: 14.sp, fontWeight: FontWeight.w400, color: Colors.grey),
                            ),
                          ],
                        ],
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
          Expanded(
            child: SingleChildScrollView(
              physics: BouncingScrollPhysics(),
              child: Column(
                children: [
                  SizedBox(height: 10.h),
                  Container(
                    margin: EdgeInsets.symmetric(horizontal: 18.w),
                    padding: EdgeInsets.all(15.h),
                    width: double.infinity,
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(12.r),
                      color: Colors.white,
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withOpacity(0.1),
                          blurRadius: 10,
                          spreadRadius: 0,
                          offset: Offset(0, 2),
                        ),
                      ],
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              AppLocale.tr('name'),
                              style: TextStyle(
                                fontSize: 12.sp,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          ],
                        ),
                        SizedBox(height: 8.h),
                        TextFormField(
                          controller: nameController,
                          textInputAction: TextInputAction.next,
                          decoration: InputDecoration(
                            hintText: AppLocale.tr('name'),
                            contentPadding: EdgeInsets.symmetric(
                              vertical: 12.h,
                              horizontal: 12.w,
                            ),
                            hintStyle: TextStyle(
                              fontSize: 14.sp,
                              color: Colors.grey,
                            ),
                            fillColor: Colors.transparent,
                            filled: true,
                            enabledBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(8.r),
                              borderSide: BorderSide(
                                color: Colors.grey.withOpacity(0.5),
                                width: 1.1.w,
                              ),
                            ),
                            focusedBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(8.r),
                              borderSide: BorderSide(
                                color: Colors.grey.withOpacity(0.5),
                                width: 1.1.w,
                              ),
                            ),
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(8.r),
                              borderSide: BorderSide(
                                color: Colors.grey,
                                width: 0.5.w,
                              ),
                            ),
                          ),
                        ),
                        SizedBox(height: 10.h),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              AppLocale.tr('email'),
                              style: TextStyle(
                                fontSize: 12.sp,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          ],
                        ),
                        SizedBox(height: 8.h),
                        Container(
                          width: double.infinity,
                          padding: EdgeInsets.symmetric(vertical: 12.h, horizontal: 12.w),
                          decoration: BoxDecoration(
                            color: Colors.grey.shade200,
                            borderRadius: BorderRadius.circular(8.r),
                            border: Border.all(color: Colors.grey.withOpacity(0.5)),
                          ),
                          child: Text(
                            _user?.email ?? '—',
                            style: TextStyle(fontSize: 14.sp, color: Colors.grey.shade700),
                          ),
                        ),
                        SizedBox(height: 10.h),
                        GestureDetector(
                          onTap: () async {
                            final updated = await context.push(ChangeEmailPage());
                            if (updated == true && mounted) _loadUser();
                          },
                          child: Container(
                            padding: EdgeInsets.symmetric(vertical: 10.h, horizontal: 12.w),
                            decoration: BoxDecoration(
                              color: AppColors.darkBlue.withOpacity(0.08),
                              borderRadius: BorderRadius.circular(8.r),
                              border: Border.all(color: AppColors.darkBlue.withOpacity(0.3)),
                            ),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.email_outlined, size: 18.sp, color: AppColors.darkBlue),
                                SizedBox(width: 8.w),
                                Text(
                                  AppLocale.tr('change_my_email'),
                                  style: TextStyle(fontSize: 14.sp, fontWeight: FontWeight.w500, color: AppColors.darkBlue),
                                ),
                              ],
                            ),
                          ),
                        ),
                        SizedBox(height: 10.h),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              AppLocale.tr('phone'),
                              style: TextStyle(
                                fontSize: 12.sp,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          ],
                        ),
                        SizedBox(height: 8.h),
                        TextFormField(
                          controller: phoneController,
                          keyboardType: TextInputType.phone,
                          textInputAction: TextInputAction.next,
                          inputFormatters: [
                            FilteringTextInputFormatter.digitsOnly,
                            LengthLimitingTextInputFormatter(
                              _phoneCountryCode == 'TR' ? 10 : 9,
                            ),
                          ],
                          onChanged: (value) {
                            _fullPhoneNumber = '$_selectedDialCode${value.replaceAll(RegExp(r'[^\d]'), '')}';
                          },
                          decoration: InputDecoration(
                            hintText: _phoneCountryCode == 'TR'
                                ? AppLocale.tr('phone_hint_tr')
                                : AppLocale.tr('phone_hint_sy'),
                            errorText: _validatePhone(),
                            errorMaxLines: 4,
                            contentPadding: EdgeInsets.symmetric(
                              vertical: 12.h,
                              horizontal: 12.w,
                            ),
                            hintStyle: TextStyle(
                              fontSize: 14.sp,
                              color: Colors.grey,
                            ),
                            fillColor: Colors.transparent,
                            filled: true,
                            prefixIconConstraints: BoxConstraints(minWidth: 0, minHeight: 0),
                            prefixIcon: Padding(
                              padding: EdgeInsetsDirectional.fromSTEB(8.w, 6.h, 8.w, 6.h),
                              child: DropdownButtonHideUnderline(
                                child: DropdownButton<String>(
                                  value: _phoneCountryCode,
                                  isDense: true,
                                  icon: const Icon(Icons.arrow_drop_down),
                                  onChanged: (val) {
                                    if (val == null) return;
                                    _onPhoneCountryChanged(val);
                                  },
                                  items: [
                                    DropdownMenuItem(
                                      value: 'SY',
                                      child: Row(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          const _SyrianGreenFlagIcon(),
                                          SizedBox(width: 6.w),
                                          Text('+963', style: TextStyle(fontSize: 13.sp)),
                                        ],
                                      ),
                                    ),
                                    DropdownMenuItem(
                                      value: 'TR',
                                      child: Row(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          Image.asset('assets/images/turkey.png', width: 18.w, height: 18.w),
                                          SizedBox(width: 6.w),
                                          Text('+90', style: TextStyle(fontSize: 13.sp)),
                                        ],
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                            enabledBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(8.r),
                              borderSide: BorderSide(
                                color: Colors.grey.withOpacity(0.5),
                                width: 1.1.w,
                              ),
                            ),
                            focusedBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(8.r),
                              borderSide: BorderSide(
                                color: Colors.grey.withOpacity(0.5),
                                width: 1.1.w,
                              ),
                            ),
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(8.r),
                              borderSide: BorderSide(
                                color: Colors.grey,
                                width: 0.5.w,
                              ),
                            ),
                          ),
                        ),
                        SizedBox(height: 10.h),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              AppLocale.tr('bio'),
                              style: TextStyle(
                                fontSize: 12.sp,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          ],
                        ),
                        SizedBox(height: 8.h),
                        TextFormField(
                          controller: bioController,
                          keyboardType: TextInputType.multiline,
                          maxLines: 3,
                          maxLength: 1000,
                          decoration: InputDecoration(
                            hintText: AppLocale.tr('bio_placeholder'),
                            contentPadding: EdgeInsets.symmetric(
                              vertical: 12.h,
                              horizontal: 12.w,
                            ),
                            hintStyle: TextStyle(
                              fontSize: 14.sp,
                              color: Colors.grey,
                            ),
                            fillColor: Colors.transparent,
                            filled: true,
                            enabledBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(8.r),
                              borderSide: BorderSide(
                                color: Colors.grey.withOpacity(0.5),
                                width: 1.1.w,
                              ),
                            ),
                            focusedBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(8.r),
                              borderSide: BorderSide(
                                color: Colors.grey.withOpacity(0.5),
                                width: 1.1.w,
                              ),
                            ),
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(8.r),
                              borderSide: BorderSide(
                                color: Colors.grey,
                                width: 0.5.w,
                              ),
                            ),
                          ),
                        ),
                        SizedBox(height: 10.h),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              AppLocale.tr('country'),
                              style: TextStyle(
                                fontSize: 12.sp,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          ],
                        ),
                        SizedBox(height: 8.h),
                        CustomDropdownFormField(
                          hintText: AppLocale.tr('select_country'),
                          value: _safeDropdownValue(
                            selectedCountry,
                            const ['SY', 'TR'],
                          ),
                          items: const ['SY', 'TR'].map((code) {
                            final displayName = LocationTranslations.display(
                              AppLocale.current,
                              code == 'SY' ? 'سوريا' : 'تركيا',
                            );
                            return DropdownMenuItem(
                              value: code,
                              child: Text(displayName),
                            );
                          }).toList(),
                          onChanged: (String? value) {
                            setState(() {
                              selectedCountry = value;
                              selectedGovernorate = null;
                              selectedDistrict = null;
                            });
                            _loadRegionCatalog(value, keepCurrent: false);
                          },
                        ),
                        SizedBox(height: 10.h),
                        if (_regionsLoading)
                          Padding(
                            padding: EdgeInsets.only(bottom: 10.h),
                            child: LinearProgressIndicator(color: AppColors.darkBlue),
                          ),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              AppLocale.tr('state'),
                              style: TextStyle(
                                fontSize: 12.sp,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          ],
                        ),
                        SizedBox(height: 8.h),
                        CustomDropdownFormField(
                          hintText: AppLocale.tr('select_state'),
                          value: _safeDropdownValue(
                            selectedGovernorate,
                            _stateNames,
                          ),
                          items: _stateNames
                              .map((g) => DropdownMenuItem(
                                    value: g,
                                    child: Text(LocationTranslations.display(AppLocale.current, g)),
                                  ))
                              .toList(),
                          onChanged: (String? value) {
                            if (selectedCountry != null && !_regionsLoading) {
                              setState(() {
                                selectedGovernorate = value;
                                selectedDistrict = null;
                              });
                            }
                          },
                        ),
                        SizedBox(height: 10.h),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              AppLocale.tr('district'),
                              style: TextStyle(
                                fontSize: 12.sp,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          ],
                        ),
                        SizedBox(height: 8.h),
                        CustomDropdownFormField(
                          hintText: AppLocale.tr('select_district'),
                          value: _safeDropdownValue(
                            selectedDistrict,
                            _cityNames,
                          ),
                          items: _cityNames
                              .map((d) => DropdownMenuItem(
                                    value: d,
                                    child: Text(LocationTranslations.display(AppLocale.current, d)),
                                  ))
                              .toList(),
                          onChanged: (String? value) {
                            if (selectedCountry != null && selectedGovernorate != null && !_regionsLoading) {
                              setState(() => selectedDistrict = value);
                            }
                          },
                        ),
                        SizedBox(height: 10.h),
                        Text(
                          "تغير كلمة السر",
                          style: TextStyle(
                            fontSize: 12.sp,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                        SizedBox(height: 10.h),

                        GestureDetector(
                          onTap: () {
                            context.push(ChangePasswordPage());
                          },
                          child: Container(
                            padding: EdgeInsets.symmetric(
                              vertical: 0.h,
                              horizontal: 15.w,
                            ),
                            width: double.infinity,
                            decoration: BoxDecoration(
                              color: HexColor("#F5F5F5"),
                              borderRadius: BorderRadius.circular(8.r),
                            ),
                            child: Center(
                              child: Row(
                                mainAxisAlignment:
                                    MainAxisAlignment.spaceBetween,
                                children: [
                                  Text(
                                    "**********",
                                    style: TextStyle(
                                      color: AppColors.darkBlue,
                                      fontSize: 20.sp,
                                    ),
                                  ),
                                  IconButton(
                                    onPressed: () {
                                      context.push(ChangePasswordPage());
                                    },
                                    icon: Icon(
                                      Icons.arrow_forward_ios,
                                      size: 16.sp,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                        SizedBox(height: 20.h),
                        Center(
                          child: GestureDetector(
                            onTap: () {
                              showDeleteAccountDialog(context);
                            },
                            child: Container(
                              width: 120.w,
                              height: 50.h,
                              decoration: BoxDecoration(
                                color: Colors.red,
                                borderRadius: BorderRadius.circular(12.r),
                              ),
                              child: Center(
                                child: Row(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    Text(
                                      "حذف الحساب",
                                      style: TextStyle(
                                        color: Colors.white,
                                        fontSize: 12.sp,
                                        fontWeight: FontWeight.w600,
                                      ),
                                    ),
                                    SizedBox(width: 5.w),
                                    Image.asset(
                                      "assets/images/Button Link.png",
                                      width: 18.w,
                                      height: 18.h,
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          ),
                        ),
                        SizedBox(height: 20.h),
                        CustomButton(
                          text: _saving ? "جاري الحفظ..." : "حفظ",
                          onTap: _saving ? () {} : _saveProfile,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class CustomDropdownFormField extends StatelessWidget {
  final String hintText;
  final String? value;
  final List<DropdownMenuItem<String>> items;
  final ValueChanged<String?> onChanged;
  final Widget? suffixIcon;
  final Widget? prefixIcon;

  const CustomDropdownFormField({
    super.key,
    required this.hintText,
    required this.value,
    required this.items,
    required this.onChanged,
    this.suffixIcon,
    this.prefixIcon,
  });

  @override
  Widget build(BuildContext context) {
    return DropdownButtonFormField<String>(
      value: value,
      decoration: InputDecoration(
        hintText: hintText,
        contentPadding: EdgeInsets.symmetric(vertical: 12.h, horizontal: 12.w),
        hintStyle: TextStyle(fontSize: 14.sp, color: Colors.grey),
        fillColor: Colors.transparent,
        filled: true,
        suffixIcon: suffixIcon,
        prefixIcon: prefixIcon,
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8.r),
          borderSide: BorderSide(
            color: Colors.grey.withOpacity(0.5),
            width: 1.1.w,
          ),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8.r),
          borderSide: BorderSide(
            color: Colors.grey.withOpacity(0.5),
            width: 1.1.w,
          ),
        ),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8.r),
          borderSide: BorderSide(color: Colors.grey, width: 0.5.w),
        ),
      ),
      items: items,
      onChanged: onChanged,
      icon: const Icon(Icons.arrow_drop_down),
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
