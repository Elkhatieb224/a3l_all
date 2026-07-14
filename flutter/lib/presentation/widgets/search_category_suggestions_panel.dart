import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/services/ad_service.dart';
import 'package:a3lnha/helpers/numeral_helper.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class SearchCategorySuggestionsPanel extends StatelessWidget {
  const SearchCategorySuggestionsPanel({
    super.key,
    required this.loading,
    required this.items,
    required this.onPick,
    required this.sectionTitle,
    required this.displayName,
    this.maxItems = 20,
    /// عند تعيينه: قائمة الاقتراحات داخل ارتفاع ثابت مع تمرير (منسدلة).
    this.maxListHeight,
    /// رأس المنزلق: «الفئة» + «حفظ البحث» ثم الخط الأصفر (بدون تكرار عنوان القسم).
    this.dropdownChrome = false,
    this.onDropdownSaveSearch,
  });

  final bool loading;
  final List<SearchCategoryItem> items;
  final ValueChanged<SearchCategoryItem> onPick;
  final String sectionTitle;
  final String Function(SearchCategoryItem) displayName;
  final int maxItems;
  final double? maxListHeight;
  final bool dropdownChrome;
  final VoidCallback? onDropdownSaveSearch;

  static const Color _accentLine = Color(0xFFFFCA57);

  @override
  Widget build(BuildContext context) {
    if (!loading && items.isEmpty) return const SizedBox.shrink();

    final list = items.take(maxItems).toList();

    return ColoredBox(
      color: Colors.white,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        mainAxisSize: MainAxisSize.min,
        children: [
          if (dropdownChrome)
            Padding(
              padding: EdgeInsets.fromLTRB(16.w, 12.h, 16.w, 0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        sectionTitle.toUpperCase(),
                        style: TextStyle(
                          color: Colors.grey[600],
                          fontSize: 11.sp,
                          fontWeight: FontWeight.w700,
                          letterSpacing: 0.6,
                        ),
                      ),
                    onDropdownSaveSearch != null
                        ? GestureDetector(
                            onTap: onDropdownSaveSearch,
                            behavior: HitTestBehavior.opaque,
                            child: Padding(
                              padding: EdgeInsets.symmetric(vertical: 4.h, horizontal: 4.w),
                              child: Text(
                                AppLocale.tr('save_search'),
                                style: TextStyle(
                                  color: AppColors.darkBlue,
                                  fontSize: 12.sp,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ),
                          )
                        : Padding(
                            padding: EdgeInsets.symmetric(vertical: 4.h, horizontal: 4.w),
                            child: Text(
                              AppLocale.tr('save_search'),
                              style: TextStyle(
                                color: AppColors.darkBlue,
                                fontSize: 12.sp,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                    ],
                  ),
                  SizedBox(height: 8.h),
                  const SizedBox(
                    width: double.infinity,
                    height: 2,
                    child: ColoredBox(color: _accentLine),
                  ),
                ],
              ),
            )
          else
            Padding(
              padding: EdgeInsets.fromLTRB(20.w, 14.h, 20.w, 0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    sectionTitle.toUpperCase(),
                    style: TextStyle(
                      color: Colors.grey[600],
                      fontSize: 11.sp,
                      fontWeight: FontWeight.w700,
                      letterSpacing: 0.8,
                    ),
                  ),
                  SizedBox(height: 6.h),
                  const SizedBox(
                    width: double.infinity,
                    height: 2,
                    child: ColoredBox(color: _accentLine),
                  ),
                ],
              ),
            ),
          if (loading)
            Padding(
              padding: EdgeInsets.symmetric(vertical: 20.h),
              child: Center(
                child: SizedBox(
                  width: 22.w,
                  height: 22.w,
                  child: CircularProgressIndicator(
                    strokeWidth: 2,
                    color: AppColors.darkBlue,
                  ),
                ),
              ),
            )
          else
            maxListHeight != null
                ? SizedBox(
                    height: maxListHeight,
                    child: ListView.separated(
                      padding: EdgeInsets.fromLTRB(20.w, 8.h, 20.w, 12.h),
                      itemCount: list.length,
                      separatorBuilder: (_, __) => Divider(
                        height: 1,
                        thickness: 1,
                        color: Colors.grey[200],
                      ),
                      itemBuilder: (context, index) =>
                          _suggestionTile(list[index], displayName, onPick),
                    ),
                  )
                : ListView.separated(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    padding: EdgeInsets.fromLTRB(20.w, 8.h, 20.w, 12.h),
                    itemCount: list.length,
                    separatorBuilder: (_, __) => Divider(
                      height: 1,
                      thickness: 1,
                      color: Colors.grey[200],
                    ),
                    itemBuilder: (context, index) =>
                        _suggestionTile(list[index], displayName, onPick),
                  ),
        ],
      ),
    );
  }
}

Widget _suggestionTile(
  SearchCategoryItem it,
  String Function(SearchCategoryItem) displayName,
  ValueChanged<SearchCategoryItem> onPick,
) {
  return InkWell(
    onTap: () => onPick(it),
    child: Padding(
      padding: EdgeInsets.symmetric(vertical: 12.h),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  displayName(it),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    color: Colors.black87,
                    fontSize: 15.sp,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                if (it.breadcrumb.trim().isNotEmpty) ...[
                  SizedBox(height: 4.h),
                  Text(
                    it.breadcrumb.trim(),
                    maxLines: 3,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      color: Colors.grey[600],
                      fontSize: 12.sp,
                      height: 1.3,
                      fontWeight: FontWeight.w400,
                    ),
                  ),
                ],
              ],
            ),
          ),
          SizedBox(width: 10.w),
          Text(
            '${NumeralHelper.formatWithThousands(it.matchingAdsCount)} ${AppLocale.tr('ads_count')}',
            style: TextStyle(
              color: Colors.grey[600],
              fontSize: 12.sp,
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
    ),
  );
}
