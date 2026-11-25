# SERBISYOLINK Backend

Laravel 13 API for the SERBISYOLINK service marketplace.

## Requirements

- PHP 8.3+
- Composer 2
- MySQL 8-compatible database

## Quick start

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Health endpoint: `GET http://localhost:8000/api/health`.

## Request flow

```text
Route
  → middleware (Sanctum, role, throttle)
  → Form Request (validation and authorization)
  → controller (orchestration)
  → service (business rules)
  → model/repository (persistence)
  → API Resource (response)
```

Controllers should stay thin. State transitions belong in services and the booking state machine. Policies enforce ownership and role boundaries.

## Main API areas

- `/api/auth` — registration, login, logout, password reset
- `/api/services` and `/api/service-categories` — service discovery
- `/api/providers` — provider profiles, map, availability, reviews
- `/api/bookings` — booking creation, status changes, location privacy
- `/api/conversations` — messaging, read state, presence
- `/api/payments` — payment methods, proofs, receipts
- `/api/verifications` — provider documents and admin decisions
- `/api/admin` — users, providers, reports, CMS, settings, backups, audit logs

See `routes/api.php` for the authoritative route list.

## Database

Migrations are in `database/migrations`. Reference data is seeded by `CaviteBarangaySeeder` and `ServiceCategorySeeder`. Local demo accounts and demo records are created by the demo seeders.

Do not run `php artisan migrate:fresh --seed` against production. It deletes application data.

## Testing

```bash
php artisan test
```

Use the test database configured in `phpunit.xml`. Feature tests cover auth, roles, booking workflows, verification, reports, files, payments, and admin actions.

## Environment and deployment

Important variables include database credentials, `APP_URL`, Sanctum stateful domains, frontend origin, mail transport, and S3-compatible storage credentials. Render runs this API in production. Read the root [HOW-TO-RUN.md](../HOW-TO-RUN.md) and [deployment guide](../docs/deployment/DEPLOYMENT.md).
