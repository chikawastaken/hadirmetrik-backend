<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Verifikasi Akun HadirMetrik</title>
</head>
<body style="font-family: Arial, sans-serif; background-color:#f9f9f9; padding:20px;">
    <div style="max-width:600px; margin:auto; background:#ffffff; padding:20px; border:1px solid #e0e0e0;">
        <div style="text-align:center; margin-bottom:20px;">
            <img src="{{ $logoUrl }}" alt="{{ config('app.name') }}" style="height:80px;" />
        </div>
        <h2 style="color:#333;">Verifikasi Akun Anda</h2>
        <p>Hai {{ $name }},</p>
        <p>Terima kasih telah mendaftar di {{ config('app.name') }}. Silakan klik tombol di bawah untuk memverifikasi alamat email Anda:</p>
        <p style="text-align:center; margin:30px 0;">
            <a href="{{ $verificationUrl }}" style="background:#1d72b8; color:#fff; padding:12px 20px; text-decoration:none; border-radius:4px;">Verifikasi Email</a>
        </p>
        <p>Jika Anda tidak melakukan pendaftaran, abaikan email ini.</p>
        <hr />
        <p style="font-size:12px; color:#777;">© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>
