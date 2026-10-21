<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MFA settings - HistARyo</title>
    <style>
        * { box-sizing: border-box; } body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 1rem; background: #e8e0ef; color: #1f1a17; font-family: 'Segoe UI', sans-serif; }
        main { width: min(620px, 100%); padding: 2rem; border-radius: 20px; background: #fff; box-shadow: 0 18px 40px rgba(53,33,21,.16); }
        input, button { width: 100%; padding: .75rem; margin-top: .4rem; border-radius: 10px; } input { border: 1px solid #d9cec5; } button { border: 0; background: #8c5c3a; color: #fff; font-weight: 700; }
        form { margin-top: 1.25rem; } code { display: block; padding: .8rem; background: #f4eff7; overflow-wrap: anywhere; } .error { color: #991b1b; } .success { color: #166534; } .codes { line-height: 2; letter-spacing: .12rem; }
    </style>
</head>
<body><main>
    <h1>Multi-factor authentication</h1>
    <p>Email OTP is always enabled for Site Manager and Curator accounts.</p>
    @if ($errors->any()) <p class="error">{{ $errors->first() }}</p> @endif
    @if (session('status')) <p class="success">{{ session('status') }}</p> @endif
    @if (session('recovery_codes'))
        <h2>Save your recovery codes</h2>
        <p>Each code works once. This is the only time they will be displayed.</p>
        <code class="codes">@foreach (session('recovery_codes') as $code){{ $code }}<br>@endforeach</code>
    @endif
    @if (! $enabled)
        <h2>Enable authenticator app</h2>
        <p>Scan this QR code with Google Authenticator, Microsoft Authenticator, Authy, or a compatible app.</p>
        <div>{!! $qrCode !!}</div>
        <p>If scanning is unavailable, enter this secret manually:</p><code>{{ $secret }}</code>
        <form method="POST" action="{{ route('mfa.settings.enable') }}">
            @csrf
            <input type="hidden" name="secret" value="{{ $secret }}">
            <label>Current password</label><input type="password" name="password" autocomplete="current-password" required>
            <label>6-digit authenticator code</label><input name="code" inputmode="numeric" maxlength="6" required>
            <button type="submit">Enable authenticator app</button>
        </form>
    @else
        <h2>Authenticator app enabled</h2>
        <form method="POST" action="{{ route('mfa.settings.recovery') }}">
            @csrf
            <label>Current password to regenerate recovery codes</label><input type="password" name="password" autocomplete="current-password" required>
            <button type="submit">Generate new recovery codes</button>
        </form>
        <form method="POST" action="{{ route('mfa.settings.disable') }}">
            @csrf
            <label>Current password to disable authenticator app</label><input type="password" name="password" autocomplete="current-password" required>
            <button type="submit">Disable authenticator app</button>
        </form>
    @endif
</main></body>
</html>
