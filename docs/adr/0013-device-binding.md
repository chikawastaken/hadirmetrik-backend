# ADR 0013 — Binding Satu Akun Satu Perangkat

**Status:** Accepted (2026-10-24)

## Konteks

Login frontend menyatakan "Perangkat ini akan terikat ke akunmu"; HR punya toggle "Satu akun satu perangkat" dan aksi "Reset perangkat"; riwayat keamanan memuat "Ganti perangkat ditolak". Insiden HR menampilkan model perangkat dan OS.

## Keputusan

- Tabel `devices` per user dengan `device_uid_hash` (hash identifier instalasi, bukan IMEI), platform, model, versi OS/app, `status` `active` / `revoked`.
- Partial unique `(user_id) WHERE status = 'active'`.
- Saat binding wajib, login/presensi dari perangkat lain ditolak `DEVICE_NOT_TRUSTED` dan dicatat `security_events` (`device.rejected`).
- Reset oleh HR = `revoked` + cabut token Sanctum perangkat itu + `audit_logs` (`device.revoked`). Tidak ada hard delete.
- `attendance_records.device_id` (nullable) + `device_reference` (salinan hash) untuk jejak.

## Konsekuensi

- Penegakan di database; toggle organisasi hanya mengubah apakah penolakan diterapkan.
- Ganti HP resmi butuh HR (beban operasional, disengaja).

## Alternatif ditolak

- Menyimpan device id di `personal_access_tokens.name` — tidak bisa ditegakkan unik dan hilang saat token dicabut.
- Identifier hardware (IMEI/serial) — tidak tersedia di OS modern dan termasuk data sensitif.
