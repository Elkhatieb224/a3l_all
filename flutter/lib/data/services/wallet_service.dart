import 'package:dio/dio.dart';

import 'package:a3lnha/core/network/api_client.dart';
import 'package:a3lnha/core/network/api_constants.dart';
import 'package:a3lnha/core/storage/token_storage.dart';

class WalletBalanceItem {
  final String currency;
  final num amount;

  WalletBalanceItem({required this.currency, required this.amount});

  factory WalletBalanceItem.fromJson(Map<String, dynamic> json) {
    return WalletBalanceItem(
      currency: (json['currency'] as String?) ?? 'SYP',
      amount: (json['amount'] as num?) ?? 0,
    );
  }
}

class WalletResponse {
  final List<WalletBalanceItem> balances;

  WalletResponse({required this.balances});

  factory WalletResponse.fromJson(Map<String, dynamic> json) {
    final list = json['balances'];
    if (list is! List) {
      return WalletResponse(balances: []);
    }
    return WalletResponse(
      balances: list
          .map((e) => e is Map<String, dynamic>
              ? WalletBalanceItem.fromJson(e)
              : null)
          .whereType<WalletBalanceItem>()
          .toList(),
    );
  }

  num getBalance(String currency) {
    for (final b in balances) {
      if (b.currency == currency) return b.amount;
    }
    return 0;
  }
}

class WalletTransactionModel {
  final int id;
  final num amount;
  final String currency;
  final String type;
  final String? description;
  final String? referenceType;
  final String createdAt;

  WalletTransactionModel({
    required this.id,
    required this.amount,
    required this.currency,
    required this.type,
    this.description,
    this.referenceType,
    required this.createdAt,
  });

  factory WalletTransactionModel.fromJson(Map<String, dynamic> json) {
    return WalletTransactionModel(
      id: (json['id'] as int?) ?? 0,
      amount: (json['amount'] as num?) ?? 0,
      currency: (json['currency'] as String?) ?? 'SYP',
      type: (json['type'] as String?) ?? 'unknown',
      description: json['description'] as String?,
      referenceType: json['reference_type'] as String?,
      createdAt: (json['created_at'] as String?) ?? '',
    );
  }

  bool get isCredit => amount > 0;
}

class WalletService {
  WalletService._();

  static Future<WalletResponse?> getWallet() async {
    if (!TokenStorage.hasToken()) return null;
    try {
      final response = await ApiClient.dio.get(ApiConstants.wallet);
      final data = response.data as Map<String, dynamic>;
      if (data['success'] != true) return null;
      final inner = data['data'];
      if (inner is! Map<String, dynamic>) return null;
      return WalletResponse.fromJson(inner);
    } on DioException {
      return null;
    } catch (_) {
      return null;
    }
  }

  static Future<List<WalletTransactionModel>> getTransactions({
    int page = 1,
    int perPage = 20,
  }) async {
    if (!TokenStorage.hasToken()) return [];
    try {
      final response = await ApiClient.dio.get(
        ApiConstants.walletTransactions,
        queryParameters: {'page': page, 'per_page': perPage},
      );
      final data = response.data as Map<String, dynamic>;
      if (data['success'] != true) return [];
      final inner = data['data'];
      final list = inner is Map ? inner['transactions'] : null;
      if (list is! List) return [];
      return list
          .map((e) => e is Map<String, dynamic>
              ? WalletTransactionModel.fromJson(e)
              : null)
          .whereType<WalletTransactionModel>()
          .toList();
    } on DioException {
      return [];
    } catch (_) {
      return [];
    }
  }

  /// إرسال طلب حوالة (مبلغ، عملة، رقم إيصال، صورة إيصال، ملاحظة اختيارية).
  /// [receiptImageBytes] للويب والموبايل (مُفضّل). [receiptImagePath] للموبايل فقط عند عدم تمرير bytes.
  static Future<Map<String, String>> submitHawalaTransfer({
    required num amount,
    required String currency,
    required String receiptNumber,
    String? receiptImagePath,
    List<int>? receiptImageBytes,
    String? note,
  }) async {
    if (!TokenStorage.hasToken()) {
      return {'success': 'false', 'message': 'يجب تسجيل الدخول'};
    }
    if (receiptImageBytes == null && (receiptImagePath == null || receiptImagePath.isEmpty)) {
      return {'success': 'false', 'message': 'صورة الإيصال مطلوبة'};
    }
    try {
      final MultipartFile filePart = receiptImageBytes != null
          ? MultipartFile.fromBytes(
              receiptImageBytes,
              filename: 'receipt.jpg',
            )
          : await MultipartFile.fromFile(
              receiptImagePath!,
              filename: 'receipt.jpg',
            );
      final map = <String, dynamic>{
        'amount': amount,
        'currency': currency,
        'receipt_number': receiptNumber,
        'receipt_image': filePart,
      };
      if (note != null && note.trim().isNotEmpty) {
        map['note'] = note.trim();
      }
      final formData = FormData.fromMap(map);
      final response = await ApiClient.dio.post(
        ApiConstants.hawalaTransfers,
        data: formData,
      );
      final data = response.data as Map<String, dynamic>;
      final success = data['success'] == true;
      return {
        'success': success.toString(),
        'message': (data['message'] as String?) ?? (success ? 'تم إرسال الطلب' : 'فشل الإرسال'),
      };
    } on DioException catch (e) {
      final msg = e.response?.data is Map
          ? (e.response!.data as Map)['message'] as String?
          : null;
      return {'success': 'false', 'message': msg ?? 'فشل إرسال طلب الحوالة'};
    } catch (_) {
      return {'success': 'false', 'message': 'حدث خطأ في الاتصال'};
    }
  }
}
