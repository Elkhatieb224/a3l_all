import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';

import 'package:a3lnha/core/locale/locale_storage.dart';
import 'package:a3lnha/core/network/api_client.dart';
import 'package:a3lnha/core/performance/ttl_memory_cache.dart';
import 'package:a3lnha/data/models/ad_model.dart';
import 'package:a3lnha/data/models/category_model.dart';

class HomeData {
  final List<CategoryModel> categories;
  final List<AdModel> featuredAds;
  final List<AdModel> urgentAds;
  final List<AdModel> latestAds;

  HomeData({
    required this.categories,
    required this.featuredAds,
    required this.urgentAds,
    required this.latestAds,
  });
}

class HomeService {
  HomeService._();

  static const Duration _homeTtl = Duration(seconds: 45);

  static String _cacheKey() => 'home.payload.v1.${LocaleStorage.getLocale()}';

  static String? _serverEtag;
  static String? _etagLocale;
  static HomeData? _lastNetworkHome;

  static void _resetEtagIfLocaleChanged() {
    final loc = LocaleStorage.getLocale();
    if (_etagLocale != loc) {
      _etagLocale = loc;
      _serverEtag = null;
      _lastNetworkHome = null;
    }
  }

  /// استدعاء API مخصص للهوم `/api/v1/home` مع دمج الطلبات المتزامنة، كاش ذاكرة، و [If-None-Match] عند توفر ETag.
  static Future<HomeData> getHome({bool forceRefresh = false}) async {
    _resetEtagIfLocaleChanged();
    if (forceRefresh) {
      _serverEtag = null;
      _lastNetworkHome = null;
    }
    return TtlMemoryCache.getOrLoad<HomeData>(
      key: _cacheKey(),
      ttl: _homeTtl,
      forceRefresh: forceRefresh,
      shouldCache: (h) =>
          h.categories.isNotEmpty ||
          h.featuredAds.isNotEmpty ||
          h.urgentAds.isNotEmpty ||
          h.latestAds.isNotEmpty,
      loader: _fetchHomeFromNetwork,
    );
  }

  static HomeData _parseHomeBody(Map<String, dynamic> data) {
    final root = data['data'] as Map<String, dynamic>;
    final catsRaw = root['categories'] as List? ?? const [];
    final featuredRaw = root['featured_ads'] as List? ?? const [];
    final urgentRaw = root['urgent_ads'] as List? ?? const [];
    final latestRaw = root['latest_ads'] as List? ?? const [];

    final categories = catsRaw
        .whereType<Map>()
        .map((e) => CategoryModel.fromJson(Map<String, dynamic>.from(e)))
        .toList();

    List<AdModel> parseAds(List list) {
      final out = <AdModel>[];
      for (final item in list.whereType<Map>()) {
        try {
          out.add(AdModel.fromJson(Map<String, dynamic>.from(item)));
        } catch (_) {}
      }
      return out;
    }

    return HomeData(
      categories: categories,
      featuredAds: parseAds(featuredRaw),
      urgentAds: parseAds(urgentRaw),
      latestAds: parseAds(latestRaw),
    );
  }

  static Future<HomeData> _fetchHomeFromNetwork() async {
    _resetEtagIfLocaleChanged();
    try {
      final response = await ApiClient.dio.get<Map<String, dynamic>>(
        '/home',
        options: Options(
          validateStatus: (s) => s != null && (s == 200 || s == 304),
          headers: <String, dynamic>{
            if (_serverEtag != null && _serverEtag!.trim().isNotEmpty)
              'If-None-Match': _serverEtag!.trim(),
          },
        ),
      );

      if (response.statusCode == 304) {
        if (_lastNetworkHome != null) return _lastNetworkHome!;
        if (kDebugMode) {
          debugPrint('[HomeService] 304 but no cached body; returning empty.');
        }
        return HomeData(categories: [], featuredAds: [], urgentAds: [], latestAds: []);
      }

      final etag = response.headers.value('etag')?.trim();
      if (etag != null && etag.isNotEmpty) {
        _serverEtag = etag;
      }

      final data = response.data;
      if (data == null || data['success'] != true || data['data'] == null) {
        return HomeData(categories: [], featuredAds: [], urgentAds: [], latestAds: []);
      }

      final parsed = _parseHomeBody(Map<String, dynamic>.from(data));
      _lastNetworkHome = parsed;
      return parsed;
    } on DioException {
      return HomeData(categories: [], featuredAds: [], urgentAds: [], latestAds: []);
    }
  }
}
