@once
    <style>
        .public-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 30px 60px;
        }

        .public-header__logo {
            font-size: 24px;
            font-weight: bold;
            color: #6e4b3a;
        }

        .public-header__nav a {
            text-decoration: none;
            color: #a8744f;
            margin-left: 25px;
            font-weight: 500;
            transition: color 0.25s ease;
        }

        .public-header__nav a:hover {
            color: #6e4b3a;
        }
    </style>
@endonce

<header class="public-header">
    <div class="public-header__logo">HistARyo</div>
    <nav class="public-header__nav">
        <a href="{{ route('home') }}">Home</a>
        <a href="{{ route('about') }}">About</a>
        <a href="{{ route('login') }}">Login</a>
        <a href="{{ route('register') }}">Register</a>
    </nav>
</header>
