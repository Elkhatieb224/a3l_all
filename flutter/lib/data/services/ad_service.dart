import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:image_picker/image_picker.dart';

import 'package:a3lnha/core/performance/persistent_ttl_cache.dart';
import 'package:a3lnha/core/performance/ttl_memory_cache.dart';
import 'package:a3lnha/core/network/api_client.dart';
import 'package:a3lnha/core/network/api_constants.dart';
import 'package:a3lnha/core/storage/token_storage.dart';
import 'package:a3lnha/data/models/ad_model.dart';
import 'package:a3lnha/helpers/image_orientation_helper.dart';

class AdsResponse {
  final List<AdModel> ads;
  final int currentPage;
  final int lastPage;
  final int total;

  AdsResponse({
    required this.ads,
    required this.currentPage,
    required this.lastPage,
    required this.total,
  });
}

class SearchCategoryItem {
  /// للتوافق مع الاستجابات القديمة: يطابق [categoryId] عادةً.
  final int id;
  final String name;
  final String? nameAr;
  final String? nameEn;
  final String? nameTr;
  final String? icon;
  final int matchingAdsCount;
  /// `category` | `subcategory` — الاستجابات القديمة بدون kind تُعامل كقسم رئيسي.
  final String kind;
  final int categoryId;
  final int? subcategoryId;
  /// مسار الأب (القسم + الفروع الأعلى) بدون اسم العقدة المطابقة، مثل الموقع التركي.
  final String breadcrumb;

  SearchCategoryItem({
    required this.id,
    required this.name,
    this.nameAr,
    this.nameEn,
    this.nameTr,
    this.icon,
    this.matchingAdsCount = 0,
    this.kind = 'category',
    required this.categoryId,
    this.subcategoryId,
    this.breadcrumb = '',
  });

  factory SearchCategoryItem.fromJson(Map<String, dynamic> json) {
    final id = (json['id'] is int) ? json['id'] as int : int.tryParse(json['id']?.toString() ?? '0') ?? 0;
    final cidRaw = json['category_id'];
    final categoryId = (cidRaw is int)
        ? cidRaw
        : int.tryParse(cidRaw?.toString() ?? '') ?? id;
    final sidRaw = json['subcategory_id'];
    final subIdParsed = sidRaw == null
        ? null
        : ((sidRaw is int) ? sidRaw : int.tryParse(sidRaw.toString()));
    final k = (json['kind'] ?? '').toString().trim().toLowerCase();
    final String kind;
    if (k == 'subcategory' || k == 'category') {
      kind = k;
    } else {
      kind = (subIdParsed != null && subIdParsed > 0) ? 'subcategory' : 'category';
    }
    final subcategoryId =
        kind == 'subcategory' && subIdParsed != null && subIdParsed > 0 ? subIdParsed : null;
    return SearchCategoryItem(
      id: id,
      name: (json['name'] ?? json['name_ar'] ?? '').toString(),
      nameAr: json['name_ar']?.toString(),
      nameEn: json['name_en']?.toString(),
      nameTr: json['name_tr']?.toString(),
      icon: json['icon']?.toString(),
      matchingAdsCount: (json['matching_ads_count'] is int) ? json['matching_ads_count'] as int : int.tryParse(json['matching_ads_count']?.toString() ?? '0') ?? 0,
      kind: kind,
      categoryId: categoryId,
      subcategoryId: subcategoryId,
      breadcrumb: json['breadcrumb']?.toString() ?? '',
    );
  }
}

class SearchCategoriesResponse {
  final List<SearchCategoryItem> data;
  final int total;
  final int minLength;

  SearchCategoriesResponse({
    required this.data,
    required this.total,
    this.minLength = 3,
  });
}

class AdService {
  AdService._();
  static const Duration _adsListTtl = Duration(seconds: 45);

  static Future<MultipartFile?> _normalizedImageMultipart(
    XFile file,
    int index,
  ) async {
    final rawBytes = await file.readAsBytes();
    if (rawBytes.isEmpty) return null;
    final normalized = normalizeImageForDisplayAndUpload(rawBytes);
    final bytesToUpload = normalized ?? rawBytes;
    return MultipartFile.fromBytes(
      bytesToUpload,
      filename: 'image_$index.jpg',
    );
  }

  /// على الويب لا يتوفر `dart:io`؛ [MultipartFile.fromFile] يرمي استثناءً. الصور تستخدم `fromBytes` بالفعل.
  static Future<void> _appendVideoToFormData(
    FormData formData,
    XFile videoFile,
  ) async {
    final bytes = await videoFile.readAsBytes();
    if (bytes.isEmpty) return;
    var name = videoFile.name.trim();
    if (name.isEmpty) name = 'video.mp4';
    if (!name.contains('.')) name = '$name.mp4';
    formData.files.add(
      MapEntry('video', MultipartFile.fromBytes(bytes, filename: name)),
    );
  }

  static Map<String, dynamic> _adToCacheJson(AdModel ad) {
    final thumb = ad.imageUrl;
    return <String, dynamic>{
      'id': ad.id,
      'uid': ad.uid,
      'title': ad.title,
      'description': ad.description,
      'price': ad.price,
      'currency': ad.currency,
      'formatted_price': ad.formattedPrice,
      if (ad.images.isNotEmpty) 'images': ad.images,
      if (thumb != null) 'thumbnail': thumb,
      'is_featured': ad.isFeatured,
      'is_urgent': ad.isUrgent,
      'is_favorite': ad.isFavorite,
      'views_count': ad.viewsCount,
      'messages_count': ad.messagesCount,
      'favorites_count': ad.favoritesCount,
      'published_at': ad.publishedAt,
      'latitude': ad.latitude,
      'longitude': ad.longitude,
      'location_state': ad.locationState,
      'location_city': ad.locationCity,
      'location_district': ad.locationDistrict,
      'location_address': ad.locationAddress,
      'location_country': ad.locationCountry,
      'show_location': ad.showLocation,
      if (ad.customFields != null && ad.customFields!.isNotEmpty)
        'custom_fields': ad.customFields,
      'category': ad.category,
      'subcategory': ad.subcategory,
      'user': ad.user,
    };
  }

  static Future<void> _seedListCache({
    required int perPage,
    bool? featured,
    bool? urgent,
    required List<AdModel> ads,
  }) async {
    if (ads.isEmpty) return;
    final cacheKey = _persistentListCacheKey(
      page: 1,
      perPage: perPage,
      featured: featured,
      urgent: urgent,
    );
    final data = <String, dynamic>{
      'success': true,
      'data': ads.map(_adToCacheJson).toList(),
      'meta': <String, dynamic>{
        'current_page': 1,
        'last_page': 1,
        'total': ads.length,
      },
    };
    await PersistentTtlCache.setJson(cacheKey, data, ttl: _adsListTtl);
    final memoryKey = _listCacheKey(
      page: 1,
      perPage: perPage,
      featured: featured,
      urgent: urgent,
    );
    TtlMemoryCache.set<AdsResponse>(
      memoryKey,
      AdsResponse(ads: ads, currentPage: 1, lastPage: 1, total: ads.length),
      _adsListTtl,
    );
  }

  /// حفظ بيانات الهوم (مميز/عاجل/أحدث) في الكاش بعد عرضها للمستخدم — بدون طلبات شبكة.
  static Future<void> seedHomeSnapshots({
    required List<AdModel> featuredAds,
    required List<AdModel> urgentAds,
    required List<AdModel> latestAds,
  }) async {
    await Future.wait<void>([
      _seedListCache(perPage: 10, featured: true, ads: featuredAds),
      _seedListCache(perPage: 10, urgent: true, ads: urgentAds),
      _seedListCache(perPage: 6, ads: latestAds),
    ]);
  }

  /// مفتاح الكاش لقائمة الإعلانات — يجب أن يشمل الفئة والفئة الفرعية حتى لا تختلط البيانات
  static String _listCacheKey({
    int? categoryId,
    int? subcategoryId,
    int page = 1,
    int perPage = 20,
    bool? featured,
    bool? urgent,
    String? search,
    num? minPrice,
    num? maxPrice,
    String? sortBy,
  }) {
    final queryParams = <String, dynamic>{'page': page, 'per_page': perPage};
    if (categoryId != null) queryParams['category_id'] = categoryId;
    if (subcategoryId != null) queryParams['subcategory_id'] = subcategoryId;
    if (featured == true) queryParams['featured'] = '1';
    if (urgent == true) queryParams['urgent'] = '1';
    if (search != null && search.isNotEmpty) queryParams['search'] = search;
    if (minPrice != null) queryParams['min_price'] = minPrice;
    if (maxPrice != null) queryParams['max_price'] = maxPrice;
    if (sortBy != null && sortBy.isNotEmpty) queryParams['sort_by'] = sortBy;
    return 'ads.list.v2.${queryParams.toString()}';
  }

  static String _persistentListCacheKey({
    int? categoryId,
    int? subcategoryId,
    int page = 1,
    int perPage = 20,
    bool? featured,
    bool? urgent,
    String? search,
    num? minPrice,
    num? maxPrice,
    String? sortBy,
  }) {
    final queryParams = <String, dynamic>{'page': page, 'per_page': perPage};
    if (categoryId != null) queryParams['category_id'] = categoryId;
    if (subcategoryId != null) queryParams['subcategory_id'] = subcategoryId;
    if (featured == true) queryParams['featured'] = '1';
    if (urgent == true) queryParams['urgent'] = '1';
    if (search != null && search.isNotEmpty) queryParams['search'] = search;
    if (minPrice != null) queryParams['min_price'] = minPrice;
    if (maxPrice != null) queryParams['max_price'] = maxPrice;
    if (sortBy != null && sortBy.isNotEmpty) queryParams['sort_by'] = sortBy;
    return 'api.ads.list.v2.${queryParams.toString()}';
  }

  static AdsResponse? _getCachedAdsSnapshot({
    int page = 1,
    int perPage = 20,
    bool? featured,
    bool? urgent,
  }) {
    final memory = TtlMemoryCache.getIfValid<AdsResponse>(
      _listCacheKey(
        page: page,
        perPage: perPage,
        featured: featured,
        urgent: urgent,
      ),
    );
    if (memory != null) return memory;

    final fresh = _extractAdsResponse(
      PersistentTtlCache.getJsonMap(
        _persistentListCacheKey(
          page: page,
          perPage: perPage,
          featured: featured,
          urgent: urgent,
        ),
      ),
    );
    if (fresh != null) return fresh;

    return _extractAdsResponse(
      PersistentTtlCache.getJsonMapAllowExpired(
        _persistentListCacheKey(
          page: page,
          perPage: perPage,
          featured: featured,
          urgent: urgent,
        ),
      ),
    );
  }

  /// قراءة الإعلانات المميزة من الكاش فقط (فوري)
  static AdsResponse? getCachedFeaturedAds() {
    return _getCachedAdsSnapshot(
      perPage: 10,
      featured: true,
    );
  }

  /// قراءة أحدث الإعلانات من الكاش فقط (فوري) - 6 إعلانات فقط
  static AdsResponse? getCachedLatestAds() {
    return _getCachedAdsSnapshot(
      perPage: 6,
    );
  }

  /// قراءة الإعلانات العاجلة من الكاش فقط (فوري)
  static AdsResponse? getCachedUrgentAds() {
    return _getCachedAdsSnapshot(
      perPage: 10,
      urgent: true,
    );
  }

  static AdModel? _adFromListItem(dynamic item) {
    if (item is! Map) return null;
    try {
      return AdModel.fromJson(Map<String, dynamic>.from(item));
    } catch (_) {
      return null;
    }
  }

  static int _parseMetaInt(Map<String, dynamic> meta, String key, int fallback) {
    final v = meta[key];
    if (v == null) return fallback;
    if (v is int) return v;
    if (v is num) return v.toInt();
    if (v is String) return int.tryParse(v) ?? fallback;
    return fallback;
  }

  /// الحصول على الإعلانات مع فلترة
  /// [forceRefresh]: يتجاهل كاش الذاكرة والقرص ويجبر طلب الشبكة (مفيد عند تعارض مع أعداد الفئات الفرعية).
  static Future<AdsResponse> getAds({
    int? categoryId,
    int? subcategoryId,
    int page = 1,
    int perPage = 20,
    bool? featured,
    bool? urgent,
    String? search,
    num? minPrice,
    num? maxPrice,
    String? sortBy,
    Map<String, dynamic>? customFilters,
    bool forceRefresh = false,
  }) async {
    final queryParams = <String, dynamic>{'page': page, 'per_page': perPage};
    if (categoryId != null) queryParams['category_id'] = categoryId;
    if (subcategoryId != null) queryParams['subcategory_id'] = subcategoryId;
    if (featured == true) queryParams['featured'] = '1';
    if (urgent == true) queryParams['urgent'] = '1';
    if (search != null && search.isNotEmpty) queryParams['search'] = search;
    if (minPrice != null) queryParams['min_price'] = minPrice;
    if (maxPrice != null) queryParams['max_price'] = maxPrice;
    if (sortBy != null && sortBy.isNotEmpty) queryParams['sort_by'] = sortBy;
    if (customFilters != null && customFilters.isNotEmpty) {
      queryParams.addAll(customFilters);
    }
    final cacheKey = _persistentListCacheKey(
      categoryId: categoryId,
      subcategoryId: subcategoryId,
      page: page,
      perPage: perPage,
      featured: featured,
      urgent: urgent,
      search: search,
      minPrice: minPrice,
      maxPrice: maxPrice,
      sortBy: sortBy,
    );

    final shouldCache =
        page == 1 &&
        (search == null || search.isEmpty) &&
        minPrice == null &&
        maxPrice == null &&
        (sortBy == null || sortBy.isEmpty) &&
        (customFilters == null || customFilters.isEmpty);

    final staleCached = shouldCache && !forceRefresh
        ? _extractAdsResponse(
            PersistentTtlCache.getJsonMapAllowExpired(cacheKey),
          )
        : null;

    if (shouldCache && !forceRefresh) {
      final cachedMap = PersistentTtlCache.getJsonMap(cacheKey);
      final cachedResponse = _extractAdsResponse(cachedMap);
      if (cachedResponse != null && cachedResponse.ads.isNotEmpty) {
        final memoryKey = _listCacheKey(
          categoryId: categoryId,
          subcategoryId: subcategoryId,
          page: page,
          perPage: perPage,
          featured: featured,
          urgent: urgent,
          search: search,
          minPrice: minPrice,
          maxPrice: maxPrice,
          sortBy: sortBy,
        );
        TtlMemoryCache.set<AdsResponse>(memoryKey, cachedResponse, _adsListTtl);
        return cachedResponse;
      }
    }

    Future<AdsResponse> loadFromApi() async {
      try {
        final response = await ApiClient.dio.get(
          ApiConstants.ads,
          queryParameters: queryParams,
        );

        final data = response.data as Map<String, dynamic>;
        if (data['success'] != true) {
          if (staleCached != null) return staleCached;
          return AdsResponse(ads: [], currentPage: 1, lastPage: 1, total: 0);
        }

        // دعم الهيكل العادي أو المهيكل مع Paginator
        dynamic rawList = data['data'];
        if (rawList is Map && rawList['data'] != null) {
          rawList = rawList['data'];
        }
        final adsList = <AdModel>[];
        if (rawList != null && rawList is List) {
          for (final item in rawList) {
            final ad = _adFromListItem(item);
            if (ad != null) adsList.add(ad);
          }
        }

        if (shouldCache && adsList.isNotEmpty) {
          await PersistentTtlCache.setJson(cacheKey, data, ttl: _adsListTtl);
        }

        Map<String, dynamic> meta = {};
        final metaVal = data['meta'];
        if (metaVal is Map) {
          meta = Map<String, dynamic>.from(metaVal);
        }
        return AdsResponse(
          ads: adsList,
          currentPage: _parseMetaInt(meta, 'current_page', 1),
          lastPage: _parseMetaInt(meta, 'last_page', 1),
          total: _parseMetaInt(meta, 'total', 0),
        );
      } catch (e) {
        if (staleCached != null) return staleCached;
        return AdsResponse(ads: [], currentPage: 1, lastPage: 1, total: 0);
      }
    }

    if (!shouldCache || forceRefresh) {
      if (shouldCache && forceRefresh) {
        TtlMemoryCache.remove(
          _listCacheKey(
            categoryId: categoryId,
            subcategoryId: subcategoryId,
            page: page,
            perPage: perPage,
            featured: featured,
            urgent: urgent,
            search: search,
            minPrice: minPrice,
            maxPrice: maxPrice,
            sortBy: sortBy,
          ),
        );
      }
      return loadFromApi();
    }
    final key = _listCacheKey(
      categoryId: categoryId,
      subcategoryId: subcategoryId,
      page: page,
      perPage: perPage,
      featured: featured,
      urgent: urgent,
      search: search,
      minPrice: minPrice,
      maxPrice: maxPrice,
      sortBy: sortBy,
    );
    return TtlMemoryCache.getOrLoad<AdsResponse>(
      key: key,
      ttl: _adsListTtl,
      loader: loadFromApi,
      shouldCache: (r) => r.ads.isNotEmpty,
    );
  }

  /// الحد الأدنى لعدد أحرف البحث (مطابق للباكند)
  static const int minSearchLength = 3;

  /// الفئات الرئيسية التي تحتوي على إعلانات مطابقة لـ [query] (بحث في العناوين وأسماء الفئات بجميع اللغات).
  /// لا يُنفّذ البحث بأقل من 3 أحرف.
  /// [forceRefresh] عند true يُضاف معامل وقت لمنع كاش الوكيل (افتراضياً false لتقليل الحمل).
  static Future<SearchCategoriesResponse> getSearchCategories(String query, {bool forceRefresh = false}) async {
    const tag = '[SearchCategories]';
    final q = query.trim();
    if (q.length < minSearchLength) {
      if (kDebugMode) {
        debugPrint('$tag query too short (${q.length} < $minSearchLength), returning empty');
      }
      return SearchCategoriesResponse(data: [], total: 0, minLength: minSearchLength);
    }
    final params = <String, dynamic>{'q': q, 'search': q};
    if (forceRefresh) params['_t'] = DateTime.now().millisecondsSinceEpoch;
    final path = '${ApiConstants.ads}/search-categories';
    final stopwatch = Stopwatch()..start();
    if (kDebugMode) {
      debugPrint('$tag REQUEST query="$q" base=${ApiConstants.baseUrl} path=$path');
    }
    try {
      final opts = Options(
        extra: {'skipAuth': true},
        headers: forceRefresh
            ? {'Cache-Control': 'no-cache', 'Pragma': 'no-cache'}
            : null,
      );
      final response = await ApiClient.dio.get(
        '${ApiConstants.ads}/search-categories',
        queryParameters: params,
        options: opts,
      );
      stopwatch.stop();
      final data = response.data as Map<String, dynamic>?;
      final dataLen = data?['data'] is List ? (data!['data'] as List).length : 0;
      if (kDebugMode) {
        debugPrint(
          '$tag RESPONSE status=${response.statusCode} ms=${stopwatch.elapsedMilliseconds} success=${data?['success']} dataLength=$dataLen total=${data?['total']}',
        );
      }
      if (data == null || data['success'] != true) {
        if (kDebugMode) debugPrint('$tag parsing: success false or null data, returning empty');
        return SearchCategoriesResponse(data: [], total: 0, minLength: minSearchLength);
      }
      final list = data['data'] as List? ?? [];
      final items = list
          .map((e) => SearchCategoryItem.fromJson(e is Map<String, dynamic> ? e : <String, dynamic>{}))
          .where((e) => e.categoryId > 0)
          .toList();
      final total = (data['total'] is int) ? data['total'] as int : 0;
      if (kDebugMode) debugPrint('$tag parsed categories=${items.length} total=$total');
      return SearchCategoriesResponse(
        data: items,
        total: total,
        minLength: (data['min_length'] is int) ? data['min_length'] as int : minSearchLength,
      );
    } on DioException catch (e) {
      stopwatch.stop();
      if (kDebugMode) {
        debugPrint(
          '$tag DioException after ${stopwatch.elapsedMilliseconds}ms: type=${e.type} status=${e.response?.statusCode} message=${e.message}',
        );
      }
      // عند 401 أو فشل: نجرب مسار الويب (نفس منطق الموقع) الذي قد لا يتطلب مصادقة
      if (e.response?.statusCode == 401 || e.type == DioExceptionType.badResponse) {
        try {
          final webUrl = '${ApiConstants.webOrigin}/ads/search-categories-json';
          if (kDebugMode) debugPrint('$tag fallback: GET $webUrl');
          final webResponse = await ApiClient.dio.get(
            webUrl,
            queryParameters: {'q': q, 'search': q},
            options: Options(extra: {'skipAuth': true}),
          );
          final data = webResponse.data as Map<String, dynamic>?;
          if (data != null && data['success'] == true) {
            final list = data['data'] as List? ?? [];
            final items = list
                .map((e) => SearchCategoryItem.fromJson(e is Map<String, dynamic> ? e : <String, dynamic>{}))
                .where((e) => e.categoryId > 0)
                .toList();
            final total = (data['total'] is int) ? data['total'] as int : 0;
            if (kDebugMode) {
              debugPrint('$tag fallback success: categories=${items.length} total=$total');
            }
            return SearchCategoriesResponse(
              data: items,
              total: total,
              minLength: (data['min_length'] is int) ? data['min_length'] as int : minSearchLength,
            );
          }
        } catch (_) {
          if (kDebugMode) debugPrint('$tag fallback request failed');
        }
      }
      rethrow;
    }
  }

  static AdsResponse? _extractAdsResponse(Map<String, dynamic>? data) {
    if (data == null || data['success'] != true) return null;

    dynamic rawList = data['data'];
    if (rawList is Map && rawList['data'] != null) {
      rawList = rawList['data'];
    }
    final adsList = <AdModel>[];
    if (rawList is List) {
      for (final item in rawList.whereType<Map>()) {
        try {
          adsList.add(AdModel.fromJson(Map<String, dynamic>.from(item)));
        } catch (_) {}
      }
    }

    Map<String, dynamic> meta = {};
    final metaVal = data['meta'];
    if (metaVal is Map) {
      meta = Map<String, dynamic>.from(metaVal);
    }
    return AdsResponse(
      ads: adsList,
      currentPage: _parseMetaInt(meta, 'current_page', 1),
      lastPage: _parseMetaInt(meta, 'last_page', 1),
      total: _parseMetaInt(meta, 'total', 0),
    );
  }

  /// تفاصيل إعلان المستخدم (لأي حالة: منشور، قيد المراجعة، إلخ)
  static Future<AdDetailsResponse?> getMyAdDetails(String uid) async {
    try {
      final response = await ApiClient.dio.get('${ApiConstants.ads}/my/$uid');
      final data = response.data as Map<String, dynamic>;
      if (data['success'] != true || data['data'] == null) return null;
      final adMap = Map<String, dynamic>.from(data['data'] as Map);
      final ad = AdModel.fromJson(adMap);
      List<AdModel> related = [];
      if (data['related_ads'] != null) {
        related = (data['related_ads'] as List)
            .map((e) => AdModel.fromJson(Map<String, dynamic>.from(e as Map)))
            .toList();
      }
      final promoteActions = PromoteActions.fromJson(
        data['promote_actions'] is Map<String, dynamic> ? data['promote_actions'] as Map<String, dynamic> : null,
      );
      return AdDetailsResponse(ad: ad, relatedAds: related, promoteActions: promoteActions);
    } on DioException {
      return null;
    }
  }

  /// تمييز الإعلان أو إلغاء التميز
  static Future<Map<String, dynamic>> setFeatured(String uid) async {
    try {
      final response = await ApiClient.dio.post('${ApiConstants.ads}/$uid/set-featured');
      final data = response.data as Map<String, dynamic>;
      return {
        'success': data['success'] == true,
        'message': data['message'] as String? ?? '',
        'is_featured': data['is_featured'] as bool?,
      };
    } on DioException catch (e) {
      final msg = e.response?.data is Map ? (e.response!.data as Map)['message'] as String? : null;
      return {'success': false, 'message': msg ?? '', 'is_featured': null};
    }
  }

  /// جعل الإعلان عاجلاً أو إلغاء العاجل
  static Future<Map<String, dynamic>> setUrgent(String uid) async {
    try {
      final response = await ApiClient.dio.post('${ApiConstants.ads}/$uid/set-urgent');
      final data = response.data as Map<String, dynamic>;
      return {
        'success': data['success'] == true,
        'message': data['message'] as String? ?? '',
        'is_urgent': data['is_urgent'] as bool?,
      };
    } on DioException catch (e) {
      final msg = e.response?.data is Map ? (e.response!.data as Map)['message'] as String? : null;
      return {'success': false, 'message': msg ?? '', 'is_urgent': null};
    }
  }

  /// الحصول على تفاصيل إعلان (عام - للإعلانات النشطة فقط)
  static Future<AdDetailsResponse?> getAdDetails(String uid, {bool forceRefresh = false}) async {
    return TtlMemoryCache.getOrLoad<AdDetailsResponse?>(
      key: 'ad.details.$uid',
      ttl: const Duration(seconds: 12),
      forceRefresh: forceRefresh,
      shouldCache: (r) => r != null,
      loader: () => _fetchAdDetailsFromNetwork(uid),
    );
  }

  static Future<AdDetailsResponse?> _fetchAdDetailsFromNetwork(String uid) async {
    final detailOptions = Options(
      connectTimeout: const Duration(seconds: 90),
      receiveTimeout: const Duration(seconds: 120),
      headers: const {
        'Cache-Control': 'no-cache',
        'Pragma': 'no-cache',
      },
    );

    for (var attempt = 0; attempt < 2; attempt++) {
      try {
        final response = await ApiClient.dio.get(
          '${ApiConstants.ads}/$uid',
          queryParameters: {'_': DateTime.now().millisecondsSinceEpoch},
          options: detailOptions,
        );
        final data = response.data as Map<String, dynamic>;
        if (data['success'] != true || data['data'] == null) return null;

        final adMap = Map<String, dynamic>.from(data['data'] as Map);
        final ad = AdModel.fromJson(adMap);
        List<AdModel> related = [];
        if (data['related_ads'] != null) {
          related = (data['related_ads'] as List)
              .map((e) => AdModel.fromJson(Map<String, dynamic>.from(e as Map)))
              .toList();
        }
        final promoteActions = PromoteActions.fromJson(
          data['promote_actions'] is Map<String, dynamic> ? data['promote_actions'] as Map<String, dynamic> : null,
        );
        return AdDetailsResponse(ad: ad, relatedAds: related, promoteActions: promoteActions);
      } on DioException catch (e) {
        final retry = e.type == DioExceptionType.connectionTimeout ||
            e.type == DioExceptionType.receiveTimeout;
        if (!retry || attempt == 1) return null;
      }
    }
    return null;
  }

  static const Duration _myAdsCacheTtl = Duration(minutes: 2);

  /// إعلانات المستخدم (تتطلب تسجيل دخول) مع كاش قصير لتسريع صفحة الحساب
  /// [status]: active | pending | rejected | expired
  static Future<AdsResponse> getMyAds({
    String? status,
    int page = 1,
    int perPage = 20,
    bool forceRefresh = false,
  }) async {
    final cacheKey = 'myads.${status ?? 'all'}.$page.$perPage';
    if (!forceRefresh) {
      final cached = TtlMemoryCache.getIfValid<AdsResponse>(cacheKey);
      if (cached != null) return cached;
    }
    try {
      final queryParams = <String, dynamic>{'page': page, 'per_page': perPage};
      if (status != null && status.isNotEmpty) {
        queryParams['status'] = status;
      }
      final response = await ApiClient.dio.get(
        '${ApiConstants.ads}/my/list',
        queryParameters: queryParams,
      );
      final data = response.data as Map<String, dynamic>;
      if (data['success'] != true) {
        return AdsResponse(ads: [], currentPage: 1, lastPage: 1, total: 0);
      }
      dynamic rawList = data['data'];
      if (rawList is Map && rawList['data'] != null) {
        rawList = rawList['data'];
      }
      final adsList = <AdModel>[];
      if (rawList != null && rawList is List) {
        for (final item in rawList) {
          final ad = _adFromListItem(item);
          if (ad != null) adsList.add(ad);
        }
      }
      Map<String, dynamic> meta = {};
      final metaVal = data['meta'];
      if (metaVal is Map) {
        meta = Map<String, dynamic>.from(metaVal);
      }
      final result = AdsResponse(
        ads: adsList,
        currentPage: meta['current_page'] as int? ?? 1,
        lastPage: meta['last_page'] as int? ?? 1,
        total: meta['total'] as int? ?? 0,
      );
      if (result.ads.isNotEmpty) {
        TtlMemoryCache.set<AdsResponse>(cacheKey, result, _myAdsCacheTtl);
      }
      return result;
    } on DioException {
      return AdsResponse(ads: [], currentPage: 1, lastPage: 1, total: 0);
    }
  }

  /// إنشاء إعلان جديد
  static Future<CreateAdResult> createAd({
    required int categoryId,
    required int subcategoryId,
    required String title,
    required String description,
    num? price,
    String? currency,
    String? locationCountry,
    String locationInputMethod = 'manual',
    bool showLocationInAd = true,
    String? locationStateCode,
    String? locationCityCode,
    String? locationDistrictCode,
    String? locationState,
    String? locationCity,
    String? locationDistrict,
    String? locationAddress,
    double? latitude,
    double? longitude,
    Map<String, dynamic>? customFields,
    List<dynamic>? imageFiles,
    String? galleryImagePath,
    XFile? videoFile,
  }) async {
    try {
      final formData = FormData.fromMap({
        'category_id': categoryId,
        'subcategory_id': subcategoryId,
        'title': title,
        'description': description,
        if (galleryImagePath != null && galleryImagePath.isNotEmpty)
          'gallery_image': galleryImagePath,
        if (price != null) 'price': price.toString(),
        if (currency != null && currency.isNotEmpty) 'currency': currency,
        if (locationCountry != null && locationCountry.isNotEmpty)
          'location_country': locationCountry,
        'location_input_method': locationInputMethod,
        'show_location': showLocationInAd ? '1' : '0',
        if (locationStateCode != null && locationStateCode.isNotEmpty)
          'location_state_code': locationStateCode,
        if (locationCityCode != null && locationCityCode.isNotEmpty)
          'location_city_code': locationCityCode,
        if (locationDistrictCode != null && locationDistrictCode.isNotEmpty)
          'location_district_code': locationDistrictCode,
        if (locationState != null && locationState.isNotEmpty)
          'location_state': locationState,
        if (locationCity != null && locationCity.isNotEmpty)
          'location_city': locationCity,
        if (locationDistrict != null && locationDistrict.isNotEmpty)
          'location_district': locationDistrict,
        if (locationAddress != null && locationAddress.isNotEmpty)
          'location_address': locationAddress,
        if (latitude != null) 'latitude': latitude.toString(),
        if (longitude != null) 'longitude': longitude.toString(),
      });

      if (customFields != null && customFields.isNotEmpty) {
        for (final e in customFields.entries) {
          final val = e.value;
          if (val == null) continue;
          if (val is Map) {
            if (val.containsKey('value') || val.containsKey('currency')) {
              formData.fields.addAll([
                MapEntry(
                  'custom_fields[${e.key}][value]',
                  (val['value'] ?? '').toString(),
                ),
                MapEntry(
                  'custom_fields[${e.key}][currency]',
                  (val['currency'] ?? 'SYP').toString(),
                ),
              ]);
            } else {
              formData.fields.addAll([
                MapEntry(
                  'custom_fields[${e.key}][latitude]',
                  (val['latitude'] ?? val['lat'] ?? '').toString(),
                ),
                MapEntry(
                  'custom_fields[${e.key}][longitude]',
                  (val['longitude'] ?? val['lng'] ?? '').toString(),
                ),
                MapEntry(
                  'custom_fields[${e.key}][address]',
                  (val['address'] ?? '').toString(),
                ),
              ]);
            }
          } else {
            formData.fields.add(
              MapEntry('custom_fields[${e.key}]', val.toString()),
            );
          }
        }
      }

      if (galleryImagePath == null || galleryImagePath.isEmpty) {
        if (imageFiles != null && imageFiles.isNotEmpty) {
          for (var i = 0; i < imageFiles.length; i++) {
            final file = imageFiles[i];
            if (file is MultipartFile) {
              formData.files.add(MapEntry('images[]', file));
            } else if (file is XFile) {
              final multipart = await _normalizedImageMultipart(file, i);
              if (multipart != null) {
                formData.files.add(MapEntry('images[]', multipart));
              }
            } else if (file is String && file.isNotEmpty) {
              formData.files.add(
                MapEntry('images[]', await MultipartFile.fromFile(file)),
              );
            }
          }
        }
      }

      if (videoFile != null) {
        await _appendVideoToFormData(formData, videoFile);
      }

      final token = TokenStorage.getToken();
      final headers = <String, dynamic>{};
      if (token != null && token.isNotEmpty) {
        final bearer = 'Bearer $token';
        headers['Authorization'] = bearer;
        headers['X-Authorization'] = bearer;
      }

      final uploadTimeout = videoFile != null
          ? const Duration(seconds: 180)
          : const Duration(seconds: 60);
      final response = await ApiClient.dio.post(
        ApiConstants.ads,
        data: formData,
        options: Options(
          contentType: 'multipart/form-data',
          sendTimeout: uploadTimeout,
          receiveTimeout: uploadTimeout,
          connectTimeout: videoFile != null ? uploadTimeout : null,
          headers: headers,
        ),
      );

      final data = response.data as Map<String, dynamic>;
      if (data['success'] == true && data['data'] != null) {
        final adData = data['data'] as Map<String, dynamic>;
        return CreateAdResult(
          success: true,
          adUid: adData['uid'] as String?,
          message: data['message'] as String? ?? 'تم نشر الإعلان بنجاح',
        );
      }
      return CreateAdResult(
        success: false,
        message: data['message'] as String? ?? 'فشل نشر الإعلان',
      );
    } on DioException catch (e) {
      final status = e.response?.statusCode;
      final raw = e.response?.data;
      String? msg;
      String? redirectTo;
      Map? errors;
      if (raw is Map) {
        msg = raw['message'] as String?;
        final r = raw['redirect_to'];
        if (r != null) redirectTo = r.toString();
        errors = raw['errors'] as Map?;
      }
      String errMsg = msg ?? 'حدث خطأ في الاتصال';
      if (errors != null && errors.isNotEmpty) {
        final first = errors.values.first;
        if (first is List && first.isNotEmpty) {
          errMsg = first.first.toString();
        }
      }
      return CreateAdResult(
        success: false,
        message: errMsg,
        redirectTo: redirectTo,
        statusCode: status,
      );
    } catch (e) {
      return CreateAdResult(
        success: false,
        message: e.toString(),
      );
    }
  }

  /// تحديث إعلان (يُعاد وضعه في انتظار المراجعة بعد التعديل)
  static Future<CreateAdResult> updateAd({
    required String adUid,
    required String title,
    required String description,
    num? price,
    String? currency,
    Map<String, dynamic>? customFields,
    List<dynamic>? imageFiles,
    XFile? videoFile,
  }) async {
    try {
      final formData = FormData.fromMap({
        '_method': 'PUT',
        'title': title,
        'description': description,
        if (price != null) 'price': price.toString(),
        if (currency != null && currency.isNotEmpty) 'currency': currency,
      });

      if (customFields != null && customFields.isNotEmpty) {
        for (final e in customFields.entries) {
          final val = e.value;
          if (val == null) continue;
          if (val is Map) {
            if (val.containsKey('value') || val.containsKey('currency')) {
              formData.fields.addAll([
                MapEntry(
                  'custom_fields[${e.key}][value]',
                  (val['value'] ?? '').toString(),
                ),
                MapEntry(
                  'custom_fields[${e.key}][currency]',
                  (val['currency'] ?? 'SYP').toString(),
                ),
              ]);
            } else {
              formData.fields.addAll([
                MapEntry(
                  'custom_fields[${e.key}][latitude]',
                  (val['latitude'] ?? val['lat'] ?? '').toString(),
                ),
                MapEntry(
                  'custom_fields[${e.key}][longitude]',
                  (val['longitude'] ?? val['lng'] ?? '').toString(),
                ),
                MapEntry(
                  'custom_fields[${e.key}][address]',
                  (val['address'] ?? '').toString(),
                ),
              ]);
            }
          } else {
            formData.fields.add(
              MapEntry('custom_fields[${e.key}]', val.toString()),
            );
          }
        }
      }

      if (imageFiles != null && imageFiles.isNotEmpty) {
        for (var i = 0; i < imageFiles.length; i++) {
          final file = imageFiles[i];
          if (file is MultipartFile) {
            formData.files.add(MapEntry('images[]', file));
          } else if (file is XFile) {
            final multipart = await _normalizedImageMultipart(file, i);
            if (multipart != null) {
              formData.files.add(MapEntry('images[]', multipart));
            }
          } else if (file is String && file.isNotEmpty) {
            formData.files.add(
              MapEntry('images[]', await MultipartFile.fromFile(file)),
            );
          }
        }
      }

      if (videoFile != null) {
        await _appendVideoToFormData(formData, videoFile);
      }

      // POST so server receives form body; _method=PUT for Laravel route
      final uploadTimeout = videoFile != null
          ? const Duration(seconds: 180)
          : const Duration(seconds: 60);
      final response = await ApiClient.dio.post(
        '${ApiConstants.ads}/$adUid',
        data: formData,
        options: Options(
          contentType: 'multipart/form-data',
          sendTimeout: uploadTimeout,
          receiveTimeout: uploadTimeout,
          connectTimeout: videoFile != null ? uploadTimeout : null,
        ),
      );

      final data = response.data as Map<String, dynamic>;
      if (data['success'] == true) {
        return CreateAdResult(
          success: true,
          adUid: adUid,
          message: data['message'] as String? ?? 'تم حفظ التعديلات بنجاح',
        );
      }
      return CreateAdResult(
        success: false,
        message: data['message'] as String? ?? 'فشل حفظ التعديلات',
      );
    } on DioException catch (e) {
      final msg = e.response?.data is Map
          ? (e.response!.data as Map)['message'] as String?
          : null;
      return CreateAdResult(
        success: false,
        message: msg ?? 'حدث خطأ في الاتصال',
      );
    } catch (e) {
      return CreateAdResult(
        success: false,
        message: e.toString(),
      );
    }
  }

  /// تعليق الإعلان (إخفاؤه عن الزوار دون حذف)
  static Future<Map<String, dynamic>> suspendAd(String uid) async {
    try {
      final response = await ApiClient.dio.post('${ApiConstants.ads}/$uid/suspend');
      final data = response.data as Map<String, dynamic>;
      return {
        'success': data['success'] == true,
        'message': data['message'] as String?,
        'redirect_to': data['redirect_to'] as String?,
      };
    } on DioException catch (e) {
      final d = e.response?.data;
      return {
        'success': false,
        'message': d is Map ? (d['message'] as String?) : null,
        'redirect_to': d is Map ? (d['redirect_to'] as String?) : null,
      };
    }
  }

  /// إلغاء تعليق الإعلان (إظهاره مرة أخرى - دون موافقة إدارة)
  static Future<Map<String, dynamic>> unsuspendAd(String uid) async {
    try {
      final response = await ApiClient.dio.post('${ApiConstants.ads}/$uid/unsuspend');
      final data = response.data as Map<String, dynamic>;
      return {
        'success': data['success'] == true,
        'message': data['message'] as String?,
        'redirect_to': data['redirect_to'] as String?,
      };
    } on DioException catch (e) {
      final d = e.response?.data;
      return {
        'success': false,
        'message': d is Map ? (d['message'] as String?) : null,
        'redirect_to': d is Map ? (d['redirect_to'] as String?) : null,
      };
    }
  }

  /// حذف الإعلان نهائياً
  static Future<Map<String, dynamic>> deleteAd(String uid) async {
    try {
      await ApiClient.dio.delete('${ApiConstants.ads}/$uid');
      return {'success': true, 'message': null, 'redirect_to': null};
    } on DioException catch (e) {
      final d = e.response?.data;
      return {
        'success': false,
        'message': d is Map ? (d['message'] as String?) : null,
        'redirect_to': null,
      };
    }
  }
}

class CreateAdResult {
  final bool success;
  final String? adUid;
  final String message;
  /// مثلاً `packages` عند تجاوز حد الإعلانات المجانية (HTTP 403).
  final String? redirectTo;
  final int? statusCode;

  CreateAdResult({
    required this.success,
    this.adUid,
    required this.message,
    this.redirectTo,
    this.statusCode,
  });

  bool get shouldOfferPackages => redirectTo == 'packages';
}

/// إمكانيات الترويج (تمييز / عاجل) للإعلان النشط — كما في الموقع
class PromoteActions {
  final bool canAddFeatured;
  final bool canRemoveFeatured;
  final bool canAddUrgent;
  final bool canRemoveUrgent;
  final int remainingFeatured;
  final int remainingUrgent;

  PromoteActions({
    required this.canAddFeatured,
    required this.canRemoveFeatured,
    required this.canAddUrgent,
    required this.canRemoveUrgent,
    this.remainingFeatured = 0,
    this.remainingUrgent = 0,
  });

  static PromoteActions? fromJson(Map<String, dynamic>? json) {
    if (json == null) return null;
    return PromoteActions(
      canAddFeatured: json['can_add_featured'] == true,
      canRemoveFeatured: json['can_remove_featured'] == true,
      canAddUrgent: json['can_add_urgent'] == true,
      canRemoveUrgent: json['can_remove_urgent'] == true,
      remainingFeatured: json['remaining_featured'] is int ? json['remaining_featured'] as int : int.tryParse(json['remaining_featured']?.toString() ?? '0') ?? 0,
      remainingUrgent: json['remaining_urgent'] is int ? json['remaining_urgent'] as int : int.tryParse(json['remaining_urgent']?.toString() ?? '0') ?? 0,
    );
  }
}

class AdDetailsResponse {
  final AdModel ad;
  final List<AdModel> relatedAds;
  final PromoteActions? promoteActions;

  AdDetailsResponse({required this.ad, required this.relatedAds, this.promoteActions});
}
