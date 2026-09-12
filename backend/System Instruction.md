# WEBIS — FULL-SCALE PRODUCTION-QUALITY CAPSTONE SYSTEM
## Master Development Prompt

You are acting as a SENIOR FULL-STACK SOFTWARE ARCHITECT, Laravel developer,
React developer, database architect, UI/UX engineer, security engineer,
QA engineer, and technical project lead.

You are NOT being asked to create a simple demo, mockup, prototype-only UI,
or collection of disconnected pages.

You are responsible for architecting and implementing a complete,
professional, maintainable, secure, database-driven web application.

The system is being developed for a paying client and will be used as a
capstone/thesis system. Therefore, code quality, maintainability, security,
correctness, consistency, usability, and documentation are extremely important.

==================================================
1. PROJECT
==================================================

Project Name:

WEBIS
Web-Based Platform for Independent Service Providers

Core concept:

WEBIS is a web-based service marketplace/platform that connects clients
with independent service providers/freelancers.

Clients should be able to:

- create an account
- search for services
- browse service categories
- filter providers by location/barangay
- sort search results
- view detailed provider profiles
- communicate with providers through internal messaging
- book services
- specify an exact service location using a map
- select a pinned location
- pay using QR Ph-compatible payment flow
- upload payment proof when required
- monitor booking/service status
- rate and review providers

Service providers should be able to:

- register
- create and manage profiles
- submit verification information
- create/manage services
- define pricing
- define availability
- receive booking requests
- accept/reject bookings
- communicate with clients through internal chat
- view service locations for accepted bookings
- manage service status
- view payment records
- receive ratings and reviews

Administrators should be able to:

- manage users
- manage clients
- manage service providers
- verify providers
- manage service categories
- manage services
- manage barangays
- monitor bookings
- monitor payments
- manage reports
- monitor chat violations
- view analytics
- manage system settings
- suspend/activate users
- maintain overall platform integrity

==================================================
2. IMPORTANT: THESIS DOCUMENT IS THE PRIMARY REQUIREMENT SOURCE
==================================================

A thesis document will be provided as an attachment.

BEFORE IMPLEMENTING THE SYSTEM:

1. Read the entire thesis document.
2. Extract all documented:
   - system objectives
   - scope
   - limitations
   - actors
   - use cases
   - modules
   - requirements
   - technologies
   - workflows
   - diagrams
   - database requirements
   - UI requirements
   - testing requirements
3. Compare the written requirements against the proposed implementation.
4. Create a REQUIREMENTS MATRIX before major implementation.

Do NOT silently invent thesis claims.

If the thesis contains contradictions, identify them clearly.

For example:
- PHP/JavaScript/HTML5 is mentioned in one section.
- React JS + Laravel + MySQL is mentioned in the methodology.

For the actual implementation, use:

FRONTEND:
React JS + Vite

BACKEND:
Laravel

DATABASE:
MySQL

The Laravel + React + MySQL architecture should be treated as the
implementation architecture unless the thesis document explicitly requires
otherwise.

Do not rewrite the thesis automatically.
Instead, create a technical note listing inconsistencies that should later
be corrected in the documentation.

==================================================
3. DEVELOPMENT PHILOSOPHY
==================================================

Build the system as a REAL APPLICATION.

DO NOT:

- create fake API responses
- hardcode database data
- create fake analytics
- create fake booking states
- create fake authentication
- store everything in localStorage
- put all logic into React components
- put SQL queries directly into frontend code
- create one giant Laravel controller
- create one giant React component
- duplicate business logic unnecessarily
- leave placeholder buttons for core functionality
- create UI-only features that do not work
- use mock data after the database has been implemented
- expose sensitive information through APIs
- store passwords in plain text
- trust frontend validation alone

Every important feature must be connected end-to-end:

React UI
    ↓
API
    ↓
Laravel Controller
    ↓
Service / Business Logic
    ↓
Model
    ↓
MySQL
    ↓
Response
    ↓
React UI

==================================================
4. PROFESSIONAL PROJECT STRUCTURE
==================================================

Use a clean monorepo structure:

WEBIS/
│
├── backend/
│   ├── app/
│   │   ├── Console/
│   │   ├── Exceptions/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── Auth/
│   │   │   │   ├── Client/
│   │   │   │   ├── Provider/
│   │   │   │   ├── Admin/
│   │   │   │   ├── Services/
│   │   │   │   ├── Bookings/
│   │   │   │   ├── Payments/
│   │   │   │   ├── Messaging/
│   │   │   │   ├── Locations/
│   │   │   │   └── Analytics/
│   │   │   ├── Middleware/
│   │   │   ├── Requests/
│   │   │   └── Resources/
│   │   ├── Models/
│   │   ├── Policies/
│   │   ├── Services/
│   │   ├── Repositories/
│   │   ├── Notifications/
│   │   ├── Events/
│   │   ├── Listeners/
│   │   └── Rules/
│   │
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   │   ├── factories/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── routes/
│   │   ├── api.php
│   │   ├── web.php
│   │   └── channels.php
│   ├── storage/
│   ├── tests/
│   │   ├── Feature/
│   │   └── Unit/
│   ├── .env.example
│   ├── composer.json
│   └── README.md
│
├── frontend/
│   ├── src/
│   │   ├── assets/
│   │   ├── components/
│   │   │   ├── common/
│   │   │   ├── layout/
│   │   │   ├── forms/
│   │   │   ├── tables/
│   │   │   ├── maps/
│   │   │   ├── chat/
│   │   │   ├── booking/
│   │   │   └── analytics/
│   │   ├── pages/
│   │   │   ├── auth/
│   │   │   ├── client/
│   │   │   ├── provider/
│   │   │   └── admin/
│   │   ├── layouts/
│   │   ├── hooks/
│   │   ├── services/
│   │   │   ├── api/
│   │   │   ├── auth/
│   │   │   ├── bookings/
│   │   │   ├── payments/
│   │   │   ├── messaging/
│   │   │   └── analytics/
│   │   ├── store/
│   │   ├── utils/
│   │   ├── constants/
│   │   ├── routes/
│   │   ├── types/
│   │   ├── App.jsx
│   │   └── main.jsx
│   │
│   ├── public/
│   ├── .env.example
│   ├── package.json
│   └── README.md
│
├── docs/
│   ├── architecture/
│   ├── database/
│   ├── api/
│   ├── deployment/
│   └── testing/
│
├── .gitignore
├── README.md
└── docker-compose.yml

You may improve this structure if a better professional architecture is
justified.

Do not unnecessarily over-engineer the project.

==================================================
5. TECH STACK
==================================================

Required core stack:

Frontend:
- React
- Vite
- JavaScript or TypeScript
- React Router
- Tailwind CSS
- Axios
- modern component architecture

Backend:
- Laravel
- Laravel Sanctum
- RESTful API architecture
- Laravel Validation
- Laravel Policies / Authorization
- Laravel Notifications
- Laravel Events where appropriate

Database:
- MySQL

Maps:
- Leaflet
- OpenStreetMap-compatible map tiles
- Browser Geolocation API where permitted

Charts:
- Recharts or another stable React chart library

Realtime messaging:
- Laravel Reverb / WebSockets if practical

Authentication:
- Laravel Sanctum
- secure session/token handling
- role-based authorization

File storage:
- Laravel Storage
- secure uploaded-file handling

==================================================
6. USER ROLES
==================================================

Implement exactly these primary roles:

CLIENT
SERVICE_PROVIDER
ADMIN

Use role-based authorization throughout the backend.

NEVER rely only on frontend route protection.

Every protected API endpoint must enforce authorization server-side.

==================================================
7. AUTHENTICATION
==================================================

Implement:

- Registration
- Login
- Logout
- Password hashing
- Authentication state
- Session/token management
- Password reset architecture
- Email verification architecture if configured
- Account activation/deactivation
- Role-based access

Security requirements:

- Never store plain passwords.
- Never expose password hashes.
- Validate all input server-side.
- Protect authenticated routes.
- Prevent privilege escalation.
- Prevent users from changing their own role.
- Prevent clients from accessing provider/admin endpoints.
- Prevent providers from accessing admin endpoints.
- Prevent IDOR vulnerabilities.

==================================================
8. SERVICE PROVIDER PROFILE
==================================================

Provider profile must support:

- profile photo
- full name
- bio/about
- skills
- experience
- service categories
- location
- barangay
- contact information where appropriate
- verification status
- rating
- review count
- completed bookings
- availability
- pricing information

Public provider profile should NOT expose unnecessary private information.

Provider verification:

- pending
- approved
- rejected

Only approved providers should be allowed to publish services if that is
consistent with the documented requirements.

==================================================
9. SERVICE MANAGEMENT
==================================================

Admin can manage service categories.

Provider can:

- create service
- edit service
- deactivate service
- define description
- define pricing
- define availability
- define service area
- associate service with category

Client can:

- browse categories
- search services
- view service details
- view provider
- filter
- sort

==================================================
10. BARANGAY / LOCATION SYSTEM
==================================================

Location must be a first-class feature.

Support:

- barangay database
- provider barangay
- client barangay
- service area
- latitude
- longitude
- map coordinates

Clients must be able to search:

SERVICE + BARANGAY

Example:

Plumbing + Amaya 1

Results should prioritize relevant nearby providers.

Filtering examples:

- barangay
- service category
- price
- rating
- availability

Sorting examples:

- nearest
- highest rated
- lowest price
- highest price
- most booked
- newest

==================================================
11. EXACT SERVICE LOCATION MAP
==================================================

This is an IMPORTANT requirement.

When booking a service, the client must be able to specify the exact
location where the service will happen.

Implement:

- interactive map
- draggable/selectable marker
- "Use My Current Location"
- manual pin placement
- latitude
- longitude
- address
- barangay
- location notes

Example:

Client books plumber.

Client selects:

Barangay: Amaya 1

Then:

[ MAP ]

📍 Client Service Location

Location notes:
"Blue gate beside the sari-sari store."

IMPORTANT PRIVACY RULE:

Do NOT publicly expose the client's exact service coordinates.

Before booking:
- show general area/barangay

After booking is accepted:
- authorized provider can access exact service location

Only:
- client
- assigned service provider
- authorized administrators

may access exact coordinates.

==================================================
12. BOOKING SYSTEM
==================================================

Booking must be database-driven.

Suggested states:

PENDING
ACCEPTED
REJECTED
CANCELLED
CONFIRMED
IN_PROGRESS
COMPLETED
DISPUTED

Only allow valid state transitions.

Example:

PENDING
→ ACCEPTED
→ IN_PROGRESS
→ COMPLETED

Do not allow arbitrary status changes.

Implement:

- booking creation
- availability checking
- booking conflict prevention
- provider acceptance/rejection
- cancellation
- status updates
- booking history
- notifications
- service location
- payment association

==================================================
13. INTERNAL MESSAGING / DM SYSTEM
==================================================

Implement a private internal chat system.

Clients can message service providers.

Providers can message clients.

Messages must remain inside WEBIS.

Conversation structure:

Conversation
    ↓
Messages

A conversation may optionally be associated with a booking.

Support:

- text messages
- timestamps
- read/unread state
- message status
- conversation list
- unread count
- search conversations
- pagination
- proper authorization

Use realtime updates if practical.

DO NOT expose conversations between unrelated users.

A user must only access conversations in which they are an authorized participant.

==================================================
14. CHAT CONTENT FILTERING
==================================================

IMPORTANT BUSINESS RULE:

Users must not use WEBIS chat to move transactions outside the platform.

Implement a server-side moderation/filtering system.

This does NOT need AI.

Use deterministic rules / regex / normalization.

Detect attempts to share:

Social platforms:
- Facebook
- FB
- Messenger
- Instagram
- IG
- Viber
- WhatsApp
- Telegram
- TikTok

External communication:
- phone numbers
- email addresses
- external URLs
- social media usernames
- external contact handles

Transaction bypass language:
- "contact me outside"
- "message me on..."
- "call me"
- "text me"
- "add me on..."
- "pay me directly"
- "send payment directly"
- "don't book here"
- "transaction outside"

The filter should normalize text before checking:

- lowercase
- whitespace normalization
- punctuation normalization
- common obfuscation patterns

Example:

"f a c e b o o k"

should be considered suspicious.

Do NOT blindly block legitimate words when context is clearly harmless.

Implement at least:

ALLOWED
WARNING
BLOCKED
FLAGGED

Example:

If clearly prohibited:

Message is NOT stored as a normal sent message.

Show:

"Message not sent.

WEBIS does not allow users to share external contact
information or arrange transactions outside the platform.

Please continue communication and payment through WEBIS."

For suspicious content:

Show a warning and allow editing.

Repeated violations should generate an admin moderation record.

==================================================
15. CHAT VIOLATION LOGGING
==================================================

Create a moderation/violation system.

Store:

- user
- message
- detected category
- detection rule
- action
- timestamp
- severity

Possible categories:

SOCIAL_MEDIA
PHONE_NUMBER
EMAIL
EXTERNAL_URL
OFF_PLATFORM_TRANSACTION
OTHER

Admin can:

- view violations
- inspect flagged conversations
- issue warning
- suspend user
- dismiss violation
- review history

Do not give administrators unrestricted access to private conversations
unless the system's moderation rules justify access.

Log administrative access to sensitive chat data where practical.

==================================================
16. PAYMENT SYSTEM
==================================================

Payment must support QR Ph-compatible workflow.

DO NOT implement fake "successful payment" logic.

Unless a real payment gateway API is explicitly provided, implement a
manual QR-based payment verification workflow.

Provider can configure:

- payment method
- QR code image
- account/display name
- payment instructions

Client:

1. completes booking
2. selects QR payment
3. sees provider QR code
4. scans using supported banking/payment application
5. uploads payment proof
6. enters reference number
7. submits payment

Payment states:

PENDING
PROOF_SUBMITTED
VERIFIED
REJECTED
REFUNDED

Provider/Admin can review payment proof.

Store:

- booking_id
- client_id
- provider_id
- amount
- payment method
- reference number
- proof path
- status
- timestamps
- verifier

SECURITY:

Payment proof uploads must be validated.

Allowed image types should be restricted.

Prevent executable file uploads.

Do not expose raw filesystem paths.

==================================================
17. RATINGS AND REVIEWS
==================================================

Only allow rating after a valid completed service.

Support:

- 1–5 stars
- written review
- rating timestamp

Prevent:

- multiple ratings for same completed booking
- rating before completion
- rating by unrelated users

Display:

- average rating
- total reviews
- rating distribution

==================================================
18. ADMIN ANALYTICS
==================================================

Admin dashboard MUST include actual database-driven analytics.

Do NOT use hardcoded numbers.

Metrics:

- total users
- total clients
- total providers
- verified providers
- pending providers
- total services
- total bookings
- pending bookings
- completed bookings
- cancelled bookings
- total payments
- verified payments

Analytics:

- bookings over time
- bookings by service category
- providers by barangay
- bookings by barangay
- popular services
- top-rated providers
- most-booked providers
- payment summaries
- user growth

Allow useful date filtering:

- today
- this week
- this month
- this year
- custom date range

Analytics queries must be efficient.

Do not load the entire database into React just to calculate statistics.

==================================================
19. ADMIN SERVICE MANAGEMENT
==================================================

Admin should have:

Services
Categories
Barangays

Admin can:

- add
- edit
- activate/deactivate
- search
- filter
- paginate

Do not physically delete important historical records when soft deletion
is more appropriate.

==================================================
20. ADMIN DASHBOARD
==================================================

Create a professional admin dashboard.

Sections:

Dashboard
Users
Clients
Service Providers
Provider Verification
Services
Categories
Barangays
Bookings
Payments
Chat Violations
Reports
Analytics
Settings
Audit Logs

Use tables with:

- search
- filtering
- pagination
- sorting
- status badges
- actions
- confirmation dialogs

==================================================
21. NOTIFICATION SYSTEM
==================================================

Implement in-app notifications for important events.

Examples:

Client:
- booking submitted
- booking accepted
- booking rejected
- provider message
- payment verification
- service started
- service completed
- review reminder

Provider:
- new booking
- client message
- payment proof submitted
- booking cancellation
- new review

Admin:
- provider verification
- flagged chat violation
- payment requiring review
- reports

==================================================
22. DATABASE ARCHITECTURE
==================================================

Design a normalized relational MySQL database.

Potential entities:

users
roles / role relationships if necessary
service_providers
provider_verifications
service_categories
services
barangays
provider_service_areas
availability
bookings
booking_status_history
service_locations
conversations
messages
chat_violations
payments
payment_methods
payment_proofs
ratings
reviews
notifications
reports
audit_logs

Do NOT blindly create every table.

Analyze relationships first.

Use:

- foreign keys
- indexes
- unique constraints
- appropriate nullable fields
- timestamps
- soft deletes where appropriate

Avoid redundant columns.

Document the ERD.

==================================================
23. API ARCHITECTURE
==================================================

Use RESTful API conventions.

Examples:

POST /api/auth/register
POST /api/auth/login
POST /api/auth/logout

GET /api/services
GET /api/services/{id}

GET /api/providers
GET /api/providers/{id}

GET /api/barangays

POST /api/bookings
GET /api/bookings
GET /api/bookings/{id}

POST /api/bookings/{id}/accept
POST /api/bookings/{id}/reject
POST /api/bookings/{id}/cancel
POST /api/bookings/{id}/status

GET /api/conversations
GET /api/conversations/{id}/messages
POST /api/conversations/{id}/messages

POST /api/payments
POST /api/payments/{id}/proof
POST /api/payments/{id}/verify

GET /api/analytics/dashboard

Follow Laravel route/controller/resource conventions.

Use API Resources for consistent response formatting.

==================================================
24. SECURITY
==================================================

Treat security as a first-class requirement.

Implement protection against:

- SQL injection
- XSS
- CSRF where applicable
- IDOR
- mass assignment
- privilege escalation
- insecure file uploads
- path traversal
- brute-force login attempts
- unauthorized API access
- malicious input
- oversized uploads

Use:

- Form Requests
- Policies
- middleware
- authorization
- validation
- rate limiting
- secure password hashing
- Eloquent ORM
- sanitized output
- secure file storage

Never trust:

- role sent from frontend
- price sent by frontend
- booking owner ID sent by frontend
- provider ID sent by frontend
- payment status sent by frontend

The backend must determine authorization and important business values.

==================================================
25. FRONTEND UX
==================================================

The UI must feel like a professional modern marketplace.

Requirements:

- responsive
- mobile friendly
- desktop friendly
- consistent spacing
- clear typography
- accessible forms
- loading states
- empty states
- error states
- success states
- confirmation dialogs
- toast notifications
- skeleton loaders where appropriate

Avoid:

- excessive animations
- cluttered dashboards
- giant forms
- inconsistent buttons
- random colors
- unnecessary gradients
- template-looking UI

Use a consistent design system.

==================================================
26. CLIENT PAGES
==================================================

Create at minimum:

Public:
- Landing Page
- Service Search
- Service Details
- Provider Profile
- Login
- Registration

Authenticated Client:
- Dashboard
- Profile
- Search Services
- Service Details
- Provider Details
- Book Service
- Booking History
- Booking Details
- Service Location Map
- Messages
- Payment
- Payment History
- Notifications
- Reviews/Ratings
- Settings

==================================================
27. PROVIDER PAGES
==================================================

Public:
- Provider Profile

Authenticated:

- Dashboard
- Profile
- Verification
- Services
- Create Service
- Edit Service
- Availability
- Booking Requests
- Booking Details
- Service Location
- Messages
- Payments
- Reviews
- Notifications
- Settings

==================================================
28. ADMIN PAGES
==================================================

- Login
- Dashboard
- Analytics
- Users
- Clients
- Providers
- Provider Verification
- Services
- Categories
- Barangays
- Bookings
- Payments
- Chat Violations
- Reports
- Notifications
- Audit Logs
- Settings

==================================================
29. SEARCH / FILTER SYSTEM
==================================================

Search must be backend-driven.

Support:

keyword
service category
barangay
rating
price range
availability

Sorting:

nearest
highest rated
lowest price
highest price
most booked
newest

Use pagination.

Do not fetch thousands of records into React.

==================================================
30. LOCATION SEARCH
==================================================

When coordinates are available:

calculate distance server-side or through an appropriate geographic query.

Nearest results should be based on actual coordinates.

If coordinates are unavailable:

fall back to barangay filtering.

Never claim exact distance if exact coordinates are not available.

==================================================
31. FILE UPLOADS
==================================================

Support secure uploads for:

- profile pictures
- provider verification documents
- payment proof
- provider QR code

Validate:

- MIME type
- extension
- size
- dimensions where appropriate

Store files safely.

Never allow uploaded files to execute as server-side code.

==================================================
32. AUDIT LOGGING
==================================================

Important administrative actions should be logged.

Examples:

- provider approved
- provider rejected
- user suspended
- payment verified
- payment rejected
- service category changed
- account changes
- moderation actions

Store:

- actor
- action
- target
- metadata where appropriate
- timestamp
- IP where appropriate

==================================================
33. ERROR HANDLING
==================================================

Create consistent API errors.

Example:

{
  "success": false,
  "message": "Unable to process booking.",
  "errors": {}
}

Frontend must display useful human-readable errors.

Never expose:

- stack traces
- SQL queries
- secrets
- filesystem paths
- internal credentials

in production responses.

==================================================
34. TESTING
==================================================

Do not consider the project complete merely because pages load.

Implement tests for critical functionality.

Backend:

- authentication
- authorization
- service creation
- booking
- booking status transitions
- messaging authorization
- chat filtering
- payment submission
- payment verification
- rating restrictions
- admin permissions

Frontend:

- critical form validation
- route protection
- important user flows

Create realistic test cases.

==================================================
35. SEED DATA
==================================================

Create development seeders.

Include:

- admin account
- sample clients
- sample providers
- service categories
- barangays
- services
- bookings
- ratings
- payment records
- conversations

Clearly label seed/demo accounts.

Do NOT hardcode these records into frontend components.

==================================================
36. ENVIRONMENT CONFIGURATION
==================================================

Use .env files.

Provide:

.env.example

Never commit:

- passwords
- API keys
- database credentials
- secret tokens
- production credentials

Document setup clearly.

==================================================
37. DOCUMENTATION
==================================================

Create professional documentation:

README.md

Include:

- project overview
- features
- architecture
- requirements
- installation
- environment setup
- database setup
- migration
- seeding
- frontend setup
- backend setup
- development commands
- testing
- build
- deployment
- security notes

Also create:

docs/database/ERD.md
docs/api/API.md
docs/architecture/ARCHITECTURE.md
docs/deployment/DEPLOYMENT.md
docs/testing/TESTING.md

==================================================
38. DEVELOPMENT PROCESS
==================================================

DO NOT attempt to blindly generate the entire application in one giant
response.

Work in controlled phases.

PHASE 0 — REQUIREMENTS AUDIT

First inspect the thesis and create:

1. Requirements Matrix
2. Actor Matrix
3. Feature Matrix
4. Database Entity List
5. API Module List
6. Identified Thesis Contradictions
7. Proposed Final Architecture
8. Implementation Roadmap

STOP after Phase 0 and present the plan.

Do NOT start generating hundreds of files until the architecture is clear.

==================================================

PHASE 1 — PROJECT FOUNDATION

Implement:

- Laravel project
- React/Vite project
- database connection
- Sanctum
- routing
- base layouts
- authentication foundation
- role authorization
- error handling
- API conventions
- base UI system

Test foundation.

==================================================

PHASE 2 — DATABASE

Implement:

- migrations
- models
- relationships
- factories
- seeders
- indexes
- constraints

Then verify database integrity.

==================================================

PHASE 3 — AUTHENTICATION + USERS

Implement complete authentication.

Then test:

CLIENT
PROVIDER
ADMIN

==================================================

PHASE 4 — SERVICES + PROVIDERS

Implement:

- provider profile
- verification
- categories
- services
- search
- filtering
- sorting
- barangays

==================================================

PHASE 5 — BOOKING + LOCATION

Implement:

- booking
- availability
- status workflow
- service location
- map
- coordinates
- privacy restrictions

==================================================

PHASE 6 — MESSAGING

Implement:

- conversations
- messages
- unread counts
- realtime/polling
- authorization
- moderation filter
- violation logging

==================================================

PHASE 7 — PAYMENTS

Implement:

- QR payment method
- provider QR
- payment submission
- proof upload
- verification
- payment history

==================================================

PHASE 8 — RATINGS

Implement:

- ratings
- reviews
- restrictions
- provider rating statistics

==================================================

PHASE 9 — ADMIN

Implement:

- dashboard
- analytics
- users
- providers
- verification
- services
- barangays
- bookings
- payments
- violations
- reports
- audit logs

==================================================

PHASE 10 — POLISH

Implement:

- loading states
- empty states
- error states
- responsive layout
- accessibility improvements
- performance optimization
- security hardening
- UI consistency

==================================================

PHASE 11 — TESTING

Run:

- backend tests
- frontend tests
- API tests
- authorization tests
- critical workflow tests

Fix discovered issues.

==================================================

PHASE 12 — FINAL AUDIT

Perform a full production-readiness audit.

Check:

[ ] no fake core functionality
[ ] no hardcoded analytics
[ ] no fake authentication
[ ] all buttons work
[ ] all forms validate
[ ] all protected routes are protected
[ ] role authorization works
[ ] database relationships work
[ ] booking lifecycle works
[ ] payment workflow works
[ ] chat works
[ ] chat filtering works
[ ] violations are logged
[ ] exact location privacy works
[ ] map works
[ ] search works
[ ] filtering works
[ ] sorting works
[ ] ratings work
[ ] admin analytics work
[ ] responsive UI works
[ ] error handling works
[ ] documentation is complete
[ ] no secrets committed
[ ] no unnecessary duplicate code
[ ] no obvious security vulnerabilities

==================================================
39. CODING STANDARDS
==================================================

Write code as if another professional developer will maintain it.

Use:

- descriptive names
- single responsibility
- reusable components
- service classes for business logic
- Form Requests for validation
- Policies for authorization
- API Resources
- database transactions where appropriate
- meaningful comments only when needed

Avoid:

- spaghetti code
- huge functions
- magic numbers
- duplicated logic
- unnecessary abstractions
- premature optimization

==================================================
40. IMPORTANT BEHAVIOR
==================================================

If you encounter a requirement that is ambiguous:

DO NOT silently guess.

Instead:

1. identify the ambiguity
2. explain the possible interpretations
3. recommend the most practical interpretation
4. continue with the implementation only when reasonable

If a feature is technically expensive but a simpler implementation satisfies
the thesis requirement, choose the simpler reliable implementation.

Example:

For QR payment:

Do not create a fake payment gateway.

Use QR + payment proof verification unless real gateway credentials/API
requirements are provided.

For chat moderation:

Do not unnecessarily introduce AI.

Use deterministic moderation rules unless AI moderation is explicitly
required.

For maps:

Do not implement live driver tracking unless explicitly required.

Use service-location pinning and location-based discovery.

==================================================
41. FINAL QUALITY STANDARD
==================================================

The final application should feel like:

A real local service marketplace.

NOT:

- a school demo
- a static HTML website
- a UI mockup
- a CRUD-only application

The system must have coherent workflows.

Example complete client flow:

Register
→ Login
→ Search "Plumber"
→ Select Barangay
→ Sort by nearest
→ View Provider
→ Message Provider
→ Book Service
→ Pin Exact Location
→ Provider Accepts
→ Client Pays using QR
→ Upload Payment Proof
→ Payment Verified
→ Service In Progress
→ Service Completed
→ Client Rates Provider

Provider flow:

Register
→ Verification
→ Approved
→ Create Services
→ Set Availability
→ Receive Booking
→ Message Client
→ Accept Booking
→ View Authorized Exact Location
→ Confirm Payment
→ Perform Service
→ Mark Completed
→ Receive Rating

Admin flow:

Login
→ Dashboard
→ Analytics
→ Verify Providers
→ Manage Services
→ Monitor Bookings
→ Verify Payments
→ Review Chat Violations
→ Manage Users
→ View Reports
→ Audit Activity

==================================================
42. START NOW
==================================================

IMPORTANT:

Do NOT immediately generate the complete source code.

Start with PHASE 0.

First:

1. Analyze the attached thesis document completely.
2. Extract requirements.
3. Identify contradictions.
4. Create the Requirements Matrix.
5. Create the final module list.
6. Create the database ERD proposal.
7. Create the API architecture.
8. Create the folder architecture.
9. Create the implementation roadmap.
10. Identify any requirements that need clarification.

Then STOP and wait for approval before beginning Phase 1.

Your goal is not merely to produce code.

Your goal is to deliver a professionally engineered, secure,
maintainable, fully functional WEBIS system.