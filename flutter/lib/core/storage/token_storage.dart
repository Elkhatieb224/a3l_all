import 'package:a3lnha/helpers/cache_helper.dart';

/// تخزين واسترجاع Token المصادقة
class TokenStorage {
  TokenStorage._();

  static const String _tokenKey = 'auth_token';

  /// حفظ الـ Token
  static Future<bool> saveToken(String token) async {
    return CacheHelper.saveData(key: _tokenKey, value: token);
  }

  /// استرجاع الـ Token
  static String? getToken() {
    final value = CacheHelper.getData(key: _tokenKey);
    return value is String ? value : null;
  }

  /// التحقق من وجود Token
  static bool hasToken() {
    final token = getToken();
    return token != null && token.isNotEmpty;
  }

  /// حذف الـ Token (عند تسجيل الخروج)
  static Future<bool> removeToken() async {
    return CacheHelper.removeData(key: _tokenKey);
  }
}
