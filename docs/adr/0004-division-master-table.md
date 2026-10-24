# Prompt: Perbarui ERD & rancangan database domain Presensi, Tugas, Pengajuan, dan Gaji

Peran kamu: senior backend & database architect HadirMetrik (absensi karyawan berbasis
face recognition). Tugas: memperdalam ERD dan rancangan database di @docs/architecture.md
untuk domain yang **belum** dibahas detail pada v1.1 — presensi (online/offline/koreksi),
tugas, pengajuan cuti/izin/lembur, transparansi gaji, sync, perangkat, profil, notifikasi,
dan lampiran. Dokumentasikan spesifikasi tabel secara melembua, bukti ringkasnya.

Kamu sudah memahami konteks aplikasi, halaman, dan alur penggunanya. Jangan buat dokumen
terpisah selain ADR. Desain murni — bukan implementasi kode.

Dokumen berada di **versi 1.1.0**. Jangan mengganti versinya; naikkan setelah
selesai ke 1.2.0 dan tuliskan **Revisi 1.2.0** sambil mempertahankan catatan revisi
1.1.0 yang ada. Jangan mengubah isi subtopic `divisions`, `shifts`,
`work_locations`, `employee_shift_assignments`, `employee_work_location_assignments`,
`employee_compensations`, `employee_invitations`, `invitation_deliveries`,
`face_templates`, `audit_logs`, `users`, `employees` — spesifikasinya sudah
final di §6.4 dan hanya boleh dirujuk, bukan diini-ubahkan re-desain.

## 1. Konteks wajib dibaca

`docs/architecture.md` — khusus dan urutan pentingnya:
- §6.1.1 & §6.1.2 (ERD, termasuk keputusan PK `bigint`)
- §6.3 & §6.4 (aturan integritas + **format spesifikasi tabel** yang movies harus ditiru)
- §5.1–§5.5 (state machine presensi, tugas, pengajuan, gaji yang sudah lazim)
- §6.2 & §6.2.1 (penemptu tabel kandidat/tersangkut)
- §4.1–§4.5 & §4.6–§4.11 (flow yang sudah digambarkan termasuk onboarding)
- §7.1–§7.8 (endpoint, error code, kontrak envelope)
- §8.1–§8.4 (offline outbox, idempotency, policy offline)
- §9.1–§9.5 & §10 (face verification; role & otorisasi)
- §12 (basis keamanan & privasi), §13 (kerangka evaluasi), §15 (decisions + open questions)
- §17 (ringkasan perubahan v1.1 & acceptance checklist)

- `docs/roadmap.md` (Phase 3–8 dan kaitannya)
- `docs/adr/0001…0008` (gaya ADR + keputusan yang harus diteruskan, bukan dilawan)
- `docs/openapi/auth.yaml` + catatan §7.8 (perubahan kontrak harus dinyatakan, kontrak
  belum diadopsi)
- `database/migrations/`, modul pada `app/Modules/` (Attendance, Tasks,
  AdministrativeRequests, Payroll, Sync, Reports — bila sudah berdirektori; bila belum,
  hukumi saja nama domain dari §3.3)
- prompt sebelumnya (onboarding) bila tersedia sebagai konteks — sama untuk pola
  spesifikasi/keputusan
- Kondisi data: pre-production; migration aktif memakai SQLite in-memory untuk test.

## 2. Kondisi saat ini yang harus digantikan

- ERD §6.1.2 (presensi/tugas/gaji) masih **kasar**: hanya mencantumkan atribut terbatas
  dan belum mengikat nilai `check`, index, unique penu, android speifikasi karakter
  seperti tipe `varchar(255)`.
- §6.4 hanya mencantumkan kolom domain identitas — tabel domain presensi, tugas,
  pengajuan, gaji **belum punya spesifikasi lengkap** dalam format yang sama.
- Preferensi offline (`PENDING_SYNC`, `SYNC_FAILED`, dst.) ada di §5.1/§8.1 tetapi
  **belum terkait** hasil fisik tabel server apa harus dibuat untuk menerima batch
  (`sync_batches`, `attendance_records.status`, dsb.).
- §8.4 (policy offline) masih terbuka tanti — desain harus **aman untuk dua-duanya**:
  bisa menerima provisional, bisa menjalankan policy "offline wajib review".
- `SALARY_STATEMENT` masih menyimpan agregat mencantum dan belum mencantumkan penjelasan
  tembusan kesalahan audit publikasi; hubungannya ke `employee_compensations`
  (paket aktif) belum dirisikan.
- Lampiran/perangkat/sync batch kandidat di §6.2 kendati di bari; `devices`,
  `sync_batches`, `request_attachments`, `task_attachments`, `notifications`,
  `attendance_correction_requests`, `salary_publication_events`.
- Status `attendance_records.status` diduga kandungan; belum mengikuti state machine
  §5.1 secara literal bahwa rekaman langsung dinilai `pending_review`/`rejected`
  outweigh `source`.
- Kardinalitas mereka antara `attendance_records` vs `task`/`request` belum
  mengizin-gambarkan
  perhitungan sisi serverimenti.Noempat: `attendance_verification` per record satu baris,
  `task` judul & daftar assignment, dsb.

## 3. Keputusan final (adopsi terangkan mutlak — jangan dirubah)

Terkunci dari prompt onboarding (yang membangun §1–§17 rev 1.1.0):

- Semua PK `bigint`; UUID/ULID hanya untuk external id/idempotency key.
- Semua timestamp `timestamptz` UTC; `occurred_at` (klien) dan `received_at` (server)
  keduanya tersimpan; jam perangkat **bukan** authoritative.
- Status memakai string + check constraint, bukan `ENUM` PostgreSQL.
- Email lowercase + unique; nomor WhatsApp/E.164 di tulisan terkait.
- `employee_number` = business key `HM-####`, immutable, identifier login keduanya menang.
- Kompensasi aktifWorks dari `employee_compensations`; total dihitung server.
- Secara memnu: `security_events` = telemetri auth; `audit_logs` = aksi
  consequential (append-only, snapshoot tanpa password/token/biometrik).
- Tanpa raw biometrik di DB; template wajah melalui fee service privat.
- Tanpa soft delete untuk employee; kalau menggelarkan banyak baris pakai status.
- Partial unique index didukung SQLite di migration lanan raw SQL bila perlu; `jsonb`
  → text/JSON di lingkungan uji.
- Role efektif dari backend; `login.succeeded` memodifikasi `last_login_at`.

Keputusan domain barusi nyata supaya tidak tertunda lagi di desain ini:

- `attendance_records` menyimpan `employee_id`, `work_location_id`, `event_type`,
  `status`, `source`, `occurred_at`, `received_at`, `event_id` UUID unik, `device_reference`.
  **`event_id` tetap idempotency key, unchecked `status` langsung menjadi result**
  (unique `event_id` + parameter payload hingga penerimaan untuk idempotent batch).
- `attendance_verifications` paling banyak **satu per record**: `attendance_record_id`,
  `method`, `model_version`, `match_score`, `liveness_status`, `decision`,
  `metadata jsonb`, `verified_at`; partial unique `(attendance_record_id)` atau
  kolom `attendance_record_id unique` — pilih salah satu dan jelaskan kenapa.
- `sync_batches` mencatat batch oleh employee: `employee_id`, `event_count`,
  `accepted_count`, `rejected_count`, `pending_review_count`, `received_at`,
  idempotency key unik bila diandalkan.
- `attendance_correction_requests` = tabel baru membuat koreksi presensi
  men-disable langkah langsung: menantang halaman/Desain server; status
  mengikuti §5.3 (draft/submitted/under_review/approved/rejected/needs_revision),
  field koreksi-upaya (event/ocne merahka), reason, reviewer/no approval historis.
- Task: `task`, `task_assignments`, `task_submissions`, `task_attachments`;
  assignment idempotent; progress kecepatan baik — `progress_percent` `0..100`,
  baris unik `(task_id, employee_id)`.
- Pengajuan: `administrative_requests`, `request_attachments`; type bazel
  cuti/izin/lembur validasi berbeda → poin di ke dinilai diaplikasi bila KOscending
  colereere rewel tinggi; durasi lembur numeric; lampiran privat.
- Gaji: `salary_statements` + `salary_components`; nilai `numeric`; `net_salary`
  **dihitung server** dari komponen; glemapuan `published_at` + audit; request
  publication log bisa dijadikan ke `salary_publication_events` kalau diadopsi.
- `employee_compensations` **bukan** slip gaji; slip merujuk periode terpublikasi.
- `devices` (jika diadopsi saat desain): `user_id`, `device_reference`, `platform`,
  `os_version`, `app_version`, `trusted_at`, `revoked_at`, `last_seen_at`; Hanya
  registrasi terketahui bisa dijadikan `_design instructor` — istilah `device_reference`
  invita mereka device_id beli daftar pegas.
- `notifications` (jika diadopsi): in-app + delivery; isi tidak menyimpan
  password/token/pii berlebih; encr-at-rest memakai model ** lokal umum pilih.
- Face attendance utama muncul langsung untuk check-in/out; enrollment yang berahi
  tetap **harus via undangan** (§4.8); jangan memasukkan `presence` link undangan.

## 4. Model data target & format spesifikasi tabel

Untuk setiap tabel: **tujuan, kolom (tipe, null, default, catatan), unique, FK dengan
`ON DELETE`, index (termasuk partial unique bila relevan), check constraint, dan alasan
desain** — tepat dalam format §6.4 v1.1. Nilai yang harus memutuhkan:

- `attendance_records`, `attendance_verifications`
- `attendance_correction_requests`
- `sync_batches`
- `task`, `task_assignments`, `task_submissions`, `task_attachments`
- `administrative_requests`, `request_attachments`
- `salary_statements`, `salary_components` (dan `salary_publication_events` bila diadopsi)
- `devices`, `notifications` — beri keputusan yang lembut/adoptasi
- tercepat penu: data `event_id`, status juga dari `checkAscii` ala list §7.7 …

Relasi yang harus terlihat kardinalitasnya (perbarui ERD §6.1.2, tetap merujukkan tarik
ulang agar kolom penuh tombol detail dikendalikan §6.4 malinstale):

- `USER 1..0..* EMPLOYEE` (sudah ada), `EMPLOYEE 2..N ATTENDANCE_RECORD` (empat
  repeated data), dsb. menyerap namun sepanjang dika sudah ditandainya:
  - `EMPLOYEE 1..N SYNC_BATCH`
  - `SYNC_BATCH 1..N ATTENDANCE_RECORD` (jika diadopsi)
  - `ATTENDANCE_RECORD 1..0..1 ATTENDANCE_VERIFICATION`
  - `EMPLOYEE 1..N ATTENDANCE_CORRECTION_REQUEST`
  - `TASK 1..N TASK_ASSIGNMENT`, `TASK 1..N TASK_SUBMISSION`,
    `EMPLOYEE 1..N TASK_ASSIGNMENT` dll.
  - `ADMIN_REQUEST 1..N REQUEST_ATTACHMENT`, `TASK 1..N TASK_ATTACHMENT`
  - `EMPLOYEE 1..N SALARY_STATEMENT` → `SALARY_STATEMENT 1..N SALARY_COMPONENT`
- Geofence mencegil pakai `work_locations.geofence_radius_m` (ADR), **bukan**
  disimpan ulang di attendance; attendance hanya menyimpan koordinat + accuracy + status
  hasil validasi (bisa adapter untuk addistiameb).

## 5. State machine & kontrasti yang perlu diteguakkan

Formulisasi (konsisten §5.1–§5.4, perkuat dengan guard table:

- Attendance server-side: `ACCEPTED | PENDING_REVIEW | REJECTED` —
  saat server menerima batch, status mengikuti policy offline (§8.4); guard: event
  duplicate `event_id` → return hasil pertama; mantap invalidasi `occurred_at`
  (di masa depan / terLau terlalu saat tak lisp) → pending_review/rejected +
  reason code; Face check (template aktif) memakai `FACE_ENROLLMENT_REQUIRED`
  jika policy aktif.
- Correction request pakai §5.3; guard duplicate decision; hasil koreksi
  menanzulmark attendance correction (tabel/medrum: `corrections` bila diadopsi
  atau `attendance_revision` — pilih dan jelaskan).
- Task (§5.2) & request (§5.3): tetap; idempotent decision.
- Salary statement (§5.4): clamp `net_salary` server-side; publikasi menulis audit
  (`salary.published`) + optionally `salary_publication_events`.
- Sync batch: `receiving` -> `processed` podlík accepted/rejected counts;
  retry idempotent.

## 6. Integrasi yang harus dirapihkan di dokumen (bukan hanya ERD)

- **§6.2** — pindahkan item yang diadopsi dari "kandidat" ke "tabel inti"; hapus dari
  kandidat yang ditolak; beri alasan tolak singkat.
- **§6.4** — tambah spesifikasi di bawah §6.4 sebagai sub-sub-section (`####`) sesuai
  abjad domain, tetap memakai tabel markdown + SQL ringkas untuk constraint kunci.
- **§6.1.2** — perbarui ERD sehingga entitas baru muncul; jangan menyentuk §6.1.1.
- **§8.1–§8.4** — perbarui state lokal outbox ke kolom `attendance_records.status` /
  `sync_batches` yang diberikan; tunjukkan tautan tabel server vs state lokal (pagi).

## 7. API (nyata hanya yang di menuma ter-intip URL, karena pahami konteks)

- `POST /api/v1/attendance/check-in|check-out`, `POST /api/v1/sync/attendance`,
  `GET /api/v1/attendance/history` masing-masing memangkas perubahan di §7.1.
- Correction: `POST /api/v1/attendance-correction-requests`,
  `POST /api/v1/attendance-correction-requests/{id}/decision`.
- Task endpoint yang sudah dicaan v1.0 (list/create/start/submissions/review).
- Request endpoint (list/submit/decision).
- Salary: `GET /api/v1/salary-statements`, `GET /{id}`,
  `POST /hr/salary-statements/{id}/publish`.
- `GET /api/v1/me` dan `GET /api/v1/bootstrap` dikatakan refrensi:
  `must_change_password`, status enrollment, `policy_versions`.

Error code menu v1.1 harus ditarik: `DUPLICATE_EVENT`, `EVENT_OUT_OF_RANGE`,
`FACE_ENROLLMENT_REQUIRED`, `INVALID_PERIOD`, `STATE_NOT_TRANSITIONABLE`, dsb.

## 8. Rencana migrasi (untuk domain ini)

- Pastikan migrasi kompatibel SQLite in-memory: partial unique index, jsonb→text.
- Urut: `attendance_records` (+ `attendance_verifications`),
  `sync_batches`, `attendance_correction_requests`, `task*`,
  `administrative_requests*`, `salary_statements*`, kemudian `devices`,
  `notifications`.
- Backfill: data pre-production — penu-standratis; kalau data init sudah ada dari daftar
  auth sekarang, putuskan: drop tetap / cerite map; jelaskan.
- Setiap migration punya `down()`.

## 9. Kerah yang perlu desain diperhatikan

- Akses **lintas-domain tidak mengembang**: mekanisme object-level authorization
  tetap mengendalikan `employee_id`, siapkan test yang menolak akses pembaca
  dari employee lain (attendance/ task/ salary).
- `audit_logs` tidak menyertakan snapshot koordinat? — jalan kotak: karena data lokasi
  adalah terkota PII, pastikan kebijakan menang )Pendat kebutulan role).
- Idempotency receipt server mechanism d engày-move: `event_id` unik bila
  kandidat kolom; write conflict kuat, kepala.
- Logging: jangan log gambar wajah, token, password, koordinat presisi bilar.

## 10. Output format

- Bahasa Indonesia; perbarui `docs/architecture.md` langsung di-section yang
  relevan, bukan menulis summary terpisah.
- ERD Mermaid harus valid rrender di GitHub; detail kolom cukup di §6.4, tidak perlu
  tiap kolom di diagram.
- Format tabel menjerman persis §6.4; SQL ringkas boleh untuk constraint kunci.
- Insert ADR baru nomor lanjut (0009 dst.) untuk keputusan yang kontroversial dan
  siapkan section untuk Open Questions baru; perbarui rangkuman changelog versi 1.2.0.
- Akhiri dengan ringkasan perubahan + acceptance checklist — gunakan pola pola §17.

## 11. Acceptance checklist

- Semua tabel di-bagian ini punya spesifikasi penuh (kolom/tipe/unique/FK/ index/check/#).
- `event_id` tetap idempotency unik; retry tidak menciptakan duplicate record.
- State machine server presensi/tugas/request/gaji sesuai §5 + guard table eksplisit.
- Schema offline sync memutus policy §8.4 baik provisional maupun wajib-review tanpa
  re-desain makro.
- `attendance_correction_requests` menggantikan edit-cont tempat, ikat audit.
- Lampiran privat ( gaze/speech) mereferensi object storage, tidak public URL.
- Salary: net dihitung server; akses object-level; publikasi beraudit.
- Tidak menyimpan raw biometrik/koordinat-akurat yang tidak dibobot direncanakan.
- Tidak ada password/token/plain email di log/snapshot.
- ERD valid mermaid; section v1.1 tidak ter-rebesar; ADR + open questions + changelog
  forked terselesaikan.
