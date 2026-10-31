import 'dart:async';

import 'package:flutter/material.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';

import '../data.dart';
import '../main.dart';
import '../state.dart';
import '../theme.dart';
import '../visuals.dart';
import '../widgets.dart';
import 'me.dart';

/// Entry point of the clock-in / clock-out flow (B02, B03).
void startClock(BuildContext context) {
  if (app.onLeave) return toast(context, 'Hari ini kamu cuti. Absen tidak diperlukan.', icon: LucideIcons.plane, aboveNav: true);
  if (app.dayDone) return toast(context, 'Absen hari ini sudah lengkap.', aboveNav: true);
  final nav = Navigator.of(context, rootNavigator: true);
  showSheet<bool>(context, (_) => const _Precheck()).then((ok) {
    if (ok == null) return;
    nav.push(MaterialPageRoute(builder: (_) => ok ? const ClockScan() : Blocked(app.sim)));
  });
}

bool get _out => app.clockIn != null;

// ---------------------------------------------------------------- precheck

const _checks = ['Lokasi GPS akurat', 'Dalam radius kantor', 'Tidak ada aplikasi lokasi palsu', 'Mode developer / mock location nonaktif', 'Perangkat tidak di-root', 'Perangkat terdaftar'];

int? get _failAt => switch (app.sim) {
      Sim.gpsLemah || Sim.lompat => 0,
      Sim.luarArea => 1,
      Sim.fakeGps => 2,
      Sim.developer => 3,
      Sim.root => 4,
      _ => null,
    };

class _Precheck extends StatefulWidget {
  const _Precheck();
  @override
  State<_Precheck> createState() => _PrecheckState();
}

class _PrecheckState extends State<_Precheck> {
  int step = 0;
  bool failed = false;
  Timer? t;
  final fail = _failAt;

  @override
  void initState() {
    super.initState();
    t = Timer.periodic(const Duration(milliseconds: 380), (_) {
      if (step == fail) {
        t?.cancel();
        setState(() => failed = true);
        Future.delayed(const Duration(milliseconds: 800), () => mounted ? Navigator.pop(context, false) : null);
      } else if (step == _checks.length) {
        t?.cancel();
        Future.delayed(const Duration(milliseconds: 300), () => mounted ? Navigator.pop(context, true) : null);
      } else {
        setState(() => step++);
      }
    });
  }

  @override
  void dispose() {
    t?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.fromLTRB(0, 0, 0, 16),
        child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          const SheetHead('Pemeriksaan keamanan', subtitle: 'Memastikan absen sah dan aman'),
          for (var i = 0; i < _checks.length; i++)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 6),
              child: Row(children: [
                SizedBox(
                  width: 28,
                  height: 28,
                  child: AnimatedSwitcher(
                    duration: const Duration(milliseconds: 200),
                    child: switch ((i < step, i == step && failed, i == step)) {
                      (true, _, _) => const IconTile(LucideIcons.check, tone: Tone.success, size: 28, round: true, key: ValueKey('ok')),
                      (_, true, _) => const IconTile(LucideIcons.x, tone: Tone.danger, size: 28, round: true, key: ValueKey('x')),
                      (_, _, true) => const Padding(padding: EdgeInsets.all(5), child: CircularProgressIndicator(strokeWidth: 2.4, key: ValueKey('p'))),
                      _ => const IconTile(LucideIcons.hourglass, tone: Tone.neutral, size: 28, round: true, key: ValueKey('w')),
                    },
                  ),
                ),
                gap12,
                Expanded(child: Text(_checks[i], style: T.bodyM.copyWith(color: i <= step ? C.text : C.text3))),
                Text(
                  i < step ? 'Lolos' : (i == step ? (failed ? 'Gagal' : 'Memeriksa...') : 'Menunggu'),
                  style: T.caption.copyWith(color: i < step ? C.accent : (i == step && failed ? C.danger : C.text3), fontWeight: FontWeight.w600),
                ),
              ]),
            ),
          gap12,
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: Row(children: [
              const Icon(LucideIcons.lock, size: 14, color: C.text3),
              gap8,
              Expanded(child: Text('Hasil pemeriksaan tersimpan bersama catatan absen.', style: T.small)),
            ]),
          ),
        ]),
      );
}

// ---------------------------------------------------------------- scan + liveness + verify

enum _P { scan, valid, live, verify, fail }

const _prompts = [(LucideIcons.eye, 'Kedipkan mata'), (LucideIcons.arrowRight, 'Tengok ke kanan'), (LucideIcons.smile, 'Senyum')];

class ClockScan extends StatefulWidget {
  const ClockScan({super.key});
  @override
  State<ClockScan> createState() => _ClockScanState();
}

class _ClockScanState extends State<ClockScan> {
  _P phase = _P.scan;
  int live = 0, gen = 0;
  Sim? failKind;

  @override
  void initState() {
    super.initState();
    run();
  }

  @override
  void dispose() {
    gen++;
    super.dispose();
  }

  Future<bool> _wait(int ms, int g) async {
    await Future.delayed(Duration(milliseconds: ms));
    return mounted && g == gen;
  }

  void _fail(Sim s) => setState(() {
        phase = _P.fail;
        failKind = s;
      });

  Future<void> run() async {
    final g = ++gen;
    setState(() {
      phase = _P.scan;
      live = 0;
    });
    final s = app.sim;
    if (!await _wait(1500, g)) return;
    if (s == Sim.gelap || s == Sim.banyakWajah) return _fail(s);
    setState(() => phase = _P.valid);
    if (!await _wait(700, g)) return;
    setState(() => phase = _P.live);
    for (var i = 0; i < 3; i++) {
      if (!await _wait(1300, g)) return;
      if (s == Sim.liveness && i == 1) return _fail(s);
      setState(() => live = i + 1);
    }
    setState(() => phase = _P.verify);
    if (!await _wait(1500, g)) return;
    if (s == Sim.wajah) {
      app.faceAttempts++;
      return _fail(s);
    }
    app.faceAttempts = 0;
    if (mounted) Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => const ClockConfirm()));
  }

  int get stepNo => switch (phase) { _P.scan || _P.valid => 1, _P.live => 2, _ => 3 };

  String get instruction => switch (phase) {
        _P.scan => 'Posisikan wajah di dalam bingkai',
        _P.valid => 'Wajah terdeteksi',
        _P.live => _prompts[live.clamp(0, 2)].$2,
        _P.verify => 'Tahan sebentar',
        _P.fail => 'Verifikasi gagal',
      };

  @override
  Widget build(BuildContext context) {
    final dark = phase == _P.fail && failKind == Sim.gelap;
    return Scaffold(
      backgroundColor: C.primary,
      body: Column(children: [
        Expanded(
          child: SafeArea(
            bottom: false,
            child: Column(children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
                child: Row(children: [
                  CircleBtn(LucideIcons.x, tooltip: 'Batalkan', onTap: () => Navigator.pop(context)),
                  const Spacer(),
                  Container(
                    padding: const EdgeInsets.fromLTRB(6, 6, 14, 6),
                    decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(999)),
                    child: Row(children: [
                      const IconTile(LucideIcons.mapPin, size: 32, round: true),
                      gap8,
                      Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        Text('Kantor Pusat Yogyakarta', style: T.small.copyWith(color: C.text, fontWeight: FontWeight.w700)),
                        Text('Dalam area · 24 m', style: T.small.copyWith(color: C.accent)),
                      ]),
                    ]),
                  ),
                ]),
              ),
              Expanded(
                child: Center(
                  child: FittedBox(
                    child: Column(children: [
                      FaceOval(
                        width: 240,
                        state: switch (phase) { _P.scan => OvalState.searching, _P.fail => OvalState.failed, _ => OvalState.valid },
                        child: phase == _P.verify || dark
                            ? ClipOval(
                                child: Container(
                                  width: 240,
                                  height: 312,
                                  color: dark ? Colors.black.withValues(alpha: .6) : Colors.white.withValues(alpha: .35),
                                  alignment: Alignment.center,
                                  child: dark ? null : const SizedBox(width: 44, height: 44, child: CircularProgressIndicator(strokeWidth: 3.5, color: Colors.white)),
                                ),
                              )
                            : null,
                      ),
                      gap12,
                      AnimatedSwitcher(
                        duration: const Duration(milliseconds: 220),
                        child: Container(
                          key: ValueKey(instruction),
                          padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 10),
                          decoration: BoxDecoration(color: phase == _P.fail ? C.dangerSubtle : Colors.white, borderRadius: BorderRadius.circular(999)),
                          child: Row(mainAxisSize: MainAxisSize.min, children: [
                            if (phase == _P.live) ...[Icon(_prompts[live.clamp(0, 2)].$1, size: 18, color: C.primary), gap8],
                            if (phase == _P.valid) ...[const Icon(LucideIcons.circleCheck, size: 18, color: C.accent), gap8],
                            Text(instruction, style: T.title.copyWith(color: phase == _P.fail ? C.dangerText : C.text)),
                          ]),
                        ),
                      ),
                    ]),
                  ),
                ),
              ),
            ]),
          ),
        ),
        AnimatedSize(
          duration: const Duration(milliseconds: 260),
          curve: Curves.easeOutCubic,
          child: Container(
            width: double.infinity,
            decoration: const BoxDecoration(color: Colors.white, borderRadius: BorderRadius.vertical(top: Radius.circular(28))),
            padding: EdgeInsets.fromLTRB(20, 22, 20, 16 + MediaQuery.paddingOf(context).bottom),
            child: phase == _P.fail ? _failPanel() : _stepPanel(),
          ),
        ),
      ]),
    );
  }

  Widget _stepPanel() => Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          Text('LANGKAH $stepNo DARI 3', style: T.overline.copyWith(color: C.primary)),
          const Spacer(),
          for (var i = 1; i <= 3; i++)
            AnimatedContainer(
              duration: const Duration(milliseconds: 200),
              margin: const EdgeInsets.only(left: 4),
              width: i == stepNo ? 20 : 8,
              height: 8,
              decoration: BoxDecoration(color: i <= stepNo ? C.primary : C.bg, borderRadius: BorderRadius.circular(8)),
            ),
        ]),
        gap4,
        Text(_out ? 'Absen Pulang' : 'Absen Masuk', style: T.h1),
        gap4,
        Text(
          switch (phase) {
            _P.live => 'Uji liveness [DL]: ikuti gerakan di layar agar kami tahu ini benar-benar kamu.',
            _P.verify => 'Mencocokkan wajah dengan data terdaftar...',
            _ => 'Lepas masker dan kacamata hitam. Pastikan wajah terang.',
          },
          style: T.body,
        ),
        if (phase == _P.live) ...[
          gap16,
          Row(children: [
            for (var i = 0; i < 3; i++) ...[
              if (i > 0) gap8,
              Expanded(
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 220),
                  padding: const EdgeInsets.symmetric(vertical: 10),
                  decoration: BoxDecoration(
                    color: i < live ? C.accentSubtle : (i == live ? C.bg : const Color(0xFFF2F5F4)),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Column(children: [
                    Icon(i < live ? LucideIcons.check : _prompts[i].$1, size: 18, color: i < live ? C.accent : C.text2),
                    gap4,
                    Text(_prompts[i].$2, style: T.small.copyWith(color: C.text)),
                  ]),
                ),
              ),
            ],
          ]),
        ],
      ]);

  Widget _failPanel() {
    final locked = failKind == Sim.wajah && app.faceAttempts >= 3;
    final (icon, title, body) = switch (failKind) {
      Sim.wajah when locked => (LucideIcons.lock, 'Batas percobaan tercapai', 'Wajah tidak cocok 3 kali. Absen dikunci 15 menit, hubungi HR untuk absen manual.'),
      Sim.wajah => (LucideIcons.scanFace, 'Wajah tidak cocok', 'Percobaan ${app.faceAttempts} dari 3. Lepas aksesori dan hadapkan wajah lurus ke kamera.'),
      Sim.liveness => (LucideIcons.eyeOff, 'Uji liveness gagal', 'Gerakan tidak terdeteksi. Ikuti petunjuk di layar dengan perlahan.'),
      Sim.gelap => (LucideIcons.moon, 'Cahaya kurang', 'Wajah terlalu gelap. Pindah ke tempat terang atau naikkan kecerahan layar.'),
      _ => (LucideIcons.users, 'Lebih dari satu wajah', 'Pastikan hanya kamu yang ada di dalam bingkai.'),
    };
    return Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
      Row(children: [
        IconTile(icon, tone: Tone.danger, size: 48),
        gap12,
        Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(title, style: T.h3), Text(body, style: T.caption)])),
      ]),
      gap16,
      Row(children: [
        Expanded(child: Btn('Laporkan Kendala', kind: BtnKind.light, onTap: () => reportIssue(context))),
        gap12,
        Expanded(child: Btn('Coba Lagi', icon: LucideIcons.refreshCw, onTap: locked ? null : run)),
      ]),
    ]);
  }
}

void reportIssue(BuildContext context) {
  final reasons = ['Wajah tidak terbaca', 'Lokasi tidak sesuai', 'Aplikasi error', 'Lainnya'];
  var pick = 0;
  showSheet(
    context,
    (ctx) => StatefulBuilder(
      builder: (ctx, set) => Padding(
        padding: const EdgeInsets.fromLTRB(20, 0, 20, 16),
        child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          const SheetHead('Laporkan kendala', subtitle: 'HR akan menghubungimu hari ini'),
          ChipWrap(reasons, (i) => i == pick, (i) => set(() => pick = i)),
          gap16,
          const Field(label: 'Ceritakan singkat', hint: 'Contoh: kamera buram walau sudah di tempat terang', maxLines: 3),
          gap20,
          Btn('Kirim Laporan', icon: LucideIcons.send, onTap: () {
            Navigator.pop(ctx);
            toast(context, 'Laporan terkirim ke HR. Absen manual bisa diproses hari ini.');
          }),
        ]),
      ),
    ),
  );
}

// ---------------------------------------------------------------- confirm + success

(String, Tone, String) _status(DateTime t) {
  if (!_out) {
    final late = app.lateMinutes(t);
    return late <= 0 ? ('Tepat Waktu', Tone.success, late == 0 ? 'Tepat saat shift mulai' : '${-late} menit sebelum shift') : ('Terlambat $late menit', Tone.warning, 'Shift mulai 08.00');
  }
  final diff = t.difference(DateTime(t.year, t.month, t.day, 17)).inMinutes;
  if (diff < 0) return ('Pulang Awal', Tone.warning, '${dur(Duration(minutes: -diff))} sebelum shift selesai');
  if (diff >= 30) return ('Lembur ${dur(Duration(minutes: diff))}', Tone.info, 'Shift selesai 17.00');
  return ('Tepat Waktu', Tone.success, 'Shift selesai 17.00');
}

class ClockConfirm extends StatefulWidget {
  const ClockConfirm({super.key});
  @override
  State<ClockConfirm> createState() => _ClockConfirmState();
}

class _ClockConfirmState extends State<ClockConfirm> {
  bool busy = false;
  final t = app.now;

  @override
  Widget build(BuildContext context) {
    final (label, tone, sub) = _status(t);
    return Screen(
      header: const TopBar('Konfirmasi Absen'),
      bottom: BottomActions([
        Btn(_out ? 'Konfirmasi Absen Pulang' : 'Konfirmasi Absen', busy: busy, onTap: () async {
          setState(() => busy = true);
          await Future.delayed(const Duration(milliseconds: 700));
          final wasOut = _out;
          app.recordClock();
          if (context.mounted) Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => ClockSuccess(out: wasOut, at: t)));
        }),
      ]),
      children: [
        gap8,
        AppCard(
          child: Column(children: [
            Row(children: [
              ClipRRect(borderRadius: BorderRadius.circular(16), child: const SizedBox(width: 76, height: 92, child: FaceArt())),
              gap16,
              Expanded(
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text(_out ? 'Jam pulang' : 'Jam masuk', style: T.caption),
                  Text(hm(t), style: T.numXL),
                  gap4,
                  Wrap(spacing: 8, runSpacing: 4, crossAxisAlignment: WrapCrossAlignment.center, children: [
                    Pill(_out ? label : (tone == Tone.success ? 'Hadir' : label), icon: tone == Tone.success ? LucideIcons.circleCheck : LucideIcons.clock, tone: tone),
                    Text(sub, style: T.small),
                  ]),
                ]),
              ),
            ]),
            gap16,
            const MapBox(height: 132, fenceSize: .4, pins: [MapPin(Alignment(.12, .1), C.primary, size: 14)]),
            gap8,
            Divided([
              const InfoRow('Lokasi', 'Kantor Pusat Yogyakarta', icon: LucideIcons.mapPin, sub: 'Dalam area · 24 m dari titik kantor'),
              InfoRow('Kecocokan wajah [DL]', '${app.faceScore.toStringAsFixed(1).replaceAll('.', ',')}%', icon: LucideIcons.scanFace, valueColor: C.accent),
              const InfoRow('Uji liveness', 'Lolos', icon: LucideIcons.eye, valueColor: C.accent),
              const InfoRow('Pemeriksaan keamanan', '6 dari 6 lolos', icon: LucideIcons.shieldCheck, valueColor: C.accent),
            ]),
          ]),
        ),
        if (_out && tone == Tone.warning) ...[
          gap12,
          const Banner2('Kamu pulang sebelum shift selesai', body: 'Jam kerja hari ini akan tercatat kurang dari 8 jam. Lanjutkan jika memang sudah izin ke atasan.'),
        ],
        if (_out) ...[
          gap12,
          AppCard(child: InfoRow('Total kerja hari ini', dur(t.difference(app.clockIn!)), icon: LucideIcons.timer)),
        ],
      ],
    );
  }
}

class ClockSuccess extends StatelessWidget {
  const ClockSuccess({super.key, required this.out, required this.at});
  final bool out;
  final DateTime at;

  @override
  Widget build(BuildContext context) {
    final late = app.lateMinutes(app.clockIn ?? at);
    final overtime = at.difference(DateTime(at.year, at.month, at.day, 17)).inMinutes;
    final (label, tone, icon) = out
        ? (overtime >= 30 ? ('Lembur ${dur(Duration(minutes: overtime))}', Tone.info, LucideIcons.clock) : (overtime < 0 ? ('Pulang Awal', Tone.warning, LucideIcons.clock) : ('Tepat Waktu', Tone.success, LucideIcons.circleCheck)))
        : (late <= 0 ? ('Hadir · Tepat Waktu', Tone.success, LucideIcons.circleCheck) : ('Terlambat $late menit', Tone.warning, LucideIcons.clock));
    return Screen(
      header: const SizedBox(height: 16),
      bottom: BottomActions([
        Btn('Lihat Riwayat', kind: BtnKind.light, onTap: () {
          Navigator.pop(context);
          Shell.open(0, const History());
        }),
        Btn('Kembali ke Beranda', onTap: () => Navigator.pop(context)),
      ]),
      children: [
        const SizedBox(height: 40),
        Center(
          child: TweenAnimationBuilder<double>(
            tween: Tween(begin: 0, end: 1),
            duration: const Duration(milliseconds: 700),
            curve: Curves.elasticOut,
            builder: (_, s, child) => Transform.scale(scale: .5 + .5 * s, child: child),
            child: Container(
              width: 140,
              height: 140,
              decoration: BoxDecoration(color: C.accent.withValues(alpha: .12), shape: BoxShape.circle),
              alignment: Alignment.center,
              child: Container(width: 100, height: 100, decoration: const BoxDecoration(color: C.accent, shape: BoxShape.circle), child: const Icon(LucideIcons.check, size: 52, color: Colors.white)),
            ),
          ),
        ),
        gap24,
        Text(out ? 'Absen pulang berhasil' : 'Absen masuk berhasil', style: T.h1, textAlign: TextAlign.center),
        gap8,
        Text(hm(at), style: T.numXL.copyWith(fontSize: 56, height: 1.1), textAlign: TextAlign.center),
        gap12,
        Center(child: Pill(label, icon: icon, tone: tone)),
        gap8,
        Text('Kantor Pusat Yogyakarta · ${tanggal(at)}', style: T.caption, textAlign: TextAlign.center),
        gap24,
        if (out)
          AppCard(
            child: Divided([
              InfoRow('Jam masuk', hm(app.clockIn!), icon: LucideIcons.logIn),
              InfoRow('Jam pulang', hm(at), icon: LucideIcons.logOut),
              InfoRow('Total kerja', dur(app.worked), icon: LucideIcons.timer),
              InfoRow('Lembur', overtime >= 30 ? dur(Duration(minutes: overtime)) : 'Tidak ada', icon: LucideIcons.clock, valueColor: overtime >= 30 ? C.infoText : null),
            ]),
          )
        else
          Banner2('Jangan lupa Clock-Out pukul 17.00', body: 'Kami akan mengingatkanmu 15 menit sebelum shift selesai.', tone: Tone.sage, icon: LucideIcons.bellRing),
      ],
    );
  }
}

// ---------------------------------------------------------------- B03 blocks

class Blocked extends StatefulWidget {
  const Blocked(this.sim, {super.key});
  final Sim sim;
  @override
  State<Blocked> createState() => _BlockedState();
}

class _BlockedState extends State<Blocked> {
  bool busy = false;

  Future<void> refresh() async {
    setState(() => busy = true);
    await Future.delayed(const Duration(milliseconds: 1100));
    if (!mounted) return;
    setState(() => busy = false);
    if (app.sim == widget.sim) {
      toast(context, 'Lokasi masih belum memenuhi syarat absen.', icon: LucideIcons.mapPinOff);
    } else {
      Navigator.pop(context);
      toast(context, 'Lokasi diperbarui. Silakan absen lagi.');
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = widget.sim;
    final (icon, tone, title, body) = switch (s) {
      Sim.fakeGps => (LucideIcons.shieldAlert, Tone.danger, 'Lokasi palsu terdeteksi', 'Aplikasi "Fake GPS Location" aktif di perangkatmu. Absen tidak bisa diproses selama aplikasi pemalsu lokasi berjalan.'),
      Sim.developer => (LucideIcons.bug, Tone.warning, 'Mode developer aktif', 'Opsi developer dengan mock location menyala. Matikan dulu agar lokasimu bisa diverifikasi.'),
      Sim.root => (LucideIcons.smartphone, Tone.danger, 'Perangkat tidak aman', 'Perangkat terdeteksi di-root atau berjalan di emulator. Demi keamanan data, absen tidak diizinkan dari perangkat ini.'),
      Sim.luarArea => (LucideIcons.mapPinOff, Tone.warning, 'Kamu di luar area kantor', 'Posisimu 1,2 km dari Kantor Pusat Yogyakarta. Absen hanya bisa dilakukan dalam radius 100 m.'),
      Sim.gpsLemah => (LucideIcons.crosshair, Tone.warning, 'Sinyal GPS lemah', 'Akurasi lokasi saat ini 68 m, batas maksimal 20 m. Kami belum bisa memastikan posisimu.'),
      _ => (LucideIcons.navigation, Tone.danger, 'Perjalanan tidak wajar', 'Lokasimu berpindah 80 km dalam 3 menit [DL]. Pola ini tidak mungkin terjadi secara normal.'),
    };
    final steps = switch (s) {
      Sim.fakeGps => ['Hapus atau nonaktifkan aplikasi Fake GPS Location', 'Matikan mock location di Opsi Developer', 'Mulai ulang perangkat, lalu coba absen lagi'],
      Sim.developer => ['Buka Pengaturan, lalu Opsi Developer', 'Matikan "Pilih aplikasi lokasi palsu"', 'Nonaktifkan Opsi Developer, lalu kembali ke HadirMetrik'],
      Sim.root => ['Gunakan perangkat terdaftar yang tidak dimodifikasi', 'Hubungi HR jika perangkatmu tidak pernah di-root'],
      Sim.luarArea => ['Datang ke area Kantor Pusat, lalu refresh lokasi', 'Sedang tugas di luar? Ajukan Dinas Luar agar lokasi absen disesuaikan'],
      Sim.gpsLemah => ['Pindah ke dekat jendela atau ruang terbuka', 'Aktifkan mode akurasi tinggi di pengaturan lokasi', 'Tunggu beberapa detik, lalu coba lagi'],
      _ => ['Matikan VPN atau aplikasi lokasi lain', 'Refresh lokasi di tempatmu berada sekarang', 'Jika ini keliru, HR akan meninjau catatanmu'],
    };
    final reported = s == Sim.fakeGps || s == Sim.root || s == Sim.lompat;
    return Screen(
      header: const TopBar('Absen tidak bisa diproses'),
      bottom: BottomActions(switch (s) {
        Sim.luarArea => [
            Btn('Ajukan Dinas Luar', kind: BtnKind.light, onTap: () {
              Navigator.pop(context);
              Shell.go(3);
            }),
            Btn('Refresh Lokasi', busy: busy, icon: LucideIcons.refreshCw, onTap: refresh),
          ],
        Sim.root => [Btn('Hubungi HR', icon: LucideIcons.phone, onTap: () => reportIssue(context))],
        Sim.developer => [Btn('Buka Pengaturan', icon: LucideIcons.settings, onTap: () => toast(context, 'Membuka Opsi Developer di pengaturan perangkat.', icon: LucideIcons.settings))],
        Sim.gpsLemah || Sim.lompat => [Btn('Refresh Lokasi', busy: busy, icon: LucideIcons.refreshCw, onTap: refresh)],
        _ => [Btn('Saya sudah memperbaikinya', busy: busy, onTap: refresh)],
      }),
      children: [
        gap16,
        Center(
          child: Container(
            width: 120,
            height: 120,
            decoration: BoxDecoration(color: tone.bg, shape: BoxShape.circle),
            alignment: Alignment.center,
            child: Container(width: 76, height: 76, decoration: BoxDecoration(color: tone == Tone.danger ? C.danger : C.warning, shape: BoxShape.circle), child: Icon(icon, size: 36, color: Colors.white)),
          ),
        ),
        gap20,
        Text(title, style: T.h1, textAlign: TextAlign.center),
        gap8,
        Text(body, style: T.body.copyWith(fontSize: 15, height: 1.45), textAlign: TextAlign.center),
        gap20,
        if (s == Sim.luarArea) ...[
          AppCard(
            padding: const EdgeInsets.all(8),
            child: Column(children: [
              const MapBox(height: 170, center: Alignment(-.35, -.1), fenceSize: .3, pins: [MapPin(Alignment(.7, .55), C.danger, size: 16)]),
              Padding(
                padding: const EdgeInsets.fromLTRB(8, 12, 8, 6),
                child: Row(children: [
                  const Expanded(child: Legend([('Area kantor', C.accent), ('Posisimu', C.danger)])),
                  Text('1,2 km dari Kantor Pusat', style: T.title.copyWith(color: C.warningText)),
                ]),
              ),
            ]),
          ),
          gap12,
        ],
        if (s == Sim.gpsLemah) ...[
          AppCard(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Row(children: [Text('Akurasi GPS', style: T.title), const Spacer(), Text('68 m', style: T.numM.copyWith(color: C.warningText))]),
              gap8,
              const Bar(.85, color: C.warning, height: 8),
              gap8,
              Text('Batas maksimal 20 m', style: T.small),
            ]),
          ),
          gap12,
        ],
        AppCard(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(s == Sim.luarArea ? 'Pilihanmu' : 'Cara memperbaiki', style: T.h3),
            gap12,
            for (var i = 0; i < steps.length; i++)
              Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Container(
                    width: 24,
                    height: 24,
                    alignment: Alignment.center,
                    decoration: const BoxDecoration(color: C.bg, shape: BoxShape.circle),
                    child: Text('${i + 1}', style: T.small.copyWith(color: C.primary, fontWeight: FontWeight.w700)),
                  ),
                  gap12,
                  Expanded(child: Padding(padding: const EdgeInsets.only(top: 2), child: Text(steps[i], style: T.bodyM))),
                ]),
              ),
          ]),
        ),
        if (reported) ...[
          gap12,
          Banner2('Percobaan ini tercatat dan dilaporkan ke HR.', tone: Tone.danger, icon: LucideIcons.info, body: '${tanggal(app.now)}, ${hm(app.now)} · ${s.desc}'),
        ],
      ],
    );
  }
}
