import 'package:dio/dio.dart';
import 'package:image_picker/image_picker.dart';

import 'package:a3lnha/core/network/api_client.dart';
import 'package:a3lnha/core/network/api_constants.dart';

class ReportModel {
  final int id;
  final String type;
  final String reason;
  final String status;
  final String? adminResponse;
  final Map<String, dynamic>? ad;
  final Map<String, dynamic>? reportedUser;
  final List<dynamic>? conversationMessages;
  final String? createdAt;
  final String? updatedAt;

  ReportModel({
    required this.id,
    required this.type,
    required this.reason,
    required this.status,
    this.adminResponse,
    this.ad,
    this.reportedUser,
    this.conversationMessages,
    this.createdAt,
    this.updatedAt,
  });

  factory ReportModel.fromJson(Map<String, dynamic> json) {
    List<dynamic>? convMsgs;
    if (json['conversation_messages'] is List) {
      convMsgs = json['conversation_messages'] as List;
    }
    return ReportModel(
      id: json['id'] as int,
      type: json['type'] as String? ?? '',
      reason: json['reason'] as String? ?? '',
      status: json['status'] as String? ?? 'pending',
      adminResponse: json['admin_response'] as String?,
      ad: json['ad'] is Map
          ? Map<String, dynamic>.from(json['ad'] as Map)
          : null,
      reportedUser: json['reported_user'] is Map
          ? Map<String, dynamic>.from(json['reported_user'] as Map)
          : null,
      conversationMessages: convMsgs,
      createdAt: json['created_at']?.toString(),
      updatedAt: json['updated_at']?.toString(),
    );
  }
}

class ReportService {
  ReportService._();

  static Future<Map<String, dynamic>> getReports({int page = 1}) async {
    try {
      final response = await ApiClient.dio.get(
        ApiConstants.reports,
        queryParameters: {'page': page, 'per_page': 15},
      );
      final data = response.data as Map<String, dynamic>;
      if (data['success'] != true) {
        return {'reports': <ReportModel>[], 'meta': null};
      }
      dynamic raw = data['data'];
      if (raw is Map && raw['data'] != null) raw = raw['data'];
      final list = raw is List
          ? (raw as List)
              .whereType<Map<String, dynamic>>()
              .map((e) => ReportModel.fromJson(e))
              .toList()
          : <ReportModel>[];
      final meta = data['meta'] is Map ? data['meta'] as Map<String, dynamic> : null;
      return {'reports': list, 'meta': meta};
    } on DioException {
      return {'reports': <ReportModel>[], 'meta': null};
    }
  }

  static Future<ReportModel?> getReport(int id) async {
    try {
      final response = await ApiClient.dio.get('${ApiConstants.reports}/$id');
      final data = response.data as Map<String, dynamic>;
      if (data['success'] == true && data['data'] != null) {
        return ReportModel.fromJson(
            data['data'] as Map<String, dynamic>);
      }
      return null;
    } on DioException {
      return null;
    }
  }

  static Future<Map<String, dynamic>> reportAd({
    required int adId,
    required String type,
    required String reason,
  }) async {
    try {
      final response = await ApiClient.dio.post(
        ApiConstants.reports,
        data: {
          'ad_id': adId,
          'type': type,
          'reason': reason,
        },
      );
      final data = response.data as Map<String, dynamic>;
      return {
        'success': data['success'] == true,
        'message': data['message'] as String? ?? 'تم إرسال البلاغ',
      };
    } on DioException catch (e) {
      final msg = e.response?.data is Map
          ? (e.response!.data as Map)['message'] as String?
          : null;
      return {'success': false, 'message': msg ?? 'فشل إرسال البلاغ'};
    }
  }

  static Future<Map<String, dynamic>> submitReport({
    required String type,
    required String reason,
    int? adId,
    int? reportedUserId,
    int? conversationId,
    List<XFile>? images,
  }) async {
    try {
      final hasImages = images != null && images.isNotEmpty;
      if (hasImages) {
        final formData = FormData.fromMap({
          'type': type,
          'reason': reason,
          if (adId != null) 'ad_id': adId,
          if (reportedUserId != null) 'reported_user_id': reportedUserId,
          if (conversationId != null) 'conversation_id': conversationId,
        });
        for (var i = 0; i < images!.length; i++) {
          final bytes = await images[i].readAsBytes();
          formData.files.add(
            MapEntry('images[]', MultipartFile.fromBytes(bytes, filename: 'report_$i.jpg')),
          );
        }
        final response = await ApiClient.dio.post(
          ApiConstants.reports,
          data: formData,
          options: Options(
            contentType: 'multipart/form-data',
            sendTimeout: const Duration(seconds: 60),
          ),
        );
        final res = response.data as Map<String, dynamic>;
        return {
          'success': res['success'] == true,
          'message': res['message'] as String? ?? 'تم تقديم البلاغ',
        };
      }
      final body = <String, dynamic>{
        'type': type,
        'reason': reason,
      };
      if (adId != null) body['ad_id'] = adId;
      if (reportedUserId != null) body['reported_user_id'] = reportedUserId;
      if (conversationId != null) body['conversation_id'] = conversationId;
      final response = await ApiClient.dio.post(
        ApiConstants.reports,
        data: body,
      );
      final res = response.data as Map<String, dynamic>;
      return {
        'success': res['success'] == true,
        'message': res['message'] as String? ?? 'تم تقديم البلاغ',
      };
    } on DioException catch (e) {
      final msg = e.response?.data is Map
          ? (e.response!.data as Map)['message'] as String?
          : null;
      return {'success': false, 'message': msg ?? 'فشل تقديم البلاغ'};
    }
  }

  static Future<Map<String, dynamic>> reportUser({
    required int reportedUserId,
    required String type,
    required String reason,
    int? conversationId,
  }) async {
    try {
      final body = <String, dynamic>{
        'reported_user_id': reportedUserId,
        'type': type,
        'reason': reason,
      };
      if (conversationId != null) {
        body['conversation_id'] = conversationId;
      }
      final response = await ApiClient.dio.post(
        ApiConstants.reports,
        data: body,
      );
      final res = response.data as Map<String, dynamic>;
      return {
        'success': res['success'] == true,
        'message': res['message'] as String? ?? 'تم إرسال البلاغ',
      };
    } on DioException catch (e) {
      final msg = e.response?.data is Map
          ? (e.response!.data as Map)['message'] as String?
          : null;
      return {'success': false, 'message': msg ?? 'فشل إرسال البلاغ'};
    }
  }
}
