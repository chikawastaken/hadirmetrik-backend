import 'package:flutter/widgets.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';

import 'theme.dart';

// ---------------------------------------------------------------- format

/// `6582500` -> `Rp 6.582.500`, negatives -> `-Rp 50.000`.
String rp(int v, {bool sign = false}) {
  final s = _dots(v.abs());
  if (v < 0) return '-Rp $s';
  return sign ? '+Rp $s' : 'Rp $s';
}

String _dots(int v) => v.toString().replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (_) => '.');

String num0(int v) => _dots(v);

/// `07.58`
String hm(DateTime t) => '${t.hour.toString().padLeft(2, '0')}.${t.minute.toString().padLeft(2, '0')}';

/// `8 j 57 m`
String dur(Duration d) => '${d.inHours} j ${d.inMinutes.remainder(60)} m';

const hariPanjang = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
const hariPendek = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
const bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const bulanPendek = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

/// `Rabu, 7 Oktober 2026`
String tanggal(DateTime d) => '${hariPanjang[d.weekday - 1]}, ${d.day} ${bulan[d.month - 1]} ${d.year}';

/// `Sel, 6 Okt`
String tglPendek(DateTime d) => '${hariPendek[d.weekday - 1]}, ${d.day} ${bulanPendek[d.month - 1]}';

/// Spells out rupiah amounts for payslips: 6482500 -> "Enam juta empat ratus ...".
String terbilang(int n) {
  const s = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
  String f(int x) {
    if (x < 12) return s[x];
    if (x < 20) return '${f(x - 10)} belas';
    if (x < 100) return '${f(x ~/ 10)} puluh ${f(x % 10)}';
    if (x < 200) return 'seratus ${f(x - 100)}';
    if (x < 1000) return '${f(x ~/ 100)} ratus ${f(x % 100)}';
    if (x < 2000) return 'seribu ${f(x - 1000)}';
    if (x < 1000000) return '${f(x ~/ 1000)} ribu ${f(x % 1000)}';
    if (x < 1000000000) return '${f(x ~/ 1000000)} juta ${f(x % 1000000)}';
    return '${f(x ~/ 1000000000)} miliar ${f(x % 1000000000)}';
  }

  final out = f(n).trim().replaceAll(RegExp(r'\s+'), ' ');
  return '${out[0].toUpperCase()}${out.substring(1)} rupiah';
}

// ---------------------------------------------------------------- people

class Person {
  const Person(this.name, this.role, this.division, this.id, {this.status = Att.hadir, this.score, this.faceOk = true, this.location = 'Kantor Pusat'});
  final String name, role, division, id, location;
  final Att status;
  final int? score;
  final bool faceOk;
  String get initials => name.split(' ').take(2).map((w) => w[0]).join();
}

const rina = Person('Rina Pratiwi', 'Staf Gudang', 'Operasional', 'HM-0247', score: 92, location: 'Gudang Kalasan');

const employees = [
  Person('Putri Ramadhani', 'Staf Admin', 'Operasional', 'HM-0251', status: Att.menunggu, faceOk: false),
  rina,
  Person('Budi Santoso', 'Staf Gudang', 'Operasional', 'HM-0233', status: Att.terlambat, score: 84),
  Person('Dewi Lestari', 'Staf Gudang', 'Operasional', 'HM-0219', score: 95, location: 'Gudang Kalasan'),
  Person('Fajar Nugroho', 'Staf Pengiriman', 'Operasional', 'HM-0312', status: Att.absen, score: 61, location: 'Gudang Kalasan'),
  Person('Maya Anggraini', 'Sales Executive', 'Penjualan', 'HM-0188', status: Att.terlambat, score: 74),
  Person('Hendra Kusuma', 'Sales Supervisor', 'Penjualan', 'HM-0142', score: 80),
  Person('Agus Setiawan', 'IT Support', 'IT', 'HM-0205', score: 70),
  Person('Lina Marlina', 'Staf Keuangan', 'Keuangan', 'HM-0176', score: 88),
  Person('Dimas Ardiansyah', 'Supervisor Gudang', 'Operasional', 'HM-0098', score: 94),
  Person('Sari Wulandari', 'HR Officer', 'HR & GA', 'HM-0064', score: 97),
  Person('Putra Wijaya', 'Staf Gudang', 'Operasional', 'HM-0290', status: Att.cuti, score: 89),
];

Person personByName(String n) => employees.firstWhere((e) => e.name == n, orElse: () => rina);

// ---------------------------------------------------------------- attendance

enum Att {
  hadir('Hadir', LucideIcons.circleCheck, Tone.success, C.accent),
  terlambat('Terlambat', LucideIcons.clock, Tone.warning, C.warning),
  absen('Absen', LucideIcons.circleX, Tone.danger, C.danger),
  cuti('Cuti', LucideIcons.plane, Tone.info, C.info),
  menunggu('Menunggu', LucideIcons.hourglass, Tone.warning, C.warning);

  const Att(this.label, this.icon, this.tone, this.dot);
  final String label;
  final IconData icon;
  final Tone tone;
  final Color dot;
}

class AttDay {
  const AttDay(this.day, this.status, {this.month = 10, this.inT, this.outT, this.note, this.lateMin = 0, this.overtime = 0});
  final int day, month;
  final Att status;
  final String? inT, outT, note;
  final int lateMin, overtime;
  DateTime get date => DateTime(2026, month, day);
  Duration get worked {
    if (inT == null || outT == null) return Duration.zero;
    Duration p(String s) => Duration(hours: int.parse(s.substring(0, 2)), minutes: int.parse(s.substring(3)));
    return p(outT!) - p(inT!);
  }
}

const octDays = [
  AttDay(1, Att.hadir, inT: '07.51', outT: '17.03'),
  AttDay(2, Att.terlambat, inT: '08.17', outT: '17.02', lateMin: 17, note: 'Hujan deras di Jl. Kaliurang, sudah lapor ke Dimas Ardiansyah.'),
  AttDay(5, Att.hadir, inT: '07.54', outT: '19.06', overtime: 2),
  AttDay(6, Att.terlambat, inT: '08.09', outT: '17.06', lateMin: 9, note: 'Ban motor bocor di Jl. Magelang, sudah lapor ke Dimas Ardiansyah.'),
  AttDay(14, Att.cuti),
  AttDay(15, Att.cuti),
  AttDay(16, Att.cuti),
];

// Juli sampai September: hari kerja penuh, sesekali terlambat. 17 Agustus libur.
List<AttDay> pastDays(int month) => [
      for (var d = 1; d <= DateTime(2026, month + 1, 0).day; d++)
        if (DateTime(2026, month, d).weekday <= 5 && !(month == 8 && d == 17))
          (d * month) % 9 == 0
              ? AttDay(d, Att.terlambat, month: month, inT: '08.1${d % 10}', outT: '17.0${d % 10}', lateMin: 10 + d % 10)
              : AttDay(d, Att.hadir, month: month, inT: '07.${50 + d % 10}', outT: '17.0${d % 10}'),
    ];

// ---------------------------------------------------------------- tasks

enum Prio {
  tinggi('Tinggi', Tone.danger),
  sedang('Sedang', Tone.warning),
  rendah('Rendah', Tone.success);

  const Prio(this.label, this.tone);
  final String label;
  final Tone tone;
}

enum TaskStatus {
  proses('Dalam Proses', LucideIcons.refreshCw, Tone.info),
  review('Menunggu', LucideIcons.hourglass, Tone.warning),
  revisi('Revisi', LucideIcons.rotateCcw, Tone.warning),
  selesai('Selesai', LucideIcons.circleCheck, Tone.success),
  terlambat('Terlambat', LucideIcons.clockAlert, Tone.danger);

  const TaskStatus(this.label, this.icon, this.tone);
  final String label;
  final IconData icon;
  final Tone tone;
}

class CheckItem {
  CheckItem(this.label, [this.done = false]);
  final String label;
  bool done;
}

class Comment {
  const Comment(this.who, this.text, this.time);
  final String who, text, time;
}

class TaskItem {
  TaskItem({
    required this.title,
    required this.desc,
    required this.prio,
    required this.status,
    required this.deadline,
    required this.from,
    this.fromRole = 'HR',
    this.overdue = false,
    this.dueToday = false,
    this.progress = 0,
    this.attachments = const [],
    List<CheckItem>? checklist,
    List<Comment>? comments,
    this.created = 'Sen, 5 Okt 2026',
    this.remaining = '',
    this.revisionNote,
    this.assignees = const ['Rina Pratiwi'],
    this.division = 'Operasional',
  })  : checklist = checklist ?? [],
        comments = comments ?? [];

  final String title, desc, deadline, from, fromRole, created, remaining, division;
  final Prio prio;
  TaskStatus status;
  final bool overdue, dueToday;
  double progress;
  final List<(String, String)> attachments;
  final List<CheckItem> checklist;
  final List<Comment> comments;
  final List<String> assignees;
  String? revisionNote;
}

List<TaskItem> seedTasks() => [
      TaskItem(
        title: 'Laporan penjualan Toko Kaliurang',
        desc: 'Rekap penjualan harian Toko Kaliurang minggu 40 lengkap dengan retur dan stok sisa.',
        prio: Prio.tinggi,
        status: TaskStatus.proses,
        deadline: 'Sel, 6 Okt, 17.00',
        overdue: true,
        from: 'Sari Wulandari',
        progress: .4,
        attachments: [('Template laporan toko.xlsx', '36 KB')],
        remaining: 'Lewat 1 hari',
        checklist: [CheckItem('Kumpulkan nota harian', true), CheckItem('Hitung retur'), CheckItem('Isi template laporan')],
        comments: [
          const Comment('Sari Wulandari', 'Rina, laporan ini ditunggu Pak Hendra untuk rapat Kamis.', 'Rab 08.10'),
          const Comment('Kamu', 'Baik Bu, nota dari toko baru masuk pagi ini.', 'Rab 08.22'),
          const Comment('Sari Wulandari', 'Oke, kabari kalau ada kendala.', 'Rab 08.25'),
          const Comment('Hendra Kusuma', 'Retur minggu ini tolong dipisah per produk ya.', 'Rab 09.02'),
        ],
      ),
      TaskItem(
        title: 'Update stok gudang Kalasan',
        desc: 'Hitung ulang stok kardus, lakban dan bubble wrap di gudang Kalasan. Foto setiap rak, lalu catat selisihnya dengan data sistem di format stok Oktober.',
        prio: Prio.tinggi,
        status: TaskStatus.proses,
        deadline: 'Jum, 9 Okt, 17.00',
        from: 'Sari Wulandari',
        progress: .6,
        remaining: 'Sisa 2 hari',
        attachments: [('Format stok Oktober.xlsx', '48 KB'), ('Denah gudang Kalasan.pdf', '1,2 MB')],
        checklist: [CheckItem('Hitung stok kardus', true), CheckItem('Hitung lakban dan bubble wrap', true), CheckItem('Foto rak 1 sampai 5'), CheckItem('Isi format stok Oktober')],
        comments: [
          const Comment('Sari Wulandari', 'Jangan lupa foto rak nomor 3 dari dekat ya.', 'Rab 10.12'),
          const Comment('Kamu', 'Siap, Bu. Sore ini saya kirim.', 'Rab 10.20'),
          const Comment('Dimas Ardiansyah', 'Kunci gudang B ada di pos satpam.', 'Rab 10.31'),
        ],
        assignees: ['Rina Pratiwi', 'Dewi Lestari', 'Budi Santoso'],
      ),
      TaskItem(
        title: 'Rekap kunjungan klien minggu 40',
        desc: 'Rangkum kunjungan klien gudang minggu 40 beserta keluhan dan tindak lanjutnya.',
        prio: Prio.sedang,
        status: TaskStatus.proses,
        deadline: 'Hari ini, 21.00',
        dueToday: true,
        from: 'Bayu Saputra',
        fromRole: 'Supervisor',
        progress: .2,
        remaining: 'Sisa 13 jam',
        attachments: [('Daftar klien minggu 40.pdf', '210 KB')],
        checklist: [CheckItem('Kumpulkan catatan kunjungan', true), CheckItem('Tulis ringkasan keluhan'), CheckItem('Usulkan tindak lanjut')],
      ),
      TaskItem(
        title: 'Isi kuesioner K3 gudang',
        desc: 'Isi kuesioner keselamatan kerja untuk area gudang Kalasan sebelum audit bulan depan.',
        prio: Prio.sedang,
        status: TaskStatus.revisi,
        deadline: 'Hari ini, 17.00',
        dueToday: true,
        from: 'Sari Wulandari',
        progress: .8,
        remaining: 'Sisa 9 jam',
        revisionNote: 'Foto APAR di rak 3 masih buram, mohon diulang dari jarak dekat.',
        checklist: [CheckItem('Isi bagian A', true), CheckItem('Isi bagian B', true), CheckItem('Lampirkan foto APAR')],
      ),
      TaskItem(
        title: 'Foto display produk baru',
        desc: 'Foto display produk baru di area depan gudang untuk katalog bulan November.',
        prio: Prio.rendah,
        status: TaskStatus.proses,
        deadline: 'Sen, 12 Okt, 12.00',
        from: 'Dimas Ardiansyah',
        fromRole: 'Atasan',
        progress: .1,
        remaining: 'Sisa 5 hari',
        checklist: [CheckItem('Susun display'), CheckItem('Foto 10 produk')],
      ),
      TaskItem(
        title: 'Inventaris alat kebersihan',
        desc: 'Catat jumlah dan kondisi alat kebersihan di gudang Kalasan.',
        prio: Prio.sedang,
        status: TaskStatus.review,
        deadline: 'Rab, 7 Okt, 12.00',
        from: 'Sari Wulandari',
        progress: 1,
        attachments: [('Inventaris kebersihan.xlsx', '22 KB')],
        checklist: [CheckItem('Hitung alat', true), CheckItem('Foto kondisi', true)],
      ),
      TaskItem(
        title: 'Laporan pengiriman minggu 39',
        desc: 'Rekap pengiriman barang keluar minggu 39 dari gudang Kalasan.',
        prio: Prio.rendah,
        status: TaskStatus.review,
        deadline: 'Sel, 6 Okt, 17.00',
        from: 'Dimas Ardiansyah',
        fromRole: 'Atasan',
        progress: 1,
        checklist: [CheckItem('Rekap surat jalan', true), CheckItem('Cocokkan dengan sistem', true)],
      ),
      for (final t in [
        ('Stock opname akhir September', 'Rab, 30 Sep'),
        ('Pelabelan rak baru gudang B', 'Jum, 2 Okt'),
        ('Rekap retur pelanggan September', 'Sel, 29 Sep'),
        ('Briefing K3 tim gudang', 'Sen, 28 Sep'),
      ])
        TaskItem(
          title: t.$1,
          desc: 'Tugas selesai dan sudah disetujui HR.',
          prio: Prio.sedang,
          status: TaskStatus.selesai,
          deadline: t.$2,
          from: 'Sari Wulandari',
          progress: 1,
        ),
    ];

// ---------------------------------------------------------------- notifications

enum NCat {
  absensi('Absensi', LucideIcons.clock, Tone.info),
  tugas('Tugas', LucideIcons.clipboardCheck, Tone.sage),
  gaji('Gaji', LucideIcons.wallet, Tone.success),
  pengajuan('Pengajuan', LucideIcons.fileText, Tone.success),
  keamanan('Absensi', LucideIcons.shieldAlert, Tone.danger),
  info('Pengumuman', LucideIcons.megaphone, Tone.violet),
  pengingat('Absensi', LucideIcons.bellRing, Tone.warning),
  revisi('Tugas', LucideIcons.pencil, Tone.warning);

  const NCat(this.filter, this.icon, this.tone);
  final String filter;
  final IconData icon;
  final Tone tone;
}

class Notif {
  Notif(this.cat, this.title, this.body, this.time, this.group, {this.read = false});
  final NCat cat;
  final String title, body, time, group;
  bool read;
}

List<Notif> seedNotifs() => [
      Notif(NCat.keamanan, 'Peringatan keamanan', 'Login dari perangkat baru (Xiaomi Redmi 12) ditolak.', '08.31', 'HARI INI'),
      Notif(NCat.tugas, 'Tugas baru dari Sari Wulandari', 'Update stok gudang Kalasan · tenggat Jum, 9 Okt', '07.45', 'HARI INI'),
      Notif(NCat.gaji, 'Slip gaji September terbit', 'Take-home Rp 6.482.500. Ketuk untuk lihat rincian.', '07.00', 'HARI INI'),
      Notif(NCat.pengajuan, 'Cuti disetujui', 'Cuti Tahunan 14 sampai 16 Okt disetujui Dimas Ardiansyah.', '16.42', 'KEMARIN', read: true),
      Notif(NCat.revisi, 'Revisi diminta', 'Sari Wulandari: foto APAR di rak 3 masih buram, mohon diulang.', '15.10', 'KEMARIN', read: true),
      Notif(NCat.info, 'Pengumuman HR', 'Kantor tutup Jumat, 16 Okt untuk perawatan gedung.', '10.00', 'KEMARIN', read: true),
      Notif(NCat.pengingat, 'Jangan lupa Clock-Out', 'Kamu belum Clock-Out pada Sen, 5 Okt pukul 17.00.', 'Sen, 5 Okt', 'SEBELUMNYA', read: true),
    ];

// ---------------------------------------------------------------- salary

class PayLine {
  const PayLine(this.label, this.note, this.amount);
  final String label, note;
  final int amount;
}

class Payslip {
  const Payslip(this.month, this.paid, this.earnings, this.deductions, {this.final_ = true});
  final String month, paid;
  final List<PayLine> earnings, deductions;
  final bool final_;
  int get gross => earnings.fold(0, (a, b) => a + b.amount);
  int get cut => deductions.fold(0, (a, b) => a + b.amount);
  int get net => gross - cut;
}

const payOct = Payslip('Oktober 2026', 'Dijadwalkan 23 Okt 2026', [
  PayLine('Gaji Pokok', 'Sesuai kontrak', 5200000),
  PayLine('Tunjangan Transport', '22 hari kerja x Rp 25.000', 550000),
  PayLine('Tunjangan Makan', '22 hari kerja x Rp 30.000', 660000),
  PayLine('Lembur', '6 jam x Rp 45.000', 270000),
  PayLine('Bonus Kinerja Q3', 'Dibayarkan bersama gaji Oktober', 300000),
], [
  PayLine('BPJS Kesehatan', '1% dari gaji pokok', 52000),
  PayLine('BPJS Ketenagakerjaan', 'JHT 2% + JP 1%', 156000),
  PayLine('PPh 21', 'Tarif efektif bulanan kategori A', 139500),
  PayLine('Keterlambatan', 'Terlambat 2x: 2 dan 6 Okt', 50000),
  PayLine('Absen tanpa keterangan', 'Tidak ada bulan ini', 0),
], final_: false);

const paySep = Payslip('September 2026', 'Dibayar 25 Sep 2026', [
  PayLine('Gaji Pokok', 'Sesuai kontrak', 5200000),
  PayLine('Tunjangan Transport', '21 hari kerja x Rp 25.000', 525000),
  PayLine('Tunjangan Makan', '21 hari kerja x Rp 30.000', 630000),
  PayLine('Lembur', '8 jam x Rp 45.000', 360000),
  PayLine('Insentif Kehadiran', 'Hadir penuh 21 hari', 175000),
], [
  PayLine('BPJS Kesehatan', '1% dari gaji pokok', 52000),
  PayLine('BPJS Ketenagakerjaan', 'JHT 2% + JP 1%', 156000),
  PayLine('PPh 21', 'Tarif efektif bulanan kategori A', 124500),
  PayLine('Keterlambatan', 'Terlambat 3x', 75000),
]);

const payAug = Payslip('Agustus 2026', 'Dibayar 25 Agu 2026', [
  PayLine('Gaji Pokok', 'Sesuai kontrak', 5200000),
  PayLine('Tunjangan Transport', '21 hari kerja x Rp 25.000', 525000),
  PayLine('Tunjangan Makan', '21 hari kerja x Rp 30.000', 630000),
  PayLine('Lembur', '5 jam x Rp 45.000', 225000),
  PayLine('Insentif Kehadiran', 'Hadir penuh 21 hari', 175000),
], [
  PayLine('BPJS Kesehatan', '1% dari gaji pokok', 52000),
  PayLine('BPJS Ketenagakerjaan', 'JHT 2% + JP 1%', 156000),
  PayLine('PPh 21', 'Tarif efektif bulanan kategori A', 127000),
  PayLine('Keterlambatan', 'Terlambat 1x', 25000),
]);

const payJul = Payslip('Juli 2026', 'Dibayar 24 Jul 2026', [
  PayLine('Gaji Pokok', 'Sesuai kontrak', 5200000),
  PayLine('Tunjangan Transport', '23 hari kerja x Rp 25.000', 575000),
  PayLine('Tunjangan Makan', '23 hari kerja x Rp 30.000', 690000),
  PayLine('Lembur', '4 jam x Rp 45.000', 180000),
  PayLine('Insentif Kehadiran', 'Hadir penuh 23 hari', 175000),
], [
  PayLine('BPJS Kesehatan', '1% dari gaji pokok', 52000),
  PayLine('BPJS Ketenagakerjaan', 'JHT 2% + JP 1%', 156000),
  PayLine('PPh 21', 'Tarif efektif bulanan kategori A', 92000),
]);

// ---------------------------------------------------------------- leave

enum LeaveType {
  izin('Izin', 'Keperluan pribadi, maksimal 2 hari', LucideIcons.clock, Tone.insight),
  tahunan('Cuti Tahunan', 'Memotong kuota cuti tahunan', LucideIcons.plane, Tone.success),
  sakit('Sakit', 'Surat dokter wajib jika lebih dari 1 hari', LucideIcons.heartPulse, Tone.danger),
  dinas('Dinas Luar', 'Tugas kantor di luar lokasi, lampirkan surat tugas', LucideIcons.briefcase, Tone.info),
  khusus('Cuti Khusus', 'Menikah, duka atau melahirkan', LucideIcons.heart, Tone.violet);

  const LeaveType(this.label, this.desc, this.icon, this.tone);
  final String label, desc;
  final IconData icon;
  final Tone tone;
}

enum LeaveStatus {
  menunggu('Menunggu', LucideIcons.hourglass, Tone.warning),
  disetujui('Disetujui', LucideIcons.circleCheck, Tone.success),
  ditolak('Ditolak', LucideIcons.circleX, Tone.danger);

  const LeaveStatus(this.label, this.icon, this.tone);
  final String label;
  final IconData icon;
  final Tone tone;
}

class LeaveReq {
  LeaveReq({
    required this.type,
    required this.range,
    required this.status,
    required this.note,
    required this.steps,
    this.reason = '',
    this.who = rina,
    this.extra,
    this.file,
    this.conflict,
  });
  final LeaveType type;
  final String range, reason;
  String note;
  LeaveStatus status;
  final List<(String, String, Tone?)> steps;
  final Person who;
  final String? extra, file, conflict;
}

List<LeaveReq> seedLeaves() => [
      LeaveReq(
        type: LeaveType.tahunan,
        range: '14 - 16 Okt 2026, 3 hari',
        status: LeaveStatus.disetujui,
        note: 'Catatan HR: disetujui, serah terima ke Budi Santoso sudah dicatat. Sisa cuti 9 hari.',
        steps: [('Diajukan Rina Pratiwi', '1 Okt, 10.05', null), ('Atasan · Dimas Ardiansyah', 'Disetujui 1 Okt', Tone.success), ('HR · Sari Wulandari', 'Disetujui 2 Okt', Tone.success)],
      ),
      LeaveReq(
        type: LeaveType.izin,
        range: '28 Sep 2026, setengah hari',
        status: LeaveStatus.ditolak,
        note: 'Ditolak Dimas Ardiansyah: bertepatan dengan stok opname bulanan. Silakan ajukan tanggal lain.',
        steps: [('Diajukan Rina Pratiwi', '24 Sep, 14.10', null), ('Atasan · Dimas Ardiansyah', 'Ditolak 25 Sep', Tone.danger), ('HR · Sari Wulandari', 'Tidak diteruskan', null)],
      ),
    ];

List<LeaveReq> seedApprovals() => [
      LeaveReq(
        type: LeaveType.tahunan,
        who: personByName('Maya Anggraini'),
        range: 'Sen, 19 sampai Rab, 21 Okt · 3 hari',
        status: LeaveStatus.menunggu,
        reason: 'Menghadiri pernikahan kakak di Surabaya.',
        note: '',
        steps: const [],
        extra: 'Sisa cuti 7 dari 12 hari, setelah ini 4',
        conflict: '2 orang di Penjualan juga cuti tanggal ini',
      ),
      LeaveReq(
        type: LeaveType.sakit,
        who: rina,
        range: 'Sel, 6 sampai Kam, 8 Okt · 3 hari',
        status: LeaveStatus.menunggu,
        reason: 'Demam tinggi dan radang tenggorokan sejak Senin malam. Dokter menyarankan istirahat 3 hari.',
        note: '',
        steps: [('Diajukan Rina Pratiwi', 'Sel, 6 Okt, 07.12', null), ('Atasan · Dimas Ardiansyah', 'Menunggu', Tone.warning), ('HR · Sari Wulandari', 'Menunggu', null)],
        file: 'Surat dokter Klinik Medika.jpg',
        extra: 'Sakit tidak memotong cuti tahunan',
      ),
      LeaveReq(
        type: LeaveType.izin,
        who: personByName('Budi Santoso'),
        range: 'Jum, 9 Okt · setengah hari pagi',
        status: LeaveStatus.menunggu,
        reason: 'Mengurus perpanjangan SIM di Polresta Sleman.',
        note: '',
        steps: const [],
        extra: 'Izin bulan ini 1 dari 2 kali',
      ),
      LeaveReq(
        type: LeaveType.dinas,
        who: personByName('Hendra Kusuma'),
        range: 'Sel, 13 sampai Rab, 14 Okt · 2 hari',
        status: LeaveStatus.menunggu,
        reason: 'Pameran produk di JEC bersama tim Penjualan.',
        note: '',
        steps: const [],
        file: 'Surat tugas pameran JEC.pdf',
        extra: 'Lokasi absen: JEC Yogyakarta',
      ),
      LeaveReq(
        type: LeaveType.khusus,
        who: personByName('Agus Setiawan'),
        range: 'Kam, 22 sampai Jum, 23 Okt · 2 hari',
        status: LeaveStatus.menunggu,
        reason: 'Istri melahirkan anak kedua.',
        note: '',
        steps: const [],
        extra: 'Cuti khusus melahirkan maksimal 3 hari',
      ),
      LeaveReq(
        type: LeaveType.izin,
        who: personByName('Putri Ramadhani'),
        range: 'Sen, 12 Okt · setengah hari siang',
        status: LeaveStatus.menunggu,
        reason: 'Mengantar ibu kontrol ke RS Panti Rapih.',
        note: '',
        steps: const [],
        extra: 'Izin bulan ini 0 dari 2 kali',
      ),
      LeaveReq(
        type: LeaveType.tahunan,
        who: personByName('Lina Marlina'),
        range: 'Sen, 19 sampai Sel, 20 Okt · 2 hari',
        status: LeaveStatus.menunggu,
        reason: 'Acara keluarga di Magelang.',
        note: '',
        steps: const [],
        extra: 'Sisa cuti 10 dari 12 hari, setelah ini 8',
      ),
      LeaveReq(
        type: LeaveType.tahunan,
        who: personByName('Dewi Lestari'),
        range: 'Kam, 1 sampai Jum, 2 Okt · 2 hari',
        status: LeaveStatus.disetujui,
        reason: 'Pulang kampung ke Klaten.',
        note: 'Disetujui Sari Wulandari, 28 Sep',
        steps: const [],
      ),
      LeaveReq(
        type: LeaveType.izin,
        who: personByName('Fajar Nugroho'),
        range: 'Rab, 30 Sep · sehari penuh',
        status: LeaveStatus.ditolak,
        reason: 'Urusan keluarga.',
        note: 'Ditolak: alasan kurang jelas, izin bulan ini sudah 2 kali',
        steps: const [],
      ),
    ];

// ---------------------------------------------------------------- HR monitor

class Incident {
  const Incident(this.who, this.time, this.place, this.reason, this.risk, this.signals, {this.device = 'Xiaomi Redmi Note 12', this.os = 'Android 13'});
  final Person who;
  final String time, place, reason, device, os;
  final double risk;
  final List<(IconData, String, String)> signals;
  bool get high => risk >= .8;
}

final seedIncidents = [
  Incident(personByName('Fajar Nugroho'), '08.47', 'Gudang Kalasan', 'Aplikasi Fake GPS terdeteksi saat clock-in', .93, const [
    (LucideIcons.mapPinOff, 'Aplikasi Fake GPS terdeteksi', 'Aplikasi pemalsu lokasi aktif saat clock-in'),
    (LucideIcons.bug, 'Mock location aktif', 'Opsi developer mengizinkan lokasi tiruan'),
    (LucideIcons.navigation, 'Perpindahan tidak wajar', '23 km dalam 3 menit dari lokasi sebelumnya'),
    (LucideIcons.scanFace, 'Wajah cocok 97%', 'Pemilik akun sesuai, uji liveness lolos'),
  ]),
  Incident(personByName('Agus Setiawan'), '07.58', 'Kantor Pusat', 'Mock location aktif, perangkat di-root', .81, const [
    (LucideIcons.bug, 'Mock location aktif', 'Opsi developer mengizinkan lokasi tiruan'),
    (LucideIcons.shieldAlert, 'Perangkat di-root', 'Akses root terdeteksi pada sistem'),
    (LucideIcons.scanFace, 'Wajah cocok 95%', 'Pemilik akun sesuai, uji liveness lolos'),
  ], device: 'Poco X5 Pro', os: 'Android 14'),
  Incident(personByName('Lina Marlina'), '08.15', 'Kantor Pusat', 'Perpindahan mustahil: Kalasan ke Kantor Pusat, 18 km dalam 4 menit', .76, const [
    (LucideIcons.navigation, 'Perpindahan tidak wajar', '18 km dalam 4 menit dari lokasi sebelumnya'),
    (LucideIcons.scanFace, 'Wajah cocok 81%', 'Skor di bawah rata-rata, cahaya kurang'),
  ], device: 'Samsung Galaxy A34', os: 'Android 14'),
];

class LiveEntry {
  const LiveEntry(this.who, this.time, this.place, this.state, {this.distance});
  final Person who;
  final String time, place, state;
  final String? distance;
}

final liveEntries = [
  LiveEntry(personByName('Hendra Kusuma'), '08.31', 'Gudang Kalasan', 'luar', distance: '1,4 km dari Gudang Kalasan'),
  LiveEntry(personByName('Budi Santoso'), '08.02', 'Kantor Pusat', 'dalam'),
  LiveEntry(personByName('Dewi Lestari'), '07.54', 'Gudang Kalasan', 'dalam'),
  LiveEntry(personByName('Maya Anggraini'), '08.12', 'Kantor Pusat', 'dalam'),
  LiveEntry(personByName('Dimas Ardiansyah'), '07.41', 'Gudang Kalasan', 'dalam'),
  LiveEntry(personByName('Sari Wulandari'), '07.38', 'Kantor Pusat', 'dalam'),
];

// ---------------------------------------------------------------- HR tasks & payroll

List<TaskItem> seedHrTasks(List<TaskItem> mine) => [
      TaskItem(
        title: 'Laporan penjualan Toko Kaliurang',
        desc: 'Rekap penjualan harian Toko Kaliurang minggu 40.',
        prio: Prio.tinggi,
        status: TaskStatus.terlambat,
        deadline: 'Lewat 1 hari · Sel, 6 Okt',
        overdue: true,
        from: 'Sari Wulandari',
        progress: .4,
        assignees: ['Maya Anggraini', 'Fajar Nugroho'],
        division: 'Penjualan',
      ),
      mine[1],
      TaskItem(
        title: 'Rekap kunjungan klien minggu 40',
        desc: 'Rangkum kunjungan klien minggu 40.',
        prio: Prio.sedang,
        status: TaskStatus.proses,
        deadline: 'Hari ini, 21.00',
        dueToday: true,
        from: 'Sari Wulandari',
        progress: .2,
        assignees: ['Hendra Kusuma'],
        division: 'Penjualan',
      ),
      TaskItem(
        title: 'Rekonsiliasi kas kecil September',
        desc: 'Cocokkan kas kecil September dengan bukti transaksi.',
        prio: Prio.tinggi,
        status: TaskStatus.terlambat,
        deadline: 'Lewat 2 hari · Sen, 5 Okt',
        overdue: true,
        from: 'Sari Wulandari',
        progress: .7,
        assignees: ['Lina Marlina'],
        division: 'Keuangan',
      ),
      TaskItem(
        title: 'Migrasi email kantor ke domain baru',
        desc: 'Pindahkan seluruh akun email ke domain sinarmetrik.co.id.',
        prio: Prio.sedang,
        status: TaskStatus.proses,
        deadline: 'Jum, 16 Okt, 17.00',
        from: 'Sari Wulandari',
        progress: .55,
        assignees: ['Agus Setiawan'],
        division: 'IT',
      ),
      TaskItem(
        title: 'Pelatihan K3 karyawan baru',
        desc: 'Jadwalkan pelatihan K3 untuk 4 karyawan baru Oktober.',
        prio: Prio.rendah,
        status: TaskStatus.selesai,
        deadline: 'Jum, 2 Okt',
        from: 'Sari Wulandari',
        progress: 1,
        assignees: ['Dimas Ardiansyah'],
      ),
    ];

class PayRow {
  PayRow(this.who, this.takeHome, this.flags, {this.reviewed = false});
  final Person who;
  int takeHome;
  final List<(String, Tone, IconData)> flags;
  bool reviewed;
}

List<PayRow> seedPayroll() => [
      PayRow(rina, 6697500, const [('Potongan keterlambatan', Tone.warning, LucideIcons.triangleAlert), ('Ada penyesuaian', Tone.success, LucideIcons.pencil)]),
      PayRow(personByName('Budi Santoso'), 6214000, const [('Potongan keterlambatan', Tone.warning, LucideIcons.triangleAlert), ('Lembur > 20 jam', Tone.info, LucideIcons.clock)]),
      PayRow(personByName('Hendra Kusuma'), 9845000, const [('Lembur > 20 jam', Tone.info, LucideIcons.clock)]),
      PayRow(personByName('Agus Setiawan'), 7120000, const [('Ada penyesuaian', Tone.success, LucideIcons.pencil)]),
      PayRow(personByName('Lina Marlina'), 7460000, const [], reviewed: true),
      PayRow(personByName('Dewi Lestari'), 6390000, const [], reviewed: true),
      PayRow(personByName('Maya Anggraini'), 8015000, const [], reviewed: true),
      PayRow(personByName('Dimas Ardiansyah'), 9310000, const [], reviewed: true),
    ];
