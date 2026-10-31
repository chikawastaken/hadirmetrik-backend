import 'package:flutter/foundation.dart';

import 'data.dart';
import 'models/employee_profile.dart';

enum Role { karyawan, hr }

/// Demo scenarios for states that a real device would produce on its own
/// (outside geofence, fake GPS, liveness failure, ...). Picked from the
/// "Mode demo" sheet (long-press the clock on Beranda).
enum Sim {
  normal('Normal', 'Semua pemeriksaan lolos'),
  luarArea('Di luar area kantor', '1,2 km dari Kantor Pusat'),
  fakeGps('Lokasi palsu', 'Aplikasi Fake GPS aktif'),
  developer('Mode developer aktif', 'Mock location menyala'),
  root('Perangkat di-root', 'Atau berjalan di emulator'),
  gpsLemah('GPS lemah', 'Akurasi 68 m'),
  lompat('Perjalanan tidak wajar', 'Pindah 80 km dalam 3 menit'),
  wajah('Wajah tidak cocok', 'Gagal mencocokkan wajah'),
  liveness('Liveness gagal', 'Gerakan tidak terdeteksi'),
  gelap('Cahaya kurang', 'Wajah terlalu gelap'),
  banyakWajah('Lebih dari satu wajah', 'Ada orang lain di bingkai'),
  cuti('Hari cuti', 'Hari ini kamu cuti');

  const Sim(this.label, this.desc);
  final String label, desc;
}

class AppState extends ChangeNotifier {
  Role role = Role.karyawan;
  bool onboarded = false;
  bool faceEnrolled = false;
  Sim sim = Sim.normal;
  bool hideSalary = true;
  EmployeeProfile? profile;

  // Demo clock: the prototype day is Rabu, 7 Oktober 2026, starting 07.52.
  DateTime _base = DateTime(2026, 10, 7, 7, 52);
  DateTime _anchor = DateTime.now();
  DateTime get now => _base.add(DateTime.now().difference(_anchor));
  void setTime(int h, int m) {
    _base = DateTime(2026, 10, 7, h, m);
    _anchor = DateTime.now();
    notifyListeners();
  }

  DateTime? clockIn, clockOut;
  double faceScore = 97.4;
  int faceAttempts = 0;

  bool get insideArea => sim != Sim.luarArea;
  bool get onLeave => sim == Sim.cuti;
  bool get dayDone => clockOut != null;
  Duration get worked => clockIn == null ? Duration.zero : (clockOut ?? now).difference(clockIn!);

  /// Minutes relative to the 08.00 shift start; negative = early.
  int lateMinutes(DateTime t) => t.difference(DateTime(t.year, t.month, t.day, 8)).inMinutes;

  final tasks = seedTasks();
  late final hrTasks = seedHrTasks(tasks);
  final notifs = seedNotifs();
  final approvals = seedApprovals();
  late final leaves = [approvals[1], ...seedLeaves()];
  final incidents = [...seedIncidents];
  final payroll = seedPayroll();
  final newEmployees = <Person>[];
  int payrollStep = 1; // 0 Draft, 1 Ditinjau, 2 Disetujui, 3 Dipublikasikan

  int get unread => notifs.where((n) => !n.read).length;

  void touch() => notifyListeners();

  Future<void> fetchProfile() async {
    try {
      final res = await Api.getProfile();
      profile = EmployeeProfile.fromJson(res);
      notifyListeners();
    } catch (_) {}
  }

  void login(Role r) {
    role = r;
    notifyListeners();
  }

  void logout() {
    clockIn = null;
    clockOut = null;
    sim = Sim.normal;
    notifyListeners();
  }

  void setSim(Sim s) {
    sim = s;
    notifyListeners();
  }

  void recordClock() {
    final t = now;
    if (clockIn == null) {
      clockIn = t;
      notifs.insert(0, Notif(NCat.absensi, 'Clock-in tercatat ${hm(t)}', 'Kantor Pusat Yogyakarta · wajah dan lokasi valid', hm(t), 'HARI INI'));
    } else {
      clockOut = t;
      notifs.insert(0, Notif(NCat.absensi, 'Clock-out tercatat ${hm(t)}', 'Total kerja hari ini ${dur(worked)}', hm(t), 'HARI INI'));
    }
    notifyListeners();
  }

  void toggleSalary() {
    hideSalary = !hideSalary;
    notifyListeners();
  }

  List<bool> markAllRead() {
    final before = [for (final n in notifs) n.read];
    for (final n in notifs) {
      n.read = true;
    }
    notifyListeners();
    return before;
  }

  void restoreRead(List<bool> before) {
    for (var i = 0; i < before.length && i < notifs.length; i++) {
      notifs[i].read = before[i];
    }
    notifyListeners();
  }
}

final app = AppState();
