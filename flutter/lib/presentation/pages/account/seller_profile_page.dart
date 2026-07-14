import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/presentation/widgets/shared/favorite_icon_button.dart';
import 'package:a3lnha/data/models/ad_model.dart';
import 'package:a3lnha/data/models/seller_model.dart';
import 'package:a3lnha/data/models/seller_rating_model.dart';
import 'package:a3lnha/data/services/blocked_user_service.dart';
import 'package:a3lnha/data/services/favorite_seller_service.dart';
import 'package:a3lnha/core/data/location_translations.dart';
import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/data/services/seller_service.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/core/network/api_constants.dart';
import 'package:a3lnha/core/storage/token_storage.dart';
import 'package:a3lnha/presentation/pages/account/chat_page.dart';
import 'package:a3lnha/presentation/pages/account/share_profile_page.dart';
import 'package:a3lnha/presentation/pages/auth/login_page.dart';
import 'package:a3lnha/presentation/pages/home/ad_details_page.dart';
import 'package:a3lnha/presentation/widgets/auth/text_form_with_label.dart';
import 'package:a3lnha/presentation/widgets/shared/app_network_image.dart';
import 'package:a3lnha/presentation/widgets/ad_list_location_label.dart';
import 'package:a3lnha/presentation/widgets/ad_status_badge_icon.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_button.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:url_launcher/url_launcher.dart';


class SellerProfilePage extends StatefulWidget {
  final String sellerSlug;

  const SellerProfilePage({super.key, required this.sellerSlug});

  @override
  State<SellerProfilePage> createState() => _SellerProfilePageState();
}

class _SellerProfilePageState extends State<SellerProfilePage> {
  SellerModel? _seller;
  List<AdModel> _ads = [];
  List<SellerRatingModel> _ratings = [];
  SellerRatingModel? _userRating;
  bool _loading = true;
  bool _isFavoriteSeller = false;
  int _followersCount = 0;
  int _followingCount = 0;
  String? _error;
  final TextEditingController _commentController = TextEditingController();
  int _selectedRating = 0;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _commentController.dispose();
    super.dispose();
  }

  Future<void> _submitRating() async {
    if (!TokenStorage.hasToken()) {
      showToast(message: AppLocale.tr('login_required'));
      context.push(LoginPage());
      return;
    }
    if (_selectedRating < 1) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(AppLocale.tr('choose_rating'))),
      );
      return;
    }
    final result = await SellerService.rateSeller(
      slug: widget.sellerSlug,
      rating: _selectedRating,
      comment: _commentController.text.trim().isEmpty
          ? null
          : _commentController.text.trim(),
    );
    if (!mounted) return;
    if (result.success) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.message ?? AppLocale.tr('rating_sent'))),
      );
      _load();
    } else {
      if (result.unauthorized) {
        showToast(message: AppLocale.tr('session_expired_login_again'));
        context.push(LoginPage());
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            result.message?.trim().isNotEmpty == true
                ? result.message!
                : AppLocale.tr('rating_failed'),
          ),
        ),
      );
    }
  }

  Future<void> _blockSeller(int sellerId) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(AppLocale.tr('block_seller')),
        content: Text(
          AppLocale.tr('block_seller_confirm'),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(AppLocale.tr('cancel')),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(AppLocale.tr('block'), style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
    if (confirm != true || !mounted) return;
    final res = await BlockedUserService.blockUser(sellerId);
    if (!mounted) return;
    showToast(message: res['message'] as String? ?? '');
    if (res['success'] == true) {
      SellerService.invalidateProfile(widget.sellerSlug);
      _load(forceRefresh: true);
    }
  }

  Future<void> _unblockSeller(int sellerId) async {
    final res = await BlockedUserService.unblockUser(sellerId);
    if (!mounted) return;
    showToast(message: res['message'] as String? ?? '');
    if (res['success'] == true) {
      SellerService.invalidateProfile(widget.sellerSlug);
      _load(forceRefresh: true);
    }
  }

  Future<void> _toggleFollowSeller() async {
    if (!TokenStorage.hasToken()) {
      showToast(message: AppLocale.tr('login_required'));
      return;
    }
    final prevFav = _isFavoriteSeller;
    final optimisticFav = !prevFav;

    // Optimistic UI: update immediately.
    setState(() {
      _isFavoriteSeller = optimisticFav;
      _followersCount = (_followersCount + (optimisticFav ? 1 : -1)).clamp(0, 1 << 30);
    });

    final result = await FavoriteSellerService.toggle(widget.sellerSlug);
    if (!mounted) return;

    if (result != null && result['success'] == true) {
      final serverFav = result['isFavorite'] == true;
      if (serverFav != _isFavoriteSeller) {
        // Server disagrees; reconcile counts.
        setState(() {
          _isFavoriteSeller = serverFav;
          _followersCount = (_followersCount + (serverFav ? 1 : -1)).clamp(0, 1 << 30);
        });
      }
      showToast(message: serverFav ? AppLocale.tr('followed_seller') : AppLocale.tr('unfollowed_seller'));
    } else {
      // Revert optimistic change on failure.
      setState(() {
        _isFavoriteSeller = prevFav;
        _followersCount = (_followersCount + (prevFav ? 1 : -1)).clamp(0, 1 << 30);
      });
      showToast(message: AppLocale.tr('failed'));
    }
  }

  Future<void> _shareSellerProfile() async {
    final seller = _seller;
    if (seller == null) return;
    final origin = ApiConstants.webOrigin;
    final path = '/seller/${seller.slug}';
    final link = origin.endsWith('/') ? '$origin${path.substring(1)}' : '$origin$path';
    if (!mounted) return;
    await context.push(
      ShareProfilePage(
        userName: seller.name,
        profileUrl: link,
      ),
    );
  }

  Future<void> _load({bool forceRefresh = false}) async {
    setState(() {
      _loading = true;
      _error = null;
    });

    final response = await SellerService.getSellerProfile(widget.sellerSlug, forceRefresh: forceRefresh);

    if (!mounted) return;

    if (response != null) {
      setState(() {
        _seller = response.seller;
        _isFavoriteSeller = response.seller.isFavorite;
        _followersCount = response.seller.followersCount;
        _followingCount = response.seller.followingCount;
        _ads = response.ads;
        _ratings = response.ratings;
        _userRating = response.userRating;
        _selectedRating = response.userRating?.rating ?? 0;
        _commentController.text = response.userRating?.comment ?? '';
        _loading = false;
      });
    } else {
      setState(() {
        _loading = false;
        _error = AppLocale.tr('profile_not_found');
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return Scaffold(
        appBar: CustomAppbar(title: AppLocale.tr('seller_profile')),
        body: Center(
          child: CircularProgressIndicator(color: AppColors.darkBlue),
        ),
      );
    }

    if (_error != null || _seller == null) {
      return Scaffold(
        appBar: CustomAppbar(title: AppLocale.tr('seller_profile')),
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.error_outline, size: 64.sp, color: Colors.grey),
              SizedBox(height: 16.h),
              Text(
                _error ?? AppLocale.tr('profile_not_available'),
                style: TextStyle(fontSize: 16.sp, color: Colors.grey[600]),
                textAlign: TextAlign.center,
              ),
            ],
          ),
        ),
      );
    }

    final seller = _seller!;
    final displayName = seller.name;
    final location = [
      seller.businessAddress,
      seller.locationCity != null
          ? LocationTranslations.display(AppLocale.current, seller.locationCity!)
          : null,
    ]
        .where((x) => x != null && x.toString().isNotEmpty)
        .join('، ');

    return Scaffold(
      appBar: CustomAppbar(title: displayName),
      body: seller.hasBlocked
          ? Column(
              children: [
                Container(
                  width: double.infinity,
                  padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 14.h),
                  color: Colors.orange.shade50,
                  child: Row(
                    children: [
                      Icon(Icons.block, color: Colors.orange.shade700, size: 24.sp),
                      SizedBox(width: 12.w),
                      Expanded(
                        child: Text(
                          AppLocale.tr('you_have_blocked_this_user'),
                          style: TextStyle(
                            fontSize: 14.sp,
                            color: Colors.orange.shade900,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                Expanded(
                  child: Center(
                    child: Padding(
                      padding: EdgeInsets.all(24.w),
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(
                            AppLocale.tr('you_have_blocked_this_user'),
                            textAlign: TextAlign.center,
                            style: TextStyle(
                              fontSize: 15.sp,
                              color: Colors.grey[700],
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                          SizedBox(height: 24.h),
                          CustomButton(
                            text: AppLocale.tr('unblock'),
                            onTap: () => _unblockSeller(seller.id),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ],
            )
          : Column(
              children: [
          Builder(
            builder: (context) {
              // Avoid overflow: ensure header/card have enough height.
              // Keep it compact: end shortly after followers row.
              final headerHeight = (MediaQuery.sizeOf(context).height / 5.6).clamp(175.h, 255.h);
              final topBarHeight = (headerHeight * 0.44).clamp(72.h, 110.h);
              return SizedBox(
                height: headerHeight,
            child: Stack(
              clipBehavior: Clip.none,
              children: [
                Container(
                  padding: EdgeInsets.symmetric(
                    horizontal: 20.w,
                    vertical: 22.h,
                  ),
                  width: double.infinity,
                  height: topBarHeight,
                  color: AppColors.darkBlue,
                ),
                Positioned(
                  left: 20.w,
                  right: 20.w,
                  top: 6.h,
                  bottom: 0,
                  child: Container(
                    width: double.infinity,
                    padding: EdgeInsets.symmetric(vertical: 6.h),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.1),
                          blurRadius: 10,
                          offset: const Offset(0, 4),
                        ),
                      ],
                      border: Border.all(color: Colors.white),
                      borderRadius: BorderRadius.circular(12.r),
                    ),
                    child: _SellerInfoWidget(
                      seller: seller,
                      location: location,
                      isFavorite: _isFavoriteSeller,
                      followersCount: _followersCount,
                      followingCount: _followingCount,
                      onFollowToggle: _toggleFollowSeller,
                      onShare: _shareSellerProfile,
                      isMe: seller.isMe,
                    ),
                  ),
                ),
              ],
            ),
              );
            },
          ),
          Expanded(
            child: SingleChildScrollView(
              physics: const BouncingScrollPhysics(),
              child: Padding(
                padding: EdgeInsets.symmetric(horizontal: 20.w),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                      Container(
                        padding: EdgeInsets.symmetric(
                          horizontal: 10.w,
                          vertical: 10.h,
                        ),
                        width: double.infinity,
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(12.r),
                          color: Colors.grey[200],
                        ),
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.end,
                              children: [
                                Text(
                                  AppLocale.tr('profile'),
                                  style: TextStyle(
                                    color: Colors.black,
                                    fontSize: 15.sp,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ],
                            ),
                            SizedBox(height: 8.h),
                            Text(
                              seller.bio != null && seller.bio!.isNotEmpty
                                  ? seller.bio!
                                  : AppLocale.tr('no_bio_from_seller'),
                              style: TextStyle(
                                color: Colors.black,
                                fontSize: 12.sp,
                                fontWeight: FontWeight.w400,
                                height: 1.8,
                              ),
                            ),
                          ],
                        ),
                      ),
                      SizedBox(height: 16.h),
                      _ContactActionsRow(
                        seller: seller,
                        onBlock: seller.hasBlocked ? null : () => _blockSeller(seller.id),
                        onUnblock: seller.hasBlocked ? () => _unblockSeller(seller.id) : null,
                        hasBlocked: seller.hasBlocked,
                        isSelf: seller.isMe,
                      ),
                      SizedBox(height: 12.h),
                      _SocialLinksRow(seller: seller),
                      SizedBox(height: 20.h),
                      ListView.separated(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        itemBuilder: (context, index) {
                          final ad = _ads[index];
                          return _SellerAdRow(
                            ad: ad,
                            onTap: () => context.push(AdDetailsPage(adUid: ad.uid)),
                          );
                        },
                        separatorBuilder: (_, __) => SizedBox(height: 0.h),
                        itemCount: _ads.length,
                      ),
                      if (_ads.isEmpty)
                        Padding(
                          padding: EdgeInsets.symmetric(vertical: 24.h),
                          child: Text(
                            AppLocale.tr('no_ads'),
                            style: TextStyle(
                              fontSize: 14.sp,
                              color: Colors.grey[600],
                            ),
                          ),
                        ),
                      SizedBox(height: 20.h),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(
                            "${seller.ratingsCount} ${AppLocale.tr('star_ratings')}",
                            style: TextStyle(
                              color: AppColors.darkBlue,
                              fontSize: 11.sp,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          SizedBox(width: 10.w),
                          _AverageStarsWidget(averageRating: seller.averageRating),
                          SizedBox(width: 6.w),
                          Text(
                            seller.averageRating.toStringAsFixed(1),
                            style: TextStyle(
                              color: AppColors.darkBlue,
                              fontSize: 11.sp,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ],
                      ),
                      ListView.builder(
                        physics: const NeverScrollableScrollPhysics(),
                        shrinkWrap: true,
                        itemCount: _ratings.length,
                        itemBuilder: (context, index) => _OpinionItem(
                          rating: _ratings[index],
                        ),
                      ),
                      if (!seller.isMe)
                        Padding(
                          padding: EdgeInsets.symmetric(horizontal: 16.w),
                          child: Column(
                            children: [
                              SizedBox(height: 30.h),
                              if (_userRating != null) ...[
                                Text(
                                  AppLocale.tr('you_already_rated'),
                                  style: TextStyle(
                                    fontSize: 14.sp,
                                    color: Colors.grey[700],
                                    fontWeight: FontWeight.w500,
                                  ),
                                  textAlign: TextAlign.center,
                                ),
                                SizedBox(height: 20.h),
                              ] else ...[
                                Text(
                                  AppLocale.tr('rate_seller'),
                                  style: TextStyle(
                                    fontSize: 14.sp,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                                SizedBox(height: 8.h),
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: List.generate(5, (i) {
                                    final star = i + 1;
                                    return GestureDetector(
                                      onTap: () => setState(() => _selectedRating = star),
                                      child: Padding(
                                        padding: EdgeInsets.symmetric(horizontal: 4.w),
                                        child: Icon(
                                          star <= _selectedRating
                                              ? Icons.star
                                              : Icons.star_border,
                                          color: Colors.amber,
                                          size: 32.sp,
                                        ),
                                      ),
                                    );
                                  }),
                                ),
                                SizedBox(height: 16.h),
                                TextFormWithLabel(
                                  hintText: AppLocale.tr('comment_optional'),
                                  controller: _commentController,
                                  keyboardType: TextInputType.text,
                                  obscureText: false,
                                  labelText: AppLocale.tr('write_comment'),
                                  maxlines: 6,
                                ),
                                SizedBox(height: 30.h),
                                CustomButton(
                                  text: AppLocale.tr('submit_rating'),
                                  onTap: _submitRating,
                                ),
                              ],
                              SizedBox(height: 50.h),
                            ],
                          ),
                        ),
                    ],
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class _AverageStarsWidget extends StatelessWidget {
  final double averageRating;

  const _AverageStarsWidget({required this.averageRating});

  @override
  Widget build(BuildContext context) {
    final decimal = averageRating - averageRating.floor();
    int fullStars = averageRating.floor().clamp(0, 5);
    final bool showHalf = decimal >= 0.25 && decimal < 0.75;
    if (decimal >= 0.75 && fullStars < 5) fullStars++;

    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        for (int i = 0; i < fullStars; i++)
          Icon(Icons.star, color: AppColors.yellow, size: 15.sp),
        if (showHalf)
          Icon(Icons.star_half, color: AppColors.yellow, size: 15.sp),
        for (int i = 0; i < 5 - fullStars - (showHalf ? 1 : 0); i++)
          Icon(Icons.star_border, color: Colors.grey[400], size: 15.sp),
      ],
    );
  }
}

class _SellerInfoWidget extends StatelessWidget {
  final SellerModel seller;
  final String location;
  final bool isFavorite;
  final int followersCount;
  final int followingCount;
  final VoidCallback? onFollowToggle;
  final VoidCallback? onShare;
  final bool isMe;

  const _SellerInfoWidget({
    required this.seller,
    required this.location,
    this.isFavorite = false,
    this.followersCount = 0,
    this.followingCount = 0,
    this.onFollowToggle,
    this.onShare,
    this.isMe = false,
  });

  @override
  Widget build(BuildContext context) {
    final displayName = seller.name;
    final following = followingCount;
    final followers = followersCount;
    final followingFollowersTemplate = AppLocale.tr('following_followers');
    final followingFollowersText = followingFollowersTemplate
        .replaceFirst('0', following.toString())
        .replaceFirst('0', followers.toString());

    return Padding(
      padding: EdgeInsets.symmetric(horizontal: 14.w, vertical: 2.h),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          // Top: avatar + name + verified badge (adjacent like ad page)
          Row(
            mainAxisAlignment: MainAxisAlignment.start,
            children: [
              AvatarWithFallback(
                imageUrl: seller.avatar,
                radius: 20.r,
                fallbackLetter: displayName.isNotEmpty ? displayName[0].toUpperCase() : '?',
                fontSize: 18.sp,
              ),
              SizedBox(width: 8.w),
              Expanded(
                child: Text.rich(
                  TextSpan(
                    children: [
                      TextSpan(text: displayName),
                      if (seller.isVerified)
                        WidgetSpan(
                          alignment: PlaceholderAlignment.middle,
                          child: Padding(
                            padding: EdgeInsetsDirectional.only(start: 6.w),
                            child: Icon(
                              Icons.verified,
                              color: const Color(0xFF1D9BF0),
                              size: 16.sp,
                            ),
                          ),
                        ),
                    ],
                  ),
                  style: TextStyle(
                    color: Colors.black,
                    fontSize: 14.sp,
                    fontWeight: FontWeight.w600,
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
          SizedBox(height: 4.h),
          // Stars line centered
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Flexible(
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    _AverageStarsWidget(averageRating: seller.averageRating),
                    SizedBox(width: 4.w),
                    Flexible(
                      child: Text(
                        '${seller.averageRating.toStringAsFixed(1)}${seller.ratingsCount > 0 ? ' (${seller.ratingsCount} ${AppLocale.tr('ratings_count')})' : ''}',
                        style: TextStyle(
                          color: AppColors.darkBlue,
                          fontSize: 11.sp,
                          fontWeight: FontWeight.bold,
                        ),
                        overflow: TextOverflow.ellipsis,
                        maxLines: 1,
                        textAlign: TextAlign.center,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          SizedBox(height: 8.h),
          // Bottom centered: follow/share buttons + counts
          Center(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Row(
                  mainAxisSize: MainAxisSize.min,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    if (!isMe)
                      _SellerProfileFollowOrShareButton(
                        title: isFavorite ? AppLocale.tr('unfollow') : AppLocale.tr('follow'),
                        icon: isFavorite ? Icons.person_remove_alt_1 : Icons.person_add_alt_1,
                        textColor: isFavorite ? AppColors.darkBlue : Colors.white,
                        backgroundColor: isFavorite ? Colors.grey[200]! : AppColors.darkBlue,
                        onTap: onFollowToggle ?? () {},
                      ),
                    if (!isMe) SizedBox(width: 10.w),
                    _SellerProfileFollowOrShareButton(
                      title: AppLocale.tr('share'),
                      icon: Icons.ios_share,
                      textColor: AppColors.darkBlue,
                      backgroundColor: AppColors.yellow,
                      onTap: onShare ?? () {},
                    ),
                  ],
                ),
                SizedBox(height: 4.h),
                Text(
                  followingFollowersText,
                  style: TextStyle(
                    color: AppColors.darkBlue,
                    fontSize: 11.sp,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _ContactActionsRow extends StatelessWidget {
  final SellerModel seller;
  final VoidCallback? onBlock;
  final VoidCallback? onUnblock;
  final bool hasBlocked;
  final bool isSelf;

  const _ContactActionsRow({
    required this.seller,
    this.onBlock,
    this.onUnblock,
    this.hasBlocked = false,
    this.isSelf = false,
  });

  bool _ensureLoggedIn(BuildContext context) {
    if (TokenStorage.hasToken()) return true;
    context.push(LoginPage());
    return false;
  }

  Uri _normalizeUri(String url) {
    final raw = url.trim();
    final uri = Uri.tryParse(raw);
    if (uri == null) return Uri.parse(raw);
    if (uri.hasScheme) return uri;
    // If url is like "www.example.com" or "example.com"
    return Uri.parse('https://$raw');
  }

  Future<void> _launchUrl(BuildContext context, String url) async {
    final uri = _normalizeUri(url);
    final ok = await launchUrl(uri, mode: kIsWeb ? LaunchMode.platformDefault : LaunchMode.externalApplication);
    if (!ok) {
      showToast(message: AppLocale.tr('failed'));
    }
  }

  String? _toWhatsAppE164(String rawPhone, String? countryCode) {
    var p = rawPhone.trim();
    if (p.isEmpty) return null;
    // Keep digits and leading '+'
    p = p.replaceAll(RegExp(r'[^0-9+]'), '');
    if (p.isEmpty) return null;
    if (p.startsWith('+')) {
      final digits = p.replaceAll(RegExp(r'[^0-9]'), '');
      return digits.isNotEmpty ? '+$digits' : null;
    }

    // Remove leading zeros (e.g., 05xxxx -> 5xxxx)
    p = p.replaceFirst(RegExp(r'^0+'), '');
    final c = (countryCode ?? '').toUpperCase();
    final cc = c == 'TR' ? '90' : (c == 'SY' ? '963' : '');
    if (cc.isNotEmpty) {
      return '+$cc$p';
    }
    // Unknown country: return as-is (digits only) without '+'
    return p;
  }

  @override
  Widget build(BuildContext context) {
    final phone = seller.phone;
    final waE164 = (phone != null)
        ? _toWhatsAppE164(phone, seller.countryCode ?? seller.locationCountry)
        : null;
    final hasPhone = waE164 != null && waE164.trim().isNotEmpty;
    final waDigits = waE164?.replaceAll('+', '');
    final hasWhatsApp = waDigits != null && waDigits.length >= 9;

    final hasContactActions = hasPhone || hasWhatsApp || !isSelf;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          AppLocale.tr('contact'),
          style: TextStyle(
            color: AppColors.darkBlue,
            fontSize: 15.sp,
            fontWeight: FontWeight.bold,
          ),
        ),
        SizedBox(height: 10.h),
        if (hasContactActions)
          Wrap(
            spacing: 8.w,
            runSpacing: 8.h,
            alignment: WrapAlignment.start,
            children: [
        if (hasPhone)
          GestureDetector(
            onTap: () {
              if (!_ensureLoggedIn(context)) return;
              _launchUrl(context, 'tel:$waE164');
            },
            child: Container(
              padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 8.h),
              decoration: BoxDecoration(
                color: AppColors.darkBlue.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(8.r),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.phone, color: AppColors.darkBlue, size: 20.sp),
                  SizedBox(width: 6.w),
                  Text(
                    AppLocale.tr('call'),
                    style: TextStyle(
                      color: AppColors.darkBlue,
                      fontSize: 13.sp,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
            ),
          ),
        if (hasWhatsApp)
          GestureDetector(
            onTap: () async {
              if (!_ensureLoggedIn(context)) return;
              // Prefer app scheme; fallback to web.
              final digits = waDigits;
              if (digits.isEmpty) {
                showToast(message: AppLocale.tr('failed'));
                return;
              }
              final webUri = Uri.parse('https://wa.me/$digits');
              if (kIsWeb) {
                final webOk = await launchUrl(webUri, mode: LaunchMode.platformDefault);
                if (!webOk) showToast(message: AppLocale.tr('failed'));
                return;
              }
              final appUri = Uri.parse('whatsapp://send?phone=$digits');
              final appOk = await launchUrl(appUri, mode: LaunchMode.externalApplication);
              if (appOk) return;
              final webOk = await launchUrl(webUri, mode: LaunchMode.externalApplication);
              if (!webOk) showToast(message: AppLocale.tr('failed'));
            },
            child: Container(
              padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 8.h),
              decoration: BoxDecoration(
                color: const Color(0xFF25D366),
                borderRadius: BorderRadius.circular(8.r),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.chat, color: Colors.white, size: 20.sp),
                  SizedBox(width: 6.w),
                  Text(
                    AppLocale.tr('whatsapp'),
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 13.sp,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
            ),
          ),
        if (!isSelf)
          GestureDetector(
            onTap: () {
              if (!_ensureLoggedIn(context)) return;
              context.push(ChatPage(sellerSlug: seller.slug, sellerName: seller.name));
            },
            child: Container(
              padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 8.h),
              decoration: BoxDecoration(
                color: AppColors.darkBlue,
                borderRadius: BorderRadius.circular(8.r),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.chat_bubble_outline, color: Colors.white, size: 20.sp),
                  SizedBox(width: 6.w),
                  Text(
                    AppLocale.tr('chat_seller'),
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 13.sp,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
            ),
          ),
        if (!isSelf && TokenStorage.hasToken())
          hasBlocked && onUnblock != null
              ? GestureDetector(
                  onTap: onUnblock,
                  child: Container(
                    padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 8.h),
                    decoration: BoxDecoration(
                      color: Colors.green.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(8.r),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.person_add_outlined, color: Colors.green.shade700, size: 20.sp),
                        SizedBox(width: 6.w),
                        Text(
                          AppLocale.tr('unblock'),
                          style: TextStyle(
                            color: Colors.green.shade700,
                            fontSize: 13.sp,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ],
                    ),
                  ),
                )
              : (onBlock != null && !hasBlocked)
                  ? GestureDetector(
                      onTap: onBlock,
                      child: Container(
                        padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 8.h),
                        decoration: BoxDecoration(
                          color: Colors.red.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(8.r),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.block, color: Colors.red, size: 20.sp),
                            SizedBox(width: 6.w),
                            Text(
                              AppLocale.tr('block_seller'),
                              style: TextStyle(
                                color: Colors.red,
                                fontSize: 13.sp,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          ],
                        ),
                      ),
                    )
                  : const SizedBox.shrink(),
            ],
          )
        else
          Text(
            AppLocale.tr('no_contact_available'),
            style: TextStyle(
              fontSize: 13.sp,
              color: Colors.grey[600],
            ),
          ),
      ],
    );
  }
}

class _SocialLinksRow extends StatelessWidget {
  final SellerModel seller;

  const _SocialLinksRow({required this.seller});

  Uri _normalizeUri(String url) {
    final raw = url.trim();
    final uri = Uri.tryParse(raw);
    if (uri == null) return Uri.parse(raw);
    if (uri.hasScheme) return uri;
    return Uri.parse('https://$raw');
  }

  Future<void> _launchUrl(String url) async {
    final uri = _normalizeUri(url);
    final ok = await launchUrl(uri, mode: kIsWeb ? LaunchMode.platformDefault : LaunchMode.externalApplication);
    if (!ok) {
      showToast(message: AppLocale.tr('failed'));
    }
  }

  @override
  Widget build(BuildContext context) {
    final hasAny = (seller.instagramUrl != null && seller.instagramUrl!.isNotEmpty) ||
        (seller.facebookUrl != null && seller.facebookUrl!.isNotEmpty) ||
        (seller.websiteUrl != null && seller.websiteUrl!.isNotEmpty);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          AppLocale.tr('social_links'),
          style: TextStyle(
            color: AppColors.darkBlue,
            fontSize: 15.sp,
            fontWeight: FontWeight.bold,
          ),
        ),
        SizedBox(height: 10.h),
        if (hasAny)
          Row(
            mainAxisAlignment: MainAxisAlignment.start,
            children: [
        if (seller.instagramUrl != null && seller.instagramUrl!.isNotEmpty)
          GestureDetector(
            onTap: () => _launchUrl(seller.instagramUrl!),
            child: Padding(
              padding: EdgeInsets.symmetric(horizontal: 8.w),
              child: FaIcon(
                FontAwesomeIcons.instagram,
                color: AppColors.darkBlue,
                size: 22.sp,
              ),
            ),
          ),
        if (seller.facebookUrl != null && seller.facebookUrl!.isNotEmpty)
          GestureDetector(
            onTap: () => _launchUrl(seller.facebookUrl!),
            child: Padding(
              padding: EdgeInsets.symmetric(horizontal: 8.w),
              child: Icon(
                Icons.facebook,
                color: AppColors.darkBlue,
                size: 24.sp,
              ),
            ),
          ),
        if (seller.websiteUrl != null && seller.websiteUrl!.isNotEmpty)
          GestureDetector(
            onTap: () => _launchUrl(seller.websiteUrl!),
            child: Padding(
              padding: EdgeInsets.symmetric(horizontal: 8.w),
              child: Icon(
                Icons.language,
                color: AppColors.darkBlue,
                size: 24.sp,
              ),
            ),
          ),
            ],
          )
        else
          Text(
            AppLocale.tr('no_links'),
            style: TextStyle(fontSize: 13.sp, color: Colors.grey[600]),
          ),
      ],
    );
  }
}

class _SellerProfileFollowOrShareButton extends StatelessWidget {
  final String title;
  final IconData? icon;
  final Color textColor;
  final Color backgroundColor;
  final VoidCallback onTap;

  const _SellerProfileFollowOrShareButton({
    required this.title,
    this.icon,
    required this.textColor,
    required this.backgroundColor,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: EdgeInsets.symmetric(vertical: 6.h, horizontal: 8.w),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(9.r),
          color: backgroundColor,
        ),
        child: Center(
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                title,
                style: TextStyle(
                  color: textColor,
                  fontWeight: FontWeight.bold,
                  fontSize: 9.sp,
                ),
              ),
              if (icon != null) ...[
                SizedBox(width: 4.w),
                Icon(icon, size: 16.sp, color: textColor),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _SellerAdRow extends StatelessWidget {
  final AdModel ad;
  final VoidCallback onTap;

  const _SellerAdRow({required this.ad, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: EdgeInsets.all(12.w),
        margin: EdgeInsets.only(bottom: 12.h),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(8.r),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.06),
              blurRadius: 8,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Row(
          children: [
            Stack(
              clipBehavior: Clip.none,
              children: [
                ClipRRect(
                  borderRadius: BorderRadius.circular(8.r),
                  child: ColoredBox(
                    color: Colors.grey[100]!,
                    child: ad.imageUrl != null
                        ? ListAdThumbnailImage(
                            imageUrl: ad.imageUrl!,
                            width: 70.w,
                            maxHeight: 70.h,
                          )
                        : SizedBox(
                            width: 70.w,
                            height: 70.h,
                            child: Icon(
                              Icons.image_not_supported,
                              color: Colors.grey[400],
                            ),
                          ),
                  ),
                ),
                if (ad.isFeatured)
                  Positioned(
                    top: 2.h,
                    left: 2.w,
                    child: AdStatusBadgeIcon.featured(size: 18.sp),
                  ),
                Positioned(
                  top: -4.h,
                  right: -4.w,
                  child: FavoriteIconButton(
                    adUid: ad.uid,
                    initialIsFavorite: ad.isFavorite,
                    size: 18.sp,
                    backgroundColor: Colors.white,
                  ),
                ),
              ],
            ),
            SizedBox(width: 12.w),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.start,
                children: [
                  Text(
                    ad.title,
                    style: TextStyle(
                      fontSize: 14.sp,
                      fontWeight: FontWeight.bold,
                      color: Colors.black,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  SizedBox(height: 6.h),
                  Row(
                    children: [
                      Expanded(
                        child: AdListLocationLabel(
                          key: ValueKey('${ad.uid}_loc'),
                          ad: ad,
                          iconSize: 16,
                          style: TextStyle(
                            fontSize: 12.sp,
                            color: Colors.grey,
                          ),
                        ),
                      ),
                      if (ad.displayPriceOrSalaryForUi != null)
                        Text(
                          ad.displayPriceOrSalaryForUi!,
                          style: TextStyle(
                            fontSize: 15.sp,
                            color: AppColors.darkBlue,
                            fontWeight: FontWeight.bold,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _OpinionItem extends StatelessWidget {
  final SellerRatingModel rating;

  const _OpinionItem({required this.rating});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(top: 20.h),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.start,
            children: [
              AvatarWithFallback(
                imageUrl: rating.userAvatar,
                radius: 20.r,
                fallbackLetter: rating.userName.isNotEmpty
                    ? rating.userName[0].toUpperCase()
                    : '?',
                fontSize: 16.sp,
              ),
              SizedBox(width: 10.w),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    rating.userName,
                    style: TextStyle(fontSize: 14.sp),
                  ),
                  Row(
                    children: List.generate(
                      5,
                      (i) => Icon(
                        i < rating.rating ? Icons.star : Icons.star_border,
                        size: 18.sp,
                        color: Colors.amber,
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
          if (rating.comment != null && rating.comment!.isNotEmpty)
            Padding(
              padding: EdgeInsets.only(top: 8.h),
              child: Text(
                rating.comment!,
                style: TextStyle(
                  color: Colors.black54,
                  fontSize: 14.sp,
                  fontWeight: FontWeight.w400,
                ),
              ),
            ),
        ],
      ),
    );
  }
}
