import 'dart:io';

import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/services/verification_service.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_button.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:dotted_border/dotted_border.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:hexcolor/hexcolor.dart';
import 'package:image_picker/image_picker.dart';

class SubmitVerificationPage extends StatefulWidget {
  const SubmitVerificationPage({super.key});

  @override
  State<SubmitVerificationPage> createState() => _SubmitVerificationPageState();
}

class _SubmitVerificationPageState extends State<SubmitVerificationPage> {
  final _formKey = GlobalKey<FormState>();
  final _businessName = TextEditingController();
  final _businessType = TextEditingController();
  final _responsiblePerson = TextEditingController();
  final _businessAddress = TextEditingController();
  final _businessPhone = TextEditingController();
  final _instagramUrl = TextEditingController();
  final _facebookUrl = TextEditingController();
  final _websiteUrl = TextEditingController();
  String _phoneCountryCode = 'SY';
  String? _fullBusinessPhone;

  String _primaryDocType = 'id';
  XFile? _primaryDocument;
  XFile? _storefrontImage;
  bool _submitting = false;

  @override
  void dispose() {
    _businessName.dispose();
    _businessType.dispose();
    _responsiblePerson.dispose();
    _businessAddress.dispose();
    _businessPhone.dispose();
    _instagramUrl.dispose();
    _facebookUrl.dispose();
    _websiteUrl.dispose();
    super.dispose();
  }

  Future<void> _pickPrimaryDocument() async {
    final result = await FilePicker.platform.pickFiles(
      type: FileType.custom,
      allowedExtensions: ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
      withData: false,
      withReadStream: false,
    );
    if (result != null && result.files.single.path != null && mounted) {
      setState(() {
        _primaryDocument = XFile(result.files.single.path!);
      });
    }
  }

  Future<void> _pickStorefrontImage() async {
    final picker = ImagePicker();
    final file = await picker.pickImage(source: ImageSource.gallery);
    if (file != null && mounted) {
      setState(() => _storefrontImage = file);
    }
  }

  Future<void> _submit() async {
    if (_primaryDocument == null) {
      showToast(message: AppLocale.tr('primary_document_hint'));
      return;
    }
    if (_businessName.text.trim().isEmpty) {
      showToast(message: AppLocale.tr('field_required').replaceAll('%s', AppLocale.tr('business_name')));
      return;
    }
    if (_businessType.text.trim().isEmpty) {
      showToast(message: AppLocale.tr('field_required').replaceAll('%s', AppLocale.tr('business_type')));
      return;
    }
    if (_responsiblePerson.text.trim().isEmpty) {
      showToast(message: AppLocale.tr('field_required').replaceAll('%s', AppLocale.tr('verification_responsible_person')));
      return;
    }
    if (_businessAddress.text.trim().isEmpty) {
      showToast(message: AppLocale.tr('field_required').replaceAll('%s', AppLocale.tr('business_address')));
      return;
    }
    final phoneError = _validateBusinessPhone();
    if (phoneError != null) {
      showToast(message: phoneError);
      return;
    }

    setState(() => _submitting = true);
    try {
      final res = await VerificationService.submitRequest(
        businessName: _businessName.text.trim(),
        businessType: _businessType.text.trim(),
        responsiblePerson: _responsiblePerson.text.trim(),
        businessAddress: _businessAddress.text.trim(),
        businessPhone: _fullBusinessPhone!.trim(),
        primaryDocumentType: _primaryDocType,
        primaryDocumentFile: _primaryDocument!,
        instagramUrl: _instagramUrl.text.trim().isEmpty ? null : _instagramUrl.text.trim(),
        facebookUrl: _facebookUrl.text.trim().isEmpty ? null : _facebookUrl.text.trim(),
        websiteUrl: _websiteUrl.text.trim().isEmpty ? null : _websiteUrl.text.trim(),
        storefrontImageFile: _storefrontImage,
      );
      if (!mounted) return;
      final ok = res['success'] == true;
      showToast(message: ok ? AppLocale.tr('verification_request_submitted') : (res['message'] as String? ?? AppLocale.tr('verification_submit_failed')));
      if (ok) {
        Navigator.of(context).pop(true);
      }
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: AppLocale.tr('submit_verification_request')),
      body: SingleChildScrollView(
        padding: EdgeInsets.all(16.w),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _buildField(AppLocale.tr('business_name'), _businessName),
              SizedBox(height: 16.h),
              _buildField(AppLocale.tr('business_type'), _businessType),
              SizedBox(height: 16.h),
              _buildField(AppLocale.tr('verification_responsible_person'), _responsiblePerson),
              SizedBox(height: 16.h),
              _buildField(AppLocale.tr('business_address'), _businessAddress),
              SizedBox(height: 16.h),
              _buildBusinessPhoneField(),
              SizedBox(height: 20.h),
              Text(
                '${AppLocale.tr('instagram')} (${AppLocale.tr('optional')})',
                style: TextStyle(fontSize: 12.sp, fontWeight: FontWeight.w500),
              ),
              SizedBox(height: 8.h),
              _buildField(AppLocale.tr('instagram'), _instagramUrl, hint: AppLocale.tr('add_your_link')),
              SizedBox(height: 16.h),
              Text(
                '${AppLocale.tr('facebook')} (${AppLocale.tr('optional')})',
                style: TextStyle(fontSize: 12.sp, fontWeight: FontWeight.w500),
              ),
              SizedBox(height: 8.h),
              _buildField(AppLocale.tr('facebook'), _facebookUrl, hint: AppLocale.tr('add_your_link')),
              SizedBox(height: 16.h),
              Text(
                '${AppLocale.tr('website')} (${AppLocale.tr('optional')})',
                style: TextStyle(fontSize: 12.sp, fontWeight: FontWeight.w500),
              ),
              SizedBox(height: 8.h),
              _buildField(AppLocale.tr('website'), _websiteUrl, hint: AppLocale.tr('add_your_link')),
              SizedBox(height: 24.h),
              Text(
                '${AppLocale.tr('primary_document_title')} *',
                style: TextStyle(fontSize: 14.sp, fontWeight: FontWeight.w600),
              ),
              SizedBox(height: 6.h),
              Text(
                AppLocale.tr('primary_document_hint'),
                style: TextStyle(fontSize: 12.sp, color: Colors.grey[600]),
              ),
              SizedBox(height: 12.h),
              Row(
                children: [
                  Radio<String>(
                    value: 'id',
                    groupValue: _primaryDocType,
                    onChanged: (v) => setState(() => _primaryDocType = v!),
                    activeColor: AppColors.darkBlue,
                  ),
                  Text(AppLocale.tr('primary_document_id'), style: TextStyle(fontSize: 14.sp)),
                  SizedBox(width: 20.w),
                  Radio<String>(
                    value: 'cr',
                    groupValue: _primaryDocType,
                    onChanged: (v) => setState(() => _primaryDocType = v!),
                    activeColor: AppColors.darkBlue,
                  ),
                  Text(AppLocale.tr('primary_document_cr'), style: TextStyle(fontSize: 14.sp)),
                ],
              ),
              SizedBox(height: 12.h),
              GestureDetector(
                behavior: HitTestBehavior.opaque,
                onTap: _pickPrimaryDocument,
                child: DottedBorder(
                  options: RoundedRectDottedBorderOptions(
                    strokeWidth: 1,
                    color: AppColors.darkBlue,
                    dashPattern: const [10, 5],
                    radius: Radius.circular(8.r),
                  ),
                  child: Container(
                    width: double.infinity,
                    padding: EdgeInsets.all(16.w),
                    child: Row(
                      children: [
                        Icon(Icons.upload_file, color: AppColors.darkBlue, size: 28.sp),
                        SizedBox(width: 12.w),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                _primaryDocument != null ? AppLocale.tr('document_selected') : AppLocale.tr('pick_document'),
                                style: TextStyle(fontSize: 14.sp, fontWeight: FontWeight.w500),
                              ),
                              if (_primaryDocument != null)
                                Text(
                                  _primaryDocument!.path.split(RegExp(r'[/\\]')).last,
                                  style: TextStyle(fontSize: 12.sp, color: Colors.grey[600]),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                            ],
                          ),
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
                behavior: HitTestBehavior.opaque,
                onTap: _pickStorefrontImage,
                child: DottedBorder(
                  options: RoundedRectDottedBorderOptions(
                    strokeWidth: 1,
                    color: AppColors.darkBlue,
                    dashPattern: const [10, 5],
                    radius: Radius.circular(8.r),
                  ),
                  child: Container(
                    width: double.infinity,
                    height: 100.h,
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
                        : Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              CircleAvatar(
                                radius: 24.r,
                                backgroundColor: HexColor("00CBFF").withValues(alpha: 0.1),
                                child: Icon(Icons.add_photo_alternate, color: AppColors.darkBlue, size: 28.sp),
                              ),
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
              SizedBox(height: 32.h),
              CustomButton(
                text: _submitting ? AppLocale.tr('sending') : AppLocale.tr('submit_verification_request'),
                onTap: _submitting ? () {} : _submit,
                backgroundColor: AppColors.darkBlue,
                textColor: Colors.white,
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildField(String label, TextEditingController controller, {String? hint}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: TextStyle(fontSize: 12.sp, fontWeight: FontWeight.w500)),
        SizedBox(height: 8.h),
        TextFormField(
          controller: controller,
          keyboardType: TextInputType.text,
          decoration: InputDecoration(
            hintText: hint ?? label,
            contentPadding: EdgeInsets.symmetric(vertical: 12.h, horizontal: 12.w),
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(8.r)),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8.r),
              borderSide: BorderSide(color: Colors.grey.withValues(alpha: 0.5)),
            ),
          ),
        ),
      ],
    );
  }

  String get _selectedDialCode => _phoneCountryCode == 'TR' ? '+90' : '+963';

  void _onPhoneCountryChanged(String countryCode) {
    if (_phoneCountryCode == countryCode) return;
    setState(() => _phoneCountryCode = countryCode);
    final maxLen = countryCode == 'TR' ? 10 : 9;
    final digits = _businessPhone.text.replaceAll(RegExp(r'[^\d]'), '');
    final trimmed = digits.length > maxLen ? digits.substring(0, maxLen) : digits;
    _businessPhone.value = TextEditingValue(
      text: trimmed,
      selection: TextSelection.collapsed(offset: trimmed.length),
    );
    _fullBusinessPhone = '$_selectedDialCode$trimmed';
  }

  String? _validateBusinessPhone() {
    final raw = _businessPhone.text.replaceAll(RegExp(r'[^\d]'), '');
    if (raw.isEmpty) return AppLocale.tr('phone_error_required');
    if (_phoneCountryCode == 'TR') {
      if (raw.length != 10 || raw.startsWith('0')) {
        return AppLocale.tr('phone_error_tr_length');
      }
    } else {
      if (raw.length != 9 || raw.startsWith('0')) {
        return AppLocale.tr('phone_error_sy_length');
      }
    }
    _fullBusinessPhone = '$_selectedDialCode$raw';
    return null;
  }

  Widget _buildBusinessPhoneField() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          AppLocale.tr('business_phone'),
          style: TextStyle(fontSize: 12.sp, fontWeight: FontWeight.w500),
        ),
        SizedBox(height: 8.h),
        TextFormField(
          controller: _businessPhone,
          keyboardType: TextInputType.phone,
          inputFormatters: [
            FilteringTextInputFormatter.digitsOnly,
            LengthLimitingTextInputFormatter(_phoneCountryCode == 'TR' ? 10 : 9),
          ],
          onChanged: (value) {
            _fullBusinessPhone =
                '$_selectedDialCode${value.replaceAll(RegExp(r'[^\d]'), '')}';
            if (mounted) setState(() {});
          },
          decoration: InputDecoration(
            hintText: _phoneCountryCode == 'TR'
                ? AppLocale.tr('phone_hint_tr')
                : AppLocale.tr('phone_hint_sy'),
            errorText: _validateBusinessPhone(),
            errorMaxLines: 3,
            contentPadding: EdgeInsets.symmetric(vertical: 12.h, horizontal: 12.w),
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(8.r)),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8.r),
              borderSide: BorderSide(color: Colors.grey.withValues(alpha: 0.5)),
            ),
            prefixIconConstraints: const BoxConstraints(minWidth: 0, minHeight: 0),
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
          ),
        ),
        SizedBox(height: 6.h),
        Text(
          AppLocale.tr('business_phone_example').replaceAll(
            '%s',
            _phoneCountryCode == 'TR'
                ? AppLocale.tr('phone_hint_tr')
                : AppLocale.tr('phone_hint_sy'),
          ),
          style: TextStyle(fontSize: 11.sp, color: Colors.grey[600]),
        ),
      ],
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
