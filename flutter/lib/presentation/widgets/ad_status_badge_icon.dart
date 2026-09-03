import 'package:flutter/widgets.dart';
import 'package:flutter_svg/flutter_svg.dart';

class AdStatusBadgeIcon extends StatelessWidget {
  final bool isFeatured;
  final double size;

  const AdStatusBadgeIcon.featured({
    super.key,
    this.size = 18,
  }) : isFeatured = true;

  const AdStatusBadgeIcon.urgent({
    super.key,
    this.size = 18,
  }) : isFeatured = false;

  @override
  Widget build(BuildContext context) {
    // featured_badge.svg ملف ضخم مُحوَّل من صورة ويسبب ضغط ذاكرة في release.
    if (isFeatured) {
      return Image.asset(
        'assets/images/featured_badge.png',
        width: size,
        height: size,
        fit: BoxFit.contain,
        filterQuality: FilterQuality.medium,
      );
    }
    return SvgPicture.asset(
      'assets/images/urgent_badge.svg',
      width: size,
      height: size,
      fit: BoxFit.contain,
    );
  }
}
