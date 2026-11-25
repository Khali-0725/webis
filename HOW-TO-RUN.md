# SERBISYOLINK — How to Run

## 1. Requirements

- Windows with Laragon, or equivalent PHP/MySQL setup
- PHP 8.3 or newer
- Composer 2
- Node.js 20 or newer and npm
- MySQL 8-compatible server
- Git

Laravel 13 requires PHP 8.3+. Apache is not required for local development because Laravel and Vite provide development servers.

## 2. Start the database

Start MySQL from Laragon. Create a database named `webis` if it does not already exist.

## 3. Configure and start the backend

```bash
cd backend
composer install
copy .env.example .env
php artisan key:generate
```

Edit `backend/.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=webis
DB_USERNAME=root
DB_PASSWORD=
```

Run migrations and local seed data:

```bash
php artisan migrate --seed
php artisan serve
```

Backend URL: `http://localhost:8000`.

Health check: `http://localhost:8000/api/health`.

## 4. Configure and start the frontend

Open a second terminal:

```bash
cd frontend
npm install
npm run dev
```

Open the URL shown by Vite, normally `http://localhost:5173`.

The local Vite proxy forwards `/api` and `/sanctum` to `http://localhost:8000`. Keep the backend running.

## 5. Demo accounts

| Role | Email | Password |
|---|---|---|
| Super administrator | `admin@webis.test` | `password123` |
| Client | `client@webis.test` | `password123` |
| Service provider | `provider@webis.test` | `password123` |

Use a separate browser profile when testing different roles at the same time.

## 6. Test and production builds

Frontend:

```bash
npm run lint
npm run test
npm run build
```

Backend:

```bash
php artisan test
```

Never run `migrate:fresh --seed` against production. It deletes all application data.

## 7. Common problems

### Database connection error

Confirm MySQL is running and `DB_*` values match the local database. Then run `php artisan config:clear`.

### Frontend cannot reach API

Confirm Laravel is running on port 8000. Restart Vite after changing environment or proxy settings.

### Authentication loops or 419 errors

Use the same browser origin, clear stale cookies, and confirm `/sanctum/csrf-cookie` responds before login.

### GPS button does not work

Allow browser location permission. Browser geolocation requires HTTPS in production; `localhost` is allowed during local development.

### Upload fails

Check `storage` permissions and filesystem variables. Production uses Backblaze B2; local development can use the local disk.

### Seed conflict

Run migrations once, then use `php artisan db:seed` for normal local reseeding. Do not edit production data manually to fix a seed failure.

## 8. Stop services

Press `Ctrl+C` in each terminal. Stop MySQL from Laragon when finished.
