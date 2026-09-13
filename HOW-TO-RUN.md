# WEBIS — how to start the system

Everything runs from the **Laragon Terminal**. You do not need PowerShell or CMD.

---

## 1. Start Laragon

Open Laragon, press **Start All**.
Wait until **MySQL 8.4.3** turns green. (Apache is not needed.)

## 2. Open the Laragon Terminal

Press the **Terminal** button in the Laragon window.

## 3. Tab 1 — backend

```
cd "C:\Users\Josh\Desktop\WEBIS SYSTEM\backend"
php artisan serve
```

Wait for:

```
INFO  Server running on [http://127.0.0.1:8000].
```

Leave this tab alone.

## 4. Tab 2 — frontend

Press **Ctrl + T**, then **Enter**. That opens a second tab in the same window.

```
cd "C:\Users\Josh\Desktop\WEBIS SYSTEM\frontend"
npm run dev
```

Wait for:

```
VITE v8.2.2  ready in ___ ms
➜  Local:   http://localhost:5173/
```

Switch between tabs with **Ctrl + Tab**.

## 5. Open the app

<http://localhost:5173>

| Role | Email | Password |
|---|---|---|
| Administrator | admin@webis.test | password123 |
| Client | client@webis.test | password123 |
| Service Provider | provider@webis.test | password123 |

## 6. Stopping

`Ctrl + C` in each tab, then **Stop All** in Laragon.

---

## Which command goes in which folder

| Folder | Command |
|---|---|
| `backend` | `php artisan serve` |
| `frontend` | `npm run dev` |

Both must be running at the same time. The frontend alone shows the pages but
cannot sign in; the backend alone has no user interface.

Wrong-folder errors:

- `npm error ENOENT ... backend\package.json` — a frontend command in the
  backend folder. The backend has no `package.json`, and that is correct.
- `Could not open input file: artisan` — a backend command in the frontend
  folder.

---

## Other commands

Backend (in `backend`):

```
php artisan test                    run the test suite (expect 228 passed)
php artisan migrate:fresh --seed    wipe and rebuild the database
```

Frontend (in `frontend`):

```
npm run test     run the frontend tests (expect 26 passed)
npm run lint     check code style
npm run build    production build
```

---

## If something is wrong

**"Cannot reach the WEBIS server" on the dashboard**
`php artisan serve` is not running, or it is on a different port than
`VITE_API_URL` in `frontend/.env`.

**`database: down`, or `SQLSTATE ... Connection refused`**
MySQL is not green in Laragon. Press Start All.

**419 / CSRF error when signing in**
`SANCTUM_STATEFUL_DOMAINS` in `backend/.env` must contain the exact origin the
SPA runs on, including the port. Use `localhost` in the browser, not
`127.0.0.1` — the browser treats them as different sites.

**Avatars, QR codes, or payment-proof images don't load, but everything
else works**
The backend (Tab 1) was not running yet when the frontend (Tab 2) started,
or was restarted after. Restart `npm run dev` in Tab 2 with the backend
already up in Tab 1 first.

**Port 8000 already in use**
An older `php artisan serve` is still running. Close that tab, or run
`php artisan serve --port=8001` and change `VITE_API_URL` in `frontend/.env`
to match.

**`php` is not recognized**
You are in PowerShell or CMD, not the Laragon Terminal. PHP lives inside
`C:\laragon\bin\php` and is not on the system PATH. Use the Laragon Terminal.

**`npm.ps1 cannot be loaded because running scripts is disabled`**
Same cause: that is PowerShell, not the Laragon Terminal. Use the Laragon
Terminal and the error does not occur.
