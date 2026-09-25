import 'package:flutter/material.dart';

/// Plate categories as issued in Iraq, each with its own colour.
enum PlateType {
  private('private', Color(0xFFFFFFFF), Color(0xFF111111)),
  government('government', Color(0xFF1F4E9C), Color(0xFFFFFFFF)),
  truck('truck', Color(0xFFF6C31A), Color(0xFF111111)),
  taxi('taxi', Color(0xFFC62828), Color(0xFFFFFFFF)),
  agricultural('agricultural', Color(0xFF2E7D32), Color(0xFFFFFFFF));

  const PlateType(this.code, this.background, this.foreground);

  final String code;
  final Color background;
  final Color foreground;

  static PlateType fromCode(String? code) => PlateType.values
      .firstWhere((t) => t.code == code, orElse: () => PlateType.private);
}

/// The 18 governorates, written as they appear on plates.
const iraqGovernorates = [
  'بغداد',
  'البصرة',
  'نينوى',
  'أربيل',
  'السليمانية',
  'دهوك',
  'كركوك',
  'الأنبار',
  'ديالى',
  'بابل',
  'كربلاء',
  'النجف',
  'واسط',
  'ميسان',
  'ذي قار',
  'المثنى',
  'القادسية',
  'صلاح الدين',
];

/// An Iraqi licence plate: governorate on the side, the number large in the
/// middle and the letter beside it, on the colour of the plate's category
/// (white private, blue government, yellow truck, red taxi, green
/// agricultural). Numbers stay left-to-right inside an RTL screen.
class IraqiPlate extends StatelessWidget {
  const IraqiPlate({
    super.key,
    required this.number,
    this.letter,
    this.governorate,
    this.type = PlateType.private,
    this.width = 220,
  });

  final String number;
  final String? letter;
  final String? governorate;
  final PlateType type;
  final double width;

  @override
  Widget build(BuildContext context) {
    final scale = width / 220;
    final fg = type.foreground;
    final border = type == PlateType.private
        ? const Color(0xFF111111)
        : fg.withValues(alpha: 0.85);
    final hasLetter = letter != null && letter!.isNotEmpty;
    final hasGov = governorate != null && governorate!.isNotEmpty;

    return Directionality(
      textDirection: TextDirection.ltr,
      child: Container(
        width: width,
        height: 64 * scale,
        padding:
            EdgeInsets.symmetric(horizontal: 8 * scale, vertical: 5 * scale),
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
                    Text(governorate!,
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
                  number.isEmpty ? '—' : number,
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
              Text(letter!,
                  textDirection: TextDirection.rtl,
                  style: TextStyle(
                      color: fg,
                      fontSize: 30 * scale,
                      fontWeight: FontWeight.w800,
                      height: 1)),
          ],
        ),
      ),
    );
  }
}
