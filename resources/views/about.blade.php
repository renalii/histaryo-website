<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>About Histaryo</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <style>
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
        }

        .about-section {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 200px;
            max-width: 2250px;
            margin: 0 auto;
            padding: 74px 60px 42px;
            flex-wrap: wrap;
        }

        .about-text {
            flex: 1 1 520px;
            max-width: 2020px;
        }

        .about-text h2 {
            font-size: 32px;
            margin-bottom: 20px;
        }

        .about-text p {
            font-size: 16px;
            line-height: 1.6;
            color: #333;
            margin-bottom: 20px;
        }

        .about-image {
            width: min(100%, 480px);
            margin-top: 0;
        }

        .image-stack {
            position: relative;
            width: 100%;
        }

        .bg-frame {
            position: absolute;
            top: 15px;
            left: 15px;
            width: 100%;
            border-radius: 12px;
            z-index: 1;
        }

        .main-image {
            position: relative;
            width: 100%;
            border-radius: 12px;
            z-index: 2;
            transform: rotate(-2deg);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }

        .btn {
            display: inline-block;
            background-color: #6e4b3a;
            color: white;
            padding: 10px 24px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            text-decoration: none;
            transition: background 0.3s ease;
        }

        .btn:hover {
            background-color: #a8744f;
        }

        .btn-secondary {
            background-color: transparent;
            color: #ffffff;
            border: 1px solid #ffffff;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.13);
        }

        .cta-section {
            max-width: 2250px;
            margin: 10px auto 44px;
            padding: 0 60px;
        }

        .cta-card {
            background: linear-gradient(130deg, #6e4b3a 0%, #8d779b 100%);
            color: #ffffff;
            border-radius: 18px;
            padding: 28px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            box-shadow: 0 14px 26px rgba(40, 22, 53, 0.2);
            max-width: 890px;
            margin-right: auto;
        }

        .cta-card h2 {
            margin: 0 0 8px;
            font-size: 30px;
            line-height: 1.2;
        }

        .cta-card p {
            margin: 0;
            font-size: 15px;
            line-height: 1.55;
            opacity: 0.94;
            max-width: 620px;
        }

        .cta-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @media (max-width: 960px) {
            .about-section {
                flex-direction: column;
                text-align: center;
                gap: 30px;
                padding: 24px 28px 36px;
            }

            .about-image {
                width: min(100%, 460px);
            }

            .cta-section {
                padding: 0 28px;
                margin: 0 auto 32px;
            }

            .cta-card {
                padding: 24px 22px;
            }

            .cta-card h2 {
                font-size: 26px;
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

<div class="about-section">
    <div class="about-text">
        <h2>What is Histaryo?</h2>
        <p>
            Histaryo is an AR-powered platform that transforms how locals and visitors explore Cebu’s rich cultural heritage.
            With just a mobile device, users can scan QR codes or GPS-based markers to unlock immersive AR experiences —
            including historical overlays, old photographs, trivia challenges, and gamified tours.
        </p>
        <p>
            Built to educate, entertain, and inspire, Histaryo bridges culture and technology to make history come alive like never before.
        </p>
    </div>

    <div class="about-image">
        <div class="image-stack">
            <img src="{{ asset('images/color.jpg') }}" class="bg-frame" alt="Color Frame">
            <img src="{{ asset('images/AR TOUR.jpg') }}" class="main-image" alt="Cebu Landmark">
        </div>
    </div>
</div>

<section class="cta-section">
    <div class="cta-card">
        <div>
            <h2>Ready to Start Exploring?</h2>
            <p>Join Histaryo and discover Cebu through AR tours, rich historical stories, and interactive landmark experiences from your phone.</p>
        </div>
        <div class="cta-actions">
            <a href="{{ route('register') }}" class="btn">Start Exploring</a>
            <a href="{{ route('login') }}" class="btn btn-secondary">Sign In</a>
        </div>
    </div>
</section>

</body>
</html>
