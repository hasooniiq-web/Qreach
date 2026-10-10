import 'package:flutter/material.dart';

import '../../core/design/tokens.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_spacing.dart';
import '../../l10n/app_localizations.dart';

class ChoiceOption {
  const ChoiceOption({required this.value, required this.label, this.leading});

  /// What is stored when this option is picked.
  final String value;

  /// What the user reads.
  final String label;
  final Widget? leading;
}

/// A form field that opens a tidy bottom sheet of choices instead of the
/// platform dropdown (which overflowed and mis-aligned on RTL). Optionally
/// searchable, and optionally ends with "Other…" so a value that is not in the
/// list can still be typed — the typed text becomes the value.
class ChoiceField extends StatelessWidget {
  const ChoiceField({
    super.key,
    required this.label,
    required this.value,
    required this.options,
    required this.onChanged,
    this.allowOther = true,
    this.searchable = false,
    this.hint,
    this.enabled = true,
    this.otherTitle,
  });

  final String label;
  final String? value;
  final List<ChoiceOption> options;
  final ValueChanged<String?> onChanged;
  final bool allowOther;
  final bool searchable;
  final String? hint;
  final bool enabled;

  /// Title of the "type it yourself" dialog; defaults to the field label.
  final String? otherTitle;

  ChoiceOption? get _selected {
    for (final o in options) {
      if (o.value == value) return o;
    }
    return null;
  }

  Future<void> _open(BuildContext context) async {
    FocusScope.of(context).unfocus();
    final result = await showModalBottomSheet<_Picked>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (_) => _Sheet(
        title: label,
        options: options,
        selected: value,
        allowOther: allowOther,
        searchable: searchable || options.length > 12,
      ),
    );
    if (result == null || !context.mounted) return;

    if (result.other) {
      final typed = await _askOther(context);
      if (typed != null && typed.trim().isNotEmpty) onChanged(typed.trim());
    } else {
      onChanged(result.value);
    }
  }

  Future<String?> _askOther(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final controller = TextEditingController(
        text: _selected == null && (value ?? '').isNotEmpty ? value : '');
    return showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        insetPadding: const EdgeInsets.symmetric(horizontal: 28, vertical: 24),
        contentPadding: const EdgeInsets.fromLTRB(24, 16, 24, 8),
        title: Text(otherTitle ?? label),
        content: TextField(
          controller: controller,
          autofocus: true,
          textInputAction: TextInputAction.done,
          onSubmitted: (v) => Navigator.of(context).pop(v),
          decoration: InputDecoration(hintText: l10n.pickerOtherHint),
        ),
        actions: [
          TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: Text(l10n.cancel)),
          TextButton(
              onPressed: () => Navigator.of(context).pop(controller.text),
              child: Text(l10n.save)),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final shown = _selected?.label ?? ((value ?? '').isEmpty ? null : value);

    return Semantics(
      button: true,
      label: label,
      child: InkWell(
        borderRadius: AppRadius.rMd,
        onTap: enabled ? () => _open(context) : null,
        child: InputDecorator(
          isEmpty: shown == null,
          decoration: InputDecoration(
            labelText: label,
            hintText: hint ?? l10n.pickerChoose,
            enabled: enabled,
            suffixIcon: Icon(Icons.keyboard_arrow_down_rounded,
                color: AppColors.ink500),
          ),
          child: Row(
            children: [
              if (_selected?.leading != null) ...[
                _selected!.leading!,
                const SizedBox(width: AppSpacing.sm),
              ],
              Expanded(
                child: Text(shown ?? '',
                    maxLines: 1, overflow: TextOverflow.ellipsis),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _Picked {
  const _Picked(this.value, {this.other = false});
  final String? value;
  final bool other;
}

class _Sheet extends StatefulWidget {
  const _Sheet({
    required this.title,
    required this.options,
    required this.selected,
    required this.allowOther,
    required this.searchable,
  });

  final String title;
  final List<ChoiceOption> options;
  final String? selected;
  final bool allowOther;
  final bool searchable;

  @override
  State<_Sheet> createState() => _SheetState();
}

class _SheetState extends State<_Sheet> {
  String _query = '';

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final q = _query.trim().toLowerCase();
    final shown = q.isEmpty
        ? widget.options
        : widget.options
            .where((o) => o.label.toLowerCase().contains(q))
            .toList();

    return DraggableScrollableSheet(
      expand: false,
      initialChildSize: 0.7,
      minChildSize: 0.4,
      maxChildSize: 0.92,
      builder: (context, scroll) => Column(
        children: [
          const SizedBox(height: AppSpacing.sm),
          Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                  color: AppColors.ink200,
                  borderRadius: BorderRadius.circular(2))),
          Padding(
            padding: const EdgeInsets.fromLTRB(
                AppSpacing.xl, AppSpacing.lg, AppSpacing.xl, AppSpacing.sm),
            child: Align(
              alignment: AlignmentDirectional.centerStart,
              child: Text(widget.title,
                  style: const TextStyle(
                      fontSize: 18, fontWeight: FontWeight.w800)),
            ),
          ),
          if (widget.searchable)
            Padding(
              padding: const EdgeInsets.symmetric(
                  horizontal: AppSpacing.lg, vertical: AppSpacing.xs),
              child: TextField(
                onChanged: (v) => setState(() => _query = v),
                decoration: InputDecoration(
                  hintText: l10n.pickerSearch,
                  prefixIcon: const Icon(Icons.search_rounded),
                  isDense: true,
                ),
              ),
            ),
          Expanded(
            child: ListView(
              controller: scroll,
              padding: const EdgeInsets.fromLTRB(
                  AppSpacing.sm, AppSpacing.xs, AppSpacing.sm, AppSpacing.xl),
              children: [
                if (shown.isEmpty && !widget.allowOther)
                  Padding(
                    padding: const EdgeInsets.all(AppSpacing.xl),
                    child: Text(l10n.pickerNoResults,
                        textAlign: TextAlign.center,
                        style: TextStyle(color: AppColors.ink500)),
                  ),
                for (final o in shown)
                  ListTile(
                    leading: o.leading,
                    title: Text(o.label,
                        style: TextStyle(
                            fontWeight: o.value == widget.selected
                                ? FontWeight.w800
                                : FontWeight.w500)),
                    trailing: o.value == widget.selected
                        ? Icon(Icons.check_circle_rounded,
                            color: AppColors.brandBlack)
                        : null,
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12)),
                    onTap: () => Navigator.of(context).pop(_Picked(o.value)),
                  ),
                if (widget.allowOther)
                  ListTile(
                    leading: const Icon(Icons.edit_outlined),
                    title: Text(l10n.pickerOther,
                        style: const TextStyle(fontWeight: FontWeight.w700)),
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12)),
                    onTap: () => Navigator.of(context)
                        .pop(const _Picked(null, other: true)),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
