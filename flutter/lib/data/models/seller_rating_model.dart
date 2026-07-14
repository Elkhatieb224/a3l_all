class SellerRatingModel {
  final int id;
  final int rating;
  final String? comment;
  final String? createdAt;
  final Map<String, dynamic>? user;

  SellerRatingModel({
    required this.id,
    required this.rating,
    this.comment,
    this.createdAt,
    this.user,
  });

  String get userName => user?['name'] as String? ?? 'مستخدم';
  String? get userAvatar => user?['avatar'] as String?;

  factory SellerRatingModel.fromJson(Map<String, dynamic> json) {
    return SellerRatingModel(
      id: json['id'] as int,
      rating: json['rating'] as int,
      comment: json['comment'] as String?,
      createdAt: json['created_at'] as String?,
      user: json['user'] as Map<String, dynamic>?,
    );
  }
}
