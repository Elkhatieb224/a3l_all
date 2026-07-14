import 'package:a3lnha/helpers/localized_name.dart';

class PackageModel {
  final int id;
  final String name;
  final String? nameAr;
  final String? nameEn;
  final String? nameTr;
  final String? description;
  final num price;
  final String? currency;
  final String? formattedPrice;
  /// Legacy API fields (credit-based packages omit these).
  final int durationDays;
  final int adsLimit;
  final bool featuredAds;
  /// Credits granted per purchase for featured activations.
  final int featuredAdsLimit;
  /// Days each featured activation lasts once applied to an ad.
  final int featuredDurationDays;
  final bool urgentAds;
  final int urgentAdsLimit;
  final int urgentDurationDays;
  final bool prioritySupport;
  final bool homepageDisplay;
  final List<String> features;
  final bool canActivateNow;
  final num? walletBalance;
  final num? requiredAmount;
  final num? missingAmount;

  PackageModel({
    required this.id,
    required this.name,
    this.nameAr,
    this.nameEn,
    this.nameTr,
    this.description,
    required this.price,
    this.currency,
    this.formattedPrice,
    this.durationDays = 0,
    this.adsLimit = 0,
    this.featuredAds = false,
    this.featuredAdsLimit = 0,
    this.featuredDurationDays = 7,
    this.urgentAds = false,
    this.urgentAdsLimit = 0,
    this.urgentDurationDays = 7,
    this.prioritySupport = false,
    this.homepageDisplay = false,
    this.features = const [],
    this.canActivateNow = false,
    this.walletBalance,
    this.requiredAmount,
    this.missingAmount,
  });

  factory PackageModel.fromJson(Map<String, dynamic> json) {
    final featuresRaw = json['features'];
    List<String> featuresList = [];
    if (featuresRaw is List) {
      for (final f in featuresRaw) {
        if (f != null) featuresList.add(f.toString());
      }
    }
    return PackageModel(
      id: json['id'] as int,
      name: json['name'] as String? ?? '',
      nameAr: json['name_ar'] as String?,
      nameEn: json['name_en'] as String?,
      nameTr: json['name_tr'] as String?,
      description: json['description'] as String?,
      price: (json['price'] as num?) ?? 0,
      currency: json['currency'] as String?,
      formattedPrice: json['formatted_price'] as String?,
      durationDays: json['duration_days'] as int? ?? 0,
      adsLimit: json['ads_limit'] as int? ?? 0,
      featuredAds: json['featured_ads'] == true,
      featuredAdsLimit: json['featured_ads_limit'] is int
          ? json['featured_ads_limit'] as int
          : int.tryParse(json['featured_ads_limit']?.toString() ?? '0') ?? 0,
      featuredDurationDays: json['featured_duration_days'] is int
          ? json['featured_duration_days'] as int
          : int.tryParse(json['featured_duration_days']?.toString() ?? '7') ?? 7,
      urgentAds: json['urgent_ads'] == true,
      urgentAdsLimit: json['urgent_ads_limit'] is int
          ? json['urgent_ads_limit'] as int
          : int.tryParse(json['urgent_ads_limit']?.toString() ?? '0') ?? 0,
      urgentDurationDays: json['urgent_duration_days'] is int
          ? json['urgent_duration_days'] as int
          : int.tryParse(json['urgent_duration_days']?.toString() ?? '7') ?? 7,
      prioritySupport: json['priority_support'] == true,
      homepageDisplay: json['homepage_display'] == true,
      features: featuresList,
      canActivateNow: json['can_activate_now'] == true,
      walletBalance: json['wallet_balance'] as num?,
      requiredAmount: json['required_amount'] as num?,
      missingAmount: json['missing_amount'] as num?,
    );
  }

  /// الاسم حسب اللغة الحالية
  String get displayName => getLocalizedName(
        nameAr: nameAr,
        nameEn: nameEn,
        nameTr: nameTr,
        defaultName: name,
      );

}
