<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Undangan Registrasi Wajah HadirMetrik</title>
</head>
<body style="font-family: Arial, sans-serif; background-color:#f9f9f9; padding:20px;">
    <div style="max-width:600px; margin:auto; background:#ffffff; padding:20px; border:1px solid #e0e0e0;">
        <div style="text-align:center; margin-bottom:20px;">
            <img src="{{ $logoUrl }}" alt="{{ config('app.name') }}" style="height:80px;" />
        </div>
        <h2 style="color:#333;">Undangan Registrasi Wajah</h2>
        <p>Hai {{ $name }},</p>
        <p>
            Anda diundang untuk menyelesaikan registrasi wajah di {{ config('app.name') }}.
            Silakan login menggunakan email atau nomor karyawan <strong>{{ $employeeNumber }}</strong>,
            lalu buka tautan berikut dari aplikasi:
        </p>
        <p style="text-align:center; margin:30px 0;">
            <a href="{{ $invitationUrl }}" style="background:#1d72b8; color:#fff; padding:12px 20px; text-decoration:none; border-radius:4px;">Mulai Registrasi Wajah</a>
        </p>
        <p>
            Tautan berlaku sampai <strong>{{ $expiresAt->translatedFormat('d F Y H:i') }}</strong>.
            Jangan bagikan tautan ini kepada orang lain.
        </p>
        <p>Jika Anda tidak merasa diundang, abaikan email ini atau hubungi HR.</p>
        <hr />
        <p style="font-size:12px; color:#777;">© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>
