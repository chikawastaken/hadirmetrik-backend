import 'package:flutter/material.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';

import '../data.dart';
import '../main.dart';
import '../state.dart';
import '../theme.dart';
import '../visuals.dart';
import '../widgets.dart';
import 'auth.dart';
import 'hr_people.dart';
import 'leave.dart';
import 'me.dart';

const divisions = ['Operasional', 'Penjualan', 'Keuangan', 'HR & GA', 'IT'];
const sari = Person('Sari Wulandari', 'HR Officer', 'HR & GA', 'HM-0064', score: 97);

Widget _stat(String value, String label, {Color? color}) => Expanded(
      child: Column(children: [
        Text(value, style: T.numM.copyWith(color: color)),
        Text(label, textAlign: TextAlign.center, style: T.small),
      ]),
    );

/// Label + bar + value row used by the HR breakdown cards.
Widget barRow(String label, double v, String value, {Color color = C.primary, String? sub, VoidCallback? onTap}) => InkWell(
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 7),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [
            Expanded(child: Text(label, style: T.bodyM)),
            if (sub != null) ...[Text(sub, style: T.small), gap8],
            Text(value, style: T.numS),
          ]),
          gap4,
          Bar(v, color: color),
        ]),
      ),
    );

// ---------------------------------------------------------------- C01 dashboard

class HrDashboard extends StatefulWidget {
  const HrDashboard({super.key});
  @override
  State<HrDashboard> createState() => _HrDashboardState();
}

class _HrDashboardState extends State<HrDashboard> {
  int period = 0, div = 0, loc = 0, bar = 2;
  DateTimeRange? range;

  static const _days = ['24', '25', '28', '29', '30', '1', '2', '5', '6', '7'];
  static const _full = ['Kamis, 24 Sep', 'Jumat, 25 Sep', 'Senin, 28 Sep', 'Selasa, 29 Sep', 'Rabu, 30 Sep', 'Kamis, 1 Okt', 'Jumat, 2 Okt', 'Senin, 5 Okt', 'Selasa, 6 Okt', 'Rabu, 7 Okt'];
  static const _late = <double>[6, 9, 14, 8, 6, 9, 11, 7, 8, 9];

  void filter() {
    var (r, d, l) = (range, div, loc);
    showSheet(
      context,
      (ctx) => StatefulBuilder(
        builder: (ctx, set) => Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
          const SheetHead('Filter dashboard'),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 16),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('Periode', style: T.title),
              gap8,
              PickerBox(
                icon: LucideIcons.calendarRange,
                value: r == null ? 'Pilih tanggal' : fmtRange(r!),
                trailing: LucideIcons.chevronRight,
                onTap: () async {
                  final x = await pickRange(ctx, initial: r);
                  if (x != null) set(() => r = x);
                },
              ),
              gap16,
              Text('Divisi', style: T.title),
              gap8,
              ChipWrap(const ['Semua', ...divisions], (i) => i == d, (i) => set(() => d = i)),
              gap16,
              Text('Lokasi', style: T.title),
              gap8,
              ChipWrap(const ['Semua lokasi', 'Kantor Pusat Yogyakarta', 'Gudang Kalasan'], (i) => i == l, (i) => set(() => l = i)),
              gap20,
              Row(children: [
                Expanded(child: Btn('Reset', kind: BtnKind.light, onTap: () => set(() => (r, d, l) = (null, 0, 0)))),
                gap12,
                Expanded(
                  child: Btn('Terapkan', onTap: () {
                    setState(() {
                      range = r;
                      div = d;
                      loc = l;
                    });
                    Navigator.pop(ctx);
                  }),
                ),
              ]),
            ]),
          ),
        ]),
      ),
    );
  }

  void activitySheet() => showSheet(
        context,
        (ctx) => Column(mainAxisSize: MainAxisSize.min, children: [
          const SheetHead('Notifikasi HR', subtitle: 'Hari ini'),
          for (final (icon, tone, t, s) in _activity)
            ListTile(
              contentPadding: const EdgeInsets.symmetric(horizontal: 20),
              leading: IconTile(icon, tone: tone, round: true),
              title: Text(t, style: T.title),
              subtitle: Text(s, style: T.caption),
            ),
          gap16,
        ]),
      );

  static const _activity = [
    (LucideIcons.mapPinOff, Tone.danger, 'Fajar Nugroho terindikasi lokasi palsu', '08.47 · Gudang Kalasan'),
    (LucideIcons.fileText, Tone.info, 'Maya Anggraini mengajukan Cuti Tahunan', '08.55 · 19 sampai 21 Okt'),
    (LucideIcons.clipboardCheck, Tone.success, 'Rina Pratiwi mengirim bukti tugas', '09.12 · Update stok gudang Kalasan'),
    (LucideIcons.mapPin, Tone.warning, 'Hendra Kusuma clock-in di luar area', '08.31 · 1,4 km dari Gudang Kalasan'),
  ];

  @override
  Widget build(BuildContext context) => ListenableBuilder(
        listenable: app,
        builder: (context, _) {
          final k = [
            (186, '86,9%', '+2,1%', 9, '4,2%', 5, '2,3%', 14, '6,5%'),
            (182, '85,2%', '+1,4%', 11, '5,1%', 6, '2,8%', 15, '6,9%'),
            (184, '86,1%', '+0,8%', 10, '4,6%', 5, '2,4%', 15, '6,9%'),
          ][range != null ? 2 : period];
          final scale = div == 0 ? 1.0 : const [.38, .22, .14, .1, .16][div - 1];
          int s(int v) => (v * scale).round();
          final inc = app.incidents;
          return Screen(
            inShell: true,
            header: PageHeader('Dashboard', subtitle: 'Rabu, 7 Oktober 2026 · Halo, Sari', actions: [
              CircleBtn(LucideIcons.bell, dot: true, tooltip: 'Notifikasi', onTap: activitySheet),
              CircleBtn(LucideIcons.slidersHorizontal, dot: div + loc > 0 || range != null, tooltip: 'Filter', onTap: filter),
            ]),
            children: [
              Segmented(const ['Hari Ini', 'Minggu Ini', 'Bulan Ini'], range != null ? -1 : period, (i) => setState(() {
                    period = i;
                    range = null;
                  })),
              gap12,
              Row(children: [
                Pill(div == 0 ? 'Semua divisi' : divisions[div - 1], icon: LucideIcons.building2, tone: Tone.neutral),
                if (range != null) ...[
                  gap8,
                  InkWell(
                    onTap: () => setState(() => range = null),
                    borderRadius: BorderRadius.circular(99),
                    child: Pill(fmtRange(range!), icon: LucideIcons.x, tone: Tone.info),
                  ),
                ],
                const Spacer(),
                Container(width: 8, height: 8, decoration: const BoxDecoration(color: C.accent, shape: BoxShape.circle)),
                gap4,
                Text('Live · diperbarui 09.41', style: T.small),
              ]),
              gap12,
              Loading(
                token: (period, div, loc, range),
                skeleton: const SkelList(n: 3, h: 120),
                child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                  for (final row in [
                    [('Hadir', s(k.$1), '${k.$2} · ${k.$3}', Att.hadir), ('Terlambat', s(k.$4), k.$5, Att.terlambat)],
                    [('Absen', s(k.$6), k.$7, Att.absen), ('Izin/Cuti', s(k.$8), k.$9, Att.cuti)],
                  ]) ...[
                    Row(children: [
                      for (final (j, (l, v, sub, a)) in row.indexed) ...[
                        if (j > 0) gap12,
                        Expanded(
                          child: AppCard(
                            radius: 20,
                            onTap: () => Shell.go(1),
                            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                              Row(children: [IconTile(a.icon, tone: a.tone, size: 32, round: true), const Spacer(), Text(l, style: T.caption)]),
                              gap8,
                              Text('$v', style: T.numL),
                              Text(sub, style: T.small.copyWith(color: a == Att.hadir ? C.accent : C.text3)),
                            ]),
                          ),
                        ),
                      ],
                    ]),
                    gap12,
                  ],
                  if (inc.isNotEmpty)
                    Banner2(
                      '${inc.length} percobaan lokasi palsu hari ini',
                      body: inc.map((i) => i.who.name).join(', '),
                      tone: Tone.danger,
                      icon: LucideIcons.shieldAlert,
                      onTap: () => push(context, const LiveMonitor(tab: 2), root: true),
                      trailing: const Icon(LucideIcons.chevronRight, size: 18, color: C.dangerText),
                    )
                  else
                    const Banner2('Tidak ada kejadian keamanan', body: 'Semua clock-in hari ini lolos verifikasi.', tone: Tone.success, icon: LucideIcons.shieldCheck),
                  gap12,
                  AppCard(
                    child: Column(children: [
                      CardTitle('Tugas tim', icon: LucideIcons.clipboardCheck, action: 'Lihat semua', onAction: () => Shell.go(2)),
                      gap8,
                      Row(children: [_stat('42', 'Aktif'), _stat('6', 'Jatuh tempo hari ini', color: C.warningText), _stat('11', 'Menunggu review', color: C.infoText)]),
                    ]),
                  ),
                  gap12,
                  AppCard(
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      CardTitle('Tren keterlambatan', icon: LucideIcons.trendingUp, action: 'Detail', onAction: () => push(context, const LateTrend())),
                      Text('24 Sep sampai 7 Okt · hari kerja', style: T.small),
                      gap12,
                      AnimatedSwitcher(
                        duration: const Duration(milliseconds: 180),
                        child: Container(
                          key: ValueKey(bar),
                          width: double.infinity,
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(color: C.toast, borderRadius: BorderRadius.circular(12)),
                          child: Text(
                            '${_full[bar]} · ${_late[bar].round()} terlambat · ${_late[bar] >= 8.7 ? '+' : ''}${((_late[bar] / 8.7 - 1) * 100).round()}% dari rata-rata 8,7',
                            style: T.small.copyWith(color: Colors.white),
                          ),
                        ),
                      ),
                      gap8,
                      Bars(_late, _days, highlight: 2, selected: bar, height: 100, onTap: (i) => setState(() => bar = i)),
                    ]),
                  ),
                  gap12,
                  AppCard(
                    onTap: () => push(context, const WorkHours()),
                    child: Column(children: [
                      const CardTitle('Distribusi jam kerja', icon: LucideIcons.timer, trailing: 'Rata-rata 8,2 jam'),
                      gap4,
                      const StackBar([(.09, C.warning), (.75, C.accent), (.16, C.danger)], height: 10),
                      gap12,
                      for (final (l, n, p, c) in const [('< 7 jam', 18, '9%', C.warning), ('7 sampai 9 jam', 152, '75%', C.accent), ('> 9 jam', 32, '16%', C.danger)])
                        Padding(
                          padding: const EdgeInsets.symmetric(vertical: 3),
                          child: Row(children: [
                            Container(width: 10, height: 10, decoration: BoxDecoration(color: c, shape: BoxShape.circle)),
                            gap8,
                            Expanded(child: Text(l, style: T.body)),
                            Text('${s(n)} orang', style: T.numS),
                            SizedBox(width: 44, child: Text(p, textAlign: TextAlign.right, style: T.small)),
                          ]),
                        ),
                    ]),
                  ),
                  gap12,
                  AppCard(
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      const CardTitle('Kehadiran per divisi', icon: LucideIcons.grid3x3),
                      gap4,
                      Row(children: [const SizedBox(width: 92), for (final d in hariPendek.take(5)) Expanded(child: Center(child: Text(d, style: T.small)))]),
                      gap4,
                      for (final (name, vals) in const [
                        ('Operasional', [96, 94, 97, 95, 92]),
                        ('Penjualan', [84, 88, 90, 86, 81]),
                        ('Keuangan', [98, 97, 99, 96, 95]),
                        ('HR & GA', [100, 98, 100, 97, 96]),
                        ('IT', [91, 93, 89, 94, 90]),
                      ])
                        Padding(
                          padding: const EdgeInsets.only(bottom: 4),
                          child: Row(children: [
                            SizedBox(width: 92, child: Text(name, style: T.caption)),
                            for (final v in vals)
                              Expanded(
                                child: Container(
                                  height: 30,
                                  margin: const EdgeInsets.symmetric(horizontal: 2),
                                  alignment: Alignment.center,
                                  decoration: BoxDecoration(
                                    color: v >= 95 ? C.accent : (v >= 90 ? C.accent.withValues(alpha: .55) : (v >= 85 ? C.warning.withValues(alpha: .45) : C.danger.withValues(alpha: .55))),
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                  child: Text('$v', style: T.small.copyWith(color: v >= 90 ? Colors.white : C.text, fontWeight: FontWeight.w700)),
                                ),
                              ),
                          ]),
                        ),
                      gap8,
                      const Legend([('95% ke atas', C.accent), ('90 sampai 94%', Color(0xFF7FA8A4)), ('85 sampai 89%', Color(0xFFFDC57B)), ('di bawah 85%', Color(0xFFE99D8D))]),
                    ]),
                  ),
                  gap12,
                  AppCard(
                    child: Column(children: [
                      const CardTitle('Aktivitas terbaru', icon: LucideIcons.activity),
                      for (final (icon, tone, t, sub) in _activity)
                        Padding(
                          padding: const EdgeInsets.symmetric(vertical: 6),
                          child: Row(children: [
                            IconTile(icon, tone: tone, size: 36, round: true),
                            gap12,
                            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(t, style: T.bodyM), Text(sub, style: T.small)])),
                          ]),
                        ),
                    ]),
                  ),
                  gap12,
                  AppCard(
                    color: C.insightSubtle,
                    onTap: () => push(context, const Insights()),
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Row(children: [
                        const IconTile(LucideIcons.sparkles, tone: Tone.insight, size: 36, round: true),
                        gap12,
                        Expanded(child: Text('Prediksi minggu depan [DL]', style: T.h3.copyWith(color: C.insightText))),
                        const Icon(LucideIcons.chevronRight, size: 18, color: C.insightText),
                      ]),
                      gap12,
                      Text('Keterlambatan diprediksi naik 18% pada Senin, 12 Okt karena prakiraan hujan pagi. Divisi Penjualan paling berisiko, 6 orang.', style: T.body.copyWith(color: C.insightText)),
                      gap12,
                      Btn('Kirim pengingat ke tim', kind: BtnKind.white, icon: LucideIcons.send, height: 44, onTap: () => toast(context, 'Pengingat berangkat lebih awal dikirim ke 6 orang di Penjualan.', aboveNav: true)),
                    ]),
                  ),
                ]),
              ),
            ],
          );
        },
      );
}

// ---------------------------------------------------------------- C03 tren keterlambatan

class LateTrend extends StatefulWidget {
  const LateTrend({super.key});
  @override
  State<LateTrend> createState() => _LateTrendState();
}

class _LateTrendState extends State<LateTrend> {
  int range = 1;
  final shown = {0, 1};

  static const _divLines = [
    ('Penjualan', C.danger, <double>[12, 14, 11, 13, 10, 9]),
    ('Operasional', C.primary, <double>[7, 6, 8, 5, 6, 4]),
    ('Keuangan', C.info, <double>[2, 3, 2, 2, 1, 2]),
    ('IT', C.violet, <double>[4, 5, 3, 4, 4, 3]),
  ];

  @override
  Widget build(BuildContext context) {
    final (total, avg, delta) = const [('52', '7,4', 'Turun 9%'), ('179', '8,1', 'Turun 12%'), ('561', '8,6', 'Turun 5%')][range];
    return Screen(
      inShell: true,
      header: PageHeader('Tren Keterlambatan', subtitle: 'Semua divisi', back: true, actions: [
        CircleBtn(LucideIcons.share2, tooltip: 'Bagikan', onTap: () => toast(context, 'Tautan laporan disalin.', aboveNav: true)),
      ]),
      children: [
        Segmented(const ['7 hari', '30 hari', '90 hari'], range, (i) => setState(() => range = i)),
        gap12,
        AppCard(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(crossAxisAlignment: CrossAxisAlignment.end, children: [
              Text(total, style: T.numXL),
              gap8,
              Padding(padding: const EdgeInsets.only(bottom: 8), child: Text('kejadian', style: T.caption)),
              const Spacer(),
              Pill(delta, icon: LucideIcons.trendingDown),
            ]),
            Text('Rata-rata $avg per hari kerja', style: T.caption),
            gap16,
            LineChart(
              key: ValueKey(range),
              series: const [Series([11, 9, 10, 8, 9, 7, 8, 6, 7], C.primary, dot: false), Series([7, 8, 9, 8], C.warning, dashed: true, start: 8)],
              band: const ([6, 6.5, 7, 6], [8, 9.5, 11, 10.5], 8),
              count: 12,
              labels: const ['Minggu 1', 'Minggu 2', 'Minggu 3', 'Prediksi'],
              max: 14,
              yTicks: const [0, 7, 14],
              marker: 8,
              markerLabel: 'Hari ini',
            ),
            gap8,
            const Legend([('Aktual', C.primary), ('Prediksi [DL]', C.warning)]),
          ]),
        ),
        const Overline('PERBANDINGAN DIVISI'),
        AppCard(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Wrap(spacing: 8, runSpacing: 8, children: [
              for (final (i, (l, _, _)) in _divLines.indexed)
                Chip2(l, selected: shown.contains(i), onTap: () => setState(() => shown.contains(i) && shown.length > 1 ? shown.remove(i) : shown.add(i))),
            ]),
            gap16,
            LineChart(
              series: [for (final i in shown) Series(_divLines[i].$3, _divLines[i].$2)],
              count: 6,
              labels: const ['M36', 'M37', 'M38', 'M39', 'M40', 'M41'],
              max: 15,
              yTicks: const [0, 5, 10, 15],
              height: 130,
            ),
            gap8,
            Legend([for (final i in shown) (_divLines[i].$1, _divLines[i].$2)]),
          ]),
        ),
        const Overline('PER HARI KERJA'),
        AppCard(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text('Senin paling rawan, 13 kejadian rata-rata', style: T.title),
            gap12,
            const Bars([13, 8, 7, 5.3, 9.3], ['Sen', 'Sel', 'Rab', 'Kam', 'Jum'], highlight: 0, valueLabels: ['13,0', '8,0', '7,0', '5,3', '9,3'], height: 100),
          ]),
        ),
        const Overline('JAM CLOCK-IN SAAT TERLAMBAT'),
        AppCard(
          child: Column(children: [
            for (final (l, n, p) in const [('08.01 sampai 08.05', 38, .21), ('08.06 sampai 08.15', 61, .34), ('08.16 sampai 08.30', 47, .26), ('08.31 sampai 09.00', 22, .12), ('Setelah 09.00', 11, .06)])
              barRow(l, p / .34, '$n', sub: '${(p * 100).round()}%', color: p > .3 ? C.warning : C.primary),
          ]),
        ),
        const Overline('PALING SERING TERLAMBAT'),
        AppCard(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
          child: Divided([
            for (final (i, (n, m)) in const [('Maya Anggraini', 142), ('Fajar Nugroho', 96), ('Agus Setiawan', 71), ('Budi Santoso', 54), ('Lina Marlina', 38)].indexed)
              InkWell(
                onTap: () => push(context, EmployeeDetail(personByName(n)), root: true),
                child: Padding(
                  padding: const EdgeInsets.symmetric(vertical: 8),
                  child: Row(children: [
                    SizedBox(width: 22, child: Text('${i + 1}', style: T.numS.copyWith(color: C.text3))),
                    Avatar(personByName(n).initials, size: 36),
                    gap12,
                    Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(n, style: T.title), Text(personByName(n).division, style: T.small)])),
                    Text('$m menit', style: T.numS.copyWith(color: i == 0 ? C.dangerText : C.text)),
                    gap8,
                    const Icon(LucideIcons.chevronRight, size: 18, color: C.text3),
                  ]),
                ),
              ),
          ]),
        ),
        gap16,
        Row(children: [
          Expanded(child: Btn('Bagikan', kind: BtnKind.light, icon: LucideIcons.share2, onTap: () => toast(context, 'Tautan laporan disalin.', aboveNav: true))),
          gap12,
          Expanded(child: Btn('Ekspor', icon: LucideIcons.download, onTap: () => push(context, const Reports(), root: true))),
        ]),
      ],
    );
  }
}

// ---------------------------------------------------------------- C04 distribusi jam kerja

class WorkHours extends StatefulWidget {
  const WorkHours({super.key});
  @override
  State<WorkHours> createState() => _WorkHoursState();
}

class _WorkHoursState extends State<WorkHours> {
  int tab = 0;
  final burnout = [('Hendra Kusuma', .78, 'Lembur 26 jam bulan ini, 4 akhir pekan berturut-turut'), ('Dewi Lestari', .61, 'Pulang setelah 19.00 selama 9 hari'), ('Putri Ramadhani', .52, 'Jam kerja naik 22% dalam 3 minggu')];

  @override
  Widget build(BuildContext context) => Screen(
        inShell: true,
        header: const PageHeader('Distribusi Jam Kerja', subtitle: '30 hari terakhir · 202 karyawan', back: true),
        children: [
          AppCard(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Row(children: [
                Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text('Rata-rata jam kerja', style: T.caption), Text('8,2 jam', style: T.numL)])),
                const Pill('Target 8 jam', icon: LucideIcons.target, tone: Tone.neutral),
              ]),
              gap16,
              const Bars([4, 14, 58, 94, 20, 9, 3], ['< 6', '6-7', '7-8', '8-9', '9-10', '10-11', '> 11'],
                  valueLabels: ['4', '14', '58', '94', '20', '9', '3'], colors: [C.warning, C.warning, C.accent, C.accent, C.danger, C.danger, C.danger], height: 110),
              gap8,
              Row(children: [_stat('152', 'Normal', color: C.accent), _stat('43', 'Ringan', color: C.warningText), _stat('7', 'Ekstrem', color: C.dangerText)]),
            ]),
          ),
          const Overline('RATA-RATA PER DIVISI'),
          AppCard(
            child: Column(children: [
              for (final (d, h) in const [('Penjualan', 9.1), ('Operasional', 8.6), ('Keuangan', 8.1), ('IT', 8.0), ('HR & GA', 7.9)])
                barRow(d, h / 10, '${h.toStringAsFixed(1).replaceAll('.', ',')} j', color: h > 9 ? C.warning : C.primary),
            ]),
          ),
          const Overline('JAM MASUK DAN PULANG'),
          Row(children: [
            for (final (i, (l, v, s)) in const [('Paling banyak masuk', '07.45 sampai 08.00', '41% · rata-rata 07.52'), ('Paling banyak pulang', '17.00 sampai 17.15', '38% · rata-rata 17.21')].indexed) ...[
              if (i > 0) gap12,
              Expanded(
                child: AppCard(
                  radius: 20,
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Icon(i == 0 ? LucideIcons.logIn : LucideIcons.logOut, size: 18, color: C.primary),
                    gap8,
                    Text(l, style: T.small),
                    Text(v, style: T.title),
                    Text(s, style: T.small),
                  ]),
                ),
              ),
            ],
          ]),
          gap12,
          const Banner2('23 orang pulang setelah 19.00', body: 'Paling banyak di Penjualan dan Gudang Kalasan minggu ini.', tone: Tone.info, icon: LucideIcons.moon),
          gap16,
          Segmented(const ['Lembur berlebih (3)', 'Kurang jam (4)'], tab, (i) => setState(() => tab = i)),
          gap12,
          AppCard(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
            child: Divided([
              for (final (n, v, s) in tab == 0
                  ? const [('Hendra Kusuma', '11,2 j/hari', 'Lembur 26 jam'), ('Budi Santoso', '10,4 j/hari', 'Lembur 21 jam'), ('Dewi Lestari', '10,1 j/hari', 'Lembur 18 jam')]
                  : const [('Fajar Nugroho', '6,1 j/hari', '2 absen, 4 terlambat'), ('Agus Setiawan', '6,8 j/hari', 'Sering pulang 16.00'), ('Putra Wijaya', '6,9 j/hari', 'Cuti 3 hari'), ('Maya Anggraini', '7,0 j/hari', '5 terlambat')])
                InkWell(
                  onTap: () => push(context, EmployeeDetail(personByName(n)), root: true),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(vertical: 8),
                    child: Row(children: [
                      Avatar(personByName(n).initials, size: 36),
                      gap12,
                      Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(n, style: T.title), Text(s, style: T.small)])),
                      Text(v, style: T.numS.copyWith(color: tab == 0 ? C.dangerText : C.warningText)),
                    ]),
                  ),
                ),
            ]),
          ),
          const Overline('RISIKO BURNOUT [DL]'),
          if (burnout.isNotEmpty) ...[
            AppCard(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Row(children: [
                  const IconTile(LucideIcons.sparkles, tone: Tone.insight, size: 36),
                  gap12,
                  Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text('Faktor utama risiko', style: T.title), Text('${burnout.length} karyawan berisiko · Penjualan dan Gudang Kalasan', style: T.small)])),
                ]),
                gap12,
                for (final (f, v, s) in const [
                  ('Lembur berlebih', .82, '26 jam/bulan'),
                  ('Kerja di akhir pekan', .64, '4 minggu beruntun'),
                  ('Pulang di atas 19.00', .55, '9 hari'),
                  ('Lonjakan jam kerja', .41, '+22% / 3 minggu'),
                ])
                  barRow(f, v, '${(v * 100).round()}%', sub: s, color: v >= .7 ? C.danger : C.warning),
              ]),
            ),
            gap12,
          ],
          if (burnout.isEmpty) const EmptyState(icon: LucideIcons.heartPulse, title: 'Tidak ada risiko', message: 'Semua peringatan sudah ditinjau.'),
          for (final b in burnout)
            Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: AppCard(
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Row(children: [
                    Avatar(personByName(b.$1).initials, size: 40),
                    gap12,
                    Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(b.$1, style: T.title), Text(b.$3, style: T.small)])),
                    Text(b.$2.toStringAsFixed(2).replaceAll('.', ','), style: T.numM.copyWith(color: b.$2 >= .7 ? C.dangerText : C.warningText)),
                  ]),
                  gap8,
                  Bar(b.$2, color: b.$2 >= .7 ? C.danger : C.warning),
                  gap12,
                  Row(children: [
                    Expanded(
                      child: Btn('Abaikan', kind: BtnKind.light, height: 44, onTap: () {
                        final i = burnout.indexOf(b);
                        setState(() => burnout.remove(b));
                        toast(context, 'Peringatan ${b.$1} diabaikan', action: 'Batalkan', onAction: () => setState(() => burnout.insert(i, b)), aboveNav: true);
                      }),
                    ),
                    gap12,
                    Expanded(child: Btn('Lihat Detail', height: 44, onTap: () => push(context, EmployeeDetail(personByName(b.$1)), root: true))),
                  ]),
                ]),
              ),
            ),
        ],
      );
}

// ---------------------------------------------------------------- C11 insight

class Insights extends StatefulWidget {
  const Insights({super.key});
  @override
  State<Insights> createState() => _InsightsState();
}

class _InsightsState extends State<Insights> {
  final anomalies = [
    (LucideIcons.mapPinOff, 'Clock-in berulang dari titik yang sama', 'Fajar Nugroho · 4 kali dalam 2 minggu, koordinat persis sama', Tone.danger),
    (LucideIcons.users, 'Clock-in beruntun dalam 30 detik', 'Agus Setiawan dan Lina Marlina · perangkat berdekatan', Tone.warning),
    (LucideIcons.scanFace, 'Skor wajah turun tajam', 'Lina Marlina · dari 96% ke 81% dalam 5 hari', Tone.warning),
    (LucideIcons.clock, 'Pola terlambat setiap Senin', 'Penjualan · 6 orang, 4 minggu berturut-turut', Tone.insight),
  ];
  final done = <int>{};

  void ignore((IconData, String, String, Tone) a) {
    final i = anomalies.indexOf(a);
    setState(() => anomalies.remove(a));
    toast(context, 'Anomali diabaikan', action: 'Batalkan', onAction: () => setState(() => anomalies.insert(i, a)), aboveNav: true);
  }

  void detail((IconData, String, String, Tone) a) => showSheet(
        context,
        (ctx) => Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
          SheetHead('Detail anomali', subtitle: a.$2),
          Flexible(
            child: ListView(shrinkWrap: true, padding: const EdgeInsets.fromLTRB(20, 0, 20, 20), children: [
              const MapBox(height: 150, pins: [MapPin(Alignment(.25, -.15), C.danger, size: 14), MapPin(Alignment(.28, -.1), C.danger), MapPin(Alignment(-.3, .2), C.accent)]),
              gap12,
              Text(a.$3, style: T.body),
              const Overline('CLOCK-IN TERKAIT', padding: EdgeInsets.fromLTRB(0, 16, 0, 8)),
              for (final (d, t, p) in const [('Rab, 7 Okt', '08.47', '-7.7829, 110.3671'), ('Jum, 2 Okt', '08.52', '-7.7829, 110.3671'), ('Sel, 29 Sep', '08.44', '-7.7829, 110.3671'), ('Sen, 28 Sep', '08.58', '-7.7829, 110.3671')])
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 4),
                  child: Row(children: [
                    const Icon(LucideIcons.mapPin, size: 16, color: C.danger),
                    gap8,
                    Expanded(child: Text('$d · $t', style: T.bodyM)),
                    Text(p, style: T.small),
                  ]),
                ),
              gap16,
              Row(children: [
                Expanded(
                  child: Btn('Abaikan', kind: BtnKind.light, icon: LucideIcons.eyeOff, onTap: () {
                    Navigator.pop(ctx);
                    ignore(a);
                  }),
                ),
                gap12,
                Expanded(
                  flex: 2,
                  child: Btn('Minta Verifikasi', icon: LucideIcons.scanFace, onTap: () {
                    Navigator.pop(ctx);
                    toast(context, 'Permintaan verifikasi ulang dikirim.', aboveNav: true);
                  }),
                ),
              ]),
            ]),
          ),
        ]),
      );

  @override
  Widget build(BuildContext context) => Screen(
        inShell: true,
        header: const PageHeader('Insight & Prediksi', subtitle: 'Diperbarui 06.00 hari ini', back: true),
        children: [
          AppCard(
            color: C.primary,
            child: Row(children: [
              const IconTile(LucideIcons.brainCircuit, tone: Tone.sage, size: 48, round: true),
              gap12,
              Expanded(
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text('Model HadirMetrik v3.2 [DL]', style: T.title.copyWith(color: Colors.white)),
                  Text('Dilatih dari 14 bulan data absensi', style: T.small.copyWith(color: Colors.white70)),
                ]),
              ),
              Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
                Text('91,4%', style: T.numM.copyWith(color: Colors.white)),
                Text('akurasi', style: T.small.copyWith(color: Colors.white70)),
              ]),
            ]),
          ),
          const Overline('PREDIKSI KEHADIRAN MINGGU DEPAN'),
          AppCard(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('Senin diprediksi paling rendah, 84%', style: T.title),
              Text('Prakiraan hujan pagi dan efek akhir pekan panjang', style: T.small),
              gap12,
              const Bars([84, 89, 90, 91, 87], ['Sen 12', 'Sel 13', 'Rab 14', 'Kam 15', 'Jum 16'], highlight: 0, valueLabels: ['84%', '89%', '90%', '91%', '87%'], height: 100),
            ]),
          ),
          const Overline('KARYAWAN BERISIKO'),
          AppCard(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
            child: Divided([
              for (final (n, r, why) in const [('Fajar Nugroho', .82, 'Absen tanpa keterangan dan lokasi palsu'), ('Agus Setiawan', .74, 'Perangkat di-root, pola pulang awal'), ('Maya Anggraini', .63, 'Terlambat 5 kali bulan ini'), ('Lina Marlina', .47, 'Skor wajah turun')])
                InkWell(
                  onTap: () => push(context, EmployeeDetail(personByName(n)), root: true),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(vertical: 8),
                    child: Row(children: [
                      Avatar(personByName(n).initials, size: 36),
                      gap12,
                      Expanded(
                        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                          Text(n, style: T.title),
                          Text(why, style: T.small),
                          gap4,
                          Bar(r, color: r >= .7 ? C.danger : (r >= .5 ? C.warning : C.primary), height: 4),
                        ]),
                      ),
                      gap12,
                      Text(r.toStringAsFixed(2).replaceAll('.', ','), style: T.numS.copyWith(color: r >= .7 ? C.dangerText : C.text)),
                    ]),
                  ),
                ),
            ]),
          ),
          Overline('ANOMALI TERDETEKSI · ${anomalies.length}'),
          for (final a in anomalies)
            Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: AppCard(
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    IconTile(a.$1, tone: a.$4, size: 36, round: true),
                    gap12,
                    Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(a.$2, style: T.title), gap4, Text(a.$3, style: T.caption)])),
                  ]),
                  gap12,
                  Row(children: [
                    Expanded(
                      child: Btn('Abaikan', kind: BtnKind.light, height: 44, onTap: () => ignore(a)),
                    ),
                    gap12,
                    Expanded(child: Btn('Lihat Detail', height: 44, onTap: () => detail(a))),
                  ]),
                ]),
              ),
            ),
          Row(children: [
            const Expanded(child: Overline('REKOMENDASI TINDAKAN')),
            Padding(padding: const EdgeInsets.only(top: 10), child: Text('${done.length}/4 selesai', style: T.small)),
          ]),
          for (final (i, (icon, tone, prio, t, s, act)) in const [
            (LucideIcons.bellRing, Tone.insight, 'Prioritas sedang', 'Kirim pengingat berangkat lebih awal', 'Penjualan · setiap Senin 06.30', 'Jadwalkan'),
            (LucideIcons.messageCircle, Tone.danger, 'Prioritas tinggi', 'Jadwalkan pembicaraan dengan Fajar Nugroho', 'Minggu ini · skor risiko 0,82', 'Buat jadwal'),
            (LucideIcons.smartphone, Tone.danger, 'Prioritas tinggi', 'Reset perangkat Agus Setiawan', 'Perangkat di-root · minta registrasi ulang', 'Reset'),
            (LucideIcons.scanFace, Tone.warning, 'Prioritas rendah', 'Minta Lina Marlina daftar ulang wajah', 'Skor wajah 81% · di tempat terang', 'Kirim'),
          ].indexed)
            Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: AnimatedOpacity(
                duration: const Duration(milliseconds: 200),
                opacity: done.contains(i) ? .6 : 1,
                child: AppCard(
                  padding: const EdgeInsets.all(14),
                  radius: 20,
                  child: Row(children: [
                    IconTile(done.contains(i) ? LucideIcons.check : icon, tone: done.contains(i) ? Tone.success : tone, size: 40),
                    gap12,
                    Expanded(
                      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        Text(done.contains(i) ? 'Selesai' : prio, style: T.small.copyWith(color: done.contains(i) ? C.accent : tone.fg, fontWeight: FontWeight.w600)),
                        Text(t, style: T.title.copyWith(decoration: done.contains(i) ? TextDecoration.lineThrough : null)),
                        Text(s, style: T.small),
                      ]),
                    ),
                    gap8,
                    done.contains(i)
                        ? TextButton(onPressed: () => setState(() => done.remove(i)), child: Text('Urungkan', style: T.small.copyWith(color: C.primary, fontWeight: FontWeight.w600)))
                        : Btn(act, kind: BtnKind.soft, height: 36, expand: false, onTap: () {
                            setState(() => done.add(i));
                            toast(context, 'Tindakan "$t" ditandai selesai.', aboveNav: true);
                          }),
                  ]),
                ),
              ),
            ),
        ],
      );
}

// ---------------------------------------------------------------- C12 laporan

class Reports extends StatefulWidget {
  const Reports({super.key});
  @override
  State<Reports> createState() => _ReportsState();
}

class _ReportsState extends State<Reports> {
  int step = 0, type = 1, period = 1, format = 0, loc = 0;
  DateTimeRange? range;
  final divs = {0, 1};
  bool busy = false;

  static const types = [
    (LucideIcons.calendarCheck, 'Rekap kehadiran', 'Hadir, terlambat, absen per karyawan'),
    (LucideIcons.clock, 'Keterlambatan', 'Kejadian, durasi dan tren'),
    (LucideIcons.timer, 'Jam kerja & lembur', 'Total jam dan lembur per divisi'),
    (LucideIcons.fileText, 'Izin & cuti', 'Pengajuan dan sisa kuota'),
    (LucideIcons.clipboardCheck, 'Kinerja tugas', 'Ketepatan dan kualitas tugas'),
    (LucideIcons.wallet, 'Penggajian', 'Rekap gaji dan potongan'),
    (LucideIcons.shieldAlert, 'Keamanan absensi', 'Lokasi palsu dan perangkat'),
  ];
  static const periods = ['Agustus 2026', 'September 2026', 'Oktober 2026'];
  static const formats = ['PDF', 'Excel', 'CSV'];

  String get periodLabel => range != null ? fmtRange(range!) : periods[period];

  String get fileName => '${types[type].$2.split(' ').first}_${range != null ? '${range!.start.day}-${range!.end.day}${bulanPendek[range!.end.month - 1]}' : periods[period].substring(0, 3)}2026_${[for (final d in divs) divisions[d].split(' ').first].join('-')}.${['pdf', 'xlsx', 'csv'][format]}';

  Future<void> generate() async {
    setState(() => busy = true);
    await Future<void>.delayed(const Duration(milliseconds: 900));
    if (!mounted) return;
    setState(() => busy = false);
    showSheet(
      context,
      (ctx) => Column(mainAxisSize: MainAxisSize.min, children: [
        const SheetHead('Laporan siap'),
        Padding(
          padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
          child: Column(children: [
            AppCard(
              color: C.sageSoft,
              child: Row(children: [
                IconTile([LucideIcons.fileText, LucideIcons.sheet, LucideIcons.fileSpreadsheet][format], tone: Tone.success),
                gap12,
                Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(fileName, style: T.title), Text('${formats[format]} · 248 KB · baru saja', style: T.small)])),
              ]),
            ),
            gap20,
            Row(children: [
              Expanded(
                child: Btn('Bagikan', kind: BtnKind.light, icon: LucideIcons.share2, onTap: () {
                  Navigator.pop(ctx);
                  toast(context, 'Tautan laporan disalin.');
                }),
              ),
              gap12,
              Expanded(
                child: Btn('Unduh', icon: LucideIcons.download, onTap: () {
                  Navigator.pop(ctx);
                  toast(context, '$fileName diunduh.', icon: LucideIcons.download);
                }),
              ),
            ]),
          ]),
        ),
      ]),
    );
  }

  @override
  Widget build(BuildContext context) => Screen(
        header: Column(children: [
          TopBar('Laporan & Ekspor', onBack: step == 0 ? null : () => setState(() => step--)),
          Padding(
            padding: pagePad,
            child: Row(children: [
              for (var i = 0; i < 3; i++) ...[
                if (i > 0) gap8,
                Expanded(child: Bar(i <= step ? 1 : 0, height: 4)),
              ],
            ]),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 4),
            child: Row(children: [Text('LANGKAH ${step + 1} DARI 3 · ${['JENIS LAPORAN', 'FILTER', 'PRATINJAU'][step]}', style: T.overline)]),
          ),
        ]),
        bottom: BottomActions([
          if (step > 0) Btn('Kembali', kind: BtnKind.light, onTap: () => setState(() => step--)),
          step < 2 ? Btn('Lanjut', onTap: divs.isEmpty ? null : () => setState(() => step++)) : Btn('Buat Laporan', icon: LucideIcons.download, busy: busy, onTap: busy ? null : generate),
        ]),
        children: [
          gap8,
          ...switch (step) {
            0 => [
                for (final (i, (icon, t, d)) in types.indexed)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 8),
                    child: AppCard(
                      padding: const EdgeInsets.all(14),
                      radius: 20,
                      border: i == type ? C.primary : null,
                      onTap: () => setState(() => type = i),
                      child: Row(children: [
                        IconTile(icon),
                        gap12,
                        Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(t, style: T.title), Text(d, style: T.small)])),
                        RadioDot(i == type),
                      ]),
                    ),
                  ),
                const Overline('LAPORAN SEBELUMNYA'),
                AppCard(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                  child: Divided([
                    for (final (n, s) in const [('Rekap_Sep2026_Semua.xlsx', '1 Okt · Sari Wulandari'), ('Keterlambatan_Agu2026_Penjualan.pdf', '2 Sep · Sari Wulandari'), ('Penggajian_Agu2026.pdf', '26 Agu · Sari Wulandari')])
                      InkWell(
                        onTap: () => toast(context, '$n diunduh.', icon: LucideIcons.download),
                        child: Padding(
                          padding: const EdgeInsets.symmetric(vertical: 10),
                          child: Row(children: [
                            const Icon(LucideIcons.fileText, size: 18, color: C.primary),
                            gap12,
                            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(n, style: T.bodyM, maxLines: 1, overflow: TextOverflow.ellipsis), Text(s, style: T.small)])),
                            const Icon(LucideIcons.download, size: 16, color: C.text3),
                          ]),
                        ),
                      ),
                  ]),
                ),
              ],
            1 => [
                AppCard(
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Text('Periode', style: T.title),
                    gap8,
                    ChipWrap([...periods, 'Rentang tanggal'], (i) => range == null ? i == period : i == 3, (i) async {
                      if (i < 3) {
                        setState(() {
                          period = i;
                          range = null;
                        });
                        return;
                      }
                      final r = await pickRange(context, initial: range);
                      if (r != null) setState(() => range = r);
                    }),
                    if (range != null) ...[
                      gap8,
                      PickerBox(icon: LucideIcons.calendarRange, small: 'Rentang tanggal', value: fmtRange(range!), trailing: LucideIcons.pencil, onTap: () async {
                        final r = await pickRange(context, initial: range);
                        if (r != null) setState(() => range = r);
                      }),
                    ],
                    gap16,
                    Text('Divisi', style: T.title),
                    gap8,
                    ChipWrap(divisions, divs.contains, (i) => setState(() => divs.contains(i) ? divs.remove(i) : divs.add(i))),
                    if (divs.isEmpty) Padding(padding: const EdgeInsets.only(top: 8), child: Text('Pilih minimal satu divisi', style: T.small.copyWith(color: C.dangerText))),
                    gap16,
                    Text('Lokasi', style: T.title),
                    gap8,
                    ChipWrap(const ['Semua lokasi', 'Kantor Pusat Yogyakarta', 'Gudang Kalasan'], (i) => i == loc, (i) => setState(() => loc = i)),
                  ]),
                ),
                gap12,
                ToggleTile('Sertakan grafik', true, (_) {}, subtitle: 'Tren mingguan dan per divisi'),
                ToggleTile('Sertakan rincian per karyawan', true, (_) {}),
              ],
            _ => [
                AppCard(
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Text(types[type].$2, style: T.h3),
                    Text('$periodLabel · ${[for (final d in divs) divisions[d]].join(', ')}', style: T.caption),
                    gap16,
                    Row(children: [_stat('118', 'kejadian'), _stat('14 m', 'rata-rata'), _stat('34', 'karyawan')]),
                    gap16,
                    const Bars([31, 36, 28, 23], ['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4'], highlight: 1, valueLabels: ['31', '36', '28', '23'], height: 90),
                  ]),
                ),
                const Overline('FORMAT'),
                Segmented(formats, format, (i) => setState(() => format = i)),
                gap12,
                Text('Nama file: $fileName', style: T.small),
              ],
          },
        ],
      );
}

// ---------------------------------------------------------------- lainnya

class More extends StatelessWidget {
  const More({super.key});

  @override
  Widget build(BuildContext context) => Screen(
        inShell: true,
        header: const PageHeader('Lainnya'),
        children: [
          AppCard(
            child: Row(children: [
              Avatar(sari.initials, size: 56),
              gap12,
              Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(sari.name, style: T.h3), Text('${sari.role} · PT Sinar Metrik Nusantara', style: T.caption)])),
              const Pill('Admin', icon: LucideIcons.shieldCheck),
            ]),
          ),
          const Overline('PEMANTAUAN'),
          AppCard(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
            child: Column(children: [
              MenuRow(LucideIcons.mapPinned, 'Pantau Lokasi', subtitle: 'Peta clock-in dan lokasi palsu', tone: Tone.danger, onTap: () => push(context, const LiveMonitor(), root: true)),
              MenuRow(LucideIcons.sparkles, 'Insight AI', subtitle: 'Prediksi dan anomali [DL]', tone: Tone.insight, onTap: () => push(context, const Insights())),
              MenuRow(LucideIcons.trendingUp, 'Tren Keterlambatan', onTap: () => push(context, const LateTrend())),
              MenuRow(LucideIcons.timer, 'Distribusi Jam Kerja', onTap: () => push(context, const WorkHours())),
            ]),
          ),
          const Overline('ADMINISTRASI'),
          AppCard(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
            child: Column(children: [
              MenuRow(LucideIcons.wallet, 'Penggajian', subtitle: 'Periode Oktober 2026', tone: Tone.success, onTap: () => push(context, const Payroll())),
              MenuRow(LucideIcons.fileDown, 'Laporan & Ekspor', onTap: () => push(context, const Reports(), root: true)),
              MenuRow(LucideIcons.settings, 'Pengaturan Perusahaan', onTap: () => push(context, const Settings())),
            ]),
          ),
          gap12,
          AppCard(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
            child: MenuRow(LucideIcons.logOut, 'Keluar', danger: true, onTap: () async {
              final ok = await confirm(context, title: 'Keluar dari HadirMetrik?', message: 'Kamu perlu masuk lagi untuk membuka dashboard HR.', action: 'Keluar', icon: LucideIcons.logOut, danger: true);
              if (!ok || !context.mounted) return;
              app.logout();
              resetTo(context, const Login());
            }),
          ),
          gap16,
          Center(child: Text('HadirMetrik versi 1.0.0', style: T.small)),
        ],
      );
}

// ---------------------------------------------------------------- C13 pengaturan

const _settings = [
  (LucideIcons.clock, 'Jam kerja & shift', '3 shift · toleransi 5 menit'),
  (LucideIcons.mapPin, 'Lokasi kantor', '2 lokasi · radius 100 m'),
  (LucideIcons.shieldCheck, 'Keamanan absensi', 'Wajah, liveness, anti lokasi palsu'),
  (LucideIcons.wallet, 'Aturan penggajian', 'Potongan, lembur, tanggal gajian'),
  (LucideIcons.fileText, 'Kebijakan cuti', '5 jenis pengajuan'),
  (LucideIcons.calendarDays, 'Hari libur', '16 hari libur nasional 2026'),
];

class Settings extends StatelessWidget {
  const Settings({super.key});
  @override
  Widget build(BuildContext context) => Screen(
        inShell: true,
        header: const PageHeader('Pengaturan Perusahaan', subtitle: 'PT Sinar Metrik Nusantara', back: true),
        children: [
          AppCard(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
            child: Column(children: [
              for (final (i, (icon, t, s)) in _settings.indexed) MenuRow(icon, t, subtitle: s, onTap: () => push(context, SettingsDetail(i), root: true)),
            ]),
          ),
          gap12,
          const Banner2('Perubahan berlaku besok', body: 'Aturan baru dipakai mulai shift berikutnya dan tercatat di log audit.', tone: Tone.info, icon: LucideIcons.info),
        ],
      );
}

class SettingsDetail extends StatefulWidget {
  const SettingsDetail(this.i, {super.key});
  final int i;
  @override
  State<SettingsDetail> createState() => _SettingsDetailState();
}

class _SettingsDetailState extends State<SettingsDetail> {
  int tolerance = 1, loc = 0;
  double radius = 100, face = .80, gps = 30;
  final toggles = <String, bool>{};

  Widget tog(String t, {String? s, bool init = true, bool dl = false}) => ToggleTile(t, toggles[t] ?? init, (v) => setState(() => toggles[t] = v), subtitle: s, dl: dl);

  Widget slider(String label, double v, double min, double max, String Function(double) fmt, ValueChanged<double> on, {int? div}) => AppCard(
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [Expanded(child: Text(label, style: T.title)), Text(fmt(v), style: T.numS.copyWith(color: C.primary))]),
          Slider(value: v, min: min, max: max, divisions: div, onChanged: (x) => setState(() => on(x))),
          Row(children: [Text(fmt(min), style: T.small), const Spacer(), Text(fmt(max), style: T.small)]),
        ]),
      );

  @override
  Widget build(BuildContext context) {
    final (_, title, _) = _settings[widget.i];
    return Screen(
      header: TopBar(title),
      bottom: BottomActions([
        Btn('Simpan Perubahan', onTap: () {
          Navigator.pop(context);
          toast(context, '$title disimpan. Berlaku mulai besok.');
        }),
      ]),
      children: [
        gap8,
        ...switch (widget.i) {
          0 => [
              for (final (n, t, c) in const [('Pagi', '08.00 sampai 17.00', '168 orang'), ('Siang', '13.00 sampai 22.00', '31 orang'), ('Malam', '21.00 sampai 06.00', '15 orang')])
                Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: AppCard(
                    padding: const EdgeInsets.all(14),
                    radius: 20,
                    child: Row(children: [
                      const IconTile(LucideIcons.clock),
                      gap12,
                      Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text('Shift $n', style: T.title), Text(t, style: T.small)])),
                      Text(c, style: T.caption),
                    ]),
                  ),
                ),
              const Overline('TOLERANSI KETERLAMBATAN'),
              ChipWrap(const ['0 menit', '5 menit', '10 menit', '15 menit'], (i) => i == tolerance, (i) => setState(() => tolerance = i)),
              gap16,
              tog('Clock-in paling awal 60 menit', s: 'Sebelum shift dimulai'),
              tog('Clock-out otomatis', s: 'Jika lupa, dicatat 4 jam setelah shift'),
              tog('Istirahat tidak dihitung', s: '12.00 sampai 13.00', init: false),
            ],
          1 => [
              MapBox(height: 200, fenceSize: .2 + radius / 600, center: loc == 0 ? Alignment.center : const Alignment(.2, -.1)),
              gap12,
              slider('Radius geofence', radius, 20, 200, (v) => '${v.round()} m', (x) => radius = x, div: 18),
              const Overline('LOKASI'),
              for (final (i, (n, a)) in const [('Kantor Pusat Yogyakarta', 'Jl. Magelang Km 5, Sleman'), ('Gudang Kalasan', 'Jl. Solo Km 12, Kalasan')].indexed)
                Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: AppCard(
                    padding: const EdgeInsets.all(14),
                    radius: 20,
                    border: i == loc ? C.primary : null,
                    onTap: () => setState(() => loc = i),
                    child: Row(children: [
                      const IconTile(LucideIcons.mapPin),
                      gap12,
                      Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(n, style: T.title), Text(a, style: T.small)])),
                      RadioDot(i == loc),
                    ]),
                  ),
                ),
              Btn('Tambah lokasi', kind: BtnKind.soft, icon: LucideIcons.plus, onTap: () => toast(context, 'Tandai titik di peta untuk menambah lokasi.')),
            ],
          2 => [
              tog('Verifikasi wajah', s: 'Wajib setiap clock-in dan clock-out', dl: true),
              tog('Uji liveness', s: 'Kedip atau toleh kepala', dl: true),
              tog('Blokir lokasi palsu', s: 'Fake GPS, mock location'),
              tog('Blokir perangkat root atau emulator'),
              tog('Satu akun satu perangkat'),
              gap12,
              slider('Ambang kecocokan wajah', face, .6, .99, (v) => v.toStringAsFixed(2).replaceAll('.', ','), (x) => face = x),
              gap12,
              slider('Akurasi GPS minimal', gps, 10, 100, (v) => '${v.round()} m', (x) => gps = x, div: 9),
            ],
          3 => [
              const Overline('JADWAL', padding: EdgeInsets.fromLTRB(4, 0, 4, 10)),
              const AppCard(
                child: Field(label: 'Tanggal gajian', icon: LucideIcons.calendar, initial: '25', keyboard: TextInputType.number, helper: 'Tanggal 1 sampai 28 setiap bulan'),
              ),
              for (final (title, rows) in const [
                ('POTONGAN', [
                  ('Potongan keterlambatan', '25000', 'Rp per kejadian di atas toleransi', LucideIcons.clock),
                  ('Absen tanpa keterangan', '150000', 'Rp per hari', LucideIcons.userX),
                  ('Pulang lebih awal', '15000', 'Rp per kejadian', LucideIcons.logOut),
                ]),
                ('TUNJANGAN DAN LEMBUR', [
                  ('Tarif lembur', '40000', 'Rp per jam', LucideIcons.timer),
                  ('Tunjangan transport', '25000', 'Rp per hari hadir', LucideIcons.bus),
                  ('Tunjangan makan', '20000', 'Rp per hari hadir', LucideIcons.utensils),
                ]),
              ]) ...[
                Overline(title),
                AppCard(
                  child: Column(children: [
                    for (final (i, (l, v, h, ic)) in rows.indexed) ...[
                      if (i > 0) gap12,
                      Field(label: l, icon: ic, initial: v, keyboard: TextInputType.number, helper: h),
                    ],
                  ]),
                ),
              ],
              gap12,
              tog('Potong otomatis keterlambatan', s: 'Di atas toleransi'),
              tog('Lembur perlu persetujuan atasan'),
              tog('Kirim slip lewat email', init: false),
            ],
          4 => [
              for (final t in LeaveType.values)
                Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: AppCard(
                    padding: const EdgeInsets.all(14),
                    radius: 20,
                    child: Row(children: [
                      IconTile(t.icon, tone: t.tone),
                      gap12,
                      Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(t.label, style: T.title), Text(t.desc, style: T.small)])),
                      Text(switch (t) { LeaveType.tahunan => '12 hari', LeaveType.izin => '2x/bulan', LeaveType.khusus => '3 hari', _ => 'Bebas' }, style: T.caption),
                    ]),
                  ),
                ),
              const Overline('ATURAN'),
              tog('Persetujuan dua tingkat', s: 'Atasan lalu HR'),
              tog('Cegah cuti bentrok', s: 'Maksimal 2 orang per divisi'),
              tog('Sisa cuti hangus akhir tahun', init: false),
            ],
          _ => [
              AppCard(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                child: Divided([
                  for (final (d, n) in const [('Sel, 20 Okt', 'Cuti bersama'), ('Rab, 25 Des', 'Hari Raya Natal'), ('Kam, 1 Jan 2027', 'Tahun Baru Masehi'), ('Jum, 16 Jan 2027', 'Isra Mikraj')])
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 10),
                      child: Row(children: [
                        const Icon(LucideIcons.calendarDays, size: 18, color: C.danger),
                        gap12,
                        Expanded(child: Text(n, style: T.bodyM)),
                        Text(d, style: T.caption),
                      ]),
                    ),
                ]),
              ),
              gap12,
              Btn('Tambah hari libur', kind: BtnKind.soft, icon: LucideIcons.plus, onTap: () => toast(context, 'Pilih tanggal untuk ditambahkan.')),
            ],
        },
      ],
    );
  }
}

// ---------------------------------------------------------------- C10 penggajian

class Payroll extends StatefulWidget {
  const Payroll({super.key});
  @override
  State<Payroll> createState() => _PayrollState();
}

const _payQs = [
  ('Budi Santoso', 'Potongan', 'Potongan keterlambatan 3 kali, padahal 1 hari sudah izin ke atasan.', '2 hari lalu'),
  ('Rina Pratiwi', 'Lembur', 'Lembur Sabtu 3 Okt belum masuk hitungan, padahal sudah disetujui Pak Dimas.', '1 hari lalu'),
  ('Dewi Lestari', 'Tunjangan', 'Tunjangan makan bulan ini kurang 2 hari.', '1 hari lalu'),
  ('Agus Setiawan', 'BPJS', 'Kenapa potongan BPJS naik dibanding September?', '5 jam lalu'),
];
// ponytail: answered state lives in memory for the prototype, move to AppState when it needs to survive reloads.
final _payAnswered = <int>{};

class _PayrollState extends State<Payroll> {
  int chip = 0, tab = 0, when = 0;
  final channels = {0, 1};

  void answer(int i) {
    final (n, topic, q, ago) = _payQs[i];
    final msg = TextEditingController();
    String? err;
    showSheet(
      context,
      (ctx) => StatefulBuilder(
        builder: (ctx, set) => Padding(
          padding: EdgeInsets.only(bottom: MediaQuery.viewInsetsOf(ctx).bottom),
          child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
            SheetHead('Pertanyaan ${n.split(' ').first}', subtitle: 'Topik: $topic · $ago'),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
              child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                AppCard(color: C.sageSoft, child: Text(q, style: T.body)),
                gap12,
                Field(label: 'Balasan', hint: 'Tulis jawaban untuk karyawan', maxLines: 3, controller: msg, error: err),
                gap16,
                Btn('Kirim Balasan', icon: LucideIcons.send, onTap: () {
                  if (msg.text.trim().isEmpty) {
                    set(() => err = 'Balasan belum diisi');
                    return;
                  }
                  setState(() => _payAnswered.add(i));
                  Navigator.pop(ctx);
                  toast(context, 'Balasan terkirim ke $n.', aboveNav: true);
                }),
              ]),
            ),
          ]),
        ),
      ),
    );
  }

  List<Widget> questions() => [
        for (final (i, (n, topic, q, ago)) in _payQs.indexed)
          Padding(
            padding: const EdgeInsets.only(bottom: 10),
            child: AppCard(
              padding: const EdgeInsets.all(14),
              radius: 20,
              onTap: _payAnswered.contains(i) ? null : () => answer(i),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Row(children: [
                  Avatar(personByName(n).initials, size: 40),
                  gap12,
                  Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(n, style: T.title), Text('$topic · $ago', style: T.small)])),
                  _payAnswered.contains(i) ? const Pill('Dijawab', icon: LucideIcons.check) : const Pill('Menunggu', tone: Tone.warning),
                ]),
                gap8,
                Text(q, style: T.body),
                if (!_payAnswered.contains(i)) ...[
                  gap12,
                  Row(children: [
                    if (app.payroll.any((r) => r.who.name == n)) ...[
                      Expanded(child: Btn('Lihat Gaji', kind: BtnKind.light, height: 44, onTap: () => push(context, PayrollDetail(app.payroll.firstWhere((r) => r.who.name == n)), root: true))),
                      gap12,
                    ],
                    Expanded(child: Btn('Balas', icon: LucideIcons.reply, height: 44, onTap: () => answer(i))),
                  ]),
                ],
              ]),
            ),
          ),
      ];

  List<Widget> publication(int left) {
    final done = app.payrollStep == 3;
    final open = _payQs.length - _payAnswered.length;
    return [
      AppCard(
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text('Cek sebelum publikasi', style: T.title),
          gap8,
          for (final (ok, l, s) in [
            (left == 0, 'Semua karyawan ditinjau', left == 0 ? 'Semua sudah ditinjau' : '$left belum ditinjau'),
            (open == 0, 'Pertanyaan gaji dijawab', open == 0 ? 'Semua sudah dijawab' : '$open belum dijawab · tidak wajib'),
            (true, 'Penyesuaian tercatat di log audit', '3 penyesuaian bulan ini'),
          ])
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 6),
              child: Row(children: [
                Icon(ok ? LucideIcons.circleCheck : LucideIcons.circleAlert, size: 20, color: ok ? C.accent : C.warning),
                gap12,
                Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(l, style: T.bodyM), Text(s, style: T.small)])),
              ]),
            ),
          if (left > 0 && !done) ...[
            gap8,
            Btn('Tandai Semua Ditinjau', kind: BtnKind.soft, icon: LucideIcons.checkCheck, height: 44, onTap: () {
              for (final r in app.payroll) {
                r.reviewed = true;
              }
              app.touch();
              toast(context, 'Semua gaji ditandai ditinjau.', aboveNav: true);
            }),
          ],
        ]),
      ),
      const Overline('JADWAL'),
      ChipWrap(const ['Sekarang', 'Besok 08.00', 'Tanggal gajian 25 Okt'], (i) => i == when, (i) => done ? null : setState(() => when = i)),
      const Overline('KIRIM LEWAT'),
      AppCard(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
        child: Column(children: [
          for (final (i, (icon, l)) in const [(LucideIcons.bell, 'Notifikasi aplikasi'), (LucideIcons.mail, 'Email kantor'), (LucideIcons.messageCircle, 'WhatsApp')].indexed)
            InkWell(
              onTap: done ? null : () => setState(() => channels.contains(i) ? channels.remove(i) : channels.add(i)),
              child: Padding(
                padding: const EdgeInsets.symmetric(vertical: 10),
                child: Row(children: [Icon(icon, size: 18, color: C.text2), gap12, Expanded(child: Text(l, style: T.bodyM)), CheckBox2(channels.contains(i))]),
              ),
            ),
        ]),
      ),
      gap16,
      Btn(
        done ? 'Sudah Dipublikasikan' : (when == 0 ? 'Publikasikan Sekarang' : 'Jadwalkan Publikasi'),
        icon: LucideIcons.send,
        onTap: left == 0 && !done && channels.isNotEmpty ? publish : null,
      ),
      if (!done && (left > 0 || channels.isEmpty))
        Padding(padding: const EdgeInsets.only(top: 8), child: Center(child: Text(left > 0 ? 'Tinjau $left karyawan lagi untuk publikasi' : 'Pilih minimal satu kanal', style: T.small))),
    ];
  }

  Future<void> publish() async {
    final ok = await confirm(context, title: 'Publikasikan slip gaji?', message: 'Slip Oktober 2026 akan terkirim ke 214 karyawan dan tidak bisa diubah lagi.', action: 'Publikasikan');
    if (!ok || !mounted) return;
    app.payrollStep = 3;
    app.notifs.insert(0, Notif(NCat.gaji, 'Slip gaji Oktober 2026 terbit', 'Take-home Rp 6.697.500 · dibayar 23 Okt', hm(app.now), 'HARI INI'));
    app.touch();
    toast(context, 'Slip gaji dipublikasikan ke 214 karyawan', action: 'Lihat', onAction: () => push(context, const Slip(payOct), root: true), aboveNav: true);
  }

  @override
  Widget build(BuildContext context) => ListenableBuilder(
        listenable: app,
        builder: (context, _) {
          final rows = app.payroll.where((r) => switch (chip) { 1 => r.flags.isNotEmpty, 2 => r.flags.any((f) => f.$1 == 'Ada penyesuaian'), _ => true }).toList();
          final left = app.payroll.where((r) => !r.reviewed).length;
          final step = app.payrollStep == 3 ? 3 : (left == 0 ? 2 : 1);
          return Screen(
            inShell: true,
            header: PageHeader('Penggajian', subtitle: 'Periode Oktober 2026', back: true, actions: [
              CircleBtn(LucideIcons.download, tooltip: 'Ekspor', onTap: () => toast(context, 'Penggajian_Okt2026.xlsx diunduh.', aboveNav: true, icon: LucideIcons.download)),
            ]),
            children: [
              AppCard(
                child: Row(children: [
                  for (final (i, l) in const ['Draft', 'Ditinjau', 'Disetujui', 'Dipublikasikan'].indexed) ...[
                    Expanded(
                      child: Column(children: [
                        AnimatedContainer(
                          duration: const Duration(milliseconds: 250),
                          width: 28,
                          height: 28,
                          decoration: BoxDecoration(color: i <= step ? C.primary : C.bg, shape: BoxShape.circle),
                          child: Icon(i < step ? LucideIcons.check : LucideIcons.circle, size: 14, color: i <= step ? Colors.white : C.text3),
                        ),
                        gap4,
                        Text(l, style: T.small.copyWith(color: i <= step ? C.text : C.text3, fontWeight: i == step ? FontWeight.w700 : null)),
                      ]),
                    ),
                  ],
                ]),
              ),
              gap12,
              AppCard(
                color: C.primary,
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text('Total take-home', style: T.caption.copyWith(color: Colors.white70)),
                  Text('Rp 1.122.335.000', style: T.numL.copyWith(color: Colors.white)),
                  Row(children: [const Icon(LucideIcons.trendingUp, size: 14, color: C.mid), gap4, Text('Naik 1,8% dari September', style: T.small.copyWith(color: C.mid))]),
                  gap16,
                  Row(children: [
                    for (final (l, v) in const [('Bruto', 'Rp 1,28 M'), ('Lembur', 'Rp 38,4 jt'), ('Potongan', 'Rp 162,3 jt'), ('Karyawan', '214')])
                      Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(l, style: T.small.copyWith(color: Colors.white70)), Text(v, style: T.title.copyWith(color: Colors.white))])),
                  ]),
                ]),
              ),
              gap12,
              if (app.payrollStep < 3)
                Banner2('$left karyawan perlu ditinjau', body: '12 karyawan ditandai: potongan keterlambatan, lembur besar, atau penyesuaian manual.', icon: LucideIcons.flag)
              else
                const Banner2('Slip gaji sudah dipublikasikan', body: 'Dibayar 23 Okt 2026 ke rekening masing-masing.', tone: Tone.success, icon: LucideIcons.circleCheck),
              gap12,
              Segmented(['Karyawan', 'Pertanyaan (${_payQs.length - _payAnswered.length})', 'Publikasi'], tab, (i) => setState(() => tab = i)),
              gap12,
              if (tab == 1) ...questions(),
              if (tab == 2) ...publication(left),
              if (tab == 0) ...[
                ChipBar(const ['Semua 214', 'Ditandai 12', 'Penyesuaian 3'], chip, (i) => setState(() => chip = i)),
                gap12,
              ],
              for (final r in tab == 0 ? rows : const <PayRow>[])
                Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: AppCard(
                    padding: const EdgeInsets.all(14),
                    radius: 20,
                    onTap: () => push(context, PayrollDetail(r), root: true),
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Row(children: [
                        Avatar(r.who.initials, size: 40),
                        gap12,
                        Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(r.who.name, style: T.title), Text('${r.who.role} · ${r.who.division}', style: T.small)])),
                        Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
                          Text(rp(r.takeHome), style: T.numS),
                          r.reviewed ? Text('Ditinjau', style: T.small.copyWith(color: C.accent)) : Text('Belum ditinjau', style: T.small.copyWith(color: C.warningText)),
                        ]),
                      ]),
                      if (r.flags.isNotEmpty) ...[
                        gap8,
                        Wrap(spacing: 6, runSpacing: 6, children: [for (final (l, t, i) in r.flags) Pill(l, icon: i, tone: t)]),
                      ],
                    ]),
                  ),
                ),
              if (tab == 0) ...[
                if (_payAnswered.length < _payQs.length)
                  Banner2('${_payQs.length - _payAnswered.length} pertanyaan gaji belum dijawab', body: 'Paling lama dari Budi Santoso, 2 hari lalu.', tone: Tone.info, icon: LucideIcons.messageCircleQuestionMark, onTap: () => setState(() => tab = 1)),
                gap16,
                Btn(app.payrollStep == 3 ? 'Sudah Dipublikasikan' : 'Lanjut ke Publikasi', icon: LucideIcons.send, onTap: app.payrollStep == 3 ? null : () => setState(() => tab = 2)),
              ],
            ],
          );
        },
      );
}

class PayrollDetail extends StatefulWidget {
  const PayrollDetail(this.r, {super.key});
  final PayRow r;
  @override
  State<PayrollDetail> createState() => _PayrollDetailState();
}

class _PayrollDetailState extends State<PayrollDetail> {
  late final isRina = widget.r.who == rina;
  late final adj = <(String, int)>[if (isRina) ('Koreksi lembur 3 Okt', 115000)];
  late final history = <(String, String, Tone?)>[
    ('Draft dibuat sistem', '1 Okt, 00.00', null),
    if (isRina) ('Penyesuaian +Rp 115.000 · Sari Wulandari', '6 Okt, 14.20', Tone.success),
    if (widget.r.reviewed) ('Ditinjau Sari Wulandari', '6 Okt, 15.02', Tone.success),
  ];

  void addAdjustment() {
    final amount = TextEditingController(), reason = TextEditingController();
    var minus = false;
    var comp = 0;
    String? err;
    const plus = ['Lembur', 'Bonus', 'Tunjangan', 'Koreksi'];
    const less = ['Keterlambatan', 'Absen', 'Pinjaman', 'Koreksi'];
    showSheet(
      context,
      (ctx) => StatefulBuilder(builder: (ctx, set) {
        final v = int.tryParse(amount.text.replaceAll('.', '')) ?? 0;
        final s = minus ? -v : v;
        return Padding(
          padding: EdgeInsets.only(bottom: MediaQuery.viewInsetsOf(ctx).bottom),
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            const SheetHead('Tambah penyesuaian'),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Row(children: [
                  for (final (i, (l, ic, t)) in const [('Tambah', LucideIcons.circlePlus, Tone.success), ('Kurangi', LucideIcons.circleMinus, Tone.danger)].indexed) ...[
                    if (i > 0) gap12,
                    Expanded(
                      child: AppCard(
                        radius: 16,
                        padding: const EdgeInsets.symmetric(vertical: 12),
                        color: (i == 1) == minus ? t.bg : Colors.white,
                        border: (i == 1) == minus ? t.fg : C.border,
                        onTap: () => set(() {
                          minus = i == 1;
                          comp = 0;
                        }),
                        child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                          Icon(ic, size: 18, color: (i == 1) == minus ? t.fg : C.text3),
                          gap8,
                          Text(l, style: T.title.copyWith(color: (i == 1) == minus ? t.fg : C.text2)),
                        ]),
                      ),
                    ),
                  ],
                ]),
                gap16,
                Text('Komponen', style: T.title),
                gap8,
                ChipWrap(minus ? less : plus, (i) => i == comp, (i) => set(() => comp = i)),
                gap16,
                Field(label: 'Jumlah', hint: '115000', icon: LucideIcons.banknote, controller: amount, keyboard: TextInputType.number, onChanged: (_) => set(() {})),
                gap12,
                Field(label: 'Alasan', hint: 'Wajib diisi, tercatat di log audit', controller: reason, maxLines: 2, error: err),
                gap12,
                AppCard(
                  color: minus ? C.dangerSubtle : C.accentSubtle,
                  padding: const EdgeInsets.all(12),
                  radius: 16,
                  child: Row(children: [
                    Expanded(child: Text('Take-home menjadi', style: T.caption)),
                    Text('${rp(s, sign: true)}  →  ${rp(widget.r.takeHome + s)}', style: T.numS.copyWith(color: minus ? C.dangerText : C.accent)),
                  ]),
                ),
                gap16,
                Btn(minus ? 'Simpan Pengurangan' : 'Simpan Penambahan', kind: minus ? BtnKind.danger : BtnKind.primary, onTap: () {
                  if (v <= 0 || reason.text.trim().length < 5) {
                    set(() => err = v <= 0 ? 'Jumlah belum diisi' : 'Alasan wajib diisi, minimal 5 karakter');
                    return;
                  }
                  setState(() {
                    adj.add(('${(minus ? less : plus)[comp]} · ${reason.text.trim()}', s));
                    widget.r.takeHome += s;
                    history.add(('Penyesuaian ${rp(s, sign: true)} · Sari Wulandari', 'Baru saja', minus ? Tone.danger : Tone.success));
                  });
                  app.touch();
                  Navigator.pop(ctx);
                  toast(context, 'Penyesuaian disimpan.');
                }),
              ]),
            ),
          ]),
        );
      }),
    );
  }

  void reply() => showSheet(
        context,
        (ctx) => Padding(
          padding: EdgeInsets.only(bottom: MediaQuery.viewInsetsOf(ctx).bottom),
          child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
            SheetHead('Pertanyaan ${widget.r.who.name.split(' ').first}', subtitle: 'Topik: Potongan · 2 hari lalu'),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                AppCard(color: C.sageSoft, child: Text('Lembur Sabtu 3 Okt belum masuk hitungan, padahal sudah disetujui Pak Dimas.', style: T.body)),
                gap12,
                const Field(label: 'Balasan', hint: 'Tulis jawaban untuk karyawan', maxLines: 3),
                gap16,
                Btn('Kirim Balasan', icon: LucideIcons.send, onTap: () {
                  Navigator.pop(ctx);
                  toast(context, 'Balasan terkirim ke ${widget.r.who.name}.');
                }),
              ]),
            ),
          ]),
        ),
      );

  @override
  Widget build(BuildContext context) {
    final r = widget.r;
    final base = isRina ? payOct : null;
    final gross = base?.gross ?? (r.takeHome * 1.08).round() ~/ 1000 * 1000;
    final cut = base?.cut ?? gross - r.takeHome;
    return Screen(
      header: const TopBar('Detail Gaji'),
      bottom: BottomActions([
        Btn('Balas Pertanyaan', kind: BtnKind.light, onTap: reply),
        Btn(r.reviewed ? 'Sudah Ditinjau' : 'Tandai Ditinjau', icon: LucideIcons.check, onTap: r.reviewed
            ? null
            : () {
                r.reviewed = true;
                app.touch();
                Navigator.pop(context);
                toast(context, 'Gaji ${r.who.name} ditandai ditinjau.');
              }),
      ]),
      children: [
        gap8,
        AppCard(
          child: Row(children: [
            Avatar(r.who.initials, size: 48),
            gap12,
            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(r.who.name, style: T.h3), Text('${r.who.id} · ${r.who.role}', style: T.caption)])),
          ]),
        ),
        gap12,
        AppCard(
          color: C.accentSubtle,
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text('Take-home Oktober 2026', style: T.caption.copyWith(color: C.accent)),
            Text(rp(r.takeHome), style: T.numL.copyWith(color: C.accent)),
          ]),
        ),
        if (r.flags.isNotEmpty) ...[
          const Overline('PERLU DIPERHATIKAN'),
          AppCard(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
            child: Divided([
              for (final (l, t, i) in r.flags)
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 8),
                  child: Row(children: [
                    IconTile(i, tone: t, size: 36, round: true),
                    gap12,
                    Expanded(child: Text(l, style: T.bodyM)),
                  ]),
                ),
            ]),
          ),
        ],
        const Overline('RINCIAN'),
        AppCard(
          child: Column(children: [
            if (base != null) ...[
              for (final l in base.earnings) InfoRow(l.label, num0(l.amount)),
              for (final l in base.deductions) InfoRow(l.label, '-${num0(l.amount)}', valueColor: C.dangerText),
            ] else ...[
              InfoRow('Total pendapatan', rp(gross)),
              InfoRow('Total potongan', '-${rp(cut)}', valueColor: C.dangerText),
            ],
            for (final (l, v) in adj) InfoRow('Penyesuaian', rp(v, sign: true), sub: l, valueColor: v >= 0 ? C.accent : C.dangerText),
            const Divider(height: 20),
            InfoRow('Take-home', rp(r.takeHome)),
          ]),
        ),
        gap12,
        Btn('Tambah penyesuaian', kind: BtnKind.soft, icon: LucideIcons.plus, onTap: addAdjustment),
        const Overline('RIWAYAT PERUBAHAN'),
        AppCard(child: Steps(history)),
      ],
    );
  }
}
