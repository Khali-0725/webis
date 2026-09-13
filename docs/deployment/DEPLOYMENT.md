# Deploying WEBIS for free (testing phase)

Stack: **Aiven** (MySQL, always-free) + **Render** (Laravel API, free web
service) + **Vercel** (React SPA, free static hosting). Total cost: ₱0.

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

## 1. Database — Aiven (MySQL)

1. Sign up at [aiven.io](https://aiven.io) (no credit card needed for the
   free plan).
2. Create a new service → **MySQL** → free plan → pick any region close to
   you.
3. Once it's provisioned, open the service's **Overview** tab and copy:
   - Host, Port, Database name (`defaultdb`), User, Password
   - The **CA Certificate** (download it as `ca.pem`)
4. Save `ca.pem` into this repo at `backend/storage/certs/aiven-ca.pem`
   (create the folder). The Dockerfile already copies the whole app
   directory into the image, so it ships with the container automatically.
   Don't commit real database credentials anywhere — only this
   certificate file, which isn't a secret.

## 2. Backend — Render (Web Service, Docker)

1. Push this repo to GitHub if it isn't already (Render deploys from a
   git repo).
2. On [render.com](https://render.com), **New → Web Service**, connect
   the repo, set **Root Directory** to `backend`, **Runtime** to
   **Docker** (it will pick up `backend/Dockerfile` automatically).
3. Under **Environment**, add these variables (values from Aiven for the
   `DB_*` ones):

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
   | `DB_HOST` | *(from Aiven)* |
   | `DB_PORT` | *(from Aiven, usually not 3306)* |
   | `DB_DATABASE` | `defaultdb` |
   | `DB_USERNAME` | *(from Aiven)* |
   | `DB_PASSWORD` | *(from Aiven)* |
   | `MYSQL_ATTR_SSL_CA` | `/app/storage/certs/aiven-ca.pem` |
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
   already configured.

## 3. Frontend — Vercel

1. On [vercel.com](https://vercel.com), **Add New → Project**, import the
   repo, set **Root Directory** to `frontend`.
2. Framework preset: Vite. Build command `npm run build`, output
   directory `dist` (Vercel usually detects these automatically).
3. Before the first deploy, edit `frontend/vercel.json` in the repo and
   replace both
   `REPLACE-WITH-RENDER-BACKEND-URL.onrender.com` placeholders with your
   actual Render service URL from step 2 above, then commit and push.
4. Environment variable:

   | Key | Value |
   |---|---|
   | `VITE_API_URL` | *(leave the value completely empty)* |

   This is deliberate — see `frontend/src/services/api/client.js`'s
   handling of an explicitly-empty `VITE_API_URL`: it means "call the
   current origin," which is what makes the Vercel rewrite proxy trick
   above work. Setting it to the Render URL directly would defeat the
   whole point and bring back the third-party-cookie problem.

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
  path wrong, or `aiven-ca.pem` wasn't actually committed/copied into the
  image. Check the Dockerfile build logs for the file.
- **419 "Page expired" on every POST** — `SANCTUM_STATEFUL_DOMAINS` on
  Render doesn't exactly match the Vercel domain (no `https://`, no
  trailing slash — just the bare host).
