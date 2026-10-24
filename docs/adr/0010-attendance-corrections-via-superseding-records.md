# ADR 0010 — Koreksi Presensi lewat Record Pengganti

**Status:** Accepted (2026-10-24)

## Konteks

Frontend menyediakan "Ajukan Koreksi Kehadiran" dari riwayat dan sheet potongan keterlambatan. Potongan gaji (Rp 25.000 per keterlambatan) bergantung pada presensi, sehingga setiap perubahan harus dapat ditelusuri. §5.1 mensyaratkan record accepted tidak diubah diam-diam.

## Keputusan

- Pengajuan disimpan di `attendance_correction_requests` (state §5.12).
- Approval membuat record baru di `attendance_records` dengan `source = 'correction'`, `correction_request_id`, dan — bila ada record asli — `supersedes_record_id` (unique). Record asli tidak pernah diubah.
- Presensi yang tidak tercatat sama sekali (lupa check-out) dikoreksi dengan record `source = 'correction'` tanpa `supersedes_record_id`.
- Record efektif = `accepted` yang tidak di-supersede. Laporan dan payroll hanya membaca record efektif.

## Konsekuensi

- Histori lengkap: record asli, pengajuan, keputusan, dan record pengganti.
- Query record efektif butuh `NOT EXISTS` (atau view `effective_attendance_records`).
- Check DB: `source = 'correction'` ⇔ `correction_request_id IS NOT NULL`; `supersedes_record_id IS NOT NULL` ⇒ `source = 'correction'`.

## Alternatif ditolak

- Update in-place + `audit_logs` — audit menjadi satu-satunya bukti nilai lama; rawan untuk payroll.
- Tabel `attendance_revisions` terpisah — dua sumber kebenaran untuk nilai presensi.
