import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/services/report_service.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/home/ad_details_page.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class ReportDetailPage extends StatefulWidget {
  final int reportId;

  const ReportDetailPage({super.key, required this.reportId});

  @override
  State<ReportDetailPage> createState() => _ReportDetailPageState();
}

class _ReportDetailPageState extends State<ReportDetailPage> {
  ReportModel? _report;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final r = await ReportService.getReport(widget.reportId);
    if (mounted) {
      setState(() {
        _report = r;
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

  String _formatDate(String? d) {
    if (d == null) return '—';
    try {
      final dt = DateTime.tryParse(d);
      if (dt != null) {
        return '${dt.year}-${dt.month.toString().padLeft(2, '0')}-${dt.day.toString().padLeft(2, '0')} ${dt.hour}:${dt.minute.toString().padLeft(2, '0')}';
      }
    } catch (_) {}
    return d;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: AppLocale.tr('report_details')),
      body: _loading
          ? Center(child: CircularProgressIndicator(color: AppColors.darkBlue))
          : _report == null
              ? Center(child: Text(AppLocale.tr('no_reports')))
              : SingleChildScrollView(
                  padding: EdgeInsets.all(16.w),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Container(
                        padding: EdgeInsets.symmetric(
                            horizontal: 14.w, vertical: 8.h),
                        decoration: BoxDecoration(
                          color: _statusColor(_report!.status)
                              .withValues(alpha: 0.2),
                          borderRadius: BorderRadius.circular(12.r),
                        ),
                        child: Text(
                          _statusLabel(_report!.status),
                          style: TextStyle(
                              fontSize: 14.sp,
                              color: _statusColor(_report!.status),
                              fontWeight: FontWeight.w600),
                        ),
                      ),
                      SizedBox(height: 20.h),
                      _buildInfoRow(
                          AppLocale.tr('report_type'), _typeLabel(_report!.type)),
                      _buildInfoRow(
                          AppLocale.tr('submitted_at'),
                          _formatDate(_report!.createdAt)),
                      if (_report!.ad != null) ...[
                        SizedBox(height: 12.h),
                        _buildSection(
                          AppLocale.tr('report_about_ad'),
                          _report!.ad!['title']?.toString() ?? '',
                          trailing: TextButton(
                            onPressed: () {
                              final uid = _report!.ad!['uid']?.toString();
                              if (uid != null) {
                                context.push(AdDetailsPage(adUid: uid));
                              }
                            },
                            child: Text(AppLocale.tr('view_ad')),
                          ),
                        ),
                      ],
                      if (_report!.reportedUser != null) ...[
                        SizedBox(height: 12.h),
                        _buildSection(
                          AppLocale.tr('report_about_user'),
                          _report!.reportedUser!['name']?.toString() ?? '',
                        ),
                      ],
                      SizedBox(height: 20.h),
                      Text(
                        AppLocale.tr('reason'),
                        style: TextStyle(
                            fontSize: 16.sp, fontWeight: FontWeight.w600),
                      ),
                      SizedBox(height: 8.h),
                      Container(
                        width: double.infinity,
                        padding: EdgeInsets.all(16.w),
                        decoration: BoxDecoration(
                          color: Colors.grey[100],
                          borderRadius: BorderRadius.circular(12.r),
                        ),
                        child: Text(
                          _report!.reason,
                          style: TextStyle(fontSize: 14.sp),
                        ),
                      ),
                      if (_report!.adminResponse != null &&
                          _report!.adminResponse!.trim().isNotEmpty) ...[
                        SizedBox(height: 20.h),
                        Container(
                          width: double.infinity,
                          padding: EdgeInsets.all(16.w),
                          decoration: BoxDecoration(
                            color: Colors.blue[50],
                            borderRadius: BorderRadius.circular(12.r),
                            border: Border(
                              right: BorderSide(
                                  color: Colors.blue, width: 4.w),
                            ),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                AppLocale.tr('admin_response'),
                                style: TextStyle(
                                    fontSize: 16.sp,
                                    fontWeight: FontWeight.w600,
                                    color: Colors.blue[900]),
                              ),
                              SizedBox(height: 8.h),
                              Text(
                                _report!.adminResponse!,
                                style: TextStyle(
                                    fontSize: 14.sp, color: Colors.blue[800]),
                              ),
                            ],
                          ),
                        ),
                      ] else ...[
                        SizedBox(height: 20.h),
                        Container(
                          padding: EdgeInsets.all(16.w),
                          decoration: BoxDecoration(
                            color: Colors.orange[50],
                            borderRadius: BorderRadius.circular(12.r),
                            border: Border(
                              right: BorderSide(
                                  color: Colors.orange, width: 4.w),
                            ),
                          ),
                          child: Row(
                            children: [
                              Icon(Icons.schedule, color: Colors.orange[700]),
                              SizedBox(width: 8.w),
                              Text(
                                AppLocale.tr('pending_review'),
                                style: TextStyle(
                                    fontSize: 14.sp,
                                    color: Colors.orange[800],
                                    fontWeight: FontWeight.w500),
                              ),
                            ],
                          ),
                        ),
                      ],
                      if (_report!.conversationMessages != null &&
                          _report!.conversationMessages!.isNotEmpty) ...[
                        SizedBox(height: 20.h),
                        Text(
                          AppLocale.tr('conversation_messages'),
                          style: TextStyle(
                              fontSize: 16.sp, fontWeight: FontWeight.w600),
                        ),
                        SizedBox(height: 8.h),
                        ...(_report!.conversationMessages as List)
                            .map((m) => _buildMessageTile(m)),
                      ],
                      SizedBox(height: 24.h),
                      Align(
                        alignment: Alignment.centerLeft,
                        child: TextButton.icon(
                          onPressed: () => Navigator.pop(context),
                          icon: Icon(Icons.arrow_back, size: 18.sp),
                          label: Text(AppLocale.tr('back')),
                        ),
                      ),
                    ],
                  ),
                ),
    );
  }

  Widget _buildInfoRow(String label, String value) {
    return Padding(
      padding: EdgeInsets.only(bottom: 8.h),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 120.w,
            child: Text(label,
                style: TextStyle(
                    fontSize: 13.sp, color: Colors.grey[600])),
          ),
          Expanded(
              child: Text(value,
                  style: TextStyle(
                      fontSize: 14.sp, fontWeight: FontWeight.w500))),
        ],
      ),
    );
  }

  Widget _buildSection(String title, String content,
      {Widget? trailing}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(title,
            style: TextStyle(fontSize: 13.sp, color: Colors.grey[600])),
        SizedBox(height: 4.h),
        Row(
          children: [
            Expanded(
                child: Text(content,
                    style: TextStyle(
                        fontSize: 14.sp, fontWeight: FontWeight.w500))),
            if (trailing != null) trailing,
          ],
        ),
      ],
    );
  }

  Widget _buildMessageTile(dynamic m) {
    if (m is! Map) return const SizedBox.shrink();
    final map = Map<String, dynamic>.from(m);
    final senderName = map['sender_name']?.toString() ?? '';
    final message = map['message']?.toString() ?? '';
    final createdAt = map['created_at']?.toString() ?? '';
    return Container(
      margin: EdgeInsets.only(bottom: 8.h),
      padding: EdgeInsets.all(12.w),
      decoration: BoxDecoration(
        color: Colors.grey[100],
        borderRadius: BorderRadius.circular(8.r),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(senderName,
              style: TextStyle(
                  fontSize: 12.sp,
                  fontWeight: FontWeight.w600,
                  color: AppColors.darkBlue)),
          SizedBox(height: 4.h),
          Text(message, style: TextStyle(fontSize: 13.sp)),
          if (createdAt.isNotEmpty)
            Text(_formatDate(createdAt),
                style: TextStyle(fontSize: 11.sp, color: Colors.grey[500])),
        ],
      ),
    );
  }
}
