# ADR 0006 — Paket kompensasi tersimpan terpisah dari salary statement bulanan

- Status: Accepted
- Date: 2026-10-24
- Deciders: backend team
- Related: `docs/architecture.md` §4.6, §6.4, §7.5; `docs/roadmap.md` Phase 7

## Context

HR menginput kompensasi (gaji pokok, tunjangan transport, tunjangan makan) di
form "Tambah Karyawan", sementara HadirMetrik juga punya konsep slip gaji
bulanan (`salary_statements`) yang dipublikasikan payroll (roadmap Phase 7).
Menyatukan keduanya menimbulkan konflik: slip bulanan harus tetap sebagai
dokumen historis yang terpublikasi, sedangkan kompensasi kontrak berubah lewat
penyesuaian. Ada juga risiko nilai total yang dikirim client dipakai
sebagai sumber kebenaran.

## Decision

1. Buat tabel `employee_compensations` berisi komponen (base, transport,
   meal) dengan periode `effective_from`/`effective_to` — satu paket aktif
   per employee (partial unique index).
2. **Total bulanan tidak pernah disimpan**; dihitung server:
   `total_monthly = base_salary + transport_allowance + meal_allowance`
   dan hanya muncul di response (tipe `numeric`, bukan float).
3. API tidak menerima `total_monthly` atau field gabungan lain dari client.
4. `salary_statements` tetap domain terpisah (tabel v1.0.0) untuk slip
   bulanan hasil payroll; kompensasi kontrak bukan slip.
5. Akses: aksi create/update dibatasi HR/payroll policy; employee melihat
   data kompensasi sendiri bila policy mengizinkan.

## Alternatives

- Menaruh gaji langsung di `employees`: ditolak — leak field payroll ke
  query yang umum dibaca (list employee) dan tidak ada sparasi historis.
- Menyimpan `total_monthly` untuk kecepatan: ditolak — redundan, rawan
  inkonsisten dengan komponen.
- Satu tabel untuk kontrak + slip: ditolak — kode lifecycle rekor berbeda
  (paket berlaku vs dokumen terpublikasi).

## Consequences

- Positive: kenaikan/penyesuaian kompensasi punya riwayat; slip
  bulanan tetap murni dokumen payroll; total selalu konsisten.
- Negative: query total butuh perhitungan server; jika kelak payroll
  memakai komponen dinamis, tabel ini perlu dinormalisasi ke
  `compensation_components` (open question §15.2 architecture).
