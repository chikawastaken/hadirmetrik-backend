# ADR 0014 — Lampiran Privat & Notifikasi In-App

**Status:** Accepted (2026-10-24)

## Konteks

Frontend memakai lampiran (brief tugas, foto bukti, surat dokter, surat tugas) dan inbox notifikasi berkategori (absensi, tugas, gaji, pengajuan, keamanan, pengumuman, pengingat, revisi) dengan read/unread dan "tandai semua dibaca". Aturan v1.1: PK `bigint`, data sensitif tidak publik.

## Keputusan

- Lampiran disimpan per domain (`task_attachments`, `request_attachments`), metadata saja di DB: `storage_disk`, `object_key` acak (unique), `original_name`, `mime_type`, `size_bytes`, `sha256`, `uploaded_by`. File di disk private; unduh lewat signed URL berumur pendek setelah policy object-level.
- Notifikasi di tabel `notifications` milik aplikasi (PK `bigint`, `category`, `title`, `body`, `data` jsonb, `read_at`, `push_status`), bukan tabel bawaan Laravel (UUID + morph). Isi notifikasi tidak memuat nominal gaji atau koordinat.

## Konsekuensi

- FK nyata ke entitas pemilik (tidak polimorfik) → integritas dan policy lebih sederhana.
- Domain baru yang butuh lampiran membuat tabel lampiran sendiri.
- Laravel Notification tetap dipakai, dengan channel database kustom yang menulis ke tabel ini.

## Alternatif ditolak

- Satu tabel `attachments` polimorfik — FK tidak bisa ditegakkan dan policy akses bercabang.
- URL publik/permanen — melanggar privasi surat dokter dan foto bukti.
- Tabel `notifications` bawaan Laravel — PK UUID bertentangan dengan aturan PK `bigint`.
