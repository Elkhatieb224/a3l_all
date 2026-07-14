import 'package:dio/dio.dart';
import 'package:a3lnha/core/network/api_client.dart';
import 'package:a3lnha/core/network/api_constants.dart';

class NotificationModel {
  final String id;
  final String type;
  final Map<String, dynamic>? data;
  final String? readAt;
  final String createdAt;

  NotificationModel({
    required this.id,
    required this.type,
    this.data,
    this.readAt,
    required this.createdAt,
  });

  factory NotificationModel.fromJson(Map<String, dynamic> json) {
    return NotificationModel(
      id: json['id'] as String? ?? '',
      type: json['type'] as String? ?? '',
      data: json['data'] is Map
          ? Map<String, dynamic>.from(json['data'] as Map)
          : null,
      readAt: json['read_at']?.toString(),
      createdAt: json['created_at']?.toString() ?? '',
    );
  }

  String get title => data?['title'] as String? ?? '';
  String get message => data?['message'] as String? ?? '';
  bool get isRead => readAt != null;
}

class NotificationService {
  NotificationService._();

  static Future<List<NotificationModel>> getNotifications(
      {int page = 1, int perPage = 20}) async {
    try {
      final response = await ApiClient.dio.get(
        ApiConstants.notifications,
        queryParameters: {'page': page, 'per_page': perPage},
      );
      final resp = response.data as Map<String, dynamic>;
      if (resp['success'] != true || resp['data'] == null) return [];
      final list = resp['data'] as List;
      return list
          .whereType<Map<String, dynamic>>()
          .map((e) => NotificationModel.fromJson(e))
          .toList();
    } on DioException {
      return [];
    }
  }

  static Future<bool> markAsRead(String id) async {
    try {
      final response = await ApiClient.dio
          .post('${ApiConstants.notifications}/$id/read');
      final data = response.data as Map<String, dynamic>;
      return data['success'] == true;
    } on DioException {
      return false;
    }
  }

  static Future<bool> markAllAsRead() async {
    try {
      final response = await ApiClient.dio
          .post('${ApiConstants.notifications}/read-all');
      final data = response.data as Map<String, dynamic>;
      return data['success'] == true;
    } on DioException {
      return false;
    }
  }

  static Future<int> getUnreadCount() async {
    try {
      final response = await ApiClient.dio
          .get('${ApiConstants.notifications}/unread-count');
      final data = response.data as Map<String, dynamic>;
      return data['count'] as int? ?? 0;
    } on DioException {
      return 0;
    }
  }
}
