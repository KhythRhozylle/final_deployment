# Start Symfony so the React Native app (emulator / phone) can connect.
# Plain "symfony serve:start" only binds 127.0.0.1 — mobile cannot reach that.
#
# From this folder (florynn):
#   .\serve-mobile.ps1
#
# Then in ovalo:
#   npm run android:dev   (emulator + adb reverse)
#   npm run api:sync-host && npm run android   (physical phone)

Write-Host "Stopping any existing Symfony server on port 8000..." -ForegroundColor Cyan
symfony server:stop 2>$null
Start-Sleep -Seconds 2

Write-Host @"

Starting for MOBILE dev (--allow-all-ip --allow-http)
  - Emulator: use ovalo npm run android:dev (adb reverse)
  - Phone:    set androidHost to your PC IP in ovalo/src/config/api.local.js

"@ -ForegroundColor Yellow

$env:SYMFONY_ALLOW_ALL_IP = 'true'
symfony serve:start --allow-all-ip --allow-http
