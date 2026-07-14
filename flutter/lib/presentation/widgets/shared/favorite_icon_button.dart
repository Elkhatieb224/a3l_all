import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/storage/token_storage.dart';
import 'package:a3lnha/data/services/favorite_service.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:flutter/material.dart';

/// زر المفضلة للإعلان - يظهر في جميع صفحات عرض الإعلانات
class FavoriteIconButton extends StatefulWidget {
  final String adUid;
  final bool initialIsFavorite;
  final double? size;
  final Color? backgroundColor;
  final void Function(bool isFavorite)? onChanged;

  const FavoriteIconButton({
    super.key,
    required this.adUid,
    this.initialIsFavorite = false,
    this.size,
    this.backgroundColor,
    this.onChanged,
  });

  @override
  State<FavoriteIconButton> createState() => _FavoriteIconButtonState();
}

class _FavoriteIconButtonState extends State<FavoriteIconButton> {
  late bool _isFavorite;

  @override
  void initState() {
    super.initState();
    _isFavorite = widget.initialIsFavorite;
  }

  @override
  void didUpdateWidget(FavoriteIconButton oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.initialIsFavorite != widget.initialIsFavorite) {
      _isFavorite = widget.initialIsFavorite;
    }
  }

  Future<void> _onTap() async {
    if (!TokenStorage.hasToken()) {
      showToast(message: AppLocale.tr('login_to_add_favorite'));
      return;
    }
    final result = await FavoriteService.toggle(widget.adUid);
    if (!mounted) return;
    if (result != null) {
      if (result['authRequired'] == true) {
        showToast(message: AppLocale.tr('login_required'));
        return;
      }
      final isFav = result['isFavorite'] == true;
      setState(() => _isFavorite = isFav);
      widget.onChanged?.call(isFav);
      showToast(
        message: isFav ? AppLocale.tr('added_to_favorites') : AppLocale.tr('removed_from_favorites_toast'),
      );
    } else {
      showToast(message: AppLocale.tr('try_again'));
    }
  }

  @override
  Widget build(BuildContext context) {
    final size = widget.size ?? 20.0;
    return GestureDetector(
      onTap: _onTap,
      behavior: HitTestBehavior.opaque,
      child: CircleAvatar(
        radius: (size / 2) + 4,
        backgroundColor: widget.backgroundColor ??
            Colors.black.withOpacity(0.15),
        child: Icon(
          _isFavorite ? Icons.favorite : Icons.favorite_border,
          color: _isFavorite 
              ? Colors.red 
              : (widget.backgroundColor == Colors.white 
                  ? Colors.grey[700] 
                  : Colors.white),
          size: size,
        ),
      ),
    );
  }
}
