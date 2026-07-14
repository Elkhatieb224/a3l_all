import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/presentation/pages/account/submit_verification_page.dart';
import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/data/services/verification_service.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class VerificationPage extends StatefulWidget {
  const VerificationPage({super.key});

  @override
  State<VerificationPage> createState() => _VerificationPageState();
}

class _VerificationPageState extends State<VerificationPage> {
  VerificationData? _data;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final data = await VerificationService.getStatus();
    if (mounted) {
      setState(() {
        _data = data;
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: AppLocale.tr('identity_verification')),
      body: _loading
          ? Center(child: CircularProgressIndicator(color: AppColors.darkBlue))
          : _data == null
              ? Center(
                  child: Text(
                    AppLocale.tr('data_load_failed'),
                    style: TextStyle(fontSize: 16.sp, color: Colors.grey[600]),
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _load,
                  child: SingleChildScrollView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: EdgeInsets.all(20.w),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        _StatusCard(
                          isVerified: _data!.isVerified,
                          pendingRequest: _data!.pendingRequest,
                          lastRequest: _data!.lastRequest,
                        ),
                        if (_data!.verificationRequirements
                            .toString()
                            .trim()
                            .isNotEmpty) ...[
                          SizedBox(height: 20.h),
                          Text(
                            AppLocale.tr('verification_requirements'),
                            style: TextStyle(
                              fontSize: 16.sp,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          SizedBox(height: 8.h),
                          Container(
                            padding: EdgeInsets.all(16.w),
                            decoration: BoxDecoration(
                              color: Colors.grey[100],
                              borderRadius: BorderRadius.circular(12.r),
                            ),
                            child: Text(
                              _data!.verificationRequirements,
                              style: TextStyle(fontSize: 14.sp),
                            ),
                          ),
                        ],
                        if (!_data!.isVerified &&
                            _data!.pendingRequest == null) ...[
                          SizedBox(height: 24.h),
                          Text(
                            AppLocale.tr('verification_request_hint'),
                            style: TextStyle(
                              fontSize: 14.sp,
                              color: Colors.grey[600],
                            ),
                            textAlign: TextAlign.center,
                          ),
                          SizedBox(height: 20.h),
                          GestureDetector(
                            behavior: HitTestBehavior.opaque,
                            onTap: () async {
                              final ok = await Navigator.of(context).push<bool>(
                                MaterialPageRoute(builder: (_) => SubmitVerificationPage()),
                              );
                              if (ok == true && mounted) _load();
                            },
                            child: Container(
                              width: double.infinity,
                              padding: EdgeInsets.symmetric(vertical: 14.h),
                              decoration: BoxDecoration(
                                color: AppColors.darkBlue,
                                borderRadius: BorderRadius.circular(8.r),
                              ),
                              child: Center(
                                child: Text(
                                  AppLocale.tr('submit_verification_request'),
                                  style: TextStyle(
                                    fontSize: 16.sp,
                                    fontWeight: FontWeight.w600,
                                    color: Colors.white,
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                ),
    );
  }
}

class _StatusCard extends StatelessWidget {
  final bool isVerified;
  final Map<String, dynamic>? pendingRequest;
  final Map<String, dynamic>? lastRequest;

  const _StatusCard({
    required this.isVerified,
    this.pendingRequest,
    this.lastRequest,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.all(20.w),
      decoration: BoxDecoration(
        color: isVerified
            ? Colors.green.withValues(alpha: 0.1)
            : Colors.orange.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(16.r),
        border: Border.all(
          color: isVerified ? Colors.green : Colors.orange,
          width: 1,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(
                isVerified ? Icons.verified : Icons.pending_actions,
                size: 32.sp,
                color: isVerified ? Colors.green : Colors.orange,
              ),
              SizedBox(width: 12.w),
              Expanded(
                child: Text(
                  isVerified
                      ? AppLocale.tr('account_verified')
                      : pendingRequest != null
                          ? AppLocale.tr('verification_pending')
                          : lastRequest != null
                              ? '${AppLocale.tr('last_request_status')}: ${lastRequest!['status'] ?? ''}'
                              : AppLocale.tr('account_not_verified'),
                  style: TextStyle(
                    fontSize: 16.sp,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
