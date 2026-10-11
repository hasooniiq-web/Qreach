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
/// searchable, and optionally ends with a plain "Other" choice. "Other" is a
/// value like any other — nothing is typed — so every answer comes from the list.
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
  });

  final String label;
  final String? value;
  final List<ChoiceOption> options;
  final ValueChanged<String?> onChanged;
  final bool allowOther;
  final bool searchable;
  final String? hint;
  final bool enabled;

  ChoiceOption? get _selected {
    for (final o in options) {
      if (o.value == value) return o;
    }
    return null;
  }

  Future<void> _open(BuildContext context) async {
    FocusScope.of(context).unfocus();
    final l10n = AppLocalizations.of(context)!;
    final picked = await showModalBottomSheet<String>(
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
        otherLabel: allowOther ? l10n.pickerOther : null,
        searchable: searchable || options.length > 12,
      ),
    );
    if (picked == null || !context.mounted) return;
    onChanged(picked);
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

class _Sheet extends StatefulWidget {
  const _Sheet({
    required this.title,
    required this.options,
    required this.selected,
    required this.otherLabel,
    required this.searchable,
  });

  final String title;
  final List<ChoiceOption> options;
  final String? selected;

  /// The plain "Other" choice (also its stored value); null = not offered.
  final String? otherLabel;
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
                if (shown.isEmpty && widget.otherLabel == null)
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
                    onTap: () => Navigator.of(context).pop(o.value),
                  ),
                if (widget.otherLabel != null)
                  ListTile(
                    leading: const Icon(Icons.more_horiz_rounded),
                    title: Text(widget.otherLabel!,
                        style: TextStyle(
                            fontWeight: widget.selected == widget.otherLabel
                                ? FontWeight.w800
                                : FontWeight.w700)),
                    trailing: widget.selected == widget.otherLabel
                        ? Icon(Icons.check_circle_rounded,
                            color: AppColors.brandBlack)
                        : null,
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12)),
                    onTap: () => Navigator.of(context).pop(widget.otherLabel),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
