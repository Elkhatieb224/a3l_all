import 'package:dio/dio.dart';
import 'package:a3lnha/core/network/api_client.dart';
import 'package:a3lnha/core/network/api_constants.dart';
import 'package:a3lnha/data/models/seller_model.dart';

class FavoriteSellerService {
  FavoriteSellerService._();

  static Future<List<SellerModel>> getFavoriteSellers() async {
    try {
      final response = await ApiClient.dio.get(ApiConstants.favoriteSellers);
      final data = response.data as Map<String, dynamic>;
      if (data['success'] != true) return [];
      dynamic raw = data['data'];
      if (raw is Map && raw['data'] != null) raw = raw['data'];
      if (raw is! List) return [];
      return (raw as List)
          .map((e) => e is Map<String, dynamic> ? SellerModel.fromJson(e) : null)
          .whereType<SellerModel>()
          .toList();
    } on DioException {
      return [];
    }
  }

  /// إضافة/إزالة التاجر من المفضلة (متابعة/إلغاء المتابعة)
  /// يرجع Map: { success, isFavorite } أو null عند فشل
  static Future<Map<String, dynamic>?> toggle(String sellerSlug) async {
    try {
      final response = await ApiClient.dio.post(
        '${ApiConstants.favoriteSellers}/$sellerSlug/toggle',
      );
      final data = response.data as Map<String, dynamic>;
      if (data['success'] == true) {
        return {
          'success': true,
          'isFavorite': data['is_favorite'] == true,
        };
      }
      return null;
    } on DioException {
      return null;
    }
  }

  static Future<bool> remove(String sellerSlug) async {
    try {
      final response = await ApiClient.dio.delete(
        '${ApiConstants.favoriteSellers}/$sellerSlug',
      );
      final data = response.data as Map<String, dynamic>;
      return data['success'] == true;
    } on DioException {
      return false;
    }
  }
}
