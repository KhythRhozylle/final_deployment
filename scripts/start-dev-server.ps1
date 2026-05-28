# Single dev API on 0.0.0.0:8000 (admin + mobile + emulator + Wi-Fi phone).
param(
    [string]$ProjectDir = (Split-Path $PSScriptRoot -Parent)
)

$pidFile = Join-Path $ProjectDir "var\dev-server.pid"

function Stop-DevServer {
    if (Test-Path $pidFile) {
        $oldPid = Get-Content $pidFile -ErrorAction SilentlyContinue
        if ($oldPid) {
            Stop-Process -Id $oldPid -Force -ErrorAction SilentlyContinue
        }
        Remove-Item $pidFile -Force -ErrorAction SilentlyContinue
    }
    Get-NetTCPConnection -LocalPort 8000 -ErrorAction SilentlyContinue |
        ForEach-Object { Stop-Process -Id $_.OwningProcess -Force -ErrorAction SilentlyContinue }
    symfony server:stop --dir=$ProjectDir 2>$null
}

Stop-DevServer
Start-Sleep -Seconds 2

$php = (Get-Command php).Source
Write-Host "Starting PHP API on 0.0.0.0:8000 (one server for admin + mobile)..." -ForegroundColor Cyan
$proc = Start-Process -FilePath $php -ArgumentList '-S', '0.0.0.0:8000', '-t', 'public' `
    -WorkingDirectory $ProjectDir -PassThru -WindowStyle Hidden
$proc.Id | Set-Content $pidFile
Start-Sleep -Seconds 2

return $pidFile
