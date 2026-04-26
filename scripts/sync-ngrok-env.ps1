# Same as `php artisan ngrok:sync`. Run AFTER `ngrok http 8000` (Laravel on that port).
$ErrorActionPreference = 'Stop'
$ProjectRoot = Split-Path -Parent $PSScriptRoot
$EnvFile = Join-Path $ProjectRoot '.env'

try {
    $resp = Invoke-RestMethod -Uri 'http://127.0.0.1:4040/api/tunnels' -TimeoutSec 5
} catch {
    Write-Host 'Could not reach ngrok (http://127.0.0.1:4040). Start ngrok first: ngrok http 8000' -ForegroundColor Red
    exit 1
}

$httpsUrl = $null
foreach ($t in $resp.tunnels) {
    if ($t.public_url -like 'https://*') {
        $httpsUrl = $t.public_url.TrimEnd('/')
        break
    }
}

if (-not $httpsUrl) {
    Write-Host 'No https tunnel found in ngrok response.' -ForegroundColor Red
    exit 1
}

Write-Host "Using ngrok URL: $httpsUrl" -ForegroundColor Green

$text = [System.IO.File]::ReadAllText($EnvFile)
if ($text -notmatch '(?m)^QR_PUBLIC_BASE_URL=') {
    Write-Host '.env has no QR_PUBLIC_BASE_URL= line; add it manually.' -ForegroundColor Red
    exit 1
}
$text = [regex]::Replace($text, '(?m)^QR_PUBLIC_BASE_URL=.*$', "QR_PUBLIC_BASE_URL=$httpsUrl")
[System.IO.File]::WriteAllText($EnvFile, $text, [System.Text.UTF8Encoding]::new($false))

Set-Location $ProjectRoot
& php artisan config:clear
Write-Host "QR_PUBLIC_BASE_URL=$httpsUrl (APP_URL left as-is). Re-open Preview QR in Curators." -ForegroundColor Green
