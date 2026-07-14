import 'package:a3lnha/data/models/category_model.dart';
import 'package:a3lnha/data/models/subcategory_model.dart';

/// يطابق [App\Support\CustomFieldsResolver] في Laravel:
/// من الورقة نصعد في السلسلة — أول عقدة لها حقول = المصدر؛ وإلا حقول القسم.
class CustomFieldsResolver {
  CustomFieldsResolver._();

  static bool hasDefinedFields(List<Map<String, dynamic>>? fields) {
    if (fields == null || fields.isEmpty) return false;
    return fields.any((f) => (f['id'] ?? '').toString().isNotEmpty);
  }

  static List<Map<String, dynamic>> onlyActive(List<Map<String, dynamic>> fields) {
    return fields.where((f) => f['is_active'] != false).toList();
  }

  /// من API: `resolved_custom_fields` إن وُجدت.
  static List<Map<String, dynamic>> fromSubcategoryJson(Map<String, dynamic> json) {
    final resolved = json['resolved_custom_fields'];
    if (resolved is List && resolved.isNotEmpty) {
      return _normalizeList(resolved);
    }
    return resolveFromOwnAndParents(
      ownFields: _normalizeList(json['custom_fields']),
      parentSubcategoryId: json['parent_subcategory_id'] != null
          ? int.tryParse(json['parent_subcategory_id'].toString())
          : null,
      lookupSubcategory: null,
      categoryFields: null,
    );
  }

  /// عند اختيار ورقة أثناء إنشاء إعلان (مع خريطة الأقسام الفرعية المحمّلة).
  static List<Map<String, dynamic>> resolveForLeaf({
    required SubcategoryModel leaf,
    required Map<int, SubcategoryModel> subcategoryById,
    CategoryModel? category,
  }) {
    final chain = <SubcategoryModel>[leaf];
    var parentId = leaf.parentSubcategoryId;
    final visited = <int>{leaf.id};
    while (parentId != null && !visited.contains(parentId)) {
      final parent = subcategoryById[parentId];
      if (parent == null) break;
      chain.add(parent);
      visited.add(parentId);
      parentId = parent.parentSubcategoryId;
    }

    for (final node in chain) {
      if (hasDefinedFields(node.customFields)) {
        return onlyActive(node.customFields!);
      }
    }

    if (category != null && hasDefinedFields(category.customFields)) {
      return onlyActive(category.customFields!);
    }

    return [];
  }

  static List<Map<String, dynamic>> resolveFromOwnAndParents({
    required List<Map<String, dynamic>> ownFields,
    required int? parentSubcategoryId,
    required SubcategoryModel? Function(int id)? lookupSubcategory,
    required List<Map<String, dynamic>>? categoryFields,
  }) {
    if (hasDefinedFields(ownFields)) {
      return onlyActive(ownFields);
    }

    var parentId = parentSubcategoryId;
    final visited = <int>{};
    while (parentId != null && !visited.contains(parentId)) {
      visited.add(parentId);
      final parent = lookupSubcategory?.call(parentId);
      if (parent == null) break;
      if (hasDefinedFields(parent.customFields)) {
        return onlyActive(parent.customFields!);
      }
      parentId = parent.parentSubcategoryId;
    }

    if (categoryFields != null && hasDefinedFields(categoryFields)) {
      return onlyActive(categoryFields);
    }

    return [];
  }

  static List<Map<String, dynamic>> _normalizeList(dynamic v) {
    if (v is! List) return [];
    final out = <Map<String, dynamic>>[];
    for (final e in v) {
      if (e is Map<String, dynamic>) {
        out.add(e);
      } else if (e is Map) {
        out.add(Map<String, dynamic>.from(e));
      }
    }
    return out;
  }

  /// Primary price field id (price/salary, else first number with show_currency).
  static String? resolvePrimaryPriceFieldId(List<Map<String, dynamic>> fields) {
    for (final field in fields) {
      if (field['is_active'] == false) continue;
      if (field['type'] != 'number') continue;
      final id = field['id']?.toString() ?? '';
      if (id == 'price' || id == 'salary') return id;
    }
    for (final field in fields) {
      if (field['is_active'] == false) continue;
      if (field['type'] != 'number') continue;
      if (field['show_currency'] == true) {
        final id = field['id']?.toString() ?? '';
        if (id.isNotEmpty) return id;
      }
    }
    return null;
  }

  /// Filterable fields for UI — excludes primary price field.
  static List<Map<String, dynamic>> filterableFields(
    List<Map<String, dynamic>> fields,
  ) {
    final priceId = resolvePrimaryPriceFieldId(fields);
    return fields.where((f) {
      if (f['is_active'] == false) return false;
      final type = f['type']?.toString() ?? 'text';
      if (type != 'number' && type != 'select' && type != 'checkbox' && type != 'date') {
        return false;
      }
      final id = f['id']?.toString() ?? '';
      if (priceId != null && id == priceId) return false;
      return true;
    }).toList();
  }

  /// Label for unified price filter (matches Laravel CustomFieldsFilterSupport).
  static String resolvePriceFilterLabel(
    List<Map<String, dynamic>> fields, {
    String locale = 'ar',
  }) {
    final priceId = resolvePrimaryPriceFieldId(fields);
    if (priceId != null) {
      for (final f in fields) {
        if (f['id']?.toString() == priceId) {
          final label = f['label'];
          if (label is Map) {
            return label[locale]?.toString() ??
                label['ar']?.toString() ??
                label['en']?.toString() ??
                'السعر';
          }
        }
      }
    }
    for (final altId in ['expected_salary', 'expected_pay', 'salary']) {
      for (final f in fields) {
        if (f['id']?.toString() == altId) {
          final label = f['label'];
          if (label is Map) {
            final v = label[locale]?.toString() ??
                label['ar']?.toString() ??
                label['en']?.toString();
            if (v != null && v.isNotEmpty) return v;
          }
        }
      }
    }
    return locale == 'en'
        ? 'Price'
        : locale == 'tr'
            ? 'Fiyat'
            : 'السعر';
  }
}
