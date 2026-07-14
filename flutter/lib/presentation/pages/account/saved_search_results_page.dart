import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/models/ad_model.dart';
import 'package:a3lnha/data/services/saved_search_service.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/home/ad_details_page.dart';
import 'package:a3lnha/presentation/widgets/shared/app_network_image.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class SavedSearchResultsPage extends StatefulWidget {
  final int savedSearchId;
  const SavedSearchResultsPage({super.key, required this.savedSearchId});

  @override
  State<SavedSearchResultsPage> createState() => _SavedSearchResultsPageState();
}

class _SavedSearchResultsPageState extends State<SavedSearchResultsPage> {
  bool _loading = true;
  String _title = '';
  List<AdModel> _ads = [];
  int _page = 1;
  int _lastPage = 1;
  bool _loadingMore = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load({bool loadMore = false}) async {
    if (loadMore) {
      if (_page >= _lastPage || _loadingMore) return;
      setState(() => _loadingMore = true);
    } else {
      setState(() => _loading = true);
    }

    final res = await SavedSearchService.getSavedSearchResults(
      widget.savedSearchId,
      page: loadMore ? _page + 1 : 1,
      perPage: 20,
    );
    if (!mounted) return;
    setState(() {
      _title = (res.savedSearch.name ?? '').trim().isNotEmpty
          ? res.savedSearch.name!.trim()
          : (res.savedSearch.filters['search']?.toString().trim().isNotEmpty == true
              ? res.savedSearch.filters['search'].toString()
              : AppLocale.tr('saved_search_results'));
      if (loadMore) {
        _ads.addAll(res.ads);
      } else {
        _ads = res.ads;
      }
      _page = res.currentPage;
      _lastPage = res.lastPage;
      _loading = false;
      _loadingMore = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: _title.isEmpty ? AppLocale.tr('saved_search_results') : _title),
      body: _loading
          ? Center(child: CircularProgressIndicator(color: AppColors.darkBlue))
          : _ads.isEmpty
              ? Center(
                  child: Text(
                    AppLocale.tr('no_results'),
                    style: TextStyle(fontSize: 15.sp, color: Colors.grey[600]),
                  ),
                )
              : ListView.builder(
                  padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 12.h),
                  itemCount: _ads.length + (_page < _lastPage ? 1 : 0),
                  itemBuilder: (context, index) {
                    if (index == _ads.length) {
                      return Center(
                        child: TextButton(
                          onPressed: _loadingMore ? null : () => _load(loadMore: true),
                          child: _loadingMore
                              ? SizedBox(
                                  width: 20.w,
                                  height: 20.w,
                                  child: CircularProgressIndicator(strokeWidth: 2, color: AppColors.darkBlue),
                                )
                              : Text(AppLocale.tr('load_more')),
                        ),
                      );
                    }
                    final ad = _ads[index];
                    return GestureDetector(
                      onTap: () => context.push(AdDetailsPage(adUid: ad.uid)),
                      child: Container(
                        padding: EdgeInsets.all(10.w),
                        margin: EdgeInsets.only(bottom: 10.h),
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
                            ClipRRect(
                              borderRadius: BorderRadius.circular(8.r),
                              child: AppNetworkImage(
                                imageUrl: ad.imageUrl,
                                width: 74.w,
                                height: 74.w,
                                fit: BoxFit.cover,
                              ),
                            ),
                            SizedBox(width: 10.w),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    ad.title,
                                    maxLines: 2,
                                    overflow: TextOverflow.ellipsis,
                                    style: TextStyle(fontSize: 13.sp, fontWeight: FontWeight.w600),
                                  ),
                                  SizedBox(height: 6.h),
                                  Text(
                                    ad.displayPriceForUi ?? '',
                                    style: TextStyle(
                                      color: AppColors.darkBlue,
                                      fontSize: 14.sp,
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
    );
  }
}

