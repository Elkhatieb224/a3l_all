/// توحيد عرض العملة بالرمز فقط (ل.س، $، TL، €) في كل واجهات التطبيق.
class CurrencyHelper {
  CurrencyHelper._();

  static const Map<String, String> symbols = {
    'SYP': 'ل.س',
    'TRY': 'TL',
    'USD': '\$',
    'EUR': '€',
  };

  /// إرجاع رمز العملة من كودها (مثال: SYP → ل.س).
  static String symbol(String? currencyCode) {
    if (currencyCode == null || currencyCode.isEmpty) return 'ل.س';
    return symbols[currencyCode.toUpperCase()] ?? currencyCode;
  }

  /// تنسيق السعر مع فواصل آلاف (فاصلة) ورمز العملة — بدون تقريب (مثال: 30100 → "30,100 ل.س").
  static String formatPrice(num? price, String? currencyCode) {
    if (price == null) return '—';
    final sym = symbol(currencyCode);
    final str = _formatNumber(price);
    return '$str $sym';
  }

  /// تنسيق الرقم فقط (للدمج مع الرمز يدوياً، مثل شريط المحفظة).
  static String formatNumber(num? n) {
    if (n == null) return '0';
    return _formatNumber(n);
  }

  static String _formatNumber(num n) {
    // بدون كسور عندما لا توجد أسنتيمات (نفس منطق format_price في الـ backend)
    final r = (n * 100).round() / 100;
    final cents = (r * 100).round() % 100;
    if (cents == 0) {
      return _addSeparator(r.round().toString());
    }
    var s = r.toStringAsFixed(2)
        .replaceFirst(RegExp(r'0+$'), '')
        .replaceFirst(RegExp(r'\.$'), '');
    if (s.contains('.')) {
      final parts = s.split('.');
      return '${_addSeparator(parts[0])}.${parts[1]}';
    }
    return _addSeparator(s);
  }

  static String _addSeparator(String s) {
    final neg = s.startsWith('-');
    if (neg) s = s.substring(1);
    final buf = StringBuffer();
    for (var i = 0; i < s.length; i++) {
      if (i > 0 && (s.length - i) % 3 == 0) buf.write(',');
      buf.write(s[i]);
    }
    return neg ? '-${buf.toString()}' : buf.toString();
  }

  /// قائمة أكواد العملة المدعومة للاختيار (للعرض نستخدم الرمز عبر [symbol]).
  static const List<String> supportedCodes = ['SYP', 'TRY', 'USD', 'EUR'];

  /// إزالة `.00` من نص سعر قادم من الـ API (مثل "1,650,000.00 TL").
  static String stripTrailingCentsZeros(String formatted) {
    return formatted.replaceAll(RegExp(r'\.00(?=\s|$)'), '');
  }
}
