import 'package:dio/dio.dart';

import 'package:a3lnha/core/network/api_client.dart';
import 'package:a3lnha/core/network/api_constants.dart';
import 'package:a3lnha/data/models/ad_model.dart';

String _fullImageUrl(String? raw) {
  if (raw == null || raw.trim().isEmpty) return '';
  final v = raw.trim();
  if (v.startsWith('http://') || v.startsWith('https://')) return v;
  final base = ApiConstants.baseUrl.replaceFirst(RegExp(r'/api.*'), '');
  if (v.startsWith('/')) return '$base$v';
  return '$base/storage/$v';
}

class ConversationModel {
  final int id;
  final int? adId;
  final String? lastMessageAt;
  final int unreadCount;
  final Map<String, dynamic>? ad;
  final Map<String, dynamic>? otherUser;
  final Map<String, dynamic>? latestMessage;

  ConversationModel({
    required this.id,
    this.adId,
    this.lastMessageAt,
    this.unreadCount = 0,
    this.ad,
    this.otherUser,
    this.latestMessage,
  });

  factory ConversationModel.fromJson(Map<String, dynamic> json) {
    return ConversationModel(
      id: json['id'] as int,
      adId: json['ad_id'] as int?,
      lastMessageAt: json['last_message_at'] as String?,
      unreadCount: json['unread_count'] as int? ?? 0,
      ad: json['ad'] is Map ? Map<String, dynamic>.from(json['ad'] as Map) : null,
      otherUser: json['other_user'] is Map ? Map<String, dynamic>.from(json['other_user'] as Map) : null,
      latestMessage: json['latest_message'] is Map ? Map<String, dynamic>.from(json['latest_message'] as Map) : null,
    );
  }
}

class MessageService {
  MessageService._();

  static Future<List<ConversationModel>> getConversations({int page = 1}) async {
    try {
      final response = await ApiClient.dio.get(
        ApiConstants.messages,
        queryParameters: {'page': page, 'per_page': 20},
      );
      final data = response.data as Map<String, dynamic>;
      if (data['success'] != true) return [];
      dynamic raw = data['data'];
      if (raw is Map && raw['data'] != null) raw = raw['data'];
      if (raw is! List) return [];
      return (raw as List)
          .map((e) => e is Map<String, dynamic> ? ConversationModel.fromJson(e) : null)
          .whereType<ConversationModel>()
          .toList();
    } on DioException {
      return [];
    }
  }

  static Future<int?> createOrGetConversation(String adUid) async {
    try {
      final response = await ApiClient.dio.post(
        '${ApiConstants.messages}/create/$adUid',
      );
      final data = response.data as Map<String, dynamic>;
      if (data['success'] == true && data['data'] != null) {
        final conv = data['data'] as Map<String, dynamic>;
        return conv['id'] as int?;
      }
      return null;
    } on DioException {
      return null;
    }
  }

  static Future<int?> createOrGetConversationWithSeller(String sellerSlug) async {
    try {
      final response = await ApiClient.dio.post(
        '${ApiConstants.messages}/create/seller/$sellerSlug',
      );
      final data = response.data as Map<String, dynamic>;
      if (data['success'] == true && data['data'] != null) {
        final conv = data['data'] as Map<String, dynamic>;
        return conv['id'] as int?;
      }
      return null;
    } on DioException {
      return null;
    }
  }

  static Future<ChatData?> getConversation(int conversationId) async {
    try {
      final response = await ApiClient.dio.get(
        '${ApiConstants.messages}/$conversationId',
      );
      final data = response.data as Map<String, dynamic>;
      if (data['success'] != true) return null;
      final inner = data['data'] as Map<String, dynamic>?;
      if (inner == null) return null;
      final conv = inner['conversation'];
      final msgs = inner['messages'];
      final adRaw = conv is Map ? conv['ad'] : null;
      final ad = adRaw is Map ? Map<String, dynamic>.from(adRaw) : null;
      final messagingRules = inner['messaging_rules'] as String? ?? '';
      List<MessageModel> list = [];
      dynamic msgList = msgs;
      if (msgs is Map && msgs['data'] != null) msgList = msgs['data'];
      if (msgList is List) {
        for (final m in msgList) {
          if (m is Map<String, dynamic>) list.add(MessageModel.fromJson(m));
        }
      }
      final otherUserRaw = conv is Map ? conv['other_user'] : null;
      final otherUser = otherUserRaw is Map
          ? Map<String, dynamic>.from(otherUserRaw as Map)
          : null;
      final isOtherUserBlocked =
          conv is Map ? (conv['is_other_user_blocked'] as bool? ?? false) : false;
      return ChatData(
        adUid: ad?['uid'] as String?,
        adTitle: ad?['title'] as String?,
        ad: ad != null ? _adMapToAdModel(ad) : null,
        otherUserId: otherUser?['id'] as int?,
        otherUserName: otherUser?['name'] as String?,
        isOtherUserBlocked: isOtherUserBlocked,
        messagingRules: messagingRules.trim().isNotEmpty ? messagingRules : null,
        messages: list,
      );
    } on DioException {
      return null;
    }
  }

  static AdModel? _adMapToAdModel(Map<String, dynamic> ad) {
    try {
      final images = ad['images'];
      List<String> imgList = [];
      if (images is List) {
        for (final x in images) {
          String? url;
          if (x is String) url = x;
          else if (x is Map && (x['url'] != null || x['path'] != null))
            url = (x['url'] ?? x['path']).toString();
          else if (x != null) url = x.toString();
          if (url != null && url.trim().isNotEmpty)
            imgList.add(_fullImageUrl(url));
        }
      }
      if (imgList.isEmpty && ad['first_image'] != null)
        imgList.add(_fullImageUrl(ad['first_image'].toString()));
      if (imgList.isEmpty && ad['image'] != null)
        imgList.add(_fullImageUrl(ad['image'].toString()));
      return AdModel(
        id: ad['id'] as int? ?? 0,
        uid: ad['uid'] as String? ?? '',
        title: ad['title'] as String? ?? '',
        description: null,
        price: ad['price'] as num?,
        currency: ad['currency'] as String?,
        formattedPrice: ad['formatted_price'] as String?,
        images: imgList,
        locationState: ad['location_state'] as String?,
        locationCity: ad['location_city'] as String?,
        locationDistrict: ad['location_district'] as String?,
        locationAddress: null,
        locationCountry: null,
        showLocation: true,
        isFavorite: false,
      );
    } catch (_) {
      return null;
    }
  }

  static Future<MessageModel?> sendMessage(int conversationId, String message) async {
    try {
      final response = await ApiClient.dio.post(
        '${ApiConstants.messages}/$conversationId',
        data: {'message': message},
      );
      final data = response.data as Map<String, dynamic>;
      if (data['success'] == true && data['data'] != null) {
        return MessageModel.fromJson(data['data'] as Map<String, dynamic>);
      }
      return null;
    } on DioException {
      return null;
    }
  }
}

class ChatData {
  final String? adUid;
  final String? adTitle;
  final AdModel? ad;
  final int? otherUserId;
  final String? otherUserName;
  final bool isOtherUserBlocked;
  final String? messagingRules;
  final List<MessageModel> messages;

  ChatData({
    this.adUid,
    this.adTitle,
    this.ad,
    this.otherUserId,
    this.otherUserName,
    this.isOtherUserBlocked = false,
    this.messagingRules,
    required this.messages,
  });
}

class MessageModel {
  final int id;
  final String message;
  final int senderId;
  final String? createdAt;
  final Map<String, dynamic>? sender;
  final bool isRead;
  final String? readAt;

  MessageModel({
    required this.id,
    required this.message,
    required this.senderId,
    this.createdAt,
    this.sender,
    this.isRead = false,
    this.readAt,
  });

  factory MessageModel.fromJson(Map<String, dynamic> json) {
    return MessageModel(
      id: json['id'] as int,
      message: json['message'] as String? ?? '',
      senderId: json['sender_id'] as int? ?? 0,
      createdAt: json['created_at'] as String?,
      sender: json['sender'] is Map ? Map<String, dynamic>.from(json['sender'] as Map) : null,
      isRead: json['is_read'] as bool? ?? false,
      readAt: json['read_at']?.toString(),
    );
  }
}
