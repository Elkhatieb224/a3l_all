/// صفة المعلن (seller_type): غير الموثق يُثبَّت على «مالك».
class SellerTypeField {
  SellerTypeField._();

  static const fieldId = 'seller_type';
  static const ownerAr = 'مالك';

  static bool isField(Map<String, dynamic> field) =>
      (field['id'] ?? '').toString() == fieldId;

  static Map<String, dynamic>? findOwnerOption(Map<String, dynamic> field) {
    final options = field['options'];
    if (options is! List) return null;
    for (final opt in options) {
      if (opt is Map) {
        final map = Map<String, dynamic>.from(opt);
        if ((map['ar'] ?? '').toString() == ownerAr) return map;
      } else if (opt?.toString() == ownerAr) {
        return {'ar': ownerAr, 'en': ownerAr, 'tr': ownerAr};
      }
    }
    return null;
  }

  /// قيمة التخزين كما في قوائم الاختيار (يفضّل ar للتوافق مع التطبيق).
  static String ownerStoredValue(
    Map<String, dynamic> field, {
    String Function(Map<String, dynamic> opt)? valueOf,
  }) {
    final owner = findOwnerOption(field);
    if (owner == null) return ownerAr;
    if (valueOf != null) {
      final v = valueOf(owner);
      if (v.isNotEmpty) return v;
    }
    return (owner['ar'] ?? owner['en'] ?? owner['tr'] ?? ownerAr).toString();
  }
}
