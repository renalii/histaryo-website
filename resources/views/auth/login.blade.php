<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HistARyo – Login</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        :root {
            --bg: #e8e0ef;
            --card: #ffffff;
            --text: #1f1a17;
            --muted: #6b5f58;
            --brand: #8c5c3a;
            --brand-dark: #6e4b3a;
            --input-border: #d9cec5;
            --focus-ring: rgba(140, 92, 58, 0.2);
            --shadow: 0 18px 40px rgba(53, 33, 21, 0.16);
        }

        html, body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            height: 100%;
            overflow: hidden;
        }

        body {
            display: flex;
            flex-direction: column;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 30px 60px;
        }

        .logo {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--brand-dark);
            letter-spacing: 0.3px;
        }

        nav a {
            text-decoration: none;
            color: #9a6f50;
            margin-left: 25px;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        nav a:hover {
            color: var(--brand-dark);
        }

        .container {
            flex: 1;
            min-height: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 0 1.2rem 0.8rem;
        }

        .login-box {
            display: flex;
            width: min(840px, 100%);
            height: min(440px, calc(100vh - 118px));
            background: var(--card);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid rgba(140, 92, 58, 0.15);
        }

        .form-side {
            flex: 0.95;
            padding: 1.3rem 1.45rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: linear-gradient(180deg, #fff 0%, #fef9f4 100%);
        }

        .eyebrow {
            margin: 0 0 0.3rem;
            font-size: 0.72rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #9a7a63;
            font-weight: 600;
        }

        .form-side h2 {
            font-size: 1.1rem;
            margin: 0 0 0.35rem;
            color: var(--brand-dark);
            line-height: 1.15;
        }

        .subtitle {
            margin: 0 0 0.6rem;
            color: var(--muted);
            line-height: 1.35;
            font-size: 0.7rem;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
        }

        .field label {
            font-size: 0.72rem;
            font-weight: 600;
            color: #654a39;
        }

        form input {
            width: 100%;
            padding: 0.56rem 0.68rem;
            border-radius: 8px;
            border: 1px solid var(--input-border);
            font-size: 0.88rem;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            background-color: #fff;
        }

        form input::placeholder {
            color: #9f938a;
        }

        form input:focus {
            border-color: #b17853;
            box-shadow: 0 0 0 4px var(--focus-ring);
        }

        form button {
            background: var(--brand);
            border: none;
            margin-top: 0.15rem;
            padding: 0.56rem;
            width: 100%;
            color: white;
            font-size: 0.9rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            letter-spacing: 0.01em;
            transition: background-color 0.2s ease, transform 0.08s ease;
        }

        form button:hover {
            background-color: var(--brand-dark);
        }

        form button:active {
            transform: translateY(1px);
        }

        .error-message {
            color: #991b1b;
            background-color: #fee2e2;
            border: 1px solid #f3b0b0;
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 0.9rem;
        }

        .success-message {
            color: #065f46;
            background-color: #d1fae5;
            border: 1px solid #99e6c6;
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 0.9rem;
        }

        .register-link {
            margin-top: 0.5rem;
            text-align: center;
            font-size: 0.78rem;
            color: var(--muted);
        }

        .register-link a {
            color: var(--brand);
            font-weight: bold;
            text-decoration: none;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .quick-note {
            margin-top: 0.55rem;
            text-align: center;
            font-size: 0.78rem;
            color: #847972;
        }

        .login-image {
            flex: 1.05;
            background-image: url('{{ asset('images/magellancross.jpg') }}');
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .image-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: end;
            padding: 1rem;
            background: linear-gradient(to top, rgba(12, 9, 8, 0.42), rgba(12, 9, 8, 0.08) 45%, transparent 75%);
        }

        .overlay-card {
            max-width: 220px;
            background: rgba(255, 255, 255, 0.92);
            border-radius: 10px;
            padding: 0.5rem 0.58rem;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .overlay-card::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: url('{{ asset('images/magellancross.jpg') }}');
            background-size: cover;
            background-position: center;
            opacity: 0.2;
            z-index: 0;
        }

        .overlay-card > * {
            position: relative;
            z-index: 1;
        }

        .overlay-card h3 {
            margin: 0 0 0.2rem;
            font-size: 0.78rem;
            color: #2b221d;
        }

        .overlay-card p {
            margin: 0;
            font-size: 0.68rem;
            color: #4e433c;
            line-height: 1.35;
        }

        @media (max-width: 850px) {
            header {
                padding: 20px 18px;
            }

            nav a {
                margin-left: 12px;
                font-size: 0.92rem;
            }

            .login-box {
                flex-direction: column;
                width: 95%;
                height: auto;
                border-radius: 16px;
            }

            .login-image {
                height: 220px;
                order: -1;
            }

            .form-side {
                padding: 1.5rem 1.1rem 1.4rem;
            }

            .form-side h2 {
                font-size: 1.55rem;
            }

            .overlay-card {
                max-width: 100%;
                padding: 0.58rem 0.68rem;
            }

            .container {
                padding: 20px 0;
                min-height: auto;
                align-items: flex-start;
            }

            html, body {
                height: auto;
                overflow-y: auto;
            }
        }

        @media (max-height: 760px) and (min-width: 851px) {
            header {
                padding: 18px 40px;
            }

            .login-box {
                height: min(420px, calc(100vh - 100px));
            }

            .form-side {
                padding: 0.95rem 1.05rem;
            }

            .subtitle {
                margin-bottom: 0.45rem;
                font-size: 0.64rem;
            }

            form {
                gap: 0.42rem;
            }

            .overlay-card {
                display: none;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="logo">Histaryo</div>
    <nav>
        <a href="{{ route('home') }}">Home</a>
        <a href="{{ route('about') }}">About</a>
        <a href="{{ route('login') }}">Login</a>
        <a href="{{ route('register') }}">Register</a>
    </nav>
</header>

<div class="container">
    <div class="login-box">
        <div class="form-side">
            <p class="eyebrow">Welcome back</p>
            <h2>Login</h2>
            <p class="subtitle">Sign in to continue exploring Cebu's landmarks, stories, and AR experiences.</p>

            <!-- {{-- ✅ Greeting if logged in --}}
            @if(session('name'))
                <div class="success-message">
                    Welcome back, {{ session('name') }}! 🎉
                </div>
            @endif -->

            {{-- ✅ Success message after registration --}}
            @if (session('success'))
                <div class="success-message">
                    {{ session('success') }}
                </div>
            @endif

            {{-- ❌ Error message --}}
            @if ($errors->any())
                <div class="error-message">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" placeholder="Enter your email address" value="{{ old('email') }}" autocomplete="email" required autofocus>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
                </div>

                <button type="submit">Login</button>
            </form>

            <div class="register-link">
                Don't have an account? <a href="{{ route('register') }}">Register here</a>
            </div>
            
        </div>

        <div class="login-image" aria-hidden="true">
            
        </div>
    </div>
</div>

</body>
</html>
