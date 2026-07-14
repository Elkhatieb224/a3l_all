/// نموذج المستخدم من API
class UserModel {
  final int id;
  final String name;
  final String? slug;
  final String email;
  final String? phone;
  final String? countryCode;
  final String? businessName;
  final String? businessType;
  final String? businessOwner;
  final String? businessAddress;
  final String? businessPhone;
  final String? instagramUrl;
  final String? facebookUrl;
  final String? websiteUrl;
  final String? storefrontImage;
  final String? avatar;
  final String? bio;
  final String? locationCountry;
  final String? locationCity;
  final String? locationDistrict;
  final bool isVerified;
  final bool isActive;
  final String? emailVerifiedAt;
  final String? phoneVerifiedAt;
  final String? lastLoginAt;
  final String? createdAt;
  final String? updatedAt;
  final int? remainingFreeAds;
  final bool? hasActiveSubscription;

  UserModel({
    required this.id,
    required this.name,
    this.slug,
    required this.email,
    this.phone,
    this.countryCode,
    this.businessName,
    this.businessType,
    this.businessOwner,
    this.businessAddress,
    this.businessPhone,
    this.instagramUrl,
    this.facebookUrl,
    this.websiteUrl,
    this.storefrontImage,
    this.avatar,
    this.bio,
    this.locationCountry,
    this.locationCity,
    this.locationDistrict,
    this.isVerified = false,
    this.isActive = true,
    this.emailVerifiedAt,
    this.phoneVerifiedAt,
    this.lastLoginAt,
    this.createdAt,
    this.updatedAt,
    this.remainingFreeAds,
    this.hasActiveSubscription,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'] as int,
      name: json['name'] as String,
      slug: json['slug'] as String?,
      email: json['email'] as String,
      phone: json['phone'] as String?,
      countryCode: json['country_code'] as String?,
      businessName: json['business_name'] as String?,
      businessType: json['business_type'] as String?,
      businessOwner: json['business_owner'] as String?,
      businessAddress: json['business_address'] as String?,
      businessPhone: json['business_phone'] as String?,
      instagramUrl: json['instagram_url'] as String?,
      facebookUrl: json['facebook_url'] as String?,
      websiteUrl: json['website_url'] as String?,
      storefrontImage: json['storefront_image'] as String?,
      avatar: json['avatar'] as String?,
      bio: json['bio'] as String?,
      locationCountry: json['location_country'] as String?,
      locationCity: json['location_city'] as String?,
      locationDistrict: json['location_district'] as String?,
      isVerified: json['is_verified'] as bool? ?? false,
      isActive: json['is_active'] as bool? ?? true,
      emailVerifiedAt: json['email_verified_at'] as String?,
      phoneVerifiedAt: json['phone_verified_at'] as String?,
      lastLoginAt: json['last_login_at'] as String?,
      createdAt: json['created_at'] as String?,
      updatedAt: json['updated_at'] as String?,
      remainingFreeAds: json['remaining_free_ads'] as int?,
      hasActiveSubscription: json['has_active_subscription'] as bool?,
    );
  }
}
