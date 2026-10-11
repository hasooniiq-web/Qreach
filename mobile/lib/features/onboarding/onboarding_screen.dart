import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/design/tokens.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_spacing.dart';
import '../../l10n/app_localizations.dart';
import '../../shared/widgets/app_button.dart';
import '../../shared/widgets/brand_mark.dart';
import 'onboarding_controller.dart';

/// Three short pages shown the first time the app is opened: how the code is
/// linked, how visitors reach the owner, and what the owner controls. Every page
/// can be skipped, and finishing (or skipping) is remembered.
class OnboardingScreen extends ConsumerStatefulWidget {
  const OnboardingScreen({super.key});

  @override
  ConsumerState<OnboardingScreen> createState() => _OnboardingScreenState();
}

class _OnboardingScreenState extends ConsumerState<OnboardingScreen> {
  final _controller = PageController();
  int _page = 0;

  static const _pageCount = 3;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _finish() =>
      ref.read(onboardingSeenProvider.notifier).complete();

  void _next() {
    if (_page >= _pageCount - 1) {
      _finish();
      return;
    }
    _controller.nextPage(
        duration: const Duration(milliseconds: 320), curve: Curves.easeOutCubic);
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final pages = <_PageData>[
      _PageData(Icons.qr_code_scanner_rounded, l10n.onboard1Title,
          l10n.onboard1Body),
      _PageData(Icons.mark_chat_unread_outlined, l10n.onboard2Title,
          l10n.onboard2Body),
      _PageData(Icons.tune_rounded, l10n.onboard3Title, l10n.onboard3Body),
    ];
    final last = _page == _pageCount - 1;

    return Scaffold(
      backgroundColor: AppColors.brandBlack,
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(
                  AppSpacing.lg, AppSpacing.sm, AppSpacing.lg, 0),
              child: Row(
                children: [
                  const BrandMark(size: 34),
                  const Spacer(),
                  // Hidden on the last page, where the main button finishes.
                  AnimatedOpacity(
                    duration: const Duration(milliseconds: 180),
                    opacity: last ? 0 : 1,
                    child: TextButton(
                      onPressed: last ? null : _finish,
                      child: Text(l10n.onboardSkip,
                          style: const TextStyle(
                              color: Colors.white70,
                              fontWeight: FontWeight.w700)),
                    ),
                  ),
                ],
              ),
            ),
            Expanded(
              child: PageView.builder(
                controller: _controller,
                itemCount: _pageCount,
                onPageChanged: (i) => setState(() => _page = i),
                itemBuilder: (context, i) => _OnboardingPage(data: pages[i]),
              ),
            ),
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                for (var i = 0; i < _pageCount; i++)
                  AnimatedContainer(
                    duration: const Duration(milliseconds: 220),
                    margin: const EdgeInsets.symmetric(horizontal: 3),
                    width: i == _page ? 22 : 7,
                    height: 7,
                    decoration: BoxDecoration(
                      color: i == _page
                          ? AppColors.brandYellow
                          : Colors.white24,
                      borderRadius: BorderRadius.circular(4),
                    ),
                  ),
              ],
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(
                  AppSpacing.xl, AppSpacing.xl, AppSpacing.xl, AppSpacing.xl),
              child: AppButton(
                label: last ? l10n.onboardStart : l10n.onboardNext,
                style: AppButtonStyle.accent,
                onPressed: _next,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _PageData {
  const _PageData(this.icon, this.title, this.body);

  final IconData icon;
  final String title;
  final String body;
}

class _OnboardingPage extends StatelessWidget {
  const _OnboardingPage({required this.data});

  final _PageData data;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: AppSpacing.xl),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            width: 148,
            height: 148,
            decoration: BoxDecoration(
              color: AppColors.brandYellow,
              borderRadius: BorderRadius.circular(AppRadius.lg + 12),
            ),
            child: Icon(data.icon, size: 72, color: AppColors.brandBlack),
          ),
          const SizedBox(height: AppSpacing.xxl),
          Text(
            data.title,
            textAlign: TextAlign.center,
            style: const TextStyle(
                color: AppColors.brandWhite,
                fontSize: 26,
                fontWeight: FontWeight.w800,
                height: 1.25),
          ),
          const SizedBox(height: AppSpacing.md),
          Text(
            data.body,
            textAlign: TextAlign.center,
            style: const TextStyle(
                color: Colors.white70, fontSize: 16, height: 1.6),
          ),
        ],
      ),
    );
  }
}
