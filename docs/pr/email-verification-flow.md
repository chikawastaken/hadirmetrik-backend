# Tambahkan verifikasi email registrasi

## Ringkasan

Menambahkan alur verifikasi email untuk akun yang didaftarkan:

- Email registrasi berisi tautan bertanda tangan yang berlaku selama 60 menit.
- Endpoint `GET /email/verify/{id}/{hash}` memvalidasi tautan dan menandai email sebagai terverifikasi.
- Menambahkan perintah lokal untuk membuat akun uji persisten dan mengirim email verifikasi. Password dimasukkan secara tersembunyi lewat terminal.
- Test otomatis tetap memakai SQLite in-memory, sedangkan mailer mengikuti konfigurasi environment.

## Perubahan

| File / Direktori | Perubahan |
|---|---|
| `app/Modules/Identity/Application/RegisterEmployee/RegisterEmployeeUseCase.php` | Membuat URL verifikasi bertanda tangan dan mengirimkannya melalui `RegistrationMail`. |
| `app/Modules/Identity/Http/Controllers/VerifyEmailController.php` | Memvalidasi email dan menandai akun sebagai terverifikasi. |
| `routes/web.php` | Menambahkan route verifikasi dengan middleware `signed`. |
| `app/Mail/RegistrationMail.php` | Mengirim URL verifikasi ke template email. |
| `resources/views/emails/registration.blade.php` | Menggunakan tautan verifikasi baru. |
| `routes/console.php` | Menambahkan `email:verification-test {email}` untuk membuat akun uji di database lokal dan mengirim email. |
| `tests/Feature/Identity/VerifyEmailTest.php` | Menguji verifikasi berhasil dan signature tidak valid. |
| `phpunit.xml` | Menghapus override mailer `array`, sehingga test mengikuti konfigurasi `MAIL_MAILER`. |
| `README.md` | Mendokumentasikan test, perintah akun uji, dan masa berlaku tautan. |

## Verifikasi

- `MAIL_MAILER=array php artisan test tests/Feature/Identity/VerifyEmailTest.php tests/Feature/Api/V1/Auth/RegisterTest.php` — **8 test lulus, 34 assertion**.
- Pint — **lulus**.

## Catatan

Perintah akun uji hanya berjalan dengan `APP_ENV=local`, meminta konfirmasi sebelum menyimpan data, dan menulis secara permanen ke database aplikasi lokal. Tautan verifikasi yang dikirim sebelum perubahan ini perlu diganti dengan tautan baru.
