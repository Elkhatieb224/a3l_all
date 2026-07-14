import 'dart:ui' as ui;

import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/rendering.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:flutter/services.dart';
import 'package:image_gallery_saver_plus/image_gallery_saver_plus.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:share_plus/share_plus.dart';

class ShareProfilePage extends StatefulWidget {
  const ShareProfilePage({
    super.key,
    required this.userName,
    required this.profileUrl,
  });

  final String userName;
  final String profileUrl;

  @override
  State<ShareProfilePage> createState() => _ShareProfilePageState();
}

class _ShareProfilePageState extends State<ShareProfilePage> {
  final GlobalKey _cardBoundaryKey = GlobalKey();
  bool _isProcessing = false;

  String get _displayName {
    final name = widget.userName.trim();
    return name.isEmpty ? '—' : name;
  }

  String get _domainLabel {
    final uri = Uri.tryParse(widget.profileUrl);
    final host = uri?.host.trim() ?? '';
    return host.isEmpty ? widget.profileUrl : host;
  }

  Future<Uint8List> _captureCardBytes() async {
    final context = _cardBoundaryKey.currentContext;
    if (context == null) {
      throw Exception('Share card is not ready');
    }

    final boundary = context.findRenderObject() as RenderRepaintBoundary;
    final image = await boundary.toImage(pixelRatio: 3);
    final byteData = await image.toByteData(format: ui.ImageByteFormat.png);
    if (byteData == null) {
      throw Exception('Failed to render share image');
    }
    return byteData.buffer.asUint8List();
  }

  Future<void> _runTask(Future<void> Function() action) async {
    if (_isProcessing) return;
    setState(() => _isProcessing = true);
    try {
      await action();
    } finally {
      if (mounted) {
        setState(() => _isProcessing = false);
      }
    }
  }

  Future<void> _copyLink() async {
    await Clipboard.setData(ClipboardData(text: widget.profileUrl));
    showToast(message: _localized('تم نسخ الرابط', 'Link copied', 'Baglanti kopyalandi'));
  }

  Future<void> _shareImage() async {
    await _runTask(() async {
      try {
        final bytes = await _captureCardBytes();
        final safeName = _displayName.replaceAll(RegExp(r'[^a-zA-Z0-9\u0600-\u06FF_-]'), '_');
        await Share.shareXFiles(
          [
            XFile.fromData(
              bytes,
              mimeType: 'image/png',
              name: 'share_$safeName.png',
            ),
          ],
          text: widget.profileUrl,
        );
      } catch (_) {
        showToast(
          message: _localized(
            'تعذر مشاركة الصورة',
            'Could not share image',
            'Gorsel paylasilamadi',
          ),
        );
      }
    });
  }

  Future<void> _saveImage() async {
    await _runTask(() async {
      try {
        final bytes = await _captureCardBytes();
        final isMobile =
            !kIsWeb &&
            (defaultTargetPlatform == TargetPlatform.android ||
                defaultTargetPlatform == TargetPlatform.iOS);

        if (isMobile) {
          final result = await ImageGallerySaverPlus.saveImage(
            bytes,
            quality: 100,
            name: 'aalenha_${DateTime.now().millisecondsSinceEpoch}',
          );
          final success = result['isSuccess'] == true || result['success'] == true;
          if (!success) {
            throw Exception('Gallery save failed');
          }
        } else {
          throw Exception('Save is not supported on this platform');
        }

        showToast(
          message: _localized(
            'تم حفظ الصورة',
            'Image saved',
            'Gorsel kaydedildi',
          ),
        );
      } catch (_) {
        showToast(
          message: _localized(
            'تعذر حفظ الصورة',
            'Could not save image',
            'Gorsel kaydedilemedi',
          ),
        );
      }
    });
  }

  String _localized(String ar, String en, String tr) {
    switch (AppLocale.current) {
      case 'en':
        return en;
      case 'tr':
        return tr;
      default:
        return ar;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF6C53B),
      appBar: CustomAppbar(title: AppLocale.tr('share')),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: EdgeInsets.symmetric(horizontal: 18.w, vertical: 12.h),
          child: Column(
            children: [
              RepaintBoundary(
                key: _cardBoundaryKey,
                child: _ShareCard(
                  userName: _displayName,
                  profileUrl: widget.profileUrl,
                  domainLabel: _domainLabel,
                ),
              ),
              SizedBox(height: 22.h),
              _ShareActions(
                isProcessing: _isProcessing,
                onSave: _saveImage,
                onCopyLink: _copyLink,
                onShare: _shareImage,
                copyLabel: _localized('نسخ الرابط', 'Copy link', 'Baglantiyi kopyala'),
                saveLabel: _localized('تنزيل', 'Download', 'Indir'),
                shareLabel: _localized('مشاركة الملف الشخصي', 'Share profile', 'Profili paylas'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ShareCard extends StatelessWidget {
  const _ShareCard({
    required this.userName,
    required this.profileUrl,
    required this.domainLabel,
  });

  final String userName;
  final String profileUrl;
  final String domainLabel;

  @override
  Widget build(BuildContext context) {
    return AspectRatio(
      aspectRatio: 374 / 810,
      child: LayoutBuilder(
        builder: (context, constraints) {
          final width = constraints.maxWidth;
          final height = constraints.maxHeight;
          final qrSize = width * 0.48;
          final darkBlue = const Color(0xFF0A366D);

          return ClipRRect(
            borderRadius: BorderRadius.circular(26.r),
            child: Stack(
              children: [
                Positioned.fill(
                  child: Container(color: const Color(0xFFF6C53B)),
                ),
                Positioned.fill(
                  child: Image.asset(
                    'assets/images/sharescreen.png',
                    fit: BoxFit.cover,
                  ),
                ),
                Positioned(
                  left: width * 0.06,
                  right: width * 0.06,
                  top: height * 0.63,
                  bottom: height * 0.07,
                  child: Container(
                    decoration: BoxDecoration(
                      color: darkBlue,
                      borderRadius: BorderRadius.circular(width * 0.03),
                    ),
                  ),
                ),
                Positioned(
                  top: height * 0.04,
                  left: width * 0.08,
                  right: width * 0.08,
                  child: _TopNameBubble(userName: userName),
                ),
                Positioned(
                  left: width * 0.17,
                  top: height * 0.37,
                  child: Container(
                    width: qrSize,
                    height: qrSize,
                    decoration: BoxDecoration(
                      color: const Color(0xFFFDE062),
                      borderRadius: BorderRadius.circular(width * 0.03),
                    ),
                  ),
                ),
                Positioned(
                  left: (width - qrSize) / 2,
                  top: height * 0.392,
                  child: Container(
                    width: qrSize,
                    height: qrSize,
                    padding: EdgeInsets.all(width * 0.018),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(width * 0.02),
                    ),
                    child: QrImageView(
                      data: profileUrl,
                      version: QrVersions.auto,
                      eyeStyle: const QrEyeStyle(
                        eyeShape: QrEyeShape.square,
                        color: Color(0xFF10376B),
                      ),
                      dataModuleStyle: const QrDataModuleStyle(
                        dataModuleShape: QrDataModuleShape.square,
                        color: Color(0xFF10376B),
                      ),
                      backgroundColor: Colors.white,
                    ),
                  ),
                ),
                Positioned(
                  left: width * 0.08,
                  right: width * 0.08,
                  bottom: height * 0.125,
                  child: Text(
                    _localizedSubtitle(),
                    textAlign: TextAlign.center,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 13.sp,
                      fontWeight: FontWeight.w500,
                      height: 1.45,
                    ),
                  ),
                ),
                Positioned(
                  left: width * 0.09,
                  right: width * 0.09,
                  bottom: height * 0.03,
                  child: Container(
                    padding: EdgeInsets.symmetric(horizontal: 14.w, vertical: 12.h),
                    decoration: BoxDecoration(
                      color: darkBlue,
                      borderRadius: BorderRadius.circular(18.r),
                    ),
                    child: Text(
                      domainLabel,
                      textAlign: TextAlign.center,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 13.sp,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  String _localizedSubtitle() {
    switch (AppLocale.current) {
      case 'en':
        return 'For more details about our ads';
      case 'tr':
        return 'Ilanlarimiz hakkinda daha fazla bilgi icin';
      default:
        return 'لمعرفة المزيد عن تفاصيل الإعلانات لدينا';
    }
  }
}

class _TopNameBubble extends StatelessWidget {
  const _TopNameBubble({required this.userName});

  final String userName;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.symmetric(horizontal: 20.w, vertical: 14.h),
      decoration: BoxDecoration(
        color: const Color(0xFFF4F0EA),
        borderRadius: BorderRadius.circular(24.r),
      ),
      child: Text(
        userName,
        textAlign: TextAlign.center,
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        style: TextStyle(
          color: AppColors.darkBlue,
          fontSize: 18.sp,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}

class _ShareActions extends StatelessWidget {
  const _ShareActions({
    required this.isProcessing,
    required this.onSave,
    required this.onCopyLink,
    required this.onShare,
    required this.copyLabel,
    required this.saveLabel,
    required this.shareLabel,
  });

  final bool isProcessing;
  final VoidCallback onSave;
  final VoidCallback onCopyLink;
  final VoidCallback onShare;
  final String copyLabel;
  final String saveLabel;
  final String shareLabel;

  @override
  Widget build(BuildContext context) {
    return AbsorbPointer(
      absorbing: isProcessing,
      child: Opacity(
        opacity: isProcessing ? 0.7 : 1,
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceEvenly,
          children: [
            _ShareActionButton(
              icon: Icons.download_rounded,
              label: saveLabel,
              onTap: onSave,
            ),
            _ShareActionButton(
              icon: Icons.link_rounded,
              label: copyLabel,
              onTap: onCopyLink,
            ),
            _ShareActionButton(
              icon: Icons.share_outlined,
              label: shareLabel,
              onTap: onShare,
            ),
          ],
        ),
      ),
    );
  }
}

class _ShareActionButton extends StatelessWidget {
  const _ShareActionButton({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(18.r),
        child: Padding(
          padding: EdgeInsets.symmetric(horizontal: 6.w, vertical: 8.h),
          child: Column(
            children: [
              Container(
                width: 52.w,
                height: 52.w,
                decoration: const BoxDecoration(
                  shape: BoxShape.circle,
                  color: Colors.white,
                ),
                child: Icon(icon, color: AppColors.darkBlue, size: 26.sp),
              ),
              SizedBox(height: 8.h),
              Text(
                label,
                textAlign: TextAlign.center,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  fontSize: 11.sp,
                  fontWeight: FontWeight.w600,
                  color: AppColors.darkBlue,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
