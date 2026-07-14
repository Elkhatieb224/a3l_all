import 'package:a3lnha/core/network/api_constants.dart';
import 'package:a3lnha/data/models/category_model.dart';
import 'package:a3lnha/data/models/subcategory_model.dart';

/// مطابقة لـ [App\Support\AdImagesConfig] في الخادم
class AdImagesEffective {
  static const String userUpload = 'user_upload';
  static const String adminGallery = 'admin_gallery';
  static const int defaultMaxImages = 10;

  final String mode;
  final List<String> galleryPaths;
  final List<String> galleryUrls;
  final int maxImages;

  const AdImagesEffective({
    required this.mode,
    required this.galleryPaths,
    required this.galleryUrls,
    required this.maxImages,
  });

  bool get isAdminGallery => mode == adminGallery;

  /// بعد التحويل على الخادم قد يبقى المسار في JSON بامتداد قديم بينما الملف أصبح `.webp`.
  static String _storagePathPreferWebp(String path) {
    return path.replaceFirstMapped(
      RegExp(r'\.(png|jpe?g|gif)$', caseSensitive: false),
      (_) => '.webp',
    );
  }

  /// يوحّد نطاق روابط التخزين مع [ApiConstants.baseUrl] (يصلح APP_URL أو www مختلف عن الـ API).
  static String resolveGalleryImageUrl(String urlOrPath) {
    final t = urlOrPath.trim();
    if (t.isEmpty) return '';
    final api = Uri.parse(ApiConstants.baseUrl);
    final origin =
        '${api.scheme}://${api.host}${api.hasPort ? ':${api.port}' : ''}';

    if (t.startsWith('http://') || t.startsWith('https://')) {
      final u = Uri.tryParse(t);
      if (u != null && u.path.contains('/storage/')) {
        final path = _storagePathPreferWebp(u.path);
        final q = u.hasQuery ? '?${u.query}' : '';
        return '$origin$path$q';
      }
      return t;
    }

    var path = t.replaceFirst(RegExp(r'^/+'), '');
    if (path.startsWith('storage/')) {
      path = path.substring(8);
    }
    path = _storagePathPreferWebp(path);
    return '$origin/storage/$path';
  }

  static AdImagesEffective resolve(CategoryModel? cat, SubcategoryModel? sub) {
    if (cat == null) {
      return const AdImagesEffective(
        mode: userUpload,
        galleryPaths: [],
        galleryUrls: [],
        maxImages: defaultMaxImages,
      );
    }

    final subModeRaw = sub?.adImagesMode?.trim();
    final String modeSource = (subModeRaw == null || subModeRaw.isEmpty)
        ? (cat.adImagesMode ?? userUpload)
        : subModeRaw;

    var mode = modeSource;
    if (mode != userUpload && mode != adminGallery) {
      mode = userUpload;
    }

    var paths = <String>[];
    if (mode == adminGallery) {
      paths = List<String>.from(sub?.adGalleryPaths ?? const []);
      if (paths.isEmpty) {
        paths = List<String>.from(cat.adGalleryPaths ?? const []);
      }
    }

    if (mode == adminGallery && paths.isEmpty) {
      mode = userUpload;
    }

    final urls = <String>[];
    if (mode == adminGallery) {
      List<String>? rawUrls;
      if ((sub?.adGalleryPaths ?? []).isNotEmpty) {
        rawUrls = sub?.adGalleryUrls;
      } else {
        rawUrls = cat.adGalleryUrls;
      }
      for (var i = 0; i < paths.length; i++) {
        final u = (rawUrls != null && i < rawUrls.length) ? rawUrls[i] : '';
        if (u.isNotEmpty) {
          urls.add(resolveGalleryImageUrl(u));
        } else {
          urls.add(resolveGalleryImageUrl(paths[i]));
        }
      }
    }

    final subMax = sub?.adImagesMax;
    final catMax = cat.adImagesMax;
    final maxImages = (subMax != null && subMax > 0)
        ? subMax
        : ((catMax != null && catMax > 0) ? catMax : defaultMaxImages);

    return AdImagesEffective(
      mode: mode,
      galleryPaths: paths,
      galleryUrls: urls,
      maxImages: maxImages,
    );
  }
}
