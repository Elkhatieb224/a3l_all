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
    return SvgPicture.asset(
      isFeatured ? 'assets/images/featured_badge.svg' : 'assets/images/urgent_badge.svg',
      width: size,
      height: size,
      fit: BoxFit.contain,
    );
  }
}
