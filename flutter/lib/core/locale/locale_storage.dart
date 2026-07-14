import 'package:a3lnha/helpers/cache_helper.dart';

/// تخزين واسترجاع لغة التطبيق
class LocaleStorage {
  LocaleStorage._();

  static const String _key = 'app_locale';

  static const String ar = 'ar';
  static const String en = 'en';
  static const String tr = 'tr';

  static const List<String> supportedLocales = [ar, en, tr];

  static Future<bool> saveLocale(String locale) async {
    if (!supportedLocales.contains(locale)) return false;
    return CacheHelper.saveData(key: _key, value: locale);
  }

  static String getLocale() {
    final value = CacheHelper.getData(key: _key);
    if (value is String && supportedLocales.contains(value)) {
      return value;
    }
    return ar;
  }

  static bool isRtl(String locale) => locale == ar;
}
