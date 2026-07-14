import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/payement/add_credit_page.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_button.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class OldWalletPage extends StatelessWidget {
  const OldWalletPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: AppLocale.tr('wallet')),
      body: Column(
        children: [
          SizedBox(
            height: MediaQuery.sizeOf(context).height / 3,
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
                      color: AppColors.darkBlue,

                      boxShadow: [
                        BoxShadow(
                          // ignore: deprecated_member_use
                          color: Colors.black.withOpacity(0.1),
                          blurRadius: 10,
                          offset: Offset(0, 4),
                          // changes position of shadow
                        ),
                      ],
                      border: Border.all(color: Colors.white),
                      borderRadius: BorderRadius.circular(12.r),
                    ),
                    child: Padding(
                      padding: EdgeInsets.symmetric(
                        horizontal: 20.0.w,
                        vertical: 16.h,
                      ),
                      child: Stack(
                        children: [
                          Center(
                            child: Opacity(
                              opacity: 0.3,
                              child: Image.asset(
                                "assets/images/مايك 1.png",
                                width: 150.w,
                                height: 150.h,
                              ),
                            ),
                          ),
                          Align(
                            alignment: Alignment.topLeft,
                            child: Image.asset("assets/images/currenc.png"),
                          ),

                          Positioned(
                            bottom: 1,
                            left: 0,
                            right: 0,
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              crossAxisAlignment: CrossAxisAlignment.center,
                              children: [
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    Image.asset(
                                      "assets/images/heroicons_eye.png",
                                      width: 12.w,
                                      height: 12.h,
                                    ),
                                    SizedBox(width: 5.w),
                                    Text(
                                      "الرصيد الحالي",
                                      style: TextStyle(
                                        color: Colors.white,
                                        fontSize: 16.sp,
                                        fontWeight: FontWeight.w500,
                                      ),
                                    ),
                                  ],
                                ),
                                Text(
                                  "1000 نقطة",
                                  style: TextStyle(
                                    color: Colors.white,
                                    fontSize: 24.sp,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
          Expanded(
            child: Padding(
              padding: EdgeInsets.symmetric(horizontal: 20.w, vertical: 20.h),
              child: SingleChildScrollView(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: CustomButton(
                            text: "أضافة بطاقة",
                            onTap: () {
                              context.push(AddCreditPage());
                            },
                            textColor: Colors.white,
                            backgroundColor: AppColors.darkBlue,
                          ),
                        ),
                        SizedBox(width: 30.w),
                        Expanded(
                          child: CustomButton(
                            text: "حذف البطاقة",
                            onTap: () {},
                            textColor: Colors.white,
                            backgroundColor: Colors.red,
                          ),
                        ),
                      ],
                    ),
                    SizedBox(height: 30.h),
                    Text(
                      "البطاقات",
                      style: TextStyle(
                        color: Colors.black,
                        fontSize: 20.sp,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    SizedBox(height: 20.h),
                    ListView.separated(
                      physics: BouncingScrollPhysics(),
                      shrinkWrap: true,
                      itemBuilder: (context, index) {
                        return CardNumberWidget();
                      },
                      separatorBuilder: (context, index) {
                        return SizedBox(height: 15.h);
                      },
                      itemCount: 4,
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class CardNumberWidget extends StatelessWidget {
  const CardNumberWidget({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      height: 40.h,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(12.r),
        color: Colors.grey[300],
      ),
      child: Center(
        child: Text(
          "****  ****  ****  7876",
          style: TextStyle(fontSize: 24.sp, fontWeight: FontWeight.bold),
        ),
      ),
    );
  }
}
