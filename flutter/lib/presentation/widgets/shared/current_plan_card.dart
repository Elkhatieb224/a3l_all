import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/services/package_service.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

/// بطاقة الخطة الحالية: تفاصيل الباقة، الحدود المتبقية، والمميزات — قابلة للاستخدام في صفحة الحساب وصفحة الباقات
class CurrentPlanCard extends StatelessWidget {
  final CurrentPlanInfo plan;
  final VoidCallback? onCreateAd;

  const CurrentPlanCard({
    super.key,
    required this.plan,
    this.onCreateAd,
  });

  @override
  Widget build(BuildContext context) {
    DateTime? exp;
    if (plan.expiresAt != null && plan.expiresAt!.isNotEmpty) {
      exp = DateTime.tryParse(plan.expiresAt!);
    }
    final expiryStr = exp != null
        ? '${exp.year}-${exp.month.toString().padLeft(2, '0')}-${exp.day.toString().padLeft(2, '0')}'
        : (plan.expiresAt ?? '');

    return Container(
      padding: EdgeInsets.all(16.w),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12.r),
        border: Border.all(color: AppColors.darkBlue.withOpacity(0.25), width: 1),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.06),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Container(
            height: 4.h,
            width: double.infinity,
            margin: EdgeInsets.only(bottom: 12.h),
            decoration: BoxDecoration(
              color: AppColors.darkBlue,
              borderRadius: BorderRadius.circular(2.r),
            ),
          ),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      AppLocale.tr('your_current_plan'),
                      style: TextStyle(
                        fontSize: 14.sp,
                        fontWeight: FontWeight.w600,
                        color: Colors.grey[700],
                      ),
                    ),
                    SizedBox(height: 4.h),
                    Text(
                      plan.planName,
                      style: TextStyle(
                        fontSize: 18.sp,
                        fontWeight: FontWeight.w700,
                        color: AppColors.darkBlue,
                      ),
                    ),
                  ],
                ),
              ),
              if (plan.remainingAds > 0 && onCreateAd != null)
                ElevatedButton(
                  onPressed: onCreateAd,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.darkBlue,
                    foregroundColor: Colors.white,
                    padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 10.h),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8.r),
                    ),
                  ),
                  child: Text(
                    AppLocale.tr('create_ad'),
                    style: TextStyle(fontSize: 13.sp),
                  ),
                ),
            ],
          ),
          SizedBox(height: 16.h),
          Wrap(
            spacing: 10.w,
            runSpacing: 10.h,
            children: [
              _LimitChip(
                label: AppLocale.tr('remaining_ads'),
                value: plan.unlimitedRegularAds
                    ? AppLocale.tr('unlimited_ads')
                    : '${plan.remainingAds} / ${plan.adsLimit}',
                color: AppColors.darkBlue,
              ),
              if (plan.featuredLimit > 0)
                _LimitChip(
                  label: AppLocale.tr('featured_ads'),
                  value: '${plan.remainingFeatured} / ${plan.featuredLimit}',
                  color: Colors.amber.shade700,
                ),
              if (plan.urgentLimit > 0)
                _LimitChip(
                  label: AppLocale.tr('urgent_ads_label'),
                  value: '${plan.remainingUrgent} / ${plan.urgentLimit}',
                  color: Colors.orange.shade700,
                ),
              if (expiryStr.isNotEmpty)
                _LimitChip(
                  label: AppLocale.tr('expires_at'),
                  value: expiryStr,
                  color: Colors.grey.shade700,
                ),
            ],
          ),
          if (plan.features.isNotEmpty) ...[
            SizedBox(height: 14.h),
            Text(
              AppLocale.tr('your_features'),
              style: TextStyle(
                fontSize: 13.sp,
                fontWeight: FontWeight.w600,
                color: Colors.grey[700],
              ),
            ),
            SizedBox(height: 8.h),
            Wrap(
              spacing: 8.w,
              runSpacing: 6.h,
              children: plan.features
                  .map((f) => Container(
                        padding: EdgeInsets.symmetric(horizontal: 10.w, vertical: 6.h),
                        decoration: BoxDecoration(
                          color: Colors.green.shade50,
                          borderRadius: BorderRadius.circular(20.r),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.check_circle, color: Colors.green.shade600, size: 16.sp),
                            SizedBox(width: 4.w),
                            Flexible(
                              child: Text(
                                f,
                                style: TextStyle(fontSize: 12.sp, color: Colors.green.shade800),
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ],
                        ),
                      ))
                  .toList(),
            ),
          ],
        ],
      ),
    );
  }
}

class _LimitChip extends StatelessWidget {
  final String label;
  final String value;
  final Color color;

  const _LimitChip({required this.label, required this.value, required this.color});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.symmetric(horizontal: 12.w, vertical: 8.h),
      decoration: BoxDecoration(
        color: color.withOpacity(0.12),
        borderRadius: BorderRadius.circular(8.r),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            label,
            style: TextStyle(fontSize: 11.sp, color: Colors.grey[700]),
          ),
          SizedBox(height: 2.h),
          Text(
            value,
            style: TextStyle(fontSize: 14.sp, fontWeight: FontWeight.w700, color: color),
          ),
        ],
      ),
    );
  }
}
