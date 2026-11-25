# SERBISYOLINK — Complete Thesis Documentation

## Abstract

SERBISYOLINK is a web-based platform for connecting clients with independent service providers in Cavite, Philippines. It combines service discovery, provider verification, schedule-based booking, GPS-assisted service locations, messaging, payment proof submission, reviews, reports, and administrator controls. The system uses a React single-page frontend and Laravel API backed by a MySQL-compatible relational database.

## 1. Project identity

**System name:** SERBISYOLINK  
**System type:** Web-based service marketplace and booking platform  
**Primary users:** Clients, independent service providers, super administrators  
**Deployment model:** Browser-based SPA plus hosted API  
**Service area:** Cavite, Philippines  

## 2. Objectives

### General objective

Develop a secure web platform that improves service discovery, booking coordination, provider verification, and transaction tracking.

### Specific objectives

1. Let clients search services and providers by category and location.
2. Let providers manage services, operating areas, schedules, and bookings.
3. Require administrator review of provider verification documents.
4. Capture a client-confirmed GPS service location with a 3 km barangay rule.
5. Support booking status, messaging, payment proof, reviews, and reports.
6. Give administrators CMS, moderation, analytics, backup, and audit tools.
7. Provide automated tests and reproducible deployment documentation.

## 3. Scope

### Included

- Email/password and Google-assisted account registration
- Client, provider, and super-admin portals
- Service categories, services, provider profiles, and barangay data
- Availability rules and exceptions
- Booking request and status workflow
- GPS location picker and privacy-gated exact coordinates
- Provider verification documents and admin decisions
- Messaging, active/last-online presence, and read state
- Payment methods, payment proof, and receipts
- Reviews, reports, violations, and performance deductions
- FAQs, announcements, platform settings, and audit logs
- Database backup/export controls

### Excluded or limited

- Automatic government identity validation
- Background checks
- Built-in real-time payment gateway settlement
- Guaranteed GPS accuracy or offline maps
- Native mobile application
- Automatic legal mediation of disputes

## 4. Functional modules

| Module | Description |
|---|---|
| Authentication | Registration, login, logout, password reset, Google account linking, local password setup |
| Public pages | Landing page, About, Contact, FAQ, Privacy Policy |
| Client | Search, provider details, booking, location, messages, payments, reviews, reports |
| Provider | Business profile, services, availability, verification, bookings, navigation, earnings |
| Administration | Dashboard, analytics, users, providers, verifications, reports, violations, CMS, settings |
| Communication | Conversations, unread counts, read state, active/last-online indicators |
| Location | Province/municipality/barangay selection, GPS pin, 3 km radius validation, Google Maps link |
| Data protection | Authorization, audit logs, soft delete, controlled file access, backup/export |

## 5. Technology stack

### Frontend

- React 18.3
- Vite 8.2
- React Router 7.18
- Tailwind CSS 3.4
- TanStack Query 5.62
- Axios 1.7
- Leaflet 1.9 and OpenStreetMap
- Vitest 5 and Testing Library

### Backend

- PHP 8.3+
- Laravel 13.17+
- Laravel Sanctum 4.3
- Eloquent ORM
- Form Requests, Policies, Middleware, API Resources
- PHPUnit 12.5

### Infrastructure

- Vercel: static frontend and API rewrite proxy
- Render: Laravel web service
- TiDB Cloud: MySQL-compatible database
- Backblaze B2: S3-compatible uploaded-file storage

## 6. Architecture

The frontend communicates with JSON endpoints under `/api`. Laravel validates input, authenticates the user, authorizes the action, applies business rules, and persists data through Eloquent. API Resources keep response shapes stable. React Query caches responses and refreshes booking, notification, and messaging data.

## 7. Data model summary

Core entities include `users`, `barangays`, `provider_profiles`, `provider_service_areas`, `services`, `service_categories`, `provider_availability_rules`, and `provider_availability_exceptions`. Transaction entities include `bookings`, `booking_locations`, `booking_status_histories`, `payments`, `payment_proofs`, and `reviews`. Supporting entities include messages/conversations, reports, violations, notifications, FAQs, announcements, settings, audit logs, and backup records. See [ERD.md](database/ERD.md).

## 8. Booking workflow

```text
Client selects service
  → selects date/time and barangay
  → clicks Use my location
  → GPS pin is checked against 3 km radius
  → confirms address and payment method
  → booking request created
  → provider accepts or declines
  → exact location revealed only after acceptance
  → provider starts/completes work
  → payment proof and review recorded
```

Booking timestamps have two meanings: `created_at` records when the request was submitted; `scheduled_date` and `scheduled_start_time` record when service should happen.

## 9. Provider verification workflow

1. Provider submits required documents.
2. Admin reviews each document.
3. Admin approves or rejects each document.
4. Admin verifies provider only after all required documents are approved.
5. Approved documents are locked from resubmission unless admin requests replacements.

Verification means platform document review. It does not claim government validation, criminal background clearance, or guaranteed safety.

## 10. Security and privacy

Authentication uses Sanctum session cookies. Passwords are hashed. Middleware and policies enforce role and ownership boundaries. Form Requests validate input. Throttling protects sensitive routes. Exact booking coordinates are authorization-gated. Uploaded files are stored outside public application logic and served through controlled endpoints. Soft deletes preserve recoverable records. Audit logs preserve administrator accountability.

## 11. Testing strategy

### Backend

PHPUnit feature tests cover authentication, authorization, booking state transitions, verification, reports, payments, file access, admin functions, and API responses.

### Frontend

Vitest and Testing Library cover route behavior, booking GPS guard, location picker, verification preview, forms, and key UI workflows.

### Manual acceptance checks

- Register client and provider accounts.
- Confirm Google account users are prompted to create a local password.
- Submit both provider verification documents.
- Approve documents as admin and verify provider.
- Create booking with GPS permission.
- Confirm wrong-radius location is rejected.
- Accept booking and open Google Maps destination.
- Confirm exact location is hidden before acceptance.
- Submit payment proof, complete booking, review, and report flow.

Run checks with:

```bash
cd frontend && npm run lint && npm run test && npm run build
cd ../backend && php artisan test
```

## 12. Deployment

Production deployment uses GitHub-triggered builds. Vercel builds the frontend with `npm run build`. Render installs Composer dependencies, runs Laravel migrations, and serves the API. Environment variables provide database, Sanctum, mail, storage, and frontend origin settings. Never commit secrets or use demo credentials in production. See [deployment/DEPLOYMENT.md](deployment/DEPLOYMENT.md).

## 13. Maintenance and backup

- Back up the database before destructive maintenance.
- Keep migrations additive and reversible where practical.
- Test restore procedures in a non-production database.
- Keep uploaded-file storage backups separate from database backups.
- Review audit logs after administrator or moderation changes.
- Run frontend lint/build and backend tests before deployment.

## 14. Known limitations and future work

- Add a proper payment gateway for automatic settlement.
- Improve barangay boundaries using polygon geodata instead of centroid radius.
- Add push notifications and native mobile clients.
- Add stronger identity verification integrations.
- Add observability dashboards, scheduled backups, and disaster-recovery drills.

## 15. Reproducibility checklist

- Clone repository.
- Install PHP and Node dependencies.
- Configure database and environment files.
- Run migrations and seed local data.
- Start backend and frontend.
- Run automated tests.
- Follow reviewer demonstration flow.
- Record commit hash, deployment URL, test output, and environment versions in the thesis appendix.
