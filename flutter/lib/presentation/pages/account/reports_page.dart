import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/services/report_service.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/account/report_detail_page.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class ReportsPage extends StatefulWidget {
  const ReportsPage({super.key});

  @override
  State<ReportsPage> createState() => _ReportsPageState();
}

class _ReportsPageState extends State<ReportsPage> {
  List<ReportModel> _reports = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final res = await ReportService.getReports(page: 1);
    if (mounted) {
      setState(() {
        _reports = (res['reports'] as List<ReportModel>?) ?? [];
        _loading = false;
      });
    }
  }

  String _typeLabel(String type) {
    const keys = {
      'spam': 'type_spam',
      'fraud': 'type_fraud',
      'inappropriate': 'type_inappropriate',
      'duplicate': 'type_duplicate',
      'other': 'type_other',
    };
    final k = keys[type];
    return k != null ? AppLocale.tr(k) : type;
  }

  String _statusLabel(String status) {
    switch (status) {
      case 'pending':
        return AppLocale.tr('status_pending');
      case 'reviewed':
        return AppLocale.tr('status_reviewed');
      case 'resolved':
        return AppLocale.tr('status_resolved');
      default:
        return AppLocale.tr('status_rejected');
    }
  }

  Color _statusColor(String status) {
    switch (status) {
      case 'pending':
        return Colors.orange;
      case 'reviewed':
        return Colors.blue;
      case 'resolved':
        return Colors.green;
      default:
        return Colors.red;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: AppLocale.tr('my_reports')),
      body: _loading
          ? Center(child: CircularProgressIndicator(color: AppColors.darkBlue))
          : _reports.isEmpty
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.flag_outlined, size: 64.sp, color: Colors.grey),
                      SizedBox(height: 16.h),
                      Text(
                        AppLocale.tr('no_reports'),
                        style: TextStyle(
                            fontSize: 16.sp, color: Colors.grey[600]),
                      ),
                    ],
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView.builder(
                    padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 12.h),
                    itemCount: _reports.length,
                    itemBuilder: (context, index) {
                      final r = _reports[index];
                      return Card(
                        margin: EdgeInsets.only(bottom: 12.h),
                        shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12.r)),
                        child: InkWell(
                          onTap: () {
                            context.push(ReportDetailPage(reportId: r.id));
                          },
                          borderRadius: BorderRadius.circular(12.r),
                          child: Padding(
                            padding: EdgeInsets.all(16.w),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Container(
                                      padding: EdgeInsets.symmetric(
                                          horizontal: 10.w, vertical: 4.h),
                                      decoration: BoxDecoration(
                                        color: _statusColor(r.status)
                                            .withValues(alpha: 0.2),
                                        borderRadius:
                                            BorderRadius.circular(8.r),
                                      ),
                                      child: Text(
                                        _statusLabel(r.status),
                                        style: TextStyle(
                                            fontSize: 12.sp,
                                            color: _statusColor(r.status),
                                            fontWeight: FontWeight.w600),
                                      ),
                                    ),
                                    SizedBox(width: 8.w),
                                    Container(
                                      padding: EdgeInsets.symmetric(
                                          horizontal: 10.w, vertical: 4.h),
                                      decoration: BoxDecoration(
                                        color: Colors.grey[200],
                                        borderRadius:
                                            BorderRadius.circular(8.r),
                                      ),
                                      child: Text(
                                        _typeLabel(r.type),
                                        style: TextStyle(
                                            fontSize: 12.sp,
                                            color: Colors.grey[700]),
                                      ),
                                    ),
                                  ],
                                ),
                                SizedBox(height: 12.h),
                                Text(
                                  r.ad != null
                                      ? '${AppLocale.tr('report_about_ad')}: ${r.ad!['title'] ?? ''}'
                                      : r.reportedUser != null
                                          ? '${AppLocale.tr('report_about_user')}: ${r.reportedUser!['name'] ?? ''}'
                                          : r.reason,
                                  style: TextStyle(
                                      fontSize: 14.sp,
                                      fontWeight: FontWeight.w600),
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                ),
                                SizedBox(height: 8.h),
                                Text(
                                  r.reason,
                                  style: TextStyle(
                                      fontSize: 12.sp,
                                      color: Colors.grey[600]),
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                ),
                                SizedBox(height: 8.h),
                                Text(
                                  '${AppLocale.tr('submitted_at')}: ${_formatDate(r.createdAt)}',
                                  style: TextStyle(
                                      fontSize: 11.sp,
                                      color: Colors.grey[500]),
                                ),
                              ],
                            ),
                          ),
                        ),
                      );
                    },
                  ),
                ),
    );
  }

  String _formatDate(String? d) {
    if (d == null) return '—';
    try {
      final dt = DateTime.tryParse(d);
      if (dt != null) return '${dt.year}-${dt.month.toString().padLeft(2, '0')}-${dt.day.toString().padLeft(2, '0')} ${dt.hour}:${dt.minute.toString().padLeft(2, '0')}';
    } catch (_) {}
    return d;
  }
}
