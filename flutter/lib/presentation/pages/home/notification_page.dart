import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/storage/token_storage.dart';
import 'package:a3lnha/data/services/notification_service.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/home/ad_details_page.dart';
import 'package:a3lnha/presentation/pages/account/chat_page.dart';
import 'package:a3lnha/presentation/pages/account/my_products_deals_page.dart';
import 'package:a3lnha/presentation/pages/account/saved_search_results_page.dart';
import 'package:a3lnha/presentation/pages/auth/login_page.dart';
import 'package:a3lnha/presentation/pages/payement/my_wallet_page.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:hexcolor/hexcolor.dart';

class NotificationPage extends StatefulWidget {
  const NotificationPage({super.key});

  @override
  State<NotificationPage> createState() => _NotificationPageState();
}

class _NotificationPageState extends State<NotificationPage> {
  List<NotificationModel> _notifications = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    if (!TokenStorage.hasToken()) {
      setState(() {
        _loading = false;
        _notifications = [];
      });
      return;
    }
    setState(() => _loading = true);
    final list = await NotificationService.getNotifications();
    if (mounted) {
      setState(() {
        _notifications = list;
        _loading = false;
      });
    }
  }

  Future<void> _markAllRead() async {
    if (!TokenStorage.hasToken()) return;
    final ok = await NotificationService.markAllAsRead();
    if (mounted) {
      if (ok) {
        showToast(message: AppLocale.tr('mark_all_read'));
        _load();
      }
    }
  }

  /// توجيه المستخدم للصفحة المناسبة حسب نوع الإشعار وبياناته
  Future<void> _onNotificationTap(NotificationModel n) async {
    if (!n.isRead) {
      await NotificationService.markAsRead(n.id);
      if (mounted) _load();
    }
    if (!mounted) return;
    final data = n.data;
    if (data == null) return;

    // رسالة جديدة -> فتح المحادثة
    final conversationId = data['conversation_id'];
    if (conversationId != null) {
      final id = conversationId is int
          ? conversationId
          : int.tryParse(conversationId.toString());
      if (id != null) {
        if (!TokenStorage.hasToken()) {
          context.push(LoginPage());
          return;
        }
        context.push(ChatPage(conversationId: id));
        return;
      }
    }

    // طلب تفاوض -> صفحة العروض المرسلة والمستلمة
    if (data['negotiation_id'] != null) {
      final type = data['type']?.toString() ?? '';
      final initialTabIndex = type == 'new_negotiation_request' ? 1 : 0;
      context.push(MyProductsDealsPage(initialTabIndex: initialTabIndex));
      return;
    }

    final savedSearchIdRaw = data['saved_search_id'];
    if (savedSearchIdRaw != null) {
      final savedSearchId = savedSearchIdRaw is int
          ? savedSearchIdRaw
          : int.tryParse(savedSearchIdRaw.toString());
      if (savedSearchId != null) {
        context.push(SavedSearchResultsPage(savedSearchId: savedSearchId));
        return;
      }
    }

    // حوالة (قبول / رفض) -> المحفظة
    final type = data['type']?.toString() ?? '';
    if (type == 'hawala_approved' || type == 'hawala_rejected' || data['hawala_transfer_id'] != null) {
      context.push(const MyWalletPage());
      return;
    }

    // إعلان (قبول / تحديث / إلخ) -> صفحة تفاصيل الإعلان
    final adUid = data['ad_uid']?.toString();
    if (adUid != null && adUid.isNotEmpty) {
      context.push(AdDetailsPage(adUid: adUid));
      return;
    }
  }

  @override
  Widget build(BuildContext context) {
    if (!TokenStorage.hasToken()) {
      return Scaffold(
        appBar: CustomAppbar(title: AppLocale.tr('notifications')),
        body: Center(
          child: Text(
            AppLocale.tr('login_to_view_notifications'),
            style: TextStyle(fontSize: 16.sp, color: Colors.grey[600]),
          ),
        ),
      );
    }

    return Scaffold(
      appBar: CustomAppbar(title: AppLocale.tr('notifications')),
      body: _loading
          ? Center(child: CircularProgressIndicator())
          : _notifications.isEmpty
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.notifications_none,
                          size: 64.sp, color: Colors.grey),
                      SizedBox(height: 16.h),
                      Text(
                        AppLocale.tr('no_notifications'),
                        style: TextStyle(
                            fontSize: 16.sp, color: Colors.grey[600]),
                      ),
                    ],
                  ),
                )
              : Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (_notifications.any((n) => !n.isRead))
                      Padding(
                        padding: EdgeInsets.symmetric(
                            horizontal: 16.w, vertical: 8.h),
                        child: TextButton(
                          onPressed: _loading ? null : _markAllRead,
                          child: Text(AppLocale.tr('mark_all_read')),
                        ),
                      ),
                    Expanded(
                      child: RefreshIndicator(
                        onRefresh: _load,
                        child: ListView.builder(
                          padding: EdgeInsets.symmetric(
                              horizontal: 16.w, vertical: 20.h),
                          itemCount: _notifications.length,
                          itemBuilder: (context, index) {
                            final n = _notifications[index];
                            return _NotificationItem(
                              notification: n,
                              onTap: () => _onNotificationTap(n),
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

class _NotificationItem extends StatelessWidget {
  final NotificationModel notification;
  final VoidCallback onTap;

  const _NotificationItem({
    required this.notification,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final n = notification;
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: onTap,
      child: Padding(
        padding: EdgeInsets.only(top: 10.h),
        child: Column(
          children: [
            Container(
              width: double.infinity,
              padding: EdgeInsets.all(20.w),
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(20.r),
                color: n.isRead
                    ? Colors.grey.withOpacity(0.15)
                    : Colors.blue.withOpacity(0.08),
              ),
              child: Row(
                children: [
                  Stack(
                    clipBehavior: Clip.none,
                    children: [
                      Image.asset(
                        "assets/images/uit_create-dashboard.png",
                        width: 24.w,
                        height: 24.h,
                        errorBuilder: (_, __, ___) => Icon(
                          Icons.notifications_outlined,
                          size: 24.sp,
                          color: Colors.grey[700],
                        ),
                      ),
                      if (!n.isRead)
                        Positioned(
                          top: -2,
                          right: -2,
                          child: Container(
                            width: 10.w,
                            height: 10.w,
                            decoration: BoxDecoration(
                              color: Colors.red,
                              shape: BoxShape.circle,
                              border: Border.all(color: Colors.white, width: 1),
                            ),
                          ),
                        ),
                    ],
                  ),
                  SizedBox(width: 10.w),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          n.title.isNotEmpty ? n.title : "إشعار",
                          style: TextStyle(
                            fontSize: 14.sp,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        SizedBox(height: 5.h),
                        Text(
                          n.message.isNotEmpty
                              ? n.message
                              : (n.data?['body'] ?? n.data?['message'] ?? '')
                                  .toString(),
                          style: TextStyle(
                            fontSize: 12.sp,
                            color: HexColor("404040"),
                            fontWeight: FontWeight.w400,
                          ),
                        ),
                        SizedBox(height: 4.h),
                        Text(
                          _formatDate(n.createdAt),
                          style: TextStyle(
                            fontSize: 10.sp,
                            color: Colors.grey,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _formatDate(String dateStr) {
    if (dateStr.isEmpty) return '';
    try {
      final dt = DateTime.tryParse(dateStr);
      if (dt == null) return dateStr;
      final now = DateTime.now();
      final diff = now.difference(dt);
      if (diff.inMinutes < 60) return AppLocale.tr('ago_minutes').replaceAll('%s', '${diff.inMinutes}');
      if (diff.inHours < 24) return AppLocale.tr('ago_hours').replaceAll('%s', '${diff.inHours}');
      if (diff.inDays < 7) return AppLocale.tr('ago_days').replaceAll('%s', '${diff.inDays}');
      return '${dt.year}-${dt.month.toString().padLeft(2, '0')}-${dt.day.toString().padLeft(2, '0')}';
    } catch (_) {
      return dateStr;
    }
  }
}
