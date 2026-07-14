// نسخة Android/iOS: استخدام dart:io للتحقق من المنصة
import 'dart:io';

bool get isMobilePlatform => Platform.isAndroid || Platform.isIOS;
bool get isAndroid => Platform.isAndroid;
bool get isIOS => Platform.isIOS;
String get deviceType =>
    Platform.isAndroid ? 'android' : (Platform.isIOS ? 'ios' : 'web');
