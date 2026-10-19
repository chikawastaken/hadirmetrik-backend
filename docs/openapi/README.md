# HadirMetrik API — OpenAPI

Folder ini memuat kontrak API yang disepakati bersama oleh developer backend
(Laravel) dan mobile (Flutter).

| File | Isi |
|------|-----|
| `auth.yaml` | Kontrak minimum untuk health check + autentikasi (register, login, `/me`, logout). |

## Cara membuka

- **VS Code / editor:** pasang ekstensi OpenAPI/Swagger untuk preview `auth.yaml`.
- **Swagger Editor:** buka <https://editor.swagger.io> lalu import `auth.yaml`.
- **CLI (opsional):** `npx @redocly/cli lint docs/openapi/auth.yaml`.

## Ringkasan kontrak

Base path: `/api/v1`. Semua request/response JSON.

| Method | Path | Auth | Fungsi |
|--------|------|------|--------|
| GET | `/api/v1/health` | — | Cek service + koneksi database (`200` sehat, `503` degraded) |
| POST | `/api/v1/register` | — | Registrasi/aktivasi karyawan dengan kode undangan |
| POST | `/api/v1/login` | — | Login, mengembalikan Sanctum token |
| GET | `/api/v1/me` | Bearer | Profil pengguna terautentikasi |
| POST | `/api/v1/logout` | Bearer | Cabut token yang sedang dipakai |

### Format response

Sukses:

```json
{ "success": true, "data": { }, "message": "opsional" }
```

Error:

```json
{ "success": false, "message": "Kredensial tidak valid.", "errors": { "email": ["..."] } }
```

`errors` hanya ada saat ada detail validasi/kegagalan. Error tingkat framework
(401, 403, 404, 405, 422, 429) memakai envelope error yang sama, sehingga Flutter
cukup menangani satu bentuk error.

### Alur token

1. Flutter memanggil `/register` atau `/login`.
2. Backend mengembalikan `data.token` (Sanctum personal access token).
3. Flutter menyimpan token di secure storage (**bukan** SharedPreferences).
4. Setiap request terproteksi mengirim `Authorization: Bearer <token>` dan `Accept: application/json`.
5. `/me` dipakai untuk memulihkan sesi saat aplikasi dibuka kembali.
6. `/logout` mencabut token; Flutter menghapus token lokal.

## Smoke test lokal

Jalankan backend (`php artisan serve`), lalu:

```bash
curl -s http://localhost:8000/api/v1/health | jq

curl -s -X POST http://localhost:8000/api/v1/login \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"email":"hr@example.com","password":"password"}' | jq

curl -s http://localhost:8000/api/v1/me \
  -H 'Accept: application/json' -H "Authorization: Bearer <TOKEN>" | jq
```

## Aturan pemeliharaan

- `auth.yaml` adalah sumber kebenaran kontrak auth. Setiap perubahan pada
  `routes/api/v1/auth.php` wajib disertai pembaruan file ini (dan sebaliknya).
- Contoh nilai di `auth.yaml` bersifat fiktif. Jangan pernah menaruh token,
  password, atau credential asli di dokumentasi.
- Perubahan kontrak yang memutus kompatibilitas harus dibahas bersama tim
  mobile sebelum di-merge.
