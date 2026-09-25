import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/theme/app_colors.dart';
import '../../features/profile/presentation/profile_controller.dart';

/// The customer's photo, or their initial on yellow when they have none.
class UserAvatar extends ConsumerWidget {
  const UserAvatar({super.key, required this.name, this.radius = 20});

  final String? name;
  final double radius;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final bytes = ref.watch(profileAvatarProvider).valueOrNull;
    final initial =
        (name != null && name!.isNotEmpty) ? name!.characters.first : '؟';

    return CircleAvatar(
      radius: radius,
      backgroundColor: AppColors.brandYellow,
      backgroundImage: bytes != null ? MemoryImage(bytes) : null,
      child: bytes != null
          ? null
          : Text(initial,
              style: TextStyle(
                  color: AppColors.brandBlack,
                  fontSize: radius * 0.85,
                  fontWeight: FontWeight.w800)),
    );
  }
}
