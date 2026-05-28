# Free port 8000 for Symfony CLI (avoids admin on Docker vs mobile on Symfony split).
if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    exit 0
}
$running = docker compose ps app 2>$null | Select-String -Pattern 'running'
if ($running) {
    Write-Host "Stopping Docker app container (keeps MySQL on 3308)..." -ForegroundColor Cyan
    docker compose stop app
}
