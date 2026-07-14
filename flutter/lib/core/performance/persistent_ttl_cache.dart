import 'dart:convert';

import 'package:a3lnha/helpers/cache_helper.dart';

/// Persistent JSON cache backed by SharedPreferences with TTL support.
class PersistentTtlCache {
  PersistentTtlCache._();

  static String _dataKey(String key) => 'ttl_cache_data_$key';
  static String _expiryKey(String key) => 'ttl_cache_exp_$key';

  static Future<void> setJson(
    String key,
    Object value, {
    required Duration ttl,
  }) async {
    final expiry = DateTime.now().add(ttl).millisecondsSinceEpoch;
    await CacheHelper.saveData(key: _dataKey(key), value: jsonEncode(value));
    await CacheHelper.saveData(key: _expiryKey(key), value: expiry);
  }

  static dynamic _decodeRaw(String? raw) {
    if (raw == null || raw.isEmpty) return null;
    try {
      return jsonDecode(raw);
    } catch (_) {
      return null;
    }
  }

  static bool _isExpired(String key) {
    final exp = CacheHelper.getData(key: _expiryKey(key));
    if (exp is! int) return true;
    final now = DateTime.now().millisecondsSinceEpoch;
    return now >= exp;
  }

  static dynamic getJson(String key) {
    if (_isExpired(key)) return null;
    final raw = CacheHelper.getData(key: _dataKey(key));
    return raw is String ? _decodeRaw(raw) : null;
  }

  /// قراءة الكاش حتى لو كان منتهي الصلاحية (stale fallback عند فشل الشبكة)
  static dynamic getJsonAllowExpired(String key) {
    final raw = CacheHelper.getData(key: _dataKey(key));
    return raw is String ? _decodeRaw(raw) : null;
  }

  static Map<String, dynamic>? getJsonMap(String key) {
    final json = getJson(key);
    if (json is Map<String, dynamic>) return json;
    if (json is Map) return Map<String, dynamic>.from(json);
    return null;
  }

  static Map<String, dynamic>? getJsonMapAllowExpired(String key) {
    final json = getJsonAllowExpired(key);
    if (json is Map<String, dynamic>) return json;
    if (json is Map) return Map<String, dynamic>.from(json);
    return null;
  }
}
