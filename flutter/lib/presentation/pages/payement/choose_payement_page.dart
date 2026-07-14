import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/account/my_products_deals_page.dart';
import 'package:a3lnha/presentation/pages/account/coming_soon_page.dart';
import 'package:a3lnha/presentation/pages/payement/hewala_page.dart';
import 'package:a3lnha/presentation/pages/payement/payment_gateway_page.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_button.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:hexcolor/hexcolor.dart';

class ChoosePayementPage extends StatelessWidget {
  const ChoosePayementPage({super.key});

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
                      title: AppLocale.tr('pay_by_card'),
                      image: "assets/images/24bb239902.png",
                      onTap: () {
                        context.push(ComingSoonPage(title: AppLocale.tr('pay_by_card')));
                      },
                    ),
                    SizedBox(height: 20.h),
                    HowToPayWidget(
                      image: "assets/images/e781be475e.png",
                      title: AppLocale.tr('charge_wallet_hevala'),
                      onTap: () {
                        context.push(HewalaPage());
                      },
                    ),
                    SizedBox(height: 40.h),
                  ],
                ),
              ),
      ),
    );
  }
}
