import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/storage/token_storage.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/models/ad_model.dart';
import 'package:a3lnha/data/services/ad_service.dart';
import 'package:a3lnha/data/services/auth_service.dart';
import 'package:a3lnha/data/services/blocked_user_service.dart';
import 'package:a3lnha/data/services/message_service.dart';
import 'package:a3lnha/data/services/report_service.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/account/on_air_ads_page.dart';
import 'package:a3lnha/presentation/pages/auth/login_page.dart';
import 'package:a3lnha/presentation/pages/home/ad_details_page.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class ChatPage extends StatefulWidget {
  final String? adUid;
  final String? sellerSlug;
  final String? sellerName;
  final int? conversationId;

  const ChatPage({super.key, this.adUid, this.sellerSlug, this.sellerName, this.conversationId});

  @override
  State<ChatPage> createState() => _ChatPageState();
}

class _ChatPageState extends State<ChatPage> {
  int? _conversationId;
  ChatData? _chatData;
  int? _currentUserId;
  bool _loading = true;
  final TextEditingController _messageController = TextEditingController();
  bool _sending = false;
  Future<AdDetailsResponse?>? _adDetailsFuture;

  @override
  void initState() {
    super.initState();
    if (!TokenStorage.hasToken()) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (!mounted) return;
        context.push(LoginPage());
        Navigator.of(context).pop();
      });
      return;
    }
    if (widget.adUid != null) {
      _adDetailsFuture = AdService.getAdDetails(widget.adUid!);
    }
    _initChat();
  }

  @override
  void dispose() {
    _messageController.dispose();
    super.dispose();
  }

  Future<void> _initChat() async {
    int? cid = widget.conversationId;
    if (cid == null && widget.adUid != null) {
      cid = await MessageService.createOrGetConversation(widget.adUid!);
    } else if (cid == null && widget.sellerSlug != null && widget.sellerSlug!.trim().isNotEmpty) {
      cid = await MessageService.createOrGetConversationWithSeller(widget.sellerSlug!.trim());
    }
    if (cid == null) {
      final me = await AuthService.getMe();
      if (mounted) {
        if (me.user != null) _currentUserId = me.user!.id;
        setState(() => _loading = false);
      }
      return;
    }
    _conversationId = cid;
    final meFuture = AuthService.getMe();
    final dataFuture = MessageService.getConversation(cid);
    final me = await meFuture;
    final data = await dataFuture;
    if (mounted) {
      if (me.user != null) _currentUserId = me.user!.id;
      if (_adDetailsFuture == null && data?.adUid != null) {
        _adDetailsFuture = AdService.getAdDetails(data!.adUid!);
      }
      setState(() {
        _chatData = data;
        _loading = false;
      });
    }
  }

  Future<void> _loadMessages() async {
    if (_conversationId == null) return;
    final data = await MessageService.getConversation(_conversationId!);
    if (mounted) setState(() {
      _chatData = data;
      _loading = false;
    });
  }

  Future<void> _sendMessage() async {
    final text = _messageController.text.trim();
    if (text.isEmpty || _conversationId == null) return;
    if (_sending) return;
    setState(() => _sending = true);
    _messageController.clear();
    final myId = _currentUserId;
    final tempId = DateTime.now().millisecondsSinceEpoch;
    if (mounted && _chatData != null && myId != null) {
      setState(() {
        _chatData = ChatData(
          adUid: _chatData!.adUid,
          adTitle: _chatData!.adTitle,
          ad: _chatData!.ad,
          otherUserId: _chatData!.otherUserId,
          otherUserName: _chatData!.otherUserName,
          isOtherUserBlocked: _chatData!.isOtherUserBlocked,
          messagingRules: _chatData!.messagingRules,
          messages: [
            ..._chatData!.messages,
            MessageModel(id: -tempId, message: text, senderId: myId, createdAt: null, sender: null),
          ],
        );
      });
    }
    final sent = await MessageService.sendMessage(_conversationId!, text);
    if (mounted) {
      setState(() => _sending = false);
      if (sent != null) _loadMessages();
    }
  }

  void _showReportBlockMenu() {
    final size = MediaQuery.sizeOf(context);
    final position = RelativeRect.fromLTRB(
      size.width - 200,
      56,
      size.width - 8,
      57,
    );
    final isBlocked = _chatData?.isOtherUserBlocked ?? false;
    showMenu<int>(
      context: context,
      position: position,
      color: const Color(0xFF0A3B78),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      items: [
        _buildMenuItem(2, AppLocale.tr('report'), Icons.flag),
        _buildMenuItem(
          isBlocked ? 4 : 3,
          isBlocked ? AppLocale.tr('unblock') : AppLocale.tr('block'),
          isBlocked ? Icons.person_add_outlined : Icons.person_off_outlined,
        ),
      ],
    ).then((value) {
      if (value != null) _onMenuSelected(value);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(
        title: widget.sellerName ?? AppLocale.tr('messages'),
        isChat: true,
        onChatMenuTap: _showReportBlockMenu,
      ),
      body: Column(
        children: [
          _buildAdHeader(),
          if (_chatData?.isOtherUserBlocked == true)
            Container(
              width: double.infinity,
              padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 10.h),
              color: Colors.orange.shade50,
              child: Row(
                children: [
                  Icon(Icons.block, color: Colors.orange.shade700, size: 20.sp),
                  SizedBox(width: 10.w),
                  Expanded(
                    child: Text(
                      AppLocale.tr('you_have_blocked_this_user'),
                      style: TextStyle(
                        fontSize: 12.sp,
                        color: Colors.orange.shade900,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          Expanded(
            child: _loading
                ? Center(child: CircularProgressIndicator(color: AppColors.darkBlue))
                : SingleChildScrollView(
                    reverse: true,
                    child: Column(
                      children: [
                        if (_chatData?.messagingRules != null &&
                            _chatData!.messagingRules!.trim().isNotEmpty)
                          Padding(
                            padding: EdgeInsets.all(16.w),
                            child: Container(
                              width: double.infinity,
                              padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 16.h),
                              decoration: BoxDecoration(
                                color: AppColors.darkBlue,
                                borderRadius: BorderRadius.circular(12.r),
                              ),
                              child: Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                crossAxisAlignment: CrossAxisAlignment.center,
                                children: [
                                  Image.asset(
                                    'assets/images/مايك 1.png',
                                    height: 40.h,
                                    width: 40.w,
                                    fit: BoxFit.contain,
                                    errorBuilder: (_, __, ___) => Icon(Icons.campaign_rounded, size: 40.sp, color: AppColors.yellow),
                                  ),
                                  SizedBox(height: 10.h),
                                  Row(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      Icon(Icons.warning_amber_rounded, size: 20.sp, color: AppColors.yellow),
                                      SizedBox(width: 6.w),
                                      Text(
                                        AppLocale.tr('important_alert'),
                                        style: TextStyle(
                                          fontSize: 14.sp,
                                          fontWeight: FontWeight.bold,
                                          color: Colors.white,
                                        ),
                                      ),
                                    ],
                                  ),
                                  SizedBox(height: 8.h),
                                  SelectableText(
                                    _stripHtml(_chatData!.messagingRules!),
                                    style: TextStyle(
                                      fontSize: 11.sp,
                                      color: Colors.white,
                                      height: 1.4,
                                    ),
                                    textAlign: TextAlign.center,
                                  ),
                                ],
                              ),
                            ),
                          )
                        else
                          Padding(
                            padding: EdgeInsets.all(16.w),
                            child: Container(
                              width: double.infinity,
                              padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 16.h),
                              decoration: BoxDecoration(
                                color: AppColors.darkBlue,
                                borderRadius: BorderRadius.circular(12.r),
                              ),
                              child: Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                crossAxisAlignment: CrossAxisAlignment.center,
                                children: [
                                  Image.asset(
                                    'assets/images/مايك 1.png',
                                    height: 40.h,
                                    width: 40.w,
                                    fit: BoxFit.contain,
                                    errorBuilder: (_, __, ___) => Icon(Icons.campaign_rounded, size: 40.sp, color: AppColors.yellow),
                                  ),
                                  SizedBox(height: 10.h),
                                  Row(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      Icon(Icons.warning_amber_rounded, size: 20.sp, color: AppColors.yellow),
                                      SizedBox(width: 6.w),
                                      Text(
                                        AppLocale.tr('important_alert'),
                                        style: TextStyle(
                                          fontSize: 14.sp,
                                          fontWeight: FontWeight.bold,
                                          color: Colors.white,
                                        ),
                                      ),
                                    ],
                                  ),
                                  SizedBox(height: 8.h),
                                  SelectableText(
                                    AppLocale.tr('messaging_rules_default'),
                                    style: TextStyle(
                                      fontSize: 11.sp,
                                      color: Colors.white,
                                      height: 1.4,
                                    ),
                                    textAlign: TextAlign.center,
                                  ),
                                ],
                              ),
                            ),
                          ),
                        if (_chatData != null && _chatData!.messages.isNotEmpty)
                          ..._chatData!.messages.map((m) => _ChatBubble(
                                message: m.message,
                                isMe: _currentUserId != null ? m.senderId == _currentUserId : false,
                                createdAt: m.createdAt,
                                isRead: m.isRead,
                              )),
                      ],
                    ),
                  ),
          ),
          Padding(
            padding: EdgeInsets.all(20.w),
            child: Row(
              children: [
                Expanded(
                  child: TextFormField(
                    controller: _messageController,
                    readOnly: _chatData?.isOtherUserBlocked == true,
                    decoration: InputDecoration(
                      hintText: _chatData?.isOtherUserBlocked == true
                          ? AppLocale.tr('you_have_blocked_this_user')
                          : AppLocale.tr('type_here'),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(10.r),
                        borderSide: BorderSide.none,
                      ),
                      fillColor: Colors.grey[200],
                      filled: true,
                      contentPadding: EdgeInsets.symmetric(horizontal: 12.w, vertical: 12.h),
                    ),
                    onFieldSubmitted: (_) => _sendMessage(),
                  ),
                ),
                SizedBox(width: 8.w),
                IconButton(
                  onPressed: (_sending || _conversationId == null || _chatData?.isOtherUserBlocked == true) ? null : _sendMessage,
                  icon: Icon(Icons.send, color: AppColors.darkBlue),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  String _stripHtml(String html) {
    return html
        .replaceAll(RegExp(r'<[^>]*>'), ' ')
        .replaceAll(RegExp(r'&nbsp;'), ' ')
        .replaceAll(RegExp(r'&amp;'), '&')
        .replaceAll(RegExp(r'&lt;'), '<')
        .replaceAll(RegExp(r'&gt;'), '>')
        .replaceAll(RegExp(r'\s+'), ' ')
        .trim();
  }

  Widget _buildAdHeader() {
    final adUid = widget.adUid ?? _chatData?.adUid;
    if (adUid == null) return const SizedBox.shrink();
    // Always load full ad details so image, location, date, views, favorites appear
    final future = _adDetailsFuture ??= AdService.getAdDetails(adUid);
    final card = FutureBuilder<AdDetailsResponse?>(
      future: future,
      builder: (_, snap) {
        if (snap.hasData && snap.data?.ad != null) {
          return AccountAdWidget(
          ad: snap.data!.ad,
          isFavourite: true,
          // Keep chat header compact and consistent regardless of entry source.
          showMessagesAndFavoritesCount: false,
          showViewsCount: false,
        );
        }
        if (snap.connectionState == ConnectionState.waiting) {
          return Padding(
            padding: EdgeInsets.symmetric(horizontal: 20.w, vertical: 16.h),
            child: Row(
              children: [
                SizedBox(
                  width: 70.w,
                  height: 70.h,
                  child: const Center(child: CircularProgressIndicator()),
                ),
                SizedBox(width: 12.w),
                Expanded(
                  child: Text(
                    _chatData?.adTitle ?? adUid,
                    style: TextStyle(fontSize: 14.sp),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
          );
        }
        return const SizedBox.shrink();
      },
    );
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: () => context.push(AdDetailsPage(adUid: adUid)),
      child: card,
    );
  }

  void _onMenuSelected(int value) {
    if (value == 2) {
      _showReportDialog();
    } else if (value == 3) {
      _blockUser();
    } else if (value == 4) {
      _unblockUser();
    }
  }

  Future<void> _unblockUser() async {
    final otherId = _chatData?.otherUserId;
    if (otherId == null) {
      showToast(message: AppLocale.tr('cannot_unblock'));
      return;
    }
    final res = await BlockedUserService.unblockUser(otherId);
    if (!mounted) return;
    showToast(message: res['message'] as String? ?? '');
    if (res['success'] == true) {
      await _loadMessages();
      if (mounted) setState(() {});
    }
  }

  Future<void> _blockUser() async {
    final otherId = _chatData?.otherUserId;
    if (otherId == null) {
      showToast(message: AppLocale.tr('cannot_block'));
      return;
    }
    final res = await BlockedUserService.blockUser(otherId);
    if (!mounted) return;
    showToast(message: res['message'] as String? ?? '');
    if (res['success'] == true) {
      Navigator.of(context).pop();
    }
  }

  void _showReportDialog() {
    final otherId = _chatData?.otherUserId;
    final otherName = _chatData?.otherUserName ?? '';
    if (otherId == null) {
      showToast(message: AppLocale.tr('cannot_report'));
      return;
    }
    showDialog(
      context: context,
      builder: (ctx) => _ReportUserDialog(
        reportedUserId: otherId,
        reportedUserName: otherName,
        conversationId: _conversationId,
        onSuccess: () {
          Navigator.of(ctx).pop();
          showToast(message: AppLocale.tr('report_sent'));
        },
      ),
    );
  }

  PopupMenuItem<int> _buildMenuItem(int value, String text, IconData icon) {
    return PopupMenuItem<int>(
      value: value,
      child: Row(
        children: [
          Icon(icon, color: Colors.white),
          SizedBox(width: 8.w),
          Expanded(
            child: Text(text, style: const TextStyle(color: Colors.white), textAlign: TextAlign.right),
          ),
        ],
      ),
    );
  }
}

class _ReportUserDialog extends StatefulWidget {
  final int reportedUserId;
  final String reportedUserName;
  final int? conversationId;
  final VoidCallback onSuccess;

  const _ReportUserDialog({
    required this.reportedUserId,
    required this.reportedUserName,
    this.conversationId,
    required this.onSuccess,
  });

  @override
  State<_ReportUserDialog> createState() => _ReportUserDialogState();
}

class _ReportUserDialogState extends State<_ReportUserDialog> {
  final _reasonController = TextEditingController();
  String _type = 'spam';
  bool _sending = false;

  static const _typeKeys = ['spam', 'fraud', 'inappropriate', 'duplicate', 'other'];

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

  @override
  void dispose() {
    _reasonController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final reason = _reasonController.text.trim();
    if (reason.isEmpty) {
      showToast(message: AppLocale.tr('enter_report_reason'));
      return;
    }
    setState(() => _sending = true);
    final res = await ReportService.reportUser(
      reportedUserId: widget.reportedUserId,
      type: _type,
      reason: reason,
      conversationId: widget.conversationId,
    );
    if (!mounted) return;
    setState(() => _sending = false);
    showToast(message: res['message'] as String? ?? '');
    if (res['success'] == true) widget.onSuccess();
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20.r)),
      child: Padding(
        padding: EdgeInsets.all(20.w),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(
              AppLocale.tr('report_user'),
              style: TextStyle(fontSize: 18.sp, fontWeight: FontWeight.bold),
            ),
            if (widget.reportedUserName.isNotEmpty)
              Padding(
                padding: EdgeInsets.only(top: 8.h),
                child: Text(
                  widget.reportedUserName,
                  style: TextStyle(fontSize: 14.sp, color: Colors.grey[600]),
                ),
              ),
            SizedBox(height: 16.h),
            DropdownButtonFormField<String>(
              value: _type,
              decoration: InputDecoration(
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(8.r)),
              ),
              items: _typeKeys
                  .map((k) => DropdownMenuItem(value: k, child: Text(_typeLabel(k))))
                  .toList(),
              onChanged: (v) => setState(() => _type = v ?? _type),
            ),
            SizedBox(height: 12.h),
            TextFormField(
              controller: _reasonController,
              maxLines: 4,
              decoration: InputDecoration(
                labelText: AppLocale.tr('report_reason'),
                hintText: AppLocale.tr('report_reason_hint'),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(8.r)),
              ),
            ),
            SizedBox(height: 20.h),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: _sending ? null : _submit,
                    style: OutlinedButton.styleFrom(
                      foregroundColor: Colors.red,
                      side: const BorderSide(color: Colors.red),
                      padding: EdgeInsets.symmetric(vertical: 12.h),
                    ),
                    child: Text(_sending ? AppLocale.tr('sending') : AppLocale.tr('send')),
                  ),
                ),
                SizedBox(width: 12.w),
                Expanded(
                  child: OutlinedButton(
                    onPressed: () => Navigator.pop(context),
                    style: OutlinedButton.styleFrom(
                      padding: EdgeInsets.symmetric(vertical: 12.h),
                    ),
                    child: Text(AppLocale.tr('cancel')),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _ChatBubble extends StatelessWidget {
  final String message;
  final bool isMe;
  final String? createdAt;
  final bool isRead;

  const _ChatBubble({
    required this.message,
    required this.isMe,
    this.createdAt,
    this.isRead = false,
  });

  @override
  Widget build(BuildContext context) {
    return Align(
      alignment: isMe ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: EdgeInsets.only(
          top: 6.h,
          bottom: 6.h,
          right: isMe ? 20.w : 80.w,
          left: isMe ? 80.w : 20.w,
        ),
        padding: EdgeInsets.symmetric(vertical: 12.h, horizontal: 15.w),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.only(
            topRight: Radius.circular(isMe ? 0 : 25.r),
            topLeft: Radius.circular(isMe ? 25.r : 0),
            bottomLeft: const Radius.circular(25),
            bottomRight: const Radius.circular(25),
          ),
          color: isMe ? AppColors.darkBlue : Colors.grey[200],
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Flexible(
              child: Text(
                message,
                style: TextStyle(
                  fontSize: 14.sp,
                  color: isMe ? Colors.white : Colors.black87,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ),
            if (isMe) ...[
              SizedBox(width: 6.w),
              Icon(
                Icons.done_all,
                size: 16.sp,
                color: isRead ? const Color(0xFF90CAF9) : Colors.white54,
              ),
            ],
          ],
        ),
      ),
    );
  }
}
