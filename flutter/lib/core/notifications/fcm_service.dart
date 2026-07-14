import 'dart:convert';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter/material.dart';
import 'package:flutter/scheduler.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

import 'package:a3lnha/core/network/api_client.dart';
import 'package:a3lnha/core/network/api_constants.dart';
import 'package:a3lnha/core/notifications/platform_check_stub.dart'
    if (dart.library.io) 'package:a3lnha/core/notifications/platform_check_io.dart' as platform_check;
import 'package:a3lnha/core/storage/token_storage.dart';
import 'package:a3lnha/core/navigation/app_navigator_key.dart';
import 'package:a3lnha/firebase_options.dart';
import 'package:a3lnha/presentation/pages/account/chat_page.dart';
import 'package:a3lnha/presentation/pages/account/my_products_deals_page.dart';
import 'package:a3lnha/presentation/pages/account/saved_search_results_page.dart';
import 'package:a3lnha/presentation/pages/auth/login_page.dart';
import 'package:a3lnha/presentation/pages/home/ad_details_page.dart';
import 'package:a3lnha/presentation/pages/home/notification_page.dart';
import 'package:a3lnha/presentation/pages/payement/my_wallet_page.dart';
import 'package:a3lnha/presentation/pages/payement/package_request_detail_page.dart';
import 'package:a3lnha/presentation/pages/account/verification_page.dart';

/// معالجة الرسائل في الخلفية (يجب أن تكون دالة علوية)
@pragma('vm:entry-point')
Future<void> _firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp(options: DefaultFirebaseOptions.currentPlatform);
  // يمكن عرض إشعار محلي هنا إذا لزم
}

class FcmService {
  FcmService._();

  static final FlutterLocalNotificationsPlugin _localNotifications =
      FlutterLocalNotificationsPlugin();
  static bool _initialized = false;

  /// تهيئة FCM وطلب الصلاحيات والحصول على التوكن
  static Future<void> init() async {
    if (_initialized) return;
    // على الويب لا نستخدم FCM بنفس الطريقة (يتطلب dart:io)
    if (kIsWeb) {
      _initialized = true;
      return;
    }
    try {
      await Firebase.initializeApp(
        options: DefaultFirebaseOptions.currentPlatform,
      );

      // معالجة الرسائل في الخلفية
      FirebaseMessaging.onBackgroundMessage(_firebaseMessagingBackgroundHandler);

      // طلب إذن الإشعارات (Android 13+)
      if (platform_check.isAndroid) {
        await FirebaseMessaging.instance
            .requestPermission(alert: true, badge: true, sound: true);
        _initLocalNotifications();
      }

      // معالجة الرسائل الواردة
      FirebaseMessaging.onMessage.listen(_handleForegroundMessage);
      FirebaseMessaging.onMessageOpenedApp.listen(_handleMessageOpenedApp);

      // عند فتح التطبيق من إشعار (التطبيق كان مغلقاً)
      final initialMessage = await FirebaseMessaging.instance.getInitialMessage();
      if (initialMessage != null) {
        _handleMessageOpenedApp(initialMessage);
      }

      // الحصول على التوكن وإرساله للباكند
      await _refreshTokenAndSendToBackend();

      // عند تجدد التوكن
      FirebaseMessaging.instance.onTokenRefresh.listen((token) {
        _sendTokenToBackend(token);
      });

      _initialized = true;
    } catch (e) {
      // في حال فشل التهيئة (مثلاً firebase_options غير مضبوط)
      // التطبيق يستمر دون إشعارات فورية
    }
  }

  static void _initLocalNotifications() {
    const androidSettings = AndroidInitializationSettings('@mipmap/launcher_icon');
    const initSettings =
        InitializationSettings(android: androidSettings);
    _localNotifications.initialize(
      initSettings,
      onDidReceiveNotificationResponse: (response) {
        if (response.payload != null && response.payload!.isNotEmpty) {
          try {
            final decoded = jsonDecode(response.payload!);
            final Map<String, String> data = {};
            if (decoded is Map) {
              for (final e in decoded.entries) {
                data[e.key.toString()] = e.value?.toString() ?? '';
              }
            }
            _navigateFromNotificationData(data);
          } catch (_) {}
        }
      },
    );

    const androidChannel = AndroidNotificationChannel(
      'aalenha_notifications',
      'إشعارات أعلنها',
      importance: Importance.high,
    );
    _localNotifications
        .resolvePlatformSpecificImplementation<
            AndroidFlutterLocalNotificationsPlugin>()
        ?.createNotificationChannel(androidChannel);
  }

  static Future<void> _handleForegroundMessage(RemoteMessage message) async {
    if (platform_check.isAndroid && message.notification != null) {
      final data = message.data;
      final payload = data.isNotEmpty ? jsonEncode(data) : null;
      await _localNotifications.show(
        message.hashCode,
        message.notification?.title ?? '',
        message.notification?.body ?? '',
        const NotificationDetails(
          android: AndroidNotificationDetails(
            'aalenha_notifications',
            'إشعارات أعلنها',
            importance: Importance.high,
          ),
        ),
        payload: payload,
      );
    }
  }

  static void _handleMessageOpenedApp(RemoteMessage message) {
    final data = Map<String, String>.from(message.data);
    _navigateFromNotificationData(data);
  }

  /// توجيه المستخدم للصفحة المناسبة حسب نوع الإشعار وبياناته (FCM data أو إشعار محلي).
  static void _navigateFromNotificationData(Map<String, String> data) {
    void pushPage(Widget page) {
      final context = navigatorKey.currentContext;
      if (context != null) {
        Navigator.of(context).push(
          MaterialPageRoute(builder: (_) => page),
        );
      } else {
        SchedulerBinding.instance.addPostFrameCallback((_) {
          final ctx = navigatorKey.currentContext;
          if (ctx != null) {
            Navigator.of(ctx).push(
              MaterialPageRoute(builder: (_) => page),
            );
          }
        });
      }
    }

    if (!TokenStorage.hasToken()) {
      pushPage(LoginPage());
      return;
    }

    // رسالة / محادثة -> فتح المحادثة
    final conversationIdStr = data['conversation_id']?.toString();
    if (conversationIdStr != null && conversationIdStr.isNotEmpty) {
      final id = int.tryParse(conversationIdStr);
      if (id != null) {
        pushPage(ChatPage(conversationId: id));
        return;
      }
    }

    // تفاوض -> عروض التفاوض
    final negotiationId = data['negotiation_id']?.toString();
    if (negotiationId != null && negotiationId.isNotEmpty) {
      final type = data['type']?.toString() ?? '';
      final initialTabIndex = type == 'new_negotiation_request' ? 1 : 0;
      pushPage(MyProductsDealsPage(initialTabIndex: initialTabIndex));
      return;
    }

    final savedSearchIdStr = data['saved_search_id']?.toString();
    if (savedSearchIdStr != null && savedSearchIdStr.isNotEmpty) {
      final savedSearchId = int.tryParse(savedSearchIdStr);
      if (savedSearchId != null) {
        pushPage(SavedSearchResultsPage(savedSearchId: savedSearchId));
        return;
      }
    }

    final type = data['type']?.toString() ?? '';

    // طلب باقة (رد إداري) -> تفاصيل طلب الباقة
    if (type == 'package_request_responded') {
      final idStr = data['package_request_id']?.toString();
      final id = int.tryParse(idStr ?? '');
      if (id != null) {
        pushPage(PackageRequestDetailPage(requestId: id));
        return;
      }
    }

    // حوالة (قبول/رفض) -> المحفظة
    if (type == 'hawala_approved' || type == 'hawala_rejected') {
      pushPage(const MyWalletPage());
      return;
    }

    // تفعيل باقة -> المحفظة
    if (type == 'package_activated') {
      pushPage(const MyWalletPage());
      return;
    }

    // تحقق هوية (قبول/رفض) -> صفحة التحقق
    if (type == 'verification_approved' || type == 'verification_rejected') {
      pushPage(VerificationPage());
      return;
    }

    // إعلان (قبول / تحديث / إلخ) -> تفاصيل الإعلان
    final adUid = data['ad_uid']?.toString();
    if (adUid != null && adUid.isNotEmpty) {
      pushPage(AdDetailsPage(adUid: adUid));
      return;
    }

    // إشعار إداري -> صفحة الإشعارات
    if (type == 'admin_notification') {
      pushPage(const NotificationPage());
      return;
    }

    // نوع message من FirebaseHelper -> تم التعامل معه عبر conversation_id أعلاه
    // إذا لم يُطابق أي شيء -> صفحة الإشعارات
    pushPage(const NotificationPage());
  }

  static Future<void> _refreshTokenAndSendToBackend() async {
    final token = await FirebaseMessaging.instance.getToken();
    if (token != null) {
      await _sendTokenToBackend(token);
    }
  }

  static Future<void> _sendTokenToBackend(String token) async {
    if (!TokenStorage.hasToken()) return;
    try {
      await ApiClient.dio.post(
        ApiConstants.fcmToken,
        data: {
          'fcm_token': token,
          'device_type': platform_check.deviceType,
        },
      );
    } catch (_) {
      // سيُعاد المحاولة عند فتح صفحة الحساب أو تجدد التوكن
    }
  }

  /// الحصول على FCM token (لإرساله مع login/register)
  static Future<String?> getToken() async {
    if (kIsWeb || !platform_check.isMobilePlatform) return null;
    try {
      return await FirebaseMessaging.instance.getToken();
    } catch (_) {
      return null;
    }
  }

  /// استدعاء يدوي لتحديث التوكن وإرساله (بعد تسجيل الدخول أو فتح الحساب)
  static Future<void> refreshAndSendToken() async {
    if (kIsWeb || !platform_check.isMobilePlatform) return;
    try {
      await _refreshTokenAndSendToBackend();
    } catch (_) {
      // تجاهل عند فشل Firebase (مثلاً على الويب)
    }
  }

  /// التحقق من حالة إذن الإشعارات
  static Future<bool> isNotificationPermissionGranted() async {
    if (kIsWeb || !platform_check.isMobilePlatform) return false;
    try {
      final settings = await FirebaseMessaging.instance.getNotificationSettings();
      return settings.authorizationStatus == AuthorizationStatus.authorized ||
          settings.authorizationStatus == AuthorizationStatus.provisional;
    } catch (_) {
      return false;
    }
  }

  /// طلب إذن الإشعارات
  static Future<bool> requestNotificationPermission() async {
    if (kIsWeb || !platform_check.isMobilePlatform) return false;
    try {
      final settings = await FirebaseMessaging.instance
          .requestPermission(alert: true, badge: true, sound: true);
      final granted = settings.authorizationStatus == AuthorizationStatus.authorized ||
          settings.authorizationStatus == AuthorizationStatus.provisional;
      if (granted) await _refreshTokenAndSendToBackend();
      return granted;
    } catch (_) {
      return false;
    }
  }
}
