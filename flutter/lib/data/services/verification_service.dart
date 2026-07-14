import 'package:dio/dio.dart';
import 'package:image_picker/image_picker.dart';
import 'package:a3lnha/core/network/api_client.dart';
import 'package:a3lnha/core/network/api_constants.dart';

class VerificationData {
  final bool isVerified;
  final String verificationRequirements;
  final Map<String, dynamic>? pendingRequest;
  final Map<String, dynamic>? lastRequest;

  VerificationData({
    required this.isVerified,
    required this.verificationRequirements,
    this.pendingRequest,
    this.lastRequest,
  });

  factory VerificationData.fromJson(Map<String, dynamic> json) {
    return VerificationData(
      isVerified: json['is_verified'] as bool? ?? false,
      verificationRequirements:
          json['verification_requirements'] as String? ?? '',
      pendingRequest: json['pending_request'] is Map
          ? Map<String, dynamic>.from(json['pending_request'] as Map)
          : null,
      lastRequest: json['last_request'] is Map
          ? Map<String, dynamic>.from(json['last_request'] as Map)
          : null,
    );
  }
}

class VerificationService {
  VerificationService._();

  static Future<VerificationData?> getStatus() async {
    try {
      final response = await ApiClient.dio.get(ApiConstants.verification);
      final data = response.data as Map<String, dynamic>;
      if (data['success'] == true && data['data'] != null) {
        return VerificationData.fromJson(
            data['data'] as Map<String, dynamic>);
      }
      return null;
    } on DioException {
      return null;
    }
  }

  static Future<Map<String, dynamic>> submitRequest({
    required String businessName,
    required String businessType,
    required String responsiblePerson,
    required String businessAddress,
    required String businessPhone,
    required String primaryDocumentType,
    required XFile primaryDocumentFile,
    String? message,
    String? instagramUrl,
    String? facebookUrl,
    String? websiteUrl,
    XFile? storefrontImageFile,
  }) async {
    try {
      final formData = FormData.fromMap({
        'business_name': businessName,
        'business_type': businessType,
        'responsible_person': responsiblePerson,
        'business_address': businessAddress,
        'business_phone': businessPhone,
        'primary_document_type': primaryDocumentType,
        if (message != null && message.isNotEmpty) 'message': message,
        if (instagramUrl != null && instagramUrl.isNotEmpty)
          'instagram_url': instagramUrl,
        if (facebookUrl != null && facebookUrl.isNotEmpty)
          'facebook_url': facebookUrl,
        if (websiteUrl != null && websiteUrl.isNotEmpty)
          'website_url': websiteUrl,
        'primary_document': await MultipartFile.fromFile(
          primaryDocumentFile.path,
          filename: primaryDocumentFile.name.split(RegExp(r'[/\\]')).last,
        ),
        if (storefrontImageFile != null)
          'storefront_image': await MultipartFile.fromFile(
            storefrontImageFile.path,
            filename: 'storefront.jpg',
          ),
      });

      final response = await ApiClient.dio.post(
        ApiConstants.verification,
        data: formData,
      );
      final data = response.data as Map<String, dynamic>;
      return {
        'success': data['success'] == true,
        'message': data['message'] as String? ?? 'تم إرسال طلب التحقق',
      };
    } on DioException catch (e) {
      String? msg;
      if (e.response?.data is Map) {
        final data = Map<String, dynamic>.from(e.response!.data as Map);
        msg = data['message'] as String?;
        final errors = data['errors'];
        if (errors is Map && errors.isNotEmpty) {
          final firstErrorValue = errors.values.first;
          if (firstErrorValue is List && firstErrorValue.isNotEmpty) {
            msg = firstErrorValue.first?.toString() ?? msg;
          } else if (firstErrorValue != null) {
            msg = firstErrorValue.toString();
          }
        }
      }
      return {'success': false, 'message': msg ?? 'فشل إرسال الطلب'};
    }
  }
}
