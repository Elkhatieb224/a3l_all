import 'dart:typed_data';

import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/services/report_service.dart';
import 'package:image_picker/image_picker.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/account/reports_page.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class ProblemsPage extends StatefulWidget {
  const ProblemsPage({
    super.key,
    this.adId,
    this.adTitle,
    this.reportedUserId,
    this.reportedUserName,
    this.conversationId,
    this.conversationAdTitle,
    this.conversationOtherUserName,
    this.conversationMessages,
  });

  final int? adId;
  final String? adTitle;
  final int? reportedUserId;
  final String? reportedUserName;
  final int? conversationId;
  final String? conversationAdTitle;
  final String? conversationOtherUserName;
  final List<Map<String, dynamic>>? conversationMessages;

  @override
  State<ProblemsPage> createState() => _ProblemsPageState();
}

class _ProblemsPageState extends State<ProblemsPage> {
  final TextEditingController _reasonController = TextEditingController();
  final ImagePicker _picker = ImagePicker();
  String? _selectedType;
  bool _loading = false;
  final List<XFile> _images = [];

  static const Map<String, String> _types = {
    'spam': 'type_spam',
    'fraud': 'type_fraud',
    'inappropriate': 'type_inappropriate',
    'duplicate': 'type_duplicate',
    'other': 'type_other',
  };

  @override
  void dispose() {
    _reasonController.dispose();
    super.dispose();
  }

  bool get _hasTarget =>
      widget.adId != null ||
      widget.reportedUserId != null ||
      widget.conversationId != null;

  Future<void> _submit() async {
    final reason = _reasonController.text.trim();
    if (reason.isEmpty) {
      showToast(message: AppLocale.tr('enter_report_reason'));
      return;
    }
    if (_selectedType == null) {
      showToast(message: AppLocale.tr('select_type'));
      return;
    }
    setState(() => _loading = true);
    final res = await ReportService.submitReport(
      type: _selectedType!,
      reason: reason,
      adId: widget.adId,
      reportedUserId: widget.reportedUserId,
      conversationId: widget.conversationId,
      images: _images.isEmpty ? null : _images,
    );
    if (!mounted) return;
    setState(() => _loading = false);
    showToast(message: res['message'] as String? ?? AppLocale.tr('report_submitted'));
    if (res['success'] == true) {
      _reasonController.clear();
      setState(() {
        _selectedType = null;
        _images.clear();
      });
      if (mounted) {
        context.pushReplacement(const ReportsPage());
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: AppLocale.tr('submit_report')),
      body: SingleChildScrollView(
        physics: const BouncingScrollPhysics(),
        padding: EdgeInsets.all(16.w),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            if (_hasTarget)
              _buildTargetInfo()
            else
              _buildGeneralHint(),
            SizedBox(height: 24.h),
            Text(
              '${AppLocale.tr('report_type')} *',
              style: TextStyle(
                  fontSize: 14.sp,
                  fontWeight: FontWeight.w600,
                  color: Colors.grey[800]),
            ),
            SizedBox(height: 8.h),
            DropdownButtonFormField<String>(
              value: _selectedType,
              decoration: InputDecoration(
                border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12.r)),
                contentPadding:
                    EdgeInsets.symmetric(horizontal: 16.w, vertical: 14.h),
              ),
              hint: Text(AppLocale.tr('select_type')),
              items: _types.entries
                  .map((e) => DropdownMenuItem<String>(
                        value: e.key,
                        child: Text(AppLocale.tr(e.value)),
                      ))
                  .toList(),
              onChanged: (v) => setState(() => _selectedType = v),
            ),
            SizedBox(height: 20.h),
            Text(
              '${AppLocale.tr('reason')} *',
              style: TextStyle(
                  fontSize: 14.sp,
                  fontWeight: FontWeight.w600,
                  color: Colors.grey[800]),
            ),
            SizedBox(height: 8.h),
            TextFormField(
              controller: _reasonController,
              maxLines: 6,
              decoration: InputDecoration(
                hintText: AppLocale.tr('reason_placeholder'),
                border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12.r)),
                contentPadding:
                    EdgeInsets.symmetric(horizontal: 16.w, vertical: 14.h),
              ),
            ),
            SizedBox(height: 8.h),
            Text(
              AppLocale.tr('reason_hint'),
              style: TextStyle(fontSize: 11.sp, color: Colors.grey[600]),
            ),
            SizedBox(height: 20.h),
            Text(
              AppLocale.tr('attach_images'),
              style: TextStyle(
                  fontSize: 14.sp,
                  fontWeight: FontWeight.w600,
                  color: Colors.grey[800]),
            ),
            SizedBox(height: 8.h),
            GestureDetector(
              onTap: () async {
                try {
                  final files = await _picker.pickMultiImage();
                  if (files.isNotEmpty && mounted) {
                    setState(() => _images.addAll(files));
                  }
                } catch (_) {
                  if (mounted) showToast(message: AppLocale.tr('pick_images_failed'));
                }
              },
              child: Container(
                width: double.infinity,
                padding: EdgeInsets.symmetric(vertical: 16.h),
                decoration: BoxDecoration(
                  border: Border.all(color: AppColors.darkBlue.withValues(alpha: 0.5)),
                  borderRadius: BorderRadius.circular(12.r),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(Icons.add_photo_alternate, color: AppColors.darkBlue, size: 24.sp),
                    SizedBox(width: 8.w),
                    Text(
                      AppLocale.tr('attach_images_hint'),
                      style: TextStyle(fontSize: 13.sp, color: AppColors.darkBlue),
                    ),
                  ],
                ),
              ),
            ),
            if (_images.isNotEmpty) ...[
              SizedBox(height: 12.h),
              SizedBox(
                height: 100.h,
                child: ListView.builder(
                  scrollDirection: Axis.horizontal,
                  itemCount: _images.length,
                  itemBuilder: (context, i) {
                    final f = _images[i];
                    return Padding(
                      padding: EdgeInsets.only(left: 8.w),
                      child: Stack(
                        alignment: Alignment.topRight,
                        children: [
                          ClipRRect(
                            borderRadius: BorderRadius.circular(8.r),
                            child: FutureBuilder<Uint8List>(
                              future: f.readAsBytes(),
                              builder: (_, snap) {
                                if (snap.hasData && snap.data!.isNotEmpty) {
                                  return Image.memory(
                                    snap.data!,
                                    width: 80.w,
                                    height: 80.h,
                                    fit: BoxFit.cover,
                                  );
                                }
                                return Container(
                                  width: 80.w,
                                  height: 80.h,
                                  color: Colors.grey[200],
                                  child: Icon(Icons.image),
                                );
                              },
                            ),
                          ),
                          GestureDetector(
                            onTap: () => setState(() => _images.removeAt(i)),
                            child: CircleAvatar(
                              backgroundColor: Colors.red,
                              radius: 12.r,
                              child: Icon(Icons.close, color: Colors.white, size: 14.sp),
                            ),
                          ),
                        ],
                      ),
                    );
                  },
                ),
              ),
            ],
            SizedBox(height: 28.h),
            Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                TextButton(
                  onPressed: _loading ? null : () => Navigator.pop(context),
                  child: Text(AppLocale.tr('cancel')),
                ),
                SizedBox(width: 12.w),
                FilledButton(
                  onPressed: _loading ? null : _submit,
                  style: FilledButton.styleFrom(
                    backgroundColor: AppColors.darkBlue,
                    padding: EdgeInsets.symmetric(horizontal: 24.w, vertical: 14.h),
                  ),
                  child: Text(
                    _loading ? AppLocale.tr('sending') : AppLocale.tr('submit_report'),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTargetInfo() {
    return Container(
      padding: EdgeInsets.all(16.w),
      decoration: BoxDecoration(
        color: Colors.blue[50],
        borderRadius: BorderRadius.circular(12.r),
        border: Border(
          right: BorderSide(color: Colors.blue, width: 4.w),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            '${AppLocale.tr('reporting_about')}:',
            style: TextStyle(
                fontSize: 14.sp,
                fontWeight: FontWeight.w600,
                color: Colors.blue[900]),
          ),
          SizedBox(height: 12.h),
          if (widget.conversationId != null) ...[
            Row(
              children: [
                Icon(Icons.chat_bubble_outline, size: 18.sp, color: Colors.blue[700]),
                SizedBox(width: 8.w),
                Expanded(
                  child: Text(
                    '${AppLocale.tr('conversation')}: ${widget.conversationAdTitle ?? '-'}',
                    style: TextStyle(fontSize: 13.sp, color: Colors.blue[800]),
                  ),
                ),
              ],
            ),
            if (widget.conversationOtherUserName != null) ...[
              SizedBox(height: 6.h),
              Text(
                '${AppLocale.tr('with_user')}: ${widget.conversationOtherUserName}',
                style: TextStyle(
                    fontSize: 13.sp,
                    color: Colors.blue[800],
                    fontWeight: FontWeight.w500),
              ),
            ],
            if (widget.conversationMessages != null &&
                widget.conversationMessages!.isNotEmpty) ...[
              SizedBox(height: 12.h),
              Container(
                padding: EdgeInsets.all(12.w),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(8.r),
                  border: Border.all(color: Colors.blue[200]!),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      AppLocale.tr('last_messages'),
                      style: TextStyle(
                          fontSize: 12.sp,
                          fontWeight: FontWeight.w600,
                          color: Colors.blue[800]),
                    ),
                    SizedBox(height: 8.h),
                    ...widget.conversationMessages!.take(5).map((m) {
                      final sender = m['sender_name'] ?? '';
                      final msg = (m['message'] ?? '').toString();
                      final truncated =
                          msg.length > 100 ? '${msg.substring(0, 100)}...' : msg;
                      return Padding(
                        padding: EdgeInsets.only(bottom: 6.h),
                        child: Text(
                          '$sender: $truncated',
                          style: TextStyle(
                              fontSize: 11.sp,
                              color: Colors.grey[700]),
                        ),
                      );
                    }),
                  ],
                ),
              ),
            ],
          ],
          if (widget.adId != null && widget.conversationId == null) ...[
            Row(
              children: [
                Icon(Icons.campaign_outlined, size: 18.sp, color: Colors.blue[700]),
                SizedBox(width: 8.w),
                Expanded(
                  child: Text(
                    '${AppLocale.tr('ad')}: ${widget.adTitle ?? '-'}',
                    style: TextStyle(
                        fontSize: 13.sp,
                        color: Colors.blue[800],
                        fontWeight: FontWeight.w500),
                  ),
                ),
              ],
            ),
          ],
          if (widget.reportedUserId != null && widget.conversationId == null) ...[
            if (widget.adId != null) SizedBox(height: 8.h),
            Row(
              children: [
                Icon(Icons.person_outline, size: 18.sp, color: Colors.blue[700]),
                SizedBox(width: 8.w),
                Expanded(
                  child: Text(
                    '${AppLocale.tr('user')}: ${widget.reportedUserName ?? '-'}',
                    style: TextStyle(
                        fontSize: 13.sp,
                        color: Colors.blue[800],
                        fontWeight: FontWeight.w500),
                  ),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildGeneralHint() {
    return Container(
      padding: EdgeInsets.all(16.w),
      decoration: BoxDecoration(
        color: Colors.grey[100],
        borderRadius: BorderRadius.circular(12.r),
        border: Border(
          right: BorderSide(color: Colors.grey[400]!, width: 4.w),
        ),
      ),
      child: Row(
        children: [
          Icon(Icons.info_outline, size: 20.sp, color: Colors.grey[700]),
          SizedBox(width: 12.w),
          Expanded(
            child: Text(
              AppLocale.tr('general_report_hint'),
              style: TextStyle(fontSize: 13.sp, color: Colors.grey[700]),
            ),
          ),
        ],
      ),
    );
  }
}
