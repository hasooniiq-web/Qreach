import 'package:flutter/material.dart';

import '../../../shared/widgets/choice_field.dart';

/// Colour choices for a vehicle. The stored value is the label in the user's
/// language; "Other" lets any colour be typed.
List<ChoiceOption> vehicleColorOptions({required bool arabic}) {
  Widget dot(Color c) => Container(
        width: 22,
        height: 22,
        decoration: BoxDecoration(
          color: c,
          shape: BoxShape.circle,
          border: Border.all(color: const Color(0x33000000)),
        ),
      );

  final colors = <(String, String, Color)>[
    ('أبيض', 'White', const Color(0xFFFFFFFF)),
    ('أسود', 'Black', const Color(0xFF111111)),
    ('فضي', 'Silver', const Color(0xFFC0C0C0)),
    ('رمادي', 'Gray', const Color(0xFF808080)),
    ('أحمر', 'Red', const Color(0xFFD32F2F)),
    ('أزرق', 'Blue', const Color(0xFF1976D2)),
    ('أخضر', 'Green', const Color(0xFF388E3C)),
    ('أصفر', 'Yellow', const Color(0xFFFFC107)),
    ('برتقالي', 'Orange', const Color(0xFFF57C00)),
    ('بني', 'Brown', const Color(0xFF6D4C41)),
    ('بيج', 'Beige', const Color(0xFFD7C4A3)),
    ('ذهبي', 'Gold', const Color(0xFFC9A227)),
    ('خمري', 'Maroon', const Color(0xFF800020)),
    ('كحلي', 'Navy', const Color(0xFF1A237E)),
  ];

  return [
    for (final (ar, en, c) in colors)
      ChoiceOption(
          value: arabic ? ar : en, label: arabic ? ar : en, leading: dot(c)),
  ];
}

/// How the emergency contact relates to the owner (father, mother, ...).
List<ChoiceOption> relationshipOptions({required bool arabic}) {
  const items = <(String, String)>[
    ('أب', 'Father'),
    ('أم', 'Mother'),
    ('أخ', 'Brother'),
    ('أخت', 'Sister'),
    ('زوج', 'Husband'),
    ('زوجة', 'Wife'),
    ('ابن', 'Son'),
    ('ابنة', 'Daughter'),
    ('صديق', 'Friend'),
    ('صديقة', 'Female friend'),
    ('قريب', 'Relative'),
    ('معرفة', 'Acquaintance'),
  ];
  return [
    for (final (ar, en) in items)
      ChoiceOption(value: arabic ? ar : en, label: arabic ? ar : en),
  ];
}
