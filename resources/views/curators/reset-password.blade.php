<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Change password | Histaryo</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <style>
        * { box-sizing: border-box; }
        body { min-height:100vh; margin:0; background:#e8e0ef; color:#1f1a17; font-family:'Segoe UI',sans-serif; }
        .topbar { display:flex; align-items:center; gap:1rem; padding:24px 48px; }
        .back { color:#7A2E1F; font-size:1.7rem; text-decoration:none; line-height:1; }
        .logo { color:#5c2318; font-size:1.4rem; font-weight:800; }
        .container { display:flex; justify-content:center; padding:4rem 1.25rem; }
        .card { width:min(480px,100%); padding:2rem 2.1rem; background:#fff; border:1px solid rgba(122,46,31,.12); border-radius:18px; box-shadow:0 18px 40px rgba(53,33,21,.16); }
        h1 { margin:0 0 .5rem; color:#5c2318; font-size:1.55rem; }
        .subtitle { margin:0 0 1.4rem; color:#6b5f58; }
        .field { margin-bottom:1rem; }
        label { display:block; margin-bottom:.35rem; color:#5c2318; font-size:.85rem; font-weight:700; }
        .password { position:relative; }
        input { width:100%; padding:.72rem 2.8rem .72rem .8rem; border:1px solid #d9cec5; border-radius:10px; font-size:.95rem; }
        input:focus { outline:none; border-color:#7A2E1F; box-shadow:0 0 0 3px rgba(122,46,31,.12); }
        .toggle { position:absolute; top:50%; right:.45rem; transform:translateY(-50%); border:0; background:transparent; color:#7A2E1F; cursor:pointer; }
        .error { margin:0 0 1rem; padding:.7rem .85rem; border:1px solid #fecaca; border-radius:10px; background:#fef2f2; color:#991b1b; font-size:.88rem; }
        .submit { width:100%; margin-top:.25rem; padding:.75rem 1rem; border:1px solid #F3C96A; border-radius:10px; background:#E8B34B; color:#7A2E1F; font-size:.95rem; font-weight:700; cursor:pointer; }
    </style>
</head>
<body>
    <header class="topbar">
        <a class="back" href="{{ route('curators.login') }}" aria-label="Back to login">&larr;</a>
        <div class="logo">Histaryo</div>
    </header>
    <main class="container">
        <section class="card">
            <h1>Change password</h1>
            <p class="subtitle">Enter your new password below.</p>
            @if ($errors->any())
                <div class="error" role="alert">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('curators.password-reset.update') }}" id="resetPasswordForm">
                @csrf
                <input type="hidden" name="oobCode" value="{{ $oobCode }}">
                <div class="field">
                    <label for="password">New password</label>
                    <div class="password">
                        <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required autofocus>
                        <button class="toggle" type="button" data-target="password" data-label="new password" aria-label="Show new password" aria-pressed="false">&#128065;</button>
                    </div>
                </div>
                <div class="field">
                    <label for="password_confirmation">Confirm password</label>
                    <div class="password">
                        <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required>
                        <button class="toggle" type="button" data-target="password_confirmation" data-label="confirm password" aria-label="Show confirm password" aria-pressed="false">&#128065;</button>
                    </div>
                </div>
                <button class="submit" type="submit">Save changes</button>
            </form>
        </section>
    </main>
    <script>
        document.querySelectorAll('.toggle').forEach(function (button) {
            button.addEventListener('click', function () {
                const input = document.getElementById(button.dataset.target);
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                button.setAttribute('aria-label', (show ? 'Hide ' : 'Show ') + button.dataset.label);
                button.setAttribute('aria-pressed', show ? 'true' : 'false');
            });
        });
        document.getElementById('resetPasswordForm').addEventListener('submit', function (event) {
            const password = document.getElementById('password');
            const confirmation = document.getElementById('password_confirmation');
            confirmation.setCustomValidity(password.value === confirmation.value ? '' : 'New password and Confirm password must match.');
            if (!this.checkValidity()) {
                event.preventDefault();
                this.reportValidity();
            }
        });
    </script>
</body>
</html>
