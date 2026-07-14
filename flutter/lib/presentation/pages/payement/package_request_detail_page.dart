import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/services/package_service.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';

/// صفحة عرض تفاصيل طلب الباقة ورد الإدارة (تُفتح من الإشعار أو من قائمة الطلبات).
class PackageRequestDetailPage extends StatefulWidget {
  const PackageRequestDetailPage({super.key, required this.requestId});

  final int requestId;

  @override
  State<PackageRequestDetailPage> createState() => _PackageRequestDetailPageState();
}

class _PackageRequestDetailPageState extends State<PackageRequestDetailPage> {
  Map<String, dynamic>? _data;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final data = await PackageService.getPackageRequest(widget.requestId);
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
      appBar: CustomAppbar(title: AppLocale.tr('request_detail')),
      body: _loading
          ? Center(child: CircularProgressIndicator(color: AppColors.darkBlue))
          : _data == null
              ? Center(
                  child: Text(
                    AppLocale.tr('error_loading'),
                    style: TextStyle(fontSize: 16.sp, color: Colors.grey[600]),
                  ),
                )
              : SingleChildScrollView(
                  padding: EdgeInsets.all(16.w),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      _buildRow(AppLocale.tr('package_name'), _data!['package_name']?.toString() ?? '—'),
                      SizedBox(height: 12.h),
                      _buildRow(AppLocale.tr('request_status'), _statusLabel(_data!['status']?.toString() ?? '')),
                      SizedBox(height: 12.h),
                      _buildRow(AppLocale.tr('request_date'), _data!['created_at']?.toString() ?? '—'),
                      if (_data!['responded_at'] != null) ...[
                        SizedBox(height: 12.h),
                        _buildRow(AppLocale.tr('responded_at'), _data!['responded_at']?.toString() ?? '—'),
                      ],
                      if (_data!['admin_response'] != null && (_data!['admin_response'] as String).isNotEmpty) ...[
                        SizedBox(height: 20.h),
                        Text(
                          AppLocale.tr('admin_response'),
                          style: TextStyle(fontSize: 16.sp, fontWeight: FontWeight.w600, color: AppColors.darkBlue),
                        ),
                        SizedBox(height: 8.h),
                        Container(
                          width: double.infinity,
                          padding: EdgeInsets.all(16.w),
                          decoration: BoxDecoration(
                            color: Colors.blue.shade50,
                            borderRadius: BorderRadius.circular(12.r),
                            border: Border.all(color: Colors.blue.shade100),
                          ),
                          child: Text(
                            _data!['admin_response'] as String,
                            style: TextStyle(fontSize: 15.sp, color: Colors.grey[800], height: 1.4),
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
    );
  }

  Widget _buildRow(String label, String value) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: TextStyle(fontSize: 13.sp, color: Colors.grey[600])),
        SizedBox(height: 4.h),
        Text(value, style: TextStyle(fontSize: 16.sp, fontWeight: FontWeight.w600)),
      ],
    );
  }

  String _statusLabel(String? status) {
    switch (status) {
      case 'approved':
        return AppLocale.tr('request_status_approved');
      case 'rejected':
        return AppLocale.tr('request_status_rejected');
      default:
        return AppLocale.tr('request_status_pending');
    }
  }
}
