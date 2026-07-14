import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/data/models/ad_model.dart';
import 'package:a3lnha/data/services/favorite_service.dart';
import 'package:a3lnha/presentation/pages/account/on_air_ads_page.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class FavouriteAdsPage extends StatefulWidget {
  final String title;
  final int? categoryId;
  final int? subcategoryId;

  const FavouriteAdsPage({
    super.key,
    required this.title,
    this.categoryId,
    this.subcategoryId,
  });

  @override
  State<FavouriteAdsPage> createState() => _FavouriteAdsPageState();
}

class _FavouriteAdsPageState extends State<FavouriteAdsPage> {
  List<AdModel> _ads = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _loadAds();
  }

  Future<void> _loadAds() async {
    setState(() => _loading = true);
    final res = await FavoriteService.getFavorites();
    if (mounted) {
      setState(() {
        _ads = res.ads;
        _loading = false;
      });
    }
  }

  Future<void> _removeFromFavorites(AdModel ad) async {
    final ok = await FavoriteService.remove(ad.uid);
    if (mounted) {
      if (ok) {
        setState(() => _ads.removeWhere((a) => a.uid == ad.uid));
        showToast(message: AppLocale.tr('ad_removed_from_favorites'));
      } else {
        showToast(message: AppLocale.tr('failed_to_remove'));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: widget.title),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _ads.isEmpty
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.favorite_border, size: 64.sp, color: Colors.grey[400]),
                      SizedBox(height: 16.h),
                      Text(
                        "لا توجد إعلانات مفضلة",
                        style: TextStyle(fontSize: 14.sp, color: Colors.grey[600]),
                      ),
                    ],
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _loadAds,
                  child: ListView.separated(
                    itemBuilder: (context, index) {
                      return AccountAdWidget(
                        ad: _ads[index],
                        isFavourite: true,
                        onRemoveFromFavorites: () => _removeFromFavorites(_ads[index]),
                      );
                    },
                    separatorBuilder: (_, __) => Divider(),
                    itemCount: _ads.length,
                  ),
                ),
    );
  }
}
