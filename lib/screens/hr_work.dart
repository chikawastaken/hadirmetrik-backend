import 'package:flutter/material.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';

import '../data.dart';
import '../state.dart';
import '../theme.dart';
import '../visuals.dart';
import '../widgets.dart';
import 'hr_main.dart';
import 'hr_people.dart';
import 'leave.dart';
import 'tasks.dart';

const _today = '7 Okt';

/// HR's task list: its own tasks plus Rina's tasks from Sari that wait for review.
List<TaskItem> hrTaskList() => [
      ...app.hrTasks,
      ...app.tasks.where((t) => t.from == 'Sari Wulandari' && t.status == TaskStatus.review && !app.hrTasks.contains(t)),
    ];

// ---------------------------------------------------------------- C07 tugas

class HrTasks extends StatefulWidget {
  const HrTasks({super.key});
  @override
  State<HrTasks> createState() => _HrTasksState();
}

class _HrTasksState extends State<HrTasks> {
  int chip = 0;

  @override
  Widget build(BuildContext context) => ListenableBuilder(
        listenable: app,
        builder: (context, _) {
          final all = hrTaskList();
          final list = all
              .where((t) => switch (chip) {
                    1 => t.status == TaskStatus.proses || t.status == TaskStatus.revisi,
                    2 => t.status == TaskStatus.review,
                    3 => t.status == TaskStatus.terlambat,
                    4 => t.status == TaskStatus.selesai,
                    _ => true,
                  })
              .toList();
          final late = all.where((t) => t.status == TaskStatus.terlambat).length;
          return Stack(children: [
            Screen(
              inShell: true,
              header: const PageHeader('Tugas', subtitle: 'Semua divisi · Oktober 2026'),
              children: [
                AppCard(
                  child: Row(children: [
                    for (final (v, l, c) in [('${42 + all.length - 6}', 'Tugas aktif', C.text), ('81%', 'Tepat waktu', C.accent), ('${4 + late}', 'Terlambat', C.dangerText)])
                      Expanded(child: Column(children: [Text(v, style: T.numM.copyWith(color: c)), Text(l, style: T.small)])),
                  ]),
                ),
                gap12,
                if (late > 0)
                  Banner2(
                    '${4 + late} tugas lewat tenggat',
                    body: 'Paling lama: Rekonsiliasi kas kecil September, lewat 2 hari.',
                    tone: Tone.danger,
                    icon: LucideIcons.clockAlert,
                    trailing: TextButton(
                      onPressed: () => toast(context, 'Pengingat dikirim ke ${4 + late} orang.', aboveNav: true),
                      child: Text('Ingatkan', style: T.button.copyWith(color: C.dangerText)),
                    ),
                  ),
                gap12,
                ChipBar(['Semua ${48 + all.length - 7}', 'Berjalan 25', 'Menunggu Review ${10 + all.where((t) => t.status == TaskStatus.review).length}', 'Terlambat', 'Selesai'], chip, (i) => setState(() => chip = i), padding: EdgeInsets.zero),
                gap12,
                if (list.isEmpty) const EmptyState(icon: LucideIcons.clipboardCheck, title: 'Tidak ada tugas', message: 'Belum ada tugas di kategori ini.'),
                for (final t in list) Padding(padding: const EdgeInsets.only(bottom: 10), child: TaskCard(t, hr: true, onTap: () => push(context, TaskReview(t), root: true))),
              ],
            ),
            Positioned(
              right: 20,
              bottom: navClearance - 8,
              child: FloatingActionButton.extended(
                heroTag: 'task',
                backgroundColor: C.primary,
                foregroundColor: Colors.white,
                onPressed: () => push(context, const CreateTask(), root: true),
                icon: const Icon(LucideIcons.plus, size: 20),
                label: Text('Buat Tugas', style: T.button.copyWith(color: Colors.white)),
              ),
            ),
          ]);
        },
      );
}

class CreateTask extends StatefulWidget {
  const CreateTask({super.key, this.assignee});
  final String? assignee;
  @override
  State<CreateTask> createState() => _CreateTaskState();
}

class _CreateTaskState extends State<CreateTask> {
  final title = TextEditingController(), desc = TextEditingController(), item = TextEditingController();
  late final picked = <String>{?widget.assignee};
  final checklist = <String>[];
  final remind = {0};
  int mode = 0, div = 0, prio = 1, due = 9;
  bool proof = true, tried = false, busy = false;

  @override
  void initState() {
    super.initState();
    if (widget.assignee == null) picked.add(rina.name);
  }

  @override
  void dispose() {
    for (final c in [title, desc, item]) {
      c.dispose();
    }
    super.dispose();
  }

  List<String> get assignees => mode == 1 ? [for (final p in employees.where((p) => p.division == divisions[div])) p.name] : picked.toList();
  String get deadline => '${tglPendek(DateTime(2026, 10, due))}, 17.00';

  Future<void> create() async {
    setState(() => tried = true);
    if (title.text.trim().length < 5 || assignees.isEmpty) return;
    setState(() => busy = true);
    await Future<void>.delayed(const Duration(milliseconds: 700));
    if (!mounted) return;
    final who = assignees;
    final t = TaskItem(
      title: title.text.trim(),
      desc: desc.text.trim().isEmpty ? 'Tidak ada deskripsi.' : desc.text.trim(),
      prio: Prio.values[prio],
      status: TaskStatus.proses,
      deadline: deadline,
      from: 'Sari Wulandari',
      remaining: 'Sisa ${due - 7} hari',
      created: 'Rab, 7 Okt 2026',
      checklist: [for (final c in checklist) CheckItem(c)],
      assignees: who,
      division: personByName(who.first).division,
    );
    app.hrTasks.insert(0, t);
    if (who.contains(rina.name)) {
      app.tasks.add(t);
      app.notifs.insert(0, Notif(NCat.tugas, 'Tugas baru dari Sari Wulandari', '${t.title} · tenggat $deadline', hm(app.now), 'HARI INI'));
    }
    app.touch();
    Navigator.pop(context);
    toast(context, 'Tugas dibuat untuk ${who.length} orang.', aboveNav: true);
  }

  @override
  Widget build(BuildContext context) {
    final names = [for (final p in employees) p.name];
    return Screen(
      header: const TopBar('Buat Tugas'),
      bottom: BottomActions([
        Btn('Simpan Draf', kind: BtnKind.light, onTap: () {
          Navigator.pop(context);
          toast(context, 'Draf tugas disimpan.', aboveNav: true);
        }),
        Btn('Buat Tugas', icon: LucideIcons.send, busy: busy, onTap: busy ? null : create),
      ]),
      children: [
        gap8,
        Field(label: 'Judul tugas', hint: 'Contoh: Update stok gudang Kalasan', controller: title, error: tried && title.text.trim().length < 5 ? 'Judul minimal 5 karakter' : null, onChanged: (_) => tried ? setState(() {}) : null),
        gap12,
        Field(label: 'Deskripsi', hint: 'Jelaskan hasil yang diharapkan', controller: desc, maxLines: 4, maxLength: 500),
        const Overline('TUGASKAN KE'),
        Segmented(const ['Individu', 'Divisi', 'Beberapa'], mode, (i) => setState(() {
              mode = i;
              if (i == 0 && picked.length > 1) {
                final first = picked.first;
                picked
                  ..clear()
                  ..add(first);
              }
            })),
        gap12,
        if (mode == 1) ...[
          ChipWrap(divisions, (i) => i == div, (i) => setState(() => div = i)),
          gap8,
          Text('${assignees.length} orang di ${divisions[div]}', style: T.small),
        ] else
          ChipWrap(
            [for (final n in names) n.split(' ').first],
            (i) => picked.contains(names[i]),
            (i) => setState(() {
              final n = names[i];
              if (mode == 0) {
                picked
                  ..clear()
                  ..add(n);
              } else {
                picked.contains(n) ? picked.remove(n) : picked.add(n);
              }
            }),
          ),
        if (tried && assignees.isEmpty) Padding(padding: const EdgeInsets.only(top: 8), child: Text('Pilih minimal satu penerima', style: T.small.copyWith(color: C.dangerText))),
        const Overline('PRIORITAS DAN TENGGAT'),
        ChipWrap([for (final p in Prio.values) p.label], (i) => i == prio, (i) => setState(() => prio = i)),
        gap12,
        PickerBox(
          small: 'Tenggat',
          value: deadline,
          icon: LucideIcons.calendar,
          onTap: () => showSheet(
            context,
            (ctx) => Column(mainAxisSize: MainAxisSize.min, children: [
              const SheetHead('Pilih tenggat'),
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
                child: MonthGrid(year: 2026, month: 10, today: 7, selected: due, onTap: (d) {
                  if (d < 7) return;
                  setState(() => due = d);
                  Navigator.pop(ctx);
                }),
              ),
            ]),
          ),
        ),
        const Overline('CHECKLIST'),
        for (final (i, c) in checklist.indexed)
          Padding(
            padding: const EdgeInsets.only(bottom: 6),
            child: Row(children: [
              const CheckBox2(false),
              gap12,
              Expanded(child: Text(c, style: T.body)),
              IconButton(tooltip: 'Hapus', onPressed: () => setState(() => checklist.removeAt(i)), icon: const Icon(LucideIcons.x, size: 18, color: C.text3)),
            ]),
          ),
        Row(children: [
          Expanded(child: Field(hint: 'Tambah item', controller: item)),
          gap8,
          CircleBtn(LucideIcons.plus, bg: C.sageSoft, fg: C.primary, tooltip: 'Tambah item', onTap: () {
            if (item.text.trim().isEmpty) return;
            setState(() => checklist.add(item.text.trim()));
            item.clear();
          }),
        ]),
        const Overline('LAMPIRAN'),
        Btn('Tambah lampiran', kind: BtnKind.soft, icon: LucideIcons.paperclip, onTap: () => toast(context, 'Pilih file dari perangkat.')),
        gap12,
        ToggleTile('Perlu bukti foto', proof, (v) => setState(() => proof = v), subtitle: 'Kualitas foto dicek otomatis', dl: true),
        const Overline('PENGINGAT'),
        ChipWrap(const ['1 hari sebelum', '3 jam sebelum', 'Saat tenggat'], remind.contains, (i) => setState(() => remind.contains(i) ? remind.remove(i) : remind.add(i))),
      ],
    );
  }
}

// ---------------------------------------------------------------- C08 review tugas

class TaskReview extends StatefulWidget {
  const TaskReview(this.t, {super.key});
  final TaskItem t;
  @override
  State<TaskReview> createState() => _TaskReviewState();
}

class _TaskReviewState extends State<TaskReview> {
  final comment = TextEditingController();

  TaskItem get t => widget.t;
  bool get rinaSent => t.assignees.contains(rina.name) && (t.status == TaskStatus.review || t.status == TaskStatus.selesai);

  @override
  void dispose() {
    comment.dispose();
    super.dispose();
  }

  void _notifyRina(NCat cat, String title, String body) {
    if (t.assignees.contains(rina.name)) app.notifs.insert(0, Notif(cat, title, body, hm(app.now), 'HARI INI'));
  }

  void revise() {
    final c = TextEditingController();
    String? err;
    showSheet(
      context,
      (ctx) => StatefulBuilder(
        builder: (ctx, set) => Padding(
          padding: EdgeInsets.only(bottom: MediaQuery.viewInsetsOf(ctx).bottom),
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            const SheetHead('Minta revisi', subtitle: 'Catatan ini dikirim ke penerima tugas'),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
              child: Column(children: [
                ChipWrap(const ['Foto buram', 'Data belum lengkap', 'Format salah'], (_) => false, (i) {
                  c.text = const ['Foto rak 3 masih buram, mohon diulang dari jarak dekat.', 'Data stok lakban belum lengkap, mohon dilengkapi.', 'Format belum sesuai template stok Oktober.'][i];
                  set(() => err = null);
                }),
                gap12,
                Field(hint: 'Jelaskan bagian yang perlu diperbaiki', controller: c, maxLines: 3, error: err),
                gap16,
                Btn('Kirim Permintaan Revisi', icon: LucideIcons.rotateCcw, onTap: () {
                  if (c.text.trim().length < 10) return set(() => err = 'Catatan wajib diisi, minimal 10 karakter');
                  t
                    ..status = TaskStatus.revisi
                    ..revisionNote = c.text.trim();
                  _notifyRina(NCat.revisi, 'Revisi diminta', 'Sari Wulandari: ${c.text.trim()}');
                  app.touch();
                  Navigator.pop(ctx);
                  Navigator.pop(context);
                  toast(context, 'Permintaan revisi dikirim.');
                }),
              ]),
            ),
          ]),
        ),
      ),
    );
  }

  void approve() {
    var stars = 4;
    showSheet(
      context,
      (ctx) => StatefulBuilder(
        builder: (ctx, set) => Padding(
          padding: EdgeInsets.only(bottom: MediaQuery.viewInsetsOf(ctx).bottom),
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            const SheetHead('Setujui tugas', subtitle: 'Beri nilai kualitas hasil kerja'),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
              child: Column(children: [
                Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                  for (var i = 1; i <= 5; i++)
                    IconButton(
                      tooltip: '$i bintang',
                      iconSize: 36,
                      onPressed: () => set(() => stars = i),
                      icon: Icon(LucideIcons.star, color: i <= stars ? C.warning : C.border, fill: i <= stars ? 1 : 0),
                    ),
                ]),
                Text('$stars dari 5 · ${const ['Kurang', 'Cukup', 'Sedang', 'Baik', 'Sangat baik'][stars - 1]}', style: T.title),
                gap12,
                const Field(hint: 'Komentar (opsional)', maxLines: 2),
                gap16,
                Btn('Setujui Tugas', icon: LucideIcons.check, onTap: () {
                  t
                    ..status = TaskStatus.selesai
                    ..progress = 1;
                  _notifyRina(NCat.tugas, 'Tugas disetujui', '${t.title} disetujui Sari Wulandari · nilai $stars dari 5');
                  app.touch();
                  Navigator.pop(ctx);
                  Navigator.pop(context);
                  toast(context, 'Tugas disetujui.');
                }),
              ]),
            ),
          ]),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) => ListenableBuilder(
        listenable: app,
        builder: (context, _) {
          final reviewing = t.status == TaskStatus.review;
          return Screen(
            header: const TopBar('Review Tugas'),
            bottom: reviewing
                ? BottomActions([
                    Btn('Minta Revisi', kind: BtnKind.light, icon: LucideIcons.rotateCcw, onTap: revise),
                    Btn('Setujui', icon: LucideIcons.check, onTap: approve),
                  ])
                : null,
            children: [
              gap8,
              AppCard(
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Wrap(spacing: 8, runSpacing: 8, children: [
                    Pill(t.status.label, icon: t.status.icon, tone: t.status.tone),
                    Pill(t.prio.label, icon: LucideIcons.flag, tone: t.prio.tone),
                    if (t.overdue) const Pill('Lewat tenggat', icon: LucideIcons.clock, tone: Tone.danger),
                  ]),
                  gap12,
                  Text(t.title, style: T.h3),
                  if (t.desc.isNotEmpty) ...[gap4, Text(t.desc, style: T.body.copyWith(color: C.text2))],
                  gap16,
                  const Divider(height: 1, color: C.border),
                  gap12,
                  Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    for (final (i, (icon, l, v)) in [
                      (LucideIcons.calendar, 'Tenggat', t.deadline),
                      (LucideIcons.building2, 'Divisi', t.division),
                      (LucideIcons.user, 'Dari', t.from.split(' ').first),
                    ].indexed) ...[
                      if (i > 0) gap8,
                      Expanded(
                        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                          Row(children: [Icon(icon, size: 13, color: C.text3), gap4, Text(l, style: T.small)]),
                          gap4,
                          Text(v, style: T.bodyM.copyWith(color: i == 0 && t.overdue ? C.dangerText : null), maxLines: 2, overflow: TextOverflow.ellipsis),
                        ]),
                      ),
                    ],
                  ]),
                ]),
              ),
              Overline('PENERIMA · ${rinaSent ? 1 : 0} DARI ${t.assignees.length} MENGIRIM BUKTI'),
              AppCard(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                child: Divided([
                  for (final (i, n) in t.assignees.indexed)
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 8),
                      child: Row(children: [
                        Avatar(personByName(n).initials, size: 36),
                        gap12,
                        Expanded(
                          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                            Text(n, style: T.title),
                            gap4,
                            Bar(n == rina.name && rinaSent ? 1 : (t.progress - i * .15).clamp(.1, .9), height: 4),
                          ]),
                        ),
                        gap12,
                        n == rina.name && rinaSent ? const Pill('Dikirim', icon: LucideIcons.check) : const Pill('Dalam proses', tone: Tone.info),
                      ]),
                    ),
                ]),
              ),
              if (rinaSent) ...[
                const Overline('BUKTI DARI RINA PRATIWI'),
                GridView.count(
                  crossAxisCount: 2,
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  mainAxisSpacing: 10,
                  crossAxisSpacing: 10,
                  childAspectRatio: 1.25,
                  children: [
                    for (final (i, l) in const ['Rak 1', 'Rak 2', 'Rak 3', 'Rak 4'].indexed)
                      ClipRRect(
                        borderRadius: BorderRadius.circular(16),
                        child: Stack(fit: StackFit.expand, children: [
                          ColoredBox(color: [const Color(0xFFD8C9B0), const Color(0xFFC9D6CF), const Color(0xFFD1C4B4), const Color(0xFFCBD3D8)][i]),
                          Center(child: Icon(LucideIcons.package, size: 40, color: i == 2 ? Colors.black12 : Colors.black26)),
                          Positioned(left: 8, bottom: 8, child: Pill(l, tone: Tone.neutral, solid: true)),
                          if (i == 2) const Positioned(right: 8, top: 8, child: Pill('Buram [DL]', icon: LucideIcons.triangleAlert, tone: Tone.warning, solid: true)),
                        ]),
                      ),
                  ],
                ),
                gap12,
                AppCard(
                  color: C.insightSubtle,
                  child: Row(children: [
                    const IconTile(LucideIcons.sparkles, tone: Tone.insight, size: 36, round: true),
                    gap12,
                    Expanded(
                      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        Text('Cek kualitas bukti [DL]', style: T.title.copyWith(color: C.insightText)),
                        Text('3 dari 4 foto jelas. Foto rak 3 buram, label stok tidak terbaca.', style: T.small.copyWith(color: C.insightText)),
                      ]),
                    ),
                  ]),
                ),
              ],
              if (t.checklist.isNotEmpty) ...[
                Overline('CHECKLIST · ${t.checklist.where((c) => c.done).length} DARI ${t.checklist.length}'),
                AppCard(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                  child: Column(children: [
                    for (final c in t.checklist)
                      Padding(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        child: Row(children: [CheckBox2(c.done), gap12, Expanded(child: Text(c.label, style: T.body))]),
                      ),
                  ]),
                ),
              ],
              const Overline('AKTIVITAS'),
              AppCard(
                child: Steps([
                  ('Dibuat ${t.from}', t.created, null),
                  if (rinaSent) ('Bukti dikirim Rina Pratiwi', 'Hari ini', Tone.success),
                  if (t.status == TaskStatus.revisi) ('Revisi diminta', 'Hari ini', Tone.warning),
                  if (t.status == TaskStatus.selesai) ('Disetujui Sari Wulandari', 'Hari ini', Tone.success),
                ]),
              ),
              Overline('KOMENTAR · ${t.comments.length}'),
              if (t.comments.isNotEmpty) ...[
                AppCard(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                  child: Divided([
                    for (final c in t.comments)
                      Padding(
                        padding: const EdgeInsets.symmetric(vertical: 10),
                        child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                          Avatar(c.who == 'Kamu' ? rina.initials : personByName(c.who).initials, size: 32),
                          gap12,
                          Expanded(
                            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                              Row(children: [Expanded(child: Text(c.who == 'Kamu' ? rina.name : c.who, style: T.bodyM)), Text(c.time, style: T.small)]),
                              gap4,
                              Text(c.text, style: T.body),
                            ]),
                          ),
                        ]),
                      ),
                  ]),
                ),
                gap12,
              ],
              Row(children: [
                Expanded(child: Field(hint: 'Tulis komentar', controller: comment)),
                gap8,
                CircleBtn(LucideIcons.send, bg: C.primary, fg: Colors.white, tooltip: 'Kirim', onTap: () {
                  if (comment.text.trim().isEmpty) return;
                  t.comments.add(Comment('Sari Wulandari', comment.text.trim(), 'Rab ${hm(app.now)}'));
                  comment.clear();
                  app.touch();
                }),
              ]),
            ],
          );
        },
      );
}

// ---------------------------------------------------------------- C09 persetujuan

/// Applies an HR decision with an undo toast.
void decide(BuildContext context, LeaveReq r, bool ok, {String? reason, bool aboveNav = false}) {
  final prev = (r.status, r.note, [...r.steps]);
  r
    ..status = ok ? LeaveStatus.disetujui : LeaveStatus.ditolak
    ..note = ok ? 'Disetujui Sari Wulandari, $_today' : 'Ditolak Sari Wulandari: $reason';
  if (r.steps.length == 3) {
    r.steps[1] = ('Atasan · Dimas Ardiansyah', 'Disetujui 6 Okt', Tone.success);
    r.steps[2] = ('HR · Sari Wulandari', ok ? 'Disetujui $_today' : 'Ditolak $_today', ok ? Tone.success : Tone.danger);
  }
  if (r.who == rina) {
    app.notifs.insert(0, Notif(NCat.pengajuan, ok ? '${r.type.label} disetujui' : '${r.type.label} ditolak', ok ? '${r.range} disetujui Sari Wulandari.' : 'Sari Wulandari: $reason', hm(app.now), 'HARI INI'));
  }
  app.touch();
  toast(context, '${r.type.label} ${r.who.name} ${ok ? 'disetujui' : 'ditolak'}', aboveNav: aboveNav, action: 'Batalkan', onAction: () {
    r
      ..status = prev.$1
      ..note = prev.$2;
    if (r.steps.length == 3) r.steps.setAll(0, prev.$3);
    if (r.who == rina) app.notifs.removeAt(0);
    app.touch();
  });
}

/// Reject sheet; returns the reason, or null when cancelled.
Future<String?> rejectReason(BuildContext context, LeaveReq r) {
  final c = TextEditingController();
  String? err;
  final first = r.who.name.split(' ').first;
  return showSheet<String>(
    context,
    (ctx) => StatefulBuilder(
      builder: (ctx, set) => Padding(
        padding: EdgeInsets.only(bottom: MediaQuery.viewInsetsOf(ctx).bottom),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          SheetHead('Tolak pengajuan $first?', subtitle: '${r.type.label} · ${r.range}'),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              ChipWrap(const ['Bentrok jadwal tim', 'Periode sibuk', 'Kuota tidak cukup', 'Lainnya'], (_) => false, (i) {
                c.text = const [
                  'Bentrok jadwal tim, sudah ada 2 orang cuti di tanggal yang sama.',
                  'Periode sibuk akhir bulan, mohon ajukan tanggal lain.',
                  'Kuota tidak cukup untuk jumlah hari yang diajukan.',
                  '',
                ][i];
                set(() => err = null);
              }),
              gap12,
              Field(hint: 'Tulis alasan penolakan', controller: c, maxLines: 3, error: err, helper: err == null ? '$first akan melihat alasan ini' : null),
              gap16,
              Btn('Tolak Pengajuan', kind: BtnKind.danger, icon: LucideIcons.x, onTap: () {
                if (c.text.trim().length < 20) return set(() => err = 'Alasan wajib diisi, minimal 20 karakter. $first akan melihatnya.');
                Navigator.pop(ctx, c.text.trim());
              }),
            ]),
          ),
        ]),
      ),
    ),
  );
}

Future<void> approveOne(BuildContext context, LeaveReq r, {bool aboveNav = false}) async => decide(context, r, true, aboveNav: aboveNav);

Future<bool> rejectOne(BuildContext context, LeaveReq r, {bool aboveNav = false}) async {
  final why = await rejectReason(context, r);
  if (why == null || !context.mounted) return false;
  decide(context, r, false, reason: why, aboveNav: aboveNav);
  return true;
}

class ApprovalCard extends StatelessWidget {
  const ApprovalCard(this.r, {super.key, this.selecting = false, this.selected = false, this.onSelect, this.aboveNav = false});
  final LeaveReq r;
  final bool selecting, selected, aboveNav;
  final VoidCallback? onSelect;

  @override
  Widget build(BuildContext context) {
    final waiting = r.status == LeaveStatus.menunggu;
    return AppCard(
      border: selected ? C.primary : null,
      onTap: selecting ? onSelect : () => push(context, ApprovalDetail(r), root: true),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          if (selecting) ...[CheckBox2(selected), gap12],
          Avatar(r.who.initials, size: 40),
          gap12,
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(r.who.name, style: T.title), Text('${r.who.role} · ${r.who.division}', style: T.small)])),
          Pill(waiting ? r.type.label : r.status.label, icon: waiting ? r.type.icon : r.status.icon, tone: waiting ? r.type.tone : r.status.tone),
        ]),
        gap12,
        Row(children: [const Icon(LucideIcons.calendar, size: 14, color: C.text3), gap8, Expanded(child: Text(r.range, style: T.bodyM))]),
        if (r.reason.isNotEmpty) ...[gap4, Text(r.reason, style: T.caption, maxLines: 2, overflow: TextOverflow.ellipsis)],
        if (r.extra != null && waiting) ...[gap4, Text(r.extra!, style: T.small)],
        if (r.conflict != null && waiting) ...[
          gap8,
          Row(children: [const Icon(LucideIcons.triangleAlert, size: 14, color: C.warningText), gap8, Expanded(child: Text(r.conflict!, style: T.small.copyWith(color: C.warningText, fontWeight: FontWeight.w600)))]),
        ],
        if (!waiting && r.note.isNotEmpty) ...[gap8, Text(r.note, style: T.small.copyWith(color: r.status.tone.fg))],
        if (waiting && !selecting) ...[
          gap12,
          Row(children: [
            Expanded(child: Btn('Tolak', kind: BtnKind.danger, height: 44, onTap: () => rejectOne(context, r, aboveNav: aboveNav))),
            gap12,
            Expanded(child: Btn('Setujui', height: 44, onTap: () => approveOne(context, r, aboveNav: aboveNav))),
          ]),
        ],
      ]),
    );
  }
}

class Approvals extends StatefulWidget {
  const Approvals({super.key});
  @override
  State<Approvals> createState() => _ApprovalsState();
}

class _ApprovalsState extends State<Approvals> {
  int tab = 0, sort = 0;
  bool selecting = false;
  final picked = <LeaveReq>{};

  Future<void> bulk(bool ok) async {
    final n = picked.length;
    if (ok) {
      if (!await confirm(context, title: 'Setujui $n pengajuan?', message: 'Semua karyawan terpilih akan mendapat notifikasi.', action: 'Setujui $n', icon: LucideIcons.circleCheck) || !mounted) return;
      for (final r in picked) {
        decide(context, r, true, aboveNav: true);
      }
    } else {
      final why = await rejectReason(context, picked.first);
      if (why == null || !mounted) return;
      for (final r in picked) {
        decide(context, r, false, reason: why, aboveNav: true);
      }
    }
    setState(() {
      picked.clear();
      selecting = false;
    });
    if (mounted) toast(context, '$n pengajuan ${ok ? 'disetujui' : 'ditolak'}', aboveNav: true);
  }

  @override
  Widget build(BuildContext context) => ListenableBuilder(
        listenable: app,
        builder: (context, _) {
          final waiting = app.approvals.where((r) => r.status == LeaveStatus.menunggu).length;
          var list = app.approvals.where((r) => r.status == LeaveStatus.values[tab]).toList();
          if (sort == 1) list = list.reversed.toList();
          return Screen(
              inShell: true,
              bottom: selecting
                  ? Padding(
                      padding: const EdgeInsets.only(bottom: navClearance - 16),
                      child: BottomActions([
                        Btn('Tolak ${picked.length}', kind: BtnKind.danger, onTap: picked.isEmpty ? null : () => bulk(false)),
                        Btn('Setujui ${picked.length}', onTap: picked.isEmpty ? null : () => bulk(true)),
                      ]),
                    )
                  : null,
              header: PageHeader('Persetujuan', subtitle: 'Izin, cuti dan dinas luar', actions: [
                if (tab == 0 && list.isNotEmpty)
                  TextButton(
                    onPressed: () => setState(() {
                      selecting = !selecting;
                      picked.clear();
                    }),
                    child: Text(selecting ? 'Batal' : 'Pilih beberapa', style: T.button.copyWith(color: C.primary)),
                  ),
              ]),
              children: [
                Segmented(['Menunggu $waiting', 'Disetujui', 'Ditolak'], tab, (i) => setState(() {
                      tab = i;
                      selecting = false;
                      picked.clear();
                    })),
                gap12,
                Row(children: [
                  Expanded(child: Text('${list.length} pengajuan', style: T.caption)),
                  SizedBox(
                    width: 170,
                    child: PickerBox(
                      value: const ['Terlama dulu', 'Terbaru dulu'][sort],
                      icon: LucideIcons.arrowUpDown,
                      onTap: () => setState(() => sort = 1 - sort),
                    ),
                  ),
                ]),
                if (tab == 0 && list.isNotEmpty && !selecting) ...[
                  gap8,
                  Row(children: [
                    const Icon(LucideIcons.moveHorizontal, size: 14, color: C.text3),
                    gap8,
                    Text('Geser kanan untuk setujui, kiri untuk tolak', style: T.small),
                  ]),
                ],
                gap12,
                if (list.isEmpty)
                  EmptyState(
                    icon: tab == 0 ? LucideIcons.inbox : LucideIcons.fileText,
                    title: tab == 0 ? 'Semua sudah diproses' : 'Belum ada pengajuan',
                    message: tab == 0 ? 'Tidak ada pengajuan yang menunggu persetujuanmu.' : 'Pengajuan yang ${tab == 1 ? 'disetujui' : 'ditolak'} muncul di sini.',
                  ),
                for (final r in list)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: tab == 0 && !selecting
                        ? Dismissible(
                            key: ObjectKey(r),
                            background: _swipeBg(true),
                            secondaryBackground: _swipeBg(false),
                            confirmDismiss: (d) async {
                              if (d == DismissDirection.startToEnd) {
                                decide(context, r, true, aboveNav: true);
                                return true;
                              }
                              return rejectOne(context, r, aboveNav: true);
                            },
                            child: ApprovalCard(r, aboveNav: true),
                          )
                        : ApprovalCard(
                            r,
                            aboveNav: true,
                            selecting: selecting,
                            selected: picked.contains(r),
                            onSelect: () => setState(() => picked.contains(r) ? picked.remove(r) : picked.add(r)),
                          ),
                  ),
              ],
          );
        },
      );

  Widget _swipeBg(bool ok) => Container(
        alignment: ok ? Alignment.centerLeft : Alignment.centerRight,
        padding: const EdgeInsets.symmetric(horizontal: 24),
        decoration: BoxDecoration(color: ok ? C.accent : C.danger, borderRadius: BorderRadius.circular(24)),
        child: Row(mainAxisSize: MainAxisSize.min, children: [
          Icon(ok ? LucideIcons.check : LucideIcons.x, color: Colors.white),
          gap8,
          Text(ok ? 'Setujui' : 'Tolak', style: T.button.copyWith(color: Colors.white)),
        ]),
      );
}

class ApprovalDetail extends StatelessWidget {
  const ApprovalDetail(this.r, {super.key});
  final LeaveReq r;

  @override
  Widget build(BuildContext context) => ListenableBuilder(
        listenable: app,
        builder: (context, _) => Screen(
          header: const TopBar('Detail Pengajuan'),
          bottom: r.status == LeaveStatus.menunggu
              ? BottomActions([
                  Btn('Tolak', kind: BtnKind.danger, icon: LucideIcons.x, onTap: () async {
                    final nav = Navigator.of(context);
                    if (await rejectOne(context, r)) nav.pop();
                  }),
                  Btn('Setujui', icon: LucideIcons.check, onTap: () {
                    decide(context, r, true);
                    Navigator.pop(context);
                  }),
                ])
              : null,
          children: [
            gap8,
            AppCard(
              child: Row(children: [
                Avatar(r.who.initials, size: 48),
                gap12,
                Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(r.who.name, style: T.h3), Text('${r.who.id} · ${r.who.role}', style: T.caption)])),
                Pill(r.status.label, icon: r.status.icon, tone: r.status.tone),
              ]),
            ),
            if (r.conflict != null && r.status == LeaveStatus.menunggu) ...[gap12, Banner2('Jadwal bentrok', body: r.conflict, icon: LucideIcons.triangleAlert)],
            if (r.status != LeaveStatus.menunggu && r.note.isNotEmpty) ...[gap12, Banner2(r.status.label, body: r.note, tone: r.status.tone, icon: r.status.icon)],
            gap12,
            AppCard(
              child: Column(children: [
                InfoRow('Jenis', r.type.label, icon: r.type.icon),
                InfoRow('Tanggal', r.range, icon: LucideIcons.calendar),
                if (r.extra != null) InfoRow('Keterangan', r.extra!, icon: LucideIcons.info),
              ]),
            ),
            if (r.file != null) ...[
              gap12,
              AppCard(
                onTap: () => toast(context, 'Membuka ${r.file}.'),
                child: Row(children: [
                  const IconTile(LucideIcons.paperclip),
                  gap12,
                  Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(r.file!, style: T.title), Text('Lampiran · ketuk untuk buka', style: T.small)])),
                  const Icon(LucideIcons.chevronRight, size: 18, color: C.text3),
                ]),
              ),
            ],
            const Overline('SISA CUTI'),
            _quota(),
            const Overline('ALUR PERSETUJUAN'),
            AppCard(
              child: Steps(r.steps.isNotEmpty
                  ? r.steps
                  : [
                      ('Diajukan ${r.who.name}', 'Diterima sistem', Tone.success),
                      ('Atasan · ${bossOf(r.who)}', 'Disetujui 6 Okt', Tone.success),
                      (
                        'HR · Sari Wulandari',
                        switch (r.status) { LeaveStatus.menunggu => 'Menunggu keputusanmu', LeaveStatus.disetujui => 'Disetujui', _ => 'Ditolak' },
                        switch (r.status) { LeaveStatus.menunggu => Tone.warning, LeaveStatus.disetujui => Tone.success, _ => Tone.danger },
                      ),
                    ]),
            ),
            const Overline('SURAT PENGAJUAN'),
            Letter(r),
          ],
        ),
      );

  Widget _quota() {
    final m = RegExp(r'Sisa cuti (\d+) dari (\d+) hari, setelah ini (\d+)').firstMatch(r.extra ?? '');
    final left = int.tryParse(m?.group(1) ?? '') ?? 12 - r.who.name.length % 5;
    final total = int.tryParse(m?.group(2) ?? '') ?? 12;
    final after = int.tryParse(m?.group(3) ?? '') ?? left;
    final cut = r.type == LeaveType.tahunan;
    return AppCard(
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          const IconTile(LucideIcons.plane, tone: Tone.success),
          gap12,
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('Sisa cuti tahunan ${r.who.name.split(' ').first}', style: T.caption),
              Text.rich(TextSpan(children: [TextSpan(text: '$left', style: T.numL.copyWith(color: C.primary)), TextSpan(text: ' dari $total hari', style: T.bodyM)])),
            ]),
          ),
          if (cut) Pill('Jadi $after hari', icon: LucideIcons.arrowRight, tone: after <= 3 ? Tone.warning : Tone.sage),
        ]),
        gap12,
        Bar(left / total, color: C.accent, height: 8),
        gap8,
        Text(cut ? 'Pengajuan ini memotong ${left - after} hari kuota' : '${r.type.label} tidak memotong kuota cuti tahunan', style: T.small),
      ]),
    );
  }
}
