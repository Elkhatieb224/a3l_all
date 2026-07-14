import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:image_picker/image_picker.dart';

import 'package:a3lnha/core/network/api_client.dart';
import 'package:a3lnha/core/network/api_constants.dart';
import 'package:a3lnha/core/storage/token_storage.dart';
import 'package:a3lnha/data/models/user_model.dart';
import 'package:a3lnha/helpers/image_orientation_helper.dart';

class UserService {
  UserService._();

  static Future<UserModel?> getUser() async {
    try {
      final response = await ApiClient.dio.get(ApiConstants.user);
      final data = response.data as Map<String, dynamic>;
      if (data['success'] == true && data['data'] != null) {
        return UserModel.fromJson(data['data'] as Map<String, dynamic>);
      }
      return null;
    } on DioException catch (e) {
      if (e.response?.statusCode == 401) {
        await TokenStorage.removeToken();
      }
      return null;
    }
  }

  static Future<Map<String, dynamic>> updateProfile({
    String? name,
    String? phone,
    String? countryCode,
    String? businessName,
    String? bio,
    String? locationCountry,
    String? locationCity,
    String? locationDistrict,
    dynamic avatarFile,
    Uint8List? avatarBytes,
  }) async {
    try {
      final Map<String, dynamic> payload = {
        if (name != null) 'name': name,
        if (phone != null) 'phone': phone,
        if (countryCode != null && countryCode.isNotEmpty) 'country_code': countryCode,
        if (businessName != null) 'business_name': businessName,
        if (bio != null) 'bio': bio,
        'location_country': locationCountry,
        'location_city': locationCity,
        'location_district': locationDistrict,
      };

      final hasAvatarUpload = (avatarBytes != null && avatarBytes.isNotEmpty) ||
          (avatarFile != null && avatarFile is XFile);
      if (hasAvatarUpload) {
        final Uint8List bytes;
        if (avatarBytes != null && avatarBytes.isNotEmpty) {
          bytes = avatarBytes;
        } else {
          final raw = await (avatarFile as XFile).readAsBytes();
          bytes = normalizeImageForDisplayAndUpload(raw) ?? raw;
        }
        final formData = FormData.fromMap({
          if (name != null) 'name': name,
          if (phone != null) 'phone': phone,
          if (countryCode != null && countryCode.isNotEmpty) 'country_code': countryCode,
          if (businessName != null) 'business_name': businessName,
          if (bio != null) 'bio': bio,
          if (locationCountry != null && locationCountry.isNotEmpty) 'location_country': locationCountry,
          if (locationCity != null) 'location_city': locationCity,
          if (locationDistrict != null) 'location_district': locationDistrict,
          'avatar': MultipartFile.fromBytes(bytes, filename: 'avatar.jpg'),
        });
        // PHP لا يملأ $_FILES مع PUT؛ نرسل POST مع _method=PUT حتى يستقبل السيرفر الملف
        final response = await ApiClient.dio.post(
          ApiConstants.user,
          data: formData,
        );
        final data = response.data as Map<String, dynamic>;
        if (data['success'] == true && data['data'] != null) {
          return {
            'success': true,
            'user': UserModel.fromJson(data['data'] as Map<String, dynamic>),
            'message': data['message'] as String? ?? 'تم تحديث الملف الشخصي بنجاح',
          };
        }
        return {'success': false, 'message': data['message'] as String? ?? 'فشل التحديث'};
      }

      final response = await ApiClient.dio.put(
        ApiConstants.user,
        data: payload,
      );
      final data = response.data as Map<String, dynamic>;
      if (data['success'] == true && data['data'] != null) {
        return {'success': true, 'user': UserModel.fromJson(data['data'] as Map<String, dynamic>)};
      }
      return {'success': false, 'message': data['message'] as String? ?? 'فشل التحديث'};
    } on DioException catch (e) {
      final msg = e.response?.data is Map
          ? (e.response!.data as Map)['message'] as String?
          : null;
      return {'success': false, 'message': msg ?? 'حدث خطأ'};
    }
  }

  static Future<Map<String, dynamic>> updatePassword({
    required String currentPassword,
    required String password,
    required String passwordConfirmation,
  }) async {
    try {
      final response = await ApiClient.dio.put(
        '${ApiConstants.user}/password',
        data: {
          'current_password': currentPassword,
          'password': password,
          'password_confirmation': passwordConfirmation,
        },
      );
      final data = response.data as Map<String, dynamic>;
      return {
        'success': data['success'] == true,
        'message': data['message'] as String? ?? 'تم تغيير كلمة السر',
      };
    } on DioException catch (e) {
      final msg = e.response?.data is Map
          ? (e.response!.data as Map)['message'] as String?
          : null;
      return {'success': false, 'message': msg ?? 'فشل تغيير كلمة السر'};
    }
  }

  /// إرسال رمز التحقق للبريد الإلكتروني
  static Future<Map<String, dynamic>> sendEmailVerificationCode() async {
    try {
      final response = await ApiClient.dio
          .post('${ApiConstants.user}/email/send-verification-code');
      final data = response.data as Map<String, dynamic>;
      return {
        'success': data['success'] == true,
        'message': data['message'] as String? ?? '',
      };
    } on DioException catch (e) {
      final msg = e.response?.data is Map
          ? (e.response!.data as Map)['message'] as String?
          : null;
      return {'success': false, 'message': msg ?? 'فشل إرسال الرمز'};
    }
  }

  /// التحقق من رمز البريد الإلكتروني (6 خانات)
  static Future<Map<String, dynamic>> verifyEmailCode(
      {required String code}) async {
    try {
      final response = await ApiClient.dio.post(
        '${ApiConstants.user}/email/verify-code',
        data: {'code': code},
      );
      final data = response.data as Map<String, dynamic>;
      return {
        'success': data['success'] == true,
        'message': data['message'] as String? ?? '',
      };
    } on DioException catch (e) {
      final msg = e.response?.data is Map
          ? (e.response!.data as Map)['message'] as String?
          : null;
      return {'success': false, 'message': msg ?? 'رمز غير صحيح أو منتهي الصلاحية'};
    }
  }

  /// طلب تغيير البريد: إرسال رمز التحقق إلى البريد الجديد
  static Future<Map<String, dynamic>> requestEmailChange({
    required String newEmail,
  }) async {
    try {
      final response = await ApiClient.dio.post(
        '${ApiConstants.user}/email/request-change',
        data: {'new_email': newEmail},
      );
      final data = response.data as Map<String, dynamic>;
      return {
        'success': data['success'] == true,
        'message': data['message'] as String? ?? '',
      };
    } on DioException catch (e) {
      final msg = e.response?.data is Map
          ? (e.response!.data as Map)['message'] as String?
          : null;
      return {'success': false, 'message': msg ?? 'فشل إرسال الرمز'};
    }
  }

  /// التحقق من رمز تغيير البريد وتحديث البريد فعلياً
  static Future<Map<String, dynamic>> verifyEmailChangeCode({
    required String code,
  }) async {
    try {
      final response = await ApiClient.dio.post(
        '${ApiConstants.user}/email/verify-change',
        data: {'code': code},
      );
      final data = response.data as Map<String, dynamic>;
      return {
        'success': data['success'] == true,
        'message': data['message'] as String? ?? '',
        'user': data['data'] != null
            ? UserModel.fromJson(data['data'] as Map<String, dynamic>)
            : null,
      };
    } on DioException catch (e) {
      final msg = e.response?.data is Map
          ? (e.response!.data as Map)['message'] as String?
          : null;
      return {'success': false, 'message': msg ?? 'رمز غير صحيح أو منتهي الصلاحية'};
    }
  }

  /// طلب إلغاء الحساب (يُحذف بعد 14 يوم)
  static Future<Map<String, dynamic>> cancelAccount({
    required String password,
    required bool confirm,
  }) async {
    try {
      final response = await ApiClient.dio.post(
        '${ApiConstants.user}/cancel-account',
        data: {'password': password, 'confirm': confirm},
      );
      final data = response.data as Map<String, dynamic>;
      if (data['success'] == true) {
        await TokenStorage.removeToken();
      }
      return {
        'success': data['success'] == true,
        'message': data['message'] as String? ?? 'تم طلب حذف الحساب',
      };
    } on DioException catch (e) {
      final msg = e.response?.data is Map
          ? (e.response!.data as Map)['message'] as String?
          : null;
      return {'success': false, 'message': msg ?? 'فشل طلب حذف الحساب'};
    }
  }

  /// تحديث بيانات النشاط التجاري (للمستخدم الموثّق فقط). خادم الـ API يتجاهل الحقول إن لم يكن الموثّق.
  static Future<Map<String, dynamic>> updateVerifiedBusinessProfile({
    required String businessName,
    required String businessType,
    required String businessOwner,
    required String businessAddress,
    required String businessPhone,
    String? instagramUrl,
    String? facebookUrl,
    String? websiteUrl,
    XFile? storefrontImageFile,
  }) async {
    try {
      final Map<String, dynamic> fields = {
        'business_name': businessName,
        'business_type': businessType,
        'business_owner': businessOwner,
        'business_address': businessAddress,
        'business_phone': businessPhone,
        'instagram_url': instagramUrl ?? '',
        'facebook_url': facebookUrl ?? '',
        'website_url': websiteUrl ?? '',
      };

      if (storefrontImageFile != null) {
        final raw = await storefrontImageFile.readAsBytes();
        final normalized = normalizeImageForDisplayAndUpload(raw);
        final bytes = normalized ?? raw;
        final baseName = storefrontImageFile.name.split(RegExp(r'[/\\]')).last;
        final filename = normalized != null
            ? (baseName.contains('.')
                ? baseName.replaceFirst(RegExp(r'\.[^.]+$'), '.jpg')
                : 'storefront.jpg')
            : baseName;
        final formData = FormData.fromMap({
          ...fields,
          'storefront_image': MultipartFile.fromBytes(
            bytes,
            filename: filename,
          ),
        });
        final response = await ApiClient.dio.post(
          ApiConstants.user,
          data: formData,
        );
        final data = response.data as Map<String, dynamic>;
        if (data['success'] == true && data['data'] != null) {
          return {
            'success': true,
            'user': UserModel.fromJson(data['data'] as Map<String, dynamic>),
            'message': data['message'] as String? ?? '',
          };
        }
        return {
          'success': false,
          'message': data['message'] as String? ?? 'فشل التحديث',
        };
      }

      final response = await ApiClient.dio.put(
        ApiConstants.user,
        data: fields,
      );
      final data = response.data as Map<String, dynamic>;
      if (data['success'] == true && data['data'] != null) {
        return {
          'success': true,
          'user': UserModel.fromJson(data['data'] as Map<String, dynamic>),
          'message': data['message'] as String? ?? '',
        };
      }
      return {
        'success': false,
        'message': data['message'] as String? ?? 'فشل التحديث',
      };
    } on DioException catch (e) {
      final msg = e.response?.data is Map
          ? (e.response!.data as Map)['message'] as String?
          : null;
      return {'success': false, 'message': msg ?? 'حدث خطأ'};
    }
  }
}
