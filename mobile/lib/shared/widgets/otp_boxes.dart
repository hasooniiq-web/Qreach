import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../core/design/motion.dart';
import '../../core/design/tokens.dart';
import '../../core/theme/app_colors.dart';

/// A one-time-code entry drawn as separate boxes. A real (invisible)
/// [TextField] underneath owns the text, focus, paste and the number keyboard;
/// the boxes only display it. The active box is outlined in yellow and each
/// digit pops in as it is typed.
class OtpBoxes extends StatefulWidget {
  const OtpBoxes({
    super.key,
    required this.controller,
    this.length = 6,
    this.onChanged,
    this.autofocus = true,
  });

  final TextEditingController controller;
  final int length;
  final ValueChanged<String>? onChanged;
  final bool autofocus;

  @override
  State<OtpBoxes> createState() => _OtpBoxesState();
}

class _OtpBoxesState extends State<OtpBoxes> {
  final _focus = FocusNode();

  @override
  void initState() {
    super.initState();
    widget.controller.addListener(_refresh);
    _focus.addListener(_refresh);
  }

  void _refresh() => setState(() {});

  @override
  void dispose() {
    widget.controller.removeListener(_refresh);
    _focus.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final text = widget.controller.text;
    final active = text.length.clamp(0, widget.length - 1);

    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: () => _focus.requestFocus(),
      child: Stack(
        alignment: Alignment.center,
        children: [
          // Digits are always shown left-to-right, even on an RTL screen.
          Directionality(
            textDirection: TextDirection.ltr,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                for (var i = 0; i < widget.length; i++)
                  _Box(
                    char: i < text.length ? text[i] : null,
                    focused: _focus.hasFocus && i == active,
                    filled: i < text.length,
                  ),
              ],
            ),
          ),
          // The real input: transparent and sitting under the boxes.
          Positioned.fill(
            child: Opacity(
              opacity: 0,
              child: TextField(
                controller: widget.controller,
                focusNode: _focus,
                autofocus: widget.autofocus,
                keyboardType: TextInputType.number,
                textDirection: TextDirection.ltr,
                maxLength: widget.length,
                autofillHints: const [AutofillHints.oneTimeCode],
                inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                decoration: const InputDecoration(counterText: ''),
                onChanged: widget.onChanged,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _Box extends StatelessWidget {
  const _Box({required this.char, required this.focused, required this.filled});

  final String? char;
  final bool focused;
  final bool filled;

  @override
  Widget build(BuildContext context) {
    return AnimatedContainer(
      duration: AppMotion.fast,
      curve: AppMotion.enter,
      width: 48,
      height: 58,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        color: filled ? AppColors.ink50 : AppColors.surface,
        borderRadius: AppRadius.rSm,
        border: Border.all(
          color: focused
              ? AppColors.brandYellow
              : (filled ? AppColors.ink300 : AppColors.ink200),
          width: focused ? 2.2 : 1.4,
        ),
      ),
      child: AnimatedSwitcher(
        duration: AppMotion.fast,
        transitionBuilder: (child, animation) => ScaleTransition(
          scale: CurvedAnimation(parent: animation, curve: AppMotion.settle),
          child: FadeTransition(opacity: animation, child: child),
        ),
        child: char == null
            ? const SizedBox.shrink(key: ValueKey('empty'))
            : Text(
                char!,
                key: ValueKey(char),
                style: TextStyle(
                    fontSize: 26,
                    fontWeight: FontWeight.w800,
                    color: AppColors.ink900),
              ),
      ),
    );
  }
}
