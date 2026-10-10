import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/choice_field.dart';
import '../data/car_catalog.dart';
import '../data/vehicle_options.dart';
import 'package:go_router/go_router.dart';
import '../../../shared/widgets/iraqi_plate.dart';
import '../../../shared/widgets/plate_labels.dart';

import '../../../core/network/api_client.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/error_banner.dart';
import '../data/vehicle_model.dart';
import 'vehicle_controller.dart';

class VehicleFormScreen extends ConsumerStatefulWidget {
  const VehicleFormScreen({super.key});

  @override
  ConsumerState<VehicleFormScreen> createState() => _VehicleFormScreenState();
}

class _VehicleFormScreenState extends ConsumerState<VehicleFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _plateController = TextEditingController();
  final _nicknameController = TextEditingController();
  String? _make;
  String? _model;
  String? _color;
  final _letterController = TextEditingController();
  String _vehicleType = 'sedan';
  String _plateType = 'private';
  String? _governorate;
  bool _submitting = false;
  String? _error;

  @override
  void dispose() {
    _plateController.dispose();
    _letterController.dispose();
    _nicknameController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      final vehicle = VehicleModel(
        id: '',
        plateNumber: _plateController.text.trim(),
        plateType: _plateType,
        plateLetter: _letterController.text.trim(),
        plateGovernorate: _governorate,
        nickname: _nicknameController.text.trim(),
        make: (_make ?? '').trim().isEmpty ? null : _make!.trim(),
        model: (_model ?? '').trim().isEmpty ? null : _model!.trim(),
        color: (_color ?? '').trim().isEmpty ? null : _color!.trim(),
        vehicleType: _vehicleType,
      );
      await ref.read(vehicleListControllerProvider.notifier).create(vehicle);
      if (mounted) context.pop();
    } catch (e) {
      setState(() => _error = asApiException(e).message);
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final isArabic = Localizations.localeOf(context).languageCode == 'ar';
    final catalog =
        ref.watch(carCatalogProvider).valueOrNull ?? CarCatalog.empty;
    final types = {
      'sedan': l10n.sedan,
      'suv': l10n.suv,
      'pickup': l10n.pickup,
      'truck': l10n.truck,
      'bus': l10n.bus,
      'motorcycle': l10n.motorcycle,
      'van': l10n.van,
      'other': l10n.other,
    };
    final sectionStyle = Theme.of(context)
        .textTheme
        .titleMedium
        ?.copyWith(fontWeight: FontWeight.w700);

    return Scaffold(
      appBar: AppBar(title: Text(l10n.addVehicle)),
      body: SafeArea(
        child: Form(
          key: _formKey,
          child: ListView(
            padding: const EdgeInsets.all(AppSpacing.lg),
            children: [
              if (_error != null) ErrorBanner(message: _error!),
              Text(l10n.vehicleFormIdentity, style: sectionStyle),
              const SizedBox(height: AppSpacing.md),
              // Live preview: the plate looks like the real one as it is typed.
              Center(
                child: ListenableBuilder(
                  listenable:
                      Listenable.merge([_plateController, _letterController]),
                  builder: (context, _) => IraqiPlate(
                    number: _plateController.text.trim().isEmpty
                        ? '00000'
                        : _plateController.text.trim(),
                    letter: _letterController.text.trim(),
                    governorate: _governorate,
                    type: PlateType.fromCode(_plateType),
                    width: 260,
                  ),
                ),
              ),
              const SizedBox(height: AppSpacing.lg),
              Text(l10n.plateTypeTitle,
                  style: const TextStyle(fontWeight: FontWeight.w600)),
              const SizedBox(height: AppSpacing.sm),
              Wrap(
                spacing: AppSpacing.sm,
                runSpacing: AppSpacing.sm,
                children: [
                  for (final t in PlateType.values)
                    ChoiceChip(
                      label: Text(plateTypeLabel(l10n, t)),
                      selected: _plateType == t.code,
                      onSelected: (_) => setState(() => _plateType = t.code),
                    ),
                ],
              ),
              const SizedBox(height: AppSpacing.lg),
              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _letterController,
                      maxLength: 4,
                      textInputAction: TextInputAction.next,
                      decoration: InputDecoration(
                          labelText: l10n.plateLetter, counterText: ''),
                    ),
                  ),
                  const SizedBox(width: AppSpacing.md),
                  Expanded(
                    flex: 2,
                    child: ChoiceField(
                      label: l10n.plateGovernorate,
                      value: _governorate,
                      allowOther: false,
                      options: [
                        for (final g in iraqGovernorates)
                          ChoiceOption(value: g, label: g),
                      ],
                      onChanged: (v) => setState(() => _governorate = v),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: AppSpacing.md),
              TextFormField(
                controller: _plateController,
                textDirection: TextDirection.ltr,
                textInputAction: TextInputAction.next,
                style: const TextStyle(
                    fontWeight: FontWeight.w700, letterSpacing: 1.5),
                decoration: InputDecoration(
                  labelText: l10n.plateNumber,
                  prefixIcon: const Icon(Icons.pin_outlined),
                ),
                validator: (v) =>
                    (v == null || v.trim().isEmpty) ? l10n.requiredField : null,
              ),
              const SizedBox(height: AppSpacing.md),
              TextFormField(
                controller: _nicknameController,
                textInputAction: TextInputAction.next,
                decoration: InputDecoration(
                  labelText: l10n.nickname,
                  helperText: l10n.vehicleFormNicknameHint,
                ),
              ),
              const SizedBox(height: AppSpacing.xl),
              Text(l10n.vehicleType, style: sectionStyle),
              const SizedBox(height: AppSpacing.md),
              // Choice chips instead of a dropdown: all six options visible,
              // one tap, and 48dp targets.
              Wrap(
                spacing: AppSpacing.sm,
                runSpacing: AppSpacing.sm,
                children: [
                  for (final entry in types.entries)
                    ChoiceChip(
                      label: Text(entry.value),
                      selected: _vehicleType == entry.key,
                      showCheckmark: false,
                      selectedColor: AppColors.brandBlack,
                      labelStyle: TextStyle(
                          color: _vehicleType == entry.key
                              ? AppColors.brandWhite
                              : AppColors.ink800,
                          fontWeight: FontWeight.w600),
                      materialTapTargetSize: MaterialTapTargetSize.padded,
                      onSelected: (_) =>
                          setState(() => _vehicleType = entry.key),
                    ),
                ],
              ),
              const SizedBox(height: AppSpacing.xl),
              Text(l10n.vehicleFormMore, style: sectionStyle),
              const SizedBox(height: AppSpacing.md),
              ChoiceField(
                label: l10n.make,
                value: _make,
                searchable: true,
                options: [
                  for (final m in catalog.makes)
                    ChoiceOption(
                        value: m.label(isArabic), label: m.label(isArabic)),
                ],
                onChanged: (v) => setState(() {
                  // A different make has different models.
                  if (v != _make) _model = null;
                  _make = v;
                }),
              ),
              const SizedBox(height: AppSpacing.md),
              ChoiceField(
                label: l10n.model,
                hint: l10n.modelHint,
                value: _model,
                searchable: true,
                options: [
                  for (final name
                      in (catalog.find(_make)?.models ?? const <String>[]))
                    ChoiceOption(value: name, label: name),
                ],
                onChanged: (v) => setState(() => _model = v),
              ),
              const SizedBox(height: AppSpacing.md),
              ChoiceField(
                label: l10n.color,
                value: _color,
                options: vehicleColorOptions(arabic: isArabic),
                onChanged: (v) => setState(() => _color = v),
              ),
              const SizedBox(height: AppSpacing.xxl),
              AppButton(
                label: l10n.save,
                loading: _submitting,
                onPressed: _submitting ? null : _submit,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
