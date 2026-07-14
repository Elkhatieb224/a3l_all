import 'dart:math' as math;
import 'dart:ui' as ui;

import 'package:a3lnha/core/styles/colors.dart';
import 'package:flutter/material.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';

/// مقياس حجم فقاعة السعر من مستوى تكبير خرائط Google (أقرب = أكبر على الشاشة).
double mapMarkerZoomScaleFromZoom(double zoom) {
  final v = math.pow(2, (zoom - 15) * 0.38);
  // عند زوم ~12 كان يصل للحد الأدنى ويصغر النص كثيراً على الشاشات العالية الدقة.
  return v.clamp(1.0, 3.0).toDouble();
}

/// فقاعة سعر على الخريطة (مشابهة لأسلوب العقارات التركية).
/// [zoomScale] يضبط حجم الفقاعة حسب تكبير الكاميرا (تفاصيل إعلان + قائمة النتائج).
class MapLabeledMarkerIcon {
  MapLabeledMarkerIcon._();

  static String _ellipsis(String s, int maxChars) {
    final t = s.trim();
    if (t.length <= maxChars) return t.isEmpty ? '—' : t;
    return '${t.substring(0, math.max(0, maxChars - 1))}…';
  }

  /// علامة سعر: خلفية زرقاء (أو حمراء للمميز) + مثلث صغير أسفل المنتصف.
  static Future<BitmapDescriptor> buildPriceTag({
    required String priceText,
    bool featured = false,
    bool visited = false,
    TextDirection textDirection = TextDirection.rtl,
    double pixelRatio = 2.0,
    double zoomScale = 1.0,
  }) async {
    final display = _ellipsis(priceText, 18);
    final dpr = pixelRatio.clamp(1.0, 3.0);
    final zs = zoomScale.clamp(1.0, 3.0);

    final fill = featured
        ? const Color(0xFFD32F2F)
        : (visited ? const Color(0xFF6B7280) : AppColors.darkBlue);
    final fontSize = 18.0 * zs;
    final textStyle = TextStyle(
      color: Colors.white,
      fontSize: fontSize,
      fontWeight: FontWeight.w700,
      height: 1.05,
    );

    final padH = 13.0 * zs;
    final padV = 10.0 * zs;
    final radius = 9.0 * zs;
    final tipH = 8.0 * zs;
    final tipHalfW = 7.0 * zs;
    final minBubbleW = 72.0 * zs;

    final tp = TextPainter(
      text: TextSpan(text: display, style: textStyle),
      textDirection: textDirection,
      maxLines: 1,
      ellipsis: '…',
    )..layout(maxWidth: 168 * zs);

    final bubbleW = math.max(tp.width + padH * 2, minBubbleW);
    final bubbleH = tp.height + padV * 2;
    final totalW = bubbleW;
    final totalH = bubbleH + tipH;

    final recorder = ui.PictureRecorder();
    final canvas = Canvas(recorder);

    final bubbleRect = RRect.fromRectAndRadius(
      Rect.fromLTWH(0, 0, bubbleW, bubbleH),
      Radius.circular(radius),
    );

    final shadow = Paint()
      ..color = const Color(0x40000000)
      ..maskFilter = const MaskFilter.blur(BlurStyle.normal, 2);
    canvas.drawRRect(bubbleRect.shift(const Offset(0, 1.5)), shadow);

    final bgPaint = Paint()..color = fill;
    final stroke = Paint()
      ..color = const Color(0x33000000)
      ..style = PaintingStyle.stroke
      ..strokeWidth = math.max(1.0, zs);
    canvas.drawRRect(bubbleRect, bgPaint);
    canvas.drawRRect(bubbleRect, stroke);

    tp.paint(
      canvas,
      Offset(
        (bubbleW - tp.width) / 2,
        padV,
      ),
    );

    final tipX = bubbleW / 2;
    final tipTop = bubbleH - 0.5;
    final pathTip = Path()
      ..moveTo(tipX - tipHalfW, tipTop)
      ..lineTo(tipX + tipHalfW, tipTop)
      ..lineTo(tipX, tipTop + tipH)
      ..close();
    canvas.drawPath(pathTip, bgPaint);
    canvas.drawPath(
      pathTip,
      Paint()
        ..color = const Color(0x33000000)
        ..style = PaintingStyle.stroke
        ..strokeWidth = math.max(1.0, zs),
    );

    final picture = recorder.endRecording();
    final wPx = (totalW * dpr).ceil();
    final hPx = (totalH * dpr).ceil();
    final image = await picture.toImage(wPx, hPx);
    final bd = await image.toByteData(format: ui.ImageByteFormat.png);
    if (bd == null) {
      return BitmapDescriptor.defaultMarkerWithHue(
        featured ? BitmapDescriptor.hueRed : BitmapDescriptor.hueAzure,
      );
    }
    return BitmapDescriptor.bytes(
      bd.buffer.asUint8List(),
      width: totalW,
      height: totalH,
    );
  }
}
