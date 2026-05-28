# One API server for admin dashboard + mobile app (same database, same port).
# From florynn:  .\serve-mobile.ps1
# From ovalo:    npm run dev:connect

& (Join-Path $PSScriptRoot 'scripts\stop-docker-app.ps1')

if (Get-Command docker -ErrorAction SilentlyContinue) {
    $mysqlRunning = docker compose ps mysql 2>$null | Select-String -Pattern 'running'
    if (-not $mysqlRunning) {
        Write-Host "Starting Docker MySQL (florynnshop_db on port 3308)..." -ForegroundColor Cyan
        docker compose up -d mysql
        Start-Sleep -Seconds 8
    }
}

Write-Host "Stopping anything on port 8000..." -ForegroundColor Cyan
symfony server:stop 2>$null
Get-NetTCPConnection -LocalPort 8000 -ErrorAction SilentlyContinue |
    ForEach-Object { Stop-Process -Id $_.OwningProcess -Force -ErrorAction SilentlyContinue }
Start-Sleep -Seconds 2

Write-Host @"

Listening on http://0.0.0.0:8000 (admin + mobile use the SAME server)
  Verify: http://127.0.0.1:8000/api/mobile/status

  ovalo: npm run dev:connect  (then reload app)

"@ -ForegroundColor Cyan

$php = (Get-Command php).Source
Set-Location $PSScriptRoot
& $php -S 0.0.0.0:8000 -t public
