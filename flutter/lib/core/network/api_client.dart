import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';

import 'package:a3lnha/core/locale/locale_storage.dart';
import 'package:a3lnha/core/network/api_constants.dart';
import 'package:a3lnha/core/storage/token_storage.dart';

/// عميل API الموحد - يدير الطلبات والـ Token تلقائياً
class ApiClient {
  ApiClient._();

  static String _locale = LocaleStorage.getLocale();

  static final Dio _dio = Dio(
    BaseOptions(
      baseUrl: ApiConstants.baseUrl,
      connectTimeout: Duration(seconds: ApiConstants.connectTimeout),
      receiveTimeout: Duration(seconds: ApiConstants.receiveTimeout),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    ),
  );

  static Dio get dio => _dio;

  /// تحديث لغة الطلبات (يُستدعى عند تغيير لغة التطبيق)
  static void setLocale(String locale) {
    if (LocaleStorage.supportedLocales.contains(locale)) {
      _locale = locale;
    }
  }

  /// تهيئة الـ Client (استدعاء مرة واحدة عند بدء التطبيق)
  static void init() {
    _locale = LocaleStorage.getLocale();
    _dio.interceptors.clear();
    _dio.interceptors.add(_LocaleInterceptor());
    _dio.interceptors.add(_AuthInterceptor());
    if (kDebugMode) {
      _dio.interceptors.add(
        LogInterceptor(
          requestBody: false,
          responseBody: false,
          requestHeader: false,
          responseHeader: false,
          error: true,
        ),
      );
    }
  }
}

/// إضافة لغة الطلب للـ API
class _LocaleInterceptor extends Interceptor {
  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) {
    final override = options.extra['locale']?.toString().trim();
    final loc = (override != null && override.isNotEmpty) ? override : ApiClient._locale;
    options.headers['X-Locale'] = loc;
    options.headers['Accept-Language'] = loc;
    handler.next(options);
  }
}

/// إضافة Bearer Token للطلبات المحمية
class _AuthInterceptor extends Interceptor {
  static const _authCheckPaths = ['/me', '/user', '/logout'];

  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) {
    // طلبات المسارات العامة (مثل search-categories) تُرسل بدون توكن لتجنب 401 عند انتهاء التوكن
    if (options.extra['skipAuth'] == true) {
      handler.next(options);
      return;
    }
    final token = TokenStorage.getToken();
    if (token != null && token.isNotEmpty) {
      final bearer = 'Bearer $token';
      options.headers['Authorization'] = bearer;
      // بديل لـ Nginx الذي قد يحذف رأس Authorization
      options.headers['X-Authorization'] = bearer;
    }
    handler.next(options);
  }

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) {
    // حذف Token فقط عند 401 على مسارات التحقق من الهوية (/me, /user)
    // لتجنب تسجيل الخروج عند 401 عابر أو خطأ في خادم الإنتاج
    if (err.response?.statusCode == 401) {
      final path = err.requestOptions.path;
      final shouldRemove = _authCheckPaths.any((p) => path.contains(p));
      if (shouldRemove) {
        TokenStorage.removeToken();
      }
    }
    handler.next(err);
  }
}
