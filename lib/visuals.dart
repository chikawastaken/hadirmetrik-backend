import 'dart:math' as math;
import 'dart:ui' as ui;

import 'package:flutter/material.dart';

import 'theme.dart';

class Logo extends StatelessWidget {
  const Logo({super.key, this.size = 48});
  final double size;
  @override
  Widget build(BuildContext context) => Image.asset('assets/images/logo.png', width: size, height: size, filterQuality: FilterQuality.medium);
}

// ---------------------------------------------------------------- bars

class Bars extends StatelessWidget {
  const Bars(this.values, this.labels, {super.key, this.highlight, this.height = 140, this.color = const Color(0xFFA9C3BD), this.hiColor = C.warning, this.valueLabels, this.onTap, this.selected, this.colors});
  final List<double> values;
  final List<String> labels;
  final int? highlight, selected;
  final double height;
  final Color color, hiColor;
  final List<String>? valueLabels;
  final List<Color>? colors;
  final ValueChanged<int>? onTap;

  @override
  Widget build(BuildContext context) {
    final max = values.reduce(math.max);
    return SizedBox(
      height: height + 24 + (valueLabels == null ? 0 : 18),
      child: TweenAnimationBuilder<double>(
        tween: Tween(begin: 0, end: 1),
        duration: const Duration(milliseconds: 600),
        curve: Curves.easeOutCubic,
        builder: (context, t, _) => Row(crossAxisAlignment: CrossAxisAlignment.end, children: [
          for (var i = 0; i < values.length; i++)
            Expanded(
              child: GestureDetector(
                behavior: HitTestBehavior.opaque,
                onTap: onTap == null ? null : () => onTap!(i),
                child: Column(mainAxisAlignment: MainAxisAlignment.end, children: [
                  if (valueLabels != null)
                    Padding(padding: const EdgeInsets.only(bottom: 4), child: Text(valueLabels![i], style: T.small.copyWith(color: C.text, fontWeight: FontWeight.w600))),
                  AnimatedContainer(
                    duration: const Duration(milliseconds: 200),
                    margin: const EdgeInsets.symmetric(horizontal: 4),
                    height: math.max(4, height * t * values[i] / max),
                    decoration: BoxDecoration(
                      color: colors?[i] ?? (i == highlight ? hiColor : color),
                      borderRadius: BorderRadius.circular(6),
                      border: selected == i ? Border.all(color: C.text, width: 1.5) : null,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(labels[i], style: T.small, maxLines: 1),
                ]),
              ),
            ),
        ]),
      ),
    );
  }
}

/// Horizontal stacked bar (salary composition).
class StackBar extends StatelessWidget {
  const StackBar(this.parts, {super.key, this.height = 8});
  final List<(double, Color)> parts;
  final double height;
  @override
  Widget build(BuildContext context) => Row(children: [
        for (var i = 0; i < parts.length; i++) ...[
          if (i > 0) const SizedBox(width: 3),
          Expanded(
            flex: (parts[i].$1 * 1000).round(),
            child: Container(height: height, decoration: BoxDecoration(color: parts[i].$2, borderRadius: BorderRadius.circular(height))),
          ),
        ],
      ]);
}

// ---------------------------------------------------------------- lines

class Series {
  const Series(this.values, this.color, {this.dashed = false, this.start = 0, this.dot = true});
  final List<double> values;
  final Color color;
  final bool dashed, dot;
  final int start;
}

class LineChart extends StatelessWidget {
  const LineChart({super.key, required this.series, required this.count, required this.labels, this.min = 0, required this.max, this.height = 150, this.band, this.marker, this.yTicks = const [], this.markerLabel});
  final List<Series> series;
  final int count;
  final List<String> labels;
  final double min, max, height;
  final (List<double>, List<double>, int)? band;
  final int? marker;
  final String? markerLabel;
  final List<double> yTicks;

  @override
  Widget build(BuildContext context) => Column(children: [
        SizedBox(
          height: height,
          width: double.infinity,
          child: RepaintBoundary(
            child: TweenAnimationBuilder<double>(
              tween: Tween(begin: 0, end: 1),
              duration: const Duration(milliseconds: 700),
              curve: Curves.easeOutCubic,
              builder: (_, t, _) => CustomPaint(painter: _LinePainter(this, t)),
            ),
          ),
        ),
        const SizedBox(height: 6),
        Padding(
          padding: EdgeInsets.only(left: yTicks.isEmpty ? 0 : 24),
          child: Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [for (final l in labels) Text(l, style: T.small)]),
        ),
      ]);
}

class _LinePainter extends CustomPainter {
  _LinePainter(this.c, this.t);
  final LineChart c;
  final double t;

  @override
  void paint(Canvas canvas, Size size) {
    final left = c.yTicks.isEmpty ? 0.0 : 24.0;
    final w = size.width - left - 6, h = size.height - 8;
    Offset p(int i, double v) => Offset(left + 3 + w * i / (c.count - 1), 4 + h * (1 - (v - c.min) / (c.max - c.min)));

    final grid = Paint()
      ..color = C.border
      ..strokeWidth = 1;
    for (final y in c.yTicks) {
      final o = p(0, y);
      _dash(canvas, Path()..moveTo(left, o.dy)..lineTo(size.width, o.dy), grid, 3, 4);
      final tp = TextPainter(text: TextSpan(text: y.toStringAsFixed(0), style: T.small), textDirection: TextDirection.ltr)..layout();
      tp.paint(canvas, Offset(0, o.dy - tp.height / 2));
    }

    canvas.save();
    canvas.clipRect(Rect.fromLTWH(0, 0, left + 6 + w * t, size.height));

    if (c.band != null) {
      final (lo, hi, s) = c.band!;
      final path = Path()..moveTo(p(s, hi[0]).dx, p(s, hi[0]).dy);
      for (var i = 1; i < hi.length; i++) {
        path.lineTo(p(s + i, hi[i]).dx, p(s + i, hi[i]).dy);
      }
      for (var i = lo.length - 1; i >= 0; i--) {
        path.lineTo(p(s + i, lo[i]).dx, p(s + i, lo[i]).dy);
      }
      canvas.drawPath(path..close(), Paint()..color = C.warning.withValues(alpha: .16));
    }

    if (c.marker != null) {
      final x = p(c.marker!, c.min).dx;
      _dash(canvas, Path()..moveTo(x, 0)..lineTo(x, size.height), Paint()..color = C.text3..strokeWidth = 1, 3, 3);
      if (c.markerLabel != null) {
        final tp = TextPainter(text: TextSpan(text: c.markerLabel, style: T.small.copyWith(color: C.text)), textDirection: TextDirection.ltr)..layout();
        tp.paint(canvas, Offset(x - tp.width - 4, 0));
      }
    }

    for (final s in c.series) {
      final path = Path();
      for (var i = 0; i < s.values.length; i++) {
        final o = p(s.start + i, s.values[i]);
        i == 0 ? path.moveTo(o.dx, o.dy) : path.lineTo(o.dx, o.dy);
      }
      final paint = Paint()
        ..color = s.color
        ..strokeWidth = 2.2
        ..style = PaintingStyle.stroke
        ..strokeJoin = StrokeJoin.round
        ..strokeCap = StrokeCap.round;
      s.dashed ? _dash(canvas, path, paint, 5, 4) : canvas.drawPath(path, paint);
      if (s.dot) {
        final last = p(s.start + s.values.length - 1, s.values.last);
        canvas.drawCircle(last, 5, Paint()..color = Colors.white);
        canvas.drawCircle(last, 5, Paint()..color = s.color..style = PaintingStyle.stroke..strokeWidth = 2);
      }
    }
    canvas.restore();
  }

  @override
  bool shouldRepaint(_LinePainter old) => old.t != t || old.c != c;
}

void _dash(Canvas canvas, Path path, Paint paint, double on, double off) {
  final stroke = Paint()
    ..color = paint.color
    ..strokeWidth = paint.strokeWidth
    ..style = PaintingStyle.stroke
    ..strokeCap = StrokeCap.round;
  for (final m in path.computeMetrics()) {
    for (double d = 0; d < m.length; d += on + off) {
      canvas.drawPath(m.extractPath(d, math.min(d + on, m.length)), stroke);
    }
  }
}

// ---------------------------------------------------------------- ring

class Ring extends StatelessWidget {
  const Ring(this.value, {super.key, this.size = 120, this.stroke = 12, this.color = C.primary, this.bg = C.bg, this.child});
  final double value, size, stroke;
  final Color color, bg;
  final Widget? child;
  @override
  Widget build(BuildContext context) => SizedBox(
        width: size,
        height: size,
        child: TweenAnimationBuilder<double>(
          tween: Tween(begin: 0, end: value),
          duration: const Duration(milliseconds: 800),
          curve: Curves.easeOutCubic,
          builder: (_, v, child) => CustomPaint(painter: _RingPainter(v, stroke, color, bg), child: Center(child: child)),
          child: child,
        ),
      );
}

class _RingPainter extends CustomPainter {
  _RingPainter(this.v, this.stroke, this.color, this.bg);
  final double v, stroke;
  final Color color, bg;
  @override
  void paint(Canvas canvas, Size size) {
    final r = Rect.fromLTWH(stroke / 2, stroke / 2, size.width - stroke, size.height - stroke);
    final p = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = stroke
      ..strokeCap = StrokeCap.round;
    canvas.drawArc(r, 0, math.pi * 2, false, p..color = bg);
    canvas.drawArc(r, -math.pi / 2, math.pi * 2 * v, false, p..color = color);
  }

  @override
  bool shouldRepaint(_RingPainter o) => o.v != v || o.color != color;
}

// ---------------------------------------------------------------- map

class MapPin {
  const MapPin(this.at, this.color, {this.size = 12});
  final Alignment at;
  final Color color;
  final double size;
}

/// Stylised street map with geofence; stands in for a real map tile layer.
class MapBox extends StatelessWidget {
  const MapBox({super.key, this.height = 160, this.radius = 16, this.fence = true, this.fenceSize = .34, this.pins = const [], this.dashedFence = false, this.center = Alignment.center});
  final double height, radius, fenceSize;
  final bool fence, dashedFence;
  final List<MapPin> pins;
  final Alignment center;
  @override
  Widget build(BuildContext context) => ClipRRect(
        borderRadius: BorderRadius.circular(radius),
        child: SizedBox(
          height: height,
          width: double.infinity,
          child: RepaintBoundary(child: CustomPaint(painter: _MapPainter(this))),
        ),
      );
}

class _MapPainter extends CustomPainter {
  _MapPainter(this.m);
  final MapBox m;

  @override
  void paint(Canvas canvas, Size s) {
    canvas.drawRect(Offset.zero & s, Paint()..color = const Color(0xFFE6EAE3));
    final block = Paint()..color = const Color(0xFFDDE2D9);
    final rnd = math.Random(7);
    for (var i = 0; i < 14; i++) {
      canvas.drawRRect(
        RRect.fromRectAndRadius(Rect.fromLTWH(rnd.nextDouble() * s.width, rnd.nextDouble() * s.height, 30 + rnd.nextDouble() * 60, 20 + rnd.nextDouble() * 40), const Radius.circular(4)),
        block,
      );
    }
    final road = Paint()
      ..color = Colors.white
      ..strokeWidth = 9
      ..strokeCap = StrokeCap.round;
    final thin = Paint()
      ..color = Colors.white
      ..strokeWidth = 5;
    canvas.drawLine(Offset(0, s.height * .32), Offset(s.width, s.height * .22), road);
    canvas.drawLine(Offset(s.width * .62, 0), Offset(s.width * .5, s.height), road);
    canvas.drawLine(Offset(0, s.height * .78), Offset(s.width, s.height * .86), thin);
    canvas.drawLine(Offset(s.width * .18, 0), Offset(s.width * .26, s.height), thin);
    canvas.drawLine(Offset(s.width * .85, 0), Offset(s.width * .92, s.height), thin);

    final c = m.center.alongSize(s);
    if (m.fence) {
      final r = s.shortestSide * m.fenceSize;
      canvas.drawCircle(c, r, Paint()..color = C.accent.withValues(alpha: .14));
      final stroke = Paint()
        ..color = C.accent
        ..style = PaintingStyle.stroke
        ..strokeWidth = 2;
      if (m.dashedFence) {
        _dash(canvas, Path()..addOval(Rect.fromCircle(center: c, radius: r)), stroke, 6, 5);
      } else {
        canvas.drawCircle(c, r, stroke);
      }
      canvas.drawCircle(c, 11, Paint()..color = Colors.white);
      canvas.drawCircle(c, 8, Paint()..color = C.text);
    }
    for (final p in m.pins) {
      final o = p.at.alongSize(s);
      canvas.drawCircle(o, p.size / 2 + 3, Paint()..color = Colors.white);
      canvas.drawCircle(o, p.size / 2, Paint()..color = p.color);
    }
  }

  @override
  bool shouldRepaint(_MapPainter o) => o.m != m;
}

// ---------------------------------------------------------------- face

/// Neutral face silhouette standing in for the camera preview.
class FaceArt extends StatelessWidget {
  const FaceArt({super.key, this.blur = false});
  final bool blur;
  @override
  Widget build(BuildContext context) {
    final art = CustomPaint(painter: _FacePainter(), size: Size.infinite);
    return blur ? ImageFiltered(imageFilter: ui.ImageFilter.blur(sigmaX: 6, sigmaY: 6), child: art) : art;
  }
}

class _FacePainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size s) {
    final w = s.width, h = s.height;
    canvas.drawRect(Offset.zero & s, Paint()..color = const Color(0xFF3B4A37));
    // hair
    canvas.drawOval(Rect.fromCenter(center: Offset(w / 2, h * .42), width: w * .78, height: h * .74), Paint()..color = const Color(0xFF2A211C));
    // shoulders
    canvas.drawOval(Rect.fromCenter(center: Offset(w / 2, h * 1.06), width: w * 1.1, height: h * .42), Paint()..color = const Color(0xFF55473F));
    // neck
    canvas.drawRect(Rect.fromCenter(center: Offset(w / 2, h * .8), width: w * .26, height: h * .2), Paint()..color = const Color(0xFFB98468));
    // face
    final face = Rect.fromCenter(center: Offset(w / 2, h * .48), width: w * .6, height: h * .62);
    canvas.drawOval(
      face,
      Paint()..shader = const RadialGradient(colors: [Color(0xFFDDAA8C), Color(0xFFB98468)], radius: .7).createShader(face),
    );
  }

  @override
  bool shouldRepaint(_) => false;
}

enum OvalState { searching, valid, failed }

/// Camera-style oval: face art inside, dashed/solid border, optional
/// segmented progress ring (face enrollment).
class FaceOval extends StatelessWidget {
  const FaceOval({super.key, this.width = 250, this.state = OvalState.searching, this.segments = 0, this.done = 0, this.active, this.blur = false, this.child});
  final double width;
  final OvalState state;
  final int segments, done;
  final int? active;
  final bool blur;
  final Widget? child;
  @override
  Widget build(BuildContext context) {
    final h = width * 1.3;
    return SizedBox(
      width: width + 28,
      height: h + 28,
      child: Stack(alignment: Alignment.center, children: [
        ClipOval(child: SizedBox(width: width, height: h, child: FaceArt(blur: blur))),
        Positioned.fill(child: CustomPaint(painter: _OvalPainter(state, segments, done, active))),
        ?child,
      ]),
    );
  }
}

class _OvalPainter extends CustomPainter {
  _OvalPainter(this.state, this.segments, this.done, this.active);
  final OvalState state;
  final int segments, done;
  final int? active;

  @override
  void paint(Canvas canvas, Size s) {
    final inner = Rect.fromLTWH(14, 14, s.width - 28, s.height - 28);
    if (segments > 0) {
      final outer = inner.inflate(8);
      const gapA = .08;
      final seg = math.pi * 2 / segments;
      for (var i = 0; i < segments; i++) {
        final color = i < done ? const Color(0xFFDCE6CF) : (i == active ? Colors.white : Colors.white.withValues(alpha: .25));
        canvas.drawArc(
          outer,
          -math.pi / 2 + seg * i + gapA / 2,
          seg - gapA,
          false,
          Paint()
            ..color = color
            ..style = PaintingStyle.stroke
            ..strokeWidth = 7
            ..strokeCap = StrokeCap.round,
        );
      }
      return;
    }
    final paint = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = 3
      ..color = switch (state) {
        OvalState.searching => Colors.white,
        OvalState.valid => const Color(0xFF2DD4BF),
        OvalState.failed => C.danger,
      };
    final path = Path()..addOval(inner.inflate(2));
    state == OvalState.searching ? _dash(canvas, path, paint, 9, 7) : canvas.drawPath(path, paint);
  }

  @override
  bool shouldRepaint(_OvalPainter o) => o.state != state || o.done != done || o.active != active;
}

// ---------------------------------------------------------------- calendar

class MonthGrid extends StatelessWidget {
  const MonthGrid({super.key, required this.year, required this.month, this.today, this.dots = const {}, this.onTap, this.selected});
  final int year, month;
  final int? today, selected;
  final Map<int, Color> dots;
  final ValueChanged<int>? onTap;

  @override
  Widget build(BuildContext context) {
    final first = DateTime(year, month, 1);
    final days = DateTime(year, month + 1, 0).day;
    final lead = first.weekday - 1;
    final cells = lead + days;
    return Column(children: [
      Row(children: [for (final d in const ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']) Expanded(child: Center(child: Text(d, style: T.small)))]),
      const SizedBox(height: 8),
      for (var r = 0; r < (cells / 7).ceil(); r++)
        Row(children: [
          for (var c = 0; c < 7; c++)
            Expanded(
              child: Builder(builder: (context) {
                final day = r * 7 + c - lead + 1;
                if (day < 1 || day > days) return const SizedBox(height: 46);
                final isToday = day == today;
                final sel = day == selected;
                final weekend = c >= 5;
                final filled = sel || (isToday && selected == null);
                final fg = filled ? Colors.white : (weekend ? C.danger : C.text);
                return SizedBox(
                  height: 46,
                  child: Center(
                    child: Material(
                      color: Colors.transparent,
                      child: InkWell(
                        onTap: onTap == null ? null : () => onTap!(day),
                        borderRadius: BorderRadius.circular(12),
                        hoverColor: C.accent.withValues(alpha: .12),
                        splashColor: C.accent.withValues(alpha: .2),
                        child: AnimatedContainer(
                          duration: const Duration(milliseconds: 160),
                          width: 40,
                          height: 40,
                          decoration: BoxDecoration(
                            color: sel ? C.accent : (filled ? C.primary : null),
                            borderRadius: BorderRadius.circular(12),
                            border: isToday && !filled ? Border.all(color: C.primary, width: 1.5) : null,
                          ),
                          child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
                            Text('$day', style: T.bodyM.copyWith(fontWeight: FontWeight.w600, color: fg)),
                            const SizedBox(height: 3),
                            Container(width: 5, height: 5, decoration: BoxDecoration(shape: BoxShape.circle, color: filled ? Colors.white : dots[day] ?? Colors.transparent)),
                          ]),
                        ),
                      ),
                    ),
                  ),
                );
              }),
            ),
        ]),
    ]);
  }
}

class Legend extends StatelessWidget {
  const Legend(this.items, {super.key});
  final List<(String, Color)> items;
  @override
  Widget build(BuildContext context) => Wrap(spacing: 14, runSpacing: 6, children: [
        for (final (l, c) in items)
          Row(mainAxisSize: MainAxisSize.min, children: [
            Container(width: 8, height: 8, decoration: BoxDecoration(color: c, shape: BoxShape.circle)),
            const SizedBox(width: 6),
            Text(l, style: T.small),
          ]),
      ]);
}
