import 'package:dio/dio.dart';

import 'package:a3lnha/core/performance/persistent_ttl_cache.dart';
import 'package:a3lnha/core/performance/ttl_memory_cache.dart';
import 'package:a3lnha/core/network/api_client.dart';
import 'package:a3lnha/core/network/api_constants.dart';

class LegalService {
  LegalService._();
  static const Duration _legalTtl = Duration(hours: 6);
  static const _privacyCacheKey = 'api.legal.privacy';
  static const _termsCacheKey = 'api.legal.terms';

  static Future<String?> getPrivacyContent({bool forceRefresh = false}) async {
    if (!forceRefresh) {
      final cached = _extractContent(
        PersistentTtlCache.getJsonMap(_privacyCacheKey),
      );
      if (cached != null) {
        TtlMemoryCache.set<String?>('legal.privacy.content', cached, _legalTtl);
        return cached;
      }
    }

    return TtlMemoryCache.getOrLoad<String?>(
      key: 'legal.privacy.content',
      ttl: _legalTtl,
      forceRefresh: forceRefresh,
      shouldCache: (s) => s != null && s.trim().isNotEmpty,
      loader: () async {
        try {
          final response = await ApiClient.dio.get(ApiConstants.legalPrivacy);
          final data = response.data as Map<String, dynamic>;
          if (data['success'] == true && data['data'] != null) {
            final inner = data['data'] as Map<String, dynamic>;
            final content = inner['content'] as String?;
            if (content != null && content.trim().isNotEmpty) {
              await PersistentTtlCache.setJson(
                _privacyCacheKey,
                data,
                ttl: _legalTtl,
              );
            }
            return content;
          }
          return null;
        } on DioException {
          return null;
        }
      },
    );
  }

  static Future<String?> getTermsContent({bool forceRefresh = false}) async {
    if (!forceRefresh) {
      final cached = _extractContent(
        PersistentTtlCache.getJsonMap(_termsCacheKey),
      );
      if (cached != null) {
        TtlMemoryCache.set<String?>('legal.terms.content', cached, _legalTtl);
        return cached;
      }
    }

    return TtlMemoryCache.getOrLoad<String?>(
      key: 'legal.terms.content',
      ttl: _legalTtl,
      forceRefresh: forceRefresh,
      shouldCache: (s) => s != null && s.trim().isNotEmpty,
      loader: () async {
        try {
          final response = await ApiClient.dio.get(ApiConstants.legalTerms);
          final data = response.data as Map<String, dynamic>;
          if (data['success'] == true && data['data'] != null) {
            final inner = data['data'] as Map<String, dynamic>;
            final content = inner['content'] as String?;
            if (content != null && content.trim().isNotEmpty) {
              await PersistentTtlCache.setJson(
                _termsCacheKey,
                data,
                ttl: _legalTtl,
              );
            }
            return content;
          }
          return null;
        } on DioException {
          return null;
        }
      },
    );
  }

  static String? _extractContent(Map<String, dynamic>? data) {
    if (data == null || data['success'] != true || data['data'] == null) {
      return null;
    }
    final inner = data['data'];
    if (inner is! Map) {
      return null;
    }
    return inner['content'] as String?;
  }
}
