import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/network/api_client.dart';
import 'package:a3lnha/core/network/api_constants.dart';
import 'package:dio/dio.dart';

Map<String, dynamic> _jsonObject(dynamic value) {
  if (value is Map<String, dynamic>) return value;
  if (value is Map) return Map<String, dynamic>.from(value);
  return {};
}

/// اسم العرض من حقول name أو name_ar / name_en / name_tr حسب اللغة المطلوبة.
String regionDisplayNameFromJson(
  Map<String, dynamic> json, {
  String? locale,
}) {
  final single = json['name']?.toString().trim() ?? '';
  final ar = json['name_ar']?.toString().trim() ?? '';
  final en = json['name_en']?.toString().trim() ?? '';
  final tr = json['name_tr']?.toString().trim() ?? '';
  final hasTri = ar.isNotEmpty || en.isNotEmpty || tr.isNotEmpty;
  if (!hasTri) return single;

  final loc = (locale ?? AppLocale.current).toLowerCase();
  if (loc == 'tr') {
    return tr.isNotEmpty ? tr : (en.isNotEmpty ? en : (ar.isNotEmpty ? ar : single));
  }
  if (loc == 'en') {
    return en.isNotEmpty ? en : (ar.isNotEmpty ? ar : (tr.isNotEmpty ? tr : single));
  }
  return ar.isNotEmpty ? ar : (en.isNotEmpty ? en : (tr.isNotEmpty ? tr : single));
}

class RegionDistrict {
  final String code;
  final String name;
  final List<String> matchNames;

  RegionDistrict({
    required this.code,
    required this.name,
    List<String>? matchNames,
  }) : matchNames = matchNames ?? const [];

  factory RegionDistrict.fromJson(Map<String, dynamic> json, {String? locale}) {
    final rawMn = json['match_names'];
    final mn = <String>[];
    if (rawMn is List) {
      for (final e in rawMn) {
        final s = e?.toString().trim() ?? '';
        if (s.isNotEmpty) mn.add(s);
      }
    }
    final name = regionDisplayNameFromJson(json, locale: locale);
    if (mn.isEmpty && name.isNotEmpty) mn.add(name);
    return RegionDistrict(
      code: json['code']?.toString() ?? '',
      name: name,
      matchNames: mn,
    );
  }
}

class RegionCity {
  final String code;
  final String name;
  final List<String> matchNames;
  final List<RegionDistrict> districts;

  RegionCity({
    required this.code,
    required this.name,
    List<String>? matchNames,
    required this.districts,
  }) : matchNames = matchNames ?? const [];

  factory RegionCity.fromJson(Map<String, dynamic> json, {String? locale}) {
    final raw = json['districts'];
    final list = <RegionDistrict>[];
    if (raw is List) {
      for (final e in raw) {
        final m = _jsonObject(e);
        if (m.isNotEmpty) list.add(RegionDistrict.fromJson(m, locale: locale));
      }
    }
    final rawMn = json['match_names'];
    final mn = <String>[];
    if (rawMn is List) {
      for (final e in rawMn) {
        final s = e?.toString().trim() ?? '';
        if (s.isNotEmpty) mn.add(s);
      }
    }
    final name = regionDisplayNameFromJson(json, locale: locale);
    if (mn.isEmpty && name.isNotEmpty) mn.add(name);
    return RegionCity(
      code: json['code']?.toString() ?? '',
      name: name,
      matchNames: mn,
      districts: list,
    );
  }
}

class RegionStateNode {
  /// معرّف الصف في geo_divisions (من الـ API).
  final int? geoId;
  final String code;
  final String name;
  final List<String> matchNames;
  final List<RegionCity> cities;

  RegionStateNode({
    this.geoId,
    required this.code,
    required this.name,
    List<String>? matchNames,
    required this.cities,
  }) : matchNames = matchNames ?? const [];

  factory RegionStateNode.fromJson(Map<String, dynamic> json, {String? locale}) {
    final raw = json['cities'];
    final list = <RegionCity>[];
    if (raw is List) {
      for (final e in raw) {
        final m = _jsonObject(e);
        if (m.isNotEmpty) list.add(RegionCity.fromJson(m, locale: locale));
      }
    }
    final rawMn = json['match_names'];
    final mn = <String>[];
    if (rawMn is List) {
      for (final e in rawMn) {
        final s = e?.toString().trim() ?? '';
        if (s.isNotEmpty) mn.add(s);
      }
    }
    final name = regionDisplayNameFromJson(json, locale: locale);
    if (mn.isEmpty && name.isNotEmpty) mn.add(name);
    final idRaw = json['id'];
    int? gid;
    if (idRaw is int) {
      gid = idRaw;
    } else if (idRaw != null) {
      gid = int.tryParse(idRaw.toString());
    }
    return RegionStateNode(
      geoId: gid,
      code: json['code']?.toString() ?? '',
      name: name,
      matchNames: mn,
      cities: list,
    );
  }
}

/// جلب شجرة المحافظات / المدن / الأحياء (سوريا وتركيا) من الـ API.
class RegionService {
  RegionService._();

  static final Map<String, List<RegionStateNode>> _mem = {};

  static String _effectiveLocaleForCountry(String country) {
    final c = country.toUpperCase();
    // نفس منطق الويب: عند اختيار تركيا نعرض قوائم الموقع بالتركي حتى لو لغة التطبيق عربية.
    if (c == 'TR') return 'tr';
    return AppLocale.current;
  }

  static String _cacheKey(String country, String locale) =>
      '${country.toUpperCase()}_${locale.toLowerCase()}';

  /// لا نخزّن قوائم فارغة حتى لا يبقى التطبيق بدون محافظات بعد فشل مؤقت.
  static Future<List<RegionStateNode>> fetchStates(
    String country, {
    bool forceRefresh = false,
  }) async {
    final c = country.toUpperCase();
    if (c != 'SY' && c != 'TR') return [];
    final effectiveLocale = _effectiveLocaleForCountry(c);
    final memKey = _cacheKey(c, effectiveLocale);
    if (forceRefresh) {
      _mem.remove(memKey);
    } else if (_mem.containsKey(memKey)) {
      return _mem[memKey]!;
    }

    List<RegionStateNode> parseStatesMap(Map data) {
      final inner = data['data'];
      if (inner is! Map) return [];
      final states = inner['states'];
      if (states is! List) return [];
      final out = <RegionStateNode>[];
      for (final e in states) {
        final m = _jsonObject(e);
        if (m.isNotEmpty) out.add(RegionStateNode.fromJson(m, locale: effectiveLocale));
      }
      return out;
    }

    // ملاحظة: على الإنتاج وخصوصاً Flutter Web، endpoint `/geo-tree/{TR|SY}` قد يكون ثقيلاً/بطيئاً
    // أو يعلّق بسبب إعدادات الخادم. endpoint `/regions/{TR|SY}` أخف وأكفأ لملء القوائم، لذلك
    // نجعله المسار الأساسي، ونستخدم geo-tree كخيار ثانٍ (مع مهلة قصيرة) عند الحاجة.
    try {
      final res = await ApiClient.dio.get<dynamic>(
        '${ApiConstants.regions}/$c',
        options: Options(extra: {'locale': effectiveLocale}),
      );
      final data = res.data;
      if (data is! Map) return [];
      if (data['success'] == false) return [];
      final out = parseStatesMap(Map<String, dynamic>.from(data));
      if (out.isNotEmpty) {
        _mem[memKey] = out;
      }
      return out;
    } catch (_) {
      // fallback: حاول geo-tree لكن لا تنتظر طويلاً حتى لا تتعطل واجهة المستخدم
      try {
        final resTree = await ApiClient.dio.get<dynamic>(
          ApiConstants.geoTree(c),
          options: Options(
            extra: {'locale': effectiveLocale},
            // مهلة أقصر من الافتراضي حتى لا يعلق التحميل عند تعطل endpoint geo-tree.
            connectTimeout: const Duration(seconds: 8),
            receiveTimeout: const Duration(seconds: 12),
          ),
        );
        final dataTree = resTree.data;
        if (dataTree is Map && dataTree['success'] != false) {
          final fromTree = parseStatesMap(Map<String, dynamic>.from(dataTree));
          if (fromTree.isNotEmpty) {
            _mem[memKey] = fromTree;
            return fromTree;
          }
        }
      } catch (_) {}
      return [];
    }
  }

  static void clearCache() {
    _mem.clear();
  }

  static void invalidateCountry(String country) {
    final prefix = '${country.toUpperCase()}_';
    _mem.removeWhere((k, _) => k.startsWith(prefix));
  }

  /// يطابق الخادم نتيجة الجيوكودينج مع الكتالوج أو يُنشئ مدينة/محافظة ديناميكية.
  static Future<Map<String, dynamic>?> discoverMapSelection({
    required String country,
    required double lat,
    required double lng,
    Map<String, String?>? primary,
    List<String>? needles,
  }) async {
    final c = country.toUpperCase();
    if (c != 'SY' && c != 'TR') return null;
    final effectiveLocale = _effectiveLocaleForCountry(c);
    try {
      final primaryMap = <String, String>{};
      if (primary != null) {
        for (final e in primary.entries) {
          final v = e.value?.trim();
          if (v != null && v.isNotEmpty) {
            primaryMap[e.key] = v;
          }
        }
      }
      final res = await ApiClient.dio.post<dynamic>(
        ApiConstants.regionsDiscoverMap,
        data: <String, dynamic>{
          'country': c,
          'latitude': lat,
          'longitude': lng,
          if (primaryMap.isNotEmpty) 'primary': primaryMap,
          'needles': needles ?? const <String>[],
        },
        options: Options(extra: {'locale': effectiveLocale}),
      );
      final data = res.data;
      if (data is! Map) return null;
      if (data['success'] != true) return null;
      final inner = data['data'];
      if (inner is! Map) return null;
      return Map<String, dynamic>.from(inner);
    } catch (_) {
      return null;
    }
  }

  /// إحداثيات المنطقة حسب الأكواد المختارة من القوائم (محافظة/مدينة/حي).
  /// يعيد null إذا لم تُوجد إحداثيات في geo_divisions.
  static Future<({double lat, double lng})?> coordsForCodes({
    required String country,
    String? stateCode,
    String? cityCode,
    String? districtCode,
  }) async {
    final c = country.toUpperCase();
    if (c != 'SY' && c != 'TR') return null;
    final effectiveLocale = _effectiveLocaleForCountry(c);
    try {
      final res = await ApiClient.dio.get<dynamic>(
        ApiConstants.geoCoords,
        queryParameters: <String, dynamic>{
          'country': c,
          if (stateCode != null && stateCode.trim().isNotEmpty) 'state_code': stateCode.trim(),
          if (cityCode != null && cityCode.trim().isNotEmpty) 'city_code': cityCode.trim(),
          if (districtCode != null && districtCode.trim().isNotEmpty) 'district_code': districtCode.trim(),
        },
        options: Options(extra: {'locale': effectiveLocale}),
      );
      final data = res.data;
      if (data is! Map) return null;
      if (data['success'] != true) return null;
      final inner = data['data'];
      if (inner is! Map) return null;
      final latRaw = inner['latitude'];
      final lngRaw = inner['longitude'];
      final lat = (latRaw is num) ? latRaw.toDouble() : double.tryParse(latRaw?.toString() ?? '');
      final lng = (lngRaw is num) ? lngRaw.toDouble() : double.tryParse(lngRaw?.toString() ?? '');
      if (lat == null || lng == null) return null;
      if (!lat.isFinite || !lng.isFinite) return null;
      return (lat: lat, lng: lng);
    } catch (_) {
      return null;
    }
  }
}
