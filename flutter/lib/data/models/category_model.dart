import 'package:a3lnha/data/models/subcategory_model.dart';
import 'package:a3lnha/helpers/localized_name.dart';

class CategoryModel {
  final int id;
  final String name;
  final String? nameAr;
  final String? nameEn;
  final String? nameTr;
  final String? icon;
  final String? image;
  final bool isActive;
  final int subcategoriesCount;
  final int adsCount;
  final List<SubcategoryModel>? subcategories;
  final List<Map<String, dynamic>>? customFields;
  /// إعدادات صور الإعلان من الـ API (`user_upload` | `admin_gallery`)
  final String? adImagesMode;
  final int? adImagesMax;
  final List<String>? adGalleryPaths;
  final List<String>? adGalleryUrls;

  CategoryModel({
    required this.id,
    required this.name,
    this.nameAr,
    this.nameEn,
    this.nameTr,
    this.icon,
    this.image,
    this.isActive = true,
    this.subcategoriesCount = 0,
    this.adsCount = 0,
    this.subcategories,
    this.customFields,
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

  factory CategoryModel.fromJson(Map<String, dynamic> json) {
    List<SubcategoryModel>? subcats;
    if (json['subcategories'] != null) {
      subcats = (json['subcategories'] as List)
          .map((e) => SubcategoryModel.fromJson(e as Map<String, dynamic>))
          .toList();
    }

    return CategoryModel(
      id: json['id'] as int,
      name: json['name'] as String? ?? json['name_ar'] as String? ?? '',
      nameAr: json['name_ar'] as String?,
      nameEn: json['name_en'] as String?,
      nameTr: json['name_tr'] as String?,
      icon: json['icon'] as String?,
      image: json['image'] as String?,
      isActive: json['is_active'] as bool? ?? true,
      subcategoriesCount: json['subcategories_count'] as int? ?? 0,
      adsCount: json['ads_count'] as int? ?? 0,
      subcategories: subcats,
      customFields: (json['custom_fields'] as List?)
          ?.map((e) => e as Map<String, dynamic>)
          .toList(),
      adImagesMode: json['ad_images_mode'] as String?,
      adImagesMax: json['ad_images_max'] is num ? (json['ad_images_max'] as num).toInt() : int.tryParse('${json['ad_images_max'] ?? ''}'),
      adGalleryPaths: _stringList(json['ad_gallery_paths']),
      adGalleryUrls: _stringList(json['ad_gallery_urls']),
    );
  }

  /// هل القسم أو أي من فروعه (فئات فرعية) يحتوي على إعلانات
  bool get hasAnyAds {
    if (adsCount > 0) return true;
    return subcategories?.any((s) => _subHasAds(s)) ?? false;
  }

  static bool _subHasAds(SubcategoryModel s) {
    if (s.adsCount > 0) return true;
    return s.children?.any((c) => _subHasAds(c)) ?? false;
  }

  /// الاسم حسب اللغة الحالية
  String get displayName => getLocalizedName(
        nameAr: nameAr,
        nameEn: nameEn,
        nameTr: nameTr,
        defaultName: name,
      );

  /// رابط الأيقونة من لوحة التحكم (`icon`) أو `image` إن وُجد
  String? get displayIconUrl {
    final u = icon?.trim();
    if (u != null && u.isNotEmpty) return u;
    final img = image?.trim();
    if (img != null && img.isNotEmpty) return img;
    return null;
  }
}
