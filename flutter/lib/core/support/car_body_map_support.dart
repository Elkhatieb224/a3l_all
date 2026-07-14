import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/locale/app_translations.dart';

/// بيانات مخطط حالة هيكل السيارة — مطابق لـ backend CarBodyMapSupport.
class CarBodyMapSupport {
  CarBodyMapSupport._();

  static const statusOriginal = 'original';
  static const statusLocalPaint = 'local_paint';
  static const statusPainted = 'painted';
  static const statusReplaced = 'replaced';

  static const List<String> statuses = [
    statusOriginal,
    statusLocalPaint,
    statusPainted,
    statusReplaced,
  ];

  static const List<String> partIds = [
    'front_bumper',
    'hood',
    'front_left_fender',
    'front_right_fender',
    'left_front_door',
    'right_front_door',
    'roof',
    'left_rear_door',
    'right_rear_door',
    'left_rear_fender',
    'right_rear_fender',
    'trunk',
    'rear_bumper',
  ];

  static const double diagramWidth = 420.41;
  static const double diagramHeight = 543.07;

  static const String diagramAsset = 'assets/images/car_body_map.svg';

  static const Map<String, int> statusColors = {
    statusOriginal: 0xFFE2E8F0,
    statusLocalPaint: 0xFFF97316,
    statusPainted: 0xFF3B82F6,
    statusReplaced: 0xFFEF4444,
  };

  static const int strokeColor = 0xFFB8C4D4;

  static Map<String, CarBodyMapShape> get shapes => _shapes;

  static final Map<String, CarBodyMapShape> _shapes = {
    'front_bumper': CarBodyMapShape(pathData: 'M112 6L308 6L315 28L105 28Z'),
    'hood': CarBodyMapShape(pathData: 'M105 28L315 28L302 102L118 102Z'),
    'front_left_fender': CarBodyMapShape(pathData: 'M26 32L102 28L98 105L22 109Z'),
    'front_right_fender': CarBodyMapShape(pathData: 'M394 32L318 28L322 105L398 109Z'),
    'left_front_door': CarBodyMapShape(pathData: 'M22 109L98 105L94 205L18 209Z'),
    'roof': CarBodyMapShape(pathData: 'M118 102L302 102L298 332L122 332Z'),
    'right_front_door': CarBodyMapShape(pathData: 'M398 109L322 105L326 205L402 209Z'),
    'left_rear_door': CarBodyMapShape(pathData: 'M18 209L94 205L90 305L14 309Z'),
    'right_rear_door': CarBodyMapShape(pathData: 'M402 209L326 205L330 305L406 309Z'),
    'left_rear_fender': CarBodyMapShape(pathData: 'M14 309L90 305L86 405L10 409Z'),
    'trunk': CarBodyMapShape(pathData: 'M122 332L298 332L292 418L128 418Z'),
    'right_rear_fender': CarBodyMapShape(pathData: 'M406 309L330 305L334 405L410 409Z'),
    'rear_bumper': CarBodyMapShape(pathData: 'M128 418L292 418L306 508L114 508Z'),
  };

  static Map<String, String> defaultParts() {
    return {for (final id in partIds) id: statusOriginal};
  }

  static Map<String, dynamic> normalizeValue(dynamic input) {
    final defaults = defaultParts();
    Map<String, dynamic> rawParts = {};
    if (input is Map) {
      final parts = input['parts'];
      if (parts is Map) {
        rawParts = Map<String, dynamic>.from(parts);
      }
    }

    final parts = Map<String, String>.from(defaults);
    for (final id in partIds) {
      final status = rawParts[id]?.toString().trim() ?? defaults[id]!;
      parts[id] = statuses.contains(status) ? status : statusOriginal;
    }

    final allOriginal = parts.values.every((s) => s == statusOriginal);
    final summary = buildSummary(parts);

    return {
      'parts': parts,
      'all_original': allOriginal,
      'summary': summary,
    };
  }

  static Map<String, String> buildSummary(Map<String, String> parts) {
    const locales = ['ar', 'en', 'tr'];
    final result = <String, String>{};

    for (final locale in locales) {
      final sep = locale == 'en' ? ', ' : '، ';
      final lines = <String>[];
      for (final status in [
        statusReplaced,
        statusPainted,
        statusLocalPaint,
      ]) {
        final names = <String>[];
        for (final entry in parts.entries) {
          if (entry.value == status) {
            names.add(partLabel(entry.key, locale));
          }
        }
        if (names.isEmpty) continue;
        lines.add('${groupTitle(status, locale)}: ${names.join(sep)}');
      }
      result[locale] = lines.isEmpty
          ? AppTranslations.tr(locale, 'car_body_all_original_summary')
          : lines.join('\n');
    }

    return result;
  }

  static String groupTitle(String status, String locale) {
    final key = switch (status) {
      statusReplaced => 'car_body_group_replaced',
      statusPainted => 'car_body_group_painted',
      statusLocalPaint => 'car_body_group_local_paint',
      _ => 'car_body_group_original',
    };
    return AppTranslations.tr(locale, key);
  }

  static String partLabel(String partId, [String? locale]) {
    final loc = locale ?? AppLocale.current;
    return _partLabels[partId]?[loc] ?? _partLabels[partId]?['ar'] ?? partId;
  }

  static String statusLabel(String status, [String? locale]) {
    final loc = locale ?? AppLocale.current;
    final key = switch (status) {
      statusLocalPaint => 'car_body_status_local_paint',
      statusPainted => 'car_body_status_painted',
      statusReplaced => 'car_body_status_replaced',
      _ => 'car_body_status_original',
    };
    return AppTranslations.tr(loc, key);
  }

  static int countNonOriginal(Map<String, String> parts) {
    return parts.values.where((s) => s != statusOriginal).length;
  }

  static const Map<String, Map<String, String>> _partLabels = {
    'front_bumper': {
      'ar': 'المصد الأمامي',
      'en': 'Front Bumper',
      'tr': 'Ön Tampon',
    },
    'hood': {'ar': 'غطاء المحرك', 'en': 'Hood', 'tr': 'Kaput'},
    'front_left_fender': {
      'ar': 'الرفرف الأمامي الأيسر',
      'en': 'Front Left Fender',
      'tr': 'Sol Ön Çamurluk',
    },
    'front_right_fender': {
      'ar': 'الرفرف الأمامي الأيمن',
      'en': 'Front Right Fender',
      'tr': 'Sağ Ön Çamurluk',
    },
    'left_front_door': {
      'ar': 'الباب الأمامي الأيسر',
      'en': 'Left Front Door',
      'tr': 'Sol Ön Kapı',
    },
    'right_front_door': {
      'ar': 'الباب الأمامي الأيمن',
      'en': 'Right Front Door',
      'tr': 'Sağ Ön Kapı',
    },
    'roof': {'ar': 'السقف', 'en': 'Roof', 'tr': 'Tavan'},
    'left_rear_door': {
      'ar': 'الباب الخلفي الأيسر',
      'en': 'Left Rear Door',
      'tr': 'Sol Arka Kapı',
    },
    'right_rear_door': {
      'ar': 'الباب الخلفي الأيمن',
      'en': 'Right Rear Door',
      'tr': 'Sağ Arka Kapı',
    },
    'left_rear_fender': {
      'ar': 'الرفرف الخلفي الأيسر',
      'en': 'Left Rear Fender',
      'tr': 'Sol Arka Çamurluk',
    },
    'right_rear_fender': {
      'ar': 'الرفرف الخلفي الأيمن',
      'en': 'Right Rear Fender',
      'tr': 'Sağ Arka Çamurluk',
    },
    'trunk': {
      'ar': 'صندوق الأمتعة',
      'en': 'Trunk',
      'tr': 'Bagaj Kapağı',
    },
    'rear_bumper': {
      'ar': 'المصد الخلفي',
      'en': 'Rear Bumper',
      'tr': 'Arka Tampon',
    },
  };
}

class CarBodyMapShape {
  const CarBodyMapShape({required this.pathData});

  final String pathData;
}
