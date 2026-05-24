<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Histaryo – Admin</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <style>
        * { box-sizing: border-box; }
        :root {
            --bg: #1a1412;
            --card: #faf7f4;
            --text: #1f1a17;
            --muted: #5c524c;
            --brand: #8c5c3a;
            --brand-dark: #6e4b3a;
            --glow: rgba(140, 92, 58, 0.35);
        }
        html, body {
            margin: 0;
            min-height: 100%;
            font-family: 'Segoe UI', sans-serif;
            background: radial-gradient(1200px 600px at 80% 0%, rgba(140, 92, 58, 0.2), transparent),
                radial-gradient(800px 400px at 0% 100%, rgba(110, 75, 58, 0.18), transparent),
                var(--bg);
            color: var(--text);
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 22px clamp(18px, 4vw, 56px);
        }
        .logo {
            font-size: 1.45rem;
            font-weight: 700;
            color: #f5ede6;
            letter-spacing: 0.02em;
        }
        .logo span {
            color: #c9a688;
            font-weight: 600;
        }
        nav a {
            color: #d8cbc0;
            text-decoration: none;
            margin-left: 1.1rem;
            font-size: 0.92rem;
            font-weight: 500;
        }
        nav a:hover { color: #fff; }
        main {
            max-width: 640px;
            margin: 0 auto;
            padding: clamp(32px, 8vh, 72px) clamp(18px, 4vw, 40px) 48px;
        }
        .card {
            background: var(--card);
            border-radius: 20px;
            padding: clamp(1.5rem, 4vw, 2.25rem);
            box-shadow: 0 28px 60px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(255, 255, 255, 0.06);
        }
        .eyebrow {
            font-size: 0.72rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--brand);
            font-weight: 700;
            margin: 0 0 0.5rem;
        }
        h1 {
            margin: 0 0 0.65rem;
            font-size: clamp(1.55rem, 3.5vw, 2rem);
            color: var(--brand-dark);
            line-height: 1.2;
        }
        .lead {
            margin: 0 0 1.5rem;
            color: var(--muted);
            line-height: 1.55;
            font-size: 0.98rem;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.65rem 1.25rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            transition: background 0.2s ease, transform 0.08s ease;
        }
        .btn-primary {
            background: var(--brand);
            color: #fff;
            box-shadow: 0 8px 24px var(--glow);
        }
        .btn-primary:hover { background: var(--brand-dark); }
        .btn-primary:active { transform: translateY(1px); }
        .btn-ghost {
            background: transparent;
            color: var(--brand-dark);
            border: 1px solid rgba(110, 75, 58, 0.35);
        }
        .btn-ghost:hover { background: rgba(140, 92, 58, 0.08); }
        .note {
            margin-top: 1.5rem;
            padding-top: 1.25rem;
            border-top: 1px solid rgba(31, 26, 23, 0.08);
            font-size: 0.82rem;
            color: #7a6f68;
            line-height: 1.45;
        }
    </style>
</head>
<body>
<header>
    <div class="logo">Hista<span>ryo</span> Admin</div>
    <nav>
        <a href="{{ route('home') }}">Public site</a>
        <a href="{{ route('login') }}">User login</a>
    </nav>
</header>
<main>
    <div class="card">
        <p class="eyebrow">Internal tools</p>
        <h1>Administrator portal</h1>
        <p class="lead">
            Sign in to approve Site Manager registrations and review new landmark submissions (with supporting evidence).
            This area is restricted to authorized Histaryo Super Admins.
        </p>
        <div class="actions">
            <a class="btn btn-primary" href="{{ route('admin.login') }}">Administrator sign in</a>
            <a class="btn btn-ghost" href="{{ route('home') }}">Back to Histaryo</a>
        </div>
        <p class="note">
            Curators and site managers should use the standard login or their dedicated sign-in URLs, not this portal.
        </p>
    </div>
</main>
</body>
</html>
