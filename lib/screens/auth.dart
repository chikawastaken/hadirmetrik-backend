import 'dart:async';

import 'package:flutter/material.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';

import '../api.dart';
import '../data.dart';
import '../deeplink.dart';
import '../main.dart';
import '../state.dart';
import '../theme.dart';
import '../visuals.dart';
import '../widgets.dart';

Route<void> fadeRoute(Widget page) => PageRouteBuilder(
      transitionDuration: const Duration(milliseconds: 380),
      pageBuilder: (_, _, _) => page,
      transitionsBuilder: (_, a, _, child) => FadeTransition(opacity: CurvedAnimation(parent: a, curve: Curves.easeOut), child: child),
    );

/// Replace the whole stack (login -> app, logout -> login).
void resetTo(BuildContext c, Widget page) => Navigator.of(c, rootNavigator: true).pushAndRemoveUntil(fadeRoute(page), (_) => false);

void enterApp(BuildContext c) => resetTo(c, const Shell());

/// Where to go once a session exists and the password is permanent.
void afterAuth(BuildContext c) {
  if (DeepLink.hasPending) {
    Navigator.of(c).pushReplacement(fadeRoute(const InvitationAccept()));
  } else if (app.role == Role.hr || app.onboarded) {
    enterApp(c);
  } else {
    Navigator.of(c).pushReplacement(fadeRoute(const Onboarding()));
  }
}

// ---------------------------------------------------------------- A01 splash

class Splash extends StatelessWidget {
  const Splash({super.key});
  @override
  Widget build(BuildContext context) => Scaffold(
        backgroundColor: C.primary,
        body: Stack(fit: StackFit.expand, children: [
          Image.asset('assets/images/splash_bg.png', fit: BoxFit.cover),
          SafeArea(
            child: Column(children: [
              const Spacer(),
              TweenAnimationBuilder<double>(
                tween: Tween(begin: 0, end: 1),
                duration: const Duration(milliseconds: 900),
                curve: Curves.easeOutCubic,
                builder: (_, t, child) => Opacity(opacity: t, child: Transform.translate(offset: Offset(0, 16 * (1 - t)), child: child)),
                child: Column(children: [
                  const Logo(size: 96),
                  gap12,
                  const Text('HadirMetrik', style: TextStyle(fontFamily: 'Momo', fontSize: 24, color: C.mid)),
                  gap4,
                  Text.rich(
                    TextSpan(children: [
                      const TextSpan(text: 'Dari '),
                      TextSpan(text: 'absensi', style: TextStyle(fontSize: 9, color: Colors.white.withValues(alpha: .55))),
                      const TextSpan(text: ' kehadiran menjadi keputusan'),
                    ]),
                    style: const TextStyle(fontFamily: 'Momo', fontSize: 12, color: Colors.white),
                  ),
                ]),
              ),
              const Spacer(),
              Padding(
                padding: const EdgeInsets.fromLTRB(32, 0, 32, 32),
                child: Material(
                  color: C.bg,
                  shape: const StadiumBorder(),
                  child: InkWell(
                    customBorder: const StadiumBorder(),
                    onTap: () => Navigator.of(context).pushReplacement(fadeRoute(const Login())),
                    child: SizedBox(
                      height: 72,
                      child: Row(children: [
                        const SizedBox(width: 32),
                        Expanded(child: Text('Get Started', style: T.h2.copyWith(color: C.primary, fontWeight: FontWeight.w700))),
                        Container(
                          width: 56,
                          height: 56,
                          margin: const EdgeInsets.only(right: 8),
                          decoration: const BoxDecoration(color: C.primary, shape: BoxShape.circle),
                          child: const Icon(LucideIcons.arrowRight, color: Colors.white),
                        ),
                      ]),
                    ),
                  ),
                ),
              ),
            ]),
          ),
        ]),
      );
}

// ---------------------------------------------------------------- A02 login

class _AuthFrame extends StatelessWidget {
  const _AuthFrame({required this.title, required this.subtitle, required this.children});
  final String title, subtitle;
  final List<Widget> children;
  @override
  Widget build(BuildContext context) => Scaffold(
        backgroundColor: C.primary,
        body: CustomScrollView(slivers: [
          SliverToBoxAdapter(
            child: SafeArea(
              bottom: false,
              child: Padding(
                padding: const EdgeInsets.fromLTRB(24, 16, 24, 28),
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Row(children: [
                    const Logo(size: 44),
                    gap12,
                    const Text('HadirMetrik', style: TextStyle(fontFamily: 'Momo', fontSize: 22, color: Colors.white)),
                  ]),
                  const SizedBox(height: 40),
                  Text(title, style: T.display.copyWith(color: Colors.white)),
                  gap4,
                  Text(subtitle, style: T.bodyM.copyWith(color: Colors.white.withValues(alpha: .85))),
                ]),
              ),
            ),
          ),
          SliverFillRemaining(
            hasScrollBody: false,
            child: Container(
              decoration: const BoxDecoration(color: Colors.white, borderRadius: BorderRadius.vertical(top: Radius.circular(32))),
              padding: EdgeInsets.fromLTRB(24, 28, 24, 16 + MediaQuery.paddingOf(context).bottom),
              child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: children),
            ),
          ),
        ]),
      );
}

class Login extends StatefulWidget {
  const Login({super.key});
  @override
  State<Login> createState() => _LoginState();
}

class _LoginState extends State<Login> {
  final id = TextEditingController(), pass = TextEditingController();
  bool hide = true, busy = false, tried = false;

  @override
  void dispose() {
    id.dispose();
    pass.dispose();
    super.dispose();
  }

  String? get idErr => tried && id.text.trim().isEmpty ? 'ID karyawan atau email wajib diisi' : null;
  String? get passErr => tried && pass.text.length < 6 ? 'Kata sandi minimal 6 karakter' : null;

  Future<void> submit() async {
    setState(() => tried = true);
    if (idErr != null || passErr != null) return;
    setState(() => busy = true);
    try {
      final data = await Api.login(id.text.trim(), pass.text);
      if (!mounted) return;
      final account = data['user'] as Map<String, dynamic>?;
      final role = account?['role'] as String?;
      final hr = role == 'hr' || role == 'admin';
      final mustChange = data['must_change_password'] == true || account?['must_change_password'] == true;
      app.login(hr ? Role.hr : Role.karyawan);
      if (mustChange) {
        Navigator.of(context).pushReplacement(fadeRoute(const ChangePassword()));
      } else {
        afterAuth(context);
      }
    } on ApiError catch (e) {
      if (!mounted) return;
      toast(context, e.message, icon: LucideIcons.triangleAlert);
    } catch (_) {
      if (!mounted) return;
      toast(context, 'Tidak bisa terhubung ke server', icon: LucideIcons.triangleAlert);
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  @override
  Widget build(BuildContext context) => _AuthFrame(
        title: 'Selamat Datang',
        subtitle: 'Masuk untuk mulai absen hari ini',
        children: [
          Field(
            label: 'ID Karyawan / Email',
            hint: 'Contoh: HM-20418',
            icon: LucideIcons.user,
            controller: id,
            error: idErr,
            onChanged: (_) => tried ? setState(() {}) : null,
          ),
          gap16,
          Field(
            label: 'Kata Sandi',
            hint: 'Masukkan kata sandi',
            icon: LucideIcons.lock,
            controller: pass,
            obscure: hide,
            error: passErr,
            onChanged: (_) => tried ? setState(() {}) : null,
            suffix: IconButton(
              tooltip: hide ? 'Tampilkan kata sandi' : 'Sembunyikan kata sandi',
              icon: Icon(hide ? LucideIcons.eye : LucideIcons.eyeOff, size: 20, color: C.text3),
              onPressed: () => setState(() => hide = !hide),
            ),
          ),
          Align(
            alignment: Alignment.centerRight,
            child: TextButton(onPressed: () => _forgot(context), child: Text('Lupa kata sandi?', style: T.title.copyWith(color: C.primary))),
          ),
          gap8,
          Btn('Masuk', busy: busy, onTap: submit),
          const Spacer(),
          Row(mainAxisAlignment: MainAxisAlignment.center, children: [
            const Icon(LucideIcons.smartphone, size: 16, color: C.text3),
            gap8,
            Text('Perangkat ini akan terikat ke akunmu', style: T.caption),
          ]),
        ],
      );

  void _forgot(BuildContext context) => toast(context, 'Fitur reset kata sandi belum tersedia. Hubungi HR.', icon: LucideIcons.mail);
}

/// A03 change password — required before any other feature when the account
/// still uses the temporary password from HR (architecture §5.9).
class ChangePassword extends StatefulWidget {
  const ChangePassword({super.key});
  @override
  State<ChangePassword> createState() => _ChangePasswordState();
}

class _ChangePasswordState extends State<ChangePassword> {
  final current = TextEditingController(), next = TextEditingController(), confirm = TextEditingController();
  bool tried = false, busy = false, hide = true;

  @override
  void dispose() {
    current.dispose();
    next.dispose();
    confirm.dispose();
    super.dispose();
  }

  String? get currentErr => tried && current.text.isEmpty ? 'Kata sandi saat ini wajib diisi' : null;
  String? get nextErr => tried && next.text.length < 8 ? 'Kata sandi baru minimal 8 karakter' : null;
  String? get confirmErr => tried && confirm.text != next.text ? 'Konfirmasi kata sandi tidak sama' : null;

  Future<void> submit() async {
    setState(() => tried = true);
    if (currentErr != null || nextErr != null || confirmErr != null || busy) return;
    setState(() => busy = true);
    try {
      await Api.changePassword(current: current.text, next: next.text);
      if (!mounted) return;
      toast(context, 'Kata sandi berhasil diperbarui', icon: LucideIcons.check);
      afterAuth(context);
    } on ApiError catch (e) {
      if (mounted) toast(context, e.message, icon: LucideIcons.triangleAlert);
    } catch (_) {
      if (mounted) toast(context, 'Tidak bisa terhubung ke server', icon: LucideIcons.triangleAlert);
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  @override
  Widget build(BuildContext context) => _AuthFrame(
        title: 'Ganti Kata Sandi',
        subtitle: 'Kata sandi sementara wajib diganti sebelum melanjutkan',
        children: [
          Field(
            label: 'Kata sandi saat ini',
            hint: 'Dari HR',
            icon: LucideIcons.lock,
            controller: current,
            obscure: hide,
            error: currentErr,
            onChanged: (_) => tried ? setState(() {}) : null,
          ),
          gap16,
          Field(
            label: 'Kata sandi baru',
            hint: 'Minimal 8 karakter',
            icon: LucideIcons.lock,
            controller: next,
            obscure: hide,
            error: nextErr,
            onChanged: (_) => tried ? setState(() {}) : null,
            suffix: IconButton(
              tooltip: hide ? 'Tampilkan kata sandi' : 'Sembunyikan kata sandi',
              icon: Icon(hide ? LucideIcons.eye : LucideIcons.eyeOff, size: 20, color: C.text3),
              onPressed: () => setState(() => hide = !hide),
            ),
          ),
          gap16,
          Field(
            label: 'Ulangi kata sandi baru',
            hint: 'Ketik ulang kata sandi baru',
            icon: LucideIcons.lock,
            controller: confirm,
            obscure: hide,
            error: confirmErr,
            onChanged: (_) => tried ? setState(() {}) : null,
          ),
          gap24,
          Btn('Simpan Kata Sandi', busy: busy, onTap: submit),
          const Spacer(),
          gap16,
          Row(children: [
            const Icon(LucideIcons.info, size: 16, color: C.text3),
            gap8,
            Expanded(child: Text('Gunakan kata sandi yang hanya kamu ketahui.', style: T.caption)),
          ]),
        ],
      );
}

/// Chooses the permanent password straight from the invitation link, so HR
/// never has to deliver the temporary one (architecture §4.8).
class InvitationSetPassword extends StatefulWidget {
  const InvitationSetPassword({super.key});
  @override
  State<InvitationSetPassword> createState() => _InvitationSetPasswordState();
}

class _InvitationSetPasswordState extends State<InvitationSetPassword> {
  final next = TextEditingController(), confirm = TextEditingController();
  bool tried = false, busy = false, hide = true;
  String? error;
  String? code;

  @override
  void dispose() {
    next.dispose();
    confirm.dispose();
    super.dispose();
  }

  String? get nextErr => tried && next.text.length < 8 ? 'Kata sandi minimal 8 karakter' : null;
  String? get confirmErr => tried && confirm.text != next.text ? 'Konfirmasi kata sandi tidak sama' : null;

  Future<void> submit() async {
    setState(() {
      tried = true;
      error = null;
      code = null;
    });
    if (nextErr != null || confirmErr != null || busy) return;
    final token = DeepLink.pendingToken;
    if (token == null) {
      setState(() => error = 'Link undangan tidak ditemukan. Buka kembali link dari email.');
      return;
    }
    setState(() => busy = true);
    try {
      final data = await Api.setPasswordFromInvitation(token, next.text);
      if (!mounted) return;
      final role = (data['user'] as Map?)?['role'];
      app.login(role == 'hr' || role == 'admin' ? Role.hr : Role.karyawan);
      Navigator.of(context).pushReplacement(fadeRoute(const InvitationAccept()));
    } on ApiError catch (e) {
      if (!mounted) return;
      setState(() {
        busy = false;
        error = e.message;
        code = e.code;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        busy = false;
        error = 'Tidak bisa terhubung ke server. Periksa koneksi lalu coba lagi.';
      });
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  @override
  Widget build(BuildContext context) => _AuthFrame(
        title: 'Buat Kata Sandi',
        subtitle: 'Tentukan kata sandi untuk masuk ke akunmu',
        children: [
          Field(
            label: 'Kata sandi baru',
            hint: 'Minimal 8 karakter',
            icon: LucideIcons.lock,
            controller: next,
            obscure: hide,
            error: nextErr,
            onChanged: (_) => tried ? setState(() {}) : null,
            suffix: IconButton(
              tooltip: hide ? 'Tampilkan kata sandi' : 'Sembunyikan kata sandi',
              icon: Icon(hide ? LucideIcons.eye : LucideIcons.eyeOff, size: 20, color: C.text3),
              onPressed: () => setState(() => hide = !hide),
            ),
          ),
          gap16,
          Field(
            label: 'Ulangi kata sandi baru',
            hint: 'Ketik ulang kata sandi baru',
            icon: LucideIcons.lock,
            controller: confirm,
            obscure: hide,
            error: confirmErr,
            onChanged: (_) => tried ? setState(() {}) : null,
          ),
          if (error != null) ...[
            gap16,
            Row(children: [
              const Icon(LucideIcons.triangleAlert, size: 18, color: C.danger),
              gap8,
              Expanded(child: Text(error!, style: T.small.copyWith(color: C.danger))),
            ]),
          ],
          gap24,
          Btn('Buat Kata Sandi', busy: busy, onTap: submit),
          if (code == 'INVITATION_PASSWORD_ALREADY_CHANGED') ...[
            gap12,
            Btn('Masuk dengan akun karyawan', icon: LucideIcons.logIn, kind: BtnKind.soft, onTap: () => resetTo(context, const Login())),
          ],
          const Spacer(),
          gap16,
          Row(children: [
            const Icon(LucideIcons.info, size: 16, color: C.text3),
            gap8,
            Expanded(child: Text('Kata sandi ini menggantikan kata sandi sementara dari HR.', style: T.caption)),
          ]),
        ],
      );
}

/// Opens the invitation from the email link once a session exists.
class InvitationAccept extends StatefulWidget {
  const InvitationAccept({super.key});
  @override
  State<InvitationAccept> createState() => _InvitationAcceptState();
}

class _InvitationAcceptState extends State<InvitationAccept> {
  String? _token;
  String? _error;
  bool _busy = true;
  bool _notOwned = false;

  @override
  void initState() {
    super.initState();
    _token = DeepLink.take();
    _accept();
  }

  Future<void> _accept() async {
    final token = _token;
    if (token == null) {
      setState(() {
        _busy = false;
        _error = 'Link undangan tidak ditemukan. Buka kembali link dari email.';
      });
      return;
    }
    setState(() {
      _busy = true;
      _error = null;
      _notOwned = false;
    });
    try {
      await Api.acceptInvitation(token);
      if (!mounted) return;
      Navigator.of(context).pushReplacement(fadeRoute(const FaceTips(first: true)));
    } on ApiError catch (e) {
      if (!mounted) return;
      setState(() {
        _busy = false;
        _error = e.message;
        _notOwned = e.code == 'INVITATION_NOT_OWNED';
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _busy = false;
        _error = 'Tidak bisa terhubung ke server. Periksa koneksi lalu coba lagi.';
      });
    }
  }

  /// The invitation belongs to another employee: drop the current session,
  /// keep the token, and let them sign in with the right account.
  Future<void> _switchAccount() async {
    DeepLink.remember(_token!);
    Api.token = null;
    app.logout();
    if (!mounted) return;
    resetTo(context, const Login());
  }

  @override
  Widget build(BuildContext context) => _AuthFrame(
        title: 'Registrasi Wajah',
        subtitle: _error == null ? 'Memverifikasi undanganmu' : 'Undangan belum bisa dipakai',
        children: [
          if (_busy) ...[
            gap24,
            const Center(child: CircularProgressIndicator()),
            gap16,
            Text('Mohon tunggu sebentar...', textAlign: TextAlign.center, style: T.body),
          ] else ...[
            gap24,
            const Center(child: Icon(LucideIcons.triangleAlert, size: 48, color: C.danger)),
            gap12,
            Text(_error!, textAlign: TextAlign.center, style: T.body),
            gap24,
            if (_notOwned)
              Btn('Masuk dengan akun karyawan', icon: LucideIcons.logIn, onTap: _switchAccount)
            else
              Btn('Coba Lagi', icon: LucideIcons.refreshCw, onTap: _accept),
            gap12,
            Btn('Kembali', kind: BtnKind.soft, onTap: () => afterAuth(context)),
          ],
        ],
      );
}
// ---------------------------------------------------------------- A03 onboarding

class Onboarding extends StatefulWidget {
  const Onboarding({super.key});
  @override
  State<Onboarding> createState() => _OnboardingState();
}

enum _Perm { kamera, lokasi, notif }

class _OnboardingState extends State<Onboarding> {
  final pc = PageController();
  int page = 0;
  final granted = <_Perm, bool?>{};

  static const pages = [
    (LucideIcons.scanFace, LucideIcons.check, 'Absen pakai wajahmu', 'Cukup lihat kamera beberapa detik. Wajahmu dicocokkan dengan data terdaftar, jadi absen tidak bisa dititipkan.'),
    (LucideIcons.mapPin, LucideIcons.shieldCheck, 'Lokasi asli, absen sah', 'GPS dicek bersama radius kantor. Aplikasi lokasi palsu dan mock location otomatis ditolak.'),
    (LucideIcons.wallet, LucideIcons.clipboardCheck, 'Gaji & tugas transparan', 'Lihat estimasi gaji berjalan, potongan, dan tugas dari HR di satu tempat.'),
  ];

  @override
  void dispose() {
    pc.dispose();
    super.dispose();
  }

  void next() => page < 3 ? pc.nextPage(duration: const Duration(milliseconds: 360), curve: Curves.easeOutCubic) : null;

  @override
  Widget build(BuildContext context) => Scaffold(
        body: SafeArea(
          child: Column(children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 8, 8, 0),
              child: Row(children: [
                const Logo(size: 32),
                const Spacer(),
                AnimatedOpacity(
                  opacity: page < 3 ? 1 : 0,
                  duration: const Duration(milliseconds: 200),
                  child: TextButton(onPressed: page < 3 ? () => pc.animateToPage(3, duration: const Duration(milliseconds: 420), curve: Curves.easeOutCubic) : null, child: Text('Lewati', style: T.title.copyWith(color: C.primary))),
                ),
              ]),
            ),
            Expanded(
              child: PageView(
                controller: pc,
                onPageChanged: (i) => setState(() => page = i),
                children: [
                  for (final p in pages) _Intro(p.$1, p.$2, p.$3, p.$4),
                  _permissions(),
                ],
              ),
            ),
            Row(mainAxisAlignment: MainAxisAlignment.center, children: [
              for (var i = 0; i < 4; i++)
                AnimatedContainer(
                  duration: const Duration(milliseconds: 250),
                  margin: const EdgeInsets.symmetric(horizontal: 3),
                  width: i == page ? 24 : 8,
                  height: 8,
                  decoration: BoxDecoration(color: i == page ? C.primary : C.mid, borderRadius: BorderRadius.circular(8)),
                ),
            ]),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 20, 20, 16),
              child: page < 3
                  ? Btn('Lanjut', icon: LucideIcons.arrowRight, onTap: next)
                  : Btn('Lanjut ke Registrasi Wajah', onTap: granted[_Perm.kamera] == true && granted[_Perm.lokasi] == true
                      ? () {
                          app.onboarded = true;
                          Navigator.of(context).pushReplacement(fadeRoute(const FaceTips(first: true)));
                        }
                      : null),
            ),
          ]),
        ),
      );

  Widget _permissions() {
    final denied = granted.entries.where((e) => e.value == false).map((e) => e.key).toList();
    Widget row(_Perm p, IconData icon, String title, String why) {
      final g = granted[p];
      return AppCard(
        margin: const EdgeInsets.only(bottom: 12),
        border: g == false ? C.danger.withValues(alpha: .4) : null,
        child: Row(children: [
          IconTile(icon, tone: g == false ? Tone.danger : Tone.sage, size: 44),
          gap12,
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(title, style: T.title), Text(why, style: T.caption)])),
          gap8,
          if (g == true)
            const Pill('Diizinkan', icon: LucideIcons.check)
          else
            Btn(g == false ? 'Coba lagi' : 'Izinkan', expand: false, height: 40, kind: BtnKind.soft, onTap: () => _ask(p, title)),
        ]),
      );
    }

    return ListView(padding: const EdgeInsets.fromLTRB(20, 16, 20, 8), children: [
      Text('Izinkan akses', style: T.h1),
      gap8,
      Text('HadirMetrik butuh tiga izin agar absen sah dan aman. Kami tidak melacak lokasimu di luar jam absen.', style: T.body),
      gap20,
      row(_Perm.kamera, LucideIcons.camera, 'Kamera', 'Untuk verifikasi wajah saat absen'),
      row(_Perm.lokasi, LucideIcons.mapPin, 'Lokasi Presisi', 'Saat aplikasi digunakan, untuk cek radius kantor'),
      row(_Perm.notif, LucideIcons.bell, 'Notifikasi', 'Pengingat absen, tugas baru, slip gaji'),
      if (denied.contains(_Perm.kamera) || denied.contains(_Perm.lokasi))
        Banner2(
          'Tanpa ${denied.contains(_Perm.kamera) ? 'kamera' : 'lokasi presisi'}, kamu tidak bisa absen',
          body: 'Izin ini wajib untuk Clock-In dan Clock-Out. Buka pengaturan untuk mengaktifkannya.',
          tone: Tone.danger,
          icon: LucideIcons.shieldAlert,
          trailing: TextButton(
            onPressed: () => setState(() => granted.updateAll((_, v) => v == false ? true : v)),
            child: Text('Buka Pengaturan', style: T.title.copyWith(color: C.dangerText)),
          ),
        ),
    ]);
  }

  Future<void> _ask(_Perm p, String title) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: Colors.white,
        title: Text('Izinkan HadirMetrik mengakses ${title.toLowerCase()}?', style: T.h3),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Jangan izinkan')),
          TextButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Izinkan')),
        ],
      ),
    );
    if (ok != null) setState(() => granted[p] = ok);
  }
}

class _Intro extends StatelessWidget {
  const _Intro(this.icon, this.badge, this.title, this.body);
  final IconData icon, badge;
  final String title, body;
  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.symmetric(horizontal: 28),
        child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
          SizedBox(
            width: 240,
            height: 240,
            child: Stack(alignment: Alignment.center, children: [
              Container(decoration: BoxDecoration(color: C.mid.withValues(alpha: .45), shape: BoxShape.circle)),
              Container(width: 168, height: 168, decoration: const BoxDecoration(color: C.sageSoft, shape: BoxShape.circle)),
              Container(
                width: 112,
                height: 112,
                decoration: BoxDecoration(color: C.primary, borderRadius: BorderRadius.circular(32)),
                child: Icon(icon, size: 52, color: Colors.white),
              ),
              Positioned(
                right: 44,
                bottom: 44,
                child: Container(
                  width: 52,
                  height: 52,
                  decoration: BoxDecoration(color: Colors.white, shape: BoxShape.circle, border: Border.all(color: C.bg, width: 4)),
                  child: Icon(badge, size: 24, color: C.accent),
                ),
              ),
            ]),
          ),
          const SizedBox(height: 40),
          Text(title, style: T.h1, textAlign: TextAlign.center),
          gap12,
          Text(body, style: T.body.copyWith(fontSize: 15, height: 1.5), textAlign: TextAlign.center),
        ]),
      );
}

// ---------------------------------------------------------------- A04 face enroll

const _angles = ['Depan', 'Kiri', 'Kanan', 'Atas', 'Bawah'];
const _angleHints = ['Lihat lurus ke kamera', 'Tengokkan kepala perlahan ke kiri', 'Tengokkan kepala perlahan ke kanan', 'Angkat dagu sedikit ke atas', 'Turunkan dagu sedikit ke bawah'];

class FaceTips extends StatelessWidget {
  const FaceTips({super.key, this.first = false});
  final bool first;
  @override
  Widget build(BuildContext context) {
    Widget tip(IconData icon, String t, bool ok) => Expanded(
          child: AppCard(
            padding: const EdgeInsets.all(14),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              IconTile(icon, tone: ok ? Tone.sage : Tone.danger, size: 40),
              gap12,
              Text(t, style: T.title),
              gap8,
              Pill(ok ? 'Lakukan' : 'Hindari', icon: ok ? LucideIcons.check : LucideIcons.x, tone: ok ? Tone.success : Tone.danger),
            ]),
          ),
        );
    return Screen(
      header: TopBar('Registrasi Wajah', back: !first),
      bottom: BottomActions([Btn('Mulai Registrasi', icon: LucideIcons.scanFace, onTap: () => Navigator.of(context).pushReplacement(fadeRoute(FaceCapture(first: first))))]),
      children: [
        gap8,
        Text('Siapkan wajahmu', style: T.h1),
        gap8,
        Text('Kami akan mengambil 5 foto dari sudut berbeda. Prosesnya kurang dari 1 menit.', style: T.body),
        gap20,
        Row(children: [tip(LucideIcons.sun, 'Cahaya terang dan merata', true), gap12, tip(LucideIcons.scanFace, 'Wajah lurus ke kamera', true)]),
        gap12,
        Row(children: [tip(LucideIcons.glasses, 'Masker atau kacamata hitam', false), gap12, tip(LucideIcons.users, 'Lebih dari satu wajah', false)]),
        const Overline('5 sudut yang direkam'),
        Wrap(spacing: 8, runSpacing: 8, children: [for (final a in _angles) Pill(a, tone: Tone.sage)]),
        gap20,
        Row(children: [
          const Icon(LucideIcons.lock, size: 16, color: C.text3),
          gap8,
          Expanded(child: Text('Data wajah disimpan terenkripsi dan hanya dipakai untuk absensi.', style: T.caption)),
        ]),
      ],
    );
  }
}

class FaceCapture extends StatefulWidget {
  const FaceCapture({super.key, this.first = false});
  final bool first;
  @override
  State<FaceCapture> createState() => _FaceCaptureState();
}

class _FaceCaptureState extends State<FaceCapture> {
  int done = 0;
  bool blurry = false, blurShown = false;
  Timer? timer;

  @override
  void initState() {
    super.initState();
    timer = Timer.periodic(const Duration(milliseconds: 1500), (_) => tick());
  }

  void tick() {
    if (!mounted) return;
    // Angle 4 shows the [DL] "blurry" rejection once, then succeeds.
    if (done == 3 && !blurShown) {
      setState(() => blurry = blurShown = true);
      return;
    }
    setState(() {
      blurry = false;
      done++;
    });
    if (done == 5) {
      timer?.cancel();
      Future.delayed(const Duration(milliseconds: 500), () {
        if (mounted) Navigator.of(context).pushReplacement(fadeRoute(FaceSuccess(first: widget.first)));
      });
    }
  }

  @override
  void dispose() {
    timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final step = done.clamp(0, 4);
    return Scaffold(
      backgroundColor: C.primary,
      body: Column(children: [
        Expanded(
          child: SafeArea(
            bottom: false,
            child: Stack(children: [
              Center(child: FaceOval(width: 230, segments: 5, done: done, active: done < 5 ? done : null, blur: blurry)),
              Positioned(
                left: 16,
                top: 8,
                child: CircleBtn(LucideIcons.x, tooltip: 'Batalkan', bg: Colors.black.withValues(alpha: .35), fg: Colors.white, onTap: () => Navigator.of(context).pushReplacement(fadeRoute(FaceTips(first: widget.first)))),
              ),
            ]),
          ),
        ),
        Container(
          decoration: const BoxDecoration(color: Colors.white, borderRadius: BorderRadius.vertical(top: Radius.circular(28))),
          padding: EdgeInsets.fromLTRB(20, 24, 20, 20 + MediaQuery.paddingOf(context).bottom),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text('SUDUT ${step + 1} DARI 5', style: T.overline.copyWith(color: C.primary)),
            gap4,
            AnimatedSwitcher(duration: const Duration(milliseconds: 250), child: Text(_angleHints[step], key: ValueKey(step), style: T.h2)),
            gap16,
            Row(children: [
              for (var i = 0; i < 5; i++)
                Expanded(
                  child: Column(children: [
                    AnimatedContainer(
                      duration: const Duration(milliseconds: 250),
                      height: 6,
                      margin: const EdgeInsets.symmetric(horizontal: 2),
                      decoration: BoxDecoration(color: i < done ? C.primary : (i == step ? C.mid : C.bg), borderRadius: BorderRadius.circular(4)),
                    ),
                    gap4,
                    Text(_angles[i], style: T.small.copyWith(color: i <= step ? C.text : C.text3)),
                  ]),
                ),
            ]),
            gap16,
            Text('Kualitas foto [DL]', style: T.caption),
            gap8,
            Wrap(spacing: 8, runSpacing: 8, children: [
              blurry ? const Pill('Gambar buram', icon: LucideIcons.triangleAlert, tone: Tone.warning) : const Pill('Fokus tajam', icon: LucideIcons.check),
              const Pill('Cahaya cukup', icon: LucideIcons.check),
              const Pill('1 wajah', icon: LucideIcons.check),
            ]),
          ]),
        ),
      ]),
    );
  }
}

class FaceSuccess extends StatelessWidget {
  const FaceSuccess({super.key, this.first = false});
  final bool first;
  @override
  Widget build(BuildContext context) => Screen(
        header: const SizedBox(height: 24),
        bottom: BottomActions([
          Btn(first ? 'Lanjut ke Beranda' : 'Selesai', onTap: () {
            app.faceEnrolled = true;
            first ? enterApp(context) : Navigator.pop(context);
          }),
        ]),
        children: [
          gap24,
          Center(
            child: TweenAnimationBuilder<double>(
              tween: Tween(begin: .6, end: 1),
              duration: const Duration(milliseconds: 600),
              curve: Curves.elasticOut,
              builder: (_, s, child) => Transform.scale(scale: s, child: child),
              child: Container(
                width: 132,
                height: 132,
                decoration: BoxDecoration(color: C.mid.withValues(alpha: .6), shape: BoxShape.circle),
                alignment: Alignment.center,
                child: Container(width: 96, height: 96, decoration: const BoxDecoration(color: C.primary, shape: BoxShape.circle), child: const Icon(LucideIcons.scanFace, size: 44, color: Colors.white)),
              ),
            ),
          ),
          gap24,
          Text('Wajah berhasil didaftarkan', style: T.h1, textAlign: TextAlign.center),
          gap12,
          Row(mainAxisAlignment: MainAxisAlignment.center, children: [const Pill('Selesai', icon: LucideIcons.check), gap8, Text('Siap dipakai untuk absen', style: T.caption)]),
          gap24,
          AppCard(
            child: Divided([
              const InfoRow('Sudut terekam', '5 dari 5', icon: LucideIcons.scanFace),
              const InfoRow('Skor kualitas [DL]', '96 / 100', icon: LucideIcons.sparkles),
              InfoRow('Waktu', '${tglPendek(app.now)}, ${hm(app.now)}', icon: LucideIcons.clock),
              const InfoRow('Penyimpanan', 'Terenkripsi', icon: LucideIcons.lock),
            ]),
          ),
        ],
      );
}
