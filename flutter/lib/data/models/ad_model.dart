import 'dart:convert';

import 'package:a3lnha/core/data/currency_helper.dart';
import 'package:a3lnha/core/data/location_translations.dart';
import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/helpers/numeral_helper.dart';
import 'package:a3lnha/core/network/api_constants.dart';
import 'package:a3lnha/data/models/ad_images_effective.dart';

/// عنصر واحد في مسار التصنيف (للتنقل إلى قائمة الإعلانات).
class CategoryPathSegment {
  final String type;
  final int id;
  final String slug;
  final String name;

  const CategoryPathSegment({
    required this.type,
    required this.id,
    required this.slug,
    required this.name,
  });

  bool get isCategory {
    final t = type.trim().toLowerCase();
    return t == 'category';
  }

  /// للتنقل: معرّف صالح فقط (المستويات الوسيطة بدون معرّف من الـ API تبقى نصاً عادياً)
  bool get isTappable => id > 0;

  factory CategoryPathSegment.fromJson(Map<String, dynamic> json) {
    final rawId = json['id'];
    final id = rawId is int
        ? rawId
        : rawId is num
            ? rawId.toInt()
            : int.tryParse(rawId.toString()) ?? 0;
    return CategoryPathSegment(
      type: json['type']?.toString() ?? '',
      id: id,
      slug: json['slug']?.toString() ?? '',
      name: json['name']?.toString() ?? '',
    );
  }
}

class AdModel {
  final int id;
  final String uid;
  final String title;
  final String? description;
  final num? price;
  final String? currency;
  final String? formattedPrice;
  final List<String> images;
  /// عنوان بث الفيديو الكامل إن وُجد (من الـ API).
  final String? videoUrl;
  final bool isFeatured;
  final bool isUrgent;
  final int viewsCount;
  final int messagesCount;
  final int favoritesCount;
  final String? status;
  final String? locationState;
  final String? locationCity;
  final String? locationDistrict;
  final String? locationAddress;
  final String? locationCountry;
  final bool showLocation;
  final double? latitude;
  final double? longitude;
  final String? publishedAt;
  final Map<String, dynamic>? customFields;
  final Map<String, dynamic>? category;
  final Map<String, dynamic>? subcategory;
  /// تسلسل الفئة الكامل من الفئة الرئيسية حتى الفئة الفرعية الأخيرة، مثال: [المركبات، هوندا، سيتي]
  final List<String>? categoryPath;
  /// مسار التصنيف مع المعرّفات (روابط إلى قوائم الإعلانات)
  final List<CategoryPathSegment>? categoryPathSegments;
  final Map<String, dynamic>? user;
  final bool isFavorite;
  final bool isOwner;
  final bool canNegotiatePrice;
  /// ISO8601؛ يظهر لصاحب الإعلان فقط من الـ API عند وجود تمييز نشط.
  final String? featuredUntil;
  final String? urgentUntil;

  AdModel({
    required this.id,
    required this.uid,
    required this.title,
    this.description,
    this.price,
    this.currency,
    this.formattedPrice,
    this.images = const [],
    this.videoUrl,
    this.isFeatured = false,
    this.isUrgent = false,
    this.viewsCount = 0,
    this.messagesCount = 0,
    this.favoritesCount = 0,
    this.status,
    this.locationState,
    this.locationCity,
    this.locationDistrict,
    this.locationAddress,
    this.locationCountry,
    this.showLocation = true,
    this.latitude,
    this.longitude,
    this.publishedAt,
    this.customFields,
    this.category,
    this.subcategory,
    this.categoryPath,
    this.categoryPathSegments,
    this.user,
    this.isFavorite = false,
    this.isOwner = false,
    this.canNegotiatePrice = true,
    this.featuredUntil,
    this.urgentUntil,
  });

  static Map<String, dynamic>? _toMap(dynamic v) {
    if (v == null) return null;
    if (v is Map<String, dynamic>) return v;
    if (v is Map) return Map<String, dynamic>.from(v);
    return null;
  }

  static int _toInt(dynamic v, {int fallback = 0}) {
    if (v is int) return v;
    if (v is num) return v.toInt();
    if (v is String) return int.tryParse(v) ?? fallback;
    return fallback;
  }

  static double? _toDoubleNullable(dynamic v) {
    if (v == null) return null;
    if (v is double) return v.isFinite ? v : null;
    if (v is int) return v.toDouble();
    if (v is num) {
      final d = v.toDouble();
      return d.isFinite ? d : null;
    }
    if (v is String) {
      final s = v.trim().replaceAll(',', '.');
      if (s.isEmpty) return null;
      return double.tryParse(s);
    }
    return null;
  }

  /// غلاف حقول النموذج: غالباً `{ "value": { "address", "lat", ... } }`.
  static Map<String, dynamic>? _unwrapCustomFieldPayload(dynamic v) {
    if (v is! Map) return null;
    var m = Map<String, dynamic>.from(v);
    final wrapped = m['value'];
    if (wrapped is Map) {
      m = Map<String, dynamic>.from(wrapped);
    }
    return m;
  }

  /// إحداثيات من حقل موقع داخل `custom_fields`.
  static (double?, double?) _latLngFromCustomFields(Map<String, dynamic>? cf) {
    if (cf == null || cf.isEmpty) return (null, null);
    for (final v in cf.values) {
      final m = _unwrapCustomFieldPayload(v);
      if (m == null) continue;
      final hasLocationShape =
          m.containsKey('address') ||
          m.containsKey('formatted_address') ||
          m.containsKey('display_name') ||
          m.containsKey('country') ||
          m.containsKey('city') ||
          m.containsKey('district') ||
          m.containsKey('location_country') ||
          m.containsKey('location_city') ||
          m.containsKey('location_district');
      if (!hasLocationShape) continue;
      final la = _toDoubleNullable(m['latitude'] ?? m['lat']);
      final ln = _toDoubleNullable(m['longitude'] ?? m['lng']);
      if (la != null && ln != null) return (la, ln);
    }
    return (null, null);
  }

  static bool _isFiniteGeo(double lat, double lng) {
    return lat.isFinite &&
        lng.isFinite &&
        lat >= -90 &&
        lat <= 90 &&
        lng >= -180 &&
        lng <= 180;
  }

  static bool _inSyriaBounds(double lat, double lng) {
    // حدود تقريبية واسعة لتجنب رفض النقاط القريبة من الحدود.
    return lat >= 32.0 && lat <= 38.5 && lng >= 35.0 && lng <= 43.0;
  }

  static bool _inTurkeyBounds(double lat, double lng) {
    // حدود تقريبية واسعة تشمل كامل تركيا والجزر القريبة.
    return lat >= 35.0 && lat <= 42.8 && lng >= 25.0 && lng <= 45.5;
  }

  static bool _fitsCountryBounds(String? country, double lat, double lng) {
    final c = (country ?? '').trim().toLowerCase();
    if (c.isEmpty) return true;
    final isSy = c == 'sy' || c == 'syria' || c == 'سوريا';
    if (isSy) return _inSyriaBounds(lat, lng);
    final isTr = c == 'tr' || c == 'turkey' || c == 'تركيا';
    if (isTr) return _inTurkeyBounds(lat, lng);
    return true;
  }

  static ({double lat, double lng})? _normalizeMapPoint(
    double? lat,
    double? lng,
    String? country,
  ) {
    if (lat == null || lng == null) return null;
    if (!_isFiniteGeo(lat, lng)) return null;
    if (_fitsCountryBounds(country, lat, lng)) {
      return (lat: lat, lng: lng);
    }
    // بعض المصادر قد ترسل lat/lng معكوسين.
    if (_isFiniteGeo(lng, lat) && _fitsCountryBounds(country, lng, lat)) {
      return (lat: lng, lng: lat);
    }
    // إن لم يوجد تطابق دولة، لا نجبر الإسقاط ونحافظ على القيمة الأصلية.
    return (lat: lat, lng: lng);
  }

  /// نص موقع من الحقول المخصصة فقط (عنوان / دولة-مدينة / تسمية) — أولوية لبطاقات القوائم.
  static String? _locationLineFromCustomFieldsOnly(
    Map<String, dynamic> cf,
    String locale,
  ) {
    for (final v in cf.values) {
      final m = _unwrapCustomFieldPayload(v);
      if (m == null) continue;
      final addr = _nullableStr(m['address']);
      if (addr != null) return addr;
      final fromRegions = LocationTranslations.formatCountryProvinceDistrict(
        locale,
        country: _nullableStr(m['country'] ?? m['location_country']),
        city: _nullableStr(m['city'] ?? m['location_city'] ?? m['province']),
        district:
            _nullableStr(m['district'] ?? m['location_district'] ?? m['area']),
      );
      if (fromRegions.isNotEmpty) return fromRegions;
      final label = _nullableStr(
        m['label'] ??
            m['formatted_address'] ??
            m['display_name'] ??
            m['place_name'],
      );
      if (label != null) return label;
    }
    return null;
  }

  static String? _nullableStr(dynamic v) {
    if (v == null) return null;
    final s = v.toString().trim();
    return s.isEmpty ? null : s;
  }

  static bool _toBool(dynamic v, {bool fallback = false}) {
    if (v is bool) return v;
    if (v is num) return v != 0;
    if (v is String) {
      final s = v.trim().toLowerCase();
      if (s == '1' || s == 'true' || s == 'yes') return true;
      if (s == '0' || s == 'false' || s == 'no') return false;
    }
    return fallback;
  }

  static List<String>? _toStringList(dynamic v) {
    if (v == null) return null;
    if (v is! List) return null;
    final list = <String>[];
    for (final e in v) {
      if (e != null && e.toString().trim().isNotEmpty) list.add(e.toString().trim());
    }
    return list.isEmpty ? null : list;
  }

  static List<CategoryPathSegment>? _parseCategoryPathSegments(dynamic v) {
    if (v == null) return null;
    if (v is String) {
      final t = v.trim();
      if (t.isEmpty) return null;
      try {
        final d = jsonDecode(t);
        return _parseCategoryPathSegments(d);
      } catch (_) {
        return null;
      }
    }
    if (v is! List) return null;
    final out = <CategoryPathSegment>[];
    for (final e in v) {
      if (e is Map<String, dynamic>) {
        final seg = CategoryPathSegment.fromJson(e);
        if (seg.id > 0 && seg.name.trim().isNotEmpty) out.add(seg);
      } else if (e is Map) {
        final seg = CategoryPathSegment.fromJson(Map<String, dynamic>.from(e));
        if (seg.id > 0 && seg.name.trim().isNotEmpty) out.add(seg);
      }
    }
    return out.isEmpty ? null : out;
  }

  static String get _apiOrigin {
    final uri = Uri.tryParse(ApiConstants.baseUrl);
    if (uri == null) return '';
    if (uri.scheme.isEmpty || uri.host.isEmpty) return '';
    final port =
        uri.hasPort && uri.port != 80 && uri.port != 443 ? ':${uri.port}' : '';
    return '${uri.scheme}://${uri.host}$port';
  }

  static String? _normalizeImageUrl(String? raw) {
    if (raw == null) return null;
    final value = raw.trim();
    if (value.isEmpty) return null;
    late final String built;
    if (value.startsWith('http://') || value.startsWith('https://')) {
      built = value;
    } else {
      final origin = _apiOrigin;
      if (origin.isEmpty) {
        return AdImagesEffective.resolveGalleryImageUrl(value);
      }
      if (value.startsWith('/')) {
        built = '$origin$value';
      } else if (value.startsWith('storage/')) {
        built = '$origin/$value';
      } else if (value.startsWith('ads/')) {
        built = '$origin/storage/$value';
      } else {
        built = '$origin/storage/$value';
      }
    }
    return AdImagesEffective.resolveGalleryImageUrl(built);
  }

  static List<String> _extractImages(dynamic raw) {
    final out = <String>[];

    void addCandidate(dynamic candidate) {
      String? val;
      if (candidate is String) {
        val = candidate;
      } else if (candidate is Map) {
        final map = Map<String, dynamic>.from(candidate);
        val = map['url']?.toString() ??
            map['path']?.toString() ??
            map['image']?.toString() ??
            map['src']?.toString() ??
            map['original_url']?.toString();
      }
      final normalized = _normalizeImageUrl(val);
      if (normalized != null && normalized.isNotEmpty) {
        out.add(normalized);
      }
    }

    if (raw is List) {
      for (final item in raw) {
        addCandidate(item);
      }
      return out;
    }

    if (raw is String) {
      final trimmed = raw.trim();
      if (trimmed.startsWith('[') || trimmed.startsWith('{')) {
        try {
          final decoded = jsonDecode(trimmed);
          return _extractImages(decoded);
        } catch (_) {}
      }
      addCandidate(trimmed);
      return out;
    }

    addCandidate(raw);
    return out;
  }

  factory AdModel.fromJson(Map<String, dynamic> json) {
    List<String> imgList = _extractImages(json['images']);

    if (imgList.isEmpty) {
      // بعض نقاط الـ API (قوائم الإعلانات) ترجع thumbnail فقط.
      final thumb = _normalizeImageUrl(json['thumbnail']?.toString());
      if (thumb != null && thumb.isNotEmpty) {
        imgList = [thumb];
      }
    }

    if (imgList.isEmpty) {
      final first = _normalizeImageUrl(
        json['first_image']?.toString() ?? json['image']?.toString(),
      );
      if (first != null && first.isNotEmpty) imgList = [first];
    }

    final rawVideo = json['video']?.toString();
    final videoUrl = _normalizeImageUrl(rawVideo);

    num? price;
    if (json['price'] != null) {
      final p = json['price'];
      if (p is num) {
        price = p;
      } else if (p is String) {
        price = num.tryParse(p);
      }
    }

    final cfMap = _toMap(json['custom_fields']);
    var lat = _toDoubleNullable(json['latitude']);
    var lng = _toDoubleNullable(json['longitude']);
    if (lat == null || lng == null) {
      final extra = _latLngFromCustomFields(cfMap);
      lat ??= extra.$1;
      lng ??= extra.$2;
    }

    return AdModel(
      id: _toInt(json['id']),
      uid: json['uid']?.toString() ?? '',
      title: json['title']?.toString() ?? '',
      description: json['description'] as String?,
      price: price,
      currency: json['currency'] as String?,
      formattedPrice: json['formatted_price'] as String?,
      images: imgList,
      videoUrl: videoUrl,
      isFeatured: _toBool(json['is_featured']),
      isUrgent: _toBool(json['is_urgent']),
      viewsCount: _toInt(json['views_count']),
      messagesCount: _toInt(json['messages_count']),
      favoritesCount: _toInt(json['favorites_count']),
      status: json['status'] as String?,
      locationState: _nullableStr(json['location_state']),
      locationCity: _nullableStr(json['location_city']) ?? _nullableStr(json['location_short']),
      locationDistrict: _nullableStr(json['location_district']),
      locationAddress: _nullableStr(json['location_address']),
      locationCountry: _nullableStr(json['location_country']),
      showLocation: _toBool(json['show_location'], fallback: true),
      latitude: lat,
      longitude: lng,
      publishedAt: json['published_at'] as String?,
      customFields: cfMap,
      category: _toMap(json['category']),
      subcategory: _toMap(json['subcategory']),
      categoryPath: _toStringList(json['category_path']),
      categoryPathSegments: _parseCategoryPathSegments(
        json['category_path_segments'] ?? json['categoryPathSegments'],
      ),
      user: _toMap(json['user']),
      isFavorite: _toBool(json['is_favorite']),
      isOwner: _toBool(json['is_owner']),
      canNegotiatePrice: _toBool(json['can_negotiate_price'], fallback: true),
      featuredUntil: _nullableStr(json['featured_until']),
      urgentUntil: _nullableStr(json['urgent_until']),
    );
  }

  String? get imageUrl {
    for (final img in images) {
      final normalized = _normalizeImageUrl(img);
      if (normalized != null && normalized.isNotEmpty) return normalized;
    }
    return null;
  }

  /// هل يوجد ما يُعرض في سطر الموقع للقوائم (بخلاف «—»).
  bool get hasLocationForList {
    if (effectiveMapPosition != null) return true;
    return staticLocationDisplayLine.isNotEmpty;
  }

  /// الحقول المخصّصة (حقل الخريطة/العنوان) أولاً، ثم أعمدة الجدول — كما يُخزَّن الموقع في النماذج.
  String get staticLocationDisplayLine {
    final locale = AppLocale.current;
    if (customFields != null && customFields!.isNotEmpty) {
      final fromCf = _locationLineFromCustomFieldsOnly(customFields!, locale);
      if (fromCf != null && fromCf.isNotEmpty) {
        return fromCf;
      }
    }

    if (!showLocation) {
      return '';
    }
    final addr = locationAddress?.trim();
    if (addr != null && addr.isNotEmpty) {
      return addr;
    }
    final parts = <String>[];
    void addSeg(String? raw) {
      if (raw == null) return;
      final t = raw.trim();
      if (t.isEmpty) return;
      final seg = LocationTranslations.segmentForUi(locale, t);
      if (seg.isNotEmpty) parts.add(seg);
    }

    addSeg(locationCountry);
    addSeg(locationState);
    addSeg(locationCity);
    addSeg(locationDistrict);
    var line = parts.join(' - ');
    return line;
  }

  /// مسار الفئات للعرض والتنقّل: من الـ API أو مُستنتج من [categoryPath] + معرّفات القسم/الفرعي.
  List<CategoryPathSegment> get resolvedCategoryPathSegments {
    if (categoryPathSegments != null && categoryPathSegments!.isNotEmpty) {
      return categoryPathSegments!;
    }
    return _buildFallbackCategoryPathSegments();
  }

  List<CategoryPathSegment> _buildFallbackCategoryPathSegments() {
    final path = categoryPath;
    if (path == null || path.isEmpty) return [];

    final catId = category != null ? _toInt(category!['id']) : 0;
    final catSlug = category?['slug']?.toString() ?? '';
    final subId = subcategory != null ? _toInt(subcategory!['id']) : 0;
    final subSlug = subcategory?['slug']?.toString() ?? '';

    final out = <CategoryPathSegment>[];
    for (int i = 0; i < path.length; i++) {
      final name = path[i].trim();
      if (name.isEmpty) continue;

      if (path.length == 1) {
        if (catId > 0) {
          out.add(CategoryPathSegment(
            type: 'category',
            id: catId,
            slug: catSlug,
            name: name,
          ));
        } else if (subId > 0) {
          out.add(CategoryPathSegment(
            type: 'subcategory',
            id: subId,
            slug: subSlug,
            name: name,
          ));
        } else {
          out.add(CategoryPathSegment(type: 'subcategory', id: 0, slug: '', name: name));
        }
        continue;
      }

      if (i == 0 && catId > 0) {
        out.add(CategoryPathSegment(
          type: 'category',
          id: catId,
          slug: catSlug,
          name: name,
        ));
      } else if (i == path.length - 1 && subId > 0) {
        out.add(CategoryPathSegment(
          type: 'subcategory',
          id: subId,
          slug: subSlug,
          name: name,
        ));
      } else {
        out.add(CategoryPathSegment(type: 'subcategory', id: 0, slug: '', name: name));
      }
    }
    return out;
  }

  /// إحداثيات للخرائط: أعمدة الإعلان أو من حقل موقع في `custom_fields`.
  ({double lat, double lng})? get effectiveMapPosition {
    final fromColumns = _normalizeMapPoint(latitude, longitude, locationCountry);
    if (fromColumns != null) return fromColumns;
    final extra = _latLngFromCustomFields(customFields);
    return _normalizeMapPoint(extra.$1, extra.$2, locationCountry);
  }

  /// سعر للعرض فقط: بدون `.00` عند عدم وجود جزء عشري، مع محاذاة تنسيق التطبيق مع الـ API.
  String? get displayPriceForUi {
    if (price != null &&
        currency != null &&
        currency.toString().trim().isNotEmpty) {
      return CurrencyHelper.formatPrice(price, currency);
    }
    final f = formattedPrice;
    if (f == null || f.trim().isEmpty) return null;
    return CurrencyHelper.stripTrailingCentsZeros(f);
  }

  /// راتب من `custom_fields.salary` (إعلانات وظائف)، بنفس تنسيق السعر.
  String? get displaySalaryForUi {
    final cf = customFields;
    if (cf == null || cf.isEmpty) return null;
    final out = _formatSalaryFromCustomField(cf['salary'], currency);
    if (out == null) return null;
    final t = out.trim();
    return t.isEmpty ? null : t;
  }

  /// للخرائط والبطاقات: يُفضَّل عرض الراتب عند وجوده بدل تكرار السعر.
  String? get displayPriceOrSalaryForUi {
    final s = displaySalaryForUi;
    if (s != null && s.isNotEmpty) return s;
    return displayPriceForUi;
  }
}

String? _formatSalaryFromCustomField(dynamic val, String? fallbackCurrency) {
  if (val == null) return null;
  final fb = fallbackCurrency?.trim();

  if (val is Map) {
    final m = Map<String, dynamic>.from(val);
    if (m['tbd'] == true || m['tbd'] == '1') {
      return AppLocale.tr('price_tbd');
    }
    final numVal = m['value'];
    final currency = (m['currency'] as String?)?.trim();
    final emptyVal = numVal == null || numVal.toString().trim().isEmpty;
    if (emptyVal) {
      if (currency != null && currency.isNotEmpty) {
        return AppLocale.tr('price_tbd');
      }
      return null;
    }
    final n = numVal is num ? numVal : NumeralHelper.parseAmount(numVal.toString());
    final cur = (currency != null && currency.isNotEmpty)
        ? currency
        : (fb != null && fb.isNotEmpty ? fb : null);
    if (n != null && cur != null && cur.isNotEmpty) {
      return CurrencyHelper.formatPrice(n, cur);
    }
  } else if (val is num) {
    if (fb != null && fb.isNotEmpty) {
      return CurrencyHelper.formatPrice(val, fb);
    }
  } else {
    final s = val.toString().trim();
    if (s.isEmpty) return null;
    final n = NumeralHelper.parseAmount(s);
    if (n != null && fb != null && fb.isNotEmpty) {
      return CurrencyHelper.formatPrice(n, fb);
    }
    return s;
  }
  return null;
}
