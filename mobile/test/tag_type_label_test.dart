import 'package:flutter_test/flutter_test.dart';
import 'package:qreach_smarttag/features/vehicles/presentation/vehicle_type_labels.dart';
import 'package:qreach_smarttag/l10n/app_localizations_ar.dart';
import 'package:qreach_smarttag/l10n/app_localizations_en.dart';

void main() {
  test('a unified code is labelled QR + NFC; older kinds keep their labels',
      () {
    final ar = AppLocalizationsAr();
    expect(tagTypeLabel(ar, 'code'), 'رمز QR + NFC');
    expect(tagTypeLabel(ar, 'qr'), 'رمز QR');
    expect(tagTypeLabel(ar, 'nfc'), 'رمز NFC');
    expect(tagTypeLabel(AppLocalizationsEn(), 'code'), 'QR + NFC tag');
  });
}
