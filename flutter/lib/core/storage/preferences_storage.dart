import 'package:a3lnha/helpers/cache_helper.dart';

/// تخزين التفضيلات المحلية للمستخدم
class PreferencesStorage {
  PreferencesStorage._();

  static const String _readReceiptKey = 'read_receipt_enabled';

  /// هل إشعارات القراءة مفعّلة (لمعرفة ما إذا قرأ الطرف الآخر الرسالة)
  static bool get readReceiptEnabled {
    final v = CacheHelper.getData(key: _readReceiptKey);
    return v == true;
  }

  static const String readReceiptStorageKey = _readReceiptKey;
}
