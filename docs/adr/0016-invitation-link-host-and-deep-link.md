# ADR 0016 — Tautan undangan mengikuti host klien dan membuka deep link terdaftar

**Status:** Accepted (2026-11-21)

**Terkait:** `docs/architecture.md` §5.6, `docs/development-local-network.md`,
ADR 0007, ADR 0017, backend PR #32, HadirMetrik #17

## Konteks

Email undangan memuat tautan registrasi wajah. Sebelumnya basis URL diambil dari
`INVITATION_URL` statis di `.env` (mis. `http://192.168.1.5:8000/invitations`).
Alamat komputer development berubah menurut jaringan (Wi-Fi kampus, hotspot, kos
teman), sehingga tautan lama sering tidak bisa dibuka.

Klien email dan pemindai tautan memblokir skema kustom, jadi email tidak boleh
memuat `hadirmetrik://` secara langsung. Solusi yang sudah ada adalah halaman
perantara HTTP `GET /invitations/{token}` yang menawarkan tombol ke aplikasi.

Pada perangkat Android, tombol tersebut mengirim intent
`hadirmetrik://invitations/{token}`. Build aplikasi belum mendaftarkan skema itu
di manifest, sehingga intent tidak terselesaikan dan browser jatuh ke Play Store
dengan pesan **“Item tidak ditemukan”** karena aplikasi belum dipublikasikan.

## Keputusan

1. Tautan undangan dibangun dengan urutan prioritas:
   1. `INVITATION_URL` eksplisit — untuk produksi, wajib HTTPS;
   2. host/skema/port request API yang benar-benar dipakai klien;
   3. IPv4 LAN mesin untuk request loopback (`adb reverse`, perintah artisan);
   4. `APP_URL` + `/invitations` sebagai fallback terakhir.
2. URL diselesaikan **saat dispatch**, ketika request masih tersedia, lalu
   diteruskan ke job queue `SendInvitationDelivery`. Worker tidak lagi membaca
   `config('invitations.url')` karena tidak memiliki `Host` request.
3. Pemilihan IPv4 LAN mengabaikan loopback, link-local, dan adapter virtual
   (Docker, VPN, bridge emulator). Nama antarmuka fisik (`en0`, `wlan0`, `eth0`)
   dan rentang privat diprioritaskan.
4. Email hanya memuat URL halaman perantara HTTP. Halaman itu:
   - `noindex`, `no-referrer`, dan token berada di path—bukan query string;
   - tombol Android memakai
     `intent://invitations/{token}#Intent;scheme=hadirmetrik;package=com.hadirmetrik.hadirmetrik;end`;
   - menyediakan tautan `hadirmetrik://invitations/{token}` sebagai fallback.
5. Aplikasi mendaftarkan filter `VIEW` + `DEFAULT` + `BROWSABLE` untuk
   `scheme=hadirmetrik`, `host=invitations`. `app_links` menangani link awal
   (cold start) dan link yang datang saat aplikasi berjalan.
6. Token undangan tetap one-time, ber-hash, dan dirotasi saat resend sesuai
   ADR 0007; perubahan URL tidak mengubah model keamanan token.

## Konsekuensi

- Development mengikuti jaringan tanpa rebuild; produksi tetap dikunci lewat
  `INVITATION_URL` HTTPS.
- Email undangan yang sudah terkirim mempertahankan URL saat dibuat; undangan
  harus dibuat ulang atau di-resend setelah perubahan ini.
- Aplikasi harus dipasang ulang setelah filter manifest berubah, karena Android
  hanya mengenali skema dari manifest yang terpasang.
- Bila `INVITATION_URL` kosong dan mesin tidak memiliki IPv4 LAN, tautan jatuh
  ke `APP_URL` (umumnya `localhost`) dan hanya bisa dibuka di mesin itu.
- Halaman perantara menjadi titik yang terlihat publik di jaringan development;
  isinya hanya token undangan dan tidak meneruskan token ke pihak ketiga.

## Alternatif ditolak

- **Mempertahankan `INVITATION_URL` statis** — rapuh saat IP berubah dan sudah
  menyebabkan tautan mati.
- **Mengisi IP LAN dari konfigurasi saat undangan dibuat** — sama rapuh dan
  tidak mengikuti host yang benar-benar dipakai klien.
- **Menyelesaikan URL di dalam queue worker** — worker tidak punya `Host`
  request; hasilnya `localhost`.
- **Mengirim `hadirmetrik://` langsung di email** — banyak klien email dan
  pemindai tautan memblokir atau membuka skema kustom lebih dulu.
- **Android App Links `https://`** — memerlukan domain produksi, DNS, dan
  `assetlinks.json`; ditunda sampai deployment, bukan kebutuhan development.
