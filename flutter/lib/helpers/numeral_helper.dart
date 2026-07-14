import 'package:flutter/services.dart';

/// تحويل الأرقام العربية/الهندية إلى إنجليزي لاستخدامها في الحسابات والـ API.
class NumeralHelper {
  NumeralHelper._();

  static const Map<String, String> _arabicToEnglish = {
    '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4',
    '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9',
    '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4',
    '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9',
  };

  /// يحول أي أرقام عربة/فارسية في النص إلى إنجليزي.
  static String toEnglishDigits(String input) {
    if (input.isEmpty) return input;
    final buffer = StringBuffer();
    for (final c in input.split('')) {
      buffer.write(_arabicToEnglish[c] ?? c);
    }
    return buffer.toString();
  }

  /// يستخرج رقم من النص (أرقام ونقطة عشرية فقط) بالإنجليزي.
  static String onlyNumbers(String input) {
    final en = toEnglishDigits(input);
    final allowed = RegExp(r'[\d.]');
    return en.split('').where((c) => allowed.hasMatch(c)).join('');
  }

  /// يحول إلى [num] أو null إذا غير صالح.
  static num? parseAmount(String input) {
    final cleaned = onlyNumbers(input);
    if (cleaned.isEmpty) return null;
    return num.tryParse(cleaned);
  }

  /// يفسّر النص كرقم مع دعم فواصل الآلاف (نقطة أو فاصلة).
  /// مثلاً: "30.100" = 30100، "1.229.000" = 1229000، "30.50" = 30.50
  /// إذا كان الجزء بعد آخر فاصلة من 3 خانات يُعتبر فاصل آلاف، وإلا يُعتبر كسور عشرية.
  static num? parseFormattedAmount(String input) {
    if (input.trim().isEmpty) return null;
    String s = input.trim().replaceAll(' ', '');
    s = NumeralHelper.toEnglishDigits(s);
    if (s.contains(',')) s = s.replaceAll(',', '.');
    final parts = s.split('.');
    if (parts.length == 1) {
      return num.tryParse(parts[0].replaceAll('.', ''));
    }
    final lastPart = parts.last;
    // إذا آخر جزء 3 خانات والجزء الأول ليس "0" → الفواصل فواصل آلاف (مثل 30.100 أو 1.229.000)
    // استثناء: 0.100 = 0.1 (كسور عشرية)
    final treatAsThousands = lastPart.length == 3 &&
        !(parts.length == 2 && (parts[0] == '0' || parts[0].isEmpty));
    if (treatAsThousands) {
      final intStr = parts.join('').replaceAll('.', '');
      if (intStr.isEmpty) return null;
      return num.tryParse(intStr);
    }
    // إذا آخر جزء 1 أو 2 خانات → فاصل عشري (مثل 30.5 أو 30.50)
    final intPartStr = parts.sublist(0, parts.length - 1).join('').replaceAll('.', '');
    if (intPartStr.isEmpty && lastPart.isEmpty) return null;
    return num.tryParse('$intPartStr.$lastPart');
  }

  /// تنسيق رقم بفواصل آلاف (فاصلة) للعرض — مثل 100,200,300 بدون فاصلة عشرية للأعداد الصحيحة.
  static String formatWithThousands(num? value) {
    if (value == null) return '';
    final n = value is int ? value : value.toDouble();
    if (n == n.truncate()) {
      final s = n.toInt().abs().toString();
      final neg = n < 0;
      final buf = StringBuffer();
      for (var i = 0; i < s.length; i++) {
        if (i > 0 && (s.length - i) % 3 == 0) buf.write(',');
        buf.write(s[i]);
      }
      return neg ? '-${buf.toString()}' : buf.toString();
    }
    final parts = n.toString().split('.');
    final intPart = formatWithThousands(num.tryParse(parts[0]));
    return '$intPart.${parts[1]}';
  }
}

/// منسق إدخال: يقبل الأرقام فقط (إنجليزي)، ويحوّل الأرقام العربية/الفارسية تلقائياً إلى إنجليزي.
class EnglishOnlyNumberInputFormatter extends TextInputFormatter {
  final bool allowDecimal;

  EnglishOnlyNumberInputFormatter({this.allowDecimal = true});

  @override
  TextEditingValue formatEditUpdate(
    TextEditingValue oldValue,
    TextEditingValue newValue,
  ) {
    String text = NumeralHelper.toEnglishDigits(newValue.text);
    final allowed = allowDecimal ? RegExp(r'[\d.]') : RegExp(r'\d');
    final chars = text.split('').where((c) => allowed.hasMatch(c)).toList();
    if (allowDecimal && chars.where((c) => c == '.').length > 1) {
      int dotCount = 0;
      text = chars.where((c) {
        if (c == '.') {
          dotCount++;
          return dotCount <= 1;
        }
        return true;
      }).join('');
    } else {
      text = chars.join('');
    }
    if (text == newValue.text && text == oldValue.text) return newValue;
    return TextEditingValue(
      text: text,
      selection: TextSelection.collapsed(offset: text.length),
    );
  }
}

/// منسق إدخال: يعرض الأرقام بفواصل آلاف (فاصلة) أثناء الكتابة — مثل 100,200,300 بدون فاصلة عشرية.
class ThousandSeparatorInputFormatter extends TextInputFormatter {
  final bool allowDecimal;

  ThousandSeparatorInputFormatter({this.allowDecimal = true});

  @override
  TextEditingValue formatEditUpdate(
    TextEditingValue oldValue,
    TextEditingValue newValue,
  ) {
    String text = NumeralHelper.toEnglishDigits(newValue.text);
    text = text.replaceAll(',', '');
    final allowed = allowDecimal ? RegExp(r'[\d.]') : RegExp(r'\d');
    final chars = text.split('').where((c) => allowed.hasMatch(c)).toList();
    int dotCount = 0;
    final filtered = <String>[];
    for (final c in chars) {
      if (c == '.') {
        dotCount++;
        if (dotCount <= 1) filtered.add(c);
      } else {
        filtered.add(c);
      }
    }
    text = filtered.join('');
    String formatted = '';
    if (allowDecimal && text.contains('.')) {
      final idx = text.indexOf('.');
      formatted = _addThousandCommas(text.substring(0, idx)) + text.substring(idx);
    } else {
      formatted = _addThousandCommas(text);
    }
    if (formatted == newValue.text && formatted == oldValue.text) return newValue;
    return TextEditingValue(
      text: formatted,
      selection: TextSelection.collapsed(offset: formatted.length),
    );
  }

  static String _addThousandCommas(String s) {
    if (s.isEmpty) return s;
    final neg = s.startsWith('-');
    if (neg) s = s.substring(1);
    final buf = StringBuffer();
    for (var i = 0; i < s.length; i++) {
      if (i > 0 && (s.length - i) % 3 == 0) buf.write(',');
      buf.write(s[i]);
    }
    return neg ? '-${buf.toString()}' : buf.toString();
  }
}
