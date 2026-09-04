# 08 — Infrastructure

## Environments

| | Dev | Production (evaluation) |
|---|---|---|
| Compose file | `docker-compose.yml` | `docker-compose.prod.yml` |
| Dockerfile | `docker/dev/Dockerfile` | `docker/prod/Dockerfile` |
| Host | localhost | Hetzner VPS |
| URL | `http://betsefer.local` / `https://betsefer.local` | `https://betsefer.appenlaweb.com` |
| Storage | Local disk (`storage/app/public`) | Local disk for the MVP; R2 in a later phase |
| Mail | Mailpit | Brevo SMTP |
| Proxy / TLS | Traefik (external, shared) | Traefik (external, shared) |

Traefik runs **outside** this stack — it is an already-running service on the
host (local machine and VPS). The app never ships its own proxy. Both
environments attach the `web` container to the shared external Docker network
`traefik_net` and let Traefik route to it. This is why nothing in the app
publishes ports 80/443 to the host.

## Dev stack

Services: `app` (PHP 8.4-FPM), `web` (Nginx), `postgres:17`, `redis:7`,
`queue` (worker), `scheduler`, `mailpit`, `vite`.

- All of them share an internal network (`betsefer_internal`). `web` is also
  attached to the external `traefik_net`.
- Mailpit UI on host `:8025`. **Do not publish port 1025 to the host** — SMTP
  stays on the internal Docker network.
- Source mounted as a volume; Vite HMR enabled.
- The Docker network `traefik_net` is created by the Traefik stack; this
  project declares it as `external: true` and never creates it.
- `betsefer.local` must already resolve to the host (local DNS / wildcard).

### Traefik labels (dev and prod use the same shape)

```yaml
labels:
  - "traefik.enable=true"
  - "traefik.docker.network=traefik_net"
  - "traefik.http.routers.betsefer.rule=Host(`betsefer.local`)"
  - "traefik.http.routers.betsefer.entrypoints=websecure"
  - "traefik.http.routers.betsefer.tls=true"
  - "traefik.http.services.betsefer.loadbalancer.server.port=80"
```

Dev additionally routes a plain-HTTP router on the `web` entrypoint so daily
work does not trip on the self-signed `websecure` certificate:

```yaml
  - "traefik.http.routers.betsefer-http.rule=Host(`betsefer.local`)"
  - "traefik.http.routers.betsefer-http.entrypoints=web"
```

Because Traefik terminates TLS, Laravel must trust the proxy for
`X-Forwarded-*`. `TRUSTED_PROXIES` is set to `*` in the containers (they are
only reachable through the shared Traefik network).

## Production image

Multi-stage:

1. `composer:2` — `composer install --no-dev --optimize-autoloader`
2. `node:22` — `npm ci && npm run build`
3. `php:8.4-fpm-alpine` — copy vendor and built assets, install `pdo_pgsql`,
   `redis`, `intl`, `gd`, `opcache`, `zip`

Then:
- OPcache enabled with `validate_timestamps=0` (see `docker/prod/php/opcache.ini`).
- The optimized autoloader and package discovery are regenerated in the image.
- Runtime caches (`php artisan optimize`) are built **at deploy time**, once the
  real `.env` exists — not at build time.
- Runs as a non-root user (`www-data`).
- No dev dependencies, no source maps, no `.env` inside the image.

Runtime services in prod: `app`, `web`, `postgres`, `redis`, `queue`,
`scheduler`. **No Mailpit, no Vite.** TLS is provided by the host Traefik.

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

TRUSTED_PROXIES=*

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

FILESYSTEM_DISK=public        # MVP: local disk. R2 in a later phase.
# R2 (later phase)
# R2_ACCESS_KEY_ID=
# R2_SECRET_ACCESS_KEY=
# R2_BUCKET=bet-sefer
# R2_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
# R2_URL=

MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=          # the 7xxxxxx@smtp-brevo.com string, NOT the account email
MAIL_PASSWORD=          # Brevo SMTP key
MAIL_FROM_ADDRESS=no-reply@appenlaweb.com   # must be a verified sender in Brevo
MAIL_FROM_NAME="Bet-Sefer"

# Google SSO is OPTIONAL. Email+password always works. Leave blank to hide SSO.
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://betsefer.appenlaweb.com/auth/google/callback

OPENROUTER_API_KEY=       # AI classification; blank = feature disabled
AI_MODEL=deepseek/deepseek-v4-flash
AI_TIMEOUT=6
AI_CLASSIFICATION_ENABLED=true

GOOGLE_BOOKS_API_KEY=     # optional, raises the quota
OPEN_LIBRARY_TIMEOUT=3
GOOGLE_BOOKS_TIMEOUT=3
METADATA_TOTAL_BUDGET=8
```

Brevo notes: the SMTP username is the `7xxxxxx@smtp-brevo.com` string from the
dashboard, not the account email, and the `From` address must exactly match a
verified sender or Brevo rejects the message. Free tier is 300 mails/day. All
mail is **queued**; if the mailer fails, no user flow breaks.

## Branching

`main` is the deployment branch. Nothing else deploys, ever.

- Work happens on `develop` and short-lived feature branches.
- `main` is written to only by merges from `develop`.
- A push to `main` is the sole signal that something should go live. There is
  no manual deploy path, no `workflow_dispatch`, and no way to ship from a
  feature branch. If it is not on `main`, it is not in production.
- **Phase gate:** no push to `main` happens until the product is validated
  locally by the maintainer (see `README.md`). Deployment is frozen until then.

Protect `main` on GitHub: require the `ci` workflow to pass, disallow force
pushes.

## CI/CD — GitHub Actions

**`ci.yml`** — runs on every push to any branch and on every pull request:
1. PHP 8.4 (setup-php) and Node 22; cache composer and npm
2. `composer install`, `npm ci`
3. `npm run build` (catches frontend compile errors)
4. `pint --test`
5. `larastan`
6. `pest` against a Postgres 17 service container
7. `composer audit`, `npm audit --audit-level=high`

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

The image is **built on the VPS**, not pushed to a registry. The workflow uses
`appleboy/ssh-action` and runs, in `/home/iromero/dev/bet-sefer/backend`:

1. `git pull origin main`
2. `docker compose -f docker-compose.prod.yml down`
3. `docker compose -f docker-compose.prod.yml up -d --build`
4. `docker compose exec -T app php artisan migrate --force` — **fail-closed**:
   a migration error stops the job (no "auto-migrate on startup" exists in
   Laravel, so a tolerated failure would leave a broken app in silence)
5. `docker compose exec -T app php artisan optimize`
6. `docker system prune -f`
7. `scripts/smoke-test.sh` — `curl -f` against `/up` with retries

The `concurrency` group with `cancel-in-progress: false` matters: two merges
landing close together must not run migrations against the same database at the
same time. The second deploy queues behind the first.

There is no automatic rollback to a previous image (nothing is pushed to a
registry). Because there is no manual trigger, the rollback path is a **revert
commit on `main`** — the correct, auditable option.

GitHub secrets: `VPS_HOST`, `VPS_USER`, `VPS_SSH_KEY` (scoped to the
`production` environment). Every runtime variable lives in a single `.env`
file provisioned on the server; it is never stored in GitHub.

## Host setup (once, for the Traefik host)

- DNS `A` record for `betsefer.appenlaweb.com` → VPS IP. Do this first; TLS
  cannot be issued until it resolves.
- Docker and Compose plugin installed.
- Firewall: 22, 80, 443 only.
- A Traefik instance on the host with the `web` (80) and `websecure` (443)
  entrypoints, the Docker provider on the shared **`traefik-public`** network,
  and a Let's Encrypt **`letsencrypt`** `certResolver` (HTTP-01 or DNS-01).
  `docker-compose.prod.yml` attaches the `web` container to `traefik-public`
  and references that `certResolver` in its labels.
- Clone the repository at `/home/iromero/dev/bet-sefer/backend` (this exact
  path is what `deploy.yml` uses) and provision the `.env` there from
  `.env.example` with production values (`APP_KEY` generated, `APP_DEBUG=false`,
  real DB/Redis/mail/Google/OpenRouter values). Confirm `.env` is untracked.
- Nightly backup via `ops/backup.sh` (pg_dump, 7-day retention). Add to cron:
  `30 3 * * * /home/iromero/dev/bet-sefer/backend/ops/backup.sh >> /var/log/betsefer-backup.log 2>&1`

## Deployment checklist (run at phase 10, after local validation)

- [ ] DNS resolves
- [ ] TLS certificate issued through the host Traefik
- [ ] `APP_DEBUG=false` confirmed in the running container
- [ ] Migrations and seeders ran
- [ ] The four demo accounts can log in
- [ ] Google SSO round-trips (optional feature; verified only if credentials exist)
- [ ] A QR code on a printed label opens the public page
- [ ] A test mail arrives through Brevo
- [ ] `/up` returns 200
