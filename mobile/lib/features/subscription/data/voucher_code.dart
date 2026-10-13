/// Activation vouchers carry 12 characters (11 random + a check character) from an
/// alphabet with nothing that can be misread — no 0 / 1 and no I, L or O — printed as
/// `K7M2 P9QX 4TRW` and also encoded as a QR holding the same serial.
///
/// Vouchers issued before that format (16 digits with a Luhn check digit) still work.
abstract final class VoucherCode {
  static const alphabet = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

  static const length = 12;
  static const legacyLength = 16;

  static const _arabicIndic = '٠١٢٣٤٥٦٧٨٩';
  static const _easternArabicIndic = '۰۱۲۳۴۵۶۷۸۹';

  /// Upper-cases and strips everything that is not a letter or digit: spaces, dashes,
  /// Arabic-Indic digits are converted, and a link around the serial is dropped
  /// (a scanned QR may hold `https://…/v/SERIAL`). Returns the 12-character serial,
  /// or a legacy 16-digit one, or null when it is neither.
  static String? parse(String raw) {
    var text = raw.trim();
    final slash = text.lastIndexOf('/');
    if (slash >= 0) text = text.substring(slash + 1);

    final buffer = StringBuffer();
    for (final rune in text.runes) {
      final ch = String.fromCharCode(rune);
      final a = _arabicIndic.indexOf(ch);
      final e = _easternArabicIndic.indexOf(ch);
      if (a >= 0) {
        buffer.write(a);
      } else if (e >= 0) {
        buffer.write(e);
      } else if ((rune >= 0x30 && rune <= 0x39) ||
          (rune >= 0x41 && rune <= 0x5A)) {
        buffer.write(ch);
      } else if (rune >= 0x61 && rune <= 0x7A) {
        buffer.write(ch.toUpperCase());
      }
    }

    final serial = buffer.toString();
    if (RegExp(r'^\d{16}$').hasMatch(serial)) return serial;
    if (serial.length == length &&
        serial.split('').every((c) => alphabet.contains(c))) {
      return serial;
    }
    return null;
  }

  /// The check character (or, for a legacy serial, the Luhn digit): catches a
  /// mistyped character before any request is made.
  static bool isValid(String serial) {
    if (RegExp(r'^\d{16}$').hasMatch(serial)) return _luhn10(serial);
    if (serial.length != length) return false;

    const n = 31;
    var factor = 1;
    var sum = 0;
    for (var i = serial.length - 1; i >= 0; i--) {
      final code = alphabet.indexOf(serial[i]);
      if (code < 0) return false;
      var addend = factor * code;
      factor = factor == 2 ? 1 : 2;
      addend = (addend ~/ n) + (addend % n);
      sum += addend;
    }
    return sum % n == 0;
  }

  static bool _luhn10(String digits) {
    var sum = 0;
    var double = false;
    for (var i = digits.length - 1; i >= 0; i--) {
      var d = digits.codeUnitAt(i) - 0x30;
      if (double) {
        d *= 2;
        if (d > 9) d -= 9;
      }
      sum += d;
      double = !double;
    }
    return sum % 10 == 0;
  }

  /// `K7M2P9QX4TRW` -> `K7M2 P9QX 4TRW`
  static String group(String serial) {
    final out = StringBuffer();
    for (var i = 0; i < serial.length; i++) {
      if (i > 0 && i % 4 == 0) out.write(' ');
      out.write(serial[i]);
    }
    return out.toString();
  }
}
