# ADR 0015 — Autentikasi API dengan Laravel Sanctum

**Status:** Accepted (2026-10-27)

## Konteks

Klien utama adalah aplikasi Flutter (mobile) yang tidak bisa memakai session cookie biasa; HR dashboard (platform belum final) mungkin berjalan sebagai SPA di domain yang sama. Aturan bisnis menuntut token yang bisa dicabut satu per satu: reset perangkat oleh HR mencabut token perangkat itu (ADR 0013), dan suspend/terminate mencabut semua token user. Tidak ada pihak ketiga yang perlu akses API atas nama user.

## Keputusan

- Pakai `laravel/sanctum` untuk semua route terproteksi (`auth:sanctum` di `routes/api/v1/*`).
- Mobile memakai personal access token (`Authorization: Bearer`), satu token per perangkat dengan nama perangkat sebagai `name`; token disimpan sebagai hash di `personal_access_tokens`.
- Pencabutan = hapus baris token (`TokenIssuer::revoke`), berlaku seketika tanpa blacklist.
- Klien web first-party boleh memakai mode stateful Sanctum (cookie + CSRF) lewat `SANCTUM_STATEFUL_DOMAINS`.
- Sanctum hanya dipakai di balik port `Identity\Contracts\TokenIssuer`, implementasinya `SanctumTokenIssuer` di `Infrastructure/Services` (ADR 0001).

## Konsekuensi

- Satu paket resmi Laravel untuk mobile dan web; tidak ada dependensi auth pihak ketiga.
- Setiap request terautentikasi melakukan lookup token ke database (bisa di-cache bila perlu).
- `expiration` saat ini `null`: token tidak kedaluwarsa sendiri, keamanan bergantung pada pencabutan (logout, reset perangkat, suspend/terminate). Masa berlaku token perlu diputuskan sebelum rilis.
- Mengganti mekanisme auth cukup dengan adapter baru untuk `TokenIssuer`.

## Alternatif ditolak

- Laravel Passport (OAuth2) — server OAuth penuh tidak dibutuhkan karena tidak ada klien pihak ketiga; skema dan operasionalnya lebih berat.
- JWT (mis. `tymon/jwt-auth`) — stateless sehingga pencabutan per perangkat butuh blacklist terpisah; paket pihak ketiga.
- Session cookie saja — tidak cocok untuk klien mobile.
