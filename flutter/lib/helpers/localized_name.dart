import 'package:a3lnha/core/locale/locale_storage.dart';

/// اختيار الاسم حسب اللغة من name_ar, name_en, name_tr
String getLocalizedName({
  String? nameAr,
  String? nameEn,
  String? nameTr,
  String? defaultName,
  String? locale,
}) {
  final loc = locale ?? LocaleStorage.getLocale();
  String? name;
  switch (loc) {
    case LocaleStorage.ar:
      name = nameAr;
      break;
    case LocaleStorage.en:
      name = nameEn;
      break;
    case LocaleStorage.tr:
      name = nameTr;
      break;
  }
  final resolved = name?.trim().isNotEmpty == true
      ? name!
      : (nameAr ?? nameEn ?? nameTr ?? defaultName ?? '');

  return formatLocalizedDisplayName(resolved, locale: loc);
}

/// Capitalize first letter for English/Turkish display names.
String formatLocalizedDisplayName(String name, {String? locale}) {
  final trimmed = name.trim();
  if (trimmed.isEmpty) return trimmed;

  final loc = locale ?? LocaleStorage.getLocale();
  if (!_shouldCapitalize(trimmed, loc)) {
    return trimmed;
  }

  return _capitalizeFirstLetter(trimmed, loc);
}

bool _shouldCapitalize(String name, String locale) {
  if (locale == LocaleStorage.en || locale == LocaleStorage.tr) {
    return true;
  }

  return _isLatinScriptName(name);
}

bool _isLatinScriptName(String name) {
  if (!RegExp(r'\p{Latin}', unicode: true).hasMatch(name)) {
    return false;
  }

  return RegExp(r"^[\p{Latin}\p{N}\s\-&.']+$", unicode: true).hasMatch(name);
}

String _capitalizeFirstLetter(String name, String locale) {
  final match = RegExp(r'\p{L}', unicode: true).firstMatch(name);
  if (match == null) return name;

  final index = match.start;
  final char = match.group(0)!;
  final upper = locale == LocaleStorage.tr
      ? _turkishUpperFirst(char)
      : char.toUpperCase();

  return name.substring(0, index) + upper + name.substring(index + char.length);
}

String _turkishUpperFirst(String char) {
  const map = {
    'i': 'İ',
    'ı': 'I',
  };
  return map[char] ?? char.toUpperCase();
}
