import 'package:flutter/material.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';

import '../api.dart';
import '../data.dart';
import '../main.dart';
import '../state.dart';
import '../theme.dart';
import '../visuals.dart';
import '../widgets.dart';
import 'auth.dart';
import 'leave.dart';
import 'tasks.dart';

String _money(int v) => app.hideSalary ? 'Rp •••••••' : rp(v);

// ---------------------------------------------------------------- B12 profil

class Profile extends StatelessWidget {
  const Profile({super.key});

  Future<void> _logout(BuildContext context) async {
    final ok = await confirm(
      context,
      title: 'Keluar dari HadirMetrik?',
      message: 'Kamu perlu masuk lagi dengan ID dan kata sandi. Riwayat absen tetap tersimpan.',
      action: 'Keluar',
      icon: LucideIcons.logOut,
      danger: true,
    );
    if (!ok || !context.mounted) return;
    try {
      await Api.logout();
    } catch (_) {
      Api.token = null;
    } finally {
      app.logout();
      if (context.mounted) resetTo(context, const Login());
    }
  }

  void _info(BuildContext context, String title, String body) => showSheet(
        context,
        (ctx) => Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
          SheetHead(title),
          Padding(padding: const EdgeInsets.fromLTRB(20, 0, 20, 20), child: Text(body, style: T.body.copyWith(height: 1.55))),
        ]),
      );

  void _password(BuildContext context) => showSheet(
        context,
        (ctx) => Column(mainAxisSize: MainAxisSize.min, children: [
          const SheetHead('Ubah kata sandi', subtitle: 'Minimal 8 karakter, campur huruf dan angka'),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
            child: Column(children: [
              const Field(label: 'Kata sandi lama', icon: LucideIcons.lock, obscure: true),
              gap12,
              const Field(label: 'Kata sandi baru', icon: LucideIcons.lockKeyhole, obscure: true),
              gap12,
              const Field(label: 'Ulangi kata sandi baru', icon: LucideIcons.lockKeyhole, obscure: true),
              gap20,
              Btn('Simpan', onTap: () {
                Navigator.pop(ctx);
                toast(context, 'Kata sandi diperbarui.', aboveNav: true);
              }),
            ]),
          ),
        ]),
      );

  void _notifSettings(BuildContext context) {
    final v = [true, true, true, false];
    showSheet(
      context,
      (ctx) => StatefulBuilder(
        builder: (ctx, set) => Column(mainAxisSize: MainAxisSize.min, children: [
          const SheetHead('Notifikasi'),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
            child: Column(children: [
              for (final (i, (t, s)) in const [
                ('Pengingat absen', 'Sebelum shift mulai dan selesai'),
                ('Tugas dan revisi', 'Tugas baru, komentar, revisi'),
                ('Gaji dan pengajuan', 'Slip terbit, status izin dan cuti'),
                ('Pengumuman perusahaan', 'Info dari HR'),
              ].indexed)
                ToggleTile(t, v[i], (x) => set(() => v[i] = x), subtitle: s),
            ]),
          ),
        ]),
      ),
    );
  }

  @override
  Widget build(BuildContext context) => Screen(
        inShell: true,
        header: const PageHeader('Profil'),
        children: [
          AppCard(
            child: Column(children: [
              Avatar(app.profile?.employee.initials ?? 'PK', size: 76, dot: C.accent),
              gap12,
              Text(app.profile?.employee.fullName ?? '-', style: T.h2),
              Text('${app.profile?.employee.jobTitle ?? '-'} · ${app.profile?.employee.division?.name ?? '-'}', style: T.caption),
              gap12,
              Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                Pill(app.profile?.employee.employeeNumber ?? '-', icon: LucideIcons.idCard, tone: Tone.neutral),
                gap8,
                const Pill('Terverifikasi', icon: LucideIcons.badgeCheck),
              ]),
            ]),
          ),
          gap12,
          Row(children: [
            for (final (icon, label, page) in [
              (LucideIcons.calendarDays, 'Riwayat', const History() as Widget),
              (LucideIcons.chartLine, 'Statistik', const Stats()),
              (LucideIcons.wallet, 'Gaji', const Pay()),
            ]) ...[
              if (label != 'Riwayat') gap12,
              Expanded(
                child: AppCard(
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  radius: 20,
                  onTap: () => push(context, page),
                  child: Column(children: [IconTile(icon, size: 36), gap8, Text(label, style: T.title)]),
                ),
              ),
            ],
          ]),
          const Overline('DATA WAJAH'),
          AppCard(
            child: Column(children: [
              Row(children: [
                const IconTile(LucideIcons.scanFace, tone: Tone.success, size: 48),
                gap12,
                Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text('Terdaftar · 5 sudut', style: T.title), Text('Skor kualitas 96 [DL] · Terenkripsi', style: T.caption)])),
                const Pill('Aktif', icon: LucideIcons.circleCheck),
              ]),
              gap16,
              Btn('Daftarkan Ulang Wajah', kind: BtnKind.soft, icon: LucideIcons.refreshCw, height: 48, onTap: () => push(context, const FaceTips(), root: true)),
            ]),
          ),
          const Overline('PERANGKAT & KERJA'),
          AppCard(
            child: Divided([
              Row(children: [
                const IconTile(LucideIcons.smartphone),
                gap12,
                Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text('Samsung Galaxy A54', style: T.title), Text('Terikat sejak 12 Jan 2026', style: T.caption)])),
                const Pill('Terikat', icon: LucideIcons.link),
              ]),
              const InfoRow('Shift', '08.00 sampai 17.00', icon: LucideIcons.clock),
              const InfoRow('Lokasi', 'Kantor Pusat Yogyakarta', icon: LucideIcons.mapPin),
              const InfoRow('Atasan', 'Dimas Ardiansyah', icon: LucideIcons.userCheck),
              const InfoRow('Rekening', 'BCA •••• 4417', icon: LucideIcons.landmark),
            ]),
          ),
          const Overline('PENGATURAN'),
          AppCard(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
            child: Column(children: [
              MenuRow(LucideIcons.keyRound, 'Ubah kata sandi', onTap: () => _password(context)),
              MenuRow(LucideIcons.bell, 'Notifikasi', subtitle: 'Pengingat, tugas, gaji', onTap: () => _notifSettings(context)),
              MenuRow(LucideIcons.languages, 'Bahasa', subtitle: 'Bahasa Indonesia', onTap: () => _info(context, 'Bahasa', 'HadirMetrik saat ini tersedia dalam Bahasa Indonesia. Bahasa lain menyusul.')),
              MenuRow(LucideIcons.lifeBuoy, 'Bantuan', onTap: () => _info(context, 'Bantuan', 'Ada kendala absen, gaji atau pengajuan? Hubungi HR di hr@sinarmetrik.co.id atau WhatsApp 0811 2900 4417, Senin sampai Jumat 08.00 sampai 17.00.')),
              MenuRow(LucideIcons.shieldCheck, 'Kebijakan Privasi', onTap: () => _info(context, 'Kebijakan Privasi', 'Foto wajah dan lokasi hanya diambil saat kamu absen, disimpan terenkripsi, dan hanya dipakai untuk verifikasi kehadiran. Data tidak dibagikan ke pihak lain di luar PT Sinar Metrik Nusantara.')),
              MenuRow(LucideIcons.logOut, 'Keluar', danger: true, onTap: () => _logout(context)),
            ]),
          ),
          gap16,
          Center(child: Text('HadirMetrik versi 1.0.0', style: T.small)),
        ],
      );
}

// ---------------------------------------------------------------- B08 gaji

class Pay extends StatefulWidget {
  const Pay({super.key});
  @override
  State<Pay> createState() => _PayState();
}

class _PayState extends State<Pay> {
  static const slips = [payOct, paySep, payAug, payJul];
  int i = 0;
  Payslip get p => slips[i];

  void period() => showSheet(
        context,
        (ctx) => Column(mainAxisSize: MainAxisSize.min, children: [
          const SheetHead('Pilih periode'),
          for (final (j, s) in slips.indexed)
            ListTile(
              contentPadding: const EdgeInsets.symmetric(horizontal: 20),
              title: Text(s.month, style: T.title),
              subtitle: Text(s.paid, style: T.caption),
              trailing: RadioDot(j == i),
              onTap: () {
                setState(() => i = j);
                Navigator.pop(ctx);
              },
            ),
          gap12,
        ]),
      );

  void lateSheet() => showSheet(
        context,
        (ctx) => Column(mainAxisSize: MainAxisSize.min, children: [
          const SheetHead('Potongan keterlambatan', subtitle: 'Rp 25.000 per keterlambatan di atas 5 menit'),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
            child: Column(children: [
              for (final d in octDays.where((d) => d.lateMin > 0))
                Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: AppCard(
                    color: C.sageSoft,
                    child: Row(children: [
                      const IconTile(LucideIcons.clock, tone: Tone.warning),
                      gap12,
                      Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(tanggal(d.date).replaceAll(' 2026', ''), style: T.title), Text('Masuk ${d.inT} · terlambat ${d.lateMin} menit', style: T.caption)])),
                      Text('-Rp 25.000', style: T.numS.copyWith(color: C.dangerText)),
                    ]),
                  ),
                ),
              Row(children: [Expanded(child: Text('Total potongan', style: T.title)), Text('-Rp 50.000', style: T.numM.copyWith(color: C.dangerText))]),
              gap20,
              Row(children: [
                Expanded(
                  child: Btn('Ajukan koreksi', kind: BtnKind.light, onTap: () {
                    Navigator.pop(ctx);
                    toast(context, 'Pengajuan koreksi dikirim ke Dimas Ardiansyah.', aboveNav: true);
                  }),
                ),
                gap12,
                Expanded(
                  child: Btn('Buka Riwayat', onTap: () {
                    Navigator.pop(ctx);
                    push(context, const History());
                  }),
                ),
              ]),
            ]),
          ),
        ]),
      );

  void ask() {
    var topic = 0;
    showSheet(
      context,
      (ctx) => StatefulBuilder(
        builder: (ctx, set) => Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
          SheetHead('Ajukan pertanyaan gaji', subtitle: 'Periode ${p.month}'),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('Topik', style: T.title),
              gap8,
              ChipWrap(const ['Potongan', 'Lembur', 'Tunjangan', 'Pajak', 'Lainnya'], (j) => j == topic, (j) => set(() => topic = j)),
              gap16,
              const Field(label: 'Pertanyaan', hint: 'Contoh: lembur Sabtu 3 Okt belum masuk hitungan', maxLines: 4, maxLength: 300),
              gap12,
              Btn('Kirim ke HR', icon: LucideIcons.send, onTap: () {
                Navigator.pop(ctx);
                toast(context, 'Pertanyaan terkirim. HR biasanya menjawab dalam 1 hari kerja.', aboveNav: true);
              }),
            ]),
          ),
        ]),
      ),
    );
  }

  Widget _lines(String title, List<PayLine> lines, int total, bool minus) => AppCard(
        child: Column(children: [
          CardTitle(title, trailing: '${minus ? '-' : '+'}${_money(total)}'),
          for (final l in lines)
            InkWell(
              onTap: l.label == 'Keterlambatan' && l.amount > 0 && i == 0 ? lateSheet : null,
              child: Padding(
                padding: const EdgeInsets.symmetric(vertical: 8),
                child: Row(children: [
                  Expanded(
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text(l.label, style: T.bodyM),
                      Text(l.note, style: T.small.copyWith(color: l.label == 'Keterlambatan' && i == 0 ? C.warningText : C.text3)),
                    ]),
                  ),
                  Text(app.hideSalary ? '•••••' : num0(l.amount), style: T.numS.copyWith(color: minus && l.amount > 0 ? C.dangerText : C.text)),
                  if (l.label == 'Keterlambatan' && l.amount > 0 && i == 0) const Icon(LucideIcons.chevronRight, size: 16, color: C.text3),
                ]),
              ),
            ),
        ]),
      );

  @override
  Widget build(BuildContext context) => ListenableBuilder(
        listenable: app,
        builder: (context, _) {
          final prev = i + 1 < slips.length ? slips[i + 1] : null;
          final diff = prev == null ? 0 : p.net - prev.net;
          final tunj = p.earnings.where((e) => e.label.startsWith('Tunjangan')).fold(0, (a, b) => a + b.amount);
          final pokok = p.earnings.first.amount;
          final extra = p.gross - pokok - tunj;
          return Screen(
            inShell: true,
            header: PageHeader('Gaji', subtitle: '${app.profile?.employee.fullName ?? '-'} · ${app.profile?.employee.division?.name ?? '-'}', back: true, actions: [
              CircleBtn(LucideIcons.circleQuestionMark, tooltip: 'Cara perhitungan gaji', onTap: () => _howTo(context)),
            ]),
            children: [
              Row(children: [
                Expanded(child: PickerBox(value: p.month, icon: LucideIcons.calendar, onTap: period)),
                gap12,
                p.final_ ? const Pill('Final', icon: LucideIcons.circleCheck) : const Pill('Estimasi berjalan', icon: LucideIcons.hourglass, tone: Tone.warning),
              ]),
              gap12,
              AppCard(
                color: C.primary,
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Row(children: [
                    Text(p.final_ ? 'Take-home pay' : 'Estimasi take-home pay', style: T.caption.copyWith(color: Colors.white70)),
                    const Spacer(),
                    CircleBtn(app.hideSalary ? LucideIcons.eyeOff : LucideIcons.eye, size: 36, bg: Colors.white.withValues(alpha: .14), fg: Colors.white, tooltip: app.hideSalary ? 'Tampilkan gaji' : 'Sembunyikan gaji', onTap: app.toggleSalary),
                  ]),
                  AnimatedSwitcher(
                    duration: const Duration(milliseconds: 200),
                    child: Text(_money(p.net), key: ValueKey('${app.hideSalary}$i'), style: T.numXL.copyWith(color: Colors.white)),
                  ),
                  gap8,
                  if (prev != null && !app.hideSalary)
                    Row(children: [
                      Icon(diff >= 0 ? LucideIcons.trendingUp : LucideIcons.trendingDown, size: 14, color: C.mid),
                      gap4,
                      Text('${diff >= 0 ? 'Naik' : 'Turun'} ${rp(diff.abs())} dari ${prev.month.split(' ').first}', style: T.small.copyWith(color: C.mid)),
                    ]),
                  gap4,
                  Text('${p.paid} ke BCA •••• 4417', style: T.small.copyWith(color: Colors.white70)),
                ]),
              ),
              const Overline('KOMPOSISI GAJI'),
              AppCard(
                child: Column(children: [
                  CardTitle('Bruto', trailing: _money(p.gross)),
                  StackBar([(pokok / p.gross, C.primary), (tunj / p.gross, C.mid), (extra / p.gross, C.warning)], height: 10),
                  gap12,
                  for (final (l, v, c) in [('Gaji Pokok', pokok, C.primary), ('Tunjangan', tunj, C.mid), ('Lembur & Bonus', extra, C.warning)])
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 4),
                      child: Row(children: [
                        Container(width: 10, height: 10, decoration: BoxDecoration(color: c, shape: BoxShape.circle)),
                        gap8,
                        Expanded(child: Text(l, style: T.body)),
                        Text(_money(v), style: T.numS),
                      ]),
                    ),
                  const Divider(height: 20),
                  Row(children: [Expanded(child: Text('Potongan', style: T.body)), Text('-${_money(p.cut)}', style: T.numS.copyWith(color: C.dangerText))]),
                ]),
              ),
              gap12,
              _lines('Pendapatan', p.earnings, p.gross, false),
              gap12,
              _lines('Potongan', p.deductions, p.cut, true),
              gap12,
              AppCard(
                color: C.accentSubtle,
                child: Row(children: [
                  Expanded(child: Text('Take-home pay', style: T.h3.copyWith(color: C.accent))),
                  Text(_money(p.net), style: T.numM.copyWith(color: C.accent)),
                ]),
              ),
              gap12,
              Btn('Cara perhitungan gaji', kind: BtnKind.light, icon: LucideIcons.calculator, height: 48, onTap: () => _howTo(context)),
              const Overline('RIWAYAT SLIP'),
              AppCard(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                child: Divided([
                  for (final s in slips.skip(1))
                    InkWell(
                      onTap: () => push(context, Slip(s), root: true),
                      child: Padding(
                        padding: const EdgeInsets.symmetric(vertical: 10),
                        child: Row(children: [
                          const IconTile(LucideIcons.receiptText),
                          gap12,
                          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(s.month, style: T.title), Text(s.paid, style: T.small)])),
                          Text(_money(s.net), style: T.numS),
                          const Icon(LucideIcons.chevronRight, size: 16, color: C.text3),
                        ]),
                      ),
                    ),
                ]),
              ),
              gap16,
              Btn('Ajukan pertanyaan gaji', kind: BtnKind.soft, icon: LucideIcons.messageCircleQuestionMark, onTap: ask),
            ],
          );
        },
      );

  void _howTo(BuildContext context) => showSheet(
        context,
        (ctx) => Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
          const SheetHead('Cara perhitungan gaji'),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
            child: Column(children: [
              for (final (n, t, d) in const [
                ('1', 'Pendapatan', 'Gaji pokok ditambah tunjangan transport dan makan per hari hadir, lembur per jam, lalu bonus.'),
                ('2', 'Potongan', 'BPJS dan PPh 21 sesuai aturan, ditambah Rp 25.000 untuk tiap keterlambatan di atas 5 menit.'),
                ('3', 'Take-home', 'Pendapatan dikurangi potongan. Estimasi berubah setiap hari sampai slip final terbit.'),
              ])
                Padding(
                  padding: const EdgeInsets.only(bottom: 14),
                  child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    CircleAvatar(radius: 13, backgroundColor: C.bg, child: Text(n, style: T.small.copyWith(color: C.primary, fontWeight: FontWeight.w700))),
                    gap12,
                    Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(t, style: T.title), Text(d, style: T.body)])),
                  ]),
                ),
            ]),
          ),
        ]),
      );
}

// ---------------------------------------------------------------- B09 slip

class Slip extends StatelessWidget {
  const Slip(this.p, {super.key});
  final Payslip p;

  @override
  Widget build(BuildContext context) {
    Widget line(String l, int v, {bool minus = false, bool bold = false}) => Padding(
          padding: const EdgeInsets.symmetric(vertical: 5),
          child: Row(children: [
            Expanded(child: Text(l, style: bold ? T.title : T.body)),
            Text('${minus && v > 0 ? '-' : ''}${num0(v)}', style: T.numS.copyWith(fontWeight: bold ? FontWeight.w800 : null, color: minus && bold ? C.dangerText : C.text)),
          ]),
        );
    final paidDay = p.paid.replaceFirst('Dibayar ', '');
    return Screen(
      header: const TopBar('Slip Gaji'),
      bottom: BottomActions([
        Btn('Bagikan', kind: BtnKind.light, icon: LucideIcons.share2, onTap: () => toast(context, 'Tautan slip disalin.')),
        Btn('Unduh PDF', icon: LucideIcons.download, onTap: () => toast(context, 'Slip_${p.month.replaceAll(' ', '_')}_HM-0247.pdf diunduh.', icon: LucideIcons.download)),
      ]),
      children: [
        gap8,
        AppCard(
          radius: 16,
          padding: const EdgeInsets.all(20),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(children: [
              const Logo(size: 36),
              gap12,
              Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text('PT Sinar Metrik Nusantara', style: T.title), Text('Jl. Magelang Km 5, Sleman, Yogyakarta', style: T.small)])),
            ]),
            const Padding(padding: EdgeInsets.symmetric(vertical: 14), child: Divider(thickness: 1.5, color: C.text)),
            Row(children: [
              Expanded(child: Text('SLIP GAJI ${p.month}', style: T.h3)),
              p.final_ ? const Pill('Dibayar', icon: LucideIcons.circleCheck) : const Pill('Estimasi', tone: Tone.warning),
            ]),
            gap12,
            for (final (k, v) in [
              ('Nama', app.profile?.employee.fullName ?? '-'),
              ('ID karyawan', app.profile?.employee.employeeNumber ?? '-'),
              ('Jabatan', '${app.profile?.employee.jobTitle ?? '-'}, ${app.profile?.employee.division?.name ?? '-'}'),
              ('Periode', '1 sampai ${p.month.startsWith('September') ? 30 : 31} ${p.month.split(' ').first.substring(0, 3)} 2026'),
              ('Dibayar', paidDay),
            ])
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 2),
                child: Row(children: [Text(k, style: T.caption), gap12, Expanded(child: Text(v, textAlign: TextAlign.right, style: T.bodyM))]),
              ),
            const Overline('PENDAPATAN', padding: EdgeInsets.fromLTRB(0, 18, 0, 6)),
            for (final l in p.earnings) line(l.label, l.amount),
            const Divider(),
            line('Total pendapatan', p.gross, bold: true),
            const Overline('POTONGAN', padding: EdgeInsets.fromLTRB(0, 18, 0, 6)),
            for (final l in p.deductions) line(l.label, l.amount, minus: true),
            const Divider(),
            line('Total potongan', p.cut, minus: true, bold: true),
            gap16,
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(color: C.accentSubtle, borderRadius: BorderRadius.circular(14)),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text('Gaji bersih', style: T.caption.copyWith(color: C.accent)),
                Text(rp(p.net), style: T.numL.copyWith(color: C.accent)),
                gap4,
                Text(terbilang(p.net), style: T.small.copyWith(fontStyle: FontStyle.italic, color: C.accent)),
              ]),
            ),
            gap12,
            Text('Ditransfer ke BCA •••• 4417 atas nama ${app.profile?.employee.fullName ?? 'Karyawan'}', style: T.caption),
            gap16,
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(border: Border.all(color: C.border), borderRadius: BorderRadius.circular(12)),
              child: Row(children: [
                const Icon(LucideIcons.shieldCheck, color: C.accent, size: 20),
                gap8,
                Expanded(child: Text('Terverifikasi digital oleh HR · Sari Wulandari. Slip ini sah tanpa tanda tangan basah.', style: T.small)),
              ]),
            ),
          ]),
        ),
      ],
    );
  }
}

// ---------------------------------------------------------------- B04 riwayat

class History extends StatefulWidget {
  const History({super.key});
  @override
  State<History> createState() => _HistoryState();
}

class _HistoryState extends State<History> {
  int filter = 0, month = 10;

  bool isToday(AttDay d) => d.month == 10 && d.day == 7;

  AttDay? get today => app.clockIn == null ? null : AttDay(7, app.lateMinutes(app.clockIn!) > 0 ? Att.terlambat : Att.hadir, inT: hm(app.clockIn!), outT: app.clockOut == null ? null : hm(app.clockOut!), lateMin: app.lateMinutes(app.clockIn!).clamp(0, 999));

  List<AttDay> get days => month == 10 ? [?today, ...octDays.reversed] : pastDays(month).reversed.toList();

  void daySheet(AttDay d) => showSheet(
        context,
        (ctx) => Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
          SheetHead(tanggal(d.date), subtitle: d.status == Att.cuti ? 'Cuti Tahunan · disetujui' : 'Shift Reguler 08.00 - 17.00'),
          Flexible(
            child: ListView(shrinkWrap: true, padding: const EdgeInsets.fromLTRB(20, 0, 20, 20), children: [
              if (d.status == Att.cuti)
                const Banner2('Kamu cuti di tanggal ini', body: 'Cuti Tahunan 14 sampai 16 Okt, disetujui Dimas Ardiansyah. Serah terima ke Budi Santoso.', tone: Tone.info, icon: LucideIcons.plane)
              else ...[
                Row(children: [
                  for (final (l, v) in [('Clock-In', d.inT ?? '--.--'), ('Clock-Out', d.outT ?? 'sekarang'), ('Durasi', d.outT == null ? dur(app.worked) : dur(d.worked))]) ...[
                    if (l != 'Clock-In') gap8,
                    Expanded(
                      child: Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(color: C.sageSoft, borderRadius: BorderRadius.circular(16)),
                        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(l, style: T.small), Text(v, style: T.numS)]),
                      ),
                    ),
                  ],
                ]),
                gap12,
                const MapBox(height: 120, pins: [MapPin(Alignment(.1, -.1), C.accent)]),
                gap12,
                Wrap(spacing: 8, runSpacing: 8, children: const [
                  Pill('Wajah cocok 98% [DL]', icon: LucideIcons.scanFace),
                  Pill('Lokasi asli', icon: LucideIcons.mapPin),
                  Pill('Perangkat terikat', icon: LucideIcons.smartphone),
                  Pill('Liveness OK', icon: LucideIcons.eye),
                ]),
                if (d.note != null) ...[gap12, Banner2('Catatan', body: d.note, tone: Tone.neutral, icon: LucideIcons.stickyNote)],
                if (d.lateMin > 0) ...[gap12, Banner2('Potongan keterlambatan: Rp 25.000', body: 'Terlambat ${d.lateMin} menit dari jadwal 08.00.', tone: Tone.warning, icon: LucideIcons.clock)],
                if (d.overtime > 0) ...[gap12, Banner2('Lembur ${d.overtime} jam', body: 'Disetujui Dimas Ardiansyah, masuk slip Oktober.', tone: Tone.info, icon: LucideIcons.timer)],
                gap16,
                Btn('Ajukan Koreksi Kehadiran', kind: BtnKind.soft, icon: LucideIcons.pencil, onTap: () {
                  Navigator.pop(ctx);
                  toast(context, 'Pengajuan koreksi dikirim ke Dimas Ardiansyah.', aboveNav: true);
                }),
              ],
            ]),
          ),
        ]),
      );

  @override
  Widget build(BuildContext context) => ListenableBuilder(
        listenable: app,
        builder: (context, _) {
          final all = days;
          final shown = switch (filter) { 1 => all.where((d) => d.status == Att.hadir), 2 => all.where((d) => d.status == Att.terlambat), 3 => all.where((d) => d.status == Att.cuti), _ => all }.toList();
          final count = {for (final a in Att.values) a: all.where((d) => d.status == a).length};
          final total = all.fold(Duration.zero, (a, d) => a + (isToday(d) ? app.worked : d.worked));
          return Screen(
            inShell: true,
            header: PageHeader('Riwayat Kehadiran', subtitle: '${bulan[month - 1]} 2026', back: true),
            children: [
              AppCard(
                child: Column(children: [
                  Row(children: [
                    CircleBtn(LucideIcons.chevronLeft, size: 40, bg: C.sageSoft, tooltip: 'Bulan sebelumnya', onTap: month > 7 ? () => setState(() => month--) : null),
                    Expanded(child: Text('${bulan[month - 1]} 2026', textAlign: TextAlign.center, style: T.h3)),
                    CircleBtn(LucideIcons.chevronRight, size: 40, bg: C.sageSoft, tooltip: 'Bulan berikutnya', onTap: month < 10 ? () => setState(() => month++) : null),
                  ]),
                  gap12,
                  MonthGrid(
                    year: 2026,
                    month: month,
                    today: month == 10 ? 7 : null,
                    dots: {for (final d in all) d.day: d.status.dot},
                    onTap: (day) {
                      final d = all.where((x) => x.day == day).firstOrNull;
                      if (d != null) daySheet(d);
                    },
                  ),
                  gap8,
                  Legend([for (final a in [Att.hadir, Att.terlambat, Att.absen, Att.cuti]) (a.label, a.dot)]),
                ]),
              ),
              const Overline('RINGKASAN'),
              AppCard(
                child: Column(children: [
                  Row(children: [
                    for (final a in [Att.hadir, Att.terlambat, Att.absen, Att.cuti])
                      Expanded(
                        child: Column(children: [
                          Text('${count[a]}', style: T.numL.copyWith(color: a.tone.fg)),
                          Text(a.label, style: T.small),
                        ]),
                      ),
                  ]),
                  const Divider(height: 24),
                  Row(children: [
                    const Icon(LucideIcons.timer, size: 16, color: C.primary),
                    gap8,
                    Expanded(child: Text('Total jam kerja', style: T.body)),
                    Text('${total.inHours} j ${total.inMinutes.remainder(60).toString().padLeft(2, '0')} m', style: T.numS),
                  ]),
                ]),
              ),
              gap16,
              ChipBar(const ['Semua', 'Hadir', 'Terlambat', 'Cuti'], filter, (i) => setState(() => filter = i)),
              gap12,
              for (final d in shown)
                Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: AppCard(
                    padding: const EdgeInsets.all(12),
                    radius: 20,
                    onTap: () => daySheet(d),
                    child: Row(children: [
                      Container(
                        width: 48,
                        height: 52,
                        decoration: BoxDecoration(color: isToday(d) ? C.primary : C.sageSoft, borderRadius: BorderRadius.circular(14)),
                        child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
                          Text(d.day.toString().padLeft(2, '0'), style: T.numS.copyWith(color: isToday(d) ? Colors.white : C.text)),
                          Text(hariPendek[d.date.weekday - 1], style: T.small.copyWith(color: isToday(d) ? Colors.white70 : C.text3)),
                        ]),
                      ),
                      gap12,
                      Expanded(
                        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                          Text(d.status == Att.cuti ? 'Cuti Tahunan' : '${d.inT} → ${d.outT ?? 'sekarang'}', style: T.title),
                          Text(d.status == Att.cuti ? 'Disetujui' : (isToday(d) && d.outT == null ? 'Sedang bekerja · ${dur(app.worked)}' : dur(d.worked)), style: T.caption),
                        ]),
                      ),
                      Pill(d.status.label, icon: d.status.icon, tone: d.status.tone),
                    ]),
                  ),
                ),
              if (shown.isEmpty) EmptyState(icon: LucideIcons.calendarX, title: 'Tidak ada data', message: 'Belum ada hari dengan status ${['', 'hadir', 'terlambat', 'cuti'][filter]} bulan ini.'),
            ],
          );
        },
      );
}

// ---------------------------------------------------------------- B05 statistik

class Stats extends StatefulWidget {
  const Stats({super.key});
  @override
  State<Stats> createState() => _StatsState();
}

class _StatsState extends State<Stats> {
  int range = 1;

  void scoreSheet() => showSheet(
        context,
        (ctx) => Column(mainAxisSize: MainAxisSize.min, children: [
          const SheetHead('Rincian skor disiplin', subtitle: 'Dihitung ulang setiap hari pukul 00.00'),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
            child: Column(children: [
              for (final (l, v, m, s) in const [
                ('Ketepatan waktu', 36, 40, 'Terlambat 2 kali bulan ini'),
                ('Kehadiran', 30, 30, 'Tidak ada absen tanpa keterangan'),
                ('Penyelesaian tugas', 20, 20, 'Semua tugas selesai sebelum tenggat'),
                ('Kepatuhan absen', 6, 10, 'Lupa clock-out 1 kali'),
              ])
                Padding(
                  padding: const EdgeInsets.only(bottom: 14),
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Row(children: [Expanded(child: Text(l, style: T.title)), Text('$v/$m', style: T.numS)]),
                    gap4,
                    Bar(v / m, color: v == m ? C.accent : C.warning),
                    gap4,
                    Text(s, style: T.small),
                  ]),
                ),
              const Divider(),
              Row(children: [Expanded(child: Text('Total', style: T.h3)), Text('92/100', style: T.numM.copyWith(color: C.primary))]),
            ]),
          ),
        ]),
      );

  @override
  Widget build(BuildContext context) {
    final hours = [
      [9.2, 8.8, 11.2, 9.0, 8.5],
      [8.9, 8.6, 9.4, 8.8, 8.7],
      [8.7, 8.5, 9.1, 8.9, 8.4],
    ][range];
    return Screen(
      inShell: true,
      header: Column(children: [
        const PageHeader('Statistik Saya', back: true),
        Padding(padding: pagePad, child: Segmented(const ['Minggu ini', 'Bulan ini', '3 Bulan'], range, (i) => setState(() => range = i))),
        gap8,
      ]),
      children: [
        gap8,
        AppCard(
          onTap: scoreSheet,
          child: Row(children: [
            Ring(.92, size: 108, child: Column(mainAxisSize: MainAxisSize.min, children: [Text('92', style: T.numXL.copyWith(color: C.primary)), Text('dari 100', style: T.small)])),
            gap16,
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text('Skor disiplin', style: T.caption),
                Text('Sangat disiplin', style: T.h2),
                gap4,
                const Pill('Naik 4 poin', icon: LucideIcons.trendingUp),
                gap8,
                Text('Ketuk untuk lihat rincian', style: T.small.copyWith(color: C.primary)),
              ]),
            ),
          ]),
        ),
        const Overline('TREN KETERLAMBATAN'),
        AppCard(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(children: [
              Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text('Menit terlambat per minggu', style: T.title), Text('8 minggu terakhir', style: T.small)])),
              const Pill('Turun 76%', icon: LucideIcons.trendingDown),
            ]),
            gap16,
            const LineChart(
              series: [Series([42, 35, 30, 28, 18, 15, 12, 10], C.primary)],
              count: 8,
              labels: ['M34', 'M35', 'M36', 'M37', 'M38', 'M39', 'M40', 'M41'],
              max: 50,
              yTicks: [0, 25, 50],
            ),
          ]),
        ),
        gap12,
        Row(children: [
          for (final (l, v, icon) in [('Rata-rata masuk', '07.56', LucideIcons.logIn), ('Rata-rata pulang', '17.21', LucideIcons.logOut)]) ...[
            if (l != 'Rata-rata masuk') gap12,
            Expanded(
              child: AppCard(
                radius: 20,
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Icon(icon, size: 18, color: C.primary), gap8, Text(l, style: T.small), Text(v, style: T.numL)]),
              ),
            ),
          ],
        ]),
        const Overline('JAM KERJA PER HARI'),
        AppCard(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(children: [Expanded(child: Text('Rata-rata ${(hours.reduce((a, b) => a + b) / 5).toStringAsFixed(1).replaceAll('.', ',')} jam', style: T.title)), Text('Target 8 jam', style: T.small)]),
            gap12,
            Bars(hours, const ['Sen', 'Sel', 'Rab', 'Kam', 'Jum'], highlight: hours.indexOf(hours.reduce((a, b) => a > b ? a : b)), valueLabels: [for (final h in hours) h.toStringAsFixed(1).replaceAll('.', ',')], height: 110, key: ValueKey(range)),
          ]),
        ),
        const Overline('PENCAPAIAN · 4 DARI 6'),
        GridView.count(
          crossAxisCount: 3,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          mainAxisSpacing: 10,
          crossAxisSpacing: 10,
          childAspectRatio: .95,
          children: [
            for (final (icon, l, got) in const [
              (LucideIcons.alarmClock, 'Tepat waktu 5 hari', true),
              (LucideIcons.calendarCheck, 'Tanpa absen', true),
              (LucideIcons.zap, 'Lembur produktif', true),
              (LucideIcons.clipboardCheck, 'Tugas tuntas', true),
              (LucideIcons.trophy, 'Sebulan sempurna', false),
              (LucideIcons.sunrise, 'Datang paling awal', false),
            ])
              AppCard(
                padding: const EdgeInsets.all(10),
                radius: 20,
                color: got ? Colors.white : C.sageSoft,
                child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
                  IconTile(got ? icon : LucideIcons.lock, tone: got ? Tone.insight : Tone.neutral, round: true),
                  gap8,
                  Text(l, textAlign: TextAlign.center, maxLines: 2, style: T.small.copyWith(color: got ? C.text : C.text3, fontWeight: FontWeight.w600)),
                ]),
              ),
          ],
        ),
        const Overline('INSIGHT & SARAN [DL]'),
        AppCard(
          color: C.insightSubtle,
          child: Column(children: [
            for (final (icon, t) in const [
              (LucideIcons.cloudRain, 'Kedua keterlambatanmu terjadi saat hujan. Berangkat 15 menit lebih awal saat mendung.'),
              (LucideIcons.calendarDays, 'Senin adalah hari dengan jam kerja terpanjang. Coba bagi tugas berat ke hari lain.'),
              (LucideIcons.target, 'Satu kali lagi tepat waktu 5 hari berturut-turut untuk lencana Sebulan sempurna.'),
            ])
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 6),
                child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Icon(icon, size: 18, color: C.insight),
                  gap12,
                  Expanded(child: Text(t, style: T.body.copyWith(color: C.insightText))),
                ]),
              ),
          ]),
        ),
      ],
    );
  }
}

// ---------------------------------------------------------------- B11 notifikasi

class Notifs extends StatefulWidget {
  const Notifs({super.key});
  @override
  State<Notifs> createState() => _NotifsState();
}

class _NotifsState extends State<Notifs> {
  int filter = 0;
  static const filters = ['Semua', 'Absensi', 'Tugas', 'Gaji', 'Pengajuan'];

  void open(Notif n) {
    n.read = true;
    app.touch();
    switch (n.cat) {
      case NCat.tugas:
        push(context, TaskDetail(app.tasks[1]), root: true);
      case NCat.revisi:
        push(context, TaskDetail(app.tasks[3]), root: true);
      case NCat.gaji:
        push(context, const Slip(paySep), root: true);
      case NCat.pengajuan:
        leaveTab.value = 1;
        Shell.go(3);
      case NCat.absensi || NCat.pengingat:
        push(context, const History());
      case NCat.keamanan || NCat.info:
        showSheet(
          context,
          (ctx) => Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
            SheetHead(n.title, subtitle: '${n.group == 'HARI INI' ? 'Hari ini' : 'Kemarin'}, ${n.time}'),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(n.body, style: T.body),
                if (n.cat == NCat.keamanan) ...[
                  gap12,
                  const AppCard(
                    color: C.sageSoft,
                    child: Divided([
                      InfoRow('Perangkat', 'Xiaomi Redmi 12', icon: LucideIcons.smartphone),
                      InfoRow('Lokasi', 'Bantul, Yogyakarta', icon: LucideIcons.mapPin),
                      InfoRow('Status', 'Ditolak otomatis', icon: LucideIcons.shieldX, valueColor: C.dangerText),
                    ]),
                  ),
                  gap12,
                  Text('Akunmu hanya bisa dipakai di Samsung Galaxy A54. Kalau ini bukan kamu, segera ganti kata sandi.', style: T.caption),
                ],
                gap20,
                Btn('Mengerti', onTap: () => Navigator.pop(ctx)),
              ]),
            ),
          ]),
        );
    }
  }

  @override
  Widget build(BuildContext context) => ListenableBuilder(
        listenable: app,
        builder: (context, _) {
          final list = app.notifs.where((n) => filter == 0 || n.cat.filter == filters[filter]).toList();
          return Screen(
            inShell: true,
            header: Column(children: [
              PageHeader('Notifikasi', subtitle: app.unread == 0 ? 'Semua sudah dibaca' : '${app.unread} belum dibaca', back: true),
              ChipBar(filters, filter, (i) => setState(() => filter = i), padding: pagePad),
              gap4,
            ]),
            children: [
              Row(children: [
                Expanded(child: Text('${list.where((n) => !n.read).length} belum dibaca', style: T.caption)),
                TextButton(
                  onPressed: app.unread == 0
                      ? null
                      : () {
                          final before = app.markAllRead();
                          toast(context, 'Semua notifikasi ditandai dibaca', action: 'Batalkan', onAction: () => app.restoreRead(before), aboveNav: true);
                        },
                  child: const Text('Tandai semua dibaca'),
                ),
              ]),
              if (list.isEmpty) const EmptyState(icon: LucideIcons.bellOff, title: 'Belum ada notifikasi', message: 'Notifikasi untuk kategori ini akan muncul di sini.'),
              for (final g in ['HARI INI', 'KEMARIN', 'SEBELUMNYA'])
                if (list.any((n) => n.group == g)) ...[
                  Overline(g, padding: const EdgeInsets.fromLTRB(4, 8, 4, 8)),
                  AppCard(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 6),
                    child: Column(children: [
                      for (final n in list.where((n) => n.group == g))
                        InkWell(
                          borderRadius: BorderRadius.circular(18),
                          onTap: () => open(n),
                          child: AnimatedContainer(
                            duration: const Duration(milliseconds: 200),
                            padding: const EdgeInsets.all(10),
                            decoration: BoxDecoration(color: n.read ? null : C.accentSubtle.withValues(alpha: .6), borderRadius: BorderRadius.circular(18)),
                            child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                              IconTile(n.cat.icon, tone: n.cat.tone, round: true),
                              gap12,
                              Expanded(
                                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                                  Row(children: [
                                    Expanded(child: Text(n.title, style: T.title.copyWith(fontWeight: n.read ? FontWeight.w600 : FontWeight.w800))),
                                    Text(n.time, style: T.small),
                                  ]),
                                  gap4,
                                  Text(n.body, style: T.caption),
                                ]),
                              ),
                              if (!n.read) Container(width: 8, height: 8, margin: const EdgeInsets.only(left: 8, top: 6), decoration: const BoxDecoration(color: C.accent, shape: BoxShape.circle)),
                            ]),
                          ),
                        ),
                    ]),
                  ),
                ],
            ],
          );
        },
      );
}
