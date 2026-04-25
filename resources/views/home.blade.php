<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Histaryo - Home</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: #e8e0ef;
            color: #1a1a1a;
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
            transition: color 0.25s ease;
        }

        nav a:hover {
            color: #6e4b3a;
        }

        .hero {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 200px;
            max-width: 2250px;
            margin: 0 auto;
            padding: 70px 60px 42px;
            /* flex-wrap: wrap; */
        }

        .hero-text {
            flex: 2 1 520px;
            max-width: 800px;
        }

        .tag {
            background-color: #6e4b3a;
            color: white;
            padding: 6px 16px;
            border-radius: 15px;
            font-size: 12px;
            display: inline-block;
            margin-bottom: 15px;
        }

        .hero-text h1 {
            font-size: clamp(40px, 5vw, 56px);
            line-height: 1.1;
            margin: 0 0 18px;
        }

        .hero-text p {
            font-size: 16px;
            line-height: 1.72;
            color: #2f2f2f;
            margin: 0 0 22px;
        }

        .btn {
            background-color: #6e4b3a;
            color: white;
            padding: 11px 22px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        .btn:hover {
            background-color: #a8744f;
            transform: translateY(-1px);
        }

        .hero-image {
            width: min(100%, 560px);
            margin-top: 0;
        }

        .hero-cta {
            margin-top: 4px;
            border-radius: 16px;
            background: linear-gradient(130deg, #6e4b3a 0%, #8d779b 100%);
            padding: 30px 20px;
            color: #ffffff;
            box-shadow: 0 12px 22px rgba(40, 22, 53, 0.2);
        }

        .hero-cta h3 {
            margin: 0 0 8px;
            font-size: 25px;
            line-height: 1.2;
        }

        .hero-cta p {
            margin: 0 0 14px;
            color: rgba(255, 255, 255, 0.92);
            font-size: 14px;
            line-height: 1.55;
        }

        .hero-cta-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .hero-cta .btn {
            background-color: #ffffff;
            color: #6e4b3a;
            font-weight: 600;
        }

        .hero-cta .btn:hover {
            background-color: #f4eaf9;
        }

        .hero-cta .btn-secondary {
            border-color: #ffffff;
            color: #ffffff;
            background: transparent;
        }

        .hero-cta .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.13);
        }



        .image-card {
            position: relative;
            width: 100%;
            padding: 9px;
            border-radius: 17px;
            background: #8d779b;
            box-shadow: 0 12px 24px rgba(34, 18, 47, 0.22);
            overflow: visible;
        }

        .image-card::before {
            content: "";
            position: absolute;
            top: 12px;
            right: -13px;
            width: 16px;
            height: calc(100% - 24px);
            border-radius: 0 12px 12px 0;
            /* background: linear-gradient(180deg, #b18d55 0%, #9f7f4c 100%); */
            z-index: 1;
        }

        .image-card::after {
            content: "";
            position: absolute;
            left: 10px;
            right: -13px;
            bottom: -9px;
            height: 14px;
            border-radius: 0 0 12px 12px;
            /* background: linear-gradient(90deg, #4a20c7 0%, #54d7ee 100%); */
            z-index: 1;
        }

        .main-image {
            position: relative;
            width: 100%;
            height: 292px;
            border-radius: 12px;
            object-fit: cover;
            border: 1px solid rgba(255, 255, 255, 0.95);
            z-index: 2;
            display: block;
        }

        .image-info {
            position: absolute;
            left: -28px;
            bottom: -20px;
            width: 182px;
            background-color: #ffffff;
            border-radius: 14px;
            padding: 14px 13px 12px;
            z-index: 3;
            box-shadow: 0 10px 20px rgba(32, 18, 42, 0.16);
        }

        .image-info h3 {
            margin: 0 0 8px;
            font-size: 20px;
            line-height: 1.06;
            color: #1f1f1f;
        }

        .image-info p {
            margin: 0;
            font-size: 13px;
            line-height: 1.45;
            color: #4a4a4a;
        }

        @media (max-width: 960px) {
            .hero {
                flex-direction: column;
                text-align: center;
                gap: 30px;
                padding: 24px 28px 36px;
            }

            .hero-image {
                width: min(100%, 560px);
            }

            .image-info {
                left: 8px;
                bottom: 10px;
            }

            .hero-cta-actions {
                justify-content: center;
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

<div class="hero">
    <div class="hero-text">
        <div class="tag">DIGITAL HERITAGE EXPERIENCE</div>
        <h1>Discover Cebu Like Never Before</h1>

        <p>
            Histaryo is an augmented reality-powered platform that brings Cebu’s rich cultural heritage to life.
            Whether you're a tourist, student, or local, explore landmarks through interactive AR, historical overlays, and gamified tours — all from your mobile device.
        </p>
        <div class="hero-cta">
            <h3>Start Exploring</h3>
            <p>Sign up and begin your interactive AR journey through Cebu's landmarks and stories.</p>
            <div class="hero-cta-actions">
                <a href="{{ route('register') }}" class="btn">Start Exploring</a>
                <a href="{{ route('about') }}" class="btn btn-secondary">Learn More</a>
            </div>
        </div>
    </div>

    <div class="hero-image">
        <div class="image-card">
            <img src="{{ asset('images/magellancross.jpg') }}" class="main-image" alt="Cebu Landmark">
            <div class="image-info">
                <h3>New Experience</h3>
                <p>Explore local history with visuals, stories, and AR overlays.</p>
            </div>
        </div>
    </div>
</div>

</body>
</html>
