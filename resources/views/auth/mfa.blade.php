<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify sign-in - HistARyo</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 1.25rem; background: #e8e0ef; color: #1f1a17; font-family: 'Segoe UI', sans-serif; }
        .card { width: min(460px, 100%); padding: clamp(1.35rem, 5vw, 2rem); border: 1px solid rgba(140, 92, 58, .15); border-radius: 20px; background: #fff; box-shadow: 0 18px 40px rgba(53, 33, 21, .16); }
        h1 { margin: 0 0 .5rem; color: #6e4b3a; font-size: 1.35rem; }
        p { color: #6b5f58; line-height: 1.5; }
        .field { margin-top: 1.1rem; }
        label { display: block; margin: 0 0 .4rem; font-weight: 600; }
        select, input { display: block; width: 100%; min-height: 46px; padding: .75rem .8rem; border: 1px solid #d9cec5; border-radius: 10px; background: #fff; font: inherit; }
        input.code { font-size: 1.2rem; letter-spacing: .3rem; text-align: center; }
        .method-note { margin: .65rem 0 0; color: #6b5f58; font-size: .92rem; }
        button { width: 100%; margin-top: 1rem; padding: .8rem; border: 0; border-radius: 10px; background: #8c5c3a; color: #fff; font-weight: 700; cursor: pointer; }
        button:disabled { cursor: not-allowed; opacity: .55; }
        .secondary { margin-top: .75rem; border: 1px solid #d9cec5; background: #fff; color: #6b5f58; font-size: .9rem; }
        .error { color: #991b1b; font-size: .9rem; }
        .success { color: #166534; font-size: .9rem; }
        .hint { margin: .7rem 0 0; font-size: .85rem; }
    </style>
</head>
<body>
    <main class="card">
        <h1>Verify your sign-in</h1>
        <p>Choose one of your enrolled second-factor methods to finish signing in.</p>
        @if ($errors->any())
            <p class="error" role="alert">{{ $errors->first() }}</p>
        @endif
        @if (session('status'))
            <p class="success" role="status">{{ session('status') }}</p>
        @endif
        <form method="POST" action="{{ route('login.mfa.verify') }}">
            @csrf
            <div class="field">
                <label for="method">Verification method</label>
                <select id="method" name="method" required>
                @foreach ($methods as $method)
                    <option value="{{ $method }}" @selected(old('method', $emailSent ? ($methods[0] ?? 'email') : 'authenticator') === $method)>{{ match ($method) { 'authenticator' => 'Authenticator app', 'recovery' => 'Recovery code', default => 'Email code' } }}</option>
                @endforeach
                </select>
                <p id="method-note" class="method-note"></p>
            </div>
            <div class="field">
                <label id="code-label" for="code">Email verification code</label>
                <input class="code" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" required autofocus>
            </div>
            <button type="submit">Verify and continue</button>
        </form>
        <form method="POST" action="{{ route('login.mfa.resend') }}">
            @csrf
            <button id="resend-button" type="submit" class="secondary">Resend email code</button>
        </form>
        <form method="POST" action="{{ route('login.mfa.cancel') }}">
            @csrf
            <button class="secondary" type="submit">Cancel</button>
        </form>
        <p class="hint">Your verification challenge expires in 5 minutes. Never share your code.</p>
    </main>
    <script>
        (() => {
            const method = document.getElementById('method');
            const label = document.getElementById('code-label');
            const input = document.getElementById('code');
            const note = document.getElementById('method-note');
            const resend = document.getElementById('resend-button');
            const verify = document.querySelector('button[type="submit"]');
            const email = @json($email);
            const emailSent = @json($emailSent);
            const initialCooldown = {{ max(0, (int) ($resendAvailableIn ?? 0)) }};

            function updateMethod() {
                const selected = method.value;
                const emailMethod = selected === 'email';
                const recovery = selected === 'recovery';
                label.textContent = emailMethod ? 'Email verification code' : (recovery ? 'Recovery code' : 'Authenticator app code');
                input.inputMode = recovery ? 'text' : 'numeric';
                input.autocomplete = emailMethod ? 'one-time-code' : 'off';
                input.maxLength = recovery ? 32 : 6;
                input.pattern = recovery ? '[A-Za-z0-9-]{8,32}' : '[0-9]{6}';
                input.value = '';
                if (emailMethod) {
                    note.textContent = emailSent ? 'Enter the six-digit code sent to ' + email + '.' : 'Send a code to ' + email + ' before verifying.';
                } else if (selected === 'authenticator') {
                    note.textContent = 'Open your enrolled authenticator app and enter its current six-digit code. No email code is required.';
                } else {
                    note.textContent = 'Enter one unused recovery code. Each recovery code works only once.';
                }
                resend.hidden = !emailMethod;
                verify.disabled = emailMethod && !emailSent;
            }

            let remaining = initialCooldown;
            function updateCooldown() {
                if (remaining > 0) {
                    resend.disabled = true;
                    resend.textContent = 'Resend email code (' + remaining + 's)';
                    remaining -= 1;
                    window.setTimeout(updateCooldown, 1000);
                } else {
                    resend.disabled = false;
                    resend.textContent = emailSent ? 'Resend email code' : 'Send email code';
                }
            }
            method.addEventListener('change', updateMethod);
            updateMethod();
            updateCooldown();
        })();
    </script>
</body>
</html>
