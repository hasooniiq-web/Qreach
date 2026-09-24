import 'package:flutter/material.dart';

import 'theme/app_colors.dart';

/// Shown only while [AuthController] resolves whether a token is already
/// stored — the router redirects away from here the instant that's known,
/// so this never needs its own navigation logic.
class SplashScreen extends StatelessWidget {
  const SplashScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      backgroundColor: AppColors.brandBlack,
      body: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            _Logo(),
            SizedBox(height: 16),
            CircularProgressIndicator(
                color: AppColors.brandYellow, strokeWidth: 2.5),
          ],
        ),
      ),
    );
  }
}

class _Logo extends StatelessWidget {
  const _Logo();

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 64,
      height: 64,
      decoration: const BoxDecoration(
          color: AppColors.brandYellow, shape: BoxShape.circle),
      alignment: Alignment.center,
      child: const Text(
        'Q',
        style: TextStyle(
            color: AppColors.brandBlack,
            fontSize: 28,
            fontWeight: FontWeight.w700),
      ),
    );
  }
}
