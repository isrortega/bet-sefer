# 08 — Infrastructure

## Environments

| | Dev | Production (evaluation) |
|---|---|---|
| Compose file | `docker-compose.yml` | `docker-compose.prod.yml` |
| Dockerfile | `docker/dev/Dockerfile` | `docker/prod/Dockerfile` |
| Host | localhost | Hetzner VPS |
| URL | `http://localhost` | `https://betsefer.appenlaweb.com` |
| Storage | MinIO | Cloudflare R2 |
| Mail | Mailpit | Brevo SMTP |
| TLS | none | Caddy, automatic |

## Dev stack

Services: `app` (PHP 8.4-FPM), `web` (Nginx), `postgres:17`, `redis:7`,
`queue` (worker), `scheduler`, `mailpit`, `minio`, `vite`.

- Mailpit UI on `:8025`. **Do not publish port 1025 to the host** — SMTP stays
  on the internal Docker network.
- MinIO console on `:9001`, bucket `bet-sefer` created by an init container.
- Source mounted as a volume; Vite HMR enabled.

A `Makefile` wraps everything: `up`, `down`, `fresh`, `test`, `check`, `shell`,
`logs`.

## Production image

Multi-stage:

1. `composer:2` — `composer install --no-dev --optimize-autoloader`
2. `node:22` — `npm ci && npm run build`
3. `php:8.4-fpm-alpine` — copy vendor and built assets, install `pdo_pgsql`,
   `redis`, `intl`, `gd`, `opcache`, `zip`

Then:
- OPcache enabled with `validate_timestamps=0` and preloading.
- `php artisan config:cache route:cache view:cache event:cache` at build time.
- Runs as a non-root user.
- `HEALTHCHECK` hitting `/up`.
- No dev dependencies, no source maps, no `.env` inside the image.

Runtime services in prod: `app`, `web`, `postgres`, `redis`, `queue`,
`scheduler`, `caddy`. **No Mailpit, no MinIO, no Vite.**

## Environment variables

```dotenv
APP_NAME="Bet-Sefer"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://betsefer.appenlaweb.com
APP_TIMEZONE=America/Bogota
APP_LOCALE=en
APP_FALLBACK_LOCALE=en

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=betsefer
DB_USERNAME=
DB_PASSWORD=

REDIS_HOST=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=database

FILESYSTEM_DISK=r2
R2_ACCESS_KEY_ID=
R2_SECRET_ACCESS_KEY=
R2_BUCKET=bet-sefer
R2_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
R2_URL=

MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=          # the 7xxxxxx@smtp-brevo.com string, NOT the account email
MAIL_PASSWORD=          # Brevo SMTP key
MAIL_FROM_ADDRESS=no-reply@appenlaweb.com   # must be a verified sender in Brevo
MAIL_FROM_NAME="Bet-Sefer"

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://betsefer.appenlaweb.com/auth/google/callback

GOOGLE_BOOKS_API_KEY=   # optional, raises the quota
OPEN_LIBRARY_TIMEOUT=3
GOOGLE_BOOKS_TIMEOUT=3
METADATA_TOTAL_BUDGET=8

ANTHROPIC_API_KEY=
AI_CLASSIFICATION_ENABLED=true
```

R2 uses the S3 driver. Brevo notes: the SMTP username is the
`7xxxxxx@smtp-brevo.com` string from the dashboard, not the account email, and
the `From` address must exactly match a verified sender or Brevo rejects the
message. Free tier is 300 mails/day. All mail is **queued**; if the mailer
fails, no user flow breaks.

## Branching

`main` is the deployment branch. Nothing else deploys, ever.

- Work happens on `develop` and short-lived feature branches.
- `main` is written to only by merges from `develop`.
- A push to `main` is the sole signal that something should go live. There is
  no manual deploy path, no `workflow_dispatch`, and no way to ship from a
  feature branch. If it is not on `main`, it is not in production.

Protect `main` on GitHub: require the `ci` workflow to pass, disallow force
pushes.

## CI/CD — GitHub Actions

**`ci.yml`** — runs on every push to any branch and on every pull request:
1. checkout, PHP 8.4, Node 22, cache composer and npm
2. `composer install`, `npm ci`
3. `pint --test`
4. `larastan`
5. `pest` against a Postgres service container
6. `composer audit`, `npm audit --audit-level=high`

**`deploy.yml`** — runs **only** on push to `main`:

```yaml
on:
  push:
    branches: [main]

concurrency:
  group: deploy-production
  cancel-in-progress: false
```

The trigger is exactly this. Do not add `workflow_dispatch`, do not add other
branches, do not add tag triggers.

Steps, after `ci` has passed on the same commit:
1. build the production image, tag with the commit SHA and with `latest`
2. push to GHCR
3. SSH to the Hetzner host
4. `docker compose -f docker-compose.prod.yml pull && up -d`
5. `php artisan migrate --force`
6. `php artisan optimize`
7. smoke test: `curl -f https://betsefer.appenlaweb.com/up`
8. on failure, redeploy the previously running SHA tag and fail the job

The `concurrency` group with `cancel-in-progress: false` matters: two merges
landing close together must not run migrations against the same database at the
same time. The second deploy queues behind the first.

Rollback is automatic inside step 8. Because there is no manual trigger, a
rollback beyond that is a revert commit on `main` — which is the correct,
auditable path anyway.

Secrets: `SSH_HOST`, `SSH_USER`, `SSH_KEY`, `GHCR_TOKEN`, plus every runtime
variable above.

## Host setup (once)

- DNS `A` record for `betsefer.appenlaweb.com` → VPS IP. Do this first; TLS
  cannot be issued until it resolves.
- Docker and Compose plugin installed.
- Firewall: 22, 80, 443 only.
- Caddyfile reverse-proxying to the `web` container, automatic Let's Encrypt.
- A nightly `pg_dump` to R2, retained 7 days.

## Deployment checklist

- [ ] DNS resolves
- [ ] TLS certificate issued
- [ ] `APP_DEBUG=false` confirmed in the running container
- [ ] Migrations and seeders ran
- [ ] The four demo accounts can log in
- [ ] Google SSO round-trips
- [ ] A QR code on a printed label opens the public page
- [ ] A test mail arrives through Brevo
- [ ] `/up` returns 200
