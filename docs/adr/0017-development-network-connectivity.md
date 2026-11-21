# ADR 0017 — Konektivitas development: adb reverse + alamat API runtime

**Status:** Accepted (2026-11-21)

**Terkait:** `docs/development-local-network.md`, README HadirMetrik,
ADR 0016, HadirMetrik #16

## Konteks

Backend development berjalan di `http://` dan alamat komputer berubah menurut
jaringan (Wi-Fi kampus, hotspot, kos teman). Dua masalah muncul:

1. Android 9+ memblokir HTTP cleartext secara default, sehingga request ke
   backend lokal ditolak sebelum dikirim.
2. Alamat API di-hardcode saat kompilasi lewat `String.fromEnvironment`, jadi
   setiap pindah jaringan memaksa rebuild dengan `--dart-define` baru.

Beberapa perangkat adb sering tersambung bersamaan (wireless debugging), dan
`adb reverse` perlu dijalankan ulang setiap kali koneksi adb terputus.

## Keputusan

1. **Default development memakai loopback + `adb reverse`.** Aplikasi memakai
   `http://127.0.0.1:8000/api/v1`; `tool/dev_run.sh` menjalankan
   `adb reverse tcp:8000 tcp:8000` lalu `flutter run`. Script mendukung
   `ANDROID_SERIAL` agar aman saat ada beberapa perangkat.
2. **Alamat API ditentukan saat runtime** (`lib/server_config.dart`) dengan
   prioritas:
   1. `--dart-define=API_URL` — mengunci alamat untuk sesi/build itu;
   2. alamat tersimpan dari dialog **Server** (SharedPreferences);
   3. default platform: `127.0.0.1` untuk Android, `localhost` untuk web.
3. **Dialog Server hanya di build debug.** Layar Login menampilkan tombol
   **Server: …**; dialog menormalisasi alamat, menguji `GET /health`, lalu
   menyimpannya. Perubahan jaringan tidak memerlukan rebuild.
4. **Cleartext hanya untuk debug.**
   `android/app/src/debug/AndroidManifest.xml` menetapkan
   `android:usesCleartextTraffic="true"`; manifest utama dan build release
   tetap memakai default aman sehingga HTTP biasa ditolak.
5. **Alamat API bukan rahasia.** Token tetap disimpan di
   `flutter_secure_storage`; URL hanya konfigurasi koneksi.
6. **Dokumentasi operasional** ada di `docs/development-local-network.md` dan
   README repo mobile, termasuk troubleshooting dan perintah verifikasi.

## Konsekuensi

- Pindah jaringan tidak memerlukan rebuild selama perangkat masih terhubung
  lewat adb.
- `adb reverse` hilang saat koneksi adb terputus (mis. wireless debugging
  reconnect); jalankan `tool/dev_run.sh` atau `adb reverse` lagi.
- Tanpa adb, alamat LAN dapat diisi lewat dialog Server (tersimpan) atau
  `--dart-define=API_URL` untuk sekali jalan.
- Build release tidak memiliki jalur cleartext maupun dialog Server, sehingga
  produksi wajib HTTPS.
- Default `127.0.0.1` tidak berguna tanpa `adb reverse`; aplikasi akan gagal
  terhubung sampai alamat LAN diisi atau reverse dijalankan. Perilaku ini
  disengaja agar build debug tidak diam-diam mengarah ke host yang salah.

## Alternatif ditolak

- **IP LAN hardcoded di aplikasi** — rusak setiap pindah jaringan dan berbeda
  antar mesin developer.
- **`--dart-define` untuk setiap run** — benar secara teknis, tetapi memaksa
  restart/rebuild dan mudah salah ketika ada beberapa perangkat.
- **HTTPS lokal dengan sertifikat self-signed** — menambah instalasi CA di
  setiap perangkat dan tidak sepadan untuk development.
- **Tunnel publik (mis. ngrok)** — mengekspos backend development ke internet
  dan menambah ketergantungan eksternal.
- **Mengizinkan cleartext di manifest utama** — akan ikut ke build release dan
  memperlemah keamanan produksi.
