import 'package:dio/dio.dart';
import 'package:a3lnha/core/network/api_client.dart';
import 'package:a3lnha/core/network/api_constants.dart';
import 'package:a3lnha/data/models/user_model.dart';

class BlockedUserService {
  BlockedUserService._();

  static Future<List<UserModel>> getBlockedUsers() async {
    try {
      final response = await ApiClient.dio.get(ApiConstants.blockedUsers);
      final data = response.data as Map<String, dynamic>;
      if (data['success'] != true || data['data'] == null) return [];
      final list = data['data'] as List;
      return list
          .whereType<Map<String, dynamic>>()
          .map((e) => UserModel.fromJson(e))
          .toList();
    } on DioException {
      return [];
    }
  }

  static Future<Map<String, dynamic>> blockUser(int userId) async {
    try {
      final response = await ApiClient.dio.post(
        ApiConstants.blockedUsers,
        data: {'user_id': userId},
      );
      final data = response.data as Map<String, dynamic>;
      return {
        'success': data['success'] == true,
        'message': data['message'] as String? ?? 'تم الحظر',
      };
    } on DioException catch (e) {
      final msg = e.response?.data is Map
          ? (e.response!.data as Map)['message'] as String?
          : null;
      return {'success': false, 'message': msg ?? 'فشل الحظر'};
    }
  }

  static Future<Map<String, dynamic>> unblockUser(int userId) async {
    try {
      final response = await ApiClient.dio
          .delete('${ApiConstants.blockedUsers}/$userId');
      final data = response.data as Map<String, dynamic>;
      return {
        'success': data['success'] == true,
        'message': data['message'] as String? ?? 'تم إلغاء الحظر',
      };
    } on DioException catch (e) {
      final msg = e.response?.data is Map
          ? (e.response!.data as Map)['message'] as String?
          : null;
      return {'success': false, 'message': msg ?? 'فشل إلغاء الحظر'};
    }
  }
}
