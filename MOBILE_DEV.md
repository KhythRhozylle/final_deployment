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

## Admin dashboard and mobile must use the same database

The shop data lives in **MySQL database `florynnshop_db`** (Docker volume).

| How you run Symfony | `DATABASE_URL` host | phpMyAdmin |
|---------------------|---------------------|------------|
| `.\serve-mobile.ps1` on Windows | `127.0.0.1:3308` | http://127.0.0.1:8080 (Docker must be running) |
| `docker compose up` (app container) | `mysql:3306` (same data) | http://127.0.0.1:8080 |

If phpMyAdmin looks **empty** but products appear in the admin UI:

1. Start MySQL: `docker compose up -d mysql`
2. Open phpMyAdmin at http://127.0.0.1:8080 — database **`florynnshop_db`**, table **`product`**
3. Confirm the API: `curl http://127.0.0.1:8000/api/mobile/status` — shows `productCount` and names

`.env.local` should keep `DATABASE_URL` pointing at `127.0.0.1:3308` when using Symfony CLI on your PC.

## Only one backend on port 8000

Do **not** run Docker `app` and Symfony CLI at the same time — Windows routes `127.0.0.1:8000` to Symfony and Wi‑Fi to Docker, so the admin and phone can see different data.

```powershell
cd c:\Users\khyth\Documents\florynn
.\scripts\stop-docker-app.ps1
.\serve-mobile.ps1
```

Use the **same URL** in your browser admin as the mobile app uses (see green Dev API bar on Shop).

## One-command sync (recommended)

```powershell
cd c:\Users\khyth\Documents\OVALO\ovalo
npm run sync:all
npm run start:reset
```

This stops Docker `app`, starts **one** PHP API on `0.0.0.0:8000`, configures the phone host, and tests `/api/mobile/shop`.

## What stays in sync

| Data | Admin | Mobile |
|------|-------|--------|
| Products / stock | `/product/` | Shop, Home, checkout |
| Categories | `/category/` | Shop filters |
| Orders | `/order/` | Profile → My orders |
| Services & contact info | `config/packages/florynn_shop.yaml` | About, Contact tabs |
| Contact messages | `/contact/inquiries` | Contact form POST |

## Sync products to the phone

```powershell
# Terminal 1 — backend (starts MySQL if needed)
cd c:\Users\khyth\Documents\florynn
.\serve-mobile.ps1

# Terminal 2 — mobile
cd c:\Users\khyth\Documents\OVALO\ovalo
npm run dev:connect
npm run start:reset
```

On the phone: open **Shop** tab and pull down to refresh, or switch away and back (auto-refresh).
