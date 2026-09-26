import 'package:flutter/material.dart';

import '../shared/widgets/brand_mark.dart';
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
            BrandMark(size: 84),
            SizedBox(height: 28),
            SizedBox(
              width: 22,
              height: 22,
              child: CircularProgressIndicator(
                  color: AppColors.brandYellow, strokeWidth: 2.5),
            ),
          ],
        ),
      ),
    );
  }
}
