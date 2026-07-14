import 'package:dio/dio.dart';

import 'package:a3lnha/core/network/api_client.dart';
import 'package:a3lnha/core/network/api_constants.dart';
import 'package:a3lnha/core/performance/ttl_memory_cache.dart';
import 'package:a3lnha/core/storage/token_storage.dart';
import 'package:a3lnha/data/models/package_model.dart';

class ActiveSubscriptionInfo {
  final int packageId;
  final String packageName;
  final String expiresAt;
  final int adsUsed;
  final int adsLimit;

  ActiveSubscriptionInfo({
    required this.packageId,
    required this.packageName,
    required this.expiresAt,
    required this.adsUsed,
    required this.adsLimit,
  });
}

class ActivePackageInfo {
  final int subscriptionId;
  final int packageId;
  final String packageName;
  final String? expiresAt;

  ActivePackageInfo({
    required this.subscriptionId,
    required this.packageId,
    required this.packageName,
    this.expiresAt,
  });
}

/// سطر واحد من رصيد الباقات المشتراة (دُفعات أرصدة تفعيل).
class CreditBatchInfo {
  final int batchId;
  final int packageId;
  final String packageName;
  final int featuredCreditsRemaining;
  final int urgentCreditsRemaining;

  CreditBatchInfo({
    required this.batchId,
    required this.packageId,
    required this.packageName,
    required this.featuredCreditsRemaining,
    required this.urgentCreditsRemaining,
  });

  factory CreditBatchInfo.fromJson(Map<String, dynamic> json) {
    return CreditBatchInfo(
      batchId: json['batch_id'] is int ? json['batch_id'] as int : int.tryParse(json['batch_id']?.toString() ?? '0') ?? 0,
      packageId: json['package_id'] is int ? json['package_id'] as int : int.tryParse(json['package_id']?.toString() ?? '0') ?? 0,
      packageName: json['package_name']?.toString() ?? '',
      featuredCreditsRemaining: json['featured_credits_remaining'] is int
          ? json['featured_credits_remaining'] as int
          : int.tryParse(json['featured_credits_remaining']?.toString() ?? '0') ?? 0,
      urgentCreditsRemaining: json['urgent_credits_remaining'] is int
          ? json['urgent_credits_remaining'] as int
          : int.tryParse(json['urgent_credits_remaining']?.toString() ?? '0') ?? 0,
    );
  }
}

/// تفاصيل الخطة الحالية (باقة أو مجانية): الحدود المتبقية والمميزات — كما في الويب
class CurrentPlanInfo {
  final String planName;
  final int remainingAds;
  final int adsLimit;
  /// عند true: الإعلانات العادية غير محدودة العدد (لا يُعرض كسر كسابق).
  final bool unlimitedRegularAds;
  final int remainingFeatured;
  final int featuredLimit;
  final int remainingUrgent;
  final int urgentLimit;
  final String? expiresAt;
  final List<String> features;

  CurrentPlanInfo({
    required this.planName,
    required this.remainingAds,
    required this.adsLimit,
    this.unlimitedRegularAds = false,
    this.remainingFeatured = 0,
    this.featuredLimit = 0,
    this.remainingUrgent = 0,
    this.urgentLimit = 0,
    this.expiresAt,
    this.features = const [],
  });

  static CurrentPlanInfo? fromJson(Map<String, dynamic>? json) {
    if (json == null) return null;
    final planName = json['plan_name'] as String? ?? '';
    final remainingAds = json['remaining_ads'] is int ? json['remaining_ads'] as int : int.tryParse(json['remaining_ads']?.toString() ?? '0') ?? 0;
    final adsLimit = json['ads_limit'] is int ? json['ads_limit'] as int : int.tryParse(json['ads_limit']?.toString() ?? '0') ?? 0;
    final unlimitedRegular =
        json['unlimited_regular_ads'] == true || adsLimit >= 999999;
    final remainingFeatured = json['remaining_featured'] is int ? json['remaining_featured'] as int : int.tryParse(json['remaining_featured']?.toString() ?? '0') ?? 0;
    final featuredLimit = json['featured_limit'] is int ? json['featured_limit'] as int : int.tryParse(json['featured_limit']?.toString() ?? '0') ?? 0;
    final remainingUrgent = json['remaining_urgent'] is int ? json['remaining_urgent'] as int : int.tryParse(json['remaining_urgent']?.toString() ?? '0') ?? 0;
    final urgentLimit = json['urgent_limit'] is int ? json['urgent_limit'] as int : int.tryParse(json['urgent_limit']?.toString() ?? '0') ?? 0;
    final expiresAt = json['expires_at'] as String?;
    List<String> features = [];
    final flist = json['features'];
    if (flist is List) {
      for (final f in flist) {
        if (f != null && f.toString().trim().isNotEmpty) features.add(f.toString());
      }
    }
    return CurrentPlanInfo(
      planName: planName,
      remainingAds: remainingAds,
      adsLimit: adsLimit,
      unlimitedRegularAds: unlimitedRegular,
      remainingFeatured: remainingFeatured,
      featuredLimit: featuredLimit,
      remainingUrgent: remainingUrgent,
      urgentLimit: urgentLimit,
      expiresAt: expiresAt,
      features: features,
    );
  }
}

class PackagesResponse {
  final List<PackageModel> packages;
  final int? remainingFreeAds;
  final int? freeAdsLimit;
  final bool hasActiveSubscription;
  final ActiveSubscriptionInfo? activeSubscription;
  final List<ActivePackageInfo> activeSubscriptions;
  final CurrentPlanInfo? currentPlan;
  final int featuredCreditsRemaining;
  final int urgentCreditsRemaining;
  final List<CreditBatchInfo> creditBatches;

  PackagesResponse({
    required this.packages,
    this.remainingFreeAds,
    this.freeAdsLimit,
    this.hasActiveSubscription = false,
    this.activeSubscription,
    this.activeSubscriptions = const [],
    this.currentPlan,
    this.featuredCreditsRemaining = 0,
    this.urgentCreditsRemaining = 0,
    this.creditBatches = const [],
  });
}

class PackageService {
  PackageService._();

  static const Duration _packagesCacheTtl = Duration(minutes: 5);
  static const _packagesCacheKey = 'packages.list';

  /// قراءة الباقات من الكاش فقط (فوري للعرض عند فتح الصفحة).
  static PackagesResponse? getCachedPackages() {
    return TtlMemoryCache.getIfValid<PackagesResponse>(_packagesCacheKey);
  }

  static Future<PackagesResponse> getPackages({bool forceRefresh = false}) async {
    if (!forceRefresh) {
      final cached = TtlMemoryCache.getIfValid<PackagesResponse>(_packagesCacheKey);
      if (cached != null) return cached;
    }
    try {
      final response = await ApiClient.dio.get(ApiConstants.packages);
      final data = response.data as Map<String, dynamic>;
      if (data['success'] != true) {
        return PackagesResponse(packages: []);
      }
      final inner = data['data'];
      if (inner is! Map<String, dynamic>) {
        return PackagesResponse(packages: []);
      }
      List<PackageModel> list = [];
      final packs = inner['packages'];
      if (packs is List) {
        for (final p in packs) {
          if (p is Map<String, dynamic>) {
            try {
              list.add(PackageModel.fromJson(p));
            } catch (_) {}
          }
        }
      }
      int? remaining;
      int? limit;
      bool hasSub = false;
      ActiveSubscriptionInfo? activeSub;
      final activeSubs = <ActivePackageInfo>[];
      var featuredCredits = 0;
      var urgentCredits = 0;
      final batches = <CreditBatchInfo>[];
      final stats = inner['user_stats'];
      if (stats is Map<String, dynamic>) {
        remaining = stats['remaining_free_ads'] as int?;
        limit = stats['free_ads_limit'] as int?;
        hasSub = stats['has_active_subscription'] == true ||
            stats['has_package_credits'] == true;
        featuredCredits = stats['featured_credits_remaining'] is int
            ? stats['featured_credits_remaining'] as int
            : int.tryParse(stats['featured_credits_remaining']?.toString() ?? '0') ?? 0;
        urgentCredits = stats['urgent_credits_remaining'] is int
            ? stats['urgent_credits_remaining'] as int
            : int.tryParse(stats['urgent_credits_remaining']?.toString() ?? '0') ?? 0;
        final cb = stats['credit_batches'];
        if (cb is List) {
          for (final e in cb) {
            if (e is Map<String, dynamic>) {
              batches.add(CreditBatchInfo.fromJson(e));
            }
          }
        }
        final activeSubData = stats['active_subscription'];
        if (activeSubData is Map<String, dynamic>) {
          final pid = activeSubData['package_id'];
          final pname = activeSubData['package_name'];
          final exp = activeSubData['expires_at'];
          final used = activeSubData['ads_used'];
          final lim = activeSubData['ads_limit'];
          if (pid != null && pname != null && exp != null && used != null && lim != null) {
            activeSub = ActiveSubscriptionInfo(
              packageId: pid is int ? pid : int.tryParse(pid.toString()) ?? 0,
              packageName: pname.toString(),
              expiresAt: exp.toString(),
              adsUsed: used is int ? used : int.tryParse(used.toString()) ?? 0,
              adsLimit: lim is int ? lim : int.tryParse(lim.toString()) ?? 0,
            );
          }
        }
        final activeSubsData = stats['active_subscriptions'];
        if (activeSubsData is List) {
          for (final item in activeSubsData) {
            if (item is! Map<String, dynamic>) continue;
            activeSubs.add(
              ActivePackageInfo(
                subscriptionId: item['subscription_id'] is int
                    ? item['subscription_id'] as int
                    : int.tryParse(item['subscription_id']?.toString() ?? '0') ?? 0,
                packageId: item['package_id'] is int
                    ? item['package_id'] as int
                    : int.tryParse(item['package_id']?.toString() ?? '0') ?? 0,
                packageName: item['package_name']?.toString() ?? '',
                expiresAt: item['expires_at']?.toString(),
              ),
            );
          }
        }
      }
      final currentPlan = CurrentPlanInfo.fromJson(inner['current_plan'] is Map<String, dynamic> ? inner['current_plan'] as Map<String, dynamic> : null);
      final result = PackagesResponse(
        packages: list,
        remainingFreeAds: remaining,
        freeAdsLimit: limit,
        hasActiveSubscription: hasSub,
        activeSubscription: activeSub,
        activeSubscriptions: activeSubs,
        currentPlan: currentPlan,
        featuredCreditsRemaining: featuredCredits,
        urgentCreditsRemaining: urgentCredits,
        creditBatches: batches,
      );
      // عدم تخزين الاستجابة الفارغة في الكاش حتى لا يبقى التطبيق يعرض "لا توجد باقات" بعد نجاح الـ API لاحقاً
      if (list.isNotEmpty) {
        TtlMemoryCache.set<PackagesResponse>(_packagesCacheKey, result, _packagesCacheTtl);
      }
      return result;
    } on DioException {
      rethrow;
    } catch (_) {
      rethrow;
    }
  }

  /// جلب تفاصيل طلب باقة واحد (لعرض رد الإدارة).
  static Future<Map<String, dynamic>?> getPackageRequest(int id) async {
    if (!TokenStorage.hasToken()) return null;
    try {
      final response = await ApiClient.dio.get('${ApiConstants.packageRequests}/$id');
      final data = response.data as Map<String, dynamic>;
      if (data['success'] != true) return null;
      final inner = data['data'];
      return inner is Map<String, dynamic> ? inner : null;
    } catch (_) {
      return null;
    }
  }

  /// تفعيل باقة مباشرة عند كفاية الرصيد.
  static Future<Map<String, dynamic>> requestPackage(int packageId) async {
    if (!TokenStorage.hasToken()) {
      return {'success': false, 'message': 'يجب تسجيل الدخول لطلب الباقة'};
    }
    try {
      final response = await ApiClient.dio.post(
        '${ApiConstants.packages}/$packageId/request',
      );
      final data = response.data as Map<String, dynamic>;
      return {
        'success': data['success'] == true,
        'message': data['message'] as String? ?? 'تم تفعيل الباقة بنجاح',
        'action': data['action'],
        'required_amount': data['required_amount'],
        'wallet_balance': data['wallet_balance'],
        'missing_amount': data['missing_amount'],
      };
    } on DioException catch (e) {
      if (e.response?.data is Map) {
        final m = Map<String, dynamic>.from(e.response!.data as Map);
        return {
          'success': false,
          'message': m['message'] as String? ?? 'فشل تفعيل الباقة',
          'action': m['action'],
          'required_amount': m['required_amount'],
          'wallet_balance': m['wallet_balance'],
          'missing_amount': m['missing_amount'],
        };
      }
      return {'success': false, 'message': 'فشل تفعيل الباقة'};
    }
  }
}
