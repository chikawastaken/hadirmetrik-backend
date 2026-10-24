# ADR 0005 — Assignment shift dan lokasi kerja dengan periode efektif

- Status: Accepted
- Date: 2026-10-24
- Deciders: backend team
- Related: `docs/architecture.md` §4.6, §5.10, §6.4; ADR 0004

## Context

Setiap employee ditempatkan pada satu shift dan satu lokasi kerja, tapi
kombinasi ini pasti berubah sepanjang masa kerja (mutasi, rotasi shift malam,
relokasi gudang). Model kolom tunggal (`employees.shift_id`, dll.) menimbulkan
pertanyaan yang tak terjawab: bagaimana histori perubahan dijaga, siapa yang
pernah mencatat mutasi, dan bagaimana presensi pada tanggal tertentu dinilai
dengan shift/lokasi yang berlaku saat itu — bukan nilai hari ini.

## Decision

1. Tabel `employee_shift_assignments` dan `employee_work_location_assignments`
   dengan `effective_from` (date, `NOT NULL`) dan `effective_to` (date,
   nullable).
2. Tepat satu assignment aktif per employee per jenis: partial unique index
   `(employee_id) WHERE effective_to IS NULL`.
3. Perubahan shift/lokasi = **tutup** record lama (isi `effective_to`) lalu
   **buat** record baru; tidak ada update nilai di record lama.
4. Saat create employee, assignment awal dibuat dengan
   `effective_from = employment_start`.
5. `created_by` FK `users.id` untuk menelusuri HR yang menjalankan perubahan
   (didukung audit log `assignment.created`).

## Alternatives

- Kolom `shift_id`/`work_location_id` di `employees` + tabel log perubahan:
  ditolak — ada dua sumber untuk nilai "aktif", mudah inkonsisten.
- Assignment per tanggal penuh (row per hari): ditolak — membengkak tanpa
  kebutuhan query yang mewajibkan.

## Consequences

- Positive: kedua pertanyaan historis ("apa yang berlaku di tanggal X",
  "siapa mengubah") terjawab dari data; geofence/aturan presensi diambil dari
  lokasi assignment aktif.
- Negative: setiap pembacaan butuh filter `effective_to IS NULL` (atau query
  `effective_from <= today < effective_to`); setiap geser assignment adalah
  dua operasi yang harus berada di dalam satu transaksi.
