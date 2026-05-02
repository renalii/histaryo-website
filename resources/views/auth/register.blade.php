<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HistARyo – Register</title>
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
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--brand-dark);
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

        .register-box {
            display: flex;
            width: min(920px, 100%);
            height: min(630px, calc(100vh - 120px));
            background: var(--card);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid rgba(140, 92, 58, 0.15);
        }

        .form-side {
            flex: 0.95;
            min-height: 0;
            padding: 1.25rem 1.35rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
            background: linear-gradient(180deg, #fff 0%, #fef9f4 100%);
        }

        .role-summary {
            border: 1px solid var(--input-border);
            border-radius: 8px;
            padding: 0.5rem 0.62rem;
            background: #fff;
        }

        .role-summary h3 {
            margin: 0 0 0.25rem;
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--brand-dark);
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .role-summary p.role-summary-intro {
            margin: 0 0 0.45rem;
            font-size: 0.68rem;
            color: var(--muted);
            line-height: 1.35;
        }

        .role-summary .perms-label {
            margin: 0 0 0.2rem;
            font-size: 0.62rem;
            font-weight: 700;
            color: #654a39;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .role-summary ul {
            margin: 0;
            padding-left: 1rem;
            font-size: 0.67rem;
            color: #5c5048;
            line-height: 1.45;
        }

        .role-summary li {
            margin-bottom: 0.12rem;
        }

        .role-summary li:last-child {
            margin-bottom: 0;
        }

        #landmark-manager-info .helper-note {
            margin-top: 0.35rem;
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
            gap: 0.48rem;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 0.42rem;
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
            font-size: 0.7rem;
            font-weight: 600;
            color: #654a39;
        }

        .hidden {
            display: none;
        }

        form input,
        form select,
        form textarea {
            width: 100%;
            padding: 0.52rem 0.66rem;
            border-radius: 8px;
            border: 1px solid var(--input-border);
            font-size: 0.88rem;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            background-color: #fff;
        }

        form textarea {
            resize: vertical;
            min-height: 4.5rem;
            line-height: 1.45;
            font-family: inherit;
        }

        form input::placeholder,
        form textarea::placeholder {
            color: #9f938a;
        }

        form input:focus,
        form select:focus,
        form textarea:focus {
            border-color: #b17853;
            box-shadow: 0 0 0 4px var(--focus-ring);
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
            padding: 0.56rem;
            width: 100%;
            color: white;
            font-size: 0.9rem;
            border-radius: 8px;
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
            flex: 1.05;
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
            header {
                padding: 20px 18px;
            }

            nav a {
                margin-left: 12px;
                font-size: 0.92rem;
            }

            .register-box {
                flex-direction: column;
                width: 95%;
                height: auto;
                max-height: unset;
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
            header {
                padding: 18px 40px;
            }

            .register-box {
                height: min(430px, calc(100vh - 100px));
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

            .name-row {
                flex-direction: column;
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
    <div class="register-box">
        <div class="form-side">
            <p class="eyebrow">Join Histaryo</p>
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

            <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data">
                @csrf
                <div class="name-row">
                    <div class="field">
                        <label for="first_name">First name</label>
                        <input id="first_name" type="text" name="first_name" placeholder="First name" value="{{ old('first_name') }}" autocomplete="given-name" required autofocus>
                    </div>
                    <div class="field">
                        <label for="last_name">Last name</label>
                        <input id="last_name" type="text" name="last_name" placeholder="Last name" value="{{ old('last_name') }}" autocomplete="family-name" required>
                    </div>
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" placeholder="Enter your email address" value="{{ old('email') }}" autocomplete="email" required>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" placeholder="Choose a secure password" autocomplete="new-password" required>
                </div>
                <div class="field">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="">Select role</option>
                        <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="curator" {{ old('role') == 'curator' ? 'selected' : '' }}>Curator</option>
                        <option value="landmark_manager" {{ old('role') == 'landmark_manager' ? 'selected' : '' }}>Landmark Manager</option>
                    </select>
                </div>

                <div class="field {{ old('role') === 'landmark_manager' ? '' : 'hidden' }}" id="landmark-manager-info" aria-live="polite">
                    <div class="role-summary">
                        <h3>Landmark Manager role</h3>
                        <p class="role-summary-intro">A Landmark Manager can manage multiple curators. That is their main responsibility—overseeing and managing curators.</p>
                        <p class="perms-label">Permissions</p>
                        <ul>
                            <li>Manage and monitor curators</li>
                            <li>Add displays (items within the landmark)</li>
                            <li>Edit landmark details</li>
                        </ul>
                    </div>
                    <p class="helper-note">Landmark Manager accounts require admin approval after registration.</p>
                </div>

                <div class="field {{ old('role') === 'curator' ? '' : 'hidden' }}" id="curator-options">
                    <label>Register as Curator</label>
                    <div class="choice-grid">
                        <label class="choice-card" for="curator_registration_type_new_landmark">
                            <input
                                id="curator_registration_type_new_landmark"
                                type="radio"
                                name="curator_registration_type"
                                value="new_landmark"
                                {{ old('curator_registration_type', 'new_landmark') === 'new_landmark' ? 'checked' : '' }}
                            >
                            <span>
                                <strong>New Landmark</strong>
                                <small>If selected, registration requires admin approval.</small>
                            </span>
                        </label>

                        <label class="choice-card" for="curator_registration_type_existing_landmark">
                            <input
                                id="curator_registration_type_existing_landmark"
                                type="radio"
                                name="curator_registration_type"
                                value="existing_landmark"
                                {{ old('curator_registration_type') === 'existing_landmark' ? 'checked' : '' }}
                            >
                            <span>
                                <strong>Existing Landmark</strong>
                                <small>Join with the curator code from your Landmark Manager—approval stays pending until they confirm.</small>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="field {{ old('role') === 'curator' && old('curator_registration_type', 'new_landmark') === 'existing_landmark' ? '' : 'hidden' }}" id="landmark-code-field">
                    <label for="landmark_code">Landmark code</label>
                    <input id="landmark_code" type="text" name="landmark_code" placeholder="Enter landmark code (e.g. LM-ABC123)" value="{{ old('landmark_code') }}">
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
                    <p>Get access to curated landmarks and engaging stories powered by Histaryo.</p>
                </div>
            </div> -->
        </div>
    </div>
</div>

<script>
    (function () {
        const roleSelect = document.getElementById('role');
        const landmarkManagerInfo = document.getElementById('landmark-manager-info');
        const curatorOptions = document.getElementById('curator-options');
        const landmarkCodeField = document.getElementById('landmark-code-field');
        const landmarkCodeInput = document.getElementById('landmark_code');
        const curatorTypeInputs = document.querySelectorAll('input[name="curator_registration_type"]');

        function getSelectedCuratorType() {
            for (const input of curatorTypeInputs) {
                if (input.checked) {
                    return input.value;
                }
            }
            return '';
        }

        function updateCuratorFields() {
            const isCurator = roleSelect && roleSelect.value === 'curator';
            const isLandmarkManager = roleSelect && roleSelect.value === 'landmark_manager';
            const selectedType = getSelectedCuratorType();
            const needsLandmarkCode = isCurator && selectedType === 'existing_landmark';

            if (landmarkManagerInfo) {
                landmarkManagerInfo.classList.toggle('hidden', !isLandmarkManager);
            }

            if (curatorOptions) {
                curatorOptions.classList.toggle('hidden', !isCurator);
            }

            if (landmarkCodeField) {
                landmarkCodeField.classList.toggle('hidden', !needsLandmarkCode);
            }

            if (landmarkCodeInput) {
                landmarkCodeInput.required = needsLandmarkCode;
                if (!needsLandmarkCode) {
                    landmarkCodeInput.value = '';
                }
            }
        }

        if (roleSelect) {
            roleSelect.addEventListener('change', updateCuratorFields);
        }

        curatorTypeInputs.forEach((input) => {
            input.addEventListener('change', updateCuratorFields);
        });

        updateCuratorFields();
    })();
</script>

</body>
</html>
