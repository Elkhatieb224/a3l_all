import 'package:dio/dio.dart';
import 'package:a3lnha/core/network/api_client.dart';
import 'package:a3lnha/core/network/api_constants.dart';

class AppInfoModel {
  final String establishmentName;
  final String commercialName;
  final String responsiblePerson;
  final String commercialRegistrationNumber;
  final String officialEmail;
  final String mersisNumber;
  final String mainOffice;
  final String callCenter;
  final String supportCenter;
  final String mapLocationUrl;

  AppInfoModel({
    required this.establishmentName,
    required this.commercialName,
    required this.responsiblePerson,
    required this.commercialRegistrationNumber,
    required this.officialEmail,
    required this.mersisNumber,
    required this.mainOffice,
    required this.callCenter,
    required this.supportCenter,
    required this.mapLocationUrl,
  });

  factory AppInfoModel.fromJson(Map<String, dynamic> json) {
    return AppInfoModel(
      establishmentName: json['establishment_name'] as String? ?? '',
      commercialName: json['commercial_name'] as String? ?? '',
      responsiblePerson: json['responsible_person'] as String? ?? '',
      commercialRegistrationNumber:
          json['commercial_registration_number'] as String? ?? '',
      officialEmail: json['official_email'] as String? ?? '',
      mersisNumber: json['mersis_number'] as String? ?? '',
      mainOffice: json['main_office'] as String? ?? '',
      callCenter: json['call_center'] as String? ?? '',
      supportCenter: json['support_center'] as String? ?? '',
      mapLocationUrl: json['map_location_url'] as String? ?? '',
    );
  }
}

class AppInfoService {
  AppInfoService._();

  static Future<AppInfoModel?> getAppInfo() async {
    try {
      final response =
          await ApiClient.dio.get(ApiConstants.appInfo);
      final data = response.data as Map<String, dynamic>;
      if (data['success'] == true && data['data'] != null) {
        return AppInfoModel.fromJson(
            data['data'] as Map<String, dynamic>);
      }
      return null;
    } on DioException {
      return null;
    }
  }
}
