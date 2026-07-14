import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/account/my_products_deals_page.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_button.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:hexcolor/hexcolor.dart';

class AddCreditPage extends StatefulWidget {
  const AddCreditPage({super.key});

  @override
  State<AddCreditPage> createState() => _AddCreditPageState();
}

class _AddCreditPageState extends State<AddCreditPage> {
  bool isMainCard = true;
  bool isSaveCard = true;
  void showDoneDialog(BuildContext context) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) {
        return Dialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(20),
          ),
          child: Directionality(
            textDirection: TextDirection.rtl,
            child: Stack(
              clipBehavior: Clip.none,
              alignment: Alignment.topCenter,
              children: [
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 60, 16, 16),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Image.asset(
                        "assets/images/done.png",
                        width: 30.w,
                        height: 30.h,
                      ),
                      Text(
                        AppLocale.tr('card_activated'),
                        style: TextStyle(
                          fontWeight: FontWeight.w600,
                          fontSize: 22.sp,
                          color: AppColors.darkBlue,
                        ),
                        textAlign: TextAlign.center,
                      ),
                      SizedBox(height: 30.h),
                      CustomButton(
                        backgroundColor: AppColors.darkBlue,
                        textColor: Colors.white,
                        text: "الأستمرار",
                        onTap: () {
                          context.pop();
                        },
                      ),
                    ],
                  ),
                ),

                // Top circle icon
                Positioned(
                  top: -30,
                  child: CircleAvatar(
                    radius: 30,
                    backgroundColor: Colors.white,
                    child: Image.asset("assets/images/card.png"),
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: AppLocale.tr('personal_profile')),
      body: SingleChildScrollView(
              physics: BouncingScrollPhysics(),
              child: Padding(
                padding: EdgeInsets.symmetric(horizontal: 20.w, vertical: 10.h),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      "الأسم علي البطاقة",
                      style: TextStyle(
                        color: Colors.black,
                        fontSize: 16.sp,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    SizedBox(height: 5.h),
                    TextFormWithBorder(hintText: AppLocale.tr('card_as_written')),
                    SizedBox(height: 15.h),
                    Text(
                      "أسم البطاقة كما تريده",
                      style: TextStyle(
                        color: Colors.black,
                        fontSize: 16.sp,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    SizedBox(height: 5.h),
                    TextFormWithBorder(hintText: AppLocale.tr('display_name_hint')),
                    SizedBox(height: 15.h),
                    Text(
                      "رقم البطاقة",
                      style: TextStyle(
                        color: Colors.black,
                        fontSize: 16.sp,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    SizedBox(height: 5.h),
                    TextFormWithBorder(hintText: '1234 5678 9012 3456'),
                    SizedBox(height: 15.h),
                    Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: [
                              Text(
                                "CVV",
                                style: TextStyle(
                                  color: Colors.black,
                                  fontSize: 16.sp,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                              TextFormWithBorder(hintText: '***'),
                            ],
                          ),
                        ),
                        SizedBox(width: 30.w),
                        Expanded(
                          child: Column(
                            children: [
                              Text(
                                "تاريخ انتهاء",
                                style: TextStyle(
                                  color: Colors.black,
                                  fontSize: 16.sp,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                              TextFormWithBorder(hintText: 'MM / YYYY'),
                            ],
                          ),
                        ),
                      ],
                    ),
                    SizedBox(height: 10.h),

                    SwitcherRow(),
                    SwitcherRow(),

                    Center(
                      child: Text(
                        AppLocale.tr('card_secure_note'),
                        style: TextStyle(
                          color: Colors.black,
                          fontSize: 13.sp,
                          fontWeight: FontWeight.w600,
                        ),
                        textAlign: TextAlign.center,
                      ),
                    ),
                    SizedBox(height: 20.h),
                    CustomButton(
                      text: "الأستمرار",
                      onTap: () {
                        showDoneDialog(context);
                      },
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

class SwitcherRow extends StatefulWidget {
  SwitcherRow({super.key});

  @override
  State<SwitcherRow> createState() => _SwitcherRowState();
}

class _SwitcherRowState extends State<SwitcherRow> {
  bool isOn = true;
  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Switch(
          value: isOn,
          onChanged: (value) {
            setState(() {
              isOn = value;
            });
          },
          activeTrackColor: HexColor("019B61"),
          activeColor: Colors.white,
        ),
        Text(
          "أجعل هذا الكرت هو الكرت الرئيسي",
          style: TextStyle(
            color: Colors.black,
            fontWeight: FontWeight.w600,
            fontSize: 16.sp,
          ),
        ),
      ],
    );
  }
}

class TextFormWithBorder extends StatelessWidget {
  final String hintText;
  const TextFormWithBorder({super.key, required this.hintText});

  @override
  Widget build(BuildContext context) {
    return TextFormField(
      decoration: InputDecoration(
        hintText: hintText,
        hintStyle: TextStyle(
          color: Colors.grey,
          fontSize: 15.sp,
          fontWeight: FontWeight.w600,
        ),
        contentPadding: EdgeInsets.all(10.h),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10.r),
          borderSide: BorderSide(color: AppColors.darkBlue, width: 0.5),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10.r),
          borderSide: BorderSide(color: AppColors.darkBlue, width: 0.8),
        ),
      ),
    );
  }
}
