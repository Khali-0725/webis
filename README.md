# WEBIS — Web-Based Platform for Independent Service Providers

Capstone system for the study *Design and Development of a Web-Based Platform for Independent Service Provider* (Bartolome, Cacha, Carienena — Cavite State University–CCAT Campus, Rosario, Cavite).

WEBIS connects clients in Tanza, Cavite with verified independent service providers: service listings with barangay-based discovery, time-slot booking with an exact service-location pin, in-platform messaging with off-platform-contact filtering, QR-based payment recording, ratings, and an administrator console.

> **Build status — Phase 1 of 12 complete and verified.** Backend 40/40 tests, frontend 16/16. The foundation (API conventions, authentication, role authorization, design system, base layouts) is built and tested. Feature modules land in later phases. See `docs/PHASE-0-REQUIREMENTS-AUDIT.md` for the full roadmap.

---

## Architecture

```
React 18 + Vite  ──HTTP/JSON──▶  Laravel 13 API  ──▶  MySQL 8
   (frontend/)      cookies         (backend/)
```

| Layer | Technology |
|---|---|
| Frontend | React 18, Vite 5, Tailwind CSS 3, React Router 6, TanStack Query 5, React Hook Form + Zod, Zustand, Axios |
| Backend | Laravel 13 (PHP 8.3+), Sanctum 4 (stateful cookie auth), Form Requests, Policies, API Resources, service classes |
| Database | MySQL 8 (InnoDB, utf8mb4) |
| Maps (Phase 5) | Leaflet + OpenStreetMap tiles |
| Charts (Phase 9) | Recharts |
| Tests | PHPUnit (backend), Vitest + Testing Library (frontend) |

Authentication uses Sanctum's **stateful cookie** mode, not bearer tokens in `localStorage`. The session cookie is `HttpOnly`, so it cannot be read by injected JavaScript — a deliberate choice that supports the *Confidentiality* and *Resistance* sub-criteria of the ISO/IEC 25010 evaluation.

---

## Repository layout

```
WEBIS/
├── backend/          Laravel 13 API
│   ├── app/
│   │   ├── Enums/            UserRole, UserStatus
│   │   ├── Exceptions/       DomainException (business-rule failures)
│   │   ├── Http/
│   │   │   ├── Controllers/Api/   grouped by audience
│   │   │   ├── Middleware/        EnsureUserRole, ForceJsonResponse
│   │   │   ├── Requests/          one Form Request per write endpoint
│   │   │   └── Resources/         API Resources (field allowlists)
│   │   ├── Models/
│   │   ├── Services/         business logic (AuthService, …)
│   │   └── Support/Api/      ApiResponse envelope, ExceptionRenderer
│   ├── config/webis.php      app-specific settings
│   ├── database/{migrations,factories,seeders}/
│   ├── routes/api.php
│   └── tests/{Feature,Unit}/
│
├── frontend/         React SPA
│   └── src/
│       ├── components/{ui,layout}/   design-system primitives
│       ├── constants/                enums mirrored from the backend
│       ├── hooks/                    useAuth and friends
│       ├── layouts/                  Public / Auth / Dashboard shells
│       ├── pages/{public,auth,client,provider,admin}/
│       ├── routes/                   router + route guards
│       ├── services/{api,auth}/      Axios instance, query client
│       └── store/                    Zustand session store
│
└── docs/             audit, architecture, database, API, deployment, testing
```

---

## Requirements

| Tool | Version |
|---|---|
| PHP | **8.3 – 8.5** (Laravel 13 will not run on 8.2) |
| Composer | 2.x |
| Node.js | 20 LTS or newer |
| MySQL | 8.0+ (Laragon ships 8.4) |

---

## Setup

### 1. Database

Create an empty database. In Laragon: **Menu → MySQL → phpMyAdmin**, or from the terminal:

```sql
CREATE DATABASE webis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Backend

```bash
cd backend

composer install
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate

# Edit .env if your MySQL credentials differ from root / (blank password)

php artisan migrate --seed
php artisan serve             # http://localhost:8000
```

Verify: open <http://localhost:8000/api/health> — you should see
`{"success":true,"data":{"status":"ok","database":"up",...}}`.

### 3. Frontend

In a second terminal:

```bash
cd frontend

npm install
cp .env.example .env          # Windows: copy .env.example .env
npm run dev                   # http://localhost:5173
```

### 4. Sign in

Seeded demo accounts — **password for all: `password123`**

| Role | Email |
|---|---|
| Administrator | `admin@webis.test` |
| Client | `client@webis.test` |
| Service Provider | `provider@webis.test` |
| Suspended (for testing) | `suspended@webis.test` |

> These are development accounts only. The deployment guide requires changing or removing them before any real deployment.

---

## Commands

**Backend**

```bash
php artisan test                    # full test suite
php artisan test --filter=Login     # one test class
php artisan migrate:fresh --seed    # rebuild the database
vendor/bin/pint                     # format PHP to Laravel's style
```

**Frontend**

```bash
npm run dev        # dev server with HMR
npm run test       # Vitest suite
npm run lint       # ESLint
npm run build      # production build into dist/
npm run preview    # serve the production build locally
```

---

## Troubleshooting

**`CSRF token mismatch` / 419 on login**
`SANCTUM_STATEFUL_DOMAINS` and `CORS_ALLOWED_ORIGINS` in `backend/.env` must both include the exact origin the SPA is served from, including the port. `VITE_API_URL` in `frontend/.env` must point at the backend with no trailing slash.

**Login succeeds but `/api/auth/me` returns 401**
The session cookie is not being stored. Both apps must be on `localhost` (not one on `localhost` and the other on `127.0.0.1`) — the browser treats those as different sites.

**`SQLSTATE[HY000] [1049] Unknown database 'webis'`**
The database has not been created yet. See step 1.

**`Class "PDO" not found` or a missing extension**
Enable `pdo_mysql` and `fileinfo` in Laragon's `php.ini`, then restart Laragon.

**`Your requirements could not be resolved` mentioning security advisories**
The framework version being requested is past end of security support. Do **not**
bypass the check with `policy.advisories.block=false` — upgrade instead. This is
why WEBIS targets Laravel 13 rather than 11 (see `backend/README.md`).

**`laravel/framework ... requires php ^8.3`**
Laragon is running PHP 8.2. Right-click the Laragon tray icon → **PHP → Version**
and pick 8.3 or 8.4, then restart Laragon.

---

## Documentation

| File | Contents |
|---|---|
| `docs/PHASE-0-REQUIREMENTS-AUDIT.md` | Requirements matrix, thesis contradictions, ERD proposal, API inventory, roadmap |
| `docs/PHASE-1-REPORT.md` | What Phase 1 delivered and how it was verified |
| `docs/architecture/ARCHITECTURE.md` | Request lifecycle, layering rules, security model *(Phase 2)* |
| `docs/database/ERD.md` | Final entity-relationship diagram *(Phase 2)* |
| `docs/api/API.md` | Endpoint reference *(built up per phase)* |
| `docs/deployment/DEPLOYMENT.md` | Production deployment *(Phase 12)* |
| `docs/testing/TESTING.md` | Test plan and the thesis Table 6 functional-test matrix *(Phase 11)* |

---

## Licence

Academic capstone project. Not licensed for redistribution.
