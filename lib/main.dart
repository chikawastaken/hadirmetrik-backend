import 'dart:async';
import 'dart:math' as math;

import 'package:app_links/app_links.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';

import 'api.dart';
import 'deeplink.dart';
import 'screens/auth.dart';
import 'screens/clock.dart';
import 'screens/home.dart';
import 'screens/hr_main.dart';
import 'screens/hr_people.dart';
import 'screens/hr_work.dart';
import 'screens/leave.dart';
import 'screens/me.dart';
import 'screens/tasks.dart';
import 'state.dart';
import 'theme.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(statusBarColor: Colors.transparent));
  try {
    final initial = await AppLinks().getInitialLink();
    final token = initial == null ? null : InvitationLink.tokenFrom(initial);
    if (token != null) DeepLink.remember(token);
  } catch (_) {
    // Deep links are unavailable on this platform; start normally.
  }
  runApp(const HadirMetrik());
}

final navigatorKey = GlobalKey<NavigatorState>();

class HadirMetrik extends StatefulWidget {
  const HadirMetrik({super.key});

  @override
  State<HadirMetrik> createState() => _HadirMetrikState();
}

class _HadirMetrikState extends State<HadirMetrik> {
  StreamSubscription<Uri>? _links;

  @override
  void initState() {
    super.initState();
    _links = AppLinks().uriLinkStream.listen(_onLink, onError: (_) {});
  }

  @override
  void dispose() {
    _links?.cancel();
    super.dispose();
  }

  /// Invitation links arrive while the app is already running.
  void _onLink(Uri uri) {
    final token = InvitationLink.tokenFrom(uri);
    if (token == null) return;
    DeepLink.remember(token);

    final nav = navigatorKey.currentState;
    if (nav == null) return;
    if (Api.token == null) {
      nav.pushAndRemoveUntil(fadeRoute(const InvitationSetPassword()), (_) => false);
    } else {
      nav.push(fadeRoute(const InvitationAccept()));
    }
  }

  @override
  Widget build(BuildContext context) => MaterialApp(
        title: 'HadirMetrik',
        navigatorKey: navigatorKey,
        debugShowCheckedModeBanner: false,
        theme: buildTheme(),
        // Phone-width canvas on tablets and desktop browsers; the design is 440 wide.
        builder: (context, child) => ColoredBox(
          color: const Color(0xFF2F3D2B),
          child: Center(
            child: LayoutBuilder(builder: (context, c) {
              final w = math.min(c.maxWidth, 440.0);
              final mq = MediaQuery.of(context);
              return SizedBox(
                width: w,
                child: MediaQuery(data: mq.copyWith(size: Size(w, mq.size.height)), child: ClipRect(child: child)),
              );
            }),
          ),
        ),
        // A cold-start invitation link goes straight to the password step: the
        // link itself is the credential, so no login is needed first.
        home: DeepLink.hasPending ? const InvitationSetPassword() : const Splash(),
      );
}

/// Signed-in shell: one nested navigator per tab so each tab keeps its own
/// stack, plus a floating nav bar laid over the content.
class Shell extends StatefulWidget {
  const Shell({super.key});
  static _ShellState? _active;

  /// Switch tab from anywhere inside the shell.
  static void go(int i) => _active?._select(i);

  /// Switch to [tab] and push [page] on that tab's stack (nav stays visible).
  static void open(int tab, Widget page) {
    final s = _active;
    if (s == null) return;
    s._select(tab);
    WidgetsBinding.instance.addPostFrameCallback((_) => s.keys[tab].currentState?.push(MaterialPageRoute(builder: (_) => page)));
  }

  @override
  State<Shell> createState() => _ShellState();
}

class _ShellState extends State<Shell> {
  int index = 0;
  final visited = <int>{0};
  final keys = List.generate(5, (_) => GlobalKey<NavigatorState>());
  bool get hr => app.role == Role.hr;

  @override
  void initState() {
    super.initState();
    Shell._active = this;
  }

  @override
  void dispose() {
    if (Shell._active == this) Shell._active = null;
    super.dispose();
  }

  void _select(int i) {
    if (!hr && i == 2) {
      startClock(context);
      return;
    }
    if (i == index) {
      keys[i].currentState?.popUntil((r) => r.isFirst);
    } else {
      setState(() {
        index = i;
        visited.add(i);
      });
    }
  }

  Widget _root(int i) => hr
      ? switch (i) { 0 => const HrDashboard(), 1 => const Employees(), 2 => const HrTasks(), 3 => const Approvals(), _ => const More() }
      : switch (i) { 0 => const Home(), 1 => const Tasks(), 3 => const LeavePage(), 4 => const Profile(), _ => const SizedBox() };

  @override
  Widget build(BuildContext context) {
    final keyboard = MediaQuery.viewInsetsOf(context).bottom > 0;
    return NavigatorPopHandler(
      onPopWithResult: (_) => keys[index].currentState?.maybePop(),
      child: Scaffold(
        resizeToAvoidBottomInset: false,
        body: Stack(children: [
          IndexedStack(index: index, children: [
            for (var i = 0; i < 5; i++)
              visited.contains(i)
                  ? HeroControllerScope.none(
                      child: Navigator(
                        key: keys[i],
                        onGenerateRoute: (s) => MaterialPageRoute(settings: s, builder: (_) => _root(i)),
                      ),
                    )
                  : const SizedBox(),
          ]),
          Positioned(
            left: 0,
            right: 0,
            bottom: 0,
            child: AnimatedSlide(
              duration: const Duration(milliseconds: 200),
              offset: keyboard ? const Offset(0, 1.5) : Offset.zero,
              child: hr ? _HrNav(index, _select) : _KaryawanNav(index, _select),
            ),
          ),
        ]),
      ),
    );
  }
}

class _NavItem extends StatelessWidget {
  const _NavItem(this.icon, this.label, this.active, this.onTap);
  final IconData icon;
  final String label;
  final bool active;
  final VoidCallback onTap;
  @override
  Widget build(BuildContext context) => Expanded(
        child: Semantics(
          button: true,
          selected: active,
          label: label,
          child: GestureDetector(
            behavior: HitTestBehavior.opaque,
            onTap: onTap,
            child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
              AnimatedContainer(
                duration: const Duration(milliseconds: 220),
                curve: Curves.easeOutCubic,
                width: 48,
                height: 32,
                decoration: BoxDecoration(color: active ? C.bg : Colors.transparent, borderRadius: BorderRadius.circular(999)),
                child: Icon(icon, size: 20, color: active ? C.primary : C.text2),
              ),
              const SizedBox(height: 3),
              Text(label, maxLines: 1, overflow: TextOverflow.clip, style: T.small.copyWith(fontSize: 10.5, color: active ? C.primary : C.text2, fontWeight: active ? FontWeight.w700 : FontWeight.w500)),
            ]),
          ),
        ),
      );
}

BoxDecoration get _navBox => BoxDecoration(
      color: Colors.white,
      borderRadius: BorderRadius.circular(36),
      boxShadow: const [BoxShadow(color: Color(0x1F0F2742), blurRadius: 24, offset: Offset(0, 8))],
    );

class _KaryawanNav extends StatelessWidget {
  const _KaryawanNav(this.index, this.onTap);
  final int index;
  final ValueChanged<int> onTap;
  @override
  Widget build(BuildContext context) => Padding(
        padding: EdgeInsets.fromLTRB(16, 0, 16, 12 + MediaQuery.paddingOf(context).bottom),
        child: SizedBox(
          height: 84,
          child: Stack(clipBehavior: Clip.none, alignment: Alignment.bottomCenter, children: [
            Container(
              height: 72,
              decoration: _navBox,
              child: Row(children: [
                _NavItem(LucideIcons.house, 'Beranda', index == 0, () => onTap(0)),
                _NavItem(LucideIcons.clipboardCheck, 'Tugas', index == 1, () => onTap(1)),
                const SizedBox(width: 84),
                _NavItem(LucideIcons.fileText, 'Surat', index == 3, () => onTap(3)),
                _NavItem(LucideIcons.user, 'Profil', index == 4, () => onTap(4)),
              ]),
            ),
            Positioned(
              top: -4,
              child: Semantics(
                button: true,
                label: 'Absen sekarang',
                child: GestureDetector(
                  onTap: () => onTap(2),
                  child: Container(
                    width: 72,
                    height: 72,
                    decoration: BoxDecoration(
                      color: C.bg,
                      shape: BoxShape.circle,
                      border: Border.all(color: Colors.white, width: 5),
                      boxShadow: const [BoxShadow(color: Color(0x264F6648), blurRadius: 16, offset: Offset(0, 6))],
                    ),
                    child: Container(
                      margin: const EdgeInsets.all(5),
                      decoration: const BoxDecoration(color: C.primary, shape: BoxShape.circle),
                      child: const Icon(LucideIcons.scanFace, color: Colors.white, size: 26),
                    ),
                  ),
                ),
              ),
            ),
          ]),
        ),
      );
}

class _HrNav extends StatelessWidget {
  const _HrNav(this.index, this.onTap);
  final int index;
  final ValueChanged<int> onTap;
  @override
  Widget build(BuildContext context) => Padding(
        padding: EdgeInsets.fromLTRB(16, 0, 16, 12 + MediaQuery.paddingOf(context).bottom),
        child: Container(
          height: 72,
          padding: const EdgeInsets.symmetric(horizontal: 6),
          decoration: _navBox,
          child: Row(children: [
            _NavItem(LucideIcons.layoutGrid, 'Dashboard', index == 0, () => onTap(0)),
            _NavItem(LucideIcons.users, 'Karyawan', index == 1, () => onTap(1)),
            _NavItem(LucideIcons.clipboardCheck, 'Tugas', index == 2, () => onTap(2)),
            _NavItem(LucideIcons.circleCheck, 'Persetujuan', index == 3, () => onTap(3)),
            _NavItem(LucideIcons.ellipsis, 'Lainnya', index == 4, () => onTap(4)),
          ]),
        ),
      );
}
