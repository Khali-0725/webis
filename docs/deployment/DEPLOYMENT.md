# Deploying WEBIS for free (testing phase)

Stack: **TiDB Cloud Starter** (MySQL-compatible, always-free, AWS
Singapore) + **Render** (Laravel API, free web service, Singapore) +
**Vercel** (React SPA, free static hosting). Total cost: ₱0.

## Why this exact stack, and the one non-obvious trick

The frontend (`*.vercel.app`) and backend (`*.onrender.com`) live on
different domains. WEBIS's auth is Sanctum's cookie-based SPA session
(`backend/CLAUDE.md`'s own hard rule: CSRF protection is never disabled for
`api/*`). A session cookie set by one domain and read by a completely
different domain is a **third-party cookie** from the browser's point of
view — Safari blocks these by default, and Chrome is moving the same
direction. If the frontend called the Render URL directly, login would
silently fail for a growing share of visitors.

**Fix**: Vercel's `rewrites` (already added at `frontend/vercel.json`)
proxy `/api/*` and `/sanctum/*` requests through Vercel's own edge to the
Render backend, server-side. The browser only ever talks to the
`vercel.app` origin — it never learns the API lives elsewhere — so the
session cookie is a normal first-party cookie again. Zero Sanctum/CORS
architecture changes needed, just this one proxy layer.

## File storage — Backblaze B2 (S3-compatible), not local disk

Render's free tier has no persistent disk, so uploaded files (avatars,
verification documents, payment proofs, payment-method QR codes) are
**not** written to the container's local filesystem — they'd be lost on
every redeploy or restart. Instead, `WEBIS_UPLOAD_DISK=s3` points Laravel's
`s3`-driver disk (`config/filesystems.php`) at a free Backblaze B2 bucket
using B2's S3-compatible API, so uploads durably survive redeploys. See
the `AWS_*` variables in the Render environment table below — the
`league/flysystem-aws-s3-v3` Composer package (already in
`backend/composer.json`) is what makes Laravel's generic `s3` disk driver
work against a non-AWS S3-compatible endpoint like B2's.

A second, unrelated thing this enables: `ProviderPaymentMethodResource`,
`PaymentResource`, `UserResource`, `ConversationResource`, and
`MessageResource` all build their file URLs as a **relative**
`/api/files/...` path rather than an absolute one — this is what lets the
browser resolve them against whatever origin actually served the page
(Vercel's rewrite proxy in production, the Vite dev proxy locally) instead
of hitting Render directly and losing the session cookie. This is
independent of which disk driver serves the bytes underneath; don't
special-case it when adding a new file-serving route.

---

## 1. Database — TiDB Cloud Starter (MySQL-compatible)

**Put the database in the same region as the Render service.** The
backend was originally on Aiven's free MySQL, which only offers a
"geographical area" on the free plan (Asia Pacific = DigitalOcean
Bangalore) — no Singapore. With Render in Singapore, every query paid a
Singapore↔Bangalore round trip plus a TLS handshake, and `/api/services`
sat at ~1s even when warm. Moving to TiDB Cloud Starter in AWS Singapore
(2026-09-13) roughly halved that with zero code changes.

1. Sign up at [tidbcloud.com](https://tidbcloud.com) (Google/GitHub login,
   no credit card).
2. **My TiDB → Create Resource** → plan **Starter** (the default is the
   paid Essential — change it) → cloud AWS → region **Singapore
   (ap-southeast-1)** → Create. Free quota: 5 GiB storage, 50M request
   units/month, 5 instances per org.
3. In the **SQL Editor**, run
   `CREATE DATABASE defaultdb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
   (the name only has to match `DB_DATABASE` on Render).
4. **Overview → Connect** → **Generate Password** (shown once, save it).
   Host is `gateway01.ap-southeast-1.prod.aws.tidbcloud.com`, port `4000`,
   user `<prefix>.root`.
5. No CA file to ship: TiDB Cloud's public endpoint uses a public CA, so
   `MYSQL_ATTR_SSL_CA` points at the Debian system bundle already inside
   the `php:8.3-cli` image (see the table below). The old
   `backend/storage/certs/aiven-ca.pem` is unused.

TiDB compatibility notes for this codebase: keyword search is `LIKE`, not
`MATCH ... AGAINST`, so the `FULLTEXT` index on `services` is never used —
strip its `FULLTEXT KEY` line from any mysqldump before importing (TiDB
Starter rejects it). No triggers, stored procedures, or updatable views
anywhere. Everything else (enum, json, foreign keys, `ONLY_FULL_GROUP_BY`
strict mode, Haversine math in `ServiceController`) works unchanged.

Importing an existing dump from Windows: run `mysql.exe` with
`--ssl-mode=REQUIRED -D defaultdb -e "source C:/path/dump.sql"`. It will
fail on the very last cleanup line (`SET CHARACTER_SET_CLIENT=@OLD...`,
"Unsupported charset cp850" — the Windows console's default) *after* all
tables and rows are already in; that error is harmless.

## 2. Backend — Render (Web Service, Docker)

1. Push this repo to GitHub if it isn't already (Render deploys from a
   git repo).
2. On [render.com](https://render.com), **New → Web Service**, connect
   the repo, set **Root Directory** to `backend`, **Runtime** to
   **Docker** (it will pick up `backend/Dockerfile` automatically).
3. Under **Environment**, add these variables (values from TiDB Cloud for
   the `DB_*` ones):

   | Key | Value |
   |---|---|
   | `APP_NAME` | `WEBIS` |
   | `APP_ENV` | `production` |
   | `APP_DEBUG` | `false` |
   | `APP_KEY` | *(generate locally: `php artisan key:generate --show`, paste the output)* |
   | `APP_URL` | `https://<your-render-service>.onrender.com` |
   | `FRONTEND_URL` | `https://<your-vercel-app>.vercel.app` |
   | `SANCTUM_STATEFUL_DOMAINS` | `<your-vercel-app>.vercel.app` |
   | `CORS_ALLOWED_ORIGINS` | `https://<your-vercel-app>.vercel.app` |
   | `SESSION_DRIVER` | `database` |
   | `SESSION_DOMAIN` | *(leave unset)* |
   | `SESSION_SAME_SITE` | `none` |
   | `SESSION_SECURE_COOKIE` | `true` |
   | `DB_CONNECTION` | `mysql` |
   | `DB_HOST` | `gateway01.ap-southeast-1.prod.aws.tidbcloud.com` |
   | `DB_PORT` | `4000` |
   | `DB_DATABASE` | `defaultdb` |
   | `DB_USERNAME` | *(from TiDB Cloud, `<prefix>.root`)* |
   | `DB_PASSWORD` | *(from TiDB Cloud, generated once)* |
   | `MYSQL_ATTR_SSL_CA` | `/etc/ssl/certs/ca-certificates.crt` |
   | `BROADCAST_CONNECTION` | `pusher` |
   | `PUSHER_APP_ID` | *(Pusher Channels → App Keys)* |
   | `PUSHER_APP_KEY` | *(Pusher Channels → App Keys)* |
   | `PUSHER_APP_SECRET` | *(Pusher Channels → App Keys)* |
   | `PUSHER_APP_CLUSTER` | `ap1` |
   | `MAIL_MAILER` | `smtp` |
   | `MAIL_HOST` | `smtp.gmail.com` |
   | `MAIL_PORT` | `587` |
   | `MAIL_USERNAME` | *(your Gmail address)* |
   | `MAIL_PASSWORD` | *(your Gmail App Password — see below)* |
   | `MAIL_FROM_ADDRESS` | *(same Gmail address)* |
   | `WEBIS_UPLOAD_DISK` | `s3` |
   | `FILESYSTEM_DISK` | `s3` |
   | `AWS_ACCESS_KEY_ID` | *(Backblaze B2 application key ID)* |
   | `AWS_SECRET_ACCESS_KEY` | *(Backblaze B2 application key)* |
   | `AWS_DEFAULT_REGION` | *(B2 region code, e.g. `us-west-002` — shown on the bucket's page)* |
   | `AWS_BUCKET` | *(your B2 bucket name)* |
   | `AWS_ENDPOINT` | *(B2's S3-compatible endpoint, e.g. `https://s3.us-west-002.backblazeb2.com`)* |
   | `AWS_USE_PATH_STYLE_ENDPOINT` | `true` |

   `SESSION_SAME_SITE=none` + `SESSION_SECURE_COOKIE=true` is required for
   cross-site cookies at all (even proxied through Vercel, Render itself
   is still a separate origin that sets the cookie) — without both,
   browsers drop the session cookie silently and every request looks
   logged-out.

4. Deploy. Render builds the Docker image and runs the container's `CMD`,
   which runs migrations, links storage, and starts `php artisan serve`
   bound to Render's `$PORT` automatically — no extra Start Command
   needed.
5. First deploy will be slow (Docker build + Composer install). Watch the
   logs for `Migrating:` lines to confirm the database connected.
6. Free tier detail: the service sleeps after ~15 minutes idle; the next
   request takes 30-50s to wake it up. Mitigated two ways so it stays warm
   in practice: `.github/workflows/keep-alive.yml` pings
   `/api/health` on a `*/10 * * * *` schedule (GitHub's scheduler is
   best-effort and can drift to every 1-2 hours on a quiet repo, so treat
   this as a bonus, not the fix), and a free **UptimeRobot** HTTP monitor
   pinging the same `/api/health` URL every 5 minutes, which is the one
   that actually keeps the 15-minute idle window from ever being reached.
   Set one up at [uptimerobot.com](https://uptimerobot.com) if it isn't
   already configured. **The monitor must target the Render backend URL
   (`https://<service>.onrender.com/api/health`), not the Vercel frontend.**
   A monitor on `*.vercel.app` always reports 100% up (Vercel never sleeps)
   while doing nothing to keep Render awake — that exact misconfiguration
   is why the backend kept cold-starting on 2026-09-13.

## 3. Frontend — Vercel

1. On [vercel.com](https://vercel.com), **Add New → Project**, import the
   repo, set **Root Directory** to `frontend`.
2. Framework preset: Vite. Build command `npm run build`, output
   directory `dist` (Vercel usually detects these automatically).
3. Before the first deploy, edit `frontend/vercel.json` in the repo and
   replace both
   `REPLACE-WITH-RENDER-BACKEND-URL.onrender.com` placeholders with your
   actual Render service URL from step 2 above, then commit and push.
4. Environment variables:

   | Key | Value |
   |---|---|
   | `VITE_API_URL` | *(leave the value completely empty)* |
   | `VITE_PUSHER_APP_KEY` | *(same `key` as on Render — public, it ships in the JS bundle)* |
   | `VITE_PUSHER_APP_CLUSTER` | `ap1` |

   The empty `VITE_API_URL` is deliberate — see
   `frontend/src/services/api/client.js`'s handling of an explicitly-empty
   value: it means "call the current origin," which is what makes the
   Vercel rewrite proxy trick above work. Setting it to the Render URL
   directly would defeat the whole point and bring back the
   third-party-cookie problem. `VITE_*` values are baked in at build time,
   so changing them needs a redeploy.

## 3b. Realtime — Pusher Channels

Messages, booking status changes and payment updates reach the other
party instantly instead of on the next poll. Added 2026-09-13; supersedes
the audit doc's Q-3 "polling only" decision (the original objection was
having to run a WebSocket daemon — a hosted broker removes that).

- **Why Pusher, not Reverb:** Render's free web service runs a single
  `php artisan serve` process with no room for a second long-running
  daemon; a separate free Reverb service would sleep like the API does,
  and Vercel's rewrite proxy cannot forward WebSockets anyway. Pusher's
  free Sandbox plan (200k messages/day, 100 concurrent connections, no
  card) with the `ap1` Singapore cluster is more than this project uses.
- **Setup:** [pusher.com](https://pusher.com) → Channels → Create app →
  cluster `ap1` → App Keys. Put `app_id`/`key`/`secret`/`cluster` on
  Render (table above) and `key`/`cluster` on Vercel. Without the key the
  app silently falls back to polling on both ends — see
  `frontend/src/services/realtime/echo.js` (`realtimeEnabled`,
  `POLL_FAST`/`POLL_SLOW`).
- **How it works:** services call `App\Support\Realtime::push()` *after*
  their DB transaction commits, which broadcasts one generic
  `UserDataChanged` event (`data.changed`) to each affected user's
  `private-App.Models.User.{id}` channel. The payload names only a scope
  (`messages`/`bookings`/`payments`) and an id — never the data. The SPA
  (`useRealtimeSync`) maps the scope to React Query keys and invalidates
  them, so the refetch goes through the normal policy-guarded API.
  `ShouldBroadcastNow` because there is no queue worker on Render; a
  failed push is logged at warning level and never fails the request.
- **Channel auth** is `POST /api/broadcasting/auth` (note the `api`
  prefix — the Vercel proxy only forwards `/api/*`), through the same
  Sanctum cookie session, using the shared axios instance as Echo's
  authorizer so the CSRF header rides along. `routes/channels.php` allows
  a user onto their own channel only.

5. Deploy. Vercel gives you a `https://<something>.vercel.app` URL —
   this is the `FRONTEND_URL`/`SANCTUM_STATEFUL_DOMAINS`/
   `CORS_ALLOWED_ORIGINS` value you set on Render in step 2. If Render was
   configured before Vercel's URL was known, go back and update those
   three Render env vars now, then redeploy Render.

## 4. Smoke test

1. Open the Vercel URL, register a new client account.
2. Confirm the CSRF cookie dance works: open DevTools → Application →
   Cookies, and confirm a cookie is set **against the `vercel.app`
   domain**, not `onrender.com` — that's the proxy working.
3. Log in, and confirm the session survives a page refresh.
4. Try the forgot-password flow end-to-end (real email, same Gmail SMTP
   setup already used in local dev).

## Troubleshooting

- **Login "succeeds" but every subsequent request is 401** — almost
  always `SESSION_SAME_SITE`/`SESSION_SECURE_COOKIE` not set correctly on
  Render, or `vercel.json`'s rewrite destination still has the placeholder
  URL in it.
- **500 error mentioning SSL on every DB query** — `MYSQL_ATTR_SSL_CA`
  must be `/etc/ssl/certs/ca-certificates.crt` (the system bundle in the
  `php:8.3-cli` image); TiDB Cloud rejects non-TLS connections outright.
- **419 "Page expired" on every POST** — `SANCTUM_STATEFUL_DOMAINS` on
  Render doesn't exactly match the Vercel domain (no `https://`, no
  trailing slash — just the bare host).
