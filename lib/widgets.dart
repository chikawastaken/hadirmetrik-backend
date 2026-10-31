import 'package:flutter/material.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';

import 'theme.dart';

const gap4 = SizedBox(width: 4, height: 4);
const gap8 = SizedBox(width: 8, height: 8);
const gap12 = SizedBox(width: 12, height: 12);
const gap16 = SizedBox(width: 16, height: 16);
const gap20 = SizedBox(width: 20, height: 20);
const gap24 = SizedBox(width: 24, height: 24);

/// Bottom padding for scroll views that sit under the floating nav bar.
const navClearance = 112.0;
const pagePad = EdgeInsets.symmetric(horizontal: 20);

Future<R?> push<R>(BuildContext c, Widget page, {bool root = false}) =>
    Navigator.of(c, rootNavigator: root).push<R>(MaterialPageRoute(builder: (_) => page));

Future<R?> replace<R>(BuildContext c, Widget page, {bool root = false}) =>
    Navigator.of(c, rootNavigator: root).pushReplacement(MaterialPageRoute(builder: (_) => page));

// ---------------------------------------------------------------- surfaces

class AppCard extends StatelessWidget {
  const AppCard({super.key, required this.child, this.padding = const EdgeInsets.all(16), this.color = Colors.white, this.onTap, this.border, this.radius = 24, this.margin = EdgeInsets.zero});
  final Widget child;
  final EdgeInsets padding, margin;
  final Color color;
  final VoidCallback? onTap;
  final Color? border;
  final double radius;

  @override
  Widget build(BuildContext context) {
    final shape = RoundedRectangleBorder(
      borderRadius: BorderRadius.circular(radius),
      side: border == null ? BorderSide.none : BorderSide(color: border!, width: 1.5),
    );
    return Padding(
      padding: margin,
      child: Material(
        color: color,
        shape: shape,
        clipBehavior: Clip.antiAlias,
        child: InkWell(onTap: onTap, child: Padding(padding: padding, child: child)),
      ),
    );
  }
}

class CardTitle extends StatelessWidget {
  const CardTitle(this.title, {super.key, this.trailing, this.icon, this.action, this.onAction});
  final String title;
  final String? trailing, action;
  final IconData? icon;
  final VoidCallback? onAction;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: Row(children: [
          if (icon != null) ...[Icon(icon, size: 18, color: C.primary), gap8],
          Expanded(child: Text(title, style: T.h3)),
          if (trailing != null) Text(trailing!, style: T.caption),
          if (action != null)
            GestureDetector(
              onTap: onAction,
              behavior: HitTestBehavior.opaque,
              child: Padding(padding: const EdgeInsets.symmetric(vertical: 6), child: Text(action!, style: T.title.copyWith(color: C.primary))),
            ),
        ]),
      );
}

class Overline extends StatelessWidget {
  const Overline(this.text, {super.key, this.padding = const EdgeInsets.fromLTRB(4, 20, 4, 10)});
  final String text;
  final EdgeInsets padding;
  @override
  Widget build(BuildContext context) => Padding(padding: padding, child: Text(text.toUpperCase(), style: T.overline));
}

// ---------------------------------------------------------------- badges, tiles

class Pill extends StatelessWidget {
  const Pill(this.label, {super.key, this.icon, this.tone = Tone.success, this.solid = false});
  final String label;
  final IconData? icon;
  final Tone tone;
  final bool solid;

  @override
  Widget build(BuildContext context) {
    final fg = solid ? Colors.white : tone.fg;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: solid ? tone.fg : tone.bg,
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: tone.fg.withValues(alpha: .18)),
      ),
      child: Row(mainAxisSize: MainAxisSize.min, children: [
        if (icon != null) ...[Icon(icon, size: 12, color: fg), const SizedBox(width: 4)],
        Text(label, style: T.small.copyWith(color: fg, fontWeight: FontWeight.w600)),
      ]),
    );
  }
}

class IconTile extends StatelessWidget {
  const IconTile(this.icon, {super.key, this.tone = Tone.sage, this.size = 40, this.round = false});
  final IconData icon;
  final Tone tone;
  final double size;
  final bool round;
  @override
  Widget build(BuildContext context) => Container(
        width: size,
        height: size,
        decoration: BoxDecoration(color: tone.bg, borderRadius: BorderRadius.circular(round ? size : size * .3)),
        child: Icon(icon, size: size * .48, color: tone.fg),
      );
}

class Avatar extends StatelessWidget {
  const Avatar(this.initials, {super.key, this.size = 40, this.dot, this.bg = C.sageSoft});
  final String initials;
  final double size;
  final Color? dot;
  final Color bg;
  @override
  Widget build(BuildContext context) => SizedBox(
        width: size,
        height: size,
        child: Stack(clipBehavior: Clip.none, children: [
          Container(
            width: size,
            height: size,
            alignment: Alignment.center,
            decoration: BoxDecoration(color: bg, shape: BoxShape.circle, border: Border.all(color: C.border)),
            child: Text(initials, style: TextStyle(fontSize: size * .34, fontWeight: FontWeight.w700, color: C.primary)),
          ),
          if (dot != null)
            Positioned(
              left: 0,
              bottom: 0,
              child: Container(
                width: size * .28,
                height: size * .28,
                decoration: BoxDecoration(color: dot, shape: BoxShape.circle, border: Border.all(color: Colors.white, width: 2)),
              ),
            ),
        ]),
      );
}

class Banner2 extends StatelessWidget {
  const Banner2(this.title, {super.key, this.body, this.tone = Tone.warning, this.icon = LucideIcons.triangleAlert, this.onTap, this.trailing});
  final String title;
  final String? body;
  final Tone tone;
  final IconData icon;
  final VoidCallback? onTap;
  final Widget? trailing;
  @override
  Widget build(BuildContext context) => AppCard(
        color: tone.bg,
        border: tone.fg.withValues(alpha: .15),
        radius: 16,
        padding: const EdgeInsets.all(14),
        onTap: onTap,
        child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Padding(padding: const EdgeInsets.only(top: 1), child: Icon(icon, size: 18, color: tone.fg)),
          gap8,
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(title, style: T.title.copyWith(color: tone.fg)),
              if (body != null) ...[const SizedBox(height: 2), Text(body!, style: T.caption.copyWith(color: tone.fg))],
            ]),
          ),
          ?trailing,
          if (onTap != null && trailing == null) Icon(LucideIcons.chevronRight, size: 18, color: tone.fg),
        ]),
      );
}

class InfoRow extends StatelessWidget {
  const InfoRow(this.label, this.value, {super.key, this.icon, this.valueColor, this.sub});
  final String label, value;
  final IconData? icon;
  final Color? valueColor;
  final String? sub;
  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 9),
        child: Row(children: [
          if (icon != null) ...[Icon(icon, size: 18, color: C.text3), gap12],
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(label, style: T.body),
              if (sub != null) Text(sub!, style: T.small),
            ]),
          ),
          gap8,
          Flexible(child: Text(value, textAlign: TextAlign.right, style: T.numS.copyWith(color: valueColor ?? C.text))),
        ]),
      );
}

class Bar extends StatelessWidget {
  const Bar(this.value, {super.key, this.color = C.primary, this.height = 6, this.bg = C.bg});
  final double value;
  final Color color, bg;
  final double height;
  @override
  Widget build(BuildContext context) => ClipRRect(
        borderRadius: BorderRadius.circular(height),
        child: SizedBox(
          height: height,
          child: ColoredBox(
            color: bg,
            child: TweenAnimationBuilder<double>(
              tween: Tween(end: value.clamp(0, 1)),
              duration: const Duration(milliseconds: 450),
              curve: Curves.easeOutCubic,
              builder: (_, v, _) => FractionallySizedBox(alignment: Alignment.centerLeft, widthFactor: v, child: ColoredBox(color: color)),
            ),
          ),
        ),
      );
}

// ---------------------------------------------------------------- buttons

enum BtnKind { primary, soft, light, danger, white }

class Btn extends StatelessWidget {
  const Btn(this.label, {super.key, this.onTap, this.kind = BtnKind.primary, this.icon, this.expand = true, this.height = 52, this.busy = false});
  final String label;
  final VoidCallback? onTap;
  final BtnKind kind;
  final IconData? icon;
  final bool expand, busy;
  final double height;

  @override
  Widget build(BuildContext context) {
    final enabled = onTap != null && !busy;
    final (bg, fg) = switch (kind) {
      BtnKind.primary => (C.primary, Colors.white),
      BtnKind.soft => (C.accentSubtle, C.primary),
      BtnKind.light => (Colors.white, C.text),
      BtnKind.danger => (const Color(0xFF8A3A28), Colors.white),
      BtnKind.white => (Colors.white, C.text),
    };
    final btn = AnimatedContainer(
      duration: const Duration(milliseconds: 200),
      height: height,
      decoration: BoxDecoration(
        color: enabled || busy ? bg : const Color(0xFFDDE1D8),
        borderRadius: BorderRadius.circular(999),
        border: kind == BtnKind.light ? Border.all(color: C.border) : null,
      ),
      child: Material(
        type: MaterialType.transparency,
        child: InkWell(
          borderRadius: BorderRadius.circular(999),
          onTap: enabled ? onTap : null,
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: Row(mainAxisSize: MainAxisSize.min, mainAxisAlignment: MainAxisAlignment.center, children: [
              if (busy)
                SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: fg))
              else ...[
                if (icon != null) ...[Icon(icon, size: 18, color: enabled ? fg : C.text3), gap8],
                Flexible(child: Text(label, overflow: TextOverflow.ellipsis, style: T.button.copyWith(color: enabled ? fg : C.text3))),
              ],
            ]),
          ),
        ),
      ),
    );
    return expand ? SizedBox(width: double.infinity, child: btn) : btn;
  }
}

class CircleBtn extends StatelessWidget {
  const CircleBtn(this.icon, {super.key, this.onTap, this.bg = Colors.white, this.fg = C.text, this.size = 44, this.dot = false, this.tooltip});
  final IconData icon;
  final VoidCallback? onTap;
  final Color bg, fg;
  final double size;
  final bool dot;
  final String? tooltip;
  @override
  Widget build(BuildContext context) => Semantics(
        button: true,
        label: tooltip,
        child: Material(
          color: bg,
          shape: const CircleBorder(),
          child: InkWell(
            customBorder: const CircleBorder(),
            onTap: onTap,
            child: SizedBox(
              width: size,
              height: size,
              child: Stack(alignment: Alignment.center, children: [
                Icon(icon, size: size * .45, color: fg),
                if (dot)
                  Positioned(
                    right: size * .22,
                    top: size * .2,
                    child: Container(width: 9, height: 9, decoration: BoxDecoration(color: C.warning, shape: BoxShape.circle, border: Border.all(color: bg, width: 1.5))),
                  ),
              ]),
            ),
          ),
        ),
      );
}

/// Fixed action area at the bottom of detail pages.
class BottomActions extends StatelessWidget {
  const BottomActions(this.children, {super.key, this.note});
  final List<Widget> children;
  final Widget? note;
  @override
  Widget build(BuildContext context) => Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [Color(0x00E0E5D1), C.bg], stops: [0, .25]),
        ),
        padding: EdgeInsets.fromLTRB(20, 16, 20, 12 + MediaQuery.paddingOf(context).bottom),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          if (note != null) ...[note!, gap8],
          Row(children: [
            for (var i = 0; i < children.length; i++) ...[if (i > 0) gap12, Expanded(child: children[i])],
          ]),
        ]),
      );
}

// ---------------------------------------------------------------- headers

class TopBar extends StatelessWidget {
  const TopBar(this.title, {super.key, this.actions = const [], this.onBack, this.back = true});
  final String title;
  final List<Widget> actions;
  final VoidCallback? onBack;
  final bool back;
  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
        child: Row(children: [
          if (back)
            CircleBtn(LucideIcons.chevronLeft, tooltip: 'Kembali', onTap: onBack ?? () => Navigator.of(context).maybePop())
          else
            const SizedBox(width: 44),
          Expanded(child: Text(title, textAlign: TextAlign.center, style: T.h3, maxLines: 1, overflow: TextOverflow.ellipsis)),
          if (actions.isEmpty) const SizedBox(width: 44) else Row(mainAxisSize: MainAxisSize.min, children: actions),
        ]),
      );
}

class PageHeader extends StatelessWidget {
  const PageHeader(this.title, {super.key, this.subtitle, this.actions = const [], this.back = false});
  final String title;
  final String? subtitle;
  final List<Widget> actions;
  final bool back;
  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.fromLTRB(20, 12, 16, 12),
        child: Row(children: [
          if (back) ...[CircleBtn(LucideIcons.chevronLeft, tooltip: 'Kembali', onTap: () => Navigator.of(context).maybePop()), gap12],
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(title, style: T.h1.copyWith(color: C.primary)),
              if (subtitle != null) Text(subtitle!, style: T.caption.copyWith(color: C.text2)),
            ]),
          ),
          for (final a in actions) Padding(padding: const EdgeInsets.only(left: 8), child: a),
        ]),
      );
}

/// Standard page: header + scrollable body + optional fixed bottom actions.
class Screen extends StatelessWidget {
  const Screen({super.key, required this.header, required this.children, this.bottom, this.inShell = false, this.padding, this.controller});
  final Widget header;
  final List<Widget> children;
  final Widget? bottom;
  final bool inShell;
  final EdgeInsets? padding;
  final ScrollController? controller;
  @override
  Widget build(BuildContext context) => Scaffold(
        backgroundColor: C.bg,
        body: SafeArea(
          bottom: false,
          child: Column(children: [
            header,
            Expanded(
              child: ListView(
                controller: controller,
                padding: padding ?? EdgeInsets.fromLTRB(20, 4, 20, inShell ? navClearance : 24),
                children: children,
              ),
            ),
          ]),
        ),
        bottomNavigationBar: bottom,
      );
}

// ---------------------------------------------------------------- inputs

class Segmented extends StatelessWidget {
  const Segmented(this.items, this.index, this.onChanged, {super.key});
  final List<String> items;
  final int index;
  final ValueChanged<int> onChanged;
  @override
  Widget build(BuildContext context) => Container(
        height: 48,
        padding: const EdgeInsets.all(4),
        decoration: BoxDecoration(color: Colors.white.withValues(alpha: .5), borderRadius: BorderRadius.circular(999), border: Border.all(color: Colors.white)),
        child: LayoutBuilder(builder: (context, c) {
          final w = c.maxWidth / items.length;
          return Stack(children: [
            AnimatedPositioned(
              duration: const Duration(milliseconds: 260),
              curve: Curves.easeOutCubic,
              left: w * index,
              width: w,
              top: 0,
              bottom: 0,
              child: DecoratedBox(
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(999),
                  boxShadow: const [BoxShadow(color: Color(0x14000000), blurRadius: 8, offset: Offset(0, 2))],
                ),
              ),
            ),
            Row(children: [
              for (var i = 0; i < items.length; i++)
                Expanded(
                  child: GestureDetector(
                    behavior: HitTestBehavior.opaque,
                    onTap: () => onChanged(i),
                    child: Center(
                      child: AnimatedDefaultTextStyle(
                        duration: const Duration(milliseconds: 200),
                        style: T.bodyM.copyWith(color: i == index ? C.text : C.text3, fontWeight: i == index ? FontWeight.w600 : FontWeight.w500),
                        child: Text(items[i], maxLines: 1, overflow: TextOverflow.ellipsis),
                      ),
                    ),
                  ),
                ),
            ]),
          ]);
        }),
      );
}

class Chip2 extends StatelessWidget {
  const Chip2(this.label, {super.key, this.selected = false, this.onTap, this.icon, this.dark = false});
  final String label;
  final bool selected, dark;
  final VoidCallback? onTap;
  final IconData? icon;
  @override
  Widget build(BuildContext context) {
    final bg = selected ? (dark ? C.text : C.primary) : Colors.white;
    final fg = selected ? Colors.white : C.text;
    return Material(
      color: Colors.transparent,
      child: InkWell(
        borderRadius: BorderRadius.circular(999),
        onTap: onTap,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 180),
          height: 38,
          padding: const EdgeInsets.symmetric(horizontal: 16),
          decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(999), border: Border.all(color: selected ? bg : C.border)),
          child: Row(mainAxisSize: MainAxisSize.min, children: [
            if (icon != null) ...[Icon(icon, size: 16, color: fg), const SizedBox(width: 6)],
            Text(label, style: T.bodyM.copyWith(color: fg, fontWeight: selected ? FontWeight.w600 : FontWeight.w500)),
          ]),
        ),
      ),
    );
  }
}

/// Horizontally scrolling single-select chip row.
class ChipBar extends StatelessWidget {
  const ChipBar(this.items, this.selected, this.onSelect, {super.key, this.leading, this.dark = false, this.padding = EdgeInsets.zero});
  final List<String> items;
  final int selected;
  final ValueChanged<int> onSelect;
  final Widget? leading;
  final bool dark;
  final EdgeInsets padding;
  @override
  Widget build(BuildContext context) => SizedBox(
        height: 40,
        child: ListView(
          scrollDirection: Axis.horizontal,
          padding: padding,
          children: [
            if (leading != null) ...[leading!, gap8],
            for (var i = 0; i < items.length; i++) ...[
              if (i > 0) gap8,
              Chip2(items[i], selected: i == selected, dark: dark, onTap: () => onSelect(i)),
            ],
          ],
        ),
      );
}

/// Wrapping chip group; used in filter sheets and forms.
class ChipWrap extends StatelessWidget {
  const ChipWrap(this.items, this.isSelected, this.onTap, {super.key});
  final List<String> items;
  final bool Function(int) isSelected;
  final ValueChanged<int> onTap;
  @override
  Widget build(BuildContext context) => Wrap(spacing: 8, runSpacing: 8, children: [
        for (var i = 0; i < items.length; i++) Chip2(items[i], selected: isSelected(i), onTap: () => onTap(i)),
      ]);
}

class Field extends StatelessWidget {
  const Field({super.key, this.label, this.hint, this.icon, this.controller, this.obscure = false, this.suffix, this.maxLines = 1, this.error, this.keyboard, this.helper, this.onChanged, this.readOnly = false, this.onTap, this.maxLength, this.initial});
  final String? label, hint, error, helper, initial;
  final IconData? icon;
  final TextEditingController? controller;
  final bool obscure, readOnly;
  final Widget? suffix;
  final int maxLines;
  final int? maxLength;
  final TextInputType? keyboard;
  final ValueChanged<String>? onChanged;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    OutlineInputBorder b(Color c, [double w = 1]) => OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: BorderSide(color: c, width: w));
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      if (label != null) Padding(padding: const EdgeInsets.only(bottom: 8, left: 2), child: Text(label!, style: T.title)),
      TextFormField(
        controller: controller,
        initialValue: controller == null ? initial : null,
        obscureText: obscure,
        maxLines: maxLines,
        maxLength: maxLength,
        keyboardType: keyboard,
        readOnly: readOnly,
        onTap: onTap,
        onChanged: onChanged,
        style: T.bodyM,
        decoration: InputDecoration(
          hintText: hint,
          hintStyle: T.body.copyWith(color: C.text3),
          filled: true,
          fillColor: Colors.white,
          isDense: true,
          counterStyle: T.small,
          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
          prefixIcon: icon == null ? null : Icon(icon, size: 20, color: C.text3),
          suffixIcon: suffix,
          enabledBorder: b(error != null ? C.danger : C.border),
          focusedBorder: b(error != null ? C.danger : C.primary, 1.5),
          border: b(C.border),
        ),
      ),
      if (error != null)
        Padding(
          padding: const EdgeInsets.only(top: 6, left: 2),
          child: Row(children: [
            const Icon(LucideIcons.triangleAlert, size: 14, color: C.danger),
            gap4,
            Expanded(child: Text(error!, style: T.caption.copyWith(color: C.dangerText))),
          ]),
        )
      else if (helper != null)
        Padding(padding: const EdgeInsets.only(top: 6, left: 2), child: Text(helper!, style: T.caption)),
    ]);
  }
}

/// Read-only field-looking button (date, dropdown).
class PickerBox extends StatelessWidget {
  const PickerBox({super.key, required this.value, this.label, this.icon, this.onTap, this.trailing = LucideIcons.chevronDown, this.small});
  final String value;
  final String? label, small;
  final IconData? icon;
  final IconData? trailing;
  final VoidCallback? onTap;
  @override
  Widget build(BuildContext context) => AppCard(
        radius: 16,
        border: C.border,
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        onTap: onTap,
        child: Row(children: [
          if (icon != null) ...[Icon(icon, size: 20, color: C.text3), gap12],
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              if (small != null) Text(small!, style: T.small),
              Text(value, style: T.bodyM.copyWith(fontWeight: FontWeight.w600)),
            ]),
          ),
          if (trailing != null) Icon(trailing, size: 18, color: C.text3),
        ]),
      );
}

class ToggleTile extends StatelessWidget {
  const ToggleTile(this.title, this.value, this.onChanged, {super.key, this.subtitle, this.dl = false});
  final String title;
  final String? subtitle;
  final bool value, dl;
  final ValueChanged<bool> onChanged;
  @override
  Widget build(BuildContext context) => InkWell(
        onTap: () => onChanged(!value),
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 10),
          child: Row(children: [
            if (dl) ...[const Icon(LucideIcons.sparkles, size: 16, color: C.primary), gap8],
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(title, style: T.title),
                if (subtitle != null) Text(subtitle!, style: T.caption),
              ]),
            ),
            Switch(value: value, onChanged: onChanged),
          ]),
        ),
      );
}

class RadioDot extends StatelessWidget {
  const RadioDot(this.selected, {super.key});
  final bool selected;
  @override
  Widget build(BuildContext context) => AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        width: 24,
        height: 24,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          color: selected ? C.accent : Colors.white,
          border: Border.all(color: selected ? C.accent : C.mid, width: 1.5),
        ),
        child: selected ? const Icon(LucideIcons.check, size: 14, color: Colors.white) : null,
      );
}

class CheckBox2 extends StatelessWidget {
  const CheckBox2(this.checked, {super.key, this.size = 22});
  final bool checked;
  final double size;
  @override
  Widget build(BuildContext context) => AnimatedContainer(
        duration: const Duration(milliseconds: 160),
        width: size,
        height: size,
        decoration: BoxDecoration(
          color: checked ? C.primary : Colors.white,
          borderRadius: BorderRadius.circular(6),
          border: Border.all(color: checked ? C.primary : C.mid, width: 1.5),
        ),
        child: checked ? Icon(LucideIcons.check, size: size * .65, color: Colors.white) : null,
      );
}

// ---------------------------------------------------------------- overlays

Future<R?> showSheet<R>(BuildContext context, WidgetBuilder builder) => showModalBottomSheet<R>(
      context: context,
      useRootNavigator: true,
      isScrollControlled: true,
      showDragHandle: true,
      backgroundColor: Colors.white,
      barrierColor: const Color(0x8C0F2742),
      constraints: BoxConstraints(maxHeight: MediaQuery.sizeOf(context).height * .92, maxWidth: 480),
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(28))),
      builder: (ctx) => Padding(
        padding: EdgeInsets.only(bottom: MediaQuery.viewInsetsOf(ctx).bottom),
        child: SafeArea(top: false, child: builder(ctx)),
      ),
    );

class SheetHead extends StatelessWidget {
  const SheetHead(this.title, {super.key, this.subtitle});
  final String title;
  final String? subtitle;
  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.fromLTRB(20, 0, 12, 12),
        child: Row(children: [
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(title, style: T.h2.copyWith(fontSize: 18)),
              if (subtitle != null) Text(subtitle!, style: T.caption),
            ]),
          ),
          CircleBtn(LucideIcons.x, size: 40, bg: const Color(0xFFF2F5F4), tooltip: 'Tutup', onTap: () => Navigator.of(context).pop()),
        ]),
      );
}

Future<bool> confirm(BuildContext context, {required String title, required String message, required String action, IconData icon = LucideIcons.send, bool danger = false}) async {
  final ok = await showDialog<bool>(
    context: context,
    barrierColor: const Color(0x8C0F2742),
    builder: (ctx) => Dialog(
      backgroundColor: Colors.white,
      insetPadding: const EdgeInsets.symmetric(horizontal: 28),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28)),
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 400),
        child: Padding(
          padding: const EdgeInsets.fromLTRB(24, 28, 24, 12),
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            IconTile(icon, tone: danger ? Tone.danger : Tone.success, size: 56),
            gap16,
            Text(title, textAlign: TextAlign.center, style: T.h2),
            gap8,
            Text(message, textAlign: TextAlign.center, style: T.body),
            gap24,
            Btn(action, kind: danger ? BtnKind.danger : BtnKind.primary, onTap: () => Navigator.pop(ctx, true)),
            gap4,
            TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              style: TextButton.styleFrom(minimumSize: const Size.fromHeight(48)),
              child: Text('Batal', style: T.button.copyWith(color: C.primary)),
            ),
          ]),
        ),
      ),
    ),
  );
  return ok ?? false;
}

const _bln = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
String fmtRange(DateTimeRange r) => '${r.start.day} ${_bln[r.start.month - 1]} – ${r.end.day} ${_bln[r.end.month - 1]} ${r.end.year}';

Future<DateTimeRange?> pickRange(BuildContext context, {DateTimeRange? initial}) => showDateRangePicker(
      context: context,
      firstDate: DateTime(2025, 1, 1),
      lastDate: DateTime(2026, 12, 31),
      currentDate: DateTime(2026, 10, 7),
      initialDateRange: initial,
      helpText: 'Pilih rentang tanggal',
      saveText: 'Simpan',
      cancelText: 'Batal',
      confirmText: 'Pilih',
      fieldStartLabelText: 'Mulai',
      fieldEndLabelText: 'Selesai',
      builder: (ctx, child) => Theme(
        data: Theme.of(ctx).copyWith(colorScheme: Theme.of(ctx).colorScheme.copyWith(primary: C.accent, onPrimary: Colors.white)),
        child: child!,
      ),
    );

void toast(BuildContext context, String msg, {String? action, VoidCallback? onAction, bool aboveNav = false, IconData icon = LucideIcons.circleCheck}) {
  final m = ScaffoldMessenger.of(context);
  m.clearSnackBars();
  m.showSnackBar(SnackBar(
    persist: false,
    behavior: SnackBarBehavior.floating,
    backgroundColor: C.toast,
    margin: EdgeInsets.fromLTRB(16, 0, 16, aboveNav ? 96 : 16),
    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
    duration: const Duration(seconds: 3),
    content: Row(children: [
      Icon(icon, size: 18, color: C.mid),
      gap12,
      Expanded(child: Text(msg, style: T.bodyM.copyWith(color: Colors.white))),
    ]),
    action: action == null ? null : SnackBarAction(label: action, textColor: C.mid, onPressed: onAction ?? () {}),
  ));
}

// ---------------------------------------------------------------- states

class Skel extends StatelessWidget {
  const Skel({super.key, this.w, this.h = 14, this.r = 8});
  final double? w;
  final double h, r;
  @override
  Widget build(BuildContext context) => Container(width: w, height: h, decoration: BoxDecoration(color: C.skeleton, borderRadius: BorderRadius.circular(r)));
}

/// Gentle opacity pulse for skeleton blocks; one controller per subtree.
class Pulse extends StatefulWidget {
  const Pulse({super.key, required this.child});
  final Widget child;
  @override
  State<Pulse> createState() => _PulseState();
}

class _PulseState extends State<Pulse> with SingleTickerProviderStateMixin {
  late final c = AnimationController(vsync: this, duration: const Duration(milliseconds: 900))..repeat(reverse: true);
  @override
  void dispose() {
    c.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => FadeTransition(opacity: Tween(begin: .55, end: 1.0).animate(c), child: widget.child);
}

/// Shows [skeleton] briefly on first build (simulated fetch), then fades to [child].
/// Change [token] to replay the skeleton (e.g. after switching a period filter).
class Loading extends StatefulWidget {
  const Loading({super.key, required this.skeleton, required this.child, this.token, this.ms = 650});
  final Widget skeleton, child;
  final Object? token;
  final int ms;
  @override
  State<Loading> createState() => _LoadingState();
}

class _LoadingState extends State<Loading> {
  bool ready = false;
  int run = 0;

  void _start() {
    ready = false;
    final my = ++run;
    Future.delayed(Duration(milliseconds: widget.ms), () {
      if (mounted && my == run) setState(() => ready = true);
    });
  }

  @override
  void initState() {
    super.initState();
    _start();
  }

  @override
  void didUpdateWidget(Loading old) {
    super.didUpdateWidget(old);
    if (old.token != widget.token) _start();
  }

  @override
  Widget build(BuildContext context) => AnimatedSwitcher(
        duration: const Duration(milliseconds: 280),
        child: ready ? KeyedSubtree(key: const ValueKey(1), child: widget.child) : KeyedSubtree(key: const ValueKey(0), child: Pulse(child: widget.skeleton)),
      );
}

/// Generic list skeleton: [n] card-shaped placeholders.
class SkelList extends StatelessWidget {
  const SkelList({super.key, this.n = 4, this.h = 104});
  final int n;
  final double h;
  @override
  Widget build(BuildContext context) => Column(children: [
        for (var i = 0; i < n; i++)
          Container(
            height: h,
            margin: const EdgeInsets.only(bottom: 12),
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(24)),
            child: const Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Skel(w: 80, h: 18, r: 9), gap12, Skel(h: 14), gap8, Skel(w: 160, h: 12)]),
          ),
      ]);
}

class EmptyState extends StatelessWidget {
  const EmptyState({super.key, required this.icon, required this.title, required this.message, this.action, this.onAction});
  final IconData icon;
  final String title, message;
  final String? action;
  final VoidCallback? onAction;
  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 48, horizontal: 24),
        child: Column(children: [
          Container(
            width: 112,
            height: 112,
            decoration: BoxDecoration(color: C.mid.withValues(alpha: .5), shape: BoxShape.circle),
            alignment: Alignment.center,
            child: Container(
              width: 72,
              height: 72,
              decoration: const BoxDecoration(color: C.primary, shape: BoxShape.circle),
              child: Icon(icon, size: 32, color: Colors.white),
            ),
          ),
          gap20,
          Text(title, style: T.h2, textAlign: TextAlign.center),
          gap8,
          Text(message, style: T.body, textAlign: TextAlign.center),
          if (action != null) ...[gap20, Btn(action!, expand: false, kind: BtnKind.soft, icon: LucideIcons.refreshCw, onTap: onAction)],
        ]),
      );
}

/// Menu row with icon, title, subtitle, chevron.
class MenuRow extends StatelessWidget {
  const MenuRow(this.icon, this.title, {super.key, this.subtitle, this.onTap, this.tone = Tone.sage, this.trailing, this.danger = false});
  final IconData icon;
  final String title;
  final String? subtitle;
  final VoidCallback? onTap;
  final Tone tone;
  final Widget? trailing;
  final bool danger;
  @override
  Widget build(BuildContext context) => InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 10),
          child: Row(children: [
            IconTile(icon, tone: danger ? Tone.danger : tone),
            gap12,
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(title, style: T.title.copyWith(color: danger ? C.dangerText : C.text)),
                if (subtitle != null) Text(subtitle!, style: T.caption),
              ]),
            ),
            trailing ?? (danger ? const SizedBox() : const Icon(LucideIcons.chevronRight, size: 18, color: C.text3)),
          ]),
        ),
      );
}

/// Column of children separated by hairlines.
class Divided extends StatelessWidget {
  const Divided(this.children, {super.key});
  final List<Widget> children;
  @override
  Widget build(BuildContext context) => Column(children: [
        for (var i = 0; i < children.length; i++) ...[if (i > 0) const Divider(), children[i]],
      ]);
}
