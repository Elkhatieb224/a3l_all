import 'package:a3lnha/core/data/location_translations.dart';
import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/data/models/ad_model.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

/// سطر الموقع في بطاقات القوائم: مدينة مترجمة، أو عنوان من الإحداثيات عند التحديد على الخريطة.
class AdListLocationLabel extends StatefulWidget {
  final AdModel ad;
  final TextStyle style;
  final double iconSize;
  final double gap;
  final Color? iconColor;
  final Widget? leading;
  final int maxLines;

  const AdListLocationLabel({
    super.key,
    required this.ad,
    required this.style,
    this.iconSize = 14,
    this.gap = 4,
    this.iconColor,
    this.leading,
    this.maxLines = 1,
  });

  @override
  State<AdListLocationLabel> createState() => _AdListLocationLabelState();
}

class _AdListLocationLabelState extends State<AdListLocationLabel> {
  @override
  Widget build(BuildContext context) {
    final ad = widget.ad;
    final iconColor = widget.iconColor ?? Colors.grey[600];
    final locale = AppLocale.current;
    String clean(String? s) =>
        (s ?? '')
            .trim()
            .replaceAll(RegExp(r'[,\u060C]'), '')
            .replaceAll(RegExp(r'\s+'), ' ')
            .trim();
    final country = clean(
      LocationTranslations.segmentForUi(locale, ad.locationCountry ?? ''),
    );
    final state = clean(
      LocationTranslations.segmentForUi(locale, ad.locationState ?? ''),
    );
    final fallback = clean(ad.staticLocationDisplayLine);
    String text;
    if (country.isNotEmpty && state.isNotEmpty) {
      text = '$country - $state';
    } else if (state.isNotEmpty) {
      text = state;
    } else if (country.isNotEmpty) {
      text = country;
    } else {
      text = fallback.isNotEmpty ? fallback : '—';
    }

    final row = Row(
      children: [
        widget.leading ??
            Icon(
              Icons.location_on,
              size: widget.iconSize.sp,
              color: iconColor,
            ),
        SizedBox(width: widget.gap.w),
        Expanded(
          child: Text(
            text,
            style: widget.style,
            maxLines: widget.maxLines,
            overflow: TextOverflow.ellipsis,
            textAlign: TextAlign.start,
          ),
        ),
      ],
    );

    return row;
  }
}
