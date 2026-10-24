# ADR 0012 — Periode Penggajian & Slip yang Dibekukan

**Status:** Accepted (2026-10-24)

## Konteks

Layar HR "Penggajian" bekerja per periode (Draft → Ditinjau → Disetujui → Dipublikasikan), menandai "Ditinjau" per karyawan, lalu mempublikasikan ke seluruh karyawan sekaligus dan "tidak bisa diubah lagi". Slip menampilkan baris pendapatan/potongan dengan keterangan. ADR 0006 memisahkan paket kompensasi dari slip.

## Keputusan

- `payroll_periods` (unik per `YYYY-MM`) menjadi unit kerja; status periode terpisah dari status statement.
- `salary_statements` menyimpan `total_earnings`, `total_deductions`, `net_salary` yang ditulis server, dengan check `net_salary = total_earnings - total_deductions`. Statement merujuk `employee_compensation_id` yang dipakai.
- Setelah `published`, statement immutable; koreksi = versi baru (`supersedes_statement_id`, versi lama → `superseded`). Partial unique menjaga satu statement hidup per `(employee_id, payroll_period_id)`.
- `salary_components` menyimpan `kind`, `code`, `label`, `explanation`, `quantity`, `rate`, `amount ≥ 0`, `source`; komponen `manual` wajib explanation.
- Publikasi massal `POST /hr/payroll-periods/{id}/publish` idempotent; dicatat di `audit_logs` dan `salary_publication_events` (append-only, menyimpan `idempotency_key`). Endpoint per statement tetap ada untuk koreksi/susulan.

## Konsekuensi

- Angka yang dilihat karyawan tetap sama walau rumus/kompensasi berubah.
- Total tersimpan berbeda dengan kebijakan v1.1 untuk kompensasi (total tidak disimpan) — disengaja karena slip adalah dokumen.

## Alternatif ditolak

- Menghitung slip on-the-fly — slip lama berubah saat aturan berubah.
- Hanya status per statement tanpa periode — tidak ada tempat untuk approval dan publikasi massal.
