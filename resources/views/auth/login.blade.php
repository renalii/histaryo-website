<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HistARyo – Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: #f9f1e8;
            color: #1a1a1a;
            height: 100%;
            overflow: hidden;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 30px 60px;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #6e4b3a;
        }

        nav a {
            text-decoration: none;
            color: #a8744f;
            margin-left: 25px;
            font-weight: 500;
        }

        nav a:hover {
            color: #6e4b3a;
        }

        .container {
            height: calc(100vh - 80px);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-box {
            display: flex;
            width: 750px;
            height: 420px;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .form-side {
            flex: 1;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-side h2 {
            font-size: 28px;
            margin-bottom: 1rem;
            color: #6e4b3a;
        }

        form input {
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 1rem;
        }

        form button {
            background: #8c5c3a;
            border: none;
            padding: 0.75rem;
            width: 100%;
            color: white;
            font-size: 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        form button:hover {
            background-color: #6e4b3a;
        }

        .error-message {
            color: #991b1b;
            background-color: #fee2e2;
            padding: 10px;
            border-radius: 6px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .success-message {
            color: #065f46;
            background-color: #d1fae5;
            padding: 10px;
            border-radius: 6px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .register-link {
            margin-top: 1rem;
            text-align: center;
            font-size: 0.9rem;
        }

        .register-link a {
            color: #8c5c3a;
            font-weight: bold;
            text-decoration: none;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .login-image {
            flex: 1;
            background-image: url('{{ asset('images/magellancross.jpg') }}');
            background-size: cover;
            background-position: center;
        }

        @media (max-width: 850px) {
            .login-box {
                flex-direction: column;
                width: 95%;
                height: auto;
            }

            .login-image {
                height: 180px;
            }

            html, body {
                overflow-y: auto;
            }

            .container {
                padding: 20px 0;
                height: auto;
                align-items: flex-start;
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
            <h2>Login</h2>

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
                <input type="email" name="email" placeholder="Email" value="{{ old('email') }}" required>
                <input type="password" name="password" placeholder="Password" required>

                <button type="submit">Login</button>
            </form>

            <div class="register-link">
                Don't have an account? <a href="{{ route('register') }}">Register here</a>
            </div>
        </div>

        <div class="login-image"></div>
    </div>
</div>

</body>
</html>
