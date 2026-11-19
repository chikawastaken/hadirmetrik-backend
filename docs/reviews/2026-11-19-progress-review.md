# Review Progress HadirMetrik — 2026-11-19

**Repo:** `hadirmetrik-backend`
**Cakupan:** `docs/roadmap.md`, `docs/architecture.md`, `docs/adr/`, `docs/openapi/`, `docs/pr/`, `docs/superpowers/plans/`
**Metode:** review berbasis dokumen, dengan spot-check ke kode (routes, modul, test, repo Flutter `HadirMetrik`). Bukan audit ulang seluruh kode.
**HEAD saat review:** `2a155dd` (merge PR #29)

---

## 1. Ringkasan status per fase

| Phase | Status dokumen | Kenyataan di repo | Catatan |
|---|---|---|---|
| 0 — Fondasi | Hampir selesai | Selesai | Satu checkbox Flutter masih terbuka (`roadmap.md:71`) padahal Flutter sudah memanggil API sejak Phase 3/4 |
| 1 — M1 Auth | Backend selesai, Flutter menyusul | Backend selesai; Flutter login/change-password/invitation ada, **session restore & secure storage belum** | DoD M1 belum tercapai |
| 2 — M2 Identitas | Selesai (2026-11-03) | Backend + Flutter profil nyata (`/me/profile`) | Konsisten |
| 3 — M3 Presensi | MVP vertical slice | Backend + Flutter (PR #21, #9, #11) | 4 item ditunda: geofence server, aturan terlambat, batas jadwal/timezone, audit koreksi |
| 4 — M4 Wajah & Offline | Backend + Flutter sync selesai | Outbox, backoff, device id ada | **Belum produksi**: model wajah/liveness belum ada, enrollment tetap `pending`, uji perangkat fisik belum |
| 5 — M5 Tugas | Backend selesai | PR #29 merged (`2a155dd`) | Flutter masih data mock; edit tugas & notifikasi belum |
| 6–9 | Belum mulai | Belum ada modul | Cuti, gaji, dashboard HR, production readiness |

**Angka repo saat ini:** 5 modul (`Attendance`, `Audit`, `Employees`, `Identity`, `Tasks`), 42 route `/api/v1`, 22 migrasi, 34 file test, 147 test / 618 assertion lulus di SQLite + PostgreSQL 17.

---

## 2. Yang sudah kuat

- **Phase 5 backend** adalah slice paling matang: workflow per-assignment, locking transaksi, kepemilikan bukti antar co-assignee, audit log create/cancel/review, lampiran privat 10 MB, dan pagination HR.
- **Batas modul** dijaga `ModuleBoundaryTest`; lintas modul lewat `EmployeeDirectory`/`UserDirectory`/`AuditLogger`.
- **Phase 4 sync** sudah tahan restart, idempotent per event, dan tidak mengklaim wajah terverifikasi — statusnya jujur.
- **Phase 2** sudah end-to-end (bukan cuma backend), jadi klaim "selesai" wajar.

---

## 3. Gap yang paling berdampak

1. **Token Flutter hanya di memori.** `Api.token` (`lib/api.dart:32`) tidak dipersistensi; `flutter_secure_storage` tidak ada di `pubspec.yaml`; `shared_preferences` hanya dipakai outbox/device. Artinya tutup aplikasi → login ulang. Ini bertentangan dengan DoD Phase 1 dan melemahkan klaim "MVP vertical slice" untuk Phase 3/4.
2. **Flutter M5 belum tersambung.** `lib/screens/tasks.dart` masih seed data, tidak ada method task di `lib/api.dart`. Ini prioritas yang sudah ditetapkan roadmap.
3. **Kontrak OpenAPI tertinggal.** `docs/openapi/` hanya punya `auth.yaml` (health, login, invitation password, change-password, me, me/profile, logout). Tidak ada kontrak untuk employees HR, clock, schedule, history, correction, face, sync, tasks. Prinsip roadmap #3 ("kunci kontrak sebelum Flutter") belum dipenuhi untuk Phase 3–5.
4. **Phase 4 belum layak produksi.** Roadmap sudah jujur soal ini, tetapi tabel ringkasan bisa dibaca seolah Phase 4 selesai. Perlu label eksplisit "backend + sync selesai; verifikasi wajah belum".

---

## 4. Inkonsistensi dokumen yang perlu dibereskan

| Lokasi | Masalah |
|---|---|
| `roadmap.md:102, 443` | Mengklaim `POST /api/v1/register` selesai, padahal route itu **tidak ada**; registrasi sekarang lewat undangan (`/invitations/{token}/accept`, `/password`) |
| `roadmap.md:166–186` | Blok kontrak register masih menjelaskan alur lama (`employee_number` + `invitation_code`) |
| `roadmap.md:151–159` | Checklist Flutter Phase 1 semua terbuka, padahal layar login/change-password/invitation sudah ada; yang benar-benar kurang hanya secure storage + session restore |
| `roadmap.md:71` | "Flutter dapat mengirim HTTP request" belum dicentang padahal sudah terjadi di Phase 3/4 |
| `roadmap.md:346` | "HR membuat dan mengubah tugas" dicentang, padahal catatannya sendiri bilang "edit belum" |
| `roadmap.md:287–292` | Item Phase 3 dicentang meski ada caveat "belum" — kotak centang menyesatkan; lebih baik pisahkan "MVP selesai" vs "ditunda" |
| `docs/superpowers/plans/*` | Kedua plan 0 kotak tercentang (Phase 5: 43 terbuka; Phase 2: 32 terbuka) padahal sudah dieksekusi dan di-merge |
| `docs/adr/0004-division-master-table.md` | Isinya **prompt tugas**, bukan ADR; judul tidak sesuai nama file |
| `docs/pr/email-verification-flow.md` | Mendokumentasikan alur verifikasi email registrasi yang sudah tidak punya endpoint API (`VerifyEmailController` hanya di `routes/web.php`) — perlu dipastikan masih dipakai atau diarsipkan |

---

## 5. Rekomendasi urutan berikutnya

1. **PR kecil: rekonsiliasi dokumen** — perbaiki register vs invitation, checkbox yang basi, ADR 0004, tandai plan selesai, dan tambah catatan "Phase 4 = belum produksi".
2. **Flutter Phase 5** — method API tugas, repository/state, ganti seed `tasks.dart`, test widget/state; sekalian pertimbangkan filter status (deferred minor backend).
3. **Secure storage + session restore** — lintas fase, murah, dan menutup DoD M1 yang masih menggantung.
4. **Notifikasi in-app** (backend + Flutter) sesuai ADR 0014.
5. **Phase 6 cuti/izin/lembur** sebagai modul produk berikutnya.
6. **Paralel:** keputusan provider wajah/liveness + uji perangkat fisik, karena ini satu-satunya blocker yang membuat klaim produksi Phase 4 belum sah.
