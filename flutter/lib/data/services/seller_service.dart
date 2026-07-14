import 'package:dio/dio.dart';

import 'package:a3lnha/core/network/api_client.dart';
import 'package:a3lnha/core/network/api_constants.dart';
import 'package:a3lnha/core/performance/ttl_memory_cache.dart';
import 'package:a3lnha/data/models/ad_model.dart';
import 'package:a3lnha/data/models/seller_model.dart';
import 'package:a3lnha/data/models/seller_rating_model.dart';

class SellerProfileResponse {
  final SellerModel seller;
  final List<AdModel> ads;
  final List<SellerRatingModel> ratings;
  final SellerRatingModel? userRating;
  final int currentPage;
  final int lastPage;
  final int total;

  SellerProfileResponse({
    required this.seller,
    required this.ads,
    this.ratings = const [],
    this.userRating,
    required this.currentPage,
    required this.lastPage,
    required this.total,
  });
}

class SellerRateResult {
  final bool success;
  final bool unauthorized;
  final String? message;

  const SellerRateResult({
    required this.success,
    this.unauthorized = false,
    this.message,
  });
}

class SellerService {
  SellerService._();

  static const Duration _profileTtl = Duration(minutes: 5);
  static String _profileCacheKey(String slug) => 'seller.profile.$slug';

  /// إبطال كاش ملف التاجر (بعد حظر/إلغاء حظر لضمان تحديث البيانات)
  static void invalidateProfile(String slug) {
    if (slug.trim().isEmpty) return;
    TtlMemoryCache.remove(_profileCacheKey(slug));
  }

  static Future<SellerProfileResponse?> getSellerProfile(String slug, {bool forceRefresh = false}) async {
    if (slug.trim().isEmpty) return null;
    try {
      return await TtlMemoryCache.getOrLoad<SellerProfileResponse?>(
        key: _profileCacheKey(slug),
        ttl: _profileTtl,
        forceRefresh: forceRefresh,
        shouldCache: (v) => v != null,
        loader: () => _fetchSellerProfile(slug),
      );
    } catch (_) {
      return null;
    }
  }

  static Future<SellerProfileResponse?> _fetchSellerProfile(String slug) async {
    try {
      final response = await ApiClient.dio.get('${ApiConstants.sellers}/$slug');
      final data = response.data as Map<String, dynamic>;
      if (data['success'] != true || data['data'] == null) return null;

      final inner = data['data'] as Map<String, dynamic>;
      final sellerJson = inner['seller'] as Map<String, dynamic>?;
      if (sellerJson == null) return null;

      final seller = SellerModel.fromJson(sellerJson);

      List<AdModel> ads = [];
      final adsRaw = inner['ads'];
      if (adsRaw is List) {
        for (final item in adsRaw) {
          if (item is Map<String, dynamic>) {
            try {
              ads.add(AdModel.fromJson(item));
            } catch (_) {}
          }
        }
      }

      List<SellerRatingModel> ratings = [];
      final ratingsRaw = inner['ratings'];
      if (ratingsRaw is List) {
        for (final item in ratingsRaw) {
          if (item is Map<String, dynamic>) {
            try {
              ratings.add(SellerRatingModel.fromJson(item));
            } catch (_) {}
          }
        }
      }

      SellerRatingModel? userRating;
      final ur = inner['user_rating'];
      if (ur is Map<String, dynamic>) {
        try {
          userRating = SellerRatingModel.fromJson(ur);
        } catch (_) {}
      }

      final meta = inner['ads_meta'] as Map<String, dynamic>? ?? {};
      return SellerProfileResponse(
        seller: seller,
        ads: ads,
        ratings: ratings,
        userRating: userRating,
        currentPage: meta['current_page'] as int? ?? 1,
        lastPage: meta['last_page'] as int? ?? 1,
        total: meta['total'] as int? ?? 0,
      );
    } on DioException {
      rethrow;
    }
  }

  /// تقييم التاجر (يحتاج تسجيل دخول)
  static Future<SellerRateResult> rateSeller({
    required String slug,
    required int rating,
    String? comment,
  }) async {
    try {
      final response = await ApiClient.dio.post(
        '${ApiConstants.sellers}/$slug/rate',
        data: {
          'rating': rating,
          if (comment != null && comment.isNotEmpty) 'comment': comment,
        },
      );
      final data = response.data as Map<String, dynamic>;
      return SellerRateResult(
        success: data['success'] == true,
        message: data['message']?.toString(),
      );
    } on DioException catch (e) {
      final data = e.response?.data;
      String? msg;
      if (data is Map<String, dynamic>) {
        msg = data['message']?.toString();
      }
      return SellerRateResult(
        success: false,
        unauthorized: e.response?.statusCode == 401,
        message: msg,
      );
    }
  }
}
