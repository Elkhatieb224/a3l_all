import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/payement/add_credit_page.dart';
import 'package:a3lnha/presentation/pages/account/my_products_deals_page.dart';
import 'package:a3lnha/presentation/pages/payement/choose_payement_page.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_button.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:hexcolor/hexcolor.dart';

class PaymentGatewayPage extends StatelessWidget {
  const PaymentGatewayPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: AppLocale.tr('personal_profile')),
      body: SingleChildScrollView(
              child: Padding(
                padding: EdgeInsets.symmetric(horizontal: 20.w, vertical: 10.h),
                child: Column(
                  children: [
                    HowToPayWidget(
                      title: AppLocale.tr('add_bank_card'),
                      onTap: () {
                        context.push(AddCreditPage());
                      },
                    ),
                    SizedBox(height: 20.h),
                    HowToPayWidget(
                      title: AppLocale.tr('transfer'),
                      onTap: () {
                        context.push(ChoosePayementPage());
                      },
                    ),
                    SizedBox(height: 40.h),
                    CustomButton(
                      text: "الأستمرار",
                      onTap: () {
                        context.push(ChoosePayementPage());
                      },
                      backgroundColor: HexColor("019B61"),
                      textColor: Colors.white,
                    ),
                  ],
                ),
              ),
        ),
    );
  }
}

class HowToPayWidget extends StatelessWidget {
  final String title;
  final VoidCallback onTap;
  final String image;
  const HowToPayWidget({
    super.key,
    required this.title,
    required this.onTap,
    this.image = "assets/images/icons8-credit-card-96 1.png",
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: double.infinity,
        height: 150.h,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16.r),
          color: Colors.white,
          boxShadow: [
            BoxShadow(
              offset: Offset(0, 4),
              spreadRadius: 0,
              blurRadius: 15.r,
              // ignore: deprecated_member_use
              color: Colors.grey.withOpacity(0.1),
            ),
          ],
        ),
        child: Column(
          children: [
            Image.asset(image, width: 95.w, height: 95.h),
            SizedBox(height: 5.h),
            Text(
              title,
              style: TextStyle(
                color: Colors.black,
                fontWeight: FontWeight.w600,
                fontSize: 16.sp,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
