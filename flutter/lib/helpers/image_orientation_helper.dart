import 'dart:typed_data';

import 'package:image/image.dart' as img;

/// يدمج اتجاه EXIF في البكسل ثم يعيد JPEG — يصلح دوران صور الكاميرا/المعرض.
Uint8List? normalizeImageForDisplayAndUpload(Uint8List bytes, {int jpegQuality = 88}) {
  try {
    final decoded = img.decodeImage(bytes);
    if (decoded == null) return null;
    final baked = img.bakeOrientation(decoded);
    return Uint8List.fromList(img.encodeJpg(baked, quality: jpegQuality));
  } catch (_) {
    return null;
  }
}
