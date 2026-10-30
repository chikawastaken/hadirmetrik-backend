<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>Registrasi Wajah — HadirMetrik</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: #4f6648;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1f2a1c;
        }
        main {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 28px;
            padding: 32px 24px;
            text-align: center;
            box-shadow: 0 24px 60px rgba(15, 39, 2, .24);
        }
        .mark {
            width: 64px;
            height: 64px;
            margin: 0 auto 20px;
            border-radius: 18px;
            background: #eef3e8;
            display: grid;
            place-items: center;
            font-weight: 800;
            font-size: 22px;
            color: #4f6648;
        }
        h1 { margin: 0 0 8px; font-size: 26px; letter-spacing: -.02em; }
        p { margin: 0 0 24px; line-height: 1.55; color: #5b6657; }
        .button {
            display: block;
            padding: 16px 20px;
            border-radius: 999px;
            background: #4f6648;
            color: #fff;
            text-decoration: none;
            font-weight: 700;
            font-size: 16px;
        }
        .fallback {
            margin-top: 16px;
            font-size: 13px;
            color: #7a8576;
            word-break: break-all;
        }
        .fallback a { color: #4f6648; }
    </style>
</head>
<body>
<main>
    <div class="mark">HM</div>
    <h1>Registrasi Wajah</h1>
    <p>Buka aplikasi HadirMetrik untuk melanjutkan. Pastikan kamu login dengan akun karyawan yang menerima undangan ini.</p>

    <a class="button" href="intent://invitations/{{ $token }}#Intent;scheme=hadirmetrik;package=com.hadirmetrik.hadirmetrik;end">
        Buka Aplikasi
    </a>

    <p class="fallback">
        iOS atau browser lain:<br>
        <a href="hadirmetrik://invitations/{{ $token }}">hadirmetrik://invitations/{{ $token }}</a>
    </p>
</main>
</body>
</html>
