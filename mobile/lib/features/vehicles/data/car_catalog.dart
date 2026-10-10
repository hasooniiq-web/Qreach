import 'dart:convert';

import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

class CarMake {
  const CarMake({required this.en, required this.ar, required this.models});

  final String en;
  final String ar;
  final List<String> models;

  String label(bool arabic) => arabic ? ar : en;
}

/// Makes and models common on Iraqi roads (assets/vehicles/car_catalog.json).
/// A make or model that is not listed can still be typed in the form.
class CarCatalog {
  const CarCatalog(this.makes);

  final List<CarMake> makes;

  static const empty = CarCatalog([]);

  factory CarCatalog.fromJson(String source) {
    final json = jsonDecode(source) as Map<String, dynamic>;
    return CarCatalog([
      for (final m in json['makes'] as List<dynamic>)
        CarMake(
          en: (m as Map<String, dynamic>)['en'] as String,
          ar: m['ar'] as String,
          models: (m['models'] as List<dynamic>).cast<String>(),
        ),
    ]);
  }

  /// The make a stored value refers to (matched on either language's name).
  CarMake? find(String? stored) {
    if (stored == null) return null;
    final s = stored.trim().toLowerCase();
    for (final m in makes) {
      if (m.en.toLowerCase() == s || m.ar == stored.trim()) return m;
    }
    return null;
  }
}

final carCatalogProvider = FutureProvider<CarCatalog>((ref) async {
  try {
    return CarCatalog.fromJson(
        await rootBundle.loadString('assets/vehicles/car_catalog.json'));
  } catch (_) {
    return CarCatalog.empty; // the form still works with free text
  }
});
