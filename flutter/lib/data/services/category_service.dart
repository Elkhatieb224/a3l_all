import 'package:dio/dio.dart';

import 'package:a3lnha/core/performance/persistent_ttl_cache.dart';
import 'package:a3lnha/core/performance/ttl_memory_cache.dart';
import 'package:a3lnha/core/network/api_client.dart';
import 'package:a3lnha/core/network/api_constants.dart';
import 'package:a3lnha/data/models/category_model.dart';
import 'package:a3lnha/data/models/subcategory_model.dart';

class CategoryService {
  CategoryService._();
  static const Duration _categoriesTtl = Duration(minutes: 20);
  static const _categoriesCacheKey = 'api.categories.all';
  static const _memoryKeyCategories = 'categories.all';
  static String _subcategoriesCacheKey(int categoryId) =>
      'api.categories.$categoryId.subcategories.v2';
  static String _subcategoryChildrenCacheKey(int subcategoryId) =>
      'api.subcategories.$subcategoryId.children.v2';

  /// حفظ قائمة الفئات التي عُرضت للمستخدم في الكاش (بدون أي طلبات شبكة).
  /// مفيد عند جلب البيانات من `/home` ثم نريد إتاحة نفس البيانات لباقي الشاشات عبر `CategoryService`.
  static Future<void> seedCategoriesCache(List<CategoryModel> list) async {
    if (list.isEmpty) return;
    final data = {
      'success': true,
      'data': list
          .map(
            (e) => <String, dynamic>{
              'id': e.id,
              'name': e.name,
              'name_ar': e.nameAr,
              'name_en': e.nameEn,
              'name_tr': e.nameTr,
              'icon': e.icon,
              'image': e.image,
              'is_active': e.isActive,
              'subcategories_count': e.subcategoriesCount,
              'ads_count': e.adsCount,
              if (e.customFields != null) 'custom_fields': e.customFields,
              if (e.adImagesMode != null) 'ad_images_mode': e.adImagesMode,
              if (e.adGalleryPaths != null) 'ad_gallery_paths': e.adGalleryPaths,
              if (e.adGalleryUrls != null) 'ad_gallery_urls': e.adGalleryUrls,
            },
          )
          .toList(),
    };
    TtlMemoryCache.set<List<CategoryModel>>(_memoryKeyCategories, list, _categoriesTtl);
    await PersistentTtlCache.setJson(_categoriesCacheKey, data, ttl: _categoriesTtl);
  }

  /// قراءة الفئات من الكاش فقط (فوري - للعرض أثناء التحميل)
  static List<CategoryModel>? getCachedCategories() {
    final cachedMap = PersistentTtlCache.getJsonMap(_categoriesCacheKey);
    final list = _extractCategoryList(cachedMap);
    if (list != null && list.isNotEmpty) {
      TtlMemoryCache.set<List<CategoryModel>>(
        _memoryKeyCategories,
        list,
        _categoriesTtl,
      );
      return list;
    }
    return TtlMemoryCache.getIfValid<List<CategoryModel>>(_memoryKeyCategories);
  }

  /// الحصول على جميع الفئات
  static Future<List<CategoryModel>> getCategories({
    bool forceRefresh = false,
  }) async {
    final staleCached = _extractCategoryList(
      PersistentTtlCache.getJsonMapAllowExpired(_categoriesCacheKey),
    );

    if (!forceRefresh) {
      final cachedMap = PersistentTtlCache.getJsonMap(_categoriesCacheKey);
      final cached = _extractCategoryList(cachedMap);
      if (cached != null && cached.isNotEmpty) {
        TtlMemoryCache.set<List<CategoryModel>>(
          _memoryKeyCategories,
          cached,
          _categoriesTtl,
        );
        return cached;
      }
    }

    return TtlMemoryCache.getOrLoad<List<CategoryModel>>(
      key: _memoryKeyCategories,
      ttl: _categoriesTtl,
      forceRefresh: forceRefresh,
      shouldCache: (list) => list.isNotEmpty,
      loader: () async {
        Future<List<CategoryModel>> doRequest() async {
          final response = await ApiClient.dio.get(ApiConstants.categories);
          final data = response.data as Map<String, dynamic>;
          if (data['success'] == true && data['data'] != null) {
            final list = (data['data'] as List)
                .map((e) => CategoryModel.fromJson(e as Map<String, dynamic>))
                .toList();
            if (list.isNotEmpty) {
              await PersistentTtlCache.setJson(
                _categoriesCacheKey,
                data,
                ttl: _categoriesTtl,
              );
            }
            return list;
          }
          if (staleCached != null && staleCached.isNotEmpty) return staleCached;
          return [];
        }

        try {
          return await doRequest();
        } on DioException catch (e) {
          final isTimeout = e.type == DioExceptionType.receiveTimeout ||
              e.type == DioExceptionType.connectionTimeout ||
              e.type == DioExceptionType.sendTimeout;
          if (isTimeout && staleCached == null) {
            try {
              return await doRequest();
            } on DioException {
              return [];
            }
          }
          if (staleCached != null && staleCached.isNotEmpty) return staleCached;
          return [];
        }
      },
    );
  }

  static List<CategoryModel>? _extractCategoryList(Map<String, dynamic>? data) {
    if (data == null || data['success'] != true || data['data'] == null) {
      return null;
    }
    final raw = data['data'];
    if (raw is! List) {
      return null;
    }
    return raw
        .whereType<Map>()
        .map((e) => CategoryModel.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  /// فئة فرعية واحدة (حقول مخصصة، مسار من الإعلان بدون معرف القسم الأب)
  static Future<SubcategoryModel?> getSubcategory(int id) async {
    try {
      final response = await ApiClient.dio.get(
        '${ApiConstants.subcategories}/$id',
      );
      final data = response.data as Map<String, dynamic>;
      if (data['success'] == true && data['data'] != null) {
        return SubcategoryModel.fromJson(
          data['data'] as Map<String, dynamic>,
        );
      }
      return null;
    } on DioException {
      return null;
    }
  }

  /// الحصول على فئة محددة
  static Future<CategoryModel?> getCategory(
    int id, {
    bool forceRefresh = false,
  }) async {
    // محاولة سريعة: إن كانت الفئات كلها في الكاش، نستخرج الفئة منها بدون طلب شبكة
    if (!forceRefresh) {
      final all = TtlMemoryCache.getIfValid<List<CategoryModel>>(_memoryKeyCategories) ??
          _extractCategoryList(PersistentTtlCache.getJsonMap(_categoriesCacheKey));
      final fromAll = all?.where((c) => c.id == id).toList();
      if (fromAll != null && fromAll.isNotEmpty) return fromAll.first;
    }
    return TtlMemoryCache.getOrLoad<CategoryModel?>(
      key: 'categories.$id',
      ttl: _categoriesTtl,
      forceRefresh: forceRefresh,
      shouldCache: (c) => c != null,
      loader: () async {
        try {
          final response = await ApiClient.dio.get(
            '${ApiConstants.categories}/$id',
          );
          final data = response.data as Map<String, dynamic>;
          if (data['success'] == true && data['data'] != null) {
            return CategoryModel.fromJson(data['data'] as Map<String, dynamic>);
          }
          return null;
        } on DioException {
          return null;
        }
      },
    );
  }

  /// قراءة الأقسام الفرعية من الكاش فقط (Persistent أو Memory) بدون شبكة.
  static List<SubcategoryModel>? getCachedSubcategories(int categoryId) {
    final memory = TtlMemoryCache.getIfValid<List<SubcategoryModel>>(
      'categories.$categoryId.subcategories.v2',
    );
    if (memory != null && memory.isNotEmpty) return memory;
    final cachedMap = PersistentTtlCache.getJsonMap(_subcategoriesCacheKey(categoryId));
    if (cachedMap == null || cachedMap['success'] != true) return null;
    final raw = cachedMap['data'];
    if (raw is! List) return null;
    final list = raw
        .whereType<Map>()
        .map((e) => SubcategoryModel.fromJson(Map<String, dynamic>.from(e)))
        .toList();
    if (list.isNotEmpty) {
      TtlMemoryCache.set<List<SubcategoryModel>>(
        'categories.$categoryId.subcategories.v2',
        list,
        _categoriesTtl,
      );
    }
    return list.isEmpty ? null : list;
  }

  /// الحصول على الفئات الفرعية لفئة محددة
  static Future<List<SubcategoryModel>> getSubcategories(
    int categoryId, {
    bool forceRefresh = false,
  }) async {
    return TtlMemoryCache.getOrLoad<List<SubcategoryModel>>(
      key: 'categories.$categoryId.subcategories.v2',
      ttl: _categoriesTtl,
      forceRefresh: forceRefresh,
      shouldCache: (list) => list.isNotEmpty,
      loader: () async {
        try {
          final response = await ApiClient.dio.get(
            '${ApiConstants.categories}/$categoryId/subcategories',
          );
          final data = response.data as Map<String, dynamic>;
          if (data['success'] == true && data['data'] != null) {
            final list = (data['data'] as List)
                .map(
                  (e) => SubcategoryModel.fromJson(e as Map<String, dynamic>),
                )
                .toList();
            if (list.isNotEmpty) {
              await PersistentTtlCache.setJson(
                _subcategoriesCacheKey(categoryId),
                data,
                ttl: _categoriesTtl,
              );
            }
            return list;
          }
          return [];
        } on DioException {
          final cached = getCachedSubcategories(categoryId);
          return cached ?? [];
        }
      },
    );
  }

  /// الحصول على الفئات الفرعية للفئة الفرعية (مستوى ثالث)
  static Future<List<SubcategoryModel>> getSubcategoryChildren(
    int subcategoryId, {
    bool forceRefresh = false,
  }) async {
    return TtlMemoryCache.getOrLoad<List<SubcategoryModel>>(
      key: 'subcategories.$subcategoryId.children.v2',
      ttl: _categoriesTtl,
      forceRefresh: forceRefresh,
      shouldCache: (list) => list.isNotEmpty,
      loader: () async {
        try {
          final response = await ApiClient.dio.get(
            '${ApiConstants.subcategories}/$subcategoryId/children',
          );
          final data = response.data as Map<String, dynamic>;
          if (data['success'] == true && data['data'] != null) {
            final list = (data['data'] as List)
                .map(
                  (e) => SubcategoryModel.fromJson(e as Map<String, dynamic>),
                )
                .toList();
            if (list.isNotEmpty) {
              await PersistentTtlCache.setJson(
                _subcategoryChildrenCacheKey(subcategoryId),
                data,
                ttl: _categoriesTtl,
              );
            }
            return list;
          }
          return [];
        } on DioException {
          // stale fallback
          final cachedMap = PersistentTtlCache.getJsonMapAllowExpired(
            _subcategoryChildrenCacheKey(subcategoryId),
          );
          if (cachedMap != null && cachedMap['success'] == true && cachedMap['data'] is List) {
            return (cachedMap['data'] as List)
                .whereType<Map>()
                .map((e) => SubcategoryModel.fromJson(Map<String, dynamic>.from(e)))
                .toList();
          }
          return [];
        }
      },
    );
  }
}
