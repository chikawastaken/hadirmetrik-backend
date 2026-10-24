# ADR 0011 — Workflow Tugas per Assignee

**Status:** Accepted (2026-10-24)

## Konteks

Satu tugas di frontend dapat memiliki banyak assignee, masing-masing dengan progres, checklist, foto bukti, dan status (proses/review/revisi/selesai). State machine v1.0 (§5.2) diletakkan di `tasks`, sehingga satu assignee bisa mengubah status untuk semua.

## Keputusan

- State §5.2 berlaku di `task_assignments.status`, unik per `(task_id, employee_id)`, dengan `progress_percent` 0..100.
- `tasks.status` hanya lifecycle header: `draft` / `assigned` / `closed` / `cancelled`; `closed` otomatis saat semua assignment selesai/batal.
- `task_submissions` terikat ke `task_assignment_id`; review memakai `review_rating` 1–5 (wajib saat approve) dan `review_note` (wajib saat revisi). Maksimal satu submission `pending` per assignment.
- `terlambat` diturunkan dari `due_at`, tidak disimpan.

## Konsekuensi

- Progres dan review independen per orang; tidak ada konflik antar-assignee.
- Daftar tugas karyawan = query `task_assignments` milik sendiri.

## Alternatif ditolak

- Status di `tasks` + submission per employee — status header tidak mewakili banyak orang.
- Menduplikasi task per assignee — kehilangan konteks bersama (lampiran brief, komentar).
