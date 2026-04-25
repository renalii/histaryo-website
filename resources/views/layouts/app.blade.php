<!DOCTYPE html>
<html>
<head>
    <title>HistARyo - Explore Heritage with AR</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: #f8f5f0;
            color: #4b2e2e;
        }

        header {
            background-color: #6e4f3a;
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        nav a {
            color: white;
            margin-left: 20px;
            text-decoration: none;
            font-weight: bold;
        }

        nav a:hover {
            text-decoration: underline;
        }

        .hero {
            background: url('https://images.unsplash.com/photo-1533106418989-88406c7cc8ca?auto=format&fit=crop&w=1500&q=80') center center/cover no-repeat;
            color: white;
            padding: 100px 20px;
            text-align: center;
        }

        .hero h1 {
            font-size: 48px;
        }

        .section {
            padding: 60px 40px;
        }

        .about-images {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }

        .about-images img {
            width: 100%;
            max-width: 400px;
            border-radius: 10px;
        }

        .features ul {
            list-style: none;
            padding-left: 0;
        }

        .features li {
            margin-bottom: 10px;
            font-size: 18px;
        }

        .call-to-action {
            text-align: center;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 10px;
            background-color: #8b5e3c;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
        }

        .btn:hover {
            background-color: #a56f45;
        }

        footer {
            background-color: #6e4f3a;
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <header>
        <div><strong>HistARyo</strong></div>
        <nav>
            <a href="{{ route('home') }}">Home</a>
            <a href="{{ route('about') }}">About</a>
            <a href="{{ route('login') }}">Login</a>
            <a href="{{ route('register') }}">Register</a>
        </nav>
    </header>

    @yield('content')

    <footer>
        &copy; {{ date('Y') }} HistARyo | Powered by Laravel + Ionic + MySQL
    </footer>
</body>
</html>
