import 'package:dio/dio.dart';

import 'package:a3lnha/core/network/api_client.dart';
import 'package:a3lnha/core/network/api_constants.dart';
import 'package:a3lnha/data/models/ad_model.dart';

class SavedSearchModel {
  final int id;
  final String? name;
  final Map<String, dynamic> filters;
  final String? createdAt;

  SavedSearchModel({
    required this.id,
    this.name,
    required this.filters,
    this.createdAt,
  });

  factory SavedSearchModel.fromJson(Map<String, dynamic> json) {
    return SavedSearchModel(
      id: (json['id'] as num?)?.toInt() ?? 0,
      name: json['name']?.toString(),
      filters: (json['filters'] is Map<String, dynamic>)
          ? (json['filters'] as Map<String, dynamic>)
          : {},
      createdAt: json['created_at']?.toString(),
    );
  }
}

class SavedSearchResultsResponse {
  final SavedSearchModel savedSearch;
  final List<AdModel> ads;
  final int currentPage;
  final int lastPage;
  final int total;

  SavedSearchResultsResponse({
    required this.savedSearch,
    required this.ads,
    required this.currentPage,
    required this.lastPage,
    required this.total,
  });
}

class SavedSearchService {
  SavedSearchService._();

  static Future<List<SavedSearchModel>> getSavedSearches() async {
    final response = await ApiClient.dio.get(ApiConstants.savedSearches);
    final data = response.data as Map<String, dynamic>;
    final list = (data['data'] as List?) ?? const [];
    return list
        .whereType<Map>()
        .map((e) => SavedSearchModel.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  static Future<String?> saveSearch({
    String? name,
    required Map<String, dynamic> filters,
  }) async {
    try {
      final response = await ApiClient.dio.post(
        ApiConstants.savedSearches,
        data: {
          if (name != null && name.trim().isNotEmpty) 'name': name.trim(),
          'filters': filters,
        },
      );
      final data = response.data as Map<String, dynamic>;
      return data['message']?.toString();
    } on DioException catch (e) {
      if (e.response?.data is Map<String, dynamic>) {
        final m = e.response!.data as Map<String, dynamic>;
        return m['message']?.toString();
      }
      rethrow;
    }
  }

  static Future<bool> deleteSavedSearch(int id) async {
    try {
      await ApiClient.dio.delete('${ApiConstants.savedSearches}/$id');
      return true;
    } catch (_) {
      return false;
    }
  }

  static Future<SavedSearchResultsResponse> getSavedSearchResults(
    int id, {
    int page = 1,
    int perPage = 20,
  }) async {
    final response = await ApiClient.dio.get(
      '${ApiConstants.savedSearches}/$id/results',
      queryParameters: {'page': page, 'per_page': perPage},
    );
    final data = response.data as Map<String, dynamic>;
    final saved = SavedSearchModel.fromJson(
      Map<String, dynamic>.from(data['saved_search'] as Map? ?? const {}),
    );
    final ads = ((data['data'] as List?) ?? const [])
        .whereType<Map>()
        .map((e) => AdModel.fromJson(Map<String, dynamic>.from(e)))
        .toList();
    final meta = Map<String, dynamic>.from(data['meta'] as Map? ?? const {});
    return SavedSearchResultsResponse(
      savedSearch: saved,
      ads: ads,
      currentPage: (meta['current_page'] as num?)?.toInt() ?? 1,
      lastPage: (meta['last_page'] as num?)?.toInt() ?? 1,
      total: (meta['total'] as num?)?.toInt() ?? ads.length,
    );
  }
}

