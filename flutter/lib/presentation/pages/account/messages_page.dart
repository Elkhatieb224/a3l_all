import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/storage/token_storage.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/services/auth_service.dart';
import 'package:a3lnha/data/services/message_service.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/account/chat_page.dart';
import 'package:a3lnha/presentation/pages/auth/login_page.dart';
import 'package:a3lnha/presentation/widgets/shared/app_network_image.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class MessagesPage extends StatefulWidget {
  const MessagesPage({super.key});

  @override
  State<MessagesPage> createState() => _MessagesPageState();
}

class _MessagesPageState extends State<MessagesPage> {
  List<ConversationModel> _conversations = [];
  bool _loading = true;
  int? _currentUserId;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final me = await AuthService.getMe();
    final list = await MessageService.getConversations();
    if (mounted) {
      setState(() {
        _currentUserId = me.user?.id;
        _conversations = list;
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: AppLocale.tr('messages')),
      body: Column(
        children: [
          Padding(
            padding: EdgeInsets.all(16.h),
            child: SearchTextFormField(),
          ),
          Expanded(
            child: _loading
                ? Center(child: CircularProgressIndicator())
                : _conversations.isEmpty
                    ? Center(
                        child: Text(
                          AppLocale.tr('no_messages'),
                          style: TextStyle(fontSize: 14.sp, color: Colors.grey[600]),
                        ),
                      )
                    : RefreshIndicator(
                        onRefresh: _load,
                        child: ListView.builder(
                          physics: BouncingScrollPhysics(parent: AlwaysScrollableScrollPhysics()),
                          padding: EdgeInsets.symmetric(vertical: 10.h, horizontal: 16.w),
                          itemCount: _conversations.length,
                          itemBuilder: (context, index) {
                            final c = _conversations[index];
                            final other = c.otherUser;
                            final latest = c.latestMessage;
                            final ad = c.ad;
                            final latestFromMe = _currentUserId != null && latest != null && (latest['sender_id'] as int?) == _currentUserId;
                            final latestRead = latest?['is_read'] == true;
                            return GestureDetector(
                              onTap: () {
                                if (!TokenStorage.hasToken()) {
                                  context.push(LoginPage());
                                  return;
                                }
                                context.push(ChatPage(
                                  conversationId: c.id,
                                  sellerName: other?['name'] as String?,
                                  adUid: ad?['uid'] as String?,
                                )).then((_) => _load());
                              },
                              child: UserBubbleWidget(
                                name: other?['name'] as String? ?? '—',
                                lastMessage: latest?['message'] as String? ?? '',
                                avatarUrl: other?['avatar'] as String?,
                                unreadCount: c.unreadCount,
                                isLatestFromMe: latestFromMe,
                                isLatestRead: latestRead,
                              ),
                            );
                          },
                        ),
                      ),
          ),
        ],
      ),
    );
  }
}

class SearchTextFormField extends StatelessWidget {
  const SearchTextFormField({super.key});

  @override
  Widget build(BuildContext context) {
    return TextFormField(
      style: TextStyle(fontSize: 14.sp),
      decoration: InputDecoration(
        fillColor: Colors.grey[200],
        focusColor: Colors.grey[200],
        border: OutlineInputBorder(borderSide: BorderSide.none),
        filled: true,
        hoverColor: Colors.grey,
        contentPadding: EdgeInsets.all(0),
        prefixIcon: Icon(Icons.search, color: Colors.grey),
        hint: Text(AppLocale.tr('search'), style: TextStyle(color: Colors.grey)),
      ),
    );
  }
}

class UserBubbleWidget extends StatelessWidget {
  final String name;
  final String lastMessage;
  final String? avatarUrl;
  final int unreadCount;
  final bool isLatestFromMe;
  final bool isLatestRead;

  const UserBubbleWidget({
    super.key,
    required this.name,
    required this.lastMessage,
    this.avatarUrl,
    this.unreadCount = 0,
    this.isLatestFromMe = false,
    this.isLatestRead = false,
  });

  @override
  Widget build(BuildContext context) {
    final checkColor = isLatestFromMe && isLatestRead ? AppColors.darkBlue : Colors.grey;
    return Padding(
      padding: EdgeInsets.symmetric(vertical: 15.h),
      child: Row(
        children: [
          UserAvatar(avatarUrl: avatarUrl),
          SizedBox(width: 15.w),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  name,
                  style: TextStyle(fontSize: 14.sp, fontWeight: FontWeight.w600),
                ),
                SizedBox(height: 4.h),
                Row(
                  children: [
                    Icon(Icons.done_all, size: 16.sp, color: checkColor),
                    SizedBox(width: 4.w),
                    Expanded(
                      child: Text(
                        lastMessage,
                        style: TextStyle(color: Colors.grey, fontSize: 12.sp),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          if (unreadCount > 0)
            Container(
              padding: EdgeInsets.symmetric(horizontal: 8.w, vertical: 4.h),
              decoration: BoxDecoration(
                color: Colors.red,
                borderRadius: BorderRadius.circular(12.r),
              ),
              child: Text(
                '$unreadCount',
                style: TextStyle(color: Colors.white, fontSize: 11.sp),
              ),
            ),
        ],
      ),
    );
  }
}

class UserAvatar extends StatelessWidget {
  final String? avatarUrl;

  const UserAvatar({super.key, this.avatarUrl});

  @override
  Widget build(BuildContext context) {
    return AvatarWithFallback(
      imageUrl: avatarUrl,
      radius: 28.r,
      fallbackLetter: '?',
      fallbackIcon: Icon(Icons.person, color: Colors.grey[600]),
    );
  }
}
