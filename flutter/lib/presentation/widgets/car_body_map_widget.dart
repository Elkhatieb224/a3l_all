import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/support/car_body_map_support.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:path_parsing/path_parsing.dart';

Path _pathFromSvg(String data) {
  final path = Path();
  writeSvgPathDataToPath(data, _FlutterPathProxy(path));
  return path;
}

class _FlutterPathProxy implements PathProxy {
  _FlutterPathProxy(this._path);

  final Path _path;

  @override
  void close() => _path.close();

  @override
  void cubicTo(double x1, double y1, double x2, double y2, double x3, double y3) {
    _path.cubicTo(x1, y1, x2, y2, x3, y3);
  }

  @override
  void lineTo(double x, double y) => _path.lineTo(x, y);

  @override
  void moveTo(double x, double y) => _path.moveTo(x, y);
}

/// مخطط تفاعلي لحالة هيكل السيارة (منظر علوي).
class CarBodyMapWidget extends StatefulWidget {
  const CarBodyMapWidget({
    super.key,
    required this.label,
    this.initialValue,
    required this.onChanged,
  });

  final String label;
  final Map<String, dynamic>? initialValue;
  final ValueChanged<Map<String, dynamic>> onChanged;

  @override
  State<CarBodyMapWidget> createState() => _CarBodyMapWidgetState();
}

class _CarBodyMapWidgetState extends State<CarBodyMapWidget> {
  late Map<String, String> _parts;
  late bool _allOriginal;

  @override
  void initState() {
    super.initState();
    _applyNormalized(CarBodyMapSupport.normalizeValue(widget.initialValue));
  }

  @override
  void didUpdateWidget(covariant CarBodyMapWidget oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.initialValue != widget.initialValue) {
      _applyNormalized(CarBodyMapSupport.normalizeValue(widget.initialValue));
    }
  }

  void _applyNormalized(Map<String, dynamic> normalized) {
    final rawParts = normalized['parts'];
    _parts = rawParts is Map
        ? rawParts.map((k, v) => MapEntry(k.toString(), v.toString()))
        : CarBodyMapSupport.defaultParts();
    _allOriginal = normalized['all_original'] == true;
  }

  Map<String, dynamic> _buildPayload() {
    return CarBodyMapSupport.normalizeValue({
      'parts': Map<String, String>.from(_parts),
      'all_original': _allOriginal,
    });
  }

  void _emitChange() {
    widget.onChanged(_buildPayload());
  }

  void _setPartStatus(String partId, String status) {
    setState(() {
      _parts[partId] = status;
      _allOriginal = _parts.values.every((s) => s == CarBodyMapSupport.statusOriginal);
    });
    _emitChange();
  }

  void _setAllOriginal(bool value) {
    setState(() {
      _allOriginal = value;
      if (value) {
        for (final id in CarBodyMapSupport.partIds) {
          _parts[id] = CarBodyMapSupport.statusOriginal;
        }
      }
    });
    _emitChange();
  }

  Future<void> _showStatusPicker(String partId) async {
    final selected = await showModalBottomSheet<String>(
      context: context,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(16.r)),
      ),
      builder: (ctx) {
        return SafeArea(
          child: Padding(
            padding: EdgeInsets.symmetric(vertical: 12.h),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Padding(
                  padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 8.h),
                  child: Text(
                    CarBodyMapSupport.partLabel(partId),
                    style: TextStyle(fontSize: 16.sp, fontWeight: FontWeight.w600),
                    textAlign: TextAlign.center,
                  ),
                ),
                ...CarBodyMapSupport.statuses.map((status) {
                  final color = Color(CarBodyMapSupport.statusColors[status] ?? 0xFFE2E8F0);
                  return ListTile(
                    leading: Container(
                      width: 18.w,
                      height: 18.w,
                      decoration: BoxDecoration(
                        color: color,
                        borderRadius: BorderRadius.circular(4.r),
                        border: Border.all(color: Colors.grey.shade400),
                      ),
                    ),
                    title: Text(
                      CarBodyMapSupport.statusLabel(status),
                      style: TextStyle(fontSize: 14.sp),
                    ),
                    onTap: () => Navigator.pop(ctx, status),
                  );
                }),
              ],
            ),
          ),
        );
      },
    );

    if (selected != null) {
      _setPartStatus(partId, selected);
    }
  }

  @override
  Widget build(BuildContext context) {
    final normalized = _buildPayload();
    final summary = normalized['summary'];
    final summaryText = summary is Map
        ? (summary[AppLocale.current] ?? summary['ar'] ?? '').toString()
        : '';
    final changed = CarBodyMapSupport.countNonOriginal(_parts);
    final total = CarBodyMapSupport.partIds.length;

    return Padding(
      padding: EdgeInsets.only(bottom: 12.h),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            widget.label,
            style: TextStyle(fontSize: 14.sp, fontWeight: FontWeight.w500),
          ),
          SizedBox(height: 10.h),
          Wrap(
            spacing: 12.w,
            runSpacing: 8.h,
            children: CarBodyMapSupport.statuses.map((status) {
              final color = Color(CarBodyMapSupport.statusColors[status] ?? 0xFFE2E8F0);
              return Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 14.w,
                    height: 14.w,
                    decoration: BoxDecoration(
                      color: color,
                      borderRadius: BorderRadius.circular(3.r),
                      border: Border.all(color: Colors.grey.shade400),
                    ),
                  ),
                  SizedBox(width: 6.w),
                  Text(
                    CarBodyMapSupport.statusLabel(status),
                    style: TextStyle(fontSize: 11.sp, color: Colors.grey.shade700),
                  ),
                ],
              );
            }).toList(),
          ),
          SizedBox(height: 8.h),
          Text(
            AppLocale.tr('car_body_progress')
                .replaceAll(':count', '$changed')
                .replaceAll(':total', '$total'),
            style: TextStyle(fontSize: 12.sp, color: Colors.grey.shade600),
          ),
          SizedBox(height: 12.h),
          Center(
            child: SizedBox(
              width: 240.w,
              child: AspectRatio(
                aspectRatio: CarBodyMapSupport.diagramWidth / CarBodyMapSupport.diagramHeight,
                child: _CarBodyMapCanvas(
                  parts: _parts,
                  onPartTap: _showStatusPicker,
                ),
              ),
            ),
          ),
          SizedBox(height: 12.h),
          CheckboxListTile(
            value: _allOriginal,
            onChanged: (v) => _setAllOriginal(v ?? false),
            contentPadding: EdgeInsets.zero,
            controlAffinity: ListTileControlAffinity.leading,
            dense: true,
            title: Text(
              AppLocale.tr('car_body_all_original_checkbox'),
              style: TextStyle(fontSize: 13.sp),
            ),
          ),
          SizedBox(height: 8.h),
          Text(
            AppLocale.tr('car_body_auto_summary_title'),
            style: TextStyle(fontSize: 13.sp, fontWeight: FontWeight.w600),
          ),
          SizedBox(height: 6.h),
          Container(
            width: double.infinity,
            padding: EdgeInsets.all(12.w),
            decoration: BoxDecoration(
              color: Colors.grey.shade50,
              borderRadius: BorderRadius.circular(10.r),
              border: Border.all(color: Colors.grey.shade300),
            ),
            child: Text(
              summaryText,
              style: TextStyle(fontSize: 13.sp, height: 1.4),
            ),
          ),
        ],
      ),
    );
  }
}

class _CarBodyMapCanvas extends StatelessWidget {
  const _CarBodyMapCanvas({
    required this.parts,
    required this.onPartTap,
  });

  final Map<String, String> parts;
  final ValueChanged<String> onPartTap;

  String? _hitPart(Offset local, double scale) {
    final point = Offset(local.dx / scale, local.dy / scale);
    for (final id in CarBodyMapSupport.partIds.reversed) {
      final shape = CarBodyMapSupport.shapes[id];
      if (shape == null) continue;
      final path = _pathFromSvg(shape.pathData);
      if (path.contains(point)) return id;
    }
    return null;
  }

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final scale = constraints.maxWidth / CarBodyMapSupport.diagramWidth;
        return GestureDetector(
          behavior: HitTestBehavior.opaque,
          onTapUp: (details) {
            final partId = _hitPart(details.localPosition, scale);
            if (partId != null) onPartTap(partId);
          },
          child: DecoratedBox(
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12.r),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.06),
                  blurRadius: 8,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(12.r),
              child: Stack(
                fit: StackFit.expand,
                children: [
                  SvgPicture.asset(
                    CarBodyMapSupport.diagramAsset,
                    fit: BoxFit.contain,
                  ),
                  CustomPaint(
                    painter: _CarBodyMapPainter(parts: parts),
                    size: Size.infinite,
                  ),
                ],
              ),
            ),
          ),
        );
      },
    );
  }
}

class _CarBodyMapPainter extends CustomPainter {
  _CarBodyMapPainter({required this.parts});

  final Map<String, String> parts;

  @override
  void paint(Canvas canvas, Size size) {
    final scale = size.width / CarBodyMapSupport.diagramWidth;
    canvas.scale(scale);

    final fillPaint = Paint()..style = PaintingStyle.fill;
    final strokePaint = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = 1;

    for (final id in CarBodyMapSupport.partIds) {
      final shape = CarBodyMapSupport.shapes[id];
      if (shape == null) continue;
      final path = _pathFromSvg(shape.pathData);
      final status = parts[id] ?? CarBodyMapSupport.statusOriginal;
      final isOriginal = status == CarBodyMapSupport.statusOriginal;
      final baseColor = Color(CarBodyMapSupport.statusColors[status] ?? 0xFFE2E8F0);
      fillPaint.color = baseColor.withValues(alpha: isOriginal ? 0 : 0.55);
      canvas.drawPath(path, fillPaint);
      if (!isOriginal) {
        strokePaint.color = baseColor.withValues(alpha: 0.85);
        canvas.drawPath(path, strokePaint);
      }
    }
  }

  @override
  bool shouldRepaint(covariant _CarBodyMapPainter oldDelegate) {
    return oldDelegate.parts != parts;
  }
}
