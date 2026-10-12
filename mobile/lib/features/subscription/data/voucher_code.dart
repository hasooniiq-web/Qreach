/// Activation vouchers carry 16 digits (15 random + a Luhn check digit), printed
/// as `0000 0000 0000 0000` and also encoded as a QR holding the same digits.
abstract final class VoucherCode {
  static const length = 16;

  static const _arabicIndic = '٠١٢٣٤٥٦٧٨٩';
  static const _easternArabicIndic = '۰۱۲۳۴۵۶۷۸۹';

  /// Digits only (Arabic-Indic digits, spaces, dashes and a URL/prefix around
  /// the number are tolerated). Returns the 16 digits, or null.
  static String? parse(String raw) {
    final buffer = StringBuffer();
    for (final rune in raw.runes) {
      final ch = String.fromCharCode(rune);
      final a = _arabicIndic.indexOf(ch);
      final e = _easternArabicIndic.indexOf(ch);
      if (a >= 0) {
        buffer.write(a);
      } else if (e >= 0) {
        buffer.write(e);
      } else if (rune >= 0x30 && rune <= 0x39) {
        buffer.write(ch);
      }
    }
    final digits = buffer.toString();
    return digits.length == length ? digits : null;
  }

  /// Luhn over all 16 digits: catches a mistyped digit before any request.
  static bool isValid(String digits) {
    if (digits.length != length) return false;
    var sum = 0;
    var double = false;
    for (var i = digits.length - 1; i >= 0; i--) {
      var d = digits.codeUnitAt(i) - 0x30;
      if (d < 0 || d > 9) return false;
      if (double) {
        d *= 2;
        if (d > 9) d -= 9;
      }
      sum += d;
      double = !double;
    }
    return sum % 10 == 0;
  }

  /// `1234567890123456` -> `1234 5678 9012 3456`
  static String group(String digits) {
    final out = StringBuffer();
    for (var i = 0; i < digits.length; i++) {
      if (i > 0 && i % 4 == 0) out.write(' ');
      out.write(digits[i]);
    }
    return out.toString();
  }
}
