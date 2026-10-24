# ADR 0002 — `employee_number` dibuat otomatis backend dan dipakai sebagai identifier login

- Status: Accepted
- Date: 2026-10-24
- Deciders: backend team
- Related: `docs/architecture.md` §4.6, §6.4, §7.5, §7.6; ADR 0003

## Context

Sebelum v1.1, nomor karyawan dibuat di luar aplikasi (manual oleh HR) dan dipakai
sebagai input untuk self-registration (`POST /register` memverifikasi
`employee_number`). Praktik ini menimbulkan masalah: nomor tidak konsisten antar-HR,
rentan salah ketik di alur self-registration, dan bukan kredensial yang diakui
oleh sistem login (login hanya menerima email). ERD v1.0.0 juga masih memakai UUID
sebagai primary key, sehingga business key dan PK ikut campur.

## Decision

1. Semua primary key memakai `bigint`; `employee_number` adalah **business key
   unik**, bukan PK.
2. `employee_number` dibuat **otomatis oleh backend** saat create employee,
   format `HM-` + nomor urut (contoh `HM-0251`). API `POST /api/v1/hr/employees`
   tidak menerima field ini; response mengembalikan nilai yang sudah dibuat.
   Kenaikan nomor memakai generator berbasis counter + unique index; race
   antar-request ditangani unique constraint + retry internal
   (`EMPLOYEE_NUMBER_TAKEN` hanya muncul bila generator gagal setelah retry).
3. Nilainya **immutable** setelah dibuat; perubahan hanya lewat jalur admin +
   audit log.
4. Login memakai `identifier`: bila berformat email → cocokkan `users.email`;
   selain itu → cocokkan `employees.employee_number`. Keduanya + password.
   Tidak ada field `username`.

## Alternatives

- UUID sebagai PK: ditolak — menumpangtindihkan business key dengan PK, lebih
  besar untuk index, dan tidak ada kebutuhan client meng-generate id.
- HR mengisi `employee_number` bebas: ditolak — pola penomoran tidak bisa
  dijamin konsisten tanpa validasi ketat di server.
- Username sebagai credentials: ditolak — menambah kredensial ketiga tanpa
  manfaat produk.

## Consequences

- Positive: nomor konsisten, tidak pernah diisi HR, dan bisa dipakai login
  di perangkat tanpa akses email.
- Negative: generator nomor serial menjadi objek yang harus dijaga (perlu
  penanganan saat restore database/kegagalan random).
- Follow-up: kontrak OpenAPI login berubah; bentuk response create employee
  didokumentasikan di `docs/architecture.md` §7.5.
