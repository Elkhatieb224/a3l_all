import 'dart:typed_data';

import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/storage/token_storage.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/services/help_service.dart';
import 'package:a3lnha/presentation/widgets/auth/text_form_with_label.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_button.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:image_picker/image_picker.dart';

class HelpPage extends StatefulWidget {
  const HelpPage({super.key});

  @override
  State<HelpPage> createState() => _HelpPageState();
}

class _HelpPageState extends State<HelpPage> {
  List<FaqItem> _faqs = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final list = await HelpService.getFaqs();
    if (mounted) {
      setState(() {
        _faqs = list;
        _loading = false;
      });
    }
  }

  void _showContactSupportDialog() {
    final subjectController = TextEditingController();
    final messageController = TextEditingController();
    final nameController = TextEditingController();
    final emailController = TextEditingController();
    final supportImages = <XFile>[];
    final picker = ImagePicker();
    var sending = false;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setModalState) {
          final isGuest = !TokenStorage.hasToken();
          return Padding(
            padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
            child: DraggableScrollableSheet(
              initialChildSize: 0.75,
              minChildSize: 0.4,
              maxChildSize: 0.95,
              builder: (_, scrollController) => Container(
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.vertical(top: Radius.circular(16.r)),
                ),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Flexible(
                      child: SingleChildScrollView(
                        controller: scrollController,
                        padding: EdgeInsets.all(20.w),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              AppLocale.tr('contact_support'),
                              style: TextStyle(
                                fontSize: 18.sp,
                                fontWeight: FontWeight.bold,
                                color: AppColors.darkBlue,
                              ),
                            ),
                            SizedBox(height: 16.h),
                            if (isGuest) ...[
                              TextFormWithLabel(
                                labelText: AppLocale.tr('name'),
                                hintText: AppLocale.tr('name'),
                                controller: nameController,
                                keyboardType: TextInputType.name,
                                obscureText: false,
                                maxlines: 1,
                              ),
                              SizedBox(height: 12.h),
                              TextFormWithLabel(
                                labelText: AppLocale.tr('email'),
                                hintText: AppLocale.tr('email'),
                                controller: emailController,
                                keyboardType: TextInputType.emailAddress,
                                obscureText: false,
                                maxlines: 1,
                              ),
                              SizedBox(height: 12.h),
                            ],
                            TextFormWithLabel(
                              labelText: AppLocale.tr('support_subject'),
                              hintText: AppLocale.tr('support_subject_hint'),
                              controller: subjectController,
                              keyboardType: TextInputType.text,
                              obscureText: false,
                              maxlines: 1,
                            ),
                            SizedBox(height: 12.h),
                            TextFormWithLabel(
                              labelText: AppLocale.tr('support_message'),
                              hintText: AppLocale.tr('support_message_hint'),
                              controller: messageController,
                              keyboardType: TextInputType.multiline,
                              obscureText: false,
                              maxlines: 5,
                            ),
                            SizedBox(height: 16.h),
                            Text(
                              AppLocale.tr('support_attach_screenshot'),
                              style: TextStyle(
                                fontSize: 14.sp,
                                fontWeight: FontWeight.w600,
                                color: Colors.grey[800],
                              ),
                            ),
                            SizedBox(height: 8.h),
                            GestureDetector(
                              onTap: () async {
                                try {
                                  final files = await picker.pickMultiImage();
                                  if (files.isNotEmpty) {
                                    supportImages.addAll(files);
                                    setModalState(() {});
                                  }
                                } catch (_) {
                                  if (ctx.mounted) showToast(message: AppLocale.tr('pick_images_failed'));
                                }
                              },
                              child: Container(
                                width: double.infinity,
                                padding: EdgeInsets.symmetric(vertical: 14.h),
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
                            if (supportImages.isNotEmpty) ...[
                              SizedBox(height: 12.h),
                              SizedBox(
                                height: 90.h,
                                child: ListView.builder(
                                  scrollDirection: Axis.horizontal,
                                  itemCount: supportImages.length,
                                  itemBuilder: (context, i) {
                                    final f = supportImages[i];
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
                                                    width: 72.w,
                                                    height: 72.h,
                                                    fit: BoxFit.cover,
                                                  );
                                                }
                                                return Container(
                                                  width: 72.w,
                                                  height: 72.h,
                                                  color: Colors.grey[200],
                                                  child: Icon(Icons.image),
                                                );
                                              },
                                            ),
                                          ),
                                          GestureDetector(
                                            onTap: () {
                                              supportImages.removeAt(i);
                                              setModalState(() {});
                                            },
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
                            SizedBox(height: 24.h),
                            CustomButton(
                              text: sending ? AppLocale.tr('sending') : AppLocale.tr('send'),
                              onTap: sending ? () {} : () async {
                                final subject = subjectController.text.trim();
                                final message = messageController.text.trim();
                                if (subject.isEmpty) {
                                  showToast(message: AppLocale.tr('support_subject_required'));
                                  return;
                                }
                                if (message.isEmpty) {
                                  showToast(message: AppLocale.tr('support_message_required'));
                                  return;
                                }
                                if (isGuest) {
                                  final name = nameController.text.trim();
                                  final email = emailController.text.trim();
                                  if (name.isEmpty || email.isEmpty) {
                                    showToast(message: AppLocale.tr('guest_name_email_required'));
                                    return;
                                  }
                                }
                                sending = true;
                                setModalState(() {});
                                final res = await HelpService.sendSupportMessage(
                                  subject: subject,
                                  message: message,
                                  name: isGuest ? nameController.text.trim() : null,
                                  email: isGuest ? emailController.text.trim() : null,
                                  imagePaths: supportImages.isEmpty ? null : supportImages.map((x) => x.path).toList(),
                                );
                                if (!ctx.mounted) return;
                                Navigator.pop(ctx);
                                final msg = res['message'] as String?;
                                final isGuestError = msg != null &&
                                    (msg.contains('Name and email') || msg.contains('guest') || msg.contains('required'));
                                showToast(
                                  message: res['success'] == true
                                      ? AppLocale.tr('message_sent')
                                      : (isGuestError ? AppLocale.tr('guest_name_email_required') : (msg ?? AppLocale.tr('failed'))),
                                );
                              },
                              backgroundColor: AppColors.darkBlue,
                              textColor: Colors.white,
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    ).whenComplete(() {
      subjectController.dispose();
      messageController.dispose();
      nameController.dispose();
      emailController.dispose();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: AppLocale.tr('help')),
      body: SingleChildScrollView(
        physics: const BouncingScrollPhysics(),
        padding: EdgeInsets.symmetric(horizontal: 20.w, vertical: 16.h),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            GestureDetector(
              onTap: _showContactSupportDialog,
              child: Container(
                width: double.infinity,
                padding: EdgeInsets.all(16.w),
                decoration: BoxDecoration(
                  color: AppColors.darkBlue.withValues(alpha: 0.08),
                  borderRadius: BorderRadius.circular(12.r),
                  border: Border.all(color: AppColors.darkBlue.withValues(alpha: 0.3)),
                ),
                child: Row(
                  children: [
                    Icon(Icons.support_agent, size: 36.sp, color: AppColors.darkBlue),
                    SizedBox(width: 16.w),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            AppLocale.tr('contact_support'),
                            style: TextStyle(
                              fontSize: 16.sp,
                              fontWeight: FontWeight.bold,
                              color: AppColors.darkBlue,
                            ),
                          ),
                          SizedBox(height: 4.h),
                          Text(
                            AppLocale.tr('support_message_hint'),
                            style: TextStyle(fontSize: 12.sp, color: Colors.grey[600]),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                      ),
                    ),
                    Icon(Icons.arrow_forward_ios, size: 16.sp, color: AppColors.darkBlue),
                  ],
                ),
              ),
            ),
            SizedBox(height: 24.h),
            Text(
              AppLocale.tr('faqs_section'),
              style: TextStyle(
                fontSize: 16.sp,
                fontWeight: FontWeight.bold,
                color: AppColors.darkBlue,
              ),
            ),
            SizedBox(height: 12.h),
            _loading
                ? Center(
                    child: Padding(
                      padding: EdgeInsets.symmetric(vertical: 40.h),
                      child: CircularProgressIndicator(color: AppColors.darkBlue),
                    ),
                  )
                : _faqs.isEmpty
                    ? Padding(
                        padding: EdgeInsets.symmetric(vertical: 24.h),
                        child: Center(
                          child: Text(
                            AppLocale.tr('no_faqs'),
                            style: TextStyle(fontSize: 14.sp, color: Colors.grey[600]),
                          ),
                        ),
                      )
                    : Column(
                        children: _faqs
                            .map(
                              (faq) => HelpQuestionItem(
                                question: faq.question,
                                answer: faq.answer,
                              ),
                            )
                            .toList(),
                      ),
          ],
        ),
      ),
    );
  }
}

class HelpQuestionItem extends StatelessWidget {
  final String question;
  final String answer;

  const HelpQuestionItem({
    super.key,
    required this.question,
    required this.answer,
  });

  @override
  Widget build(BuildContext context) {
    return ExpansionTile(
      title: Text(
        question,
        style: TextStyle(
          color: Colors.black,
          fontWeight: FontWeight.w600,
          fontSize: 14.sp,
        ),
      ),
      children: <Widget>[
        Padding(
          padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 8.h),
          child: Text(
            answer,
            style: TextStyle(
              fontSize: 13.sp,
              fontWeight: FontWeight.w400,
              color: Colors.black87,
              height: 1.5,
            ),
          ),
        ),
      ],
    );
  }
}
