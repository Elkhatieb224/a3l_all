class SellerModel {
  final int id;
  final String name;
  final String slug;
  final String? avatar;
  final String? bio;
  final String? businessType;
  final String? locationCountry;
  final String? locationCity;
  final String? businessAddress;
  final String? phone;
  final String? countryCode;
  final bool isVerified;
  final String? storefrontImagePath;
  final String? instagramUrl;
  final String? facebookUrl;
  final String? websiteUrl;
  final int adsCount;
  final double averageRating;
  final int ratingsCount;
  final int followersCount;
  final int followingCount;
  final String? memberSince;
  final String? createdAt;
  final bool isFavorite;
  final bool isMe;
  final bool hasBlocked;

  SellerModel({
    required this.id,
    required this.name,
    required this.slug,
    this.avatar,
    this.bio,
    this.businessType,
    this.locationCountry,
    this.locationCity,
    this.businessAddress,
    this.phone,
    this.countryCode,
    this.isVerified = false,
    this.storefrontImagePath,
    this.instagramUrl,
    this.facebookUrl,
    this.websiteUrl,
    this.adsCount = 0,
    this.averageRating = 0,
    this.ratingsCount = 0,
    this.followersCount = 0,
    this.followingCount = 0,
    this.memberSince,
    this.createdAt,
    this.isFavorite = false,
    this.isMe = false,
    this.hasBlocked = false,
  });

  factory SellerModel.fromJson(Map<String, dynamic> json) {
    return SellerModel(
      id: json['id'] as int,
      name: json['name'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
      avatar: json['avatar'] as String?,
      bio: json['bio'] as String?,
      businessType: json['business_type'] as String?,
      locationCountry: json['location_country'] as String?,
      locationCity: json['location_city'] as String?,
      businessAddress: json['business_address'] as String?,
      phone: json['phone'] as String?,
      countryCode: json['country_code'] as String?,
      isVerified: json['is_verified'] as bool? ?? false,
      storefrontImagePath: json['storefront_image_path'] as String?,
      instagramUrl: json['instagram_url'] as String?,
      facebookUrl: json['facebook_url'] as String?,
      websiteUrl: json['website_url'] as String?,
      adsCount: json['ads_count'] as int? ?? 0,
      averageRating: (json['average_rating'] as num?)?.toDouble() ?? 0,
      ratingsCount: json['ratings_count'] as int? ?? 0,
      followersCount: json['followers_count'] as int? ?? 0,
      followingCount: json['following_count'] as int? ?? 0,
      memberSince: json['member_since']?.toString(),
      createdAt: json['created_at'] as String?,
      isFavorite: json['is_favorite'] as bool? ?? false,
      isMe: json['is_me'] as bool? ?? false,
      hasBlocked: json['has_blocked'] as bool? ?? false,
    );
  }
}
