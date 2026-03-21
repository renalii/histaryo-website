<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HistARyo – Register</title>
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
            font-size: 1.5rem;
            font-weight: bold;
            color: #6e4b3a;
        }

        nav a {
            text-decoration: none;
            color: #8c5c3a;
            margin-left: 25px;
            font-weight: 500;
        }

        nav a:hover {
            color: #a8744f;
        }

        .container {
            height: calc(100vh - 80px); /* Full height minus header */
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .register-box {
            display: flex;
            width: 900px;
            height: 100%;
            max-height: 460px;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .form-side {
            flex: 1;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-side h2 {
            margin-bottom: 0.5rem;
            font-size: 28px;
            color: #6e4b3a;
        }

        .form-side p {
            margin-bottom: 1rem;
            color: #555;
        }

        form input,
        form select {
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 0.75rem;
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

        .login-link {
            margin-top: 0.5rem;
            text-align: center;
            font-size: 0.9rem;
        }

        .login-link a {
            color: #8c5c3a;
            text-decoration: none;
            font-weight: bold;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .register-image {
            flex: 1;
            background-image: url('{{ asset('images/magellancross.jpg') }}');
            background-size: cover;
            background-position: center;
        }

        .error-message {
            color: #991b1b;
            background-color: #fee2e2;
            padding: 10px;
            border-radius: 6px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 850px) {
            .register-box {
                flex-direction: column;
                width: 95%;
                height: auto;
                max-height: unset;
            }

            .register-image {
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
    <div class="register-box">
        <div class="form-side">
            <h2>Create Account</h2>
            <p>Register to access your HistARyo dashboard.</p>

            @if ($errors->any())
                <div class="error-message">
                    <ul style="margin: 0; padding-left: 20px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data">
                @csrf
                <input type="text" name="name" placeholder="Full Name" value="{{ old('name') }}" required>
                <input type="email" name="email" placeholder="Email" value="{{ old('email') }}" required>
                <input type="password" name="password" placeholder="Password" required>

                <select name="role" required>
                    <option value="">Select Role</option>
                    <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="curator" {{ old('role') == 'curator' ? 'selected' : '' }}>Curator</option>
                </select>

                <input type="file" name="profile_image" accept="image/*">

                <button type="submit">Register</button>
            </form>

            <div class="login-link">
                Already have an account? <a href="{{ route('login') }}">Log in</a>
            </div>
        </div>

        <div class="register-image"></div>
    </div>
</div>

</body>
</html>
