# ADR 0009 — Status Presensi Server & Skema Offline yang Policy-Agnostic

**Status:** Accepted (2026-10-24)

## Konteks

Frontend menampilkan presensi tersimpan offline, insiden HR (risk score, sinyal fake GPS, mock location, root, akurasi GPS rendah, impossible travel), dan status "Menunggu". Kebijakan offline (§8.4) belum diputuskan product owner. State machine v1.0 (§5.1) mencampur state lokal dan server serta memiliki `REVIEW_ACCEPTED`/`REVIEW_REJECTED`.

## Keputusan

- Server hanya menyimpan `status` = `accepted` / `pending_review` / `rejected`, ditambah `decision_source` = `auto` / `review`.
- Setiap record menyimpan snapshot kebijakan yang diterapkan (`offline_policy`), `source` (`online` / `offline_sync` / `correction`), `reason_code`, `integrity_flags` (jsonb), `risk_score`, `location_status`, dan `distance_m`.
- Idempotency: `event_id` (uuid, unique) + `payload_hash` (SHA-256). `event_id` sama dengan hash beda → `IDEMPOTENCY_KEY_REUSED`.
- Batch offline dilacak di `sync_batches`, unik per `(employee_id, client_batch_id)`.
- Status harian (hadir/terlambat/absen/cuti/menunggu) diturunkan, tidak disimpan.

## Konsekuensi

- Ketiga kebijakan §8.4 bisa diaktifkan lewat setting tanpa migrasi.
- Query antrean review sederhana (`status = 'pending_review'`).
- Laporan menghitung status harian saat query; materialisasi ditunda sampai ada masalah performa.

## Alternatif ditolak

- Status `review_accepted`/`review_rejected` terpisah — menggandakan logika laporan; informasi yang sama didapat dari `decision_source`.
- Kolom per kebijakan atau tabel terpisah untuk event offline — memecah satu sumber kebenaran presensi.
- Hanya `event_id` tanpa hash — replay dengan isi berbeda tidak terdeteksi.
