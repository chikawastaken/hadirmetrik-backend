import 'package:flutter/cupertino.dart' show CupertinoPageTransitionsBuilder;
import 'package:flutter/material.dart';

/// Design tokens from the HadirMetrik Figma file (sage theme).
abstract final class C {
  static const primary = Color(0xFF4F6648);
  static const secondary = Color(0xFF516550);
  static const ring = Color(0xFF445340);
  static const bg = Color(0xFFE0E5D1);
  static const mid = Color(0xFFB5C1AB);
  static const sageSoft = Color(0xFFEEF1E6);
  static const surface = Colors.white;

  static const text = Color(0xFF0F2742);
  static const text2 = Color(0xFF4E5652);
  static const text3 = Color(0xFF6B7370);
  static const border = Color(0xFFDFE4E2);
  static const skeleton = Color(0xFFEDF0E8);

  static const accent = Color(0xFF0B544E);
  static const accentSubtle = Color(0xFFE7F1F1);

  static const warning = Color(0xFFFC960F);
  static const warningText = Color(0xFF975A09);
  static const warningSubtle = Color(0xFFFFF5E7);

  static const danger = Color(0xFFDA5439);
  static const dangerText = Color(0xFF833222);
  static const dangerSubtle = Color(0xFFF9E5E1);

  static const info = Color(0xFF3B82F6);
  static const infoText = Color(0xFF1D4ED8);
  static const infoSubtle = Color(0xFFDBEAFE);

  static const violet = Color(0xFF7C3AED);
  static const violetSubtle = Color(0xFFEDE9FE);

  static const insight = Color(0xFFC2670E);
  static const insightText = Color(0xFF8A490A);
  static const insightSubtle = Color(0xFFF9F0E7);

  static const toast = Color(0xFF16263A);
}

/// Semantic tone used by badges, banners and icon tiles.
enum Tone {
  success(C.accent, C.accentSubtle),
  warning(C.warningText, C.warningSubtle),
  danger(C.dangerText, C.dangerSubtle),
  info(C.infoText, C.infoSubtle),
  violet(C.violet, C.violetSubtle),
  insight(C.insightText, C.insightSubtle),
  sage(C.primary, C.bg),
  neutral(C.text2, Color(0xFFF2F5F4));

  const Tone(this.fg, this.bg);
  final Color fg;
  final Color bg;
}

const _tab = [FontFeature.tabularFigures()];

/// Plus Jakarta Sans type scale.
abstract final class T {
  static const _f = 'Jakarta';
  static const display = TextStyle(fontFamily: _f, fontSize: 32, height: 40 / 32, fontWeight: FontWeight.w800, color: C.text, letterSpacing: -0.6);
  static const h1 = TextStyle(fontFamily: _f, fontSize: 24, height: 32 / 24, fontWeight: FontWeight.w700, color: C.text, letterSpacing: -0.3);
  static const h2 = TextStyle(fontFamily: _f, fontSize: 20, height: 28 / 20, fontWeight: FontWeight.w600, color: C.text);
  static const h3 = TextStyle(fontFamily: _f, fontSize: 16, height: 24 / 16, fontWeight: FontWeight.w600, color: C.text);
  static const title = TextStyle(fontFamily: _f, fontSize: 14, height: 20 / 14, fontWeight: FontWeight.w600, color: C.text);
  static const body = TextStyle(fontFamily: _f, fontSize: 14, height: 20 / 14, fontWeight: FontWeight.w400, color: C.text2);
  static const bodyM = TextStyle(fontFamily: _f, fontSize: 14, height: 20 / 14, fontWeight: FontWeight.w500, color: C.text);
  static const caption = TextStyle(fontFamily: _f, fontSize: 12, height: 16 / 12, fontWeight: FontWeight.w500, color: C.text3);
  static const small = TextStyle(fontFamily: _f, fontSize: 11, height: 14 / 11, fontWeight: FontWeight.w500, color: C.text3);
  static const overline = TextStyle(fontFamily: _f, fontSize: 11, height: 16 / 11, fontWeight: FontWeight.w700, color: C.text3, letterSpacing: 0.8);
  static const button = TextStyle(fontFamily: _f, fontSize: 15, height: 20 / 15, fontWeight: FontWeight.w600);

  static const clock = TextStyle(fontFamily: _f, fontSize: 64, height: 72 / 64, fontWeight: FontWeight.w800, color: Colors.white, letterSpacing: -1.92, fontFeatures: _tab);
  static const numXL = TextStyle(fontFamily: _f, fontSize: 40, height: 48 / 40, fontWeight: FontWeight.w700, color: C.text, fontFeatures: _tab, letterSpacing: -0.8);
  static const numL = TextStyle(fontFamily: _f, fontSize: 28, height: 36 / 28, fontWeight: FontWeight.w700, color: C.text, fontFeatures: _tab, letterSpacing: -0.4);
  static const numM = TextStyle(fontFamily: _f, fontSize: 20, height: 28 / 20, fontWeight: FontWeight.w600, color: C.text, fontFeatures: _tab);
  static const numS = TextStyle(fontFamily: _f, fontSize: 14, height: 20 / 14, fontWeight: FontWeight.w600, color: C.text, fontFeatures: _tab);
}

ThemeData buildTheme() {
  const scheme = ColorScheme.light(
    primary: C.primary,
    onPrimary: Colors.white,
    secondary: C.secondary,
    surface: C.surface,
    onSurface: C.text,
    error: C.danger,
  );
  return ThemeData(
    useMaterial3: true,
    colorScheme: scheme,
    fontFamily: 'Jakarta',
    scaffoldBackgroundColor: C.bg,
    splashFactory: InkSparkle.splashFactory,
    pageTransitionsTheme: const PageTransitionsTheme(builders: {
      TargetPlatform.android: CupertinoPageTransitionsBuilder(),
      TargetPlatform.iOS: CupertinoPageTransitionsBuilder(),
      TargetPlatform.windows: CupertinoPageTransitionsBuilder(),
      TargetPlatform.macOS: CupertinoPageTransitionsBuilder(),
      TargetPlatform.linux: CupertinoPageTransitionsBuilder(),
      TargetPlatform.fuchsia: CupertinoPageTransitionsBuilder(),
    }),
    textSelectionTheme: const TextSelectionThemeData(cursorColor: C.primary, selectionColor: C.mid, selectionHandleColor: C.primary),
    sliderTheme: const SliderThemeData(
      activeTrackColor: C.primary,
      inactiveTrackColor: C.bg,
      thumbColor: Colors.white,
      overlayColor: Color(0x224F6648),
      trackHeight: 6,
      thumbShape: RoundSliderThumbShape(enabledThumbRadius: 11, elevation: 2),
    ),
    switchTheme: SwitchThemeData(
      thumbColor: const WidgetStatePropertyAll(Colors.white),
      trackColor: WidgetStateProperty.resolveWith((s) => s.contains(WidgetState.selected) ? C.primary : C.border),
      trackOutlineColor: const WidgetStatePropertyAll(Colors.transparent),
    ),
    progressIndicatorTheme: const ProgressIndicatorThemeData(color: C.primary),
    dividerTheme: const DividerThemeData(color: C.border, thickness: 1, space: 1),
  );
}
