import 'package:flutter/material.dart';

import '../../../core/design/motion.dart';
import '../../../core/design/tokens.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../shared/widgets/brand_mark.dart';
import '../../../shared/widgets/iraqi_plate.dart';

/// Shared layout for the login flow: a black hero (brand mark, title,
/// subtitle and a tilted plate as a nod to what the app protects) over a form
/// panel that rises from the bottom. Scrolls, so it stays usable on short
/// screens and with the keyboard open.
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
    final reduce = MediaQuery.of(context).disableAnimations;

    Widget panel = Container(
      padding: EdgeInsets.fromLTRB(AppSpacing.xl, AppSpacing.xl + 4,
          AppSpacing.xl, AppSpacing.xxl + bottomInset),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius:
            const BorderRadius.vertical(top: Radius.circular(AppRadius.lg + 6)),
      ),
      child: child,
    );
    if (!reduce) {
      panel = TweenAnimationBuilder<double>(
        tween: Tween(begin: 1, end: 0),
        duration: AppMotion.slow,
        curve: AppMotion.enter,
        child: panel,
        builder: (context, t, child) => FractionalTranslation(
          translation: Offset(0, t * 0.35),
          child: Opacity(opacity: 1 - t * 0.6, child: child),
        ),
      );
    }

    return Scaffold(
      backgroundColor: AppColors.brandBlack,
      resizeToAvoidBottomInset: true,
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
                  Stack(
                    children: [
                      // Soft glow behind the hero.
                      PositionedDirectional(
                        top: -80,
                        end: -60,
                        child: Container(
                          width: 300,
                          height: 300,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            gradient: RadialGradient(colors: [
                              AppColors.brandYellow.withValues(alpha: 0.18),
                              AppColors.brandYellow.withValues(alpha: 0),
                            ]),
                          ),
                        ),
                      ),
                      Padding(
                        padding: const EdgeInsets.fromLTRB(AppSpacing.xl,
                            AppSpacing.md, AppSpacing.xl, AppSpacing.xxl),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            if (showBack)
                              IconButton(
                                onPressed: () =>
                                    Navigator.of(context).maybePop(),
                                icon: const Icon(Icons.arrow_back_rounded,
                                    color: AppColors.brandWhite),
                                tooltip: MaterialLocalizations.of(context)
                                    .backButtonTooltip,
                              )
                            else
                              const SizedBox(height: AppSpacing.xxl),
                            const SizedBox(height: AppSpacing.lg),
                            const BrandMark(size: 60),
                            const SizedBox(height: AppSpacing.xl + 8),
                            FadeSlideIn(
                              index: 1,
                              child: Text(title,
                                  style: const TextStyle(
                                      color: AppColors.brandWhite,
                                      fontSize: 30,
                                      fontWeight: FontWeight.w800,
                                      height: 1.25)),
                            ),
                            const SizedBox(height: AppSpacing.sm),
                            FadeSlideIn(
                              index: 2,
                              child: Text(subtitle,
                                  style: const TextStyle(
                                      color: Colors.white70,
                                      fontSize: 15,
                                      height: 1.5)),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const _HeroArt(),
                  panel,
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

/// A tilted plate with a QR sticker beside it: what Qreach is about, in one
/// glance. Drifts up and down a few times, then rests.
class _HeroArt extends StatefulWidget {
  const _HeroArt();

  @override
  State<_HeroArt> createState() => _HeroArtState();
}

class _HeroArtState extends State<_HeroArt>
    with SingleTickerProviderStateMixin {
  late final AnimationController _float = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 2600),
  )..repeat(reverse: true, count: 6);

  @override
  void dispose() {
    _float.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final reduce = MediaQuery.of(context).disableAnimations;
    if (reduce && _float.isAnimating) _float.stop();

    return FadeSlideIn(
      index: 3,
      offset: 28,
      child: SizedBox(
        height: 190,
        child: AnimatedBuilder(
          animation: _float,
          builder: (context, child) {
            final t = Curves.easeInOut.transform(reduce ? 0.5 : _float.value);
            return Transform.translate(
                offset: Offset(0, -8 + 16 * t), child: child);
          },
          child: Stack(
            alignment: Alignment.center,
            children: [
              Container(
                width: 230,
                height: 230,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  gradient: RadialGradient(colors: [
                    AppColors.brandYellow.withValues(alpha: 0.16),
                    AppColors.brandYellow.withValues(alpha: 0),
                  ]),
                ),
              ),
              Transform.rotate(
                angle: -0.09,
                child: const IraqiPlate(
                  number: '48291',
                  letter: 'ب',
                  governorate: 'بغداد',
                  width: 250,
                ),
              ),
              PositionedDirectional(
                bottom: 2,
                start: 56,
                child: Transform.rotate(
                  angle: 0.14,
                  child: Container(
                    width: 64,
                    height: 64,
                    decoration: BoxDecoration(
                      color: AppColors.brandYellow,
                      borderRadius: BorderRadius.circular(18),
                      boxShadow: const [
                        BoxShadow(
                            color: Color(0x66000000),
                            blurRadius: 16,
                            offset: Offset(0, 6)),
                      ],
                    ),
                    child: const Icon(Icons.qr_code_2_rounded,
                        size: 40, color: AppColors.brandBlack),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
