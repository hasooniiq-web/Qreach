import 'package:flutter/material.dart';

import '../../core/design/tokens.dart';
import '../../core/theme/app_colors.dart';

/// A bottom sheet with the app's look: a large top radius, a grab handle, and
/// content that grows above the keyboard. Use instead of a bare
/// `showModalBottomSheet` so every sheet feels the same.
Future<T?> showAppSheet<T>(
  BuildContext context, {
  required WidgetBuilder builder,
  bool scrollControlled = true,
}) {
  return showModalBottomSheet<T>(
    context: context,
    isScrollControlled: scrollControlled,
    useSafeArea: true,
    showDragHandle: false,
    backgroundColor: AppColors.surface,
    barrierColor: Colors.black.withValues(alpha: 0.45),
    shape: const RoundedRectangleBorder(
      borderRadius: BorderRadius.vertical(top: Radius.circular(AppRadius.lg)),
    ),
    clipBehavior: Clip.antiAlias,
    builder: (context) => SafeArea(
      top: false,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const SizedBox(height: 10),
          Container(
            width: 40,
            height: 4,
            decoration: BoxDecoration(
              color: AppColors.ink200,
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          const SizedBox(height: 8),
          Flexible(child: builder(context)),
        ],
      ),
    ),
  );
}
