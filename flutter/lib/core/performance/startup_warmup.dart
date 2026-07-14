import 'dart:async';

import 'package:a3lnha/core/storage/token_storage.dart';
import 'package:a3lnha/data/services/ad_service.dart';
import 'package:a3lnha/data/services/auth_service.dart';
import 'package:a3lnha/data/services/blocked_user_service.dart';
import 'package:a3lnha/data/services/category_service.dart';
import 'package:a3lnha/data/services/favorite_seller_service.dart';
import 'package:a3lnha/data/services/favorite_service.dart';
import 'package:a3lnha/data/services/help_service.dart';
import 'package:a3lnha/data/services/home_service.dart';
import 'package:a3lnha/data/services/legal_service.dart';
import 'package:a3lnha/data/services/message_service.dart';
import 'package:a3lnha/data/services/negotiation_service.dart';
import 'package:a3lnha/data/services/notification_service.dart';
import 'package:a3lnha/data/services/package_service.dart';

class StartupWarmup {
  StartupWarmup._();

  static Future<HomeData> loadHomeDuringSplash() async {
    final home = await HomeService.getHome();
    Future<void>.microtask(() async {
      try {
        // لا نكتب كاش قوائم الإعلانات من السبلاش: قد يسبق واجهة الرئيسية ويتعارض مع بيانات /home.
        // تخزين الفئات فقط؛ قوائم المميز/الأحدث تُفعَّل من home_page بعد عرض البيانات الكاملة.
        await CategoryService.seedCategoriesCache(home.categories);
      } catch (_) {}
    });
    return home;
  }

  static bool _secondaryContentPrewarmStarted = false;

  static void scheduleSecondaryPrewarm() {
    if (_secondaryContentPrewarmStarted) return;
    _secondaryContentPrewarmStarted = true;
    Future<void>.microtask(() async {
      try {
        await Future.wait<void>([
          HelpService.getFaqs().then((_) {}),
          LegalService.getPrivacyContent().then((_) {}),
          LegalService.getTermsContent().then((_) {}),
        ]);
      } catch (_) {}
    });
  }

  static void runAfterHomeVisible() {
    scheduleSecondaryPrewarm();
    Future<void>.microtask(() async {
      if (TokenStorage.hasToken()) {
        try {
          await NotificationService.getUnreadCount();
        } catch (_) {}
      }
    });
  }

  /// تشغيل بعد تسجيل الدخول: جلب جميع بيانات صفحات الحساب في الكاش بالتزامن مع العمليات الأخرى.
  static void runAfterLogin() {
    Future.microtask(() async {
      await Future.wait<void>([
        AuthService.getMe().then((_) {}),
        AdService.getMyAds(status: 'active', perPage: 1).then((_) {}),
        AdService.getMyAds(status: 'pending', perPage: 1).then((_) {}),
        NotificationService.getUnreadCount().then((_) {}),
        MessageService.getConversations().then((_) {}),
        NegotiationService.getAllSent().then((_) {}),
        NegotiationService.getAllReceived().then((_) {}),
        PackageService.getPackages().then((_) {}),
        BlockedUserService.getBlockedUsers().then((_) {}),
        FavoriteService.getFavorites().then((_) {}),
        FavoriteSellerService.getFavoriteSellers().then((_) {}),
      ]);
    });
  }
}
