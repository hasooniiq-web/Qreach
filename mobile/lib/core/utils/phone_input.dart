import 'package:flutter/services.dart';

/// What a local Iraqi mobile field accepts while typing: digits only (Arabic-Indic digits
/// are converted), 10 of them without the leading zero (`7701234567`) or 11 with it
/// (`07701234567`) — nothing longer can be entered.
final List<TextInputFormatter> localPhoneFormatters = [
  _ArabicDigitsToLatin(),
  FilteringTextInputFormatter.digitsOnly,
  _LocalPhoneLength(),
];

class _ArabicDigitsToLatin extends TextInputFormatter {
  static const _arabic = '٠١٢٣٤٥٦٧٨٩';
  static const _persian = '۰۱۲۳۴۵۶۷۸۹';

  @override
  TextEditingValue formatEditUpdate(
      TextEditingValue oldValue, TextEditingValue newValue) {
    final out = StringBuffer();
    for (final rune in newValue.text.runes) {
      final ch = String.fromCharCode(rune);
      final a = _arabic.indexOf(ch);
      final p = _persian.indexOf(ch);
      out.write(a >= 0 ? a : (p >= 0 ? p : ch));
    }
    final text = out.toString();
    return text == newValue.text ? newValue : newValue.copyWith(text: text);
  }
}

class _LocalPhoneLength extends TextInputFormatter {
  @override
  TextEditingValue formatEditUpdate(
      TextEditingValue oldValue, TextEditingValue newValue) {
    final digits = newValue.text;
    final max = digits.startsWith('0') ? 11 : 10;
    return digits.length > max ? oldValue : newValue;
  }
}
