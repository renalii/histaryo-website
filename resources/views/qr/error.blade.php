<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR code — Histaryo</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #e8e0ef;
            color: #1a1a1a;
            min-height: 100vh;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 28px;
            border-bottom: 1px solid rgba(110, 75, 58, 0.12);
        }
        .logo {
            font-size: 20px;
            font-weight: 700;
            color: #6e4b3a;
        }
        nav a {
            text-decoration: none;
            color: #a8744f;
            margin-left: 20px;
            font-weight: 500;
        }
        main {
            max-width: 520px;
            margin: 0 auto;
            padding: 36px 22px;
        }
        .card {
            background: #fff;
            border-radius: 18px;
            padding: 28px 24px;
            box-shadow: 0 14px 36px rgba(40, 22, 53, 0.12);
        }
        h1 {
            margin: 0 0 12px;
            font-size: 22px;
            color: #2a1810;
        }
        p {
            margin: 0;
            line-height: 1.6;
            color: #444;
        }
        .code {
            margin-top: 14px;
            font-family: ui-monospace, monospace;
            font-size: 14px;
            color: #666;
            word-break: break-all;
        }
    </style>
</head>
<body>
<header>
    <div class="logo">Histaryo</div>
    <nav>
        <a href="{{ route('home') }}">Home</a>
    </nav>
</header>
<main>
    <div class="card">
        <h1>We couldn’t open this QR link</h1>
        <p>{{ $message }}</p>
        @if (!empty($code))
            <p class="code">Code: {{ $code }}</p>
        @endif
    </div>
</main>
</body>
</html>
