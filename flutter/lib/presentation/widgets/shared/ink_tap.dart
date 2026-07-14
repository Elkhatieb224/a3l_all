import 'package:flutter/material.dart';

/// غلاف للمس مع رد فعل بصري فوري (InkWell).
/// استخدمه بدلاً من GestureDetector لتحسين استجابة اللمس.
class InkTap extends StatelessWidget {
  final VoidCallback? onTap;
  final Widget child;
  final BorderRadius? borderRadius;
  final EdgeInsetsGeometry? padding;

  const InkTap({
    super.key,
    required this.onTap,
    required this.child,
    this.borderRadius,
    this.padding,
  });

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: borderRadius ?? BorderRadius.zero,
        splashColor: Theme.of(context).splashColor,
        highlightColor: Theme.of(context).highlightColor,
        child: Padding(
          padding: padding ?? EdgeInsets.zero,
          child: child,
        ),
      ),
    );
  }
}
