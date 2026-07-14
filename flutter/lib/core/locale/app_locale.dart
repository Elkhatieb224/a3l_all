import 'package:flutter/material.dart';

import 'package:a3lnha/core/locale/locale_storage.dart';
import 'package:a3lnha/core/locale/app_translations.dart';
import 'package:a3lnha/core/network/api_client.dart';

/// إدارة لغة التطبيق وتحديث الـ API
class AppLocale {
  AppLocale._();

  static final ValueNotifier<String> localeNotifier = ValueNotifier(LocaleStorage.getLocale());

  static String get current => localeNotifier.value;

  static bool get isRtl => LocaleStorage.isRtl(current);

  static TextDirection get textDirection => isRtl ? TextDirection.rtl : TextDirection.ltr;

  static Locale get locale => Locale(current);

  /// تغيير اللغة وحفظها وتحديث الـ API
  static Future<void> setLocale(String newLocale) async {
    if (!LocaleStorage.supportedLocales.contains(newLocale)) return;
    await LocaleStorage.saveLocale(newLocale);
    ApiClient.setLocale(newLocale);
    localeNotifier.value = newLocale;
  }

  static String tr(String key) => AppTranslations.tr(current, key);
}
