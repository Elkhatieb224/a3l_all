import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/models/seller_model.dart';
import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/data/services/favorite_seller_service.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/account/messages_page.dart';
import 'package:a3lnha/presentation/pages/account/seller_profile_page.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class FavouriteSellersPage extends StatefulWidget {
  const FavouriteSellersPage({super.key});

  @override
  State<FavouriteSellersPage> createState() => _FavouriteSellersPageState();
}

class _FavouriteSellersPageState extends State<FavouriteSellersPage> {
  List<SellerModel> _sellers = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final list = await FavoriteSellerService.getFavoriteSellers();
    if (mounted) setState(() {
      _sellers = list;
      _loading = false;
    });
  }

  Future<void> _removeSeller(SellerModel seller) async {
    final ok = await FavoriteSellerService.remove(seller.slug);
    if (mounted) {
      showToast(message: ok ? AppLocale.tr('removed_from_favorites') : AppLocale.tr('failed'));
      if (ok) setState(() => _sellers.removeWhere((s) => s.slug == seller.slug));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: AppLocale.tr('favorite_sellers_title')),
      body: Padding(
        padding: EdgeInsets.all(16.h),
        child: Column(
          children: [
            SearchTextFormField(),
            Expanded(
              child: _loading
                  ? Center(child: CircularProgressIndicator(color: AppColors.darkBlue))
                  : _sellers.isEmpty
                      ? Center(
                          child: Text(
                            AppLocale.tr('no_favorite_sellers'),
                            style: TextStyle(fontSize: 14.sp, color: Colors.grey[600]),
                          ),
                        )
                      : RefreshIndicator(
                          onRefresh: _load,
                          child: ListView.builder(
                            itemCount: _sellers.length,
                            itemBuilder: (context, index) {
                              final seller = _sellers[index];
                              return FavSellerItem(
                                seller: seller,
                                onViewProfile: () => context.push(SellerProfilePage(sellerSlug: seller.slug)).then((_) => _load()),
                                onRemove: () => _removeSeller(seller),
                              );
                            },
                          ),
                        ),
            ),
          ],
        ),
      ),
    );
  }
}

class FavSellerItem extends StatelessWidget {
  final SellerModel seller;
  final VoidCallback onViewProfile;
  final VoidCallback onRemove;

  const FavSellerItem({
    super.key,
    required this.seller,
    required this.onViewProfile,
    required this.onRemove,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: () {
        showDialog(
          context: context,
          builder: (ctx) => Dialog(
            backgroundColor: Colors.white,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24.r)),
            child: Padding(
              padding: EdgeInsets.all(35.h),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  InDialogButton(
                    title: AppLocale.tr('go_to_seller'),
                    backgroundColor: AppColors.darkBlue,
                    textColor: Colors.white,
                    onTap: () {
                      Navigator.pop(ctx);
                      onViewProfile();
                    },
                  ),
                  SizedBox(height: 25.h),
                  InDialogButton(
                    title: AppLocale.tr('remove_seller_from_favorites'),
                    backgroundColor: Colors.white,
                    textColor: Colors.red,
                    onTap: () {
                      Navigator.pop(ctx);
                      onRemove();
                    },
                  ),
                ],
              ),
            ),
          ),
        );
      },
      child: Padding(
        padding: EdgeInsets.only(top: 20.h),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            UserAvatar(avatarUrl: seller.avatar),
            SizedBox(width: 10.w),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    seller.name,
                    style: TextStyle(fontSize: 14.sp, fontWeight: FontWeight.w600),
                  ),
                  Text(
                    seller.businessType ?? "—",
                    style: TextStyle(fontSize: 12.sp, fontWeight: FontWeight.w400, color: Colors.grey),
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

class InDialogButton extends StatelessWidget {
  final String title;
  final Color backgroundColor;
  final Color textColor;
  final VoidCallback onTap;
  final String? imagePath;

  const InDialogButton({
    super.key,
    required this.title,
    required this.backgroundColor,
    required this.textColor,
    required this.onTap,
    this.imagePath,
  });

  @override
  Widget build(BuildContext context) {
    return Material(
      color: backgroundColor,
      borderRadius: BorderRadius.circular(50.r),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(50.r),
        splashColor: textColor.withValues(alpha: 0.2),
        highlightColor: textColor.withValues(alpha: 0.1),
        child: Container(
          padding: EdgeInsets.symmetric(vertical: 10.h),
          width: double.infinity,
          decoration: BoxDecoration(
            border: Border.all(color: AppColors.darkBlue),
            borderRadius: BorderRadius.circular(50.r),
            color: Colors.transparent,
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(title, style: TextStyle(color: textColor, fontSize: 20.sp, fontWeight: FontWeight.w500)),
            ],
          ),
        ),
      ),
    );
  }
}
