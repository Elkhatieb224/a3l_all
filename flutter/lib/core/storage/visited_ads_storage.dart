import 'dart:convert';

import 'package:a3lnha/helpers/cache_helper.dart';

/// تخزين الإعلانات التي تم فتحها (لتمييزها على الخريطة) لمدة محددة.
class VisitedAdsStorage {
  VisitedAdsStorage._();

  static const String _key = 'visited_ads_v1';
  static const Duration retention = Duration(days: 5);

  static Map<String, int> _readRaw() {
    final raw = CacheHelper.getData(key: _key);
    if (raw is! String || raw.isEmpty) return {};
    try {
      final decoded = jsonDecode(raw);
      if (decoded is! Map) return {};
      final out = <String, int>{};
      decoded.forEach((k, v) {
        final uid = k?.toString().trim();
        if (uid == null || uid.isEmpty) return;
        int? ts;
        if (v is int) {
          ts = v;
        } else if (v != null) {
          ts = int.tryParse(v.toString());
        }
        if (ts != null && ts > 0) out[uid] = ts;
      });
      return out;
    } catch (_) {
      return {};
    }
  }

  static bool _isExpiredTs(int ts) {
    final now = DateTime.now().millisecondsSinceEpoch;
    final cutoff = now - retention.inMilliseconds;
    return ts < cutoff;
  }

  /// يرجع الخريطة بعد تنظيف العناصر المنتهية.
  static Future<Map<String, int>> loadAndPrune() async {
    final map = _readRaw();
    final pruned = <String, int>{};
    for (final e in map.entries) {
      if (!_isExpiredTs(e.value)) pruned[e.key] = e.value;
    }
    if (pruned.length != map.length) {
      await CacheHelper.saveData(key: _key, value: jsonEncode(pruned));
    }
    return pruned;
  }

  static Future<void> markVisited(String adUid) async {
    final uid = adUid.trim();
    if (uid.isEmpty) return;
    final map = _readRaw();
    map[uid] = DateTime.now().millisecondsSinceEpoch;
    // تنظيف بسيط عند كل كتابة.
    map.removeWhere((_, ts) => _isExpiredTs(ts));
    await CacheHelper.saveData(key: _key, value: jsonEncode(map));
  }
}

