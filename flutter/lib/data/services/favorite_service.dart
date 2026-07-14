import 'package:dio/dio.dart';

import 'package:a3lnha/core/network/api_client.dart';
import 'package:a3lnha/core/network/api_constants.dart';
import 'package:a3lnha/data/models/ad_model.dart';

class FavoritesResponse {
  final List<AdModel> ads;
  final int currentPage;
  final int lastPage;
  final int total;

  FavoritesResponse({
    required this.ads,
    required this.currentPage,
    required this.lastPage,
    required this.total,
  });
}

class FavoriteService {
  FavoriteService._();

  /// قائمة الإعلانات المفضلة (تتطلب تسجيل دخول)
  static Future<FavoritesResponse> getFavorites({
    int page = 1,
    int perPage = 20,
  }) async {
    try {
      final response = await ApiClient.dio.get(
        ApiConstants.favorites,
        queryParameters: {'page': page, 'per_page': perPage},
      );
      final data = response.data as Map<String, dynamic>;
      if (data['success'] != true) {
        return FavoritesResponse(ads: [], currentPage: 1, lastPage: 1, total: 0);
      }
      dynamic rawList = data['data'];
      if (rawList is Map && rawList['data'] != null) {
        rawList = rawList['data'];
      }
      List<AdModel> adsList = [];
      if (rawList != null && rawList is List) {
        for (final item in rawList) {
          if (item is Map<String, dynamic>) {
            try {
              adsList.add(AdModel.fromJson(item));
            } catch (_) {}
          }
        }
      }
      Map<String, dynamic> meta = {};
      final metaVal = data['meta'];
      if (metaVal is Map) {
        meta = Map<String, dynamic>.from(metaVal);
      }
      return FavoritesResponse(
        ads: adsList,
        currentPage: meta['current_page'] as int? ?? 1,
        lastPage: meta['last_page'] as int? ?? 1,
        total: meta['total'] as int? ?? 0,
      );
    } on DioException {
      return FavoritesResponse(ads: [], currentPage: 1, lastPage: 1, total: 0);
    }
  }

  /// إضافة/إزالة من المفضلة (تتطلب تسجيل دخول)
  /// يرجع Map: { success, isFavorite } أو null عند فشل/غير مسجل
  static Future<Map<String, dynamic>?> toggle(String adUid) async {
    try {
      final response = await ApiClient.dio.post(
        '${ApiConstants.favorites}/$adUid/toggle',
      );
      final data = response.data as Map<String, dynamic>;
      if (data['success'] == true) {
        return {
          'success': true,
          'isFavorite': data['is_favorite'] == true,
        };
      }
      return null;
    } on DioException catch (e) {
      if (e.response?.statusCode == 401) {
        return {'success': false, 'isFavorite': false, 'authRequired': true};
      }
      return null;
    }
  }

  /// إزالة من المفضلة
  static Future<bool> remove(String adUid) async {
    try {
      final response = await ApiClient.dio.delete(
        '${ApiConstants.favorites}/$adUid',
      );
      final data = response.data as Map<String, dynamic>;
      return data['success'] == true;
    } on DioException {
      return false;
    }
  }
}
