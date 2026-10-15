import 'package:flutter/material.dart';


/// Which silhouette a vehicle gets. A taxi or agricultural plate overrides
/// the vehicle type (a taxi is drawn as a taxi, a farm plate as a tractor),
/// so the picture always matches what the vehicle actually is.
enum ThumbKind {
  sedan,
  suv,
  pickup,
  truck,
  bus,
  motorcycle,
  taxi,
  tractor,
  van,
  other
}

/// The supplied illustrations (assets/vehicles). A taxi uses the sedan picture;
/// only the tractor has no supplied picture yet and stays drawn in code.
const _assetFor = {
  ThumbKind.sedan: 'sedan',
  ThumbKind.taxi: 'sedan',
  ThumbKind.suv: 'suv',
  ThumbKind.pickup: 'pickup',
  ThumbKind.truck: 'truck',
  ThumbKind.bus: 'bus',
  ThumbKind.motorcycle: 'motorcycle',
  ThumbKind.van: 'van',
  ThumbKind.other: 'other',
};

/// The picture follows the vehicle type the owner chose — choosing a taxi or
/// agricultural plate no longer swaps it ([plateType] is accepted but ignored).
ThumbKind thumbKindFor({String? vehicleType, String? plateType}) {
  return switch (vehicleType) {
    'sedan' => ThumbKind.sedan,
    'suv' => ThumbKind.suv,
    'pickup' => ThumbKind.pickup,
    'truck' => ThumbKind.truck,
    'bus' => ThumbKind.bus,
    'motorcycle' => ThumbKind.motorcycle,
    'van' => ThumbKind.van,
    _ => ThumbKind.other,
  };
}

/// A small flat side-view illustration of a vehicle, drawn in code so the
/// whole set shares one visual style (one colour, same wheels, same window
/// cut-outs) and needs no image assets. Used as the default picture on a
/// vehicle card until the owner has a real photo.
class VehicleThumb extends StatelessWidget {
  const VehicleThumb({
    super.key,
    required this.kind,
    this.color = Colors.white,
    this.cutout = const Color(0xFF111111),
    this.width = 84,
  });

  final ThumbKind kind;

  /// Silhouette colour.
  final Color color;

  /// The card colour behind it — windows are "cut out" in this colour.
  final Color cutout;
  final double width;

  @override
  Widget build(BuildContext context) {
    final asset = _assetFor[kind];

    if (asset != null) {
      return SizedBox(
        width: width,
        height: width * 0.56,
        child: Image.asset('assets/vehicles/$asset.png',
            fit: BoxFit.contain, filterQuality: FilterQuality.medium),
      );
    }

    return SizedBox(
      width: width,
      height: width * 0.42,
      child: CustomPaint(painter: _ThumbPainter(kind, color, cutout)),
    );
  }
}

class _ThumbPainter extends CustomPainter {
  _ThumbPainter(this.kind, this.color, this.cutout);

  final ThumbKind kind;
  final Color color;
  final Color cutout;

  // Drawn in a 100 x 42 box, facing right, then scaled to the widget.
  @override
  void paint(Canvas canvas, Size size) {
    canvas.scale(size.width / 100, size.height / 42);

    final body = Paint()..color = color;
    final hole = Paint()..color = cutout;
    final stroke = Paint()
      ..color = color
      ..style = PaintingStyle.stroke
      ..strokeWidth = 3
      ..strokeCap = StrokeCap.round
      ..strokeJoin = StrokeJoin.round;

    void poly(List<Offset> pts, Paint paint) {
      final path = Path()..moveTo(pts.first.dx, pts.first.dy);
      for (final p in pts.skip(1)) {
        path.lineTo(p.dx, p.dy);
      }
      canvas.drawPath(path..close(), paint);
    }

    void window(double l, double t, double r, double b) => canvas.drawRRect(
        RRect.fromLTRBR(l, t, r, b, const Radius.circular(1.5)), hole);

    void wheel(double x, double y, double r) {
      canvas.drawCircle(Offset(x, y), r + 1.8, hole);
      canvas.drawCircle(Offset(x, y), r, body);
      canvas.drawCircle(Offset(x, y), r * 0.42, hole);
    }

    switch (kind) {
      case ThumbKind.sedan:
      case ThumbKind.taxi:
        poly(const [
          Offset(3, 32),
          Offset(5, 25),
          Offset(22, 22),
          Offset(32, 12),
          Offset(64, 12),
          Offset(76, 22),
          Offset(94, 25),
          Offset(97, 32),
        ], body);
        window(35, 14.5, 47, 22);
        window(50, 14.5, 63, 22);
        if (kind == ThumbKind.taxi) {
          canvas.drawRRect(
              RRect.fromLTRBR(42, 6, 56, 11.5, const Radius.circular(1.5)),
              body);
        }
        wheel(22, 32, 6);
        wheel(78, 32, 6);
      case ThumbKind.suv:
        poly(const [
          Offset(3, 32),
          Offset(4, 23),
          Offset(16, 21),
          Offset(24, 8),
          Offset(78, 8),
          Offset(86, 21),
          Offset(96, 24),
          Offset(97, 32),
        ], body);
        window(27, 11, 40, 21);
        window(43, 11, 57, 21);
        window(60, 11, 75, 21);
        wheel(22, 32, 6.5);
        wheel(78, 32, 6.5);
      case ThumbKind.pickup:
        poly(const [
          Offset(3, 32),
          Offset(4, 21),
          Offset(50, 21),
          Offset(50, 11),
          Offset(72, 11),
          Offset(82, 21),
          Offset(96, 24),
          Offset(97, 32),
        ], body);
        window(54, 13.5, 70, 21);
        wheel(20, 32, 6.5);
        wheel(78, 32, 6.5);
      case ThumbKind.truck:
        poly(const [
          Offset(3, 6),
          Offset(64, 6),
          Offset(64, 32),
          Offset(3, 32),
        ], body);
        poly(const [
          Offset(67, 32),
          Offset(67, 14),
          Offset(82, 14),
          Offset(92, 23),
          Offset(97, 25),
          Offset(97, 32),
        ], body);
        window(70, 16.5, 82, 23);
        wheel(16, 33, 6);
        wheel(36, 33, 6);
        wheel(80, 33, 6);
      case ThumbKind.bus:
        canvas.drawRRect(
            RRect.fromLTRBR(3, 5, 97, 33, const Radius.circular(5)), body);
        for (var i = 0; i < 5; i++) {
          window(9 + i * 15.5, 10, 21 + i * 15.5, 20);
        }
        window(86, 10, 94, 22);
        wheel(22, 33, 6);
        wheel(78, 33, 6);
      case ThumbKind.van:
      case ThumbKind.other:
        poly(const [
          Offset(3, 32),
          Offset(3, 8),
          Offset(70, 8),
          Offset(70, 12),
          Offset(84, 20),
          Offset(97, 24),
          Offset(97, 32),
        ], body);
        window(60, 12, 68, 20);
        window(72, 14, 82, 20);
        wheel(22, 32, 6.5);
        wheel(78, 32, 6.5);
      case ThumbKind.motorcycle:
        break; // drawn from assets/vehicles/motorcycle.png, never reaches the painter
      case ThumbKind.tractor:
        poly(const [
          Offset(44, 20),
          Offset(90, 20),
          Offset(90, 28),
          Offset(44, 28),
        ], body);
        canvas.drawRRect(
            RRect.fromLTRBR(14, 6, 46, 24, const Radius.circular(3)), body);
        window(19, 9.5, 41, 20);
        canvas.drawLine(const Offset(84, 14), const Offset(84, 20), stroke);
        wheel(30, 28, 11);
        wheel(80, 33, 6.5);
    }
  }

  @override
  bool shouldRepaint(covariant _ThumbPainter old) =>
      old.kind != kind || old.color != color || old.cutout != cutout;
}
