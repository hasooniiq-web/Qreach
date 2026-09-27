import 'package:flutter_test/flutter_test.dart';
import 'package:qreach_smarttag/core/utils/phone.dart';

void main() {
  group('isValidIraqiLocalNumber', () {
    test('accepts both with and without the leading trunk zero', () {
      expect(isValidIraqiLocalNumber('7701234567'), isTrue);
      expect(isValidIraqiLocalNumber('07701234567'), isTrue);
    });

    test('rejects the wrong length either way', () {
      expect(isValidIraqiLocalNumber('770123456'), isFalse); // 9 digits
      expect(isValidIraqiLocalNumber('077012345678'), isFalse); // 12 digits
      expect(isValidIraqiLocalNumber('0123456789'), isFalse); // not 07...
      expect(isValidIraqiLocalNumber(''), isFalse);
    });
  });

  group('toIraqiE164', () {
    test('strips the trunk zero before the fixed +964 prefix', () {
      expect(toIraqiE164('7701234567'), '+9647701234567');
      expect(toIraqiE164('07701234567'), '+9647701234567');
    });
  });

  group('normalizePhoneInput', () {
    test('turns a local number, with or without the zero, into +964...', () {
      expect(normalizePhoneInput('7701234567'), '+9647701234567');
      expect(normalizePhoneInput('07701234567'), '+9647701234567');
    });

    test('leaves a number already written with a country code alone', () {
      expect(normalizePhoneInput('+9647701234567'), '+9647701234567');
      expect(normalizePhoneInput('9647701234567'), '+9647701234567');
    });

    test('rejects nonsense', () {
      expect(normalizePhoneInput(''), isNull);
      expect(normalizePhoneInput('abc'), isNull);
      expect(normalizePhoneInput('123'), isNull);
    });
  });
}
