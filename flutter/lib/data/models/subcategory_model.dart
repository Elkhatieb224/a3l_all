import 'package:a3lnha/helpers/localized_name.dart';

class SubcategoryModel {
  final int id;
  final int categoryId;
  final int? parentSubcategoryId;
  final String name;
  final String? nameAr;
  final String? nameEn;
  final String? nameTr;
  final String? icon;
  final String? image;
  final bool isActive;
  final int adsCount;
  final List<SubcategoryModel>? children;
  final List<Map<String, dynamic>>? customFields;
  /// حقول فعّالة بعد الوراثة (من API: resolved_custom_fields).
  final List<Map<String, dynamic>>? resolvedCustomFields;
  /// null = وراثة من القسم الرئيسي
  final String? adImagesMode;
  final int? adImagesMax;
  final List<String>? adGalleryPaths;
  final List<String>? adGalleryUrls;

  SubcategoryModel({
    required this.id,
    required this.categoryId,
    this.parentSubcategoryId,
    required this.name,
    this.nameAr,
    this.nameEn,
    this.nameTr,
    this.icon,
    this.image,
    this.isActive = true,
    this.adsCount = 0,
    this.children,
    this.customFields,
    this.resolvedCustomFields,
    this.adImagesMode,
    this.adImagesMax,
    this.adGalleryPaths,
    this.adGalleryUrls,
  });

  static List<String>? _stringList(dynamic v) {
    if (v is! List) return null;
    final out = v.map((e) => e.toString()).where((s) => s.isNotEmpty).toList();
    return out.isEmpty ? null : out;
  }

  static int _toInt(dynamic v, {int fallback = 0}) {
    if (v == null) return fallback;
    if (v is int) return v;
    if (v is num) return v.toInt();
    if (v is String) return int.tryParse(v) ?? fallback;
    return fallback;
  }

  static int _toAdsCount(Map<String, dynamic> json) {
    final v = json['ads_count'] ?? json['matching_ads_count'];
    return _toInt(v);
  }

  static List<Map<String, dynamic>>? _customFieldsList(dynamic v) {
    if (v == null) return null;
    if (v is! List) return null;
    final out = <Map<String, dynamic>>[];
    for (final e in v) {
      if (e is Map<String, dynamic>) {
        out.add(e);
      } else if (e is Map) {
        out.add(Map<String, dynamic>.from(e));
      }
    }
    return out.isEmpty ? null : out;
  }

  factory SubcategoryModel.fromJson(Map<String, dynamic> json) {
    List<SubcategoryModel>? childList;
    final rawChildren = json['children'];
    if (rawChildren is List && rawChildren.isNotEmpty) {
      childList = [];
      for (final e in rawChildren) {
        if (e is Map<String, dynamic>) {
          childList.add(SubcategoryModel.fromJson(e));
        } else if (e is Map) {
          childList.add(SubcategoryModel.fromJson(Map<String, dynamic>.from(e)));
        }
      }
    }

    return SubcategoryModel(
      id: _toInt(json['id']),
      categoryId: _toInt(json['category_id']),
      parentSubcategoryId: json['parent_subcategory_id'] != null
          ? _toInt(json['parent_subcategory_id'])
          : null,
      name: json['name']?.toString() ?? json['name_ar']?.toString() ?? '',
      nameAr: json['name_ar']?.toString(),
      nameEn: json['name_en']?.toString(),
      nameTr: json['name_tr']?.toString(),
      icon: json['icon']?.toString(),
      image: json['image']?.toString(),
      isActive: json['is_active'] is bool
          ? json['is_active'] as bool
          : (json['is_active'] == null ||
              json['is_active'].toString() == '1' ||
              json['is_active'].toString().toLowerCase() == 'true'),
      adsCount: _toAdsCount(json),
      children: childList,
      customFields: _customFieldsList(json['custom_fields']),
      resolvedCustomFields: _customFieldsList(json['resolved_custom_fields']),
      adImagesMode: json['ad_images_mode'] as String?,
      adImagesMax: _toInt(json['ad_images_max'], fallback: 0) > 0 ? _toInt(json['ad_images_max']) : null,
      adGalleryPaths: _stringList(json['ad_gallery_paths']),
      adGalleryUrls: _stringList(json['ad_gallery_urls']),
    );
  }

  /// الاسم حسب اللغة الحالية
  String get displayName => getLocalizedName(
        nameAr: nameAr,
        nameEn: nameEn,
        nameTr: nameTr,
        defaultName: name,
      );
}
