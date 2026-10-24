# ADR 0003 — Password sementara dibuat sistem, ditampilkan sekali, wajib diganti

- Status: Accepted
- Date: 2026-10-24
- Deciders: backend team
- Related: `docs/architecture.md` §4.6, §5.9, §7.6, §12.6; ADR 0002

## Context

Sebelum v1.1, karyawan mengatur password sendiri lewat `POST /register`
(self-registration). Setelah flow berubah menjadi HR-only
(`POST /hr/employees`), HR tidak lagi menerima password dari karyawan.
HR juga tidak boleh menetapkan password sendiri: HR yang mengetik password yang
sama untuk semua karyawan adalah anti-pattern keamanan. Ada juga hambatan kanal:
email/WhatsApp tidak selalu aman, dan password tidak boleh berada di pesan yang
sama dengan link verifikasi wajah.

## Decision

1. Password awal **dibuat sistem** pada saat create employee (dan saat
   reset-password HR): string acak 16 karakter dari CSPRNG.
2. Password ditampilkan **satu kali** ke HR di response create
   (`initial_password`) untuk disampaikan ke karyawan melalui kanal terpisah
   (telepon/pertemuan langsung) — bukan via email/WhatsApp undangan.
3. Nilai plaintext **tidak pernah disimpan, di-log, atau dicantumkan di
   database**; yang disimpan hanya hash (Argon2id/bcrypt via port
   `PasswordHasher`, ADR 0001).
4. Akun dibuat dengan `must_change_password = true`; login pertama wajib
   mengganti password lewat `POST /api/v1/auth/change-password` sebelum fitur
   lain dapat diakses (guard `PASSWORD_CHANGE_REQUIRED`).

## Alternatives

- HR mengetik password sementara: ditolak — invita peluang password lemah dan
  berulang.
- Email OTP aktivasi: ditolak untuk MVP — tidak menyelesaikan masalah payload
  password dan menambah ketergantungan kanal email.
- Link "set password pertama kali" via email: ditimbang, tapi tidak menggantikan
  requirement karena HR perlu bisa memberikan password secara langsung
  (karyawan bisa membutuhkan akses sebelum membaca email).

## Consequences

- Positive: kualitas password dijamin sistem; sekali-tampil menghilangkan
  kebutuhan menyimpan plaintext; alur reset HR mengikuti pola yang sama.
- Negative: HR harus menyampaikannya lewat kanal luar sistem; bila HR tidak
  menyampaikan, karyawan tidak bisa masuk sampai reset dijalankan.
- Follow-up: `GET` tidak menampilkan `initial_password` lagi; audit log
  `password.reset` mencatat aksi tanpa nilai password.
