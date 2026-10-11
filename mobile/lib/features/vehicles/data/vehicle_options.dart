import 'package:flutter/material.dart';

import '../../../shared/widgets/choice_field.dart';

/// Colour choices for a vehicle. The stored value is the label in the user's
/// language ("Other" is a plain choice, nothing is typed).
///
/// With [kurdish] the labels are Kurdish while the stored value stays the Arabic
/// name, so the server and the public page keep one spelling per colour.
List<ChoiceOption> vehicleColorOptions(
    {required bool arabic, bool kurdish = false}) {
  Widget dot(Color c) => Container(
        width: 22,
        height: 22,
        decoration: BoxDecoration(
          color: c,
          shape: BoxShape.circle,
          border: Border.all(color: const Color(0x33000000)),
        ),
      );

  final colors = <(String, String, String, Color)>[
    ('أبيض', 'White', 'سپی', const Color(0xFFFFFFFF)),
    ('أسود', 'Black', 'ڕەش', const Color(0xFF111111)),
    ('فضي', 'Silver', 'زیوی', const Color(0xFFC0C0C0)),
    ('رمادي', 'Gray', 'خۆڵەمێشی', const Color(0xFF808080)),
    ('أحمر', 'Red', 'سوور', const Color(0xFFD32F2F)),
    ('أزرق', 'Blue', 'شین', const Color(0xFF1976D2)),
    ('أخضر', 'Green', 'سەوز', const Color(0xFF388E3C)),
    ('أصفر', 'Yellow', 'زەرد', const Color(0xFFFFC107)),
    ('برتقالي', 'Orange', 'پرتەقاڵی', const Color(0xFFF57C00)),
    ('بني', 'Brown', 'قاوەیی', const Color(0xFF6D4C41)),
    ('بيج', 'Beige', 'بێژ', const Color(0xFFD7C4A3)),
    ('ذهبي', 'Gold', 'زێڕین', const Color(0xFFC9A227)),
    ('خمري', 'Maroon', 'مەیی', const Color(0xFF800020)),
    ('كحلي', 'Navy', 'شینی تۆخ', const Color(0xFF1A237E)),
  ];

  return [
    for (final (ar, en, ku, c) in colors)
      ChoiceOption(
          value: arabic ? ar : en,
          label: kurdish ? ku : (arabic ? ar : en),
          leading: dot(c)),
  ];
}

/// How the emergency contact relates to the owner (father, mother, ...).
/// Same rule as the colours: the stored value is Arabic for Arabic and Kurdish.
List<ChoiceOption> relationshipOptions(
    {required bool arabic, bool kurdish = false}) {
  const items = <(String, String, String)>[
    ('أب', 'Father', 'باوک'),
    ('أم', 'Mother', 'دایک'),
    ('أخ', 'Brother', 'برا'),
    ('أخت', 'Sister', 'خوشک'),
    ('زوج', 'Husband', 'مێرد'),
    ('زوجة', 'Wife', 'ژن'),
    ('ابن', 'Son', 'کوڕ'),
    ('ابنة', 'Daughter', 'کچ'),
    ('صديق', 'Friend', 'هاوڕێ'),
    ('صديقة', 'Female friend', 'هاوڕێی کچ'),
    ('قريب', 'Relative', 'خزم'),
    ('معرفة', 'Acquaintance', 'ناسیاو'),
  ];
  return [
    for (final (ar, en, ku) in items)
      ChoiceOption(
          value: arabic ? ar : en, label: kurdish ? ku : (arabic ? ar : en)),
  ];
}
