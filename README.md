# Bet-Sefer — Library Management System

A working library management system for a single branch: catalogue, circulation
(check-out / check-in / renewal), full-text search, role-based access, a
shelving queue for staff, and a public information point reachable by QR.

Built as a single Laravel monolith: **Laravel 13 (PHP 8.4) + Inertia + Vue 3 +
Tailwind 4 + PostgreSQL 17 + Redis**. There is no separate frontend service.

> Technical assessment for Valsoft. Delivery-gate: the product runs locally
> behind the shared Traefik at `betsefer.local`. Production deployment is
> deliberately deferred until local validation (see `docs/08`).

## Quick start

Prerequisites: Docker with the Compose plugin, and an existing Traefik instance
with a shared `traefik_net` network and `betsefer.local` already resolving to
the host (local DNS is fine).

```bash
cp .env.example .env       # first time only; fills APP_KEY automatically
make up                    # build images, start services, install deps
make fresh                 # migrate:fresh --seed (demo data)
```

Then open **https://betsefer.local** (the shared Traefik redirects HTTP to
HTTPS; accept the self-signed certificate once).

Useful commands:

| Command | What it does |
|---|---|
| `make up` / `make down` | Start / stop the dev stack |
| `make fresh` | `migrate:fresh --seed` |
| `make seed` | Re-run seeders (after a fresh migration) |
| `make test` | Run Pest |
| `make check` | Pint + Larastan + Pest (run before every commit) |
| `make shell` | Bash inside the app container |
| `make logs` | Follow container logs |

Services run in Docker: `app` (PHP-FPM), `web` (Nginx), `queue`, `scheduler`,
`postgres:17`, `redis:7`, `mailpit` (UI on http://localhost:8025), and `vite`
which compiles assets to `public/build` (no HMR, to keep HTTPS simple).

## Demo accounts

**Note: every demo account uses the same test password — `DemoPassword-2026`.**

| Role | Email | What they can do |
|---|---|---|
| Administrator | `admin@betsefer.local` | Everything, incl. reader restore |
| Librarian | `librarian@betsefer.local` | Front desk, identity verification |
| Shelver | `shelver@betsefer.local` | The `/staff/shelving` queue |
| Reader | `reader@betsefer.local` | Catalogue + `/account` area |

The seed data: 46 real editions (~117 copies), 112 historical loans (some
active, some overdue), and a shelving queue with copies waiting.

## Reviewer guide

Open **[`VALIDATION.html`](VALIDATION.html)** — a 10–15 minute functional
walkthrough of the production app (and the local build) with the demo
credentials, deterministic ISBN/title examples, and a SQL snippet to fetch the
per-environment random copy/member codes.

## Main screens

- **Public** — `/catalog` search by title/author/tag; `/lookup?isbn=` and
  `/i/{code}` (QR target) show status, location and availability **without any
  borrower identity**. Anonymous payloads are allow-listed.
- **Sign in / register** — email+password always works. Google SSO appears
  only when `GOOGLE_CLIENT_ID`/`GOOGLE_CLIENT_SECRET` are set. New accounts
  land in `pending_email`, then `pending_identity`; borrowing activates only
  after the librarian verifies the document in person.
- **Front desk** — `/staff/desk` (librarian): check out by scanning member code
  + copy code, check in, renew.
- **Shelving** — `/staff/shelving` (shelver, mobile): copies move
  `at_reception → in_transit → available`.
- **Readers** — `/staff/readers` (librarian/admin): verify identities, reopen
  closed accounts.
- **Reader account** — `/account`: current loans, history, member QR card.

## Configuration (all optional for local)

`.env.example` documents every variable, with the ones that need real values
when you go live:

- `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` — enables SSO.
- `OPENROUTER_API_KEY` + `AI_MODEL` — enables AI tag/category suggestions on
  ISBN import (the import screens are a later phase).
- Brevo SMTP values — production mail; local dev uses Mailpit.
- R2 variables are commented out — storage is local disk for this phase.

## Roadmap and deliberate cuts

See `docs/09-roadmap.md`. The schema is ready for fines, reservations and
metadata enrichment; the code is not. This build's cuts are listed there so the
15-minute reviewer journey stays clean.

## Production deploy

Pushing to `main` deploys to `https://betsefer.appenlaweb.com`. The image is
built on the VPS from `docker/prod/Dockerfile` and served behind the shared
Traefik (`traefik-public` + `letsencrypt`). The GitHub Actions `Deploy`
workflow pulls `main` on the host, builds, migrates (fail-closed), seeds the
demo data only on first provisioning, and smoke-tests `/up`. See
`docs/08-infrastructure.md` for the full flow and one-time host setup.

### Backups

The nightly dump script **`ops/backup.sh`** (`pg_dump` + 7-day retention) is
implemented and committed. The cron entry on the production host is **not
configured yet**; to activate it:

```
30 3 * * * /home/iromero/dev/bet-sefer/backend/ops/backup.sh >> /var/log/betsefer-backup.log 2>&1
```

## Docs

`CLAUDE.md` holds the architecture rules; `docs/` holds the design (domain
model, business rules, ISBN/AI, public point, design system, security,
testing, infrastructure, roadmap).
