# HadirMetrik Backend API

Backend RESTful API untuk HadirMetrik — platform absensi karyawan berbasis pengenalan wajah (*face recognition*), manajemen penugasan, dan transparansi kompensasi. Dibangun menggunakan Laravel 13 dengan pendekatan **Modular Monolith** dan kontrak domain terstandarisasi.

Panduan ini ditujukan bagi developer (khususnya tim **Frontend Flutter** atau anggota tim baru) untuk menjalankan server lokal dan berkolaborasi dengan lancar.

---

## Daftar Isi
- [Prasyarat Sistem](#prasyarat-sistem)
- [Langkah Setup Cepat](#langkah-setup-cepat)
- [Menjalankan Database via Docker](#menjalankan-database-via-docker)
- [Konfigurasi Environment (.env)](#konfigurasi-environment-env)
- [Migrasi & Seeding Data](#migrasi--seeding-data)
- [Menjalankan Server Backend](#menjalankan-server-backend)
- [Menjalankan Automated Tests](#menjalankan-automated-tests)
- [Makefile — Shortcut Perintah](#makefile--shortcut-perintah)
- [Koneksi dari Aplikasi Flutter / Emulator](#koneksi-dari-aplikasi-flutter--emulator)
- [Ringkasan Endpoint Autentikasi & Registrasi](#ringkasan-endpoint-autentikasi--registrasi)
- [Dokumentasi & Arsitektur](#dokumentasi--arsitektur)

---

## Prasyarat Sistem

Sebelum memulai, pastikan perangkat lokal Anda telah terinstal:
- **PHP 8.2+** (disarankan PHP 8.3 / 8.4) beserta ekstensi `pdo_pgsql`, `mbstring`, `openssl`, `curl`.
- **Composer 2.x**
- **Docker & Docker Compose** (untuk database PostgreSQL lokal).

---

## Langkah Setup Cepat

```bash
# 1. Clone repository
git clone <repository-url>
cd hadirmetrik-backend

# 2. Install dependensi PHP
composer install

# 3. Salin konfigurasi environment
cp .env.example .env

# 4. Generate App Key
php artisan key:generate
```

> **💡 Shortcut:** Atau cukup jalankan `make setup` — semua langkah di atas (termasuk Docker DB, migrasi, dan seeding) dijalankan secara otomatis.

---

## Menjalankan Database via Docker

Proyek ini sudah dilengkapi file `compose.yaml` yang berisi layanan **PostgreSQL 17** dan Web GUI **Adminer**.

### 1. Jalankan Container Database
Jalankan perintah berikut di root project:

```bash
docker compose up -d db
```
> **Catatan:** Port default PostgreSQL di-expose ke host pada port **`5433`** (menghindari bentrok dengan PostgreSQL lokal port `5432`). Container mapping: `5433:5432`.

### 2. (Opsional) Menjalankan Adminer (Web Database GUI)
Bila ingin melihat isi tabel database lewat browser:

```bash
docker compose --profile tools up -d
```
Akses Adminer di: [http://localhost:8080](http://localhost:8080)
- **System**: PostgreSQL
- **Server**: `db` (jika login dari dalam container network) atau `127.0.0.1:5433`
- **Username**: `hadirmetrik`
- **Password**: Nilai `POSTGRES_PASSWORD` pada file `.env` Anda
- **Database**: `hadirmetrik`

### 3. Menghentikan Docker
```bash
docker compose down
```

---

## Konfigurasi Environment (`.env`)

Sesuaikan variabel database di file `.env` agar sesuai dengan container Docker:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5433
DB_DATABASE=hadirmetrik
DB_USERNAME=hadirmetrik
DB_PASSWORD=secret-password-anda

POSTGRES_DB=hadirmetrik
POSTGRES_USER=hadirmetrik
POSTGRES_PASSWORD=secret-password-anda
POSTGRES_PORT=5433
ADMINER_PORT=8080

# Kredensial akun awal untuk seeding (wajib diisi agar seeder bisa berjalan)
SEED_ADMIN_PASSWORD=adminPassword123!
SEED_EMPLOYEE_PASSWORD=employeePassword123!
```

---

## Migrasi & Seeding Data

Setelah database Docker menyala dan `.env` terkonfigurasi:

```bash
# Jalankan migrasi database
php artisan migrate

# Jalankan seeder akun awal
php artisan db:seed
```

Setelah seeder berjalan, Anda memiliki akun awal:
1. **HR / Admin**:
   - Email: `hr@hadirmetrik.local`
   - Password: Nilai dari `SEED_ADMIN_PASSWORD`
   - Role: `hr`
2. **Employee**:
   - Email: `employee@hadirmetrik.local`
   - Nomor Karyawan: `EMP-0001`
   - Password: Nilai dari `SEED_EMPLOYEE_PASSWORD`
   - Role: `employee`

---

## Menjalankan Server Backend

Jalankan server development:

```bash
php artisan serve
```
Server akan berjalan di: **`http://127.0.0.1:8000`**

### Cek Health Endpoint
Uji apakah server dan database telah terkoneksi dengan benar:
```bash
curl http://127.0.0.1:8000/api/v1/health
```
Response sukses:
```json
{
  "success": true,
  "data": {
    "status": "healthy",
    "database": "connected"
  }
}
```

---

## Menjalankan Automated Tests

Test otomatis menggunakan SQLite in-memory, sehingga Anda bisa menjalankannya kapan saja tanpa mempengaruhi database PostgreSQL:

```bash
php artisan test
```

---

## Makefile — Shortcut Perintah

Proyek ini menyediakan `Makefile` agar perintah-perintah umum cukup diketik singkat. Jalankan `make help` untuk melihat daftar lengkap.

| Perintah | Fungsi |
| :--- | :--- |
| `make setup` | 🚀 Setup lengkap pertama kali (env → install → key → db → migrate → seed) |
| `make db` | Jalankan container PostgreSQL |
| `make db-stop` | Hentikan container database |
| `make db-reset` | ⚠️ Hapus volume DB & buat ulang (data hilang) |
| `make adminer` | Jalankan Adminer (Web GUI) |
| `make migrate` | Jalankan migrasi database |
| `make fresh` | ⚠️ migrate:fresh + db:seed |
| `make serve` | Jalankan server development (`http://127.0.0.1:8000`) |
| `make serve-network` | Jalankan server yang bisa diakses dari HP/emulator |
| `make test` | Jalankan automated test suite |
| `make clean` | Bersihkan cache Laravel |
| `make routes` | Tampilkan daftar route API |

---

## Koneksi dari Aplikasi Flutter / Emulator

Untuk menghubungkan aplikasi **Flutter** ke backend lokal:

| Platform Klien | Base URL API |
| :--- | :--- |
| **Android Emulator** | `http://10.0.2.2:8000/api/v1` |
| **iOS Simulator** | `http://127.0.0.1:8000/api/v1` |
| **Real Device (WiFi Sama)** | `http://<IP-Lokal-Komputer>:8000/api/v1` (jalankan `php artisan serve --host 0.0.0.0`) |

---

## Ringkasan Endpoint Autentikasi & Registrasi

Format envelope response selalu konsisten:
```json
{
  "success": true,
  "data": { ... },
  "message": "Opsional"
}
```

### 1. Login (`POST /api/v1/auth/login` atau `POST /api/v1/login`)
Mendukung login menggunakan **email** atau **nomor karyawan** (`employee_number` seperti `HM-0001`):

**Request Payload:**
```json
{
  "identifier": "employee@hadirmetrik.local",
  "password": "employeePassword123!",
  "device_name": "Flutter Android Pixel 7"
}
```
**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "token": "1|sanctum_token_string...",
    "token_type": "Bearer",
    "must_change_password": false,
    "user": {
      "id": 2,
      "name": "Karyawan Contoh",
      "email": "employee@hadirmetrik.local"
    }
  },
  "message": "Login berhasil."
}
```
> **Catatan untuk Flutter:** Jika `must_change_password: true`, arahkan user langsung ke layar ganti password (`/change-password`).

### 2. Informasi Akun Aktif (`GET /api/v1/auth/me` atau `GET /api/v1/me`)
Headers: `Authorization: Bearer <token>`

### 3. Logout (`POST /api/v1/auth/logout` atau `POST /api/v1/logout`)
Headers: `Authorization: Bearer <token>`

### 4. Tambah Karyawan oleh HR (`POST /api/v1/hr/employees`)
Endpoint ini khusus dipanggil oleh user dengan role `hr` atau `admin`. Backend secara otomatis mengenerate `employee_number` (`HM-####`), password sementara 16 karakter CSPRNG (`initial_password`), assignment shift/lokasi, dan token undangan verifikasi wajah.

---

## Dokumentasi & Arsitektur

Untuk pemahaman lebih mendalam mengenai sistem:
- **Arsitektur Sistem & Spesifikasi Tabel**: [`docs/architecture.md`](docs/architecture.md)
- **Keputusan Arsitektur (ADR)**:
  - [ADR 0001: Modular Monolith Boundaries](docs/adr/0001-modular-monolith-module-contracts.md)
  - [ADR 0002: Auto-generated Employee Number](docs/adr/0002-autogenerated-employee-number.md)
  - [ADR 0003: System Temporary Password](docs/adr/0003-system-generated-temporary-password.md)
  - [ADR 0004: Division Master Table](docs/adr/0004-division-master-table.md)
  - [ADR 0005: Effective-Dated Assignments](docs/adr/0005-effective-dated-assignments.md)
  - [ADR 0006: Compensation Packages vs Salary](docs/adr/0006-compensation-packages-vs-salary-statements.md)
  - [ADR 0007: Invitation Token Hash & Rotation](docs/adr/0007-invitation-token-hash-and-rotation.md)
  - [ADR 0008: Security Events vs Audit Logs](docs/adr/0008-security-events-vs-audit-logs.md)
- **OpenAPI Specification**: [`docs/openapi/auth.yaml`](docs/openapi/auth.yaml)
- **Roadmap Proyek**: [`docs/roadmap.md`](docs/roadmap.md)
