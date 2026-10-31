import 'dart:async';

import 'package:flutter/material.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';

import '../data.dart';
import '../main.dart';
import '../state.dart';
import '../theme.dart';
import '../visuals.dart';
import '../widgets.dart';

/// 0 Buat Pengajuan, 1 Status Pengajuan. Shared so "Lihat Status" can land on the right tab.
final leaveTab = ValueNotifier(0);

const _sakitReason = 'Demam tinggi dan radang tenggorokan sejak Senin malam. Dokter menyarankan istirahat 3 hari.';

int workDays(DateTime a, DateTime b) {
  var n = 0;
  for (var d = a; !d.isAfter(b); d = d.add(const Duration(days: 1))) {
    if (d.weekday < 6) n++;
  }
  return n;
}

String rangeText(DateTime a, DateTime b, int days, String durasi) => a == b
    ? '${tglPendek(a)} · $durasi'
    : '${hariPendek[a.weekday - 1]}, ${a.day} sampai ${tglPendek(b)} · $days hari';

// ---------------------------------------------------------------- B10 pengajuan

class LeavePage extends StatefulWidget {
  const LeavePage({super.key});
  @override
  State<LeavePage> createState() => _LeavePageState();
}

class _LeavePageState extends State<LeavePage> {
  var type = LeaveType.sakit;

  @override
  Widget build(BuildContext context) => ListenableBuilder(
        listenable: Listenable.merge([app, leaveTab]),
        builder: (context, _) => Screen(
          inShell: true,
          header: Column(children: [
            const PageHeader('Pengajuan', subtitle: 'Izin, cuti, sakit dan dinas luar'),
            Padding(padding: pagePad, child: Segmented(const ['Buat Pengajuan', 'Status Pengajuan'], leaveTab.value, (i) => leaveTab.value = i)),
            gap8,
          ]),
          children: leaveTab.value == 0 ? _create(context) : _status(context),
        ),
      );

  List<Widget> _create(BuildContext context) => [
        gap8,
        AppCard(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(children: [
              const IconTile(LucideIcons.plane, tone: Tone.success),
              gap12,
              Expanded(
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text('Sisa cuti tahunan', style: T.caption),
                  Text.rich(TextSpan(children: [TextSpan(text: '9', style: T.numL.copyWith(color: C.primary)), TextSpan(text: ' dari 12 hari', style: T.bodyM)])),
                ]),
              ),
            ]),
            gap12,
            const Bar(9 / 12, color: C.accent, height: 8),
            gap8,
            Text('Terpakai 3 hari · Cuti Tahunan 14 sampai 16 Okt', style: T.small),
          ]),
        ),
        const Overline('LANGKAH 1 DARI 2 · PILIH JENIS'),
        for (final t in LeaveType.values)
          Padding(
            padding: const EdgeInsets.only(bottom: 10),
            child: AppCard(
              onTap: () => setState(() => type = t),
              border: t == type ? C.primary : null,
              child: Row(children: [
                IconTile(t.icon, tone: t.tone),
                gap12,
                Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(t.label, style: T.title), Text(t.desc, style: T.caption)])),
                RadioDot(t == type),
              ]),
            ),
          ),
        gap12,
        Btn('Lanjut', icon: LucideIcons.arrowRight, onTap: () => push(context, LeaveForm(type), root: true)),
      ];

  List<Widget> _status(BuildContext context) => [
        gap8,
        if (app.leaves.isEmpty)
          const EmptyState(icon: LucideIcons.fileText, title: 'Belum ada pengajuan', message: 'Pengajuan izin, cuti dan sakit yang kamu kirim akan muncul di sini.')
        else
          for (final r in app.leaves)
            Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: AppCard(
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Row(children: [
                    IconTile(r.type.icon, tone: r.type.tone),
                    gap12,
                    Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(r.type.label, style: T.h3), Text(r.range, style: T.caption)])),
                    Pill(r.status.label, icon: r.status.icon, tone: r.status.tone),
                  ]),
                  if (r.steps.isNotEmpty) ...[gap16, Steps(r.steps)],
                  if (r.note.isNotEmpty) ...[
                    gap12,
                    Banner2(r.note, tone: r.status == LeaveStatus.ditolak ? Tone.danger : Tone.success, icon: r.status == LeaveStatus.ditolak ? LucideIcons.circleX : LucideIcons.messageSquare),
                  ],
                  if (r.status == LeaveStatus.menunggu) ...[
                    gap12,
                    Btn('Batalkan pengajuan', kind: BtnKind.light, icon: LucideIcons.x, height: 48, onTap: () async {
                      final ok = await confirm(context, title: 'Batalkan pengajuan?', message: '${r.type.label} ${r.range} akan ditarik dan tidak diproses atasan.', action: 'Batalkan Pengajuan', icon: LucideIcons.x, danger: true);
                      if (!ok || !context.mounted) return;
                      app.leaves.remove(r);
                      app.approvals.remove(r);
                      app.touch();
                      toast(context, 'Pengajuan ${r.type.label.toLowerCase()} dibatalkan.', aboveNav: true);
                    }),
                  ],
                ]),
              ),
            ),
      ];
}

/// Vertical approval timeline: (title, detail, tone of the dot).
class Steps extends StatelessWidget {
  const Steps(this.steps, {super.key});
  final List<(String, String, Tone?)> steps;
  @override
  Widget build(BuildContext context) => Column(children: [
        for (final (i, (title, detail, tone)) in steps.indexed)
          Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Column(children: [
              Container(
                width: 22,
                height: 22,
                decoration: BoxDecoration(shape: BoxShape.circle, color: tone?.bg ?? (i == 0 ? C.accentSubtle : C.bg)),
                child: Icon(
                  switch (tone) { Tone.success => LucideIcons.check, Tone.danger => LucideIcons.x, Tone.warning => LucideIcons.hourglass, _ => i == 0 ? LucideIcons.send : LucideIcons.circle },
                  size: 12,
                  color: tone?.fg ?? (i == 0 ? C.accent : C.text3),
                ),
              ),
              if (i < steps.length - 1) Container(width: 2, height: 22, color: C.bg),
            ]),
            gap12,
            Expanded(
              child: Padding(
                padding: const EdgeInsets.only(top: 1),
                child: Row(children: [
                  Expanded(child: Text(title, style: T.bodyM)),
                  Text(detail, style: T.small.copyWith(color: tone?.fg ?? C.text3, fontWeight: tone == null ? null : FontWeight.w700)),
                ]),
              ),
            ),
          ]),
      ]);
}

// ---------------------------------------------------------------- B10 form

class LeaveForm extends StatefulWidget {
  const LeaveForm(this.type, {super.key});
  final LeaveType type;
  @override
  State<LeaveForm> createState() => _LeaveFormState();
}

class _LeaveFormState extends State<LeaveForm> {
  late var type = widget.type;
  late DateTime start = type == LeaveType.sakit ? DateTime(2026, 10, 6) : DateTime(2026, 10, 8);
  late DateTime end = type == LeaveType.sakit ? DateTime(2026, 10, 8) : start;
  int durasi = 0;
  late final reason = TextEditingController(text: type == LeaveType.sakit ? _sakitReason : '');
  String delegate = 'Budi Santoso';
  String contact = '0812 3456 7821';
  String? file;
  double? upload;
  Timer? timer;
  bool tried = false;

  static const _durasi = ['Sehari penuh', 'Setengah hari pagi', 'Setengah hari siang'];

  int get days => workDays(start, end);
  bool get needsFile => type == LeaveType.dinas || (type == LeaveType.sakit && days > 1);
  bool get overQuota => type == LeaveType.tahunan && days > 9;
  String get fileLabel => type == LeaveType.dinas ? 'Surat tugas' : 'Surat dokter';

  List<String> get errors => [
        if (reason.text.trim().length < 10) 'Alasan minimal 10 karakter',
        if (needsFile && file == null) '$fileLabel wajib dilampirkan',
        if (days == 0) 'Pilih minimal 1 hari kerja',
      ];

  @override
  void dispose() {
    timer?.cancel();
    reason.dispose();
    super.dispose();
  }

  void pickType() => showSheet(
        context,
        (ctx) => Column(mainAxisSize: MainAxisSize.min, children: [
          const SheetHead('Ganti jenis pengajuan'),
          for (final t in LeaveType.values)
            ListTile(
              contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 2),
              leading: IconTile(t.icon, tone: t.tone),
              title: Text(t.label, style: T.title),
              subtitle: Text(t.desc, style: T.caption),
              trailing: RadioDot(t == type),
              onTap: () {
                setState(() {
                  type = t;
                  if (t == LeaveType.sakit && reason.text.isEmpty) reason.text = _sakitReason;
                });
                Navigator.pop(ctx);
              },
            ),
          gap12,
        ]),
      );

  void pickDate(bool isStart) {
    var month = (isStart ? start : end).month;
    showSheet(
      context,
      (ctx) => StatefulBuilder(
        builder: (ctx, set) => Column(mainAxisSize: MainAxisSize.min, children: [
          SheetHead(isStart ? 'Tanggal mulai' : 'Tanggal selesai'),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
            child: Column(children: [
              Row(children: [
                CircleBtn(LucideIcons.chevronLeft, size: 40, bg: C.sageSoft, tooltip: 'Bulan sebelumnya', onTap: month > 10 ? () => set(() => month--) : null),
                Expanded(child: Text('${bulan[month - 1]} 2026', textAlign: TextAlign.center, style: T.h3)),
                CircleBtn(LucideIcons.chevronRight, size: 40, bg: C.sageSoft, tooltip: 'Bulan berikutnya', onTap: month < 12 ? () => set(() => month++) : null),
              ]),
              gap12,
              MonthGrid(
                year: 2026,
                month: month,
                today: month == 10 ? 7 : null,
                selected: (isStart ? start : end).month == month ? (isStart ? start : end).day : null,
                onTap: (d) {
                  final v = DateTime(2026, month, d);
                  setState(() {
                    if (isStart) {
                      start = v;
                      if (end.isBefore(v)) end = v;
                    } else {
                      end = v.isBefore(start) ? start : v;
                    }
                  });
                  Navigator.pop(ctx);
                },
              ),
            ]),
          ),
        ]),
      ),
    );
  }

  void pickDelegate() => showSheet(
        context,
        (ctx) => Column(mainAxisSize: MainAxisSize.min, children: [
          const SheetHead('Delegasi tugas', subtitle: 'Rekan yang menggantikan selama kamu tidak masuk'),
          for (final p in employees.where((e) => e.division == 'Operasional' && e != rina))
            ListTile(
              contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 2),
              leading: Avatar(p.initials),
              title: Text(p.name, style: T.title),
              subtitle: Text(p.role, style: T.caption),
              trailing: RadioDot(p.name == delegate),
              onTap: () {
                setState(() => delegate = p.name);
                Navigator.pop(ctx);
              },
            ),
          gap12,
        ]),
      );

  void startUpload() {
    setState(() => upload = 0);
    timer = Timer.periodic(const Duration(milliseconds: 60), (t) {
      setState(() => upload = (upload ?? 0) + .04);
      if (upload! >= 1) {
        t.cancel();
        setState(() {
          upload = null;
          file = type == LeaveType.dinas ? 'Surat tugas kantor.pdf' : 'Surat dokter Klinik Medika.jpg';
        });
      }
    });
  }

  void next() {
    setState(() => tried = true);
    if (errors.isNotEmpty || overQuota) return;
    push(
      context,
      LeavePreview(
        LeaveReq(
          type: type,
          range: rangeText(start, end, days, _durasi[durasi]),
          status: LeaveStatus.menunggu,
          note: '',
          reason: reason.text.trim(),
          file: file,
          steps: [
            ('Diajukan Rina Pratiwi', '${tglPendek(app.now)}, ${hm(app.now)}', null),
            ('Atasan · Dimas Ardiansyah', 'Menunggu', Tone.warning),
            ('HR · Sari Wulandari', 'Menunggu', null),
          ],
          extra: type == LeaveType.tahunan ? 'Sisa cuti 9 dari 12 hari, setelah ini ${9 - days}' : (type == LeaveType.sakit ? 'Sakit tidak memotong cuti tahunan' : null),
        ),
        delegate: delegate,
        contact: contact,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final errs = tried ? errors : const <String>[];
    return Screen(
      header: const TopBar('Buat Pengajuan'),
      bottom: BottomActions([Btn('Lanjut ke Pratinjau', icon: LucideIcons.arrowRight, onTap: next)]),
      children: [
        const Overline('LANGKAH 2 DARI 2 · ISI DETAIL', padding: EdgeInsets.fromLTRB(4, 8, 4, 10)),
        if (errs.isNotEmpty) ...[
          Banner2('Lengkapi ${errs.length} isian berikut', body: errs.map((e) => '• $e').join('\n'), tone: Tone.danger, icon: LucideIcons.circleAlert),
          gap12,
        ],
        if (overQuota) ...[
          Banner2('Kuota cuti tidak cukup', body: 'Kamu mengajukan $days hari, sisa cuti tinggal 9 hari. Kurangi tanggal atau pilih Izin.', tone: Tone.danger, icon: LucideIcons.circleX),
          gap12,
        ],
        AppCard(
          child: Row(children: [
            IconTile(type.icon, tone: type.tone),
            gap12,
            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(type.label, style: T.title), Text(type.desc, style: T.caption)])),
            TextButton(onPressed: pickType, child: Text('Ganti', style: T.button.copyWith(color: C.primary))),
          ]),
        ),
        gap16,
        Row(children: [
          Expanded(child: PickerBox(label: 'Tanggal mulai', value: tglPendek(start), icon: LucideIcons.calendar, onTap: () => pickDate(true))),
          gap12,
          Expanded(child: PickerBox(label: 'Tanggal selesai', value: tglPendek(end), icon: LucideIcons.calendar, onTap: () => pickDate(false))),
        ]),
        gap8,
        Row(children: [
          const Icon(LucideIcons.info, size: 14, color: C.text3),
          gap4,
          Text('$days hari kerja${start != end ? ', Sabtu dan Minggu tidak dihitung' : ''}', style: T.caption),
        ]),
        if (start == end) ...[
          gap16,
          Text('Durasi', style: T.title),
          gap8,
          ChipWrap(_durasi, (i) => i == durasi, (i) => setState(() => durasi = i)),
        ],
        gap16,
        Field(
          label: 'Alasan',
          hint: 'Ceritakan singkat alasan pengajuanmu',
          controller: reason,
          maxLines: 4,
          maxLength: 500,
          onChanged: (_) => setState(() {}),
          error: tried && reason.text.trim().length < 10 ? 'Alasan minimal 10 karakter' : null,
        ),
        gap12,
        Text(needsFile ? '$fileLabel (wajib)' : 'Lampiran (opsional)', style: T.title),
        gap8,
        if (file != null)
          AppCard(
            padding: const EdgeInsets.fromLTRB(16, 10, 8, 10),
            child: Row(children: [
              const IconTile(LucideIcons.fileImage, tone: Tone.success),
              gap12,
              Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(file!, style: T.title, maxLines: 1, overflow: TextOverflow.ellipsis), Text('1,8 MB · terunggah', style: T.small)])),
              IconButton(tooltip: 'Hapus lampiran', icon: const Icon(LucideIcons.x, size: 18, color: C.text3), onPressed: () => setState(() => file = null)),
            ]),
          )
        else if (upload != null)
          AppCard(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Row(children: [
                Expanded(child: Text('Mengunggah ${fileLabel.toLowerCase()}...', style: T.title)),
                Text('${(upload! * 100).clamp(0, 100).round()}%', style: T.numS),
              ]),
              gap8,
              Bar(upload!.clamp(0, 1)),
            ]),
          )
        else
          AppCard(
            onTap: startUpload,
            border: tried && needsFile ? C.danger : C.mid,
            child: Column(children: [
              const Icon(LucideIcons.upload, color: C.primary),
              gap8,
              Text('Unggah ${fileLabel.toLowerCase()}', style: T.title.copyWith(color: C.primary)),
              Text('JPG, PNG atau PDF, maksimal 5 MB', style: T.small),
            ]),
          ),
        gap16,
        PickerBox(label: 'Delegasi tugas', value: delegate, icon: LucideIcons.userCheck, onTap: pickDelegate),
        gap16,
        Field(label: 'Kontak selama tidak masuk', icon: LucideIcons.phone, initial: contact, keyboard: TextInputType.phone, onChanged: (v) => contact = v),
      ],
    );
  }
}

// ---------------------------------------------------------------- preview, letter, sent

class LeavePreview extends StatefulWidget {
  const LeavePreview(this.req, {super.key, required this.delegate, required this.contact});
  final LeaveReq req;
  final String delegate, contact;
  @override
  State<LeavePreview> createState() => _LeavePreviewState();
}

class _LeavePreviewState extends State<LeavePreview> {
  bool busy = false;

  Future<void> send() async {
    setState(() => busy = true);
    await Future.delayed(const Duration(milliseconds: 900));
    if (!mounted) return;
    app.leaves.insert(0, widget.req);
    app.approvals.insert(0, widget.req);
    app.touch();
    Navigator.of(context).pushAndRemoveUntil(MaterialPageRoute(builder: (_) => LeaveSent(widget.req)), (r) => r.isFirst);
  }

  @override
  Widget build(BuildContext context) => Screen(
        header: const TopBar('Pratinjau Surat'),
        bottom: BottomActions([
          Btn('Ubah', kind: BtnKind.light, onTap: () => Navigator.pop(context)),
          Btn('Kirim Pengajuan', icon: LucideIcons.send, busy: busy, onTap: send),
        ]),
        children: [
          gap8,
          const Banner2('Periksa lagi sebelum dikirim', body: 'Surat ini akan diteruskan ke atasan lalu HR untuk disetujui.', tone: Tone.info, icon: LucideIcons.info),
          gap12,
          Letter(widget.req, delegate: widget.delegate, contact: widget.contact),
        ],
      );
}

class Letter extends StatelessWidget {
  const Letter(this.r, {super.key, this.delegate = 'Budi Santoso', this.contact = '0812 3456 7821'});
  final LeaveReq r;
  final String delegate, contact;

  @override
  Widget build(BuildContext context) {
    final title = switch (r.type) {
      LeaveType.sakit => 'SURAT IZIN SAKIT',
      LeaveType.izin => 'SURAT IZIN',
      LeaveType.tahunan => 'SURAT PERMOHONAN CUTI TAHUNAN',
      LeaveType.dinas => 'SURAT DINAS LUAR',
      LeaveType.khusus => 'SURAT PERMOHONAN CUTI KHUSUS',
    };
    Widget row(String k, String v) => Padding(
          padding: const EdgeInsets.symmetric(vertical: 3),
          child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
            SizedBox(width: 92, child: Text(k, style: T.caption)),
            Text(':  ', style: T.caption),
            Expanded(child: Text(v, style: T.bodyM)),
          ]),
        );
    return AppCard(
      padding: const EdgeInsets.all(20),
      radius: 16,
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          const Logo(size: 36),
          gap12,
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('PT Sinar Metrik Nusantara', style: T.title),
              Text('Jl. Magelang Km 5, Sleman, Yogyakarta', style: T.small),
            ]),
          ),
        ]),
        const Padding(padding: EdgeInsets.symmetric(vertical: 14), child: Divider(thickness: 1.5, color: C.text)),
        Center(child: Text(title, textAlign: TextAlign.center, style: T.h3.copyWith(letterSpacing: .5))),
        Center(child: Text('Nomor: 041/HM-SMN/X/2026', style: T.small)),
        gap16,
        Text('Yang bertanda tangan di bawah ini:', style: T.body),
        gap8,
        row('Nama', r.who.name),
        row('ID karyawan', r.who.id),
        row('Jabatan', r.who.role),
        row('Divisi', r.who.division),
        gap12,
        Text('Dengan ini mengajukan ${r.type.label.toLowerCase()} pada ${r.range}, dengan alasan:', style: T.body),
        gap8,
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(color: C.sageSoft, borderRadius: BorderRadius.circular(12)),
          child: Text(r.reason, style: T.bodyM.copyWith(fontStyle: FontStyle.italic)),
        ),
        gap12,
        if (r.file != null) row('Lampiran', r.file!),
        row('Delegasi', delegate),
        row('Kontak', contact),
        if (r.extra != null) row('Catatan', r.extra!),
        gap20,
        Align(
          alignment: Alignment.centerRight,
          child: Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
            Text('Sleman, 7 Oktober 2026', style: T.caption),
            gap8,
            Row(mainAxisSize: MainAxisSize.min, children: [const Icon(LucideIcons.badgeCheck, size: 16, color: C.accent), gap4, Text('Ditandatangani digital', style: T.small.copyWith(color: C.accent))]),
            gap4,
            Text(r.who.name, style: T.title),
          ]),
        ),
      ]),
    );
  }
}

class LeaveSent extends StatelessWidget {
  const LeaveSent(this.r, {super.key});
  final LeaveReq r;
  @override
  Widget build(BuildContext context) => Scaffold(
        backgroundColor: C.bg,
        body: SafeArea(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(children: [
              const Spacer(),
              Container(
                width: 120,
                height: 120,
                decoration: BoxDecoration(color: C.mid.withValues(alpha: .5), shape: BoxShape.circle),
                alignment: Alignment.center,
                child: Container(width: 84, height: 84, decoration: const BoxDecoration(color: C.primary, shape: BoxShape.circle), child: const Icon(LucideIcons.send, color: Colors.white, size: 36)),
              ),
              gap24,
              Text('Pengajuan ${r.type.label.toLowerCase()} terkirim', textAlign: TextAlign.center, style: T.h1),
              gap8,
              Text('Dimas Ardiansyah akan meninjau lebih dulu, lalu diteruskan ke HR. Kamu akan dapat notifikasi setiap ada perubahan.', textAlign: TextAlign.center, style: T.body),
              gap24,
              AppCard(child: Divided([InfoRow('Jenis', r.type.label, icon: r.type.icon), InfoRow('Tanggal', r.range, icon: LucideIcons.calendar), const InfoRow('Status', 'Menunggu atasan', icon: LucideIcons.hourglass, valueColor: C.warningText)])),
              const Spacer(),
              Btn('Lihat Status', onTap: () {
                leaveTab.value = 1;
                Navigator.pop(context);
                Shell.go(3);
              }),
              gap12,
              Btn('Kembali ke Beranda', kind: BtnKind.light, onTap: () {
                leaveTab.value = 0;
                Navigator.pop(context);
                Shell.go(0);
              }),
            ]),
          ),
        ),
      );
}
