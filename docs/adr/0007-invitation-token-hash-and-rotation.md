# ADR 0007 — Token undangan: hash + pepper, one-time use, rotasi saat resend

- Status: Accepted
- Date: 2026-10-24
- Deciders: backend team
- Related: `docs/architecture.md` §4.9, §5.6, §6.4, §12.6; ADR 0003

## Context

Fitur undangan bergeser dari kode aktivasi `invitation_codes` (dimiliki
oleh HR, disimpan plaintext, tanpa expiry, tanpa audit) menjadi link
undangan yang dikirim lewat email/WhatsApp untuk **registrasi wajah**.
Link yang dikirim via kanal bisa ter-leak (chat yang mudah diteruskan,
screenshot, log analytics). Token plaintext di database memperluas dampak bila
database atau dump terekspos. HR juga bisa perlu mengirim ulang link tanpa
token lama tetap berlaku.

## Decision

1. Token undangan = 32 byte random (CSPRNG) dari server, dikodekan hex/URLsafe.
2. Database menyimpan **hanya hash**: SHA-256(token + pepper) sebagai
   `token_hash varchar(64)` (unique). Pepper disimpan di environment server,
   tidak pernah di repository.
3. Perbandingan token memakai constant-time compare.
4. Token one-time use (`accepted` setelah enrollment sukses), punya
   `expires_at` (default 3 hari, rentang API 1–14 hari), dan bisa `revoked`.
5. **Resend = rotasi token**: undangan lama → `revoked`, dibuat undangan baru
   dengan token hash baru; hanya satu undangan aktif per `(employee_id,
   purpose)` — partial unique index.
6. Riwayat pengiriman disimpan di `invitation_deliveries` per channel
   (email/WhatsApp) dengan `attempt` unique; token tidak pernah muncul di
   delivery/log.

## Alternatives

- JWT/signed URL tanpa state: ditolak — revoke & one-time use memaksa
  database state tetap ada; signed URL juga menyisipkan data di link.
- Menyimpan token TERENKRIPSI (bukan hash): ditolak — masih plaintext efektif
  bila kunci dan dump bocor bersamaan; hash + pepper lebih aman.
- Kode 6 digit (contoh aktivasi lama): ditolak — ruang token terlalu kecil
  untuk link yang berumur sampai 14 hari.

## Consequences

- Positive: kebocoran database tidak membocorkan link; revoke/resend
  tegas; riwayat delivery bisa diaudit.
- Negative: link yang hilang di chat tidak bisa dibaca ulang oleh sistem
  (by design); HR wajib resend bila karyawan butuh link baru.
