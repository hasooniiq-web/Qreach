/// Iraqi mobile numbers are written two ways by people: with the leading
/// trunk zero used for local dialing ("07701234567", 11 digits) or without
/// it ("7701234567", 10 digits) — both mean the same subscriber and must
/// both be accepted everywhere a phone number is entered.
library;

/// Strips a single leading trunk zero from a local Iraqi number, if present.
/// `07701234567` -> `7701234567`; `7701234567` is returned unchanged.
/// Anything else (already has a country code, wrong length, etc.) is
/// returned unchanged too — this only ever removes that one optional zero.
String stripIraqiTrunkZero(String raw) {
  final value = raw.trim();
  if (value.length == 11 && value.startsWith('07')) {
    return value.substring(1);
  }
  return value;
}

/// True for a local Iraqi mobile number in either form: `7XXXXXXXXX` (10
/// digits) or `07XXXXXXXXX` (11 digits, the leading zero people dial with).
bool isValidIraqiLocalNumber(String raw) {
  return RegExp(r'^7\d{9}$').hasMatch(stripIraqiTrunkZero(raw));
}

/// Builds the full `+964XXXXXXXXXX` number from the local number the user
/// typed on the login screen — no "+964" is ever shown on screen, since the
/// app is Iraq-only; the country code is only added here, internally, before
/// the request is sent.
String toIraqiE164(String localNumber) =>
    '+964${stripIraqiTrunkZero(localNumber)}';

/// For a free-form phone field (no fixed prefix shown): accepts a local
/// Iraqi number in either form and turns it into `+964...`, accepts a number
/// already written with a country code (`+964...`, `964...`, or any other
/// country's `+...`) unchanged, and returns null for anything else so the
/// caller can show its own validation message.
String? normalizePhoneInput(String raw) {
  final value = raw.trim();
  if (value.isEmpty) return null;

  if (isValidIraqiLocalNumber(value)) {
    return toIraqiE164(value);
  }

  final withPlus = value.startsWith('+') ? value : '+$value';
  return RegExp(r'^\+[1-9]\d{7,14}$').hasMatch(withPlus) ? withPlus : null;
}
