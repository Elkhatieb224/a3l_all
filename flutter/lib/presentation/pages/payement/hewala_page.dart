import 'dart:typed_data';

import 'package:a3lnha/core/data/currency_helper.dart';
import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/services/wallet_service.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/helpers/numeral_helper.dart';
import 'package:a3lnha/presentation/pages/home/info_about_app_page.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_button.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:hexcolor/hexcolor.dart';
import 'package:image_picker/image_picker.dart';

class HewalaPage extends StatefulWidget {
  const HewalaPage({super.key});

  @override
  State<HewalaPage> createState() => _HewalaPageState();
}

class _HewalaPageState extends State<HewalaPage> {
  final _amountController = TextEditingController();
  final _receiptNumberController = TextEditingController();
  final _noteController = TextEditingController();
  String _selectedCurrency = CurrencyHelper.supportedCodes.first;
  Uint8List? _receiptImageBytes;
  bool _isSubmitting = false;

  @override
  void dispose() {
    _amountController.dispose();
    _receiptNumberController.dispose();
    _noteController.dispose();
    super.dispose();
  }

  Future<void> _pickReceiptImage() async {
    final picker = ImagePicker();
    final file = await picker.pickImage(source: ImageSource.gallery);
    if (file != null && mounted) {
      final bytes = await file.readAsBytes();
      if (mounted) setState(() => _receiptImageBytes = bytes);
    }
  }

  void _removeReceiptImage() {
    setState(() => _receiptImageBytes = null);
  }

  Future<void> _submit() async {
    final amountText = NumeralHelper.toEnglishDigits(_amountController.text.trim());
    final amount = NumeralHelper.parseAmount(amountText);
    if (amount == null || amount <= 0) {
      _showSnack(AppLocale.tr('enter_valid_amount'));
      return;
    }
    final receiptNumber = _receiptNumberController.text.trim();
    if (receiptNumber.isEmpty) {
      _showSnack(AppLocale.tr('enter_receipt_number'));
      return;
    }
    if (_receiptImageBytes == null || _receiptImageBytes!.isEmpty) {
      _showSnack(AppLocale.tr('attach_receipt_image'));
      return;
    }
    setState(() => _isSubmitting = true);
    final note = _noteController.text.trim();
    final result = await WalletService.submitHawalaTransfer(
      amount: amount,
      currency: _selectedCurrency,
      receiptNumber: receiptNumber,
      receiptImageBytes: _receiptImageBytes!,
      note: note.isEmpty ? null : note,
    );
    if (!mounted) return;
    setState(() => _isSubmitting = false);
    final success = result['success'] == 'true';
    _showSnack(result['message'] ?? (success ? 'تم إرسال الطلب' : 'فشل'));
    if (success) {
      context.pop();
    }
  }

  void _showSnack(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message)),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: AppLocale.tr('charge_wallet_hevala')),
      body: SingleChildScrollView(
        physics: const BouncingScrollPhysics(),
        child: Padding(
          padding: EdgeInsets.symmetric(horizontal: 20.w, vertical: 10.h),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      AppLocale.tr('hewala_instructions'),
                      style: TextStyle(
                        color: Colors.black,
                        fontSize: 16.sp,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                  SizedBox(width: 80.w),
                ],
              ),
              SizedBox(height: 15.h),
              GestureDetector(
                onTap: () => context.push(const InfoAboutAppPage()),
                child: Container(
                  padding: EdgeInsets.symmetric(horizontal: 20.w),
                  width: double.infinity,
                  height: 50.h,
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(12.r),
                    color: Colors.white,
                    boxShadow: [
                      BoxShadow(
                        color: Colors.grey.withOpacity(0.1),
                        offset: const Offset(0, 4),
                        blurRadius: 4,
                        spreadRadius: 0,
                      ),
                    ],
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        AppLocale.tr('app_info'),
                        style: TextStyle(
                          color: Colors.black,
                          fontSize: 12.sp,
                          fontWeight: FontWeight.w400,
                        ),
                      ),
                      Icon(Icons.arrow_forward_ios, size: 13.sp),
                    ],
                  ),
                ),
              ),
              SizedBox(height: 24.h),
              Text(
                AppLocale.tr('amount'),
                style: TextStyle(
                  color: Colors.black,
                  fontSize: 16.sp,
                  fontWeight: FontWeight.w600,
                ),
              ),
              SizedBox(height: 8.h),
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    flex: 2,
                    child: TextFormField(
                      controller: _amountController,
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      inputFormatters: [EnglishOnlyNumberInputFormatter()],
                      decoration: InputDecoration(
                        hintText: '0',
                        contentPadding: EdgeInsets.symmetric(horizontal: 12.w, vertical: 14.h),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(10.r),
                          borderSide: BorderSide(color: AppColors.darkBlue, width: 0.8),
                        ),
                        enabledBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(10.r),
                          borderSide: BorderSide(color: AppColors.darkBlue, width: 0.8),
                        ),
                      ),
                    ),
                  ),
                  SizedBox(width: 12.w),
                  Expanded(
                    child: Container(
                      padding: EdgeInsets.symmetric(horizontal: 8.w),
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(10.r),
                        border: Border.all(color: AppColors.darkBlue, width: 0.8),
                      ),
                      child: DropdownButtonHideUnderline(
                        child: DropdownButton<String>(
                          value: _selectedCurrency,
                          isExpanded: true,
                          items: CurrencyHelper.supportedCodes.map((code) {
                            return DropdownMenuItem(
                              value: code,
                              child: Text(
                                '${CurrencyHelper.symbol(code)} ($code)',
                                style: TextStyle(fontSize: 14.sp),
                              ),
                            );
                          }).toList(),
                          onChanged: (v) {
                            if (v != null) setState(() => _selectedCurrency = v);
                          },
                        ),
                      ),
                    ),
                  ),
                ],
              ),
              SizedBox(height: 20.h),
              Text(
                AppLocale.tr('receipt_number'),
                style: TextStyle(
                  color: Colors.black,
                  fontSize: 16.sp,
                  fontWeight: FontWeight.w600,
                ),
              ),
              SizedBox(height: 8.h),
              TextFormField(
                controller: _receiptNumberController,
                keyboardType: TextInputType.number,
                inputFormatters: [EnglishOnlyNumberInputFormatter(allowDecimal: false)],
                decoration: InputDecoration(
                  hintText: AppLocale.tr('receipt_number_hint'),
                  contentPadding: EdgeInsets.symmetric(horizontal: 12.w, vertical: 14.h),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(10.r),
                    borderSide: BorderSide(color: AppColors.darkBlue, width: 0.8),
                  ),
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(10.r),
                    borderSide: BorderSide(color: AppColors.darkBlue, width: 0.8),
                  ),
                ),
              ),
              SizedBox(height: 20.h),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Image.asset(
                    "assets/images/add image.png",
                    width: 30.w,
                    height: 30.h,
                  ),
                  SizedBox(width: 15.w),
                  Text(
                    AppLocale.tr('attach_receipt_image'),
                    style: TextStyle(
                      color: Colors.black,
                      fontSize: 16.sp,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
              SizedBox(height: 10.h),
              GestureDetector(
                onTap: _receiptImageBytes == null ? _pickReceiptImage : null,
                child: _receiptImageBytes != null
                    ? Stack(
                        alignment: Alignment.topRight,
                        children: [
                          ClipRRect(
                            borderRadius: BorderRadius.circular(8.r),
                            child: Image.memory(
                              _receiptImageBytes!,
                              width: double.infinity,
                              height: 180.h,
                              fit: BoxFit.cover,
                            ),
                          ),
                          GestureDetector(
                            onTap: _removeReceiptImage,
                            child: CircleAvatar(
                              backgroundColor: AppColors.darkBlue,
                              radius: 16.r,
                              child: Icon(Icons.close, color: Colors.white, size: 18.sp),
                            ),
                          ),
                        ],
                      )
                    : Container(
                        width: double.infinity,
                        height: 120.h,
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(8.r),
                          border: Border.all(color: AppColors.darkBlue.withOpacity(0.5)),
                          color: Colors.grey.shade100,
                        ),
                        child: Center(
                          child: Text(
                            AppLocale.tr('tap_to_attach_receipt'),
                            style: TextStyle(
                              color: Colors.grey.shade600,
                              fontSize: 14.sp,
                            ),
                          ),
                        ),
                      ),
              ),
              SizedBox(height: 20.h),
              Text(
                AppLocale.tr('note_optional'),
                style: TextStyle(
                  color: Colors.black,
                  fontSize: 16.sp,
                  fontWeight: FontWeight.w600,
                ),
              ),
              SizedBox(height: 8.h),
              TextFormField(
                controller: _noteController,
                maxLines: 3,
                maxLength: 1000,
                decoration: InputDecoration(
                  hintText: AppLocale.tr('note_placeholder'),
                  contentPadding: EdgeInsets.symmetric(horizontal: 12.w, vertical: 14.h),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(10.r),
                    borderSide: BorderSide(color: AppColors.darkBlue, width: 0.8),
                  ),
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(10.r),
                    borderSide: BorderSide(color: AppColors.darkBlue, width: 0.8),
                  ),
                ),
              ),
              SizedBox(height: 32.h),
              CustomButton(
                text: AppLocale.tr('continue'),
                onTap: _isSubmitting ? () {} : () { _submit(); },
                textColor: Colors.white,
                backgroundColor: HexColor("019B61"),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
