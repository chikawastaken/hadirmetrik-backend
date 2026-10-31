import 'package:flutter/material.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';

import '../data.dart';
import '../state.dart';
import '../theme.dart';
import '../widgets.dart';

// ---------------------------------------------------------------- B06 tugas saya

class Tasks extends StatefulWidget {
  const Tasks({super.key});
  @override
  State<Tasks> createState() => _TasksState();
}

class _TasksState extends State<Tasks> {
  int seg = 0, chip = 0, due = 0, sort = 0, reload = 0;
  final prios = <int>{};
  bool searching = false;
  String q = '';

  bool _inSeg(TaskItem t) => switch (seg) {
        0 => t.status == TaskStatus.proses || t.status == TaskStatus.revisi || t.status == TaskStatus.terlambat,
        1 => t.status == TaskStatus.review,
        _ => t.status == TaskStatus.selesai,
      };

  List<TaskItem> get list {
    final r = app.tasks.where((t) {
      if (!_inSeg(t)) return false;
      if (chip > 0 && t.prio.index != chip - 1) return false;
      if (prios.isNotEmpty && !prios.contains(t.prio.index)) return false;
      if (due == 1 && !t.dueToday) return false;
      if (due == 2 && t.overdue) return false;
      if (due == 3 && !t.overdue) return false;
      return q.isEmpty || t.title.toLowerCase().contains(q.toLowerCase());
    }).toList();
    int urgency(TaskItem t) => t.overdue ? 0 : (t.dueToday ? 1 : 2);
    switch (sort) {
      case 0:
        r.sort((a, b) => urgency(a) - urgency(b));
      case 1:
        r.sort((a, b) => a.prio.index - b.prio.index);
      default:
        return r.reversed.toList();
    }
    return r;
  }

  bool get filtered => prios.isNotEmpty || due != 0 || sort != 0;

  void filterSheet() {
    final p = {...prios};
    var d = due, s = sort;
    showSheet(
      context,
      (ctx) => StatefulBuilder(
        builder: (ctx, set) => Column(mainAxisSize: MainAxisSize.min, children: [
          const SheetHead('Filter Tugas'),
          Padding(
          padding: const EdgeInsets.fromLTRB(20, 0, 20, 16),
          child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text('Prioritas', style: T.title),
            gap8,
            ChipWrap(const ['Tinggi', 'Sedang', 'Rendah'], p.contains, (i) => set(() => p.contains(i) ? p.remove(i) : p.add(i))),
            gap20,
            Text('Tenggat', style: T.title),
            gap8,
            ChipWrap(const ['Semua', 'Hari ini', '7 hari ke depan', 'Lewat tenggat'], (i) => i == d, (i) => set(() => d = i)),
            gap20,
            Text('Urutkan', style: T.title),
            for (final (i, l) in const ['Tenggat terdekat', 'Prioritas tertinggi', 'Terbaru'].indexed)
              InkWell(
                onTap: () => set(() => s = i),
                child: Padding(padding: const EdgeInsets.symmetric(vertical: 10), child: Row(children: [Expanded(child: Text(l, style: T.bodyM)), RadioDot(s == i)])),
              ),
            gap16,
            Row(children: [
              Expanded(child: Btn('Reset', kind: BtnKind.light, onTap: () => set(() {
                    p.clear();
                    d = 0;
                    s = 0;
                  }))),
              gap12,
              Expanded(
                child: Btn('Terapkan', onTap: () {
                  setState(() {
                    prios
                      ..clear()
                      ..addAll(p);
                    due = d;
                    sort = s;
                    reload++;
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

  @override
  Widget build(BuildContext context) => ListenableBuilder(
        listenable: app,
        builder: (context, _) {
          final active = app.tasks.where((t) => t.status == TaskStatus.proses || t.status == TaskStatus.revisi).length;
          final today = app.tasks.where((t) => t.dueToday && t.status != TaskStatus.selesai).length;
          final items = list;
          final segCount = app.tasks.where(_inSeg).length;
          return Screen(
            inShell: true,
            header: Column(children: [
              PageHeader('Tugas Saya', subtitle: '$active aktif · $today jatuh tempo hari ini', actions: [
                CircleBtn(searching ? LucideIcons.x : LucideIcons.search, bg: C.primary, fg: Colors.white, tooltip: 'Cari tugas', onTap: () => setState(() {
                      searching = !searching;
                      q = '';
                    })),
              ]),
              AnimatedSize(
                duration: const Duration(milliseconds: 200),
                child: searching
                    ? Padding(
                        padding: const EdgeInsets.fromLTRB(20, 0, 20, 12),
                        child: Field(hint: 'Cari judul tugas', icon: LucideIcons.search, onChanged: (v) => setState(() => q = v)),
                      )
                    : const SizedBox(width: double.infinity),
              ),
              Padding(padding: pagePad, child: Segmented(const ['Aktif', 'Review', 'Selesai'], seg, (i) => setState(() => seg = i))),
              gap12,
              ChipBar(
                const ['Semua', 'Tinggi', 'Sedang', 'Rendah'],
                chip,
                (i) => setState(() => chip = i),
                padding: pagePad,
                leading: CircleBtn(LucideIcons.slidersHorizontal, size: 40, dot: filtered, tooltip: 'Filter', onTap: filterSheet),
              ),
              gap8,
            ]),
            children: [
              if (seg > 0)
                Padding(
                  padding: const EdgeInsets.fromLTRB(4, 4, 4, 10),
                  child: Text(seg == 1 ? '$segCount tugas sedang dicek HR' : 'Oktober 2026 · $segCount tugas selesai', style: T.caption),
                ),
              Loading(
                token: (seg, chip, reload),
                skeleton: const SkelList(n: 3, h: 150),
                child: items.isEmpty
                    ? EmptyState(
                        icon: LucideIcons.clipboardCheck,
                        title: q.isNotEmpty || filtered || chip > 0 ? 'Tidak ada tugas yang cocok' : 'Belum ada tugas',
                        message: q.isNotEmpty || filtered || chip > 0 ? 'Coba ubah kata kunci atau filter.' : 'Tugas dari HR atau atasan akan muncul di sini. Santai dulu, nanti kami kabari.',
                        action: 'Muat ulang',
                        onAction: () => setState(() {
                          chip = 0;
                          prios.clear();
                          due = 0;
                          reload++;
                        }),
                      )
                    : Column(children: [
                        for (final t in items) Padding(padding: const EdgeInsets.only(bottom: 12), child: TaskCard(t, onTap: () => push(context, TaskDetail(t), root: true))),
                      ]),
              ),
            ],
          );
        },
      );
}

class TaskCard extends StatelessWidget {
  const TaskCard(this.t, {super.key, this.onTap, this.hr = false});
  final TaskItem t;
  final VoidCallback? onTap;
  final bool hr;

  @override
  Widget build(BuildContext context) {
    final late = t.overdue && t.status != TaskStatus.selesai;
    return AppCard(
      onTap: onTap,
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          Pill(t.prio.label, icon: LucideIcons.flag, tone: t.prio.tone),
          if (t.status != TaskStatus.proses) ...[gap8, Pill(t.status.label, icon: t.status.icon, tone: t.status.tone)],
          const Spacer(),
          Icon(LucideIcons.clock, size: 13, color: late ? C.danger : C.text3),
          gap4,
          Flexible(
            child: Text(late && !hr ? t.remaining : t.deadline, maxLines: 1, overflow: TextOverflow.ellipsis, style: T.small.copyWith(color: late ? C.dangerText : C.text3, fontWeight: late ? FontWeight.w700 : FontWeight.w500)),
          ),
        ]),
        gap12,
        Text(t.title, style: T.h3),
        gap8,
        Row(children: [
          if (hr)
            AvatarStack(t.assignees)
          else ...[
            Avatar(personByName(t.from).name == t.from ? personByName(t.from).initials : t.from.split(' ').map((w) => w[0]).take(2).join(), size: 24),
            gap8,
          ],
          Expanded(child: Text(hr ? '${t.assignees.first}${t.assignees.length > 1 ? ' +${t.assignees.length - 1}' : ''} · ${t.division}' : 'Dari ${t.from}, ${t.fromRole}', style: T.caption, maxLines: 1, overflow: TextOverflow.ellipsis)),
        ]),
        gap12,
        Row(children: [
          Expanded(child: Bar(t.progress, color: t.status == TaskStatus.selesai ? C.accent : (late ? C.danger : C.primary))),
          gap12,
          Text('${(t.progress * 100).round()}%', style: T.numS),
        ]),
        if (!hr && (t.attachments.isNotEmpty || t.comments.isNotEmpty)) ...[
          gap12,
          Row(children: [
            if (t.attachments.isNotEmpty) ...[const Icon(LucideIcons.paperclip, size: 14, color: C.text3), gap4, Text('${t.attachments.length} lampiran', style: T.small), gap16],
            if (t.comments.isNotEmpty) ...[const Icon(LucideIcons.messageSquare, size: 14, color: C.text3), gap4, Text('${t.comments.length} komentar', style: T.small)],
          ]),
        ],
      ]),
    );
  }
}

class AvatarStack extends StatelessWidget {
  const AvatarStack(this.names, {super.key, this.size = 26});
  final List<String> names;
  final double size;
  @override
  Widget build(BuildContext context) {
    final shown = names.take(3).toList();
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: SizedBox(
        width: size + (shown.length - 1) * size * .65,
        height: size,
        child: Stack(children: [
          for (var i = 0; i < shown.length; i++)
            Positioned(
              left: i * size * .65,
              child: Container(
                decoration: const BoxDecoration(shape: BoxShape.circle, color: Colors.white),
                padding: const EdgeInsets.all(1.5),
                child: Avatar(personByName(shown[i]).initials, size: size - 3),
              ),
            ),
        ]),
      ),
    );
  }
}

// ---------------------------------------------------------------- B07 detail tugas

class TaskDetail extends StatefulWidget {
  const TaskDetail(this.t, {super.key});
  final TaskItem t;
  @override
  State<TaskDetail> createState() => _TaskDetailState();
}

class _TaskDetailState extends State<TaskDetail> {
  TaskItem get t => widget.t;
  final comment = TextEditingController();
  final proofs = ['Rak 1', 'Rak 2'];
  bool busy = false;

  bool get editable => t.status == TaskStatus.proses || t.status == TaskStatus.revisi;

  @override
  void dispose() {
    comment.dispose();
    super.dispose();
  }

  void send() {
    final v = comment.text.trim();
    if (v.isEmpty) return;
    setState(() => t.comments.add(Comment('Kamu', v, 'Rab ${hm(app.now)}')));
    comment.clear();
    FocusScope.of(context).unfocus();
  }

  Future<void> submit() async {
    final again = t.status == TaskStatus.revisi;
    final ok = await confirm(
      context,
      title: again ? 'Kirim ulang untuk review?' : 'Kirim untuk review?',
      message: 'HR akan memeriksa hasil kerjamu. Progres dan checklist tidak bisa diubah selama review.',
      action: 'Kirim',
    );
    if (!ok || !mounted) return;
    setState(() => busy = true);
    await Future.delayed(const Duration(milliseconds: 700));
    if (!mounted) return;
    setState(() {
      busy = false;
      t.status = TaskStatus.review;
    });
    app.touch();
    toast(context, again ? 'Revisi terkirim. HR akan meninjau ulang.' : 'Tugas dikirim untuk review.');
  }

  @override
  Widget build(BuildContext context) {
    final done = t.checklist.where((c) => c.done).length;
    final (banner, bTone, bBody) = switch (t.status) {
      TaskStatus.review => ('Menunggu review HR', Tone.info, 'Dikirim hari ini. Kamu akan mendapat notifikasi setelah HR memeriksa.'),
      TaskStatus.revisi => ('Revisi diminta oleh ${t.from}', Tone.warning, '"${t.revisionNote ?? 'Mohon perbaiki bagian yang ditandai.'}"'),
      TaskStatus.selesai => ('Tugas disetujui', Tone.success, 'Selesai tepat waktu. Terima kasih atas kerjamu.'),
      _ when t.overdue => ('Lewat tenggat', Tone.danger, 'Tenggat ${t.deadline}. Segera kirim atau kabari ${t.from} lewat komentar.'),
      _ => (null, Tone.info, ''),
    };
    return Screen(
      header: const TopBar('Detail Tugas'),
      bottom: BottomActions([
        switch (t.status) {
          TaskStatus.review => const Btn('Menunggu Review', icon: LucideIcons.hourglass),
          TaskStatus.selesai => Btn('Kembali ke Daftar Tugas', kind: BtnKind.light, onTap: () => Navigator.pop(context)),
          TaskStatus.revisi => Btn('Kirim Ulang untuk Review', icon: LucideIcons.send, busy: busy, onTap: submit),
          _ => Btn('Kirim untuk Review', icon: LucideIcons.send, busy: busy, onTap: submit),
        },
      ]),
      children: [
        if (banner != null) ...[gap8, Banner2(banner, body: bBody, tone: bTone, icon: switch (t.status) { TaskStatus.selesai => LucideIcons.circleCheck, TaskStatus.review => LucideIcons.hourglass, TaskStatus.revisi => LucideIcons.rotateCcw, _ => LucideIcons.clockAlert })],
        gap12,
        Wrap(spacing: 8, runSpacing: 8, children: [
          Pill('Prioritas ${t.prio.label.toLowerCase()}', icon: LucideIcons.flag, tone: t.prio.tone),
          Pill(t.status.label, icon: t.status.icon, tone: t.status.tone),
        ]),
        gap12,
        Text(t.title, style: T.h1),
        gap8,
        Text(t.desc, style: T.body.copyWith(fontSize: 15, height: 1.5)),
        gap16,
        AppCard(
          child: Divided([
            InfoRow('Pemberi tugas', t.from, icon: LucideIcons.user),
            InfoRow('Dibuat', t.created, icon: LucideIcons.calendar),
            InfoRow('Tenggat', t.deadline, icon: LucideIcons.calendarCheck),
            if (t.remaining.isNotEmpty && t.status != TaskStatus.selesai) InfoRow('Sisa waktu', t.remaining, icon: LucideIcons.hourglass, valueColor: t.overdue ? C.dangerText : C.warningText),
          ]),
        ),
        if (t.attachments.isNotEmpty) ...[
          const Overline('Lampiran'),
          AppCard(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
            child: Divided([
              for (final (name, size) in t.attachments)
                InkWell(
                  onTap: () => toast(context, 'Mengunduh $name', icon: LucideIcons.download),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    child: Row(children: [
                      IconTile(name.endsWith('.pdf') ? LucideIcons.fileText : LucideIcons.fileSpreadsheet, tone: name.endsWith('.pdf') ? Tone.danger : Tone.success),
                      gap12,
                      Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(name, style: T.title), Text(size, style: T.small)])),
                      const Icon(LucideIcons.download, size: 18, color: C.text3),
                    ]),
                  ),
                ),
            ]),
          ),
        ],
        if (t.checklist.isNotEmpty) ...[
          const Overline('Checklist'),
          AppCard(
            padding: const EdgeInsets.fromLTRB(16, 14, 16, 6),
            child: Column(children: [
              CardTitle('$done dari ${t.checklist.length} selesai', trailing: '${(done / t.checklist.length * 100).round()}%'),
              Bar(done / t.checklist.length, color: C.accent),
              gap8,
              for (final c in t.checklist)
                InkWell(
                  onTap: editable ? () => setState(() => c.done = !c.done) : null,
                  child: Padding(
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    child: Row(children: [
                      CheckBox2(c.done),
                      gap12,
                      Expanded(child: Text(c.label, style: T.bodyM.copyWith(color: c.done ? C.text3 : C.text, decoration: c.done ? TextDecoration.lineThrough : null))),
                    ]),
                  ),
                ),
            ]),
          ),
        ],
        const Overline('Progres'),
        AppCard(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(children: [Expanded(child: Text('Progres pekerjaan', style: T.title)), Text('${(t.progress * 100).round()}%', style: T.numM.copyWith(color: C.primary))]),
            SliderTheme(
              data: SliderTheme.of(context).copyWith(overlayShape: SliderComponentShape.noOverlay),
              child: Padding(
                padding: const EdgeInsets.symmetric(vertical: 12),
                child: Slider(value: t.progress, divisions: 20, onChanged: editable ? (v) => setState(() => t.progress = v) : null, onChangeEnd: (_) => app.touch()),
              ),
            ),
            if (editable) const Field(label: 'Catatan progres', hint: 'Contoh: rak 1 dan 2 sudah dihitung, sisa rak 3 sampai 5', maxLines: 2),
          ]),
        ),
        const Overline('Bukti pekerjaan'),
        Wrap(spacing: 10, runSpacing: 10, children: [
          for (final p in proofs)
            Container(
              width: 96,
              height: 96,
              decoration: BoxDecoration(color: C.mid, borderRadius: BorderRadius.circular(16)),
              child: Stack(children: [
                const Center(child: Icon(LucideIcons.image, color: Colors.white, size: 28)),
                Positioned(
                  left: 8,
                  bottom: 8,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                    decoration: BoxDecoration(color: Colors.black.withValues(alpha: .45), borderRadius: BorderRadius.circular(6)),
                    child: Text(p, style: T.small.copyWith(color: Colors.white)),
                  ),
                ),
              ]),
            ),
          if (editable)
            InkWell(
              borderRadius: BorderRadius.circular(16),
              onTap: () {
                setState(() => proofs.add('Rak ${proofs.length + 1}'));
                toast(context, 'Foto bukti ditambahkan.', icon: LucideIcons.image);
              },
              child: Container(
                width: 96,
                height: 96,
                decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16), border: Border.all(color: C.mid, width: 1.5)),
                child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
                  const Icon(LucideIcons.upload, color: C.primary),
                  gap4,
                  Text('Unggah', style: T.caption.copyWith(color: C.primary)),
                ]),
              ),
            ),
        ]),
        const Overline('Aktivitas'),
        AppCard(
          child: Column(children: [
            for (final (i, (title, time)) in [
              ('Tugas dibuat oleh ${t.from}', t.created),
              if (t.progress > 0) ('Progres diperbarui ke ${(t.progress * 100).round()}%', 'Rab, 7 Okt, 10.20'),
              if (t.status == TaskStatus.revisi) ('Revisi diminta ${t.from}', 'Sel, 6 Okt, 15.10'),
              if (t.status == TaskStatus.review) ('Dikirim untuk review', 'Rab, 7 Okt, ${hm(app.now)}'),
              if (t.status == TaskStatus.selesai) ('Disetujui ${t.from}', t.deadline),
            ].reversed.indexed)
              Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Column(children: [
                  Container(width: 10, height: 10, margin: const EdgeInsets.only(top: 5), decoration: BoxDecoration(shape: BoxShape.circle, color: i == 0 ? C.primary : C.mid)),
                  Container(width: 2, height: 30, color: C.bg),
                ]),
                gap12,
                Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(title, style: T.bodyM), Text(time, style: T.small)])),
              ]),
          ]),
        ),
        Overline('Komentar (${t.comments.length})'),
        AppCard(
          child: Column(children: [
            for (final c in t.comments)
              Padding(
                padding: const EdgeInsets.only(bottom: 14),
                child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Avatar(c.who == 'Kamu' ? rina.initials : personByName(c.who).initials, size: 32, bg: c.who == 'Kamu' ? C.bg : C.sageSoft),
                  gap12,
                  Expanded(
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Row(children: [Expanded(child: Text(c.who, style: T.title)), Text(c.time, style: T.small)]),
                      gap4,
                      Text(c.text, style: T.body),
                    ]),
                  ),
                ]),
              ),
            if (t.comments.isEmpty) Padding(padding: const EdgeInsets.only(bottom: 12), child: Text('Belum ada komentar.', style: T.caption)),
            Field(
              controller: comment,
              hint: 'Tulis komentar untuk HR',
              suffix: IconButton(icon: const Icon(LucideIcons.send, size: 18, color: C.primary), tooltip: 'Kirim', onPressed: send),
            ),
          ]),
        ),
      ],
    );
  }
}
