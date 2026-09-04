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
- OPcache enabled with `validate_timestamps=0` and preloading.
- `php artisan config:cache route:cache view:cache event:cache` at build time.
- Runs as a non-root user.
- `HEALTHCHECK` hitting `/up`.
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

## Host setup (once, for the Traefik host)

- DNS `A` record for `betsefer.appenlaweb.com` → VPS IP. Do this first; TLS
  cannot be issued until it resolves.
- Docker and Compose plugin installed.
- Firewall: 22, 80, 443 only.
- A Traefik instance on the host with the `web` (80) and `websecure` (443)
  entrypoints, the Docker provider on the shared `traefik_net` network, and a
  Let's Encrypt `certResolver` (HTTP-01 or DNS-01). The app's labels reference
  that `certResolver` in production; the exact name is confirmed at deploy time.
- A nightly `pg_dump` to the host (R2 backup in a later phase), retained 7 days.

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
