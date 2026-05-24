<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Histaryo – Set your password</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <style>
        * { box-sizing: border-box; }
        :root {
            --bg: #e8e0ef;
            --card: #ffffff;
            --text: #1f1a17;
            --muted: #6b5f58;
            --brand: #7A2E1F;
            --brand-dark: #5c2318;
            --accent: #E8B34B;
            --input-border: #d9cec5;
            --shadow: 0 18px 40px rgba(53, 33, 21, 0.16);
        }
        html, body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100%;
        }
        body {
            display: flex;
            flex-direction: column;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px 48px;
        }
        .logo {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--brand-dark);
        }
        .container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1rem 1.25rem 2rem;
        }
        .card {
            width: min(480px, 100%);
            background: var(--card);
            border-radius: 18px;
            padding: 2rem 2.1rem;
            box-shadow: var(--shadow);
            border: 1px solid rgba(122, 46, 31, 0.12);
        }
        .eyebrow {
            margin: 0 0 0.35rem;
            font-size: 0.72rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #9a7a63;
            font-weight: 600;
        }
        h1 {
            margin: 0 0 0.5rem;
            font-size: 1.45rem;
            color: var(--brand-dark);
        }
        .lead {
            margin: 0 0 1.25rem;
            color: var(--muted);
            font-size: 0.92rem;
            line-height: 1.5;
        }
        .account {
            margin: 0 0 1.1rem;
            padding: 0.65rem 0.8rem;
            border-radius: 10px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            font-size: 0.88rem;
            color: #374151;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
        }
        label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            margin-bottom: 0.3rem;
            color: var(--brand-dark);
        }
        input {
            width: 100%;
            padding: 0.65rem 0.75rem;
            border: 1px solid var(--input-border);
            border-radius: 10px;
            font-size: 0.95rem;
        }
        input:focus {
            outline: none;
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(122, 46, 31, 0.12);
        }
        .password-wrap {
            position: relative;
        }
        .password-wrap > input {
            padding-right: 2.5rem;
        }
        .password-toggle {
            position: absolute;
            right: 0.35rem;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            cursor: pointer;
            color: #9a7a63;
            padding: 0.25rem;
        }
        button[type="submit"] {
            margin-top: 0.35rem;
            background: var(--accent);
            color: var(--brand);
            border: 1px solid #F3C96A;
            font-weight: 700;
            font-size: 0.95rem;
            padding: 0.7rem 1rem;
            border-radius: 10px;
            cursor: pointer;
        }
        button[type="submit"]:hover {
            filter: brightness(1.03);
        }
        .message {
            padding: 0.65rem 0.85rem;
            border-radius: 10px;
            font-size: 0.88rem;
            margin-bottom: 1rem;
        }
        .message.error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .alt {
            margin-top: 1.25rem;
            text-align: center;
            font-size: 0.88rem;
            color: var(--muted);
        }
        .alt a {
            color: var(--brand);
            font-weight: 600;
        }
    </style>
</head>
<body>
<header>
    <div class="logo">Histaryo Curator</div>
</header>

<div class="container">
    <div class="card">
        <p class="eyebrow">First-time setup</p>
        <h1>Welcome, {{ $curatorName }}</h1>
        <p class="lead">
            Choose a secure password for your curator account. You will use this password to sign in from now on.
        </p>

        <p class="account">
            <strong>Account email</strong><br>{{ $email }}
        </p>

        @if ($errors->any())
            <div class="message error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ $signedAction }}">
            @csrf
            <div>
                <label for="password">New password</label>
                <div class="password-wrap">
                    <input id="password" type="password" name="password"
                           autocomplete="new-password" minlength="8" required autofocus>
                    <button type="button" class="password-toggle" aria-label="Show password" data-target="password">Show</button>
                </div>
            </div>
            <div>
                <label for="password_confirmation">Confirm new password</label>
                <div class="password-wrap">
                    <input id="password_confirmation" type="password" name="password_confirmation"
                           autocomplete="new-password" minlength="8" required>
                    <button type="button" class="password-toggle" aria-label="Show password" data-target="password_confirmation">Show</button>
                </div>
            </div>
            <button type="submit">Save password and continue</button>
        </form>

        <p class="alt">
            Already set your password?
            <a href="{{ route('curators.login') }}">Sign in</a>
        </p>
    </div>
</div>

<script>
    document.querySelectorAll('.password-toggle').forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.target);
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.textContent = show ? 'Hide' : 'Show';
        });
    });
</script>
</body>
</html>
