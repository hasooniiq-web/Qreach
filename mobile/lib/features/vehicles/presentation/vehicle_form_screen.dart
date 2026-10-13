import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/choice_field.dart';
import '../data/car_catalog.dart';
import '../data/vehicle_options.dart';
import 'package:go_router/go_router.dart';
import '../../../shared/widgets/iraqi_plate.dart';
import '../../../shared/widgets/plate_labels.dart';

import '../../../core/network/api_client.dart';
import '../../../core/providers.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/error_banner.dart';
import '../data/vehicle_model.dart';
import 'vehicle_controller.dart';

class VehicleFormScreen extends ConsumerStatefulWidget {
  const VehicleFormScreen({super.key, this.vehicle});

  /// Set when editing an existing vehicle; null adds a new one.
  final VehicleModel? vehicle;

  @override
  ConsumerState<VehicleFormScreen> createState() => _VehicleFormScreenState();
}

class _VehicleFormScreenState extends ConsumerState<VehicleFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _plateController = TextEditingController();
  final _nicknameController = TextEditingController();
  String? _make;
  // The model is typed (e.g. "G class", "Camry SE"): there are too many to list.
  final _modelController = TextEditingController();
  String? _color;
  int? _year;
  final _vinController = TextEditingController();
  final _letterController = TextEditingController();
  String _vehicleType = 'sedan';
  String _plateType = 'private';
  String? _governorate;
  bool _submitting = false;
  String? _error;

  bool get _editing => widget.vehicle != null;

  @override
  void initState() {
    super.initState();
    final v = widget.vehicle;
    if (v == null) return;
    _plateController.text = v.plateNumber;
    _nicknameController.text = v.nickname ?? '';
    _letterController.text = v.plateLetter ?? '';
    _vinController.text = v.vin ?? '';
    _make = v.make;
    _modelController.text = v.model ?? '';
    _color = v.color;
    _year = v.year;
    _vehicleType = v.vehicleType ?? 'sedan';
    _plateType = v.plateType;
    _governorate = v.plateGovernorate;
  }

  @override
  void dispose() {
    _plateController.dispose();
    _letterController.dispose();
    _nicknameController.dispose();
    _vinController.dispose();
    _modelController.dispose();
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
        id: widget.vehicle?.id ?? '',
        plateNumber: _plateController.text.trim(),
        plateType: _plateType,
        plateLetter: _letterController.text.trim(),
        plateGovernorate: _governorate,
        nickname: _nicknameController.text.trim(),
        make: (_make ?? '').trim().isEmpty ? null : _make!.trim(),
        model: _modelController.text.trim().isEmpty
            ? null
            : _modelController.text.trim(),
        year: _year,
        vin: _vinController.text.trim().toUpperCase(),
        color: (_color ?? '').trim().isEmpty ? null : _color!.trim(),
        vehicleType: _vehicleType,
      );
      if (_editing) {
        await ref
            .read(vehicleRepositoryProvider)
            .update(widget.vehicle!.id, vehicle);
        ref.invalidate(vehicleDetailProvider(widget.vehicle!.id));
        await ref.read(vehicleListControllerProvider.notifier).refresh();
      } else {
        await ref.read(vehicleListControllerProvider.notifier).create(vehicle);
      }
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
    // Arabic-script data (makes, stored colour names) is used for Kurdish too; only
    // the colour labels have a Kurdish spelling.
    final code = Localizations.localeOf(context).languageCode;
    final isArabic = code != 'en';
    final isKurdish = code == 'ckb';
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
      appBar: AppBar(
          title: Text(_editing ? l10n.editVehicle : l10n.addVehicle)),
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
                      // One letter, as on an Iraqi plate.
                      maxLength: 1,
                      textInputAction: TextInputAction.next,
                      inputFormatters: [
                        FilteringTextInputFormatter.allow(
                            RegExp(r'[\p{L}]', unicode: true)),
                        LengthLimitingTextInputFormatter(1),
                      ],
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
                // Up to six digits.
                keyboardType: TextInputType.number,
                maxLength: 6,
                inputFormatters: [
                  FilteringTextInputFormatter.digitsOnly,
                  LengthLimitingTextInputFormatter(6),
                ],
                textInputAction: TextInputAction.next,
                style: const TextStyle(
                    fontWeight: FontWeight.w700, letterSpacing: 1.5),
                decoration: InputDecoration(
                  labelText: l10n.plateNumber,
                  counterText: '',
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
                onChanged: (v) => setState(() => _make = v),
              ),
              const SizedBox(height: AppSpacing.md),
              TextFormField(
                controller: _modelController,
                maxLength: 100,
                textInputAction: TextInputAction.next,
                textDirection: TextDirection.ltr,
                decoration: InputDecoration(
                  labelText: l10n.model,
                  hintText: l10n.modelHint,
                  counterText: '',
                ),
              ),
              const SizedBox(height: AppSpacing.md),
              // Model year: 1900 up to next year (a car can arrive before its
              // model year starts).
              ChoiceField(
                label: l10n.year,
                value: _year?.toString(),
                allowOther: false,
                searchable: true,
                options: [
                  for (var y = DateTime.now().year + 1; y >= 1900; y--)
                    ChoiceOption(value: '$y', label: '$y'),
                ],
                onChanged: (v) => setState(() => _year = int.tryParse(v ?? '')),
              ),
              const SizedBox(height: AppSpacing.md),
              ChoiceField(
                label: l10n.color,
                value: _color,
                options: vehicleColorOptions(arabic: isArabic, kurdish: isKurdish),
                onChanged: (v) => setState(() => _color = v),
              ),
              const SizedBox(height: AppSpacing.md),
              TextFormField(
                controller: _vinController,
                textDirection: TextDirection.ltr,
                textCapitalization: TextCapitalization.characters,
                maxLength: 32,
                decoration: InputDecoration(
                    labelText: l10n.vinLabel, counterText: ''),
                validator: (v) {
                  final s = (v ?? '').trim();
                  if (s.isEmpty) return null;
                  return RegExp(r'^[A-HJ-NPR-Za-hj-npr-z0-9]+$').hasMatch(s)
                      ? null
                      : l10n.vinInvalid;
                },
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
