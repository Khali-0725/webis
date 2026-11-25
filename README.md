# SERBISYOLINK

Web-based service marketplace for clients and independent service providers in Cavite, Philippines.

SERBISYOLINK lets clients discover services, choose a service location, book an available schedule, message providers, submit payment proof, and leave reviews. Providers manage their profile, services, availability, bookings, messages, payments, and verification documents. Super administrators manage users, providers, services, categories, barangays, reports, FAQs, announcements, backups, and platform settings.

## Documentation

- [How to run locally](HOW-TO-RUN.md)
- [Reviewer and defense guide](REVIEWER.md)
- [Complete thesis documentation](docs/THESIS-DOCUMENTATION.md)
- [Deployment guide](docs/deployment/DEPLOYMENT.md)
- [Database ERD](docs/database/ERD.md)
- [Professor revision tracker](docs/PROFESSOR-REVISION-TRACKER.md)

## Architecture

```text
React SPA (Vercel)
        │ HTTP/JSON, Sanctum session cookie
        ▼
Laravel API (Render)
        │ Eloquent ORM
        ▼
MySQL-compatible database (TiDB Cloud)
        │
        └── S3-compatible file storage (Backblaze B2)
```

Vercel rewrites `/api/*` and `/sanctum/*` to the Laravel API. This keeps authentication cookies first-party from the browser.

## Technology stack

| Layer | Technology |
|---|---|
| Client | React 18, Vite 8, React Router 7 |
| UI | Tailwind CSS 3, accessible reusable components |
| Client data | TanStack Query 5, Axios |
| Maps | Leaflet 1.9, OpenStreetMap tiles, browser Geolocation API |
| API | Laravel 13, PHP 8.3+, Laravel Sanctum 4 |
| Database | MySQL 8-compatible database, InnoDB, `utf8mb4` |
| Storage | Backblaze B2 through Laravel filesystem/S3 adapter |
| Tests | PHPUnit 12, Vitest 5, Testing Library |
| Hosting | Vercel frontend, Render backend, TiDB Cloud database |

## Repository layout

```text
backend/                 Laravel API, migrations, seeders, tests
frontend/                React SPA, components, pages, API clients, tests
docs/                    Thesis, architecture, database, deployment, testing
README.md                Project overview
REVIEWER.md              Oral defense and demonstration guide
HOW-TO-RUN.md            Local setup and troubleshooting
```

## Local quick start

Requirements: PHP 8.3+, Composer, Node.js 20+, npm, MySQL 8-compatible database.

```bash
cd backend
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

In another terminal:

```bash
cd frontend
npm install
npm run dev
```

Open `http://localhost:5173`. Full instructions are in [HOW-TO-RUN.md](HOW-TO-RUN.md).

## Useful commands

```bash
# frontend
npm run dev
npm run lint
npm run test
npm run build

# backend
php artisan serve
php artisan test
php artisan migrate
php artisan migrate:fresh --seed   # local development only
```

## Demo accounts

Seeded local accounts use password `password123`:

| Role | Email |
|---|---|
| Super administrator | `admin@webis.test` |
| Client | `client@webis.test` |
| Service provider | `provider@webis.test` |

Do not use seeded credentials in production.

## Important behavior

- Provider verification requires the required documents and administrator approval.
- Client booking requires a selected barangay, address, payment method, and successful **Use my location** GPS confirmation.
- Booking GPS pins are restricted to the configured 3 km barangay radius.
- Exact booking location is hidden until the provider accepts the booking.
- Providers can open accepted-job coordinates in Google Maps.
- Booking lists sort newest request first using `created_at`.
- Google sign-up users must create a local password before using password login.
- Reports, provider violations, performance deductions, chat moderation, and audit logs are administrator-controlled.

## License and academic use

SERBISYOLINK is a student thesis/capstone system. Review `docs/THESIS-DOCUMENTATION.md` for scope, limitations, security controls, test strategy, and deployment notes.
