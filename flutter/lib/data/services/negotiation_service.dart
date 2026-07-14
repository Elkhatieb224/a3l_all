import 'package:dio/dio.dart';
import 'package:a3lnha/core/network/api_client.dart';
import 'package:a3lnha/core/network/api_constants.dart';

class NegotiationModel {
  final int id;
  final Map<String, dynamic>? ad;
  final Map<String, dynamic>? buyer;
  final Map<String, dynamic>? seller;
  final num? offeredPrice;
  final String? currency;
  final String? message;
  final String status;
  final String? rejectionReason;
  final int? conversationId;
  final String? createdAt;

  NegotiationModel({
    required this.id,
    this.ad,
    this.buyer,
    this.seller,
    this.offeredPrice,
    this.currency,
    this.message,
    required this.status,
    this.rejectionReason,
    this.conversationId,
    this.createdAt,
  });

  factory NegotiationModel.fromJson(Map<String, dynamic> json) {
    int _toInt(dynamic v) {
      if (v is int) return v;
      if (v is num) return v.toInt();
      return int.tryParse(v?.toString() ?? '') ?? 0;
    }

    int? _toNullableInt(dynamic v) {
      if (v == null) return null;
      if (v is int) return v;
      if (v is num) return v.toInt();
      return int.tryParse(v.toString());
    }

    num? _toNum(dynamic v) {
      if (v == null) return null;
      if (v is num) return v;
      return num.tryParse(v.toString());
    }

    return NegotiationModel(
      id: _toInt(json['id']),
      ad: json['ad'] is Map ? Map<String, dynamic>.from(json['ad'] as Map) : null,
      buyer: json['buyer'] is Map ? Map<String, dynamic>.from(json['buyer'] as Map) : null,
      seller: json['seller'] is Map ? Map<String, dynamic>.from(json['seller'] as Map) : null,
      offeredPrice: _toNum(json['offered_price']) ?? 0,
      currency: json['currency'] as String?,
      message: json['message'] as String?,
      status: json['status'] as String? ?? 'pending',
      rejectionReason: json['rejection_reason'] as String?,
      conversationId: _toNullableInt(json['conversation_id']),
      createdAt: json['created_at']?.toString(),
    );
  }
}

class NegotiationService {
  NegotiationService._();

  static Future<List<NegotiationModel>> getSent({int page = 1, int perPage = 50}) async {
    try {
      final response = await ApiClient.dio.get(
        '${ApiConstants.negotiations}/sent',
        queryParameters: {'page': page, 'per_page': perPage},
      );
      return _parseList(response.data);
    } on DioException {
      return [];
    }
  }

  static Future<List<NegotiationModel>> getReceived({int page = 1, int perPage = 50}) async {
    try {
      final response = await ApiClient.dio.get(
        '${ApiConstants.negotiations}/received',
        queryParameters: {'page': page, 'per_page': perPage},
      );
      return _parseList(response.data);
    } on DioException {
      return [];
    }
  }

  /// جلب جميع الطلبات (جميع الصفحات) لعرض المُرسلة والمُستلمة بما فيها المكتملة
  static Future<List<NegotiationModel>> getAllSent() async {
    final all = <NegotiationModel>[];
    int page = 1;
    while (true) {
      try {
        final response = await ApiClient.dio.get(
          '${ApiConstants.negotiations}/sent',
          queryParameters: {'page': page, 'per_page': 50},
        );
        final data = response.data as Map<String, dynamic>?;
        if (data == null || data['success'] != true) break;
        final items = _parseList(data);
        if (items.isEmpty) break;
        all.addAll(items);
        final meta = data['meta'];
        if (meta is! Map) break;
        final lastPage = meta['last_page'] as int? ?? 1;
        if (page >= lastPage) break;
        page++;
      } on DioException {
        break;
      }
    }
    return all;
  }

  static Future<List<NegotiationModel>> getAllReceived() async {
    final all = <NegotiationModel>[];
    int page = 1;
    while (true) {
      try {
        final response = await ApiClient.dio.get(
          '${ApiConstants.negotiations}/received',
          queryParameters: {'page': page, 'per_page': 50},
        );
        final data = response.data as Map<String, dynamic>?;
        if (data == null || data['success'] != true) break;
        final items = _parseList(data);
        if (items.isEmpty) break;
        all.addAll(items);
        final meta = data['meta'];
        if (meta is! Map) break;
        final lastPage = meta['last_page'] as int? ?? 1;
        if (page >= lastPage) break;
        page++;
      } on DioException {
        break;
      }
    }
    return all;
  }

  static List<NegotiationModel> _parseList(dynamic responseData) {
    if (responseData == null) return [];
    Map<String, dynamic>? data;
    if (responseData is Map) {
      data = Map<String, dynamic>.from(responseData as Map);
    } else {
      return [];
    }
    if (data['success'] != true) return [];
    dynamic raw = data['data'];
    if (raw is Map && raw['data'] != null) {
      raw = (raw as Map)['data'];
    }
    if (raw is Map) {
      raw = raw.values.toList();
    }
    if (raw is! List) return [];
    final list = <NegotiationModel>[];
    for (final e in raw) {
      try {
        if (e is Map) {
          final m = Map<String, dynamic>.from(e as Map);
          list.add(NegotiationModel.fromJson(m));
        }
      } catch (_) {}
    }
    return list;
  }

  static Future<Map<String, dynamic>> store({
    required String adUid,
    required num offeredPrice,
    required String currency,
    String? message,
  }) async {
    try {
      final response = await ApiClient.dio.post(
        '${ApiConstants.negotiations}/$adUid',
        data: {
          'offered_price': offeredPrice,
          'currency': currency,
          if (message != null && message.isNotEmpty) 'message': message,
        },
      );
      final data = response.data as Map<String, dynamic>;
      return {
        'success': data['success'] == true,
        'message': data['message'] as String? ?? 'تم إرسال طلب التفاوض',
      };
    } on DioException catch (e) {
      final msg = e.response?.data is Map
          ? (e.response!.data as Map)['message'] as String?
          : null;
      return {'success': false, 'message': msg ?? 'فشل إرسال طلب التفاوض'};
    }
  }

  static Future<bool> accept(int id) async {
    try {
      final response = await ApiClient.dio.post(
        '${ApiConstants.negotiations}/$id/accept',
      );
      final data = response.data as Map<String, dynamic>;
      return data['success'] == true;
    } on DioException {
      return false;
    }
  }

  static Future<bool> reject(int id, {String? reason}) async {
    try {
      final response = await ApiClient.dio.post(
        '${ApiConstants.negotiations}/$id/reject',
        data: {'rejection_reason': reason ?? ''},
      );
      final data = response.data as Map<String, dynamic>;
      return data['success'] == true;
    } on DioException {
      return false;
    }
  }
}
