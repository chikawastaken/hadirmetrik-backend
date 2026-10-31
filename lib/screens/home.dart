import 'dart:async';

import 'package:flutter/material.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';

import '../data.dart';
import '../main.dart';
import '../state.dart';
import '../theme.dart';
import '../widgets.dart';
import 'clock.dart';
import 'me.dart';
import 'tasks.dart';

/// Rebuilds [builder] every second without touching the rest of the tree.
class Tick extends StatefulWidget {
  const Tick(this.builder, {super.key});
  final WidgetBuilder builder;
  @override
  State<Tick> createState() => _TickState();
}

class _TickState extends State<Tick> {
  Timer? t;
  @override
  void initState() {
    super.initState();
    t = Timer.periodic(const Duration(seconds: 1), (_) => setState(() {}));
  }

  @override
  void dispose() {
    t?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => RepaintBoundary(child: widget.builder(context));
}

String _greeting(int h) => h < 11 ? 'pagi' : (h < 15 ? 'siang' : (h < 18 ? 'sore' : 'malam'));

// ---------------------------------------------------------------- B01 beranda

class Home extends StatelessWidget {
  const Home({super.key});

  @override
  Widget build(BuildContext context) => Scaffold(
        backgroundColor: C.bg,
        body: ListenableBuilder(
          listenable: app,
          builder: (context, _) {
            final active = app.tasks.where((t) => t.status == TaskStatus.proses || t.status == TaskStatus.revisi).toList()
              ..sort((a, b) => (b.overdue ? 2 : (b.dueToday ? 1 : 0)) - (a.overdue ? 2 : (a.dueToday ? 1 : 0)));
            return ListView(padding: const EdgeInsets.only(bottom: navClearance), children: [
              _Hero(),
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 16, 20, 0),
                child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                  Row(children: [
                    _Summary('Jam Masuk', app.clockIn == null ? '--.--' : hm(app.clockIn!), LucideIcons.logIn),
                    gap8,
                    _Summary('Jam Pulang', app.clockOut == null ? '--.--' : hm(app.clockOut!), LucideIcons.logOut),
                    gap8,
                    Expanded(
                      child: AppCard(
                        padding: const EdgeInsets.all(12),
                        radius: 20,
                        child: _SummaryBody('Total Kerja', LucideIcons.timer, app.clockIn != null && !app.dayDone ? Tick((_) => Text(dur(app.worked), style: T.numS)) : Text(dur(app.worked), style: T.numS)),
                      ),
                    ),
                  ]),
                  if (app.clockIn != null && app.lateMinutes(app.clockIn!) > 0 && !app.dayDone) ...[
                    gap12,
                    Banner2('Kamu terlambat ${app.lateMinutes(app.clockIn!)} menit hari ini', body: 'Potongan Rp 25.000 akan tercatat di slip Oktober. Ada kendala? Ajukan koreksi dari Riwayat.', onTap: () => push(context, const History())),
                  ],
                  Overline('Tugas hari ini', padding: const EdgeInsets.fromLTRB(4, 24, 4, 10)),
                  for (final t in active.take(3))
                    Padding(padding: const EdgeInsets.only(bottom: 10), child: _TaskRow(t)),
                  Center(
                    child: TextButton.icon(
                      onPressed: () => Shell.go(1),
                      icon: const Icon(LucideIcons.arrowRight, size: 16),
                      iconAlignment: IconAlignment.end,
                      label: Text('Lihat semua ${active.length} tugas', style: T.title.copyWith(color: C.primary)),
                    ),
                  ),
                  const Overline('Gaji bulan ini', padding: EdgeInsets.fromLTRB(4, 12, 4, 10)),
                  _PayPreview(),
                  const Overline('Ringkasan Oktober'),
                  AppCard(
                    child: Column(children: [
                      Row(children: [
                        for (final (v, l, tone) in [('3', 'Hadir', Tone.success), ('2', 'Terlambat', Tone.warning), ('0', 'Absen', Tone.danger), ('3', 'Cuti', Tone.info)])
                          Expanded(
                            child: Column(children: [
                              Text(v, style: T.numL.copyWith(color: tone.fg)),
                              Text(l, style: T.caption),
                            ]),
                          ),
                      ]),
                      gap12,
                      const Divider(),
                      Row(children: [
                        Expanded(child: TextButton.icon(onPressed: () => push(context, const History()), icon: const Icon(LucideIcons.calendarDays, size: 16), label: const Text('Riwayat'))),
                        Container(width: 1, height: 24, color: C.border),
                        Expanded(child: TextButton.icon(onPressed: () => push(context, const Stats()), icon: const Icon(LucideIcons.chartLine, size: 16), label: const Text('Statistik · Skor 92'))),
                      ]),
                    ]),
                  ),
                  gap16,
                  AppCard(
                    color: C.insightSubtle,
                    border: C.insight.withValues(alpha: .2),
                    onTap: () => push(context, const Stats()),
                    child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      const IconTile(LucideIcons.sparkles, tone: Tone.insight),
                      gap12,
                      Expanded(
                        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                          Text('Insight untukmu [DL]', style: T.title.copyWith(color: C.insightText)),
                          gap4,
                          Text('Dua keterlambatanmu bulan ini terjadi saat hujan. Saat langit mendung, coba berangkat 15 menit lebih awal.', style: T.caption.copyWith(color: C.insightText)),
                        ]),
                      ),
                    ]),
                  ),
                ]),
              ),
            ]);
          },
        ),
      );
}

class _Summary extends StatelessWidget {
  const _Summary(this.label, this.value, this.icon);
  final String label, value;
  final IconData icon;
  @override
  Widget build(BuildContext context) => Expanded(
        child: AppCard(padding: const EdgeInsets.all(12), radius: 20, child: _SummaryBody(label, icon, Text(value, style: T.numS))),
      );
}

class _SummaryBody extends StatelessWidget {
  const _SummaryBody(this.label, this.icon, this.value);
  final String label;
  final IconData icon;
  final Widget value;
  @override
  Widget build(BuildContext context) => Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Icon(icon, size: 16, color: C.primary),
        gap8,
        Text(label, style: T.small),
        value,
      ]);
}

class _TaskRow extends StatelessWidget {
  const _TaskRow(this.t);
  final TaskItem t;
  @override
  Widget build(BuildContext context) => AppCard(
        padding: const EdgeInsets.all(14),
        radius: 20,
        onTap: () => push(context, TaskDetail(t), root: true),
        child: Row(children: [
          IconTile(t.status == TaskStatus.revisi ? LucideIcons.rotateCcw : LucideIcons.clipboardList, tone: t.overdue ? Tone.danger : (t.status == TaskStatus.revisi ? Tone.warning : Tone.sage)),
          gap12,
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(t.title, style: T.title, maxLines: 1, overflow: TextOverflow.ellipsis),
              gap4,
              Row(children: [
                Icon(LucideIcons.clock, size: 12, color: t.overdue ? C.danger : C.text3),
                gap4,
                Flexible(child: Text(t.overdue ? t.remaining : t.deadline, style: T.small.copyWith(color: t.overdue ? C.dangerText : C.text3), maxLines: 1)),
                if (t.status == TaskStatus.revisi) ...[gap8, const Pill('Revisi', tone: Tone.warning)],
              ]),
            ]),
          ),
          gap8,
          SizedBox(
            width: 40,
            height: 40,
            child: Stack(alignment: Alignment.center, children: [
              CircularProgressIndicator(value: t.progress, strokeWidth: 3.5, backgroundColor: C.bg),
              Text('${(t.progress * 100).round()}', style: T.small.copyWith(color: C.text, fontWeight: FontWeight.w700)),
            ]),
          ),
        ]),
      );
}

class _PayPreview extends StatelessWidget {
  const _PayPreview();
  @override
  Widget build(BuildContext context) => AppCard(
        color: C.primary,
        onTap: () => push(context, const Pay()),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [
            Text('Estimasi take-home Oktober', style: T.caption.copyWith(color: Colors.white70)),
            const Spacer(),
            CircleBtn(app.hideSalary ? LucideIcons.eyeOff : LucideIcons.eye, size: 36, bg: Colors.white.withValues(alpha: .14), fg: Colors.white, tooltip: app.hideSalary ? 'Tampilkan gaji' : 'Sembunyikan gaji', onTap: app.toggleSalary),
          ]),
          AnimatedSwitcher(
            duration: const Duration(milliseconds: 200),
            child: Text(app.hideSalary ? 'Rp •••••••' : rp(payOct.net), key: ValueKey(app.hideSalary), style: T.numL.copyWith(color: Colors.white)),
          ),
          gap8,
          Row(children: [
            const Icon(LucideIcons.trendingUp, size: 14, color: C.mid),
            gap4,
            Expanded(child: Text(app.hideSalary ? 'Dijadwalkan 23 Okt 2026' : 'Naik Rp 100.000 dari September', style: T.small.copyWith(color: C.mid))),
            Text('Lihat rincian', style: T.title.copyWith(color: Colors.white)),
            const Icon(LucideIcons.chevronRight, size: 16, color: Colors.white),
          ]),
        ]),
      );
}

// ---------------------------------------------------------------- hero + clock button

class _Hero extends StatelessWidget {
  // Not const: rebuilt by Home's ListenableBuilder.
  const _Hero();

  @override
  Widget build(BuildContext context) {
    final now = app.now;
    return Container(
      decoration: const BoxDecoration(color: C.primary, borderRadius: BorderRadius.vertical(bottom: Radius.circular(32))),
      padding: EdgeInsets.fromLTRB(20, MediaQuery.paddingOf(context).top + 12, 20, 28),
      child: Column(children: [
        Row(children: [
          Avatar(rina.initials, size: 44, bg: Colors.white),
          gap12,
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('Selamat ${_greeting(now.hour)}, ${app.profile?.employee.fullName?.split(' ').first ?? 'Karyawan'}', style: T.h3.copyWith(color: Colors.white)),
              Text(tanggal(now), style: T.caption.copyWith(color: C.mid)),
            ]),
          ),
          CircleBtn(LucideIcons.bell, dot: app.unread > 0, tooltip: 'Notifikasi', bg: Colors.white.withValues(alpha: .14), fg: Colors.white, onTap: () => push(context, const Notifs())),
        ]),
        gap24,
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(999)),
          child: Row(mainAxisSize: MainAxisSize.min, children: [
            const Icon(LucideIcons.briefcase, size: 14, color: C.primary),
            gap8,
            Text('Shift Reguler 08.00 - 17.00', style: T.caption.copyWith(color: C.text, fontWeight: FontWeight.w600)),
          ]),
        ),
        gap8,
        const _LiveClock(),
        gap16,
        _ClockButton(),
        gap24,
        Container(
          padding: const EdgeInsets.fromLTRB(6, 6, 16, 6),
          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(999)),
          child: Row(mainAxisSize: MainAxisSize.min, children: [
            IconTile(app.insideArea ? LucideIcons.mapPin : LucideIcons.mapPinOff, size: 32, round: true, tone: app.insideArea ? Tone.sage : Tone.warning),
            gap8,
            Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(app.insideArea ? 'Dalam area · 24 m' : 'Di luar area · 1,2 km', style: T.small.copyWith(color: app.insideArea ? C.accent : C.warningText, fontWeight: FontWeight.w700)),
              Text('Kantor Pusat Yogyakarta', style: T.caption.copyWith(color: C.text)),
            ]),
          ]),
        ),
        gap12,
        Row(mainAxisSize: MainAxisSize.min, children: [
          Icon(app.insideArea ? LucideIcons.shieldCheck : LucideIcons.shieldAlert, size: 14, color: C.mid),
          gap8,
          Text(app.insideArea ? 'Lokasi terverifikasi, perangkat aman' : 'Mendekat ke kantor untuk absen', style: T.caption.copyWith(color: C.mid)),
        ]),
      ]),
    );
  }
}

class _LiveClock extends StatelessWidget {
  const _LiveClock();
  @override
  Widget build(BuildContext context) => GestureDetector(
        onLongPress: () => demoSheet(context),
        child: Tick((_) => Text(hm(app.now), style: T.clock)),
      );
}

class _ClockButton extends StatefulWidget {
  const _ClockButton();
  @override
  State<_ClockButton> createState() => _ClockButtonState();
}

class _ClockButtonState extends State<_ClockButton> with SingleTickerProviderStateMixin {
  late final c = AnimationController(vsync: this, duration: const Duration(milliseconds: 2200))..repeat();

  @override
  void dispose() {
    c.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final (icon, title, sub, live) = switch (app) {
      _ when app.onLeave => (LucideIcons.plane, 'Cuti', 'Hari ini kamu cuti', false),
      _ when app.dayDone => (LucideIcons.check, 'Selesai', 'Pulang pukul ${hm(app.clockOut!)}', false),
      _ when !app.insideArea => (LucideIcons.mapPinOff, 'Di luar area', 'Ketuk untuk detail', false),
      _ when app.clockIn != null => (LucideIcons.logOut, 'Clock Out', 'Masuk pukul ${hm(app.clockIn!)}', true),
      _ => (LucideIcons.scanFace, 'Clock In', 'Masuk kerja', true),
    };
    final dim = app.onLeave || app.dayDone || !app.insideArea;
    return Semantics(
      button: true,
      label: '$title, $sub',
      child: GestureDetector(
        onTap: () => startClock(context),
        child: SizedBox(
          width: 236,
          height: 236,
          child: Stack(alignment: Alignment.center, children: [
            if (live)
              RepaintBoundary(
                child: AnimatedBuilder(
                  animation: c,
                  builder: (_, _) => Container(
                    width: 200 + 36 * c.value,
                    height: 200 + 36 * c.value,
                    decoration: BoxDecoration(shape: BoxShape.circle, color: Colors.white.withValues(alpha: .16 * (1 - c.value))),
                  ),
                ),
              ),
            AnimatedContainer(
              duration: const Duration(milliseconds: 300),
              width: 200,
              height: 200,
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(shape: BoxShape.circle, color: dim ? const Color(0xFF5E7457) : C.ring),
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 300),
                decoration: BoxDecoration(shape: BoxShape.circle, color: dim ? const Color(0xFFCCD3C2) : C.bg),
                child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
                  Icon(icon, size: 40, color: C.primary),
                  gap8,
                  Text(title, style: T.h2.copyWith(color: C.primary, fontWeight: FontWeight.w700)),
                  Text(sub, style: T.caption.copyWith(color: C.text2)),
                ]),
              ),
            ),
          ]),
        ),
      ),
    );
  }
}

/// Long-press the clock: pick a scenario and a time to walk through every state.
void demoSheet(BuildContext context) => showSheet(
      context,
      (ctx) => ListenableBuilder(
        listenable: app,
        builder: (ctx, _) => ListView(shrinkWrap: true, padding: const EdgeInsets.only(bottom: 16), children: [
          const SheetHead('Mode demo', subtitle: 'Simulasikan kondisi perangkat dan waktu'),
          Padding(
            padding: pagePad,
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('Jam', style: T.title),
              gap8,
              Wrap(spacing: 8, runSpacing: 8, children: [
                for (final (h, m) in const [(7, 52), (8, 12), (17, 5), (19, 10)]) Chip2('${h.toString().padLeft(2, '0')}.${m.toString().padLeft(2, '0')}', selected: app.now.hour == h && app.now.minute == m, onTap: () => app.setTime(h, m)),
              ]),
              gap16,
              Btn('Reset absen hari ini', kind: BtnKind.light, icon: LucideIcons.rotateCcw, onTap: () {
                app
                  ..clockIn = null
                  ..clockOut = null
                  ..faceAttempts = 0
                  ..touch();
              }),
              gap16,
              Text('Kondisi', style: T.title),
            ]),
          ),
          for (final s in Sim.values)
            InkWell(
              onTap: () => app.setSim(s),
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                child: Row(children: [
                  Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(s.label, style: T.bodyM), Text(s.desc, style: T.small)])),
                  RadioDot(app.sim == s),
                ]),
              ),
            ),
        ]),
      ),
    );
