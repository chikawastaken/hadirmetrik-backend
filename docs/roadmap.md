# HadirMetrik — Milestone & Implementation Roadmap

**Versi:** 1.5
**Terakhir diperbarui:** 2026-11-21
**Status:** Phase 1–3 MVP, Phase 4 backend + offline sync, dan Phase 5 backend selesai; M1 Flutter, konektivitas jaringan lokal, dan deep link undangan selesai; integrasi Flutter M5 menjadi slice berikutnya
**Prioritas saat ini:** Integrasi Flutter Phase 5, notifikasi in-app, lalu penguatan face recognition
**Arsitektur:** Flutter mobile + Laravel modular monolith + PostgreSQL; face-recognition service terpisah hanya jika diperlukan.

### Status terbaru — 2026-11-21

- **Konektivitas development** tidak lagi bergantung pada IP Wi-Fi: `adb reverse` + alamat API runtime (`ServerConfig`), dengan cleartext hanya di build debug. (HadirMetrik #16, ADR 0017)
- **Tautan undangan** mengikuti host yang dipakai klien; request loopback memakai IPv4 LAN mesin, dan `INVITATION_URL` tetap menjadi override produksi. (backend #32, ADR 0016)
- **Deep link undangan** `hadirmetrik://invitations/<token>` terdaftar di Android; tautan email sekarang membuka aplikasi, bukan Play Store. (HadirMetrik #17)
- **Verifikasi:** backend 157 tes / 631 assertions (SQLite + PostgreSQL 17), Flutter 69 tes, APK debug, serta uji perangkat fisik untuk health check dan deep link.

---

## 0. Versi Teknologi

| Lapisan     | Teknologi        | Versi target         | Terverifikasi |
|-------------|------------------|----------------------|---------------|
| Runtime     | PHP              | ^8.3 (`composer.json`) | 8.5.8       |
| Framework   | Laravel          | ^13.17               | 13.37.0       |
| Auth        | Laravel Sanctum  | ^4.0                 | 4.3.3         |
| Mobile      | Flutter          | 3.47.x (stable)      | 3.47.5        |
| Bahasa UI   | Dart             | 3.13.x (ikut Flutter)| 3.13.4        |
| Database    | PostgreSQL       | 17+                  | 18.4 (lokal)  |

> Kolom **Terverifikasi** berasal dari `composer.lock`, `php artisan about`,
> `psql --version`, serta `flutter --version`/`dart --version` di mesin
> development. Repo mobile (`HadirMetrik`) dikelola terpisah dari repo backend.

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
| 1 | **M1 — Authentication MVP** | Register, login, session restoration, `/me`, logout, token aman | **Selesai (backend + Flutter)** |
| 2 | M2 — Identitas karyawan dan akses berbasis peran | Profil karyawan, role/permission dasar, pengelolaan akun karyawan | Tinggi |
| 3 | M3 — Presensi inti | Check-in/out online, jadwal, lokasi, riwayat dan koreksi | Tinggi |
| 4 | M4 — Verifikasi wajah dan offline sync | Enrollment dan offline sync selesai; evaluasi face/liveness terbuka | Tinggi |
| 5 | M5 — Manajemen tugas | Backend selesai; integrasi Flutter dan notifikasi berikutnya | Menengah |
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
- [x] Tentukan versi PHP/Laravel, Flutter/Dart, dan PostgreSQL yang dipakai tim. — Backend terverifikasi (PHP 8.5.8, Laravel 13.37.0, Sanctum 4.3.3, PostgreSQL 18.4); mobile terverifikasi Flutter 3.47.5 / Dart 3.13.4.
- [x] Siapkan repository backend dan mobile sesuai pembagian tanggung jawab. — Repo backend ini aktif; repo mobile dikelola terpisah.
- [x] Siapkan `.env.example`; jangan commit file `.env` atau credential. — `.env` masuk `.gitignore`; tidak ada secret di repo.
- [x] Pastikan Laravel terhubung ke PostgreSQL. — `php artisan migrate:status` semua migrasi `Ran` di connection `pgsql`.
- [x] Tetapkan prefix API `/api/v1`.
- [x] Tambahkan endpoint health check untuk membedakan service hidup dari database/API bermasalah. — `GET /api/v1/health` (200 sehat, 503 degraded).
- [x] Buat dokumentasi OpenAPI minimum untuk auth. — `docs/openapi/auth.yaml` + panduan `docs/openapi/README.md`.
- [x] Tentukan format response sukses dan error yang konsisten. — `App\Shared\Http\ApiResponse` (`success`/`message`/`data`/`errors`); sejak Phase 1 kelas ini tinggal di `app/Shared`.

### Definition of Done
- [x] Backend dapat dijalankan lokal dan koneksi database terverifikasi (`php artisan serve` + `GET /api/v1/health` mengembalikan `database: connected`).
- [x] Flutter dapat mengirim HTTP request ke backend lokal. _(terbukti pada integrasi Phase 3/4; sejak 2026-11-21 lewat `adb reverse` + alamat API runtime, tanpa bergantung IP Wi-Fi)_
- [x] Kontrak auth dipahami oleh developer Flutter dan backend (OpenAPI di `docs/openapi/auth.yaml`).
- [x] Tidak ada secret yang disimpan di repository (`composer.json` tidak punya `minimum-stability` dev, `.env` tidak di-commit).

> Seluruh item Phase 0 sudah tertutup. Backend menyediakan `/api/v1/health`, CORS,
> dan OpenAPI sebagai acuan kontrak; Flutter sudah memanggil API sejak Phase 3/4.

### Status konektivitas lokal — 2026-11-21

- **Alamat API runtime:** `lib/server_config.dart` memilih `--dart-define=API_URL`
  → alamat tersimpan → default `http://127.0.0.1:8000/api/v1`; dialog **Server**
  di layar Login (build debug) menguji `/health` lalu menyimpan alamat.
- **`adb reverse`:** `tool/dev_run.sh` menjalankan `adb reverse tcp:8000 tcp:8000`
  lalu `flutter run`; mendukung beberapa perangkat lewat `ANDROID_SERIAL`.
- **Cleartext:** hanya `android/app/src/debug/AndroidManifest.xml`; build release
  tetap menolak HTTP biasa dan harus memakai HTTPS.
- **Dokumentasi:** `docs/development-local-network.md` dan README repo mobile.
- **Keputusan:** ADR 0017.

---

## 4. Phase 1 — M1: Authentication MVP (Selesai)

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

Sisi Flutter juga sudah selesai. Registrasi tidak memakai form publik: karyawan
masuk lewat tautan undangan (`/invitations/{token}/accept` dan
`/invitations/{token}/password`), lalu login biasa.

### Status Flutter M1 — 2026-11-21

- Sesi disimpan di `flutter_secure_storage` dan dipulihkan saat aplikasi dibuka;
  `/me` divalidasi di latar belakang sehingga cold start offline tetap bisa
  masuk, dan respons `401` memaksa logout. (HadirMetrik #15)
- Token berlaku 7 hari (`SANCTUM_EXPIRATION=10080`); `expires_at` disimpan klien. (backend #31)
- Login, alur undangan accept/set-password, logout, dan pesan error server
  terhubung ke API nyata.
- Konektivitas development mengikuti jaringan tanpa rebuild; lihat ADR 0017.
- Tautan undangan membuka aplikasi lewat skema `hadirmetrik://invitations/<token>`;
  lihat ADR 0016. (HadirMetrik #17)
- Verifikasi: `flutter test` → 69 passed; `flutter analyze` → 1 warning lama;
  `flutter build apk --debug` → sukses; login dan deep link diuji di perangkat fisik.

### Flutter — pekerjaan

- [x] Halaman Login dan alur undangan (accept/set-password); tidak ada registrasi publik.
- [x] `ApiClient` terpusat (`lib/api.dart`) dengan base URL dari `--dart-define=API_URL`.
- [x] Model request/response auth dan state sesi (`AppState`, `Session`).
- [x] Hubungkan form dengan endpoint API; tampilkan loading, validasi, dan error dari server.
- [x] Simpan token melalui `flutter_secure_storage`, bukan `SharedPreferences` atau penyimpanan teks biasa.
- [x] Saat aplikasi dibuka, pulihkan sesi dari secure storage dan validasi token lewat `/me` di latar belakang (tetap bisa masuk saat offline).
- [x] State sesi jelas: belum masuk, memulihkan, terautentikasi, dan sesi berakhir.
- [x] Implementasikan logout, hapus token lokal, dan arahkan ke halaman login.
- [x] Jangan menyimpan password setelah login.

**Kedaluwarsa token:** `SANCTUM_EXPIRATION` (menit, default `10080` = 7 hari).
Login dan set-password mengembalikan `data.expires_at`; klien menyimpannya dan
meminta login ulang setelah lewat. Respons `401` pada permintaan terproteksi
menghapus sesi dan kembali ke Login.

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
- [x] Token tersimpan aman dan sesi dapat dipulihkan setelah aplikasi ditutup/dibuka kembali. _(secure storage + restore; token berlaku 7 hari)_
- [x] Logout mencabut token dan membersihkan penyimpanan lokal.
- [x] Role ditetapkan di backend dan tidak dapat dinaikkan melalui payload.
- [x] Feature tests backend dan alur utama Flutter lulus. _(backend 157 tes / 631 assertions di SQLite + PostgreSQL 17; Flutter 69 tes)_
- [x] Flutter terhubung ke API Laravel yang berjalan di lingkungan development, bukan data dummy saja.

### Urutan implementasi untuk mempercepat hasil

1. Definisikan request/response auth dan kebijakan registrasi. ✅
2. Siapkan migration, model `User`/`Employee`, Sanctum, dan seeder akun uji. ✅ (modul `Identity`/`Employees`)
3. Implementasikan login, `/me`, logout, lalu tes dengan Postman/HTTP client. ✅
4. Implementasikan register dan aturan aktivasi. ✅
5. Integrasikan Flutter: login → simpan token → `/me` → auto-restore → logout. ✅
6. Tambahkan alur undangan (accept/set-password) dan integrasikan ke backend. ✅
7. Uji error, token kedaluwarsa/tidak valid, dan restart aplikasi. ✅

Urutan ini memprioritaskan jalur login yang berfungsi sesegera mungkin tanpa menunda aturan keamanan registrasi.

---

## 5. Phase 2 — M2: Identitas Karyawan dan Akses Berbasis Peran

### Pekerjaan
- [x] Lengkapi profil karyawan, status aktif/nonaktif, nomor karyawan, divisi, dan jabatan sesuai kebutuhan. _(`GET/PATCH /me/profile`, detail karyawan HR)_
- [x] Definisikan permission karyawan dan HR. _(role `employee`/`hr`/`admin` + middleware `hr.role`)_
- [x] Buat endpoint profil diri dan pembaruan field yang diizinkan. _(`EmployeeProfileController`, `UpdateEmployeeProfileRequest`)_
- [x] HR hanya dapat membaca/mengubah data sesuai scope kewenangannya. _(endpoint `/hr/*` ditolak untuk role employee)_
- [x] Tentukan alur aktivasi, penonaktifan, dan reset akun. _(invitation, accept/set-password, resend/revoke, reset password HR)_

### Definition of Done
Akun pengguna terhubung ke satu data karyawan yang valid; akun nonaktif tidak dapat mengakses fitur terlindungi; kontrol akses diuji di sisi API.

### Status penutupan — 2026-11-03
Phase 2 dinyatakan selesai berdasarkan implementasi dan test yang sudah ada; sebelumnya hanya belum dicentang di dokumen ini.

---

## 6. Phase 3 — M3: Presensi Inti

### Pekerjaan
- [x] Jadwal/shift dan aturan keterlambatan. _(MVP: daftar shift aktif dari tabel `shifts`; perhitungan keterlambatan belum)_
- [x] Lokasi kerja dan kebijakan geofence, jika diwajibkan. _(MVP: indikator area di Flutter; enforcement server-side belum)_
- [x] Check-in dan check-out online. _(`POST /clock-in`, `POST /clock-out`, tersimpan di `clock_records`)_
- [x] Riwayat presensi. _(`GET /clock`, `GET /history`)_
- [x] Validasi duplikasi, urutan check-in/out, zona waktu, dan batas jadwal. _(MVP: duplikasi & urutan divalidasi; zona waktu & batas jadwal belum)_
- [x] Workflow koreksi presensi dengan audit log. _(MVP: `PATCH /history/{id}/correction` → `pending_review`; audit log belum)_

### Definition of Done
Presensi online bisa dilakukan end-to-end; aturan bisnis divalidasi server-side; retry tidak menciptakan catatan ganda; HR dapat meninjau dan mengoreksi melalui workflow yang terotorisasi.

### Status penutupan — 2026-11-01
Phase 3 ditutup sebagai **MVP vertical slice** (backend + Flutter):

- **Backend** (`hadirmetrik-backend`, PR #21): `POST /clock-in`, `POST /clock-out`, `GET /clock`, `GET /schedule`, `GET /history`, `PATCH /history/{id}/correction`; model `ClockRecord` + migrasi `clock_records`.
- **Flutter** (`HadirMetrik`, PR #9 + #11): `ClockScreen`, `recordClock()` async ke API, method API `clockIn/clockOut/getSchedule/getHistory/correctHistory`.
- **Verifikasi**: `php artisan test` lulus; `flutter analyze` 0 error; `flutter build apk --debug` sukses.
- **Ditunda**: enforcement geofence server-side, aturan keterlambatan, batas jadwal, dan audit log koreksi (kandidat Phase 4/8).

---

## 7. Phase 4 — M4: Verifikasi Wajah dan Offline Sync

### Pekerjaan
- [ ] Pilih pendekatan inferensi di perangkat, server, atau hybrid melalui evaluasi teknis.
- [x] Definisikan proses enrollment, versioning, revocation, dan deletion template wajah. _(backend: `face_templates`, `face_enrollment_requests`, `/face/*`)_
- [ ] Evaluasi face match dan liveness/anti-spoofing sebelum digunakan untuk keputusan produksi.
- [x] Tambahkan local database/outbox untuk event presensi. _(backend: `attendance_sync_batches`, `attendance_sync_events`; Flutter: `lib/outbox.dart` di atas `shared_preferences`)_
- [x] Implementasikan batch sync, idempotency key, dan hasil per event. _(backend + `AppState.flushOutbox`; batch key dipakai ulang sampai semua event dijawab server, respons tidak lengkap dipolling lewat `GET /sync/attendance/{id}`)_
- [x] Tambahkan retry otomatis dengan backoff di klien. _(Flutter: backoff berbatas, error per event, urutan kausal; flush dipicu saat aplikasi dibuka, setelah login, saat membuka Profil/layar absen, dan lewat tombol manual)_
- [x] Tentukan status `pending_sync`, `synced`, `pending_review`, `rejected`, dan `sync_failed`. _(`attendance_sync_events.status` + `clock_records.status`; `pending_review` dianggap final untuk outbox karena server sudah menyimpan event)_
- [x] Uji offline, app restart, timeout, duplikasi batch, dan model tidak tersedia. _(test Flutter: reload outbox, hidrasi jam setelah restart, flush gagal, respons 5xx, penolakan server, duplikasi backend; konektivitas perangkat sudah diverifikasi 2026-11-21 lewat `adb reverse`, health check, dan deep link; uji offline penuh di perangkat belum)_

### Definition of Done
Presensi offline disimpan secara tahan restart dan tersinkron tanpa duplikasi. Status lokal tidak disamakan dengan penerimaan final oleh server. Keputusan face recognition telah dievaluasi pada dataset yang mewakili penggunaan sebenarnya.

### Status backend — 2026-11-01
Backend Phase 4 (kontrak + sinkronisasi) selesai, model wajah belum:

- `POST /face/enroll`, `GET /face/enrollments/{id}`, `GET /face/templates`, `POST /face/templates/{id}/revoke`, `DELETE /face/templates/{id}`.
- `POST /sync/attendance`, `GET /sync/attendance/{id}`; batch idempotent, event idempotent, status per event.
- Enrollment tetap `pending` sampai provider/model wajah tersedia.
- Verifikasi: `php artisan test` → 101 passed (456 assertions), termasuk run langsung terhadap PostgreSQL 17 di Docker.

### Status Flutter — 2026-11-01
Klien Phase 4 terhubung ke kontrak backend (PR HadirMetrik #12, ditinjau ulang di #13):

- `FaceEnrollment`/`FaceTemplate` + layar `Data Wajah`; kartu profil tidak lagi mengklaim "Terdaftar · 5 sudut".
- `FaceCapture` mengirim 5 sampel; hasil ditampilkan sebagai `Menunggu verifikasi`, bukan terverifikasi. Upload gagal dilaporkan jujur dan tidak pernah ditampilkan sebagai terverifikasi.
- Outbox tahan restart (`shared_preferences`) menyimpan event dan batch key, dibatasi per akun, dan dipulihkan saat aplikasi dibuka. `recordClock` menguras antrean lebih dulu, menyimpan ke outbox saat jaringan gagal atau server 5xx, dan tidak mengulang event yang ditolak server.
- `occurred_at` dikirim sebagai UTC eksplisit; batch key hanya dirotasi setelah semua event dijawab server.
- `SyncCard` dan layar absen menampilkan jumlah event tertunda, event `pending_review`, serta penolakan server.
- Verifikasi: `flutter test` → 53 passed saat slice ini ditutup (total 69 per 2026-11-21); `flutter analyze` → kini tersisa 1 warning lama; `flutter build apk --debug` → sukses.
- Belum: inferensi/liveness, enrollment dengan model nyata, dan uji offline penuh di perangkat fisik. Konektivitas perangkat sudah terverifikasi pada 2026-11-21. Retry backoff berbatas dan device id per-install sudah tersedia.

---

## 8. Phase 5 — M5: Manajemen Tugas

### Pekerjaan
- [x] HR membuat dan mengubah tugas. _(backend: `POST /hr/tasks`, `GET /hr/tasks`; edit belum)_
- [x] Menetapkan penerima, prioritas, dan tenggat waktu. _(`assignee_ids`, `priority`, `due_at`)_
- [x] Karyawan melihat tugas dan memperbarui progres. _(`GET /tasks`, `GET /tasks/{id}`, `POST /tasks/{id}/start`, `PATCH /tasks/{id}/progress`)_
- [x] Checklist dan lampiran/bukti kerja bila diperlukan. _(template checklist, `POST /tasks/{id}/attachments`, bukti wajib saat `requires_photo_proof`)_
- [x] Submission, review, approval, dan request revision. _(`POST /tasks/{id}/submissions`, `POST /hr/task-submissions/{id}/review`)_
- [ ] Notifikasi opsional untuk assignment dan perubahan status. _(slice berikutnya bersama integrasi Flutter)_

### Definition of Done
Transisi status tugas hanya dapat dilakukan oleh aktor yang berwenang; semua submission dan keputusan review tersimpan serta bisa ditelusuri.

### Status backend — 2026-11-03
Backend Phase 5 selesai sebagai modul `App\Modules\Tasks`:

- Tabel `tasks`, `task_assignments`, `task_submissions`, `task_attachments`, `task_comments`; status hidup per assignment.
- Endpoint HR: buat/daftar tugas, opsi prioritas, batalkan tugas, nilai submission.
- Endpoint karyawan: daftar/detail tugas, mulai, progres, submit, komentar, unggah lampiran privat.
- Lampiran privat di disk `local`; whitelist `jpg/jpeg/png/pdf/docx/xlsx`, maksimum 10 MB.
- Review hanya oleh pembuat tugas atau HR/admin; pembatalan hanya oleh pembuat atau admin.
- Verifikasi: `php artisan test` → 147 passed (618 assertions) pada SQLite dan PostgreSQL 17.
- **Status integrasi Flutter — 2026-11-21:** Integrasi M5 selesai penuh di sisi Flutter (`feature/m5-task-screens-wire`).
  - `lib/api.dart`: method karyawan (`taskList`, `taskDetail`, `taskStart`, `taskProgress`, `taskSubmit`, `taskComment`, `taskAttachment`) & method HR (`hrTaskList`, `hrTaskOptions`, `hrTaskCreate`, `hrTaskCancel`, `hrTaskReview`).
  - `lib/data.dart`: `TaskItem.fromApi` mendukung bentuk array ganda `{assignment, task}` (karyawan) maupun flat (detail & HR), parsing checklist template, komentar, lampiran, dan submission review.
  - `lib/screens/tasks.dart`: layar detail fetch data langsung via `Api.taskDetail()`, komentar via `Api.taskComment()`, checklist & slider progress sinkron via `Api.taskProgress()`, dan form pengajuan via `Api.taskSubmit()`.
  - `lib/screens/hr_work.dart`: list tugas HR fetch dari `Api.hrTaskList()`, pembuatan tugas via `Api.hrTaskCreate()`, serta persetujuan & revisi via `Api.hrTaskReview()`.
  - Verifikasi: Flutter test 72 passed, `flutter analyze` bersih (0 task issue).
- **Masih terbuka:** notifikasi in-app (Phase 8).

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
10. `AUTH-10` — Buat Flutter `ApiClient`, auth model, dan repository. ✅ `lib/api.dart`, `lib/state.dart`.
11. `AUTH-11` — Buat UI Login dan integrasi API. ✅ termasuk alur undangan.
12. `AUTH-12` — Simpan/pulihkan token dengan secure storage. ✅ HadirMetrik #15.
13. `AUTH-13` — Buat UI Register dan integrasi API. ✅ diganti alur undangan; tidak ada registrasi publik.
14. `AUTH-14` — Tambahkan logout, guard navigasi, dan session restoration. ✅ termasuk `401` → Login.
15. `AUTH-15` — Uji end-to-end register → login → `/me` → restart app → logout. ✅ diuji di perangkat fisik.

> Catatan: `AUTH-05`–`AUTH-08` pada draf awal menulis path `/auth/*`; kontrak
> final memakai prefix `/api/v1` sesuai `docs/openapi/auth.yaml`.

### Epic: `DEV — Konektivitas development dan tautan undangan`

1. `DEV-01` — Alamat API runtime + dialog **Server** di build debug. ✅ HadirMetrik #16.
2. `DEV-02` — `adb reverse` otomatis lewat `tool/dev_run.sh` dan dukungan multi-perangkat. ✅
3. `DEV-03` — Cleartext HTTP hanya di manifest debug; release tetap HTTPS. ✅
4. `DEV-04` — Tautan undangan mengikuti host klien dengan fallback IPv4 LAN. ✅ backend #32.
5. `DEV-05` — Daftarkan skema `hadirmetrik://invitations` di Android. ✅ HadirMetrik #17.
6. `DEV-06` — Dokumentasi jaringan lokal dan ADR. ✅ `docs/development-local-network.md`, ADR 0016–0017.

### Urutan branch yang disarankan

Buat branch pekerjaan per slice dan merge lewat PR; jangan memakai branch
permanen `fe`/`be` sebagai pengganti folder proyek.

```text
backend  main
├── feature/phase-0-foundation      # fondasi + kontrak API (merged)
├── feature/auth-modular-monolith   # Phase 1 backend (merged)
└── fix/dynamic-invitation-url      # tautan undangan (PR #32, merged)

mobile   main
├── feature/session-persistence     # M1 sesi 7 hari (HadirMetrik #15)
├── fix/dev-network-connectivity    # konektivitas lokal (HadirMetrik #16)
└── fix/invitation-deeplink         # skema undangan (HadirMetrik #17)
```

Slice berikutnya (integrasi Flutter M5) dimulai dari branch baru di repo mobile.

---

## 14. Acceptance Checklist untuk Demo Login/Register

MVP auth dianggap siap didemokan jika:

- [x] Akun uji tersedia melalui seeder yang aman.
- [x] Pengguna yang memenuhi syarat dapat mendaftar/aktivasi. _(terverifikasi di level API)_
- [x] Pengguna dapat login melalui Flutter yang benar-benar memanggil Laravel API. _(diuji di perangkat fisik)_
- [x] Login gagal menampilkan pesan yang wajar, tanpa crash. _(pesan error server ditampilkan di layar Login)_
- [x] Setelah login, aplikasi menampilkan nama/identitas dari endpoint `/me`.
- [x] Setelah aplikasi ditutup dan dibuka kembali, sesi dipulihkan dengan validasi ke server. _(cold start offline tetap masuk; `/me` divalidasi di latar belakang; `401` → Login)_
- [x] Logout membuat token lama tidak lagi bisa digunakan.
- [x] Endpoint protected menolak request tanpa autentikasi.
- [x] Role tidak bisa dimanipulasi dari payload klien.
- [x] Tes backend untuk happy path dan failure path lulus.

---

## 15. Referensi Implementasi

- Laravel Sanctum, autentikasi API token untuk klien mobile dan API: https://laravel.com/docs/sanctum
- `flutter_secure_storage`, penyimpanan key-value sensitif menggunakan mekanisme secure storage platform: https://pub.dev/packages/flutter_secure_storage
- Panduan jaringan lokal: [`docs/development-local-network.md`](development-local-network.md)
- ADR 0016 — Tautan undangan dan deep link: [`docs/adr/0016-invitation-link-host-and-deep-link.md`](adr/0016-invitation-link-host-and-deep-link.md)
- ADR 0017 — Konektivitas development: [`docs/adr/0017-development-network-connectivity.md`](adr/0017-development-network-connectivity.md)

Versi package dan konfigurasi aktual harus disesuaikan dengan versi Flutter, Dart, PHP, dan Laravel yang dipilih proyek.
