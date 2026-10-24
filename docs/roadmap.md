# HadirMetrik — Milestone & Implementation Roadmap

**Versi:** 1.2  
**Status:** Phase 1 backend selesai (modular monolith, PR #3); item Phase 0/1 yang tersisa bergantung pada repo mobile  
**Prioritas saat ini:** Menutup integrasi Flutter M1, lalu Phase 2 (backend)  
**Arsitektur:** Flutter mobile + Laravel modular monolith + PostgreSQL; face-recognition service terpisah hanya jika diperlukan.

---

## 0. Versi Teknologi

| Lapisan     | Teknologi        | Versi target         | Terverifikasi |
|-------------|------------------|----------------------|---------------|
| Runtime     | PHP              | ^8.3 (`composer.json`) | 8.5.8       |
| Framework   | Laravel          | ^13.17               | 13.37.0       |
| Auth        | Laravel Sanctum  | ^4.0                 | 4.3.3         |
| Mobile      | Flutter          | 3.47.x (stable)      | Belum — repo mobile terpisah |
| Bahasa UI   | Dart             | 3.13.x (ikut Flutter)| Belum — repo mobile terpisah |
| Database    | PostgreSQL       | 17+                  | 18.4 (lokal)  |

> Kolom **Terverifikasi** berasal dari `composer.lock`, `php artisan about`, dan
> `psql --version` di mesin development. Versi mobile ditetapkan oleh tim Flutter
> setelah repo mobile dibuat.

---

## 1. Prinsip Pengerjaan

1. Kerjakan satu **vertical slice** sampai berfungsi dari UI Flutter, REST API Laravel, database, hingga pengujian. Jangan menyelesaikan seluruh UI atau seluruh backend secara terpisah sebelum integrasi pertama.
2. Backend adalah sumber kebenaran untuk autentikasi, otorisasi, status presensi, approval, dan data gaji.
3. Kunci kontrak API sebelum implementasi Flutter dan Laravel berjalan paralel.
4. Bangun fitur berdasarkan dependensi: autentikasi dan identitas karyawan lebih dulu, lalu presensi, kemudian fitur administrasi lainnya.
5. Wajah, lokasi, dan data gaji membutuhkan kontrol privasi serta pengujian khusus; jangan menunda aspek keamanan sampai tahap akhir.

## 2. Ringkasan Phase dan Milestone

| Phase | Milestone | Hasil utama | Prioritas |
|---|---|---|---|
| 0 | Fondasi proyek dan kontrak API | Repository, environment lokal, struktur backend, kontrak API minimum | Wajib |
| 1 | **M1 — Authentication MVP** | Register, login, session restoration, `/me`, logout, token aman | **Backend selesai; Flutter menyusul** |
| 2 | M2 — Identitas karyawan dan akses berbasis peran | Profil karyawan, role/permission dasar, pengelolaan akun karyawan | Tinggi |
| 3 | M3 — Presensi inti | Check-in/out online, jadwal, lokasi, riwayat dan koreksi | Tinggi |
| 4 | M4 — Verifikasi wajah dan offline sync | Enrollment, evaluasi face verification, outbox lokal, sinkronisasi idempotent | Tinggi |
| 5 | M5 — Manajemen tugas | Pembuatan tugas, assignment, progres, checklist, bukti dan review | Menengah |
| 6 | M6 — Cuti, izin, dan lembur | Pengajuan, lampiran bila diperlukan, approval, riwayat status | Menengah |
| 7 | M7 — Transparansi gaji | Daftar periode, komponen gaji, slip gaji dan pembatasan akses | Menengah |
| 8 | M8 — Dashboard dan monitoring HR | Ringkasan presensi, keterlambatan, peta/lokasi sesuai kebijakan, antrean approval | Menengah |
| 9 | M9 — Production readiness | Security review, load test, backup/restore, monitoring dan deployment | Wajib sebelum produksi |

**Catatan urutan:** Presensi online dapat dimulai setelah autentikasi dan identitas karyawan siap. Face recognition dan offline sync berkaitan erat dengan presensi, tetapi jangan menganggap hasil AI di perangkat sebagai bukti yang otomatis tepercaya.

---

## 3. Phase 0 — Fondasi Proyek dan Kontrak API

### Tujuan
Menyiapkan alur kerja minimum supaya Flutter dan Laravel bisa dikembangkan dan diuji secara terintegrasi.

### Pekerjaan
- [x] Tentukan versi PHP/Laravel, Flutter/Dart, dan PostgreSQL yang dipakai tim. — Backend terverifikasi (PHP 8.5.8, Laravel 13.37.0, Sanctum 4.3.3, PostgreSQL 18.4); versi Flutter/Dart menunggu repo mobile.
- [x] Siapkan repository backend dan mobile sesuai pembagian tanggung jawab. — Repo backend ini aktif; repo mobile dikelola terpisah.
- [x] Siapkan `.env.example`; jangan commit file `.env` atau credential. — `.env` masuk `.gitignore`; tidak ada secret di repo.
- [x] Pastikan Laravel terhubung ke PostgreSQL. — `php artisan migrate:status` semua migrasi `Ran` di connection `pgsql`.
- [x] Tetapkan prefix API `/api/v1`.
- [x] Tambahkan endpoint health check untuk membedakan service hidup dari database/API bermasalah. — `GET /api/v1/health` (200 sehat, 503 degraded).
- [x] Buat dokumentasi OpenAPI minimum untuk auth. — `docs/openapi/auth.yaml` + panduan `docs/openapi/README.md`.
- [x] Tentukan format response sukses dan error yang konsisten. — `App\Shared\Http\ApiResponse` (`success`/`message`/`data`/`errors`); sejak Phase 1 kelas ini tinggal di `app/Shared`.

### Definition of Done
- [x] Backend dapat dijalankan lokal dan koneksi database terverifikasi (`php artisan serve` + `GET /api/v1/health` mengembalikan `database: connected`).
- [ ] Flutter dapat mengirim HTTP request ke backend lokal.
- [x] Kontrak auth dipahami oleh developer Flutter dan backend (OpenAPI di `docs/openapi/auth.yaml`).
- [x] Tidak ada secret yang disimpan di repository (`composer.json` tidak punya `minimum-stability` dev, `.env` tidak di-commit).

> Satu-satunya item Phase 0 yang belum tertutup adalah verifikasi HTTP request dari Flutter.
> Item ini hanya bisa diselesaikan setelah repo mobile dibuat; sampai saat itu backend
> menyediakan `/api/v1/health`, CORS, dan OpenAPI sebagai acuan kontrak.

---

## 4. Phase 1 — M1: Authentication MVP (Backend Selesai)

### Tujuan
Karyawan dapat mendaftar/aktivasi akun, login, tetap login setelah aplikasi ditutup, melihat identitas akun aktif, dan logout.

### Keputusan registrasi

HadirMetrik adalah aplikasi internal karyawan, sehingga **hindari registrasi publik yang langsung memberikan akses ke aplikasi**. Rekomendasi untuk MVP:

1. Data karyawan dibuat lebih dahulu oleh HR/admin atau melalui seeder awal.
2. Karyawan mendaftar menggunakan nomor karyawan, email kerja, password, dan kode undangan/aktivasi yang valid.
3. Backend memverifikasi bahwa karyawan tersebut memang sudah terdaftar dan belum memiliki akun.
4. Role ditetapkan oleh backend, bukan dipilih dari form registrasi.
5. Akun hanya diaktifkan sesuai hasil verifikasi/aktivasi yang ditetapkan.

Jika layanan email/OTP belum siap, gunakan kode undangan terbatas untuk development atau status `pending_verification`/`pending_approval`; jangan membuka pendaftaran bebas untuk akun aktif. Buat akun HR awal menggunakan seeder/command yang membaca credential dari environment, bukan credential hard-coded di source control.

### Backend Laravel — pekerjaan

- [x] Pasang dan konfigurasi Laravel Sanctum untuk API token mobile.
- [x] Buat/validasi struktur `users`, `employees`, role atau permission dasar, dan relasinya.
- [x] Implementasikan `POST /api/v1/register`.
- [x] Implementasikan `POST /api/v1/login`.
- [x] Implementasikan `GET /api/v1/me` dengan autentikasi.
- [x] Implementasikan `POST /api/v1/logout` untuk mencabut token saat ini.
- [x] Tambahkan validasi payload, respons error, rate limiting login/register, dan pencatatan peristiwa keamanan yang relevan.
- [x] Pastikan API tidak menerima `role`, `is_admin`, atau status istimewa dari payload registrasi.
- [x] Buat seeder data awal untuk pengujian lokal: akun HR, satu karyawan, dan/atau invitation code development.
- [x] Tambahkan automated feature tests untuk register, login, `/me`, logout, dan akses tidak sah.

### Status implementasi backend (selesai)

Seluruh item backend di atas sudah dikerjakan, diuji, dan di-commit pada commit
`4d61000` di branch `feature/auth-modular-monolith` (PR #3, base
`feature/phase-0-foundation`).

- **Struktur:** modular monolith `app/Modules/{Identity,Employees,Audit}` +
  `app/Shared`. Tiap modul punya `Contracts/`, `Application/`, `Infrastructure/`,
  `Http/`, dan `[Module]ServiceProvider`. Route tetap terpusat di `routes/` dan
  migration tetap di `database/migrations/`. Keputusan dicatat di
  `docs/adr/0001-modular-monolith-module-contracts.md`.
- **Batas modul:** lintas modul hanya lewat `Contracts`; data lintas modul berupa
  readonly DTO, bukan model Eloquent. Ditegakkan otomatis oleh
  `tests/Architecture/ModuleBoundaryTest.php` (tanpa dependency baru).
- **Port framework:** Sanctum, hashing, dan transaksi DB berada di balik port
  (`TokenIssuer`, `PasswordHasher`, `TransactionManager`) dengan implementasi di
  `Infrastructure/Services`.
- **Endpoint:** `POST /api/v1/register`, `POST /api/v1/login`, `GET /api/v1/me`,
  `POST /api/v1/logout` — bentuk respons tetap sesuai `docs/openapi/auth.yaml`.
- **Perilaku baru di atas Phase 0:**
  - kolom `users.role`; registrasi **selalu** menetapkan `role=employee` dari
    backend (field `role`/`is_admin` pada payload diabaikan);
  - register memverifikasi `employee_number` cocok dengan record HR;
  - kode undangan dikonsumsi secara atomik di dalam transaksi;
  - tabel `security_events` mencatat `register.succeeded`, `login.succeeded`,
    `login.failed`, dan `logout`.
- **Seeder:** password wajib dari environment (`SEED_ADMIN_PASSWORD`,
  `SEED_EMPLOYEE_PASSWORD`); tidak ada credential hard-coded. Variabel `SEED_*`
  didokumentasikan di `.env.example`.
- **Pengujian:** `php artisan test` → 26 tes / 83 assertion lulus; `pint --test`
  lulus. Uji manual pada PostgreSQL nyata: register `200`, `employee_number`
  salah `422`, kode undangan terpakai `422`, akun sudah ada `409`, login `200`,
  password salah `401`, `/me` `200`, logout `200`, `/me` setelah logout `401`,
  health `200`.

Item yang **belum** selesai murni ada di sisi Flutter (lihat bagian Flutter dan
Definition of Done di bawah).

### Flutter — pekerjaan

- [ ] Buat halaman Register dan Login.
- [ ] Buat `ApiClient` terpusat dengan base URL dari konfigurasi environment.
- [ ] Buat model request/response auth dan `AuthRepository`.
- [ ] Hubungkan form dengan endpoint API; tampilkan loading, validasi, dan error dari server.
- [ ] Simpan token melalui secure storage perangkat, bukan `SharedPreferences` atau penyimpanan teks biasa.
- [ ] Saat aplikasi dibuka, pulihkan sesi dengan memeriksa token dan memanggil `/me`.
- [ ] Buat state yang jelas: `unauthenticated`, `authenticating`, `authenticated`, dan `error`.
- [ ] Implementasikan logout, hapus token lokal, dan arahkan ke halaman login.
- [ ] Jangan menyimpan password setelah login.

### Kontrak endpoint minimum

> Kontrak final dan skema request/response ada di `docs/openapi/auth.yaml`.
> Bagian ini hanya ringkasan agar mudah dibaca dari roadmap.

#### `POST /api/v1/register`

Payload:

```json
{
  "employee_number": "EMP-0001",
  "work_email": "employee@example.com",
  "password": "example-password",
  "password_confirmation": "example-password",
  "invitation_code": "one-time-code"
}
```

`work_email` harus cocok dengan data karyawan di tabel `employees`, dan
`invitation_code` harus kode yang belum dipakai. Nama field dan formatnya sudah
dikunci di `docs/openapi/auth.yaml`. Nilai contoh bersifat fiktif.

Kebijakan saat ini: akun yang lolos verifikasi langsung aktif dan menerima
token. Jika kelak alur aktivasi diubah, jangan mengembalikan token sesi penuh
untuk akun yang belum diizinkan masuk.

#### `POST /api/v1/login`

```json
{
  "email": "employee@example.com",
  "password": "example-password",
  "device_name": "android-device"
}
```

Respons sukses:

```json
{
  "success": true,
  "message": "Login berhasil.",
  "data": {
    "token": "opaque-api-token",
    "token_type": "Bearer",
    "user": {
      "id": 2,
      "name": "Employee Example",
      "email": "employee@example.com"
    }
  }
}
```

Token asli tidak boleh ditulis ke log. Bentuk respons sudah dikunci di
`docs/openapi/auth.yaml`; tambahan field (mis. `role`) harus lewat perubahan
kontrak.

#### `GET /api/v1/me`

Header:

```http
Authorization: Bearer <token>
Accept: application/json
```

Mengembalikan identitas pengguna yang sedang login, bukan profil yang dipilih
melalui ID dari klien.

#### `POST /api/v1/logout`

Mencabut token yang sedang digunakan. Flutter kemudian menghapus token dari
secure storage.

### Definition of Done — Authentication MVP

M1 dianggap selesai apabila:

- [x] Register berhasil dengan data karyawan/invitation yang valid.
- [x] Register ditolak untuk karyawan yang tidak dikenal, email tidak cocok, undangan tidak valid, atau akun yang sudah terdaftar.
- [x] Pengguna bisa login dengan credential yang benar.
- [x] Password salah menghasilkan error yang aman dan tidak membocorkan apakah akun tertentu ada.
- [x] Endpoint terlindungi menolak request tanpa token atau dengan token tidak valid.
- [x] `/me` hanya mengembalikan identitas pengguna terautentikasi.
- [ ] Token tersimpan aman dan sesi dapat dipulihkan setelah aplikasi ditutup/dibuka kembali. _(sisi Flutter)_
- [ ] Logout mencabut token dan membersihkan penyimpanan lokal. _(pencabutan token backend selesai; pembersihan storage menyusul di Flutter)_
- [x] Role ditetapkan di backend dan tidak dapat dinaikkan melalui payload.
- [ ] Feature tests backend dan alur utama Flutter lulus. _(feature tests backend lulus: 26 tes; alur Flutter belum ada)_
- [ ] Flutter terhubung ke API Laravel yang berjalan di lingkungan development, bukan data dummy saja.

### Urutan implementasi untuk mempercepat hasil

1. Definisikan request/response auth dan kebijakan registrasi. ✅
2. Siapkan migration, model `User`/`Employee`, Sanctum, dan seeder akun uji. ✅ (modul `Identity`/`Employees`)
3. Implementasikan login, `/me`, logout, lalu tes dengan Postman/HTTP client. ✅
4. Implementasikan register dan aturan aktivasi. ✅
5. Integrasikan Flutter: login → simpan token → `/me` → auto-restore → logout. ⬜
6. Tambahkan register UI dan integrasikan ke backend. ⬜
7. Uji error, token kedaluwarsa/tidak valid, dan restart aplikasi. ⬜

Urutan ini memprioritaskan jalur login yang berfungsi sesegera mungkin tanpa menunda aturan keamanan registrasi.

---

## 5. Phase 2 — M2: Identitas Karyawan dan Akses Berbasis Peran

### Pekerjaan
- [ ] Lengkapi profil karyawan, status aktif/nonaktif, nomor karyawan, divisi, dan jabatan sesuai kebutuhan.
- [ ] Definisikan permission karyawan dan HR.
- [ ] Buat endpoint profil diri dan pembaruan field yang diizinkan.
- [ ] HR hanya dapat membaca/mengubah data sesuai scope kewenangannya.
- [ ] Tentukan alur aktivasi, penonaktifan, dan reset akun.

### Definition of Done
Akun pengguna terhubung ke satu data karyawan yang valid; akun nonaktif tidak dapat mengakses fitur terlindungi; kontrol akses diuji di sisi API.

---

## 6. Phase 3 — M3: Presensi Inti

### Pekerjaan
- [ ] Jadwal/shift dan aturan keterlambatan.
- [ ] Lokasi kerja dan kebijakan geofence, jika diwajibkan.
- [ ] Check-in dan check-out online.
- [ ] Riwayat presensi.
- [ ] Validasi duplikasi, urutan check-in/out, zona waktu, dan batas jadwal.
- [ ] Workflow koreksi presensi dengan audit log.

### Definition of Done
Presensi online bisa dilakukan end-to-end; aturan bisnis divalidasi server-side; retry tidak menciptakan catatan ganda; HR dapat meninjau dan mengoreksi melalui workflow yang terotorisasi.

---

## 7. Phase 4 — M4: Verifikasi Wajah dan Offline Sync

### Pekerjaan
- [ ] Pilih pendekatan inferensi di perangkat, server, atau hybrid melalui evaluasi teknis.
- [ ] Definisikan proses enrollment, versioning, revocation, dan deletion template wajah.
- [ ] Evaluasi face match dan liveness/anti-spoofing sebelum digunakan untuk keputusan produksi.
- [ ] Tambahkan local database/outbox untuk event presensi.
- [ ] Implementasikan batch sync, idempotency key, retry dengan backoff, dan hasil per event.
- [ ] Tentukan status `pending_sync`, `synced`, `pending_review`, `rejected`, dan `sync_failed`.
- [ ] Uji offline, app restart, timeout, duplikasi batch, dan model tidak tersedia.

### Definition of Done
Presensi offline disimpan secara tahan restart dan tersinkron tanpa duplikasi. Status lokal tidak disamakan dengan penerimaan final oleh server. Keputusan face recognition telah dievaluasi pada dataset yang mewakili penggunaan sebenarnya.

---

## 8. Phase 5 — M5: Manajemen Tugas

### Pekerjaan
- [ ] HR membuat dan mengubah tugas.
- [ ] Menetapkan penerima, prioritas, dan tenggat waktu.
- [ ] Karyawan melihat tugas dan memperbarui progres.
- [ ] Checklist dan lampiran/bukti kerja bila diperlukan.
- [ ] Submission, review, approval, dan request revision.
- [ ] Notifikasi opsional untuk assignment dan perubahan status.

### Definition of Done
Transisi status tugas hanya dapat dilakukan oleh aktor yang berwenang; semua submission dan keputusan review tersimpan serta bisa ditelusuri.

---

## 9. Phase 6 — M6: Pengajuan Cuti, Izin, dan Lembur

### Pekerjaan
- [ ] Form pengajuan dengan field berbeda sesuai jenis pengajuan.
- [ ] Validasi tanggal, jam lembur, overlap, dan field wajib.
- [ ] Lampiran privat jika diperlukan.
- [ ] Daftar pengajuan dan status.
- [ ] Approval/rejection/request revision oleh HR.
- [ ] Audit keputusan dan notifikasi opsional.

### Definition of Done
Pengajuan dapat dilacak dari submit hingga keputusan; request ganda dan keputusan berulang ditangani; file lampiran tidak dapat diakses pengguna tanpa izin.

---

## 10. Phase 7 — M7: Transparansi Gaji

### Keputusan wajib lebih dulu
Tetapkan apakah HadirMetrik hanya menampilkan slip gaji final dari HR/payroll atau juga menghitung payroll. Rekomendasi baseline: mulai dari **menampilkan data gaji final yang sudah divalidasi**, bukan membangun mesin payroll penuh sebelum aturan penggajian jelas.

### Pekerjaan
- [ ] Model periode dan salary statement.
- [ ] Rincian gaji pokok, tunjangan, lembur, potongan, dan gaji bersih.
- [ ] Import/input data dan publikasi oleh role payroll yang berwenang.
- [ ] Riwayat periode dan tampilan/download slip jika diperlukan.
- [ ] Uji authorization agar karyawan hanya dapat melihat gajinya sendiri.

### Definition of Done
Nilai bersumber dari data payroll yang disetujui; komponen dihitung/disimpan dengan tipe numerik yang tepat; akses lintas karyawan ditolak; perubahan publikasi tercatat.

---

## 11. Phase 8 — M8: Dashboard dan Monitoring HR

### Pekerjaan
- [ ] Ringkasan jumlah hadir, terlambat, dan belum check-out.
- [ ] Tren kehadiran dan antrean koreksi/approval.
- [ ] Lokasi presensi sesuai kebijakan privasi dan otorisasi.
- [ ] Filter tanggal, divisi, lokasi, serta status.
- [ ] Export laporan bila dibutuhkan.
- [ ] Dashboard dapat berupa web client terpisah; tetap memakai API Laravel yang sama.

### Definition of Done
Metrik dapat ditelusuri ke catatan sumber; query/report dibatasi oleh scope HR; fitur lokasi tidak mengumpulkan atau menampilkan data melebihi kebijakan yang disetujui.

---

## 12. Phase 9 — M9: Production Readiness

- [ ] Authorization test untuk setiap resource sensitif.
- [ ] Rate limiting, validasi upload, proteksi replay, dan pemeriksaan dependency.
- [ ] Uji beban pada puncak check-in/check-out.
- [ ] Backup database dan uji restore.
- [ ] Monitoring log, error, latency, queue backlog, dan kegagalan sync.
- [ ] TLS/domain/API routing dan pembatasan akses service internal.
- [ ] Kebijakan retensi/penghapusan untuk biometrik, lokasi, lampiran, dan data gaji.
- [ ] Prosedur incident response, deployment rollback, serta dokumentasi operasional.
- [ ] Review kewajiban UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi dan aturan lain yang relevan sebelum produksi.

---

## 13. Backlog Prioritas Sekarang

Daftar ini adalah pekerjaan konkret yang dapat langsung dibuat sebagai issue GitHub/GitLab.

### Epic: `AUTH — Authentication MVP`

1. `AUTH-01` — Sepakati kontrak OpenAPI untuk register/login/me/logout. ✅ `docs/openapi/auth.yaml`.
2. `AUTH-02` — Siapkan migration `users` dan relasi `employees`. ✅ termasuk kolom `users.role`.
3. `AUTH-03` — Konfigurasi Laravel Sanctum API tokens. ✅
4. `AUTH-04` — Seed akun HR development dan data employee uji tanpa credential hard-coded. ✅ password dari variabel `SEED_*`.
5. `AUTH-05` — Implementasi `POST /api/v1/login`. ✅
6. `AUTH-06` — Implementasi middleware auth dan `GET /api/v1/me`. ✅
7. `AUTH-07` — Implementasi `POST /api/v1/logout` dan pencabutan token. ✅
8. `AUTH-08` — Implementasi register/aktivasi terkontrol. ✅
9. `AUTH-09` — Tambahkan rate limit dan feature tests backend. ✅ 26 tes lulus.
10. `AUTH-10` — Buat Flutter `ApiClient`, auth model, dan repository. ⬜
11. `AUTH-11` — Buat UI Login dan integrasi API. ⬜
12. `AUTH-12` — Simpan/pulihkan token dengan secure storage. ⬜
13. `AUTH-13` — Buat UI Register dan integrasi API. ⬜
14. `AUTH-14` — Tambahkan logout, guard navigasi, dan session restoration. ⬜
15. `AUTH-15` — Uji end-to-end register → login → `/me` → restart app → logout. ⬜

> Catatan: `AUTH-05`–`AUTH-08` pada draf awal menulis path `/auth/*`; kontrak
> final memakai prefix `/api/v1` sesuai `docs/openapi/auth.yaml`.

### Urutan branch yang disarankan

Jangan memakai branch permanen `fe` dan `be` sebagai pengganti folder proyek. Gunakan branch pekerjaan yang bisa di-merge:

```text
main
├── feature/phase-0-foundation     # fondasi backend + kontrak API (PR #2)
└── feature/auth-modular-monolith  # Phase 1 backend, di-stack di atas phase-0 (PR #3)
    └── feature/auth-flutter       # integrasi M1 (belum dibuat)
```

Jika developer backend dan Flutter bekerja paralel, sepakati kontrak API pada `AUTH-01` terlebih dahulu. Backend dapat memakai contoh JSON kontrak untuk membuat test; Flutter dapat membuat repository/API client dengan kontrak sama.

> PR #3 masih berbasis `feature/phase-0-foundation` karena PR #2 belum di-merge.
> Setelah PR #2 masuk ke `main`, ubah base PR #3 menjadi `main` sebelum merge.

---

## 14. Acceptance Checklist untuk Demo Login/Register

MVP auth dianggap siap didemokan jika:

- [x] Akun uji tersedia melalui seeder yang aman.
- [x] Pengguna yang memenuhi syarat dapat mendaftar/aktivasi. _(terverifikasi di level API)_
- [ ] Pengguna dapat login melalui Flutter yang benar-benar memanggil Laravel API.
- [ ] Login gagal menampilkan pesan yang wajar, tanpa crash. _(backend sudah mengembalikan pesan aman; UI Flutter menyusul)_
- [ ] Setelah login, aplikasi menampilkan nama/identitas dari endpoint `/me`.
- [ ] Setelah aplikasi ditutup dan dibuka kembali, sesi dipulihkan dengan validasi ke server.
- [x] Logout membuat token lama tidak lagi bisa digunakan.
- [x] Endpoint protected menolak request tanpa autentikasi.
- [x] Role tidak bisa dimanipulasi dari payload klien.
- [x] Tes backend untuk happy path dan failure path lulus.

---

## 15. Referensi Implementasi

- Laravel Sanctum, autentikasi API token untuk klien mobile dan API: https://laravel.com/docs/sanctum
- `flutter_secure_storage`, penyimpanan key-value sensitif menggunakan mekanisme secure storage platform: https://pub.dev/packages/flutter_secure_storage

Versi package dan konfigurasi aktual harus disesuaikan dengan versi Flutter, Dart, PHP, dan Laravel yang dipilih proyek.
