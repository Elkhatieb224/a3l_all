import 'package:dio/dio.dart';

import 'package:a3lnha/core/network/api_client.dart';
import 'package:a3lnha/core/network/api_constants.dart';
import 'package:a3lnha/core/performance/ttl_memory_cache.dart';
import 'package:a3lnha/core/storage/token_storage.dart';
import 'package:a3lnha/data/models/user_model.dart';

/// نتيجة عملية المصادقة
class AuthResult {
  final bool success;
  final String? message;
  final UserModel? user;
  final String? token;
  final Map<String, dynamic>? errors;

  AuthResult({
    required this.success,
    this.message,
    this.user,
    this.token,
    this.errors,
  });
}

/// خدمة المصادقة
class AuthService {
  AuthService._();

  /// تسجيل الدخول
  static Future<AuthResult> login({
    required String email,
    required String password,
    String? fcmToken,
  }) async {
    try {
      final data = <String, dynamic>{
        'email': email,
        'password': password,
      };
      if (fcmToken != null && fcmToken.isNotEmpty) {
        data['fcm_token'] = fcmToken;
      }
      final response = await ApiClient.dio.post(
        ApiConstants.login,
        data: data,
      );

      final responseData = response.data as Map<String, dynamic>;
      if (responseData['success'] == true && responseData['data'] != null) {
        final dataObj = responseData['data'] as Map<String, dynamic>;
        final token = dataObj['token'] as String?;
        final userJson = dataObj['user'] as Map<String, dynamic>?;

        if (token != null) {
          await TokenStorage.saveToken(token);
        }

        return AuthResult(
          success: true,
          message: responseData['message'] as String?,
          user: userJson != null ? UserModel.fromJson(userJson) : null,
          token: token,
        );
      }

      return AuthResult(
        success: false,
        message: responseData['message'] as String? ?? 'حدث خطأ',
      );
    } on DioException catch (e) {
      return _handleDioError(e);
    }
  }

  /// تسجيل مستخدم جديد
  static Future<AuthResult> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    String? phone,
    String? countryCode,
    String? fcmToken,
  }) async {
    try {
      final data = <String, dynamic>{
        'name': name,
        'email': email,
        'password': password,
        'password_confirmation': passwordConfirmation,
        if (phone != null && phone.isNotEmpty) 'phone': phone,
        if (countryCode != null && countryCode.isNotEmpty) 'country_code': countryCode,
      };
      if (fcmToken != null && fcmToken.isNotEmpty) {
        data['fcm_token'] = fcmToken;
      }
      final response = await ApiClient.dio.post(
        ApiConstants.register,
        data: data,
      );

      final responseData = response.data as Map<String, dynamic>;
      if (responseData['success'] == true && responseData['data'] != null) {
        final dataObj = responseData['data'] as Map<String, dynamic>;
        final token = dataObj['token'] as String?;
        final userJson = dataObj['user'] as Map<String, dynamic>?;

        if (token != null) {
          await TokenStorage.saveToken(token);
        }

        return AuthResult(
          success: true,
          message: responseData['message'] as String?,
          user: userJson != null ? UserModel.fromJson(userJson) : null,
          token: token,
        );
      }

      return AuthResult(
        success: false,
        message: responseData['message'] as String? ?? 'حدث خطأ',
      );
    } on DioException catch (e) {
      return _handleDioError(e);
    }
  }

  /// تسجيل الخروج
  static Future<AuthResult> logout() async {
    try {
      await ApiClient.dio.post(ApiConstants.logout);
      await TokenStorage.removeToken();
      return AuthResult(success: true, message: 'تم تسجيل الخروج بنجاح');
    } on DioException catch (e) {
      // حتى لو فشل الطلب، نحذف الـ Token محلياً
      await TokenStorage.removeToken();
      if (e.response?.statusCode == 401) {
        return AuthResult(success: true, message: 'تم تسجيل الخروج');
      }
      return _handleDioError(e);
    }
  }

  static const Duration _meCacheTtl = Duration(minutes: 2);
  static const _meCacheKey = 'auth.me';

  /// الحصول على بيانات المستخدم الحالي (مع كاش قصير لتسريع صفحة الحساب بعد تسجيل الدخول)
  static Future<AuthResult> getMe({bool forceRefresh = false}) async {
    if (!forceRefresh) {
      final cached = TtlMemoryCache.getIfValid<AuthResult>(_meCacheKey);
      if (cached != null) return cached;
    }
    try {
      final response = await ApiClient.dio.get(ApiConstants.me);
      final data = response.data as Map<String, dynamic>;
      if (data['success'] == true && data['data'] != null) {
        final user = UserModel.fromJson(data['data'] as Map<String, dynamic>);
        final result = AuthResult(success: true, user: user);
        if (result.user != null) {
          TtlMemoryCache.set<AuthResult>(_meCacheKey, result, _meCacheTtl);
        }
        return result;
      }
      return AuthResult(success: false, message: 'فشل تحميل البيانات');
    } on DioException catch (e) {
      if (e.response?.statusCode == 401) {
        await TokenStorage.removeToken();
      }
      return _handleDioError(e);
    }
  }

  /// التحقق من صلاحية الـ Token
  static Future<bool> checkAuth() async {
    if (!TokenStorage.hasToken()) return false;
    final result = await getMe();
    return result.success;
  }

  /// إرسال رمز استعادة كلمة المرور
  static Future<AuthResult> sendPasswordResetCode({required String email}) async {
    try {
      final response = await ApiClient.dio.post(
        ApiConstants.passwordForgot,
        data: {'email': email},
      );
      final data = response.data as Map<String, dynamic>;
      return AuthResult(
        success: data['success'] == true,
        message: data['message'] as String? ?? 'تم إرسال الرمز',
      );
    } on DioException catch (e) {
      return _handleDioError(e);
    }
  }

  /// استعادة كلمة المرور بالرمز
  static Future<AuthResult> resetPassword({
    required String email,
    required String code,
    required String password,
    required String passwordConfirmation,
  }) async {
    try {
      final response = await ApiClient.dio.post(
        ApiConstants.passwordReset,
        data: {
          'email': email,
          'code': code,
          'password': password,
          'password_confirmation': passwordConfirmation,
        },
      );
      final data = response.data as Map<String, dynamic>;
      return AuthResult(
        success: data['success'] == true,
        message: data['message'] as String? ?? 'تم تغيير كلمة المرور',
      );
    } on DioException catch (e) {
      return _handleDioError(e);
    }
  }

  static AuthResult _handleDioError(DioException e) {
    final response = e.response;
    if (response != null && response.data is Map<String, dynamic>) {
      final data = response.data as Map<String, dynamic>;
      return AuthResult(
        success: false,
        message: data['message'] as String? ?? 'حدث خطأ',
        errors: data['errors'] as Map<String, dynamic>?,
      );
    }
    return AuthResult(
      success: false,
      message: e.message ?? 'خطأ في الاتصال بالخادم',
    );
  }
}
