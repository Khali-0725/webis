# SERBISYOLINK — Reviewer and Defense Guide

## 1. One-minute explanation

SERBISYOLINK is a web platform that connects clients with independent service providers in Cavite, Philippines. Clients search for services, select a schedule and exact GPS-based service location, send a booking request, communicate with providers, submit payment proof, and review completed work. Providers publish services, manage availability, accept bookings, navigate to accepted locations, and track earnings. Super administrators verify providers, manage platform content, resolve reports, moderate users, and maintain audit records.

## 2. Problem addressed

Independent workers often rely on informal posts and messaging. Clients have difficulty comparing providers, checking availability, confirming service locations, and tracking booking status. SERBISYOLINK centralizes discovery, booking, communication, verification, and transaction tracking.

## 3. User roles

| Role | Main responsibilities |
|---|---|
| Client | Search services, select location, book, message, pay, report, review |
| Provider | Manage profile, services, availability, verification, bookings, messages, payments |
| Super administrator | Manage users, providers, documents, services, content, reports, settings, backups |

## 4. Core demonstration flow

1. Register or log in as a client.
2. Search a service and open its details.
3. Select province, municipality, and barangay.
4. Click **Use my location** and allow GPS permission.
5. Confirm address, payment method, and location checkbox.
6. Send booking request.
7. Log in as provider and accept the request.
8. Open **Go to this location** to launch Google Maps.
9. Start and complete the job.
10. Submit payment proof and leave a review.
11. Use admin account to review provider documents and reports.

## 5. Technical design

- React/Vite provides the single-page client application.
- Laravel exposes JSON API resources and enforces authorization through policies, middleware, and form requests.
- Sanctum provides stateful cookie authentication.
- Eloquent models and migrations define the relational data model.
- TanStack Query manages API cache, loading state, refetching, and mutations.
- Leaflet renders maps; browser Geolocation supplies the client GPS position.
- Vercel rewrites API requests to Render so Sanctum cookies remain first-party.

## 6. Security and privacy controls

- Passwords are hashed by Laravel; plain passwords are never stored.
- Sanctum protects authenticated API requests.
- Role middleware and policies restrict client, provider, and admin actions.
- Form Requests validate input before controllers execute business logic.
- Rate limits protect authentication and sensitive endpoints.
- Exact booking location is hidden until the provider accepts the booking.
- Uploaded documents use controlled storage paths and authorization checks.
- Soft deletes and audit logs support recovery and accountability.
- Admin actions record actor, target, action, and timestamp.

## 7. Key business rules

- Provider must submit required verification documents before final activation.
- Admin makes the final verification decision.
- Client booking requires a selected schedule and valid location data.
- Client must confirm GPS using **Use my location**.
- Pin must be within 3 km of selected barangay center.
- Booking request time is `bookings.created_at`; service schedule is `scheduled_date` plus `scheduled_start_time`.
- Newest bookings appear first.
- Validated client reports can create provider violations and performance deductions.
- Approved verification documents cannot be resubmitted unless admin requests new documents.

## 8. Likely defense questions

**Why React and Laravel?** React supports reusable interactive screens; Laravel provides mature routing, validation, authentication, ORM, and testing.

**Why use a 3 km radius?** Barangay coordinates are centroids, not property boundaries. A radius gives a practical service-area rule while preventing obviously incorrect locations.

**How is location privacy handled?** The client chooses the location, but the exact pin is withheld from the provider until the booking is accepted.

**How do you prevent unauthorized access?** Authentication identifies the user; role middleware, policies, scoped queries, validation, and privacy checks authorize each action.

**Why use Vercel rewrites?** Frontend and API run on separate hosts. Rewrites keep browser requests on one origin and avoid third-party cookie problems with Sanctum.

**What are the limitations?** GPS accuracy depends on device and permission; OpenStreetMap tiles depend on network availability; provider verification is administrator review, not government validation; free hosting may sleep or delay requests.

## 9. Files to show during review

- `frontend/src/pages/public/ServiceDetailPage.jsx` — booking form and GPS guard
- `frontend/src/components/map/LocationPicker.jsx` — Leaflet and Geolocation
- `backend/routes/api.php` — API surface and middleware
- `backend/app/Http/Controllers/Api/BookingController.php` — booking access and ordering
- `backend/app/Services/BookingService.php` — booking creation rules
- `backend/database/migrations/` — schema history
- `frontend/tests/` and `backend/tests/` — automated test evidence
- `docs/database/ERD.md` — database relationships

## 10. Live deployment

The thesis deployment uses Vercel for the React frontend, Render for the Laravel API, TiDB Cloud for MySQL-compatible storage, and Backblaze B2 for uploaded files. See [docs/deployment/DEPLOYMENT.md](docs/deployment/DEPLOYMENT.md) for environment variables and deployment steps.
