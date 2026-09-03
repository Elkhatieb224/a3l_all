import 'package:a3lnha/core/cache/app_image_cache.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';

bool _isSvgUrl(String url) {
  final lower = url.toLowerCase();
  return lower.endsWith('.svg') || lower.contains('.svg?');
}

/// أيقونات الأقسام من السيرفر غالباً SVG من Illustrator (مئات الـ KB + filters).
/// `flutter_svg` لا يدعمها بشكل آمن ويستنزف الذاكرة في release / Play → شاشة رمادية.
Widget _networkSvgFallback({
  required BuildContext context,
  double? width,
  double? height,
  Widget Function(BuildContext)? placeholder,
  Widget Function(BuildContext, Object)? errorBuilder,
}) {
  if (errorBuilder != null) {
    return SizedBox(
      width: width,
      height: height,
      child: errorBuilder(context, StateError('network-svg-skipped')),
    );
  }
  if (placeholder != null) {
    return SizedBox(
      width: width,
      height: height,
      child: placeholder(context),
    );
  }
  return SizedBox(
    width: width,
    height: height,
    child: ColoredBox(color: Colors.grey[200]!),
  );
}

class AvatarWithFallback extends StatelessWidget {
  final String? imageUrl;
  final double radius;
  final String fallbackLetter;
  final double? fontSize;
  final Widget? fallbackIcon;

  const AvatarWithFallback({
    super.key,
    required this.imageUrl,
    required this.radius,
    this.fallbackLetter = '?',
    this.fontSize,
    this.fallbackIcon,
  });

  @override
  Widget build(BuildContext context) {
    final url = imageUrl?.trim();
    final fs = fontSize ?? radius * 0.8;
    final fallback = fallbackIcon ??
        Center(
          child: Text(
            fallbackLetter,
            style: TextStyle(
              fontSize: fs,
              color: AppColors.darkBlue,
              fontWeight: FontWeight.bold,
            ),
          ),
        );
    return CircleAvatar(
      radius: radius,
      backgroundColor: Colors.grey[300],
      child: url == null || url.isEmpty || _isSvgUrl(url)
          ? fallback
          : ClipOval(
              child: CachedNetworkImage(
                imageUrl: url,
                cacheManager: AppImageCache.instance,
                width: radius * 2,
                height: radius * 2,
                fit: BoxFit.cover,
                placeholder: (_, __) => fallback,
                errorWidget: (_, __, ___) => fallback,
                fadeInDuration: Duration.zero,
                fadeOutDuration: Duration.zero,
              ),
            ),
    );
  }
}

/// صورة من الشبكة مع placeholder عند التحميل أو الفشل أو غياب الرابط.
class AppNetworkImage extends StatelessWidget {
  final String? imageUrl;
  final double? width;
  final double? height;
  final BoxFit fit;
  final BorderRadius? borderRadius;
  final Widget Function(BuildContext)? placeholder;

  const AppNetworkImage({
    super.key,
    required this.imageUrl,
    this.width,
    this.height,
    this.fit = BoxFit.cover,
    this.borderRadius,
    this.placeholder,
  });

  /// حجم أيقونة placeholder: يجب أن يكون دائماً محدوداً (تجنّب ∞ عند width/height = infinity).
  double _placeholderIconSize() {
    double? w = width;
    double? h = height;
    if (w != null && !w.isFinite) w = null;
    if (h != null && !h.isFinite) h = null;
    if (w != null && h != null) {
      final smaller = w < h ? w : h;
      if (smaller > 0) return (smaller * 0.5).clamp(18.0, 48.0);
    }
    if (w != null && w > 0) return (w * 0.5).clamp(18.0, 48.0);
    if (h != null && h > 0) return (h * 0.5).clamp(18.0, 48.0);
    return 40;
  }

  Widget _placeholder(BuildContext context) {
    if (placeholder != null) return placeholder!(context);
    return Container(
      color: Colors.grey[200],
      child: Icon(
        Icons.image_not_supported,
        color: Colors.grey[400],
        size: _placeholderIconSize(),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final url = imageUrl?.trim();
    if (url == null || url.isEmpty) {
      return ClipRRect(
        borderRadius: borderRadius ?? BorderRadius.zero,
        child: SizedBox(
          width: width,
          height: height,
          child: _placeholder(context),
        ),
      );
    }
    if (_isSvgUrl(url)) {
      return ClipRRect(
        borderRadius: borderRadius ?? BorderRadius.zero,
        child: _networkSvgFallback(
          context: context,
          width: width,
          height: height,
          placeholder: (_) => _placeholder(context),
        ),
      );
    }
    return ClipRRect(
      borderRadius: borderRadius ?? BorderRadius.zero,
      child: CachedNetworkImage(
        imageUrl: url,
        cacheManager: AppImageCache.instance,
        width: width,
        height: height,
        fit: fit,
        progressIndicatorBuilder: (_, __, ___) => SizedBox(
          width: width,
          height: height,
          child: _placeholder(context),
        ),
        errorWidget: (_, __, ___) => SizedBox(
          width: width,
          height: height,
          child: _placeholder(context),
        ),
        fadeInDuration: Duration.zero,
        fadeOutDuration: Duration.zero,
      ),
    );
  }
}

/// استبدال مباشر لـ [Image.network] مع كاش القرص (قوائم، بطاقات، إلخ).
class CachedUrlImage extends StatelessWidget {
  final String imageUrl;
  final double? width;
  final double? height;
  final BoxFit fit;
  final Widget Function(BuildContext context)? placeholder;
  final Widget Function(BuildContext context, Object error)? errorBuilder;

  const CachedUrlImage({
    super.key,
    required this.imageUrl,
    this.width,
    this.height,
    this.fit = BoxFit.cover,
    this.placeholder,
    this.errorBuilder,
  });

  @override
  Widget build(BuildContext context) {
    final url = imageUrl.trim();
    final ph = placeholder;
    if (url.isEmpty) {
      return SizedBox(
        width: width,
        height: height,
        child: ph?.call(context) ?? const SizedBox.shrink(),
      );
    }
    if (_isSvgUrl(url)) {
      return _networkSvgFallback(
        context: context,
        width: width,
        height: height,
        placeholder: ph,
        errorBuilder: errorBuilder,
      );
    }
    return CachedNetworkImage(
      imageUrl: url,
      cacheManager: AppImageCache.instance,
      width: width,
      height: height,
      fit: fit,
      progressIndicatorBuilder: (_, __, ___) => SizedBox(
        width: width,
        height: height,
        child: ph?.call(context) ?? ColoredBox(color: Colors.grey[200]!),
      ),
      errorWidget: errorBuilder != null
          ? (_, __, err) => errorBuilder!(context, err)
          : (_, __, ___) => SizedBox(
              width: width,
              height: height,
              child: ph?.call(context) ?? ColoredBox(color: Colors.grey[200]!),
            ),
      fadeInDuration: Duration.zero,
      fadeOutDuration: Duration.zero,
    );
  }
}


/// مصغّر لقوائم الإعلانات: حجم الصندوق من [width] و [maxHeight] (عرض موحّد من المتصل).
/// الصورة تُعرض كاملة داخل الصندوق بـ [BoxFit.contain] (مصغّرة مع فراغات إن لزم،
/// مثلاً للصور الطويلة يظهر فراغ يمين/يسار) بدلاً من [BoxFit.cover] الذي يقصّ الصورة.
class ListAdThumbnailImage extends StatelessWidget {
  final String imageUrl;
  final double width;
  /// ارتفاع صندوق المصغّر (غالباً يساوي [width] للمربع، أو أقل في بعض الشاشات).
  final double maxHeight;
  final Widget Function(BuildContext context)? placeholder;
  final Widget Function(BuildContext context, Object error)? errorBuilder;

  const ListAdThumbnailImage({
    super.key,
    required this.imageUrl,
    required this.width,
    required this.maxHeight,
    this.placeholder,
    this.errorBuilder,
  });

  Widget _defaultPlaceholder() => ColoredBox(color: Colors.grey[200]!);

  @override
  Widget build(BuildContext context) {
    final url = imageUrl.trim();
    final boxW = width;
    final boxH = (maxHeight > 0 && maxHeight.isFinite) ? maxHeight : boxW;

    if (url.isEmpty) {
      return SizedBox(
        width: boxW,
        height: boxH,
        child: placeholder?.call(context) ?? _defaultPlaceholder(),
      );
    }

    return CachedUrlImage(
      imageUrl: url,
      width: boxW,
      height: boxH,
      fit: BoxFit.contain,
      placeholder: placeholder != null
          ? (_) => SizedBox(
                width: boxW,
                height: boxH,
                child: placeholder!(context),
              )
          : null,
      errorBuilder: errorBuilder != null
          ? (c, e) => SizedBox(
                width: boxW,
                height: boxH,
                child: errorBuilder!(c, e),
              )
          : null,
    );
  }
}
