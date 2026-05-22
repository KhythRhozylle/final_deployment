# Mobile app (OVALO) ↔ Symfony API

## The problem with `symfony serve:start` alone

Since Symfony CLI 5.10.3, the server **only listens on `127.0.0.1`**. That works in your browser, but **not** from:

- Android emulator (`10.0.2.2` / device network)
- A physical phone on Wi‑Fi

You will see the yellow warning in the terminal — follow it.

## Correct way to start the backend for mobile

**Option A — from this folder (recommended):**

```powershell
.\serve-mobile.ps1
```

**Option B — same flags manually:**

```powershell
symfony serve:start --allow-all-ip --allow-http
```

**Option C — from the React Native project:**

```powershell
cd ..\OVALO\ovalo
npm run symfony:start
```

## Run the app

| Device | Backend | Mobile (ovalo) |
|--------|---------|----------------|
| Android emulator | `serve-mobile.ps1` or flags above | `npm run android:dev` |
| Physical phone | `serve-mobile.ps1` + same Wi‑Fi | `npm run api:sync-host` then `npm run android` |

## Quick test

With the server running:

```powershell
curl http://127.0.0.1:8000/api/mobile/products
curl http://192.168.1.16:8000/api/mobile/products
```

Replace `192.168.1.16` with your PC IP from `ipconfig`.
