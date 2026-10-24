# ADR 0008 — Pemisahan `security_events` dan `audit_logs`

- Status: Accepted
- Date: 2026-10-24
- Deciders: backend team
- Related: `docs/architecture.md` §6.4, §12.6

## Context

Sejak M1, terdapat tabel `security_events` yang mencatat telemetri auth
(`login.succeeded`, `login.failed`, `logout`). Fitur v1.1 menambah banyak
aksi **berkonsiensi** oleh HR/karyawan (create employee, ubah kompensasi,
assignment, undangan, enrollment wajah, terminasi) yang bukan hanya
telemetri: mereka butuh aktor, entitas target, before/after, dan reason.
Jika dua kebutuhan ini dilebur jadi satu tabel, kebisingan event login
menyembunyikan aksi HR, dan telemetri yang seharusnya ringan ikut menanggung
struktur snapshot before/after yang berat.

## Decision

1. `security_events` tetap menjadi **telemetri autentikasi** — ringan,
   event per permintaan: `login.succeeded`, `login.failed`, `logout`,
   `password.changed`, `password.reset.requested`, `invitation.accepted`,
   `face.enrollment.succeeded`, `face.enrollment.failed`. Tanpa
   password/token di context.
2. Tabel baru `audit_logs` (append-only) untuk **aksi yang berdampak
   business**: `employee.created`, `employee.updated`,
   `employee.employment_status_changed`, `compensation.created`,
   `assignment.created`, `invitation.created|resent|revoked|accepted`,
   `face.enrolled|revoked`, `user.status_changed`, `password.reset`.
   Kolom: actor, entity_type/entity_id, before/after snapshot (jsonb), reason,
   ip, user agent, recorded_at.
3. Kebijakan akses & retensi keduanya berbeda: `security_events` boleh
   dirotasi agak cepat; `audit_logs` retensi panjang (disepakati di
   open question retensi) dan tidak memuat data yang bisa membahayakan bila
   tersebar (tanpa password/token/face data).

## Alternatives

- Satu tabel "events" dengan kolom entitas nullable: ditolak — rapi di
  awal, gagal ketika butuh before/after + reason; konsumsi data (SIEM vs
  auditor HR terpisah).
- Menambah kolom entitas di `security_events`: ditolak — telemetri
  tidak sebaiknya dibawa keputusan bisnis.

## Consequences

- Positive: kategori pertanyaan investigasi dibaca pada tabel yang tepat;
  volume tabel terkendali; retensi bisa berbeda.
- Negative: tim harus memilih tabel yang tepat; ada perubahan kecil di kode
  logging lama untuk memisahkan kategori event.
