<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HistARyo – Register</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
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
            overflow-x: hidden;
            overflow-y: auto;
        }

        .container {
            flex: 1;
            min-height: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 0 1.2rem 0.8rem;
        }

        .register-box {
            display: flex;
            width: min(940px, 100%);
            height: auto;
            background: var(--card);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid rgba(140, 92, 58, 0.15);
        }

        .form-side {
            flex: 0 0 45%;
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

        .form-side h1 {
            margin: 0 0 0.3rem;
            font-size: 1.55rem;
            color: var(--brand-dark);
            line-height: 1.15;
        }

        .form-side h2 {
            margin: 0 0 0.3rem;
            font-size: 1.05rem;
            color: var(--brand-dark);
            line-height: 1.15;
        }

        .subtitle {
            margin: 0 0 0.55rem;
            color: var(--muted);
            font-size: 0.68rem;
            line-height: 1.35;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 0.72rem;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 0.36rem;
        }

        .name-row {
            display: flex;
            gap: 0.45rem;
        }

        .name-row .field {
            flex: 1;
            min-width: 0;
        }

        .field label {
            font-size: 0.66rem;
            font-weight: 600;
            color: #566170;
        }

        .hidden {
            display: none;
        }

        form input,
        form select,
        form textarea {
            width: 100%;
            padding: 0.78rem 0.85rem;
            min-height: 48px;
            border-radius: 13px;
            border: 1px solid #d8e1ee;
            font-size: 0.88rem;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            background-color: #faf8f3;
        }

        form textarea {
            resize: vertical;
            min-height: 4.5rem;
            line-height: 1.45;
            font-family: inherit;
        }

        form input::placeholder,
        form textarea::placeholder {
            color: #b3c0d0;
        }

        .field-input-wrap {
            position: relative;
            width: 100%;
        }

        .field-input-wrap > input {
            padding-left: 2.65rem;
            border-color: #d8e1ee;
            outline: none;
            box-shadow: inset 0 1px 2px rgba(148, 163, 184, 0.08), 0 1px 3px rgba(148, 163, 184, 0.12);
        }

        .field-input-wrap > input:focus,
        .field-input-wrap > input:focus-visible {
            outline: none !important;
            border-color: #b8c9df;
            box-shadow: inset 0 1px 2px rgba(148, 163, 184, 0.08), 0 0 0 3px rgba(142, 160, 184, 0.14);
        }

        .field-input-icon {
            position: absolute;
            left: 0.86rem;
            top: 50%;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #8ea0b8;
            pointer-events: none;
            transform: translateY(-50%);
        }

        .field-input-icon svg {
            width: 1.12rem;
            height: 1.12rem;
        }

        .password-wrap {
            position: relative;
            width: 100%;
        }

        .password-wrap > input {
            padding-left: 2.65rem;
            padding-right: 2.8rem;
        }

        form .password-toggle {
            position: absolute;
            right: 0.45rem;
            top: 50%;
            transform: translateY(-50%);
            width: 2.15rem;
            height: 2.15rem;
            margin-top: 0;
            padding: 0;
            border: none;
            background: transparent;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #4f86ff;
            transition: color 0.2s ease, background-color 0.2s ease;
        }

        form .password-toggle:hover {
            color: #3d73e8;
            background: rgba(79, 134, 255, 0.08);
        }

        form .password-toggle:focus-visible {
            outline: 2px solid var(--brand);
            outline-offset: 2px;
        }

        form .password-toggle:active {
            transform: translateY(-50%);
        }

        .password-toggle svg {
            width: 1.12rem;
            height: 1.12rem;
            flex-shrink: 0;
        }

        .password-toggle .pw-icon-hide {
            display: none;
        }

        .password-toggle[aria-pressed="true"] .pw-icon-show {
            display: none;
        }

        .password-toggle[aria-pressed="true"] .pw-icon-hide {
            display: block;
        }

        .field-hint {
            margin: -0.15rem 0 0;
            font-size: 0.65rem;
            line-height: 1.35;
            color: #991b1b;
        }

        .field-hint[hidden] {
            display: none;
        }

        form input.input-invalid {
            border-color: #dc2626;
        }

        form input.input-invalid:focus {
            border-color: #dc2626;
            box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.15);
        }

        .choice-grid {
            display: flex;
            flex-direction: column;
            gap: 0.42rem;
            margin-top: 0.1rem;
        }

        .choice-card {
            display: flex;
            align-items: flex-start;
            gap: 0.48rem;
            padding: 0.52rem 0.62rem;
            margin: 0;
            border: 1px solid var(--input-border);
            border-radius: 8px;
            background: #fff;
            cursor: pointer;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .choice-card:focus-within {
            border-color: #b17853;
            box-shadow: 0 0 0 3px var(--focus-ring);
        }

        .choice-card input[type="radio"] {
            width: auto;
            margin: 0.2rem 0 0;
            accent-color: var(--brand-dark);
            flex-shrink: 0;
        }

        .choice-card > span {
            display: flex;
            flex-direction: column;
            gap: 0.18rem;
        }

        .choice-card strong {
            font-size: 0.75rem;
            color: var(--brand-dark);
        }

        .choice-card small {
            font-size: 0.65rem;
            color: var(--muted);
            line-height: 1.35;
            font-weight: 500;
        }

        form button {
            background: var(--brand);
            border: none;
            margin-top: 0.15rem;
            min-height: 48px;
            padding: 0.7rem;
            width: 100%;
            color: white;
            font-size: 0.9rem;
            border-radius: 13px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.2s ease, transform 0.08s ease;
        }

        form button:hover {
            background-color: var(--brand-dark);
        }

        form button:active {
            transform: translateY(1px);
        }

        .login-link {
            margin-top: 0.45rem;
            text-align: center;
            font-size: 0.78rem;
            color: var(--muted);
        }

        .login-link a {
            color: var(--brand);
            text-decoration: none;
            font-weight: bold;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .helper-note {
            margin-top: 0.45rem;
            text-align: center;
            font-size: 0.78rem;
            color: #847972;
        }

        .register-image {
            flex: 0 0 55%;
            background-image: url('{{ asset('images/magellancross.jpg') }}');
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .error-message {
            color: #991b1b;
            background-color: #fee2e2;
            border: 1px solid #f3b0b0;
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
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
            .register-box {
                flex-direction: column;
                width: 95%;
                height: auto;
                border-radius: 16px;
            }

            .register-image {
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

@include('partials.public-header')

<div class="container">
    <div class="register-box">
        <div class="form-side">
            <h1>Site Manager</h1>
            <p class="eyebrow">Join HistARyo</p>
            <h2>Create Account</h2>
            

            @if ($errors->any())
                <div class="error-message">
                    <ul style="margin: 0; padding-left: 20px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div class="name-row">
                    <div class="field">
                        <label for="first_name">First name</label>
                        <div class="field-input-wrap">
                            <span class="field-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.1a7.5 7.5 0 0 1 15 0"/></svg>
                            </span>
                            <input id="first_name" type="text" name="first_name" placeholder="First name" value="{{ old('first_name') }}" autocomplete="given-name" required autofocus>
                        </div>
                    </div>
                    <div class="field">
                        <label for="last_name">Last name</label>
                        <div class="field-input-wrap">
                            <span class="field-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.1a7.5 7.5 0 0 1 15 0"/></svg>
                            </span>
                            <input id="last_name" type="text" name="last_name" placeholder="Last name" value="{{ old('last_name') }}" autocomplete="family-name" required>
                        </div>
                    </div>
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <div class="field-input-wrap">
                        <span class="field-input-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5v10.5H3.75V6.75Z"/><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 7.5 7.5 5.25 7.5-5.25"/></svg>
                        </span>
                        <input id="email" type="email" name="email" placeholder="Enter your email address" value="{{ old('email') }}" autocomplete="email" required>
                    </div>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <div class="password-wrap field-input-wrap">
                        <span class="field-input-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 10.5h12v9H6v-9Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 10.5V7.75a3.75 3.75 0 0 1 7.5 0v2.75"/></svg>
                        </span>
                        <input id="password" type="password" name="password" placeholder="Create a strong password" autocomplete="new-password" required>
                        <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false" data-label-show="Show password" data-label-hide="Hide password">
                            <svg class="pw-icon pw-icon-show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 5 12 5c4.638 0 8.573 2.51 9.964 6.322.053.158.053.33 0 .488C20.577 16.49 16.64 19 12 19c-4.638 0-8.573-2.51-9.964-6.322z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <svg class="pw-icon pw-icon-hide" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19 12 19c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 5c4.756 0 8.773 2.663 10.065 7.022a10.522 10.522 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                        </button>
                    </div>
                </div>
                <div class="field">
                    <label for="password_confirmation">Confirm password</label>
                    <div class="password-wrap field-input-wrap">
                        <span class="field-input-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 10.5h12v9H6v-9Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 10.5V7.75a3.75 3.75 0 0 1 7.5 0v2.75"/></svg>
                        </span>
                        <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Confirm your password" autocomplete="new-password" required aria-describedby="password-confirm-hint">
                        <button type="button" class="password-toggle" aria-label="Show confirm password" aria-pressed="false" data-label-show="Show confirm password" data-label-hide="Hide confirm password">
                            <svg class="pw-icon pw-icon-show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 5 12 5c4.638 0 8.573 2.51 9.964 6.322.053.158.053.33 0 .488C20.577 16.49 16.64 19 12 19c-4.638 0-8.573-2.51-9.964-6.322z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <svg class="pw-icon pw-icon-hide" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19 12 19c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 5c4.756 0 8.773 2.663 10.065 7.022a10.522 10.522 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                        </button>
                    </div>
                    <p id="password-confirm-hint" class="field-hint" role="alert" hidden>Passwords do not match.</p>
                </div>
                <button type="submit">Register</button>
            </form>

            <div class="login-link">
                Already have an account? <a href="{{ route('login') }}">Log in</a>
            </div>
        </div>

        <div class="register-image" aria-hidden="true">
            <!-- <div class="image-overlay">
                <div class="overlay-card">
                    <h3>Explore Local Heritage</h3>
                    <p>Get access to curated landmarks and engaging stories powered by HistARyo.</p>
                </div>
            </div> -->
        </div>
    </div>
</div>

<script>
    (function () {
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('password_confirmation');
        const confirmHint = document.getElementById('password-confirm-hint');
        const registerForm = document.querySelector('form[action="{{ route('register') }}"]');

        function updatePasswordMatch() {
            if (!passwordInput || !confirmInput || !confirmHint) {
                return;
            }

            const password = passwordInput.value;
            const confirm = confirmInput.value;
            const mismatch = confirm !== '' && password !== confirm;

            confirmHint.hidden = !mismatch;
            confirmInput.classList.toggle('input-invalid', mismatch);
        }

        if (passwordInput && confirmInput) {
            passwordInput.addEventListener('input', updatePasswordMatch);
            confirmInput.addEventListener('input', updatePasswordMatch);
        }

        if (registerForm) {
            registerForm.addEventListener('submit', (event) => {
                updatePasswordMatch();
                if (
                    confirmInput
                    && confirmInput.value !== ''
                    && passwordInput
                    && passwordInput.value !== confirmInput.value
                ) {
                    event.preventDefault();
                    confirmInput.focus();
                }
            });
        }

        document.querySelectorAll('.password-wrap').forEach((wrap) => {
            const input = wrap.querySelector('input');
            const btn = wrap.querySelector('.password-toggle');
            if (!input || !btn) {
                return;
            }
            const showLabel = btn.dataset.labelShow || 'Show password';
            const hideLabel = btn.dataset.labelHide || 'Hide password';
            btn.addEventListener('click', () => {
                const willShow = input.type === 'password';
                input.type = willShow ? 'text' : 'password';
                btn.setAttribute('aria-pressed', willShow ? 'true' : 'false');
                btn.setAttribute('aria-label', willShow ? hideLabel : showLabel);
            });
        });
    })();
</script>

</body>
</html>
