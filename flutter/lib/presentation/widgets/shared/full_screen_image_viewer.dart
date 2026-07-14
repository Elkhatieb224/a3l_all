import 'package:a3lnha/core/cache/app_image_cache.dart';
import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:photo_view/photo_view.dart';
import 'package:photo_view/photo_view_gallery.dart';

/// معرض صور بملء الشاشة مع إمكانية التكبير والتمرير
class FullScreenImageViewer extends StatefulWidget {
  final List<String> imageUrls;
  final int initialIndex;

  const FullScreenImageViewer({
    super.key,
    required this.imageUrls,
    this.initialIndex = 0,
  });

  static Future<void> show(
    BuildContext context, {
    required List<String> imageUrls,
    int initialIndex = 0,
  }) {
    return Navigator.of(context).push(
      MaterialPageRoute(
        fullscreenDialog: true,
        builder: (ctx) => FullScreenImageViewer(
          imageUrls: imageUrls,
          initialIndex: initialIndex,
        ),
      ),
    );
  }

  @override
  State<FullScreenImageViewer> createState() => _FullScreenImageViewerState();
}

class _FullScreenImageViewerState extends State<FullScreenImageViewer> {
  late PageController _pageController;
  late int _currentIndex;

  @override
  void initState() {
    super.initState();
    _pageController = PageController(initialPage: widget.initialIndex);
    _currentIndex = widget.initialIndex;
  }

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final images = widget.imageUrls;
    final isLandscape =
        MediaQuery.of(context).orientation == Orientation.landscape;
    final closeIconSize = isLandscape ? 18.sp : 24.sp;
    final closePadding = isLandscape ? 8.w : 12.w;
    final closeRadius = isLandscape ? 18.r : 24.r;
    final counterFontSize = isLandscape ? 11.sp : 14.sp;
    final counterH = isLandscape ? 12.w : 16.w;
    final counterV = isLandscape ? 5.h : 8.h;
    final counterBottomOffset = isLandscape ? 10.h : 24.h;

    return Scaffold(
      backgroundColor: Colors.black,
      body: Stack(
        children: [
          PhotoViewGallery.builder(
            scrollPhysics: const BouncingScrollPhysics(),
            builder: (BuildContext context, int index) {
              return PhotoViewGalleryPageOptions(
                imageProvider: CachedNetworkImageProvider(
                  images[index],
                  cacheManager: AppImageCache.instance,
                ),
                initialScale: PhotoViewComputedScale.contained,
                minScale: PhotoViewComputedScale.contained * 0.8,
                maxScale: PhotoViewComputedScale.covered * 2.5,
              );
            },
            itemCount: images.length,
            loadingBuilder: (context, event) => Center(
              child: CircularProgressIndicator(
                value: event == null || event.expectedTotalBytes == null
                    ? null
                    : event.cumulativeBytesLoaded / event.expectedTotalBytes!,
                color: Colors.white,
              ),
            ),
            backgroundDecoration: const BoxDecoration(color: Colors.black),
            pageController: _pageController,
            onPageChanged: (index) => setState(() => _currentIndex = index),
          ),
          // زر الإغلاق
          Positioned(
            top: MediaQuery.of(context).padding.top + 8.h,
            right: 16.w,
            child: SafeArea(
              child: Material(
                color: Colors.black54,
                borderRadius: BorderRadius.circular(closeRadius),
                child: InkWell(
                  onTap: () => Navigator.of(context).pop(),
                  borderRadius: BorderRadius.circular(closeRadius),
                  child: Padding(
                    padding: EdgeInsets.all(closePadding),
                    child: Icon(
                      Icons.close,
                      color: Colors.white,
                      size: closeIconSize,
                    ),
                  ),
                ),
              ),
            ),
          ),
          // عداد الصور (1 من 5)
          if (images.length > 1)
            Positioned(
              bottom: MediaQuery.of(context).padding.bottom + counterBottomOffset,
              left: 0,
              right: 0,
              child: Center(
                child: Container(
                  padding: EdgeInsets.symmetric(horizontal: counterH, vertical: counterV),
                  decoration: BoxDecoration(
                    color: Colors.black54,
                    borderRadius: BorderRadius.circular(20.r),
                  ),
                  child: Text(
                    AppLocale.tr('image_count_format')
                        .replaceAll('%1', '${_currentIndex + 1}')
                        .replaceAll('%2', '${images.length}'),
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: counterFontSize,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }
}
