import 'package:dio/dio.dart';
import 'package:a3lnha/core/performance/persistent_ttl_cache.dart';
import 'package:a3lnha/core/performance/ttl_memory_cache.dart';
import 'package:a3lnha/core/network/api_client.dart';
import 'package:a3lnha/core/network/api_constants.dart';

class FaqItem {
  final int id;
  final String question;
  final String answer;
  final int? order;

  FaqItem({
    required this.id,
    required this.question,
    required this.answer,
    this.order,
  });

  factory FaqItem.fromJson(Map<String, dynamic> json) {
    return FaqItem(
      id: json['id'] as int,
      question: json['question'] as String? ?? '',
      answer: json['answer'] as String? ?? '',
      order: json['order'] as int?,
    );
  }
}

class HelpService {
  HelpService._();
  static const Duration _helpTtl = Duration(minutes: 30);
  static const _helpFaqsCacheKey = 'api.help.faqs';

  static Future<List<FaqItem>> getFaqs({bool forceRefresh = false}) async {
    if (!forceRefresh) {
      final cached = _extractFaqList(
        PersistentTtlCache.getJsonMap(_helpFaqsCacheKey),
      );
      if (cached != null && cached.isNotEmpty) {
        TtlMemoryCache.set<List<FaqItem>>('help.faqs', cached, _helpTtl);
        return cached;
      }
    }

    return TtlMemoryCache.getOrLoad<List<FaqItem>>(
      key: 'help.faqs',
      ttl: _helpTtl,
      forceRefresh: forceRefresh,
      shouldCache: (list) => list.isNotEmpty,
      loader: () async {
        try {
          final response = await ApiClient.dio.get(ApiConstants.help);
          final data = response.data as Map<String, dynamic>;
          if (data['success'] != true) {
            return [];
          }
          dynamic raw = data['data'];
          if (raw is! List) {
            return [];
          }
          final list = raw
              .map(
                (e) => e is Map<String, dynamic> ? FaqItem.fromJson(e) : null,
              )
              .whereType<FaqItem>()
              .toList();
          if (list.isNotEmpty) {
            await PersistentTtlCache.setJson(
              _helpFaqsCacheKey,
              data,
              ttl: _helpTtl,
            );
          }
          return list;
        } on DioException {
          return [];
        }
      },
    );
  }

  static List<FaqItem>? _extractFaqList(Map<String, dynamic>? data) {
    if (data == null || data['success'] != true || data['data'] == null) {
      return null;
    }
    final raw = data['data'];
    if (raw is! List) {
      return null;
    }
    return raw
        .whereType<Map>()
        .map((e) => FaqItem.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  static Future<Map<String, dynamic>> sendSupportMessage({
    required String subject,
    required String message,
    String? name,
    String? email,
    List<String>? imagePaths,
  }) async {
    try {
      if (imagePaths != null && imagePaths.isNotEmpty) {
        final files = imagePaths;
        final formData = FormData.fromMap({
          'subject': subject,
          'message': message,
          if (name != null && name.isNotEmpty) 'name': name,
          if (email != null && email.isNotEmpty) 'email': email,
        });
        for (var i = 0; i < files.length; i++) {
          final path = files[i];
          if (path.isNotEmpty) {
            formData.files.add(
              MapEntry('attachments[$i]', await MultipartFile.fromFile(path)),
            );
          }
        }
        final response = await ApiClient.dio.post(
          '${ApiConstants.help}/contact',
          data: formData,
        );
        final data = response.data as Map<String, dynamic>;
        return {
          'success': data['success'] == true,
          'message': data['message'] as String? ?? 'تم الإرسال',
        };
      }
      final response = await ApiClient.dio.post(
        '${ApiConstants.help}/contact',
        data: {
          'subject': subject,
          'message': message,
          if (name != null && name.isNotEmpty) 'name': name,
          if (email != null && email.isNotEmpty) 'email': email,
        },
      );
      final data = response.data as Map<String, dynamic>;
      return {
        'success': data['success'] == true,
        'message': data['message'] as String? ?? 'تم الإرسال',
      };
    } on DioException catch (e) {
      final msg = e.response?.data is Map
          ? (e.response!.data as Map)['message'] as String?
          : null;
      return {'success': false, 'message': msg ?? 'فشل الإرسال'};
    }
  }
}
