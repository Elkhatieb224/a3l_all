class _CacheEntry<T> {
  final T value;
  final DateTime expiresAt;

  _CacheEntry({required this.value, required this.expiresAt});

  bool get isValid => DateTime.now().isBefore(expiresAt);
}

/// Lightweight in-memory cache with TTL and in-flight request de-duplication.
class TtlMemoryCache {
  TtlMemoryCache._();

  static final Map<String, _CacheEntry<dynamic>> _cache = {};
  static final Map<String, Future<dynamic>> _inFlight = {};

  static T? getIfValid<T>(String key) {
    final entry = _cache[key];
    if (entry == null) return null;
    if (!entry.isValid) {
      _cache.remove(key);
      return null;
    }
    return entry.value as T;
  }

  static void set<T>(String key, T value, Duration ttl) {
    _cache[key] = _CacheEntry<T>(
      value: value,
      expiresAt: DateTime.now().add(ttl),
    );
  }

  /// إزالة عنصر من الكاش (مثلاً بعد حظر/إلغاء حظر لتحديث البيانات)
  static void remove(String key) {
    _cache.remove(key);
  }

  /// [shouldCache]: إن كان مُقدَّماً ونتيجته false لا يُخزَّن الرد (لا يتم تخزين أي استجابة فارغة).
  static Future<T> getOrLoad<T>({
    required String key,
    required Duration ttl,
    required Future<T> Function() loader,
    bool forceRefresh = false,
    bool Function(T value)? shouldCache,
  }) async {
    if (!forceRefresh) {
      final cached = getIfValid<T>(key);
      if (cached != null) return cached;

      final existing = _inFlight[key];
      if (existing != null) return await existing as T;
    }

    final future = loader();
    _inFlight[key] = future;

    try {
      final value = await future;
      if (shouldCache == null || shouldCache(value)) {
        set<T>(key, value, ttl);
      }
      return value;
    } finally {
      _inFlight.remove(key);
    }
  }
}
