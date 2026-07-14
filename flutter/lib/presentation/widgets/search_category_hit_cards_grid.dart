import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/services/ad_service.dart';
import 'package:a3lnha/helpers/numeral_helper.dart';
import 'package:a3lnha/presentation/widgets/shared/app_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

/// شبكة بطاقات أقسام بعد تنفيذ البحث (أيقونة دائرة + اسم + عدد إعلانات).
class SearchCategoryHitCardsGrid extends StatelessWidget {
  const SearchCategoryHitCardsGrid({
    super.key,
    required this.loading,
    required this.items,
    required this.displayName,
    required this.onPick,
    this.crossAxisCount = 2,
  });

  final bool loading;
  final List<SearchCategoryItem> items;
  final String Function(SearchCategoryItem) displayName;
  final ValueChanged<SearchCategoryItem> onPick;
  final int crossAxisCount;

  static const List<Color> _accentTints = [
    Color(0xFFFFE4CC),
    Color(0xFFFFF3CD),
    Color(0xFFE3F2FD),
    Color(0xFFE8F5E9),
  ];

  @override
  Widget build(BuildContext context) {
    final bg = Colors.grey.shade100;
    if (loading) {
      return Container(
        color: bg,
        width: double.infinity,
        padding: EdgeInsets.symmetric(vertical: 48.h),
        child: Center(
          child: SizedBox(
            width: 28.w,
            height: 28.w,
            child: CircularProgressIndicator(
              strokeWidth: 2.5,
              color: AppColors.darkBlue,
            ),
          ),
        ),
      );
    }
    if (items.isEmpty) return const SizedBox.shrink();

    return Container(
      color: bg,
      width: double.infinity,
      padding: EdgeInsets.symmetric(horizontal: 14.w, vertical: 12.h),
      child: GridView.builder(
        shrinkWrap: true,
        physics: const NeverScrollableScrollPhysics(),
        itemCount: items.length,
        gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: crossAxisCount,
          mainAxisSpacing: 12.h,
          crossAxisSpacing: 12.w,
          childAspectRatio: 0.88,
        ),
        itemBuilder: (context, index) {
          final it = items[index];
          final tint = _accentTints[index % _accentTints.length];
          return _SearchCategoryHitCard(
            name: displayName(it),
            count: it.matchingAdsCount,
            iconUrl: it.icon,
            circleTint: tint,
            onTap: () => onPick(it),
          );
        },
      ),
    );
  }
}

class _SearchCategoryHitCard extends StatelessWidget {
  const _SearchCategoryHitCard({
    required this.name,
    required this.count,
    this.iconUrl,
    required this.circleTint,
    required this.onTap,
  });

  final String name;
  final int count;
  final String? iconUrl;
  final Color circleTint;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final url = iconUrl?.trim();
    final hasImage = url != null &&
        url.isNotEmpty &&
        (url.startsWith('http://') || url.startsWith('https://'));

    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(16.r),
      elevation: 2,
      shadowColor: Colors.black26,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16.r),
        child: Padding(
          padding: EdgeInsets.symmetric(vertical: 16.h, horizontal: 10.w),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              SizedBox(
                width: 56.w,
                height: 56.w,
                child: hasImage
                    ? ClipOval(
                        child: CachedUrlImage(
                          imageUrl: url,
                          width: 56.w,
                          height: 56.w,
                          fit: BoxFit.cover,
                          errorBuilder: (_, Object _) => _folderCircle(circleTint),
                        ),
                      )
                    : _folderCircle(circleTint),
              ),
              SizedBox(height: 10.h),
              Text(
                name,
                textAlign: TextAlign.center,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  fontWeight: FontWeight.w700,
                  fontSize: 13.sp,
                  color: Colors.black87,
                  height: 1.2,
                ),
              ),
              SizedBox(height: 6.h),
              Text(
                '${NumeralHelper.formatWithThousands(count)} ${AppLocale.tr('ads_count')}',
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 12.sp,
                  color: Colors.grey[600],
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _folderCircle(Color tint) {
    return DecoratedBox(
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: tint,
      ),
      child: Center(
        child: Icon(
          Icons.folder_rounded,
          size: 28.sp,
          color: const Color(0xFFFF9800),
        ),
      ),
    );
  }
}
