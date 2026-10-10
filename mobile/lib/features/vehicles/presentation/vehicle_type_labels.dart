import 'package:flutter/widgets.dart';

import '../../../l10n/app_localizations.dart';

/// Maps the backend's English enum values to localized labels — never show
/// a raw "sedan" to the user (the same bug was caught and fixed in the
/// admin dashboard's public scan page; the mobile app must not repeat it).
String vehicleTypeLabel(BuildContext context, String? type) {
  final l10n = AppLocalizations.of(context)!;
  return switch (type) {
    'sedan' => l10n.sedan,
    'suv' => l10n.suv,
    'pickup' => l10n.pickup,
    'truck' => l10n.truck,
    'bus' => l10n.bus,
    'motorcycle' => l10n.motorcycle,
    'van' => l10n.van,
    _ => l10n.other,
  };
}

String tagStatusLabel(BuildContext context, String status) {
  final l10n = AppLocalizations.of(context)!;
  return switch (status) {
    'assigned' => l10n.statusAssigned,
    'revoked' => l10n.statusRevoked,
    _ => l10n.statusActive,
  };
}

/// `code` is the unified QR + NFC code; `qr` / `nfc` are older single-purpose tags.
String tagTypeLabel(AppLocalizations l10n, String type) => switch (type) {
      'code' => l10n.tagTypeCode,
      'nfc' => l10n.tagTypeNfc,
      _ => l10n.tagTypeQr,
    };
