import 'dart:math' as math;

import 'package:flutter/material.dart';

/// Plate categories as issued in Iraq, each with its own colour.
enum PlateType {
  private('private', Color(0xFFFFFFFF), Color(0xFF111111), 'خصوصي'),
  government('government', Color(0xFF1F4E9C), Color(0xFFFFFFFF), 'حكومي'),
  truck('truck', Color(0xFFF6C31A), Color(0xFF111111), 'حمل'),
  taxi('taxi', Color(0xFFC62828), Color(0xFFFFFFFF), 'أجرة'),
  agricultural('agricultural', Color(0xFF2E7D32), Color(0xFFFFFFFF), 'زراعي');

  const PlateType(this.code, this.background, this.foreground, this.plateWord);

  final String code;
  final Color background;
  final Color foreground;

  /// The category as it is written on an old-style plate (always Arabic).
  final String plateWord;

  static PlateType fromCode(String? code) => PlateType.values
      .firstWhere((t) => t.code == code, orElse: () => PlateType.private);
}

/// The three ways a plate can be drawn. "english" is the current Iraqi plate
/// (governorate number, Latin letter, strip with IRQ), "german" the older one
/// (Arabic-Indic digits and letter, governorate and category underneath) and
/// "other" the plain card the app has always used.
enum PlateStyle {
  english('english'),
  german('german'),
  other('other');

  const PlateStyle(this.code);

  final String code;

  static PlateStyle fromCode(String? code) => PlateStyle.values
      .firstWhere((s) => s.code == code, orElse: () => PlateStyle.other);
}

/// The governorates, written as they appear on plates.
const iraqGovernorates = [
  'بغداد',
  'نينوى',
  'ميسان',
  'البصرة',
  'الأنبار',
  'القادسية',
  'المثنى',
  'بابل',
  'كربلاء',
  'ديالى',
  'السليمانية',
  'أربيل',
  'حلبجة',
  'دهوك',
  'كركوك',
  'صلاح الدين',
  'ذي قار',
  'النجف',
  'واسط',
];

/// The number printed first on a current plate: 11 Baghdad … 29 Wasit.
const iraqGovernorateCodes = {
  'بغداد': 11,
  'نينوى': 12,
  'ميسان': 13,
  'البصرة': 14,
  'الأنبار': 15,
  'القادسية': 16,
  'المثنى': 17,
  'بابل': 18,
  'كربلاء': 19,
  'ديالى': 20,
  'السليمانية': 21,
  'أربيل': 22,
  'حلبجة': 23,
  'دهوك': 24,
  'كركوك': 25,
  'صلاح الدين': 26,
  'ذي قار': 27,
  'النجف': 28,
  'واسط': 29,
};

/// Arabic plate letters and the Latin letter the current plates print for them.
const _latinFor = {
  'أ': 'A', 'ا': 'A', 'آ': 'A', 'إ': 'A', 'ب': 'B', 'ت': 'T', 'ث': 'T',
  'ج': 'J', 'ح': 'H', 'خ': 'K', 'د': 'D', 'ذ': 'D', 'ر': 'R', 'ز': 'Z',
  'س': 'S', 'ش': 'S', 'ص': 'S', 'ض': 'D', 'ط': 'T', 'ظ': 'Z', 'ع': 'E',
  'غ': 'G', 'ف': 'F', 'ق': 'Q', 'ك': 'K', 'ل': 'L', 'م': 'M', 'ن': 'N',
  'ه': 'H', 'ة': 'H', 'و': 'W', 'ي': 'Y', 'ى': 'Y',
};

/// The Latin form of a plate letter ("ك" → "K"; a Latin letter stays as it is).
String plateLatinLetter(String? letter) {
  final l = (letter ?? '').trim();
  if (l.isEmpty) return '';
  return _latinFor[l] ?? l.toUpperCase();
}

String _arabicDigits(String s) {
  const arabic = '٠١٢٣٤٥٦٧٨٩';
  return s.replaceAllMapped(
      RegExp(r'[0-9]'), (m) => arabic[int.parse(m.group(0)!)]);
}

/// An Iraqi licence plate in one of three designs, on the colour of the plate's
/// category (white private, blue government, yellow truck, red taxi, green
/// agricultural). Everything stays left-to-right inside an RTL screen.
class IraqiPlate extends StatelessWidget {
  const IraqiPlate({
    super.key,
    required this.number,
    this.letter,
    this.governorate,
    this.type = PlateType.private,
    this.style = PlateStyle.other,
    this.width = 220,
  });

  final String number;
  final String? letter;
  final String? governorate;
  final PlateType type;
  final PlateStyle style;
  final double width;

  /// Width ÷ height of each design.
  static double aspectOf(PlateStyle style) => switch (style) {
        PlateStyle.english => 4.6,
        PlateStyle.german => 2.05,
        PlateStyle.other => 220 / 64,
      };

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.ltr,
      child: switch (style) {
        PlateStyle.english => _EnglishPlate(plate: this),
        PlateStyle.german => _GermanPlate(plate: this),
        PlateStyle.other => _CardPlate(plate: this),
      },
    );
  }
}

/// The current plate: "14 · G 34567" on white with a black frame, and a strip
/// on the leading side carrying IRQ top to bottom in the colour of the category.
class _EnglishPlate extends StatelessWidget {
  const _EnglishPlate({required this.plate});

  final IraqiPlate plate;

  @override
  Widget build(BuildContext context) {
    final w = plate.width;
    final h = w / IraqiPlate.aspectOf(PlateStyle.english);
    final frame = h * 0.045;
    final type = plate.type;
    final code = iraqGovernorateCodes[plate.governorate];
    final latin = plateLatinLetter(plate.letter);
    final fontSize = h * 0.66;
    final textStyle = TextStyle(
      color: const Color(0xFF111111),
      fontSize: fontSize,
      fontWeight: FontWeight.w800,
      height: 1,
      letterSpacing: fontSize * 0.02,
      fontFamilyFallback: const ['Roboto', 'Arial'],
    );

    return Container(
      width: w,
      height: h,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(h * 0.12),
        border: Border.all(color: const Color(0xFF111111), width: frame),
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(h * 0.12 - frame),
        child: Row(
          children: [
            Container(
              width: w * 0.096,
              height: double.infinity,
              decoration: BoxDecoration(
                color: type.background,
                border: type == PlateType.private
                    ? Border(
                        right: BorderSide(
                            color: const Color(0xFF111111),
                            width: frame * 0.7))
                    : null,
              ),
              alignment: Alignment.center,
              child: Column(
                mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                children: [
                  for (final c in const ['I', 'R', 'Q'])
                    Text(c,
                        style: TextStyle(
                            color: type.foreground,
                            fontSize: h * 0.2,
                            fontWeight: FontWeight.w900,
                            height: 1)),
                ],
              ),
            ),
            Expanded(
              child: Padding(
                padding: EdgeInsets.symmetric(horizontal: w * 0.02),
                child: FittedBox(
                  fit: BoxFit.scaleDown,
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.center,
                    children: [
                      if (code != null) ...[
                        Text('$code', style: textStyle),
                        // The small square between the number and the letter.
                        Padding(
                          padding:
                              EdgeInsets.symmetric(horizontal: fontSize * 0.18),
                          child: Container(
                            width: fontSize * 0.26,
                            height: fontSize * 0.26,
                            decoration: BoxDecoration(
                              color: const Color(0xFF6F7B8C),
                              borderRadius:
                                  BorderRadius.circular(fontSize * 0.04),
                              border: Border.all(
                                  color: const Color(0xFF111111),
                                  width: math.max(0.8, fontSize * 0.025)),
                            ),
                          ),
                        ),
                      ],
                      if (latin.isNotEmpty) ...[
                        Text(latin, style: textStyle),
                        SizedBox(width: fontSize * 0.2),
                      ],
                      Text(plate.number.isEmpty ? '—' : plate.number,
                          style: textStyle),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// The older plate: a white panel with big Arabic-Indic digits and the letter,
/// a wavy line and the same in Latin under it, then a bottom band with the
/// governorate and the category. The strip with IRAQ is coloured by category.
class _GermanPlate extends StatelessWidget {
  const _GermanPlate({required this.plate});

  final IraqiPlate plate;

  @override
  Widget build(BuildContext context) {
    final w = plate.width;
    final h = w / IraqiPlate.aspectOf(PlateStyle.german);
    final frame = h * 0.028;
    final type = plate.type;
    final ink = const Color(0xFF111111);
    final letter = (plate.letter ?? '').trim();
    final digits = plate.number.isEmpty ? '—' : _arabicDigits(plate.number);
    final latinLine = [
      if (letter.isNotEmpty) plateLatinLetter(letter),
      ...plate.number.split(''),
    ].join(' ');
    final hasGov = (plate.governorate ?? '').isNotEmpty;

    return Container(
      width: w,
      height: h,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(h * 0.08),
        border: Border.all(color: ink, width: frame),
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(h * 0.08),
        child: Row(
          children: [
            // The strip: IRAQ top to bottom and a small black square.
            Container(
              width: w * 0.105,
              height: double.infinity,
              decoration: BoxDecoration(
                color: type.background,
                border: Border(
                    right: BorderSide(color: ink, width: frame * 0.7)),
              ),
              padding: EdgeInsets.symmetric(vertical: h * 0.06),
              child: Column(
                children: [
                  Expanded(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                      children: [
                        for (final c in const ['I', 'R', 'A', 'Q'])
                          Text(c,
                              style: TextStyle(
                                  color: type.foreground,
                                  fontSize: h * 0.115,
                                  fontWeight: FontWeight.w900,
                                  height: 1)),
                      ],
                    ),
                  ),
                  SizedBox(height: h * 0.04),
                  Container(
                      width: w * 0.04, height: w * 0.04, color: ink),
                ],
              ),
            ),
            Expanded(
              child: Padding(
                padding: EdgeInsets.all(h * 0.04),
                child: Column(
                  children: [
                    Expanded(
                      flex: 6,
                      child: Container(
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(h * 0.05),
                          border: Border.all(color: ink, width: frame * 0.6),
                        ),
                        padding: EdgeInsets.symmetric(
                            horizontal: w * 0.03, vertical: h * 0.02),
                        child: Column(
                          children: [
                            Expanded(
                              child: FittedBox(
                                fit: BoxFit.scaleDown,
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    if (letter.isNotEmpty) ...[
                                      Text(letter,
                                          textDirection: TextDirection.rtl,
                                          style: TextStyle(
                                              color: ink,
                                              fontSize: h * 0.4,
                                              fontWeight: FontWeight.w900,
                                              height: 1.1)),
                                      SizedBox(width: h * 0.06),
                                    ],
                                    Text(digits,
                                        style: TextStyle(
                                            color: ink,
                                            fontSize: h * 0.4,
                                            fontWeight: FontWeight.w900,
                                            height: 1.1)),
                                  ],
                                ),
                              ),
                            ),
                            SizedBox(
                              height: h * 0.1,
                              width: double.infinity,
                              child: CustomPaint(painter: _WavePainter(ink)),
                            ),
                            SizedBox(
                              height: h * 0.15,
                              child: FittedBox(
                                fit: BoxFit.scaleDown,
                                child: Text(latinLine,
                                    style: TextStyle(
                                        color: ink,
                                        fontSize: h * 0.13,
                                        fontWeight: FontWeight.w600,
                                        letterSpacing: h * 0.03,
                                        height: 1.1)),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    SizedBox(height: h * 0.03),
                    Expanded(
                      flex: 3,
                      child: Container(
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(h * 0.05),
                          border: Border.all(color: ink, width: frame * 0.6),
                        ),
                        padding: EdgeInsets.symmetric(horizontal: w * 0.04),
                        child: Row(
                          textDirection: TextDirection.rtl,
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Flexible(
                              child: FittedBox(
                                fit: BoxFit.scaleDown,
                                child: Text(hasGov ? plate.governorate! : '',
                                    textDirection: TextDirection.rtl,
                                    style: TextStyle(
                                        color: ink,
                                        fontSize: h * 0.17,
                                        fontWeight: FontWeight.w900,
                                        height: 1.1)),
                              ),
                            ),
                            SizedBox(width: w * 0.04),
                            FittedBox(
                              fit: BoxFit.scaleDown,
                              child: Text(type.plateWord,
                                  textDirection: TextDirection.rtl,
                                  style: TextStyle(
                                      color: ink,
                                      fontSize: h * 0.17,
                                      fontWeight: FontWeight.w900,
                                      height: 1.1)),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _WavePainter extends CustomPainter {
  const _WavePainter(this.color);

  final Color color;

  @override
  void paint(Canvas canvas, Size size) {
    final stroke = math.max(1.2, size.height * 0.2);
    final paint = Paint()
      ..color = color
      ..style = PaintingStyle.stroke
      ..strokeWidth = stroke
      ..strokeJoin = StrokeJoin.round;
    // A ring at each end, as on the printed plate; the wave runs between them.
    final r = size.height * 0.34;
    final left = r + stroke;
    final right = size.width - r - stroke;
    canvas.drawCircle(Offset(r, size.height / 2), r, paint);
    canvas.drawCircle(Offset(size.width - r, size.height / 2), r, paint);

    final path = Path()..moveTo(left, size.height / 2);
    const waves = 8;
    final span = right - left;
    for (var x = 0.0; x <= span; x += 1) {
      path.lineTo(
          left + x,
          size.height / 2 +
              math.sin(x / span * waves * 2 * math.pi) *
                  (size.height / 2 - stroke / 2));
    }
    canvas.drawPath(path, paint);
  }

  @override
  bool shouldRepaint(covariant _WavePainter old) => old.color != color;
}

/// The plain card: governorate on the side, the number large in the middle and
/// the letter beside it (the design every vehicle had before plate styles).
class _CardPlate extends StatelessWidget {
  const _CardPlate({required this.plate});

  final IraqiPlate plate;

  @override
  Widget build(BuildContext context) {
    final width = plate.width;
    final type = plate.type;
    final scale = width / 220;
    final fg = type.foreground;
    final border = type == PlateType.private
        ? const Color(0xFF111111)
        : fg.withValues(alpha: 0.85);
    final hasLetter = plate.letter != null && plate.letter!.isNotEmpty;
    final hasGov = plate.governorate != null && plate.governorate!.isNotEmpty;

    return Container(
      width: width,
      height: 64 * scale,
      padding: EdgeInsets.symmetric(horizontal: 8 * scale, vertical: 5 * scale),
      decoration: BoxDecoration(
        color: type.background,
        borderRadius: BorderRadius.circular(8 * scale),
        border: Border.all(color: border, width: 2 * scale),
      ),
      child: Row(
        children: [
          if (hasGov)
            SizedBox(
              width: 50 * scale,
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text('العراق',
                      style: TextStyle(
                          color: fg.withValues(alpha: 0.8),
                          fontSize: 8 * scale,
                          fontWeight: FontWeight.w600)),
                  const SizedBox(height: 2),
                  Text(plate.governorate!,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      textDirection: TextDirection.rtl,
                      style: TextStyle(
                          color: fg,
                          fontSize: 11 * scale,
                          fontWeight: FontWeight.w800)),
                ],
              ),
            ),
          if (hasGov)
            Container(
              width: 1.5 * scale,
              margin: EdgeInsets.symmetric(horizontal: 6 * scale),
              color: fg.withValues(alpha: 0.5),
            ),
          Expanded(
            child: FittedBox(
              fit: BoxFit.scaleDown,
              child: Text(
                plate.number.isEmpty ? '—' : plate.number,
                style: TextStyle(
                    color: fg,
                    fontSize: 34 * scale,
                    fontWeight: FontWeight.w800,
                    letterSpacing: 2 * scale,
                    height: 1),
              ),
            ),
          ),
          if (hasLetter)
            Container(
              width: 1.5 * scale,
              margin: EdgeInsets.symmetric(horizontal: 6 * scale),
              color: fg.withValues(alpha: 0.5),
            ),
          if (hasLetter)
            Text(plate.letter!,
                textDirection: TextDirection.rtl,
                style: TextStyle(
                    color: fg,
                    fontSize: 30 * scale,
                    fontWeight: FontWeight.w800,
                    height: 1)),
        ],
      ),
    );
  }
}
