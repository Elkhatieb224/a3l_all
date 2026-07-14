import 'package:a3lnha/core/network/api_client.dart';
import 'package:a3lnha/core/network/api_constants.dart';
import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:geocoding/geocoding.dart';

/// تحويل الإحداثيات إلى نص مقروء (دولة - محافظة - مدينة) عبر المنصّة أو Nominatim (مفيد للويب).
class ReverseGeocodingService {
  ReverseGeocodingService._();

  static final Map<String, String> _positiveCache = {};
  static final Map<String, Future<String?>> _pending = {};

  static String _cacheKey(double lat, double lng, String lang) =>
      '$lang|${lat.toStringAsFixed(4)}|${lng.toStringAsFixed(4)}';

  static final Dio _dio = Dio(
    BaseOptions(
      connectTimeout: const Duration(seconds: 12),
      receiveTimeout: const Duration(seconds: 12),
    ),
  );

  static String _formatPlacemark(Placemark p) {
    final parts = <String>[];
    void add(String? s) {
      final t = s?.trim();
      if (t == null || t.isEmpty) return;
      for (final e in parts) {
        if (e.toLowerCase() == t.toLowerCase()) return;
      }
      parts.add(t);
    }

    add(p.country);
    add(p.administrativeArea);
    add(p.locality);
    if (parts.length < 3) add(p.subAdministrativeArea);
    if (parts.length < 3) add(p.subLocality);
    return parts.take(3).join(' - ');
  }

  static String? _fromNominatimAddress(Map<String, dynamic> data) {
    final addr = data['address'];
    if (addr is Map) {
      final m = Map<String, dynamic>.from(addr);
      final parts = <String>[];
      void addKey(String k) {
        final v = m[k]?.toString().trim();
        if (v == null || v.isEmpty) return;
        if (parts.any((e) => e.toLowerCase() == v.toLowerCase())) return;
        parts.add(v);
      }

      addKey('country');
      addKey('state');
      addKey('region');
      addKey('province');
      addKey('county');
      addKey('city');
      addKey('town');
      addKey('village');
      addKey('suburb');
      addKey('neighbourhood');
      if (parts.isNotEmpty) return parts.take(3).join(' - ');
    }
    final display = data['display_name']?.toString().trim();
    if (display != null && display.isNotEmpty) return display;
    return null;
  }

  /// [languageCode] مثل ar / en / tr لـ Nominatim فقط.
  /// يُخزَّن النص الناجح فقط حتى لا تُكرَّر طلبات الشبكة في قوائم الإعلانات.
  static Future<String?> humanReadableFromCoordinates(
    double latitude,
    double longitude, {
    String languageCode = 'ar',
  }) {
    final key = _cacheKey(latitude, longitude, languageCode);
    final hit = _positiveCache[key];
    if (hit != null) return Future.value(hit);

    final existing = _pending[key];
    if (existing != null) return existing;

    final fut = _humanReadableFromCoordinatesUncached(
      latitude,
      longitude,
      languageCode: languageCode,
    ).then<String?>((r) {
      if (r != null && r.isNotEmpty) _positiveCache[key] = r;
      return r;
    }).whenComplete(() => _pending.remove(key));
    _pending[key] = fut;
    return fut;
  }

  static Future<String?> _humanReadableFromCoordinatesUncached(
    double latitude,
    double longitude, {
    String languageCode = 'ar',
  }) async {
    try {
      final marks = await placemarkFromCoordinates(latitude, longitude);
      if (marks.isNotEmpty) {
        final line = _formatPlacemark(marks.first);
        if (line.isNotEmpty) return line;
      }
    } catch (e) {
      if (kDebugMode) {
        debugPrint('ReverseGeocodingService.placemark: $e');
      }
    }

    try {
      final uri = Uri.parse(
        'https://nominatim.openstreetmap.org/reverse'
        '?lat=$latitude&lon=$longitude&format=json&addressdetails=1&accept-language=$languageCode',
      );
      final res = await _dio.getUri(
        uri,
        options: Options(
          headers: {
            'User-Agent': 'A3lnha/1.0 (classifieds; +https://aalenha.com)',
          },
          responseType: ResponseType.json,
          validateStatus: (s) => s != null && s < 500,
        ),
      );
      if (res.statusCode != 200 || res.data == null) return null;
      final raw = res.data;
      if (raw is! Map) return null;
      return _fromNominatimAddress(Map<String, dynamic>.from(raw));
    } catch (e) {
      if (kDebugMode) {
        debugPrint('ReverseGeocodingService.nominatim: $e');
      }
      return null;
    }
  }

  /// سلاسل إضافية لمطابقة الكتالوج عندما يعيد [placemarkFromCoordinates] حقولاً فارغة (خصوصاً على الويب).
  static List<String> matchNeedlesFromNominatimJson(Map<String, dynamic> data) {
    final out = <String>{};
    final addr = data['address'];
    if (addr is Map) {
      final m = Map<String, dynamic>.from(addr);
      const keys = <String>[
        'state',
        'state_district',
        'region',
        'province',
        'county',
        'municipality',
        'city',
        'town',
        'village',
        'suburb',
        'neighbourhood',
        'quarter',
        'district',
        'road',
        'amenity',
        'tourism',
      ];
      for (final k in keys) {
        final v = m[k]?.toString().trim();
        if (v == null || v.isEmpty) continue;
        out.add(v);
        for (final part in v.split(RegExp(r'[\s,/\-_.]+'))) {
          final t = part.trim();
          if (t.length >= 3) out.add(t);
        }
      }
      final country = m['country']?.toString().trim();
      if (country != null && country.isNotEmpty) out.add(country);
    }
    final dn = data['display_name']?.toString().trim();
    if (dn != null && dn.isNotEmpty) {
      out.add(dn);
      for (final part in dn.split(RegExp(r'[,;]'))) {
        final t = part.trim();
        if (t.length >= 3) out.add(t);
      }
    }
    return out.toList();
  }

  static Future<List<String>> nominatimMatchNeedles(
    double latitude,
    double longitude, {
    String languageCode = 'en,ar',
  }) async {
    try {
      Map<String, dynamic>? rawMap;

      if (kIsWeb) {
        try {
          final res = await ApiClient.dio.get<dynamic>(
            ApiConstants.reverseGeocode,
            queryParameters: <String, dynamic>{
              'lat': latitude,
              'lng': longitude,
              'accept_language': languageCode,
            },
          );
          final data = res.data;
          if (data is Map &&
              data['success'] == true &&
              data['data'] is Map) {
            rawMap = Map<String, dynamic>.from(data['data'] as Map);
          } else if (kDebugMode) {
            debugPrint(
              'ReverseGeocodingService.nominatimMatchNeedles: API body unexpected $data',
            );
          }
        } catch (e) {
          if (kDebugMode) {
            debugPrint(
              'ReverseGeocodingService.nominatimMatchNeedles(api proxy): $e',
            );
          }
        }
      }

      if (rawMap == null) {
        final uri = Uri.parse(
          'https://nominatim.openstreetmap.org/reverse'
          '?lat=$latitude&lon=$longitude&format=json&addressdetails=1&accept-language=$languageCode',
        );
        final res = await _dio.getUri(
          uri,
          options: Options(
            headers: {
              'User-Agent': 'A3lnha/1.0 (classifieds; +https://aalenha.com)',
            },
            responseType: ResponseType.json,
            validateStatus: (s) => s != null && s < 500,
          ),
        );
        if (res.statusCode != 200 || res.data == null) return [];
        final raw = res.data;
        if (raw is! Map) return [];
        rawMap = Map<String, dynamic>.from(raw);
      }

      return matchNeedlesFromNominatimJson(rawMap);
    } catch (e) {
      if (kDebugMode) {
        debugPrint('ReverseGeocodingService.nominatimMatchNeedles: $e');
      }
      return [];
    }
  }
}
