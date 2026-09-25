import 'package:flutter/material.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';

/// Shared layout for the login flow: a black hero (brand mark, title,
/// subtitle) over a white form panel that rises from the bottom. Scrolls, so
/// it stays usable on short screens and with the keyboard open.
class AuthScaffold extends StatelessWidget {
  const AuthScaffold({
    super.key,
    required this.title,
    required this.subtitle,
    required this.child,
    this.showBack = false,
  });

  final String title;
  final String subtitle;
  final Widget child;
  final bool showBack;

  @override
  Widget build(BuildContext context) {
    final bottomInset = MediaQuery.of(context).padding.bottom;

    return Scaffold(
      backgroundColor: AppColors.brandBlack,
      body: SafeArea(
        bottom: false,
        child: LayoutBuilder(
          builder: (context, constraints) => SingleChildScrollView(
            child: ConstrainedBox(
              constraints: BoxConstraints(minHeight: constraints.maxHeight),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Padding(
                    padding: const EdgeInsets.fromLTRB(AppSpacing.xl,
                        AppSpacing.md, AppSpacing.xl, AppSpacing.xxl),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        if (showBack)
                          IconButton(
                            onPressed: () => Navigator.of(context).maybePop(),
                            icon: const Icon(Icons.arrow_forward,
                                color: AppColors.brandWhite),
                            tooltip: MaterialLocalizations.of(context)
                                .backButtonTooltip,
                          )
                        else
                          const SizedBox(height: AppSpacing.xxl),
                        const SizedBox(height: AppSpacing.lg),
                        Container(
                          width: 56,
                          height: 56,
                          decoration: const BoxDecoration(
                              color: AppColors.brandYellow,
                              shape: BoxShape.circle),
                          alignment: Alignment.center,
                          child: const Text('Q',
                              style: TextStyle(
                                  color: AppColors.brandBlack,
                                  fontSize: 26,
                                  fontWeight: FontWeight.w800)),
                        ),
                        const SizedBox(height: AppSpacing.xl),
                        Text(title,
                            style: const TextStyle(
                                color: AppColors.brandWhite,
                                fontSize: 28,
                                fontWeight: FontWeight.w800,
                                height: 1.25)),
                        const SizedBox(height: AppSpacing.sm),
                        Text(subtitle,
                            style: const TextStyle(
                                color: Colors.white70, fontSize: 15)),
                      ],
                    ),
                  ),
                  Container(
                    padding: EdgeInsets.fromLTRB(AppSpacing.xl, AppSpacing.xxl,
                        AppSpacing.xl, AppSpacing.xxl + bottomInset),
                    decoration: const BoxDecoration(
                      color: AppColors.brandWhite,
                      borderRadius:
                          BorderRadius.vertical(top: Radius.circular(28)),
                    ),
                    child: child,
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
