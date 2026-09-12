# WEBIS — Backend (Laravel 13 API)

The HTTP API for WEBIS. See the repository root `README.md` for full setup, and
`../docs/PHASE-0-REQUIREMENTS-AUDIT.md` for the architecture and roadmap.

**Requires PHP 8.3–8.5.** Laravel 13 does not run on PHP 8.2.

## Quick start

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Then check <http://localhost:8000/api/health>.

## Layering rules

```
Route  ->  Middleware (auth:sanctum, role:*, throttle:*)
       ->  Form Request      (validation + authorize)
       ->  Controller        (thin - no business logic)
       ->  Service class     (business rules, DB transactions)
       ->  Policy            (record-level authorization)
       ->  Eloquent Model    ->  MySQL
       ->  API Resource      (explicit field allowlist)
       ->  ApiResponse       (the only response builder)
```

Rules that are not negotiable:

- The client never supplies `role`, `user_id`, `provider_profile_id`, `price`,
  `status` or `payment.status`. All are derived server-side.
- Every route that loads a record by ID runs a Policy against the **loaded
  model**, not against the ID.
- Every model declares `$fillable`. Never `$guarded = []`.
- Controllers build responses only through `App\Support\Api\ApiResponse`.
- Expected business-rule failures throw `App\Exceptions\DomainException`.
- Request-forgery protection is never excluded for `api/*`. Sanctum installs it
  for first-party SPA requests; excluding it would undo that.

## Tests

```bash
php artisan test
```

Runs against in-memory SQLite by default. To run the same suite against MySQL,
set `DB_CONNECTION=mysql` and `DB_DATABASE=webis_testing` in `phpunit.xml`.

## Version policy

Laravel 13 was chosen because Laravel 11 reached end of security support on
12 March 2026 — every 11.x release now carries unpatched advisories and
Composer refuses to install it. Laravel 13 receives bug fixes until Q3 2027 and
security fixes until Q1 2028, which covers this project's timeline.
