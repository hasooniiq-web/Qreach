import '../../l10n/app_localizations.dart';
import 'iraqi_plate.dart';

String plateTypeLabel(AppLocalizations l10n, PlateType type) => switch (type) {
      PlateType.private => l10n.plateTypePrivate,
      PlateType.government => l10n.plateTypeGovernment,
      PlateType.truck => l10n.plateTypeTruck,
      PlateType.taxi => l10n.plateTypeTaxi,
      PlateType.agricultural => l10n.plateTypeAgricultural,
    };
