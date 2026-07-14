import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/services/ad_service.dart';
import 'package:a3lnha/helpers/numeral_helper.dart';
import 'package:a3lnha/presentation/pages/search/search_page.dart';
import 'package:a3lnha/presentation/widgets/search_category_hit_cards_grid.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/helpers/localized_name.dart';

/// نتائج البحث من الرئيسية: بطاقات الأقسام الرئيسية التي فيها إعلانات مطابقة لكلمة البحث.
class SearchResultsPage extends StatefulWidget {
  final String initialQuery;

  const SearchResultsPage({super.key, required this.initialQuery});

  @override
  State<SearchResultsPage> createState() => _SearchResultsPageState();
}

class _SearchResultsPageState extends State<SearchResultsPage> {
  List<SearchCategoryItem> _categories = [];
  int _total = 0;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  String _displayName(SearchCategoryItem item) {
    return getLocalizedName(
      nameAr: item.nameAr,
      nameEn: item.nameEn,
      nameTr: item.nameTr,
      defaultName: item.name,
      locale: AppLocale.current,
    );
  }

  Future<void> _load() async {
    const tag = '[SearchResultsPage]';
    if (kDebugMode) debugPrint('$tag _load start query="${widget.initialQuery}"');
    setState(() => _loading = true);
    try {
      final q = widget.initialQuery.trim();
      var globalTotal = 0;
      try {
        final snap = await AdService.getAds(
          search: q.isEmpty ? null : q,
          page: 1,
          perPage: 1,
        );
        globalTotal = snap.total;
      } catch (_) {}

      List<SearchCategoryItem> list = [];
      if (q.length >= AdService.minSearchLength) {
        final res = await AdService.getSearchCategories(q);
        list = res.data;
      }

      if (kDebugMode) {
        debugPrint('$tag categories=${list.length} globalTotal=$globalTotal');
      }
      if (mounted) {
        setState(() {
          _categories = list;
          _total = globalTotal;
          _loading = false;
        });
      }
    } catch (_) {
      if (kDebugMode) debugPrint('$tag _load failed');
      if (mounted) {
        setState(() {
          _categories = [];
          _total = 0;
          _loading = false;
        });
      }
    }
  }

  void _openCategory(SearchCategoryItem cat) {
    context.push(
      SearchPage(
        initialSearchQuery: widget.initialQuery,
        initialCategoryId: cat.categoryId,
        initialSubcategoryId: cat.kind == 'subcategory' ? cat.subcategoryId : null,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: widget.initialQuery),
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: double.infinity,
            padding: EdgeInsets.symmetric(horizontal: 20.w, vertical: 16.h),
            color: AppColors.darkBlue,
            child: Text(
              AppLocale.tr('results_available').replaceAll(
                '%s',
                NumeralHelper.formatWithThousands(_total),
              ),
              style: TextStyle(
                color: Colors.white70,
                fontSize: 14.sp,
              ),
            ),
          ),
          Expanded(
            child: _loading
                ? Center(child: CircularProgressIndicator(color: AppColors.darkBlue))
                : _categories.isEmpty
                    ? Center(
                        child: Padding(
                          padding: EdgeInsets.all(24.w),
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Text(
                                AppLocale.tr('no_results'),
                                style: TextStyle(fontSize: 16.sp, color: Colors.grey[600]),
                                textAlign: TextAlign.center,
                              ),
                              SizedBox(height: 16.h),
                              TextButton.icon(
                                onPressed: () {
                                  if (kDebugMode) {
                                    debugPrint(
                                      '[SearchResultsPage] view_all_results tapped query="${widget.initialQuery}"',
                                    );
                                  }
                                  context.push(SearchPage(initialSearchQuery: widget.initialQuery));
                                },
                                icon: Icon(Icons.list, color: AppColors.darkBlue, size: 20.sp),
                                label: Text(
                                  AppLocale.tr('view_all_results'),
                                  style: TextStyle(color: AppColors.darkBlue, fontWeight: FontWeight.w600),
                                ),
                              ),
                            ],
                          ),
                        ),
                      )
                    : RefreshIndicator(
                        onRefresh: _load,
                        child: SingleChildScrollView(
                          physics: const AlwaysScrollableScrollPhysics(),
                          child: SearchCategoryHitCardsGrid(
                            loading: false,
                            items: _categories,
                            displayName: _displayName,
                            onPick: _openCategory,
                          ),
                        ),
                      ),
          ),
        ],
      ),
    );
  }
}
