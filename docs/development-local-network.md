# Menghubungkan Flutter ke Backend di Jaringan Lokal

Dokumen ini menjelaskan cara menjalankan aplikasi Flutter terhadap backend
Laravel lokal ketika alamat komputer **berubah-ubah** (Wi-Fi kampus, hotspot,
kos teman, USB, atau *wireless debugging*).

---

## 1. Dua masalah yang diselesaikan

1. **Android 9+ memblokir HTTP cleartext.** Backend lokal berjalan di
   `http://`, sehingga request ditolak sebelum dikirim. Build **debug** sekarang
   mengizinkan cleartext lewat `android/app/src/debug/AndroidManifest.xml`;
   build **release** tetap memblokir HTTP biasa dan harus memakai HTTPS.
2. **Alamat API tidak lagi di-hardcode saat kompilasi.** Aplikasi memakai
   `ServerConfig` (`lib/server_config.dart`) yang menentukan alamat saat runtime.

Urutan penentuan alamat API:

| Prioritas | Sumber | Catatan |
|---|---|---|
| 1 | `--dart-define=API_URL=...` | Mengunci alamat untuk sesi/ build tersebut |
| 2 | Alamat yang disimpan dari dialog **Server** | Bertahan setelah aplikasi ditutup |
| 3 | Default platform | `http://127.0.0.1:8000/api/v1` (Android), `http://localhost:8000/api/v1` (web) |

---

## 2. Cara yang direkomendasikan: `adb reverse`

`adb reverse` meneruskan `127.0.0.1:8000` **di perangkat** ke port `8000`
**di komputer** melalui koneksi adb. Alamat tidak bergantung pada IP Wi-Fi,
jadi tetap sama di jaringan mana pun.

### Langkah

```bash
# Terminal 1 — backend harus mendengarkan semua interface
cd hadirmetrik-backend
php artisan serve --host=0.0.0.0 --port=8000
```

```bash
# Terminal 2 — Flutter
cd HadirMetrik

# Jika hanya satu perangkat terhubung:
tool/dev_run.sh

# Jika ada beberapa perangkat:
ANDROID_SERIAL=192.168.18.126:37215 \
  tool/dev_run.sh -d 192.168.18.126:37215
```

Script `tool/dev_run.sh` menjalankan:

```bash
adb reverse tcp:8000 tcp:8000
flutter run
```

### Manual

```bash
adb -s <serial> reverse tcp:8000 tcp:8000
flutter run -d <serial>
```

> **Penting:** `adb reverse` hilang setiap kali koneksi adb terputus
> (mis. *wireless debugging* reconnect). Jalankan ulang `adb reverse` atau
> gunakan `tool/dev_run.sh`.

### Verifikasi

```bash
adb -s <serial> reverse --list
# host-29 tcp:8000 tcp:8000

adb -s <serial> shell \
  '(echo "GET /api/v1/health HTTP/1.0"; echo "Host: 127.0.0.1"; echo; sleep 1) \
   | nc -q 2 127.0.0.1 8000'
# HTTP/1.0 200 OK ...
```

---

## 3. Alternatif: alamat LAN

Gunakan ini bila perangkat tidak terhubung lewat adb (mis. memasang APK lalu
membukanya langsung, atau menguji dari perangkat lain).

1. Cari IP komputer:
   ```bash
   ipconfig getifaddr en0      # macOS Wi-Fi
   hostname -I                 # Linux
   ipconfig                    # Windows
   ```
2. Jalankan backend di semua interface:
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```
3. Di aplikasi, buka layar **Login** → ketuk **“Server: …”** → isi
   `192.168.18.124:8000` → **Tes** → **Simpan**.

Alamat tersimpan dan dipakai lagi saat aplikasi dibuka. Tidak perlu rebuild.

Alternatif sekali jalan:

```bash
flutter run -d <serial> --dart-define=API_URL=http://192.168.18.124:8000/api/v1
```

> Jika alamat berubah, cukup ubah lewat dialog **Server**; tidak perlu
> membangun ulang APK.

---

## 4. Tautan undangan email

Link undangan dibangun oleh backend dan mengikuti alamat yang dipakai klien.
Urutan penentuannya:

| Prioritas | Sumber | Contoh |
|---|---|---|
| 1 | `INVITATION_URL` (eksplisit) | `https://app.hadirmetrik.site/invitations` |
| 2 | Host request yang dipakai klien | `http://192.168.18.124:8000/invitations` |
| 3 | IP LAN komputer (untuk request loopback/CLI) | `http://192.168.18.124:8000/invitations` |
| 4 | `APP_URL` + `/invitations` | `http://localhost:8000/invitations` |

- Di **development**, biarkan `INVITATION_URL=` kosong di `.env` agar link
  menyesuaikan jaringan. Nilai lama seperti `http://192.168.1.5:...` akan
  mengunci link ke alamat yang sudah tidak berlaku.
- Di **produksi**, isi `INVITATION_URL` dengan URL HTTPS publik.
- Untuk request loopback (`adb reverse` atau perintah artisan), backend memilih
  IPv4 LAN aktif dan mengabaikan adapter virtual (Docker, VPN, bridge emulator).
- Link hanya berlaku untuk undangan yang **dibuat setelah** perubahan; email
  lama tetap memakai alamat saat dibuat.

Setelah mengubah `.env`, bersihkan cache konfigurasi bila pernah di-cache:

```bash
php artisan config:clear
```

---

## 5. Troubleshooting

| Gejala | Penyebab umum | Solusi |
|---|---|---|
| “Tidak bisa terhubung ke server” | Cleartext diblokir / `adb reverse` belum aktif | Pastikan build **debug**; jalankan `adb reverse`; cek `reverse --list` |
| Berhasil di emulator, gagal di HP | Default `10.0.2.2` hanya untuk emulator | Gunakan `tool/dev_run.sh` atau isi alamat LAN lewat dialog **Server** |
| Link undangan membuka `127.0.0.1` di perangkat lain | Undangan dibuat lewat `adb reverse` di perangkat dev | Buat undangan dari klien yang memakai IP LAN, atau isi `INVITATION_URL` |
| `curl` di komputer berhasil, aplikasi gagal | Firewall memblokir port `8000` dari perangkat | Izinkan port `8000` di firewall; pastikan satu jaringan |
| Resend: “Recipient not found” | Alamat penerima tidak ada / salah ketik | Kirim ke alamat aktif; hapus dari suppression list Resend |
| Perubahan `.env` tidak terasa | Konfigurasi ter-cache | `php artisan config:clear` |

---

## 6. Checklist verifikasi

- [ ] `curl http://<host>:8000/api/v1/health` → `database: connected`
- [ ] `adb reverse --list` menampilkan `tcp:8000 tcp:8000`
- [ ] Aplikasi dapat login dan memuat profil
- [ ] Undangan baru berisi link dengan host yang dapat dibuka dari perangkat
- [ ] Logout lalu login ulang tetap bekerja
- [ ] `flutter test` dan `php artisan test` lulus

> Build release **tidak** mengizinkan HTTP biasa. Untuk produksi gunakan HTTPS
> dan set `INVITATION_URL` ke domain publik.
