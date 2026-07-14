import 'package:flutter_cache_manager/flutter_cache_manager.dart';

/// كاش قرصي لصور الشبكة (إعلانات، أيقونات، أفاتار) لتفادي إعادة التحميل عند التنقّل.
class AppImageCache {
  AppImageCache._();

  static final CacheManager instance = CacheManager(
    Config(
      'aalenha_images_v1',
      stalePeriod: const Duration(days: 30),
      maxNrOfCacheObjects: 600,
    ),
  );
}
