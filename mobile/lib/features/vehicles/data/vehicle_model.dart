import 'package:equatable/equatable.dart';

import '../../tags/data/tag_model.dart';

class VehicleModel extends Equatable {
  const VehicleModel({
    required this.id,
    this.nickname,
    required this.plateNumber,
    this.plateCountry,
    this.plateType = 'private',
    this.plateLetter,
    this.plateGovernorate,
    this.make,
    this.model,
    this.year,
    this.vin,
    this.color,
    this.vehicleType,
    this.description,
    this.status,
    this.tags = const [],
    this.previousTagSerial,
  });

  final String id;
  final String? nickname;
  final String plateNumber;
  final String? plateCountry;

  /// private | government | truck | taxi | agricultural
  final String plateType;
  final String? plateLetter;
  final String? plateGovernorate;
  final String? make;
  final String? model;
  final int? year;
  final String? vin;
  final String? color;
  final String? vehicleType;
  final String? description;
  final String? status;
  final List<TagModel> tags;

  /// Serial of the code the owner removed last, which can be linked again (null when none).
  final String? previousTagSerial;

  String get displayName =>
      nickname?.isNotEmpty == true ? nickname! : plateNumber;

  factory VehicleModel.fromJson(Map<String, dynamic> json) => VehicleModel(
        id: json['id'] as String,
        nickname: json['nickname'] as String?,
        plateNumber: json['plate_number'] as String,
        plateCountry: json['plate_country'] as String?,
        plateType: json['plate_type'] as String? ?? 'private',
        plateLetter: json['plate_letter'] as String?,
        plateGovernorate: json['plate_governorate'] as String?,
        make: json['make'] as String?,
        model: json['model'] as String?,
        year: json['year'] as int?,
        vin: json['vin'] as String?,
        color: json['color'] as String?,
        vehicleType: json['vehicle_type'] as String?,
        description: json['description'] as String?,
        status: json['status'] as String?,
        tags: (json['tags'] as List<dynamic>? ?? [])
            .map((t) => TagModel.fromJson(t as Map<String, dynamic>))
            .toList(),
        previousTagSerial:
            (json['previous_tag'] as Map<String, dynamic>?)?['serial_number']
                as String?,
      );

  Map<String, dynamic> toCreateJson() => {
        'plate_number': plateNumber,
        if (nickname != null && nickname!.isNotEmpty) 'nickname': nickname,
        if (plateCountry != null) 'plate_country': plateCountry,
        'plate_type': plateType,
        if (plateLetter != null && plateLetter!.isNotEmpty)
          'plate_letter': plateLetter,
        if (plateGovernorate != null && plateGovernorate!.isNotEmpty)
          'plate_governorate': plateGovernorate,
        if (make != null) 'make': make,
        if (model != null) 'model': model,
        if (year != null) 'year': year,
        if (vin != null && vin!.isNotEmpty) 'vin': vin,
        if (color != null) 'color': color,
        if (vehicleType != null) 'vehicle_type': vehicleType,
        if (description != null) 'description': description,
      };

  /// Edit payload: a field the owner emptied is sent as null so it is cleared
  /// on the server (create omits empty fields instead).
  Map<String, dynamic> toUpdateJson() => {
        'plate_number': plateNumber,
        'nickname': (nickname ?? '').isEmpty ? null : nickname,
        'plate_type': plateType,
        'plate_letter': (plateLetter ?? '').isEmpty ? null : plateLetter,
        'plate_governorate':
            (plateGovernorate ?? '').isEmpty ? null : plateGovernorate,
        'make': make,
        'model': model,
        'year': year,
        'vin': (vin ?? '').isEmpty ? null : vin,
        'color': color,
        if (vehicleType != null) 'vehicle_type': vehicleType,
      };

  @override
  List<Object?> get props => [
        id,
        nickname,
        plateNumber,
        plateCountry,
        plateType,
        plateLetter,
        plateGovernorate,
        make,
        model,
        year,
        vin,
        color,
        vehicleType,
        description,
        status,
        tags,
      ];
}
