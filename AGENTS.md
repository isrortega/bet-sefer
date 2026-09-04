<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>

---

# Bet-Sefer project notes (learned while building)

Operational knowledge for future sessions. `CLAUDE.md` holds the architecture
rules; these are the practical gotchas this codebase actually has.

## Local environment

- The app runs in Docker behind a shared Traefik. Local URL: **https://betsefer.local**
  (Traefik redirects HTTP→HTTPS; accept the self-signed cert once). Mailpit UI: `http://localhost:8025`.
- The `vite` container runs `npm run build -- --watch` (no HMR — the app is HTTPS-only).
  **After adding/editing Vue pages, force-recreate the vite container**
  (`docker compose up -d --force-recreate vite`) — the watcher sometimes misses changes.
  Verify a page compiled by curling it and reading the Inertia component JSON.
- `make check` = Pint + Larastan + Pest. Run it before every commit.

## Filesystem/permissions (caused real bugs)

- Storage must stay **host-owned and world-writable**: `make perms` does
  `chown -R 1000:1000 storage bootstrap/cache && chmod -R a+rwX`. Do **not**
  chown storage to `www-data` — tracked `.gitignore` placeholders under
  `storage/` then block `git` when switching branches, and php-fpm (`www-data`)
  hits `touch(): Utime failed` compiling Blade if files aren't world-writable.
- Makefile identity split: runtime commands (artisan/tests/pest) run as
  `www-data` (uid 33); source-writing tools (`make pint`, `phpstan`,
  `key:generate`) run as the host UID. phpstan needs `--tmp-file /tmp` (writable).
- Prod image runs as `www-data`; the `web` container runs the **same prod image**
  with nginx (`nginx -g 'daemon off;'`) as root, so both containers share the
  baked `/var/www/html`. Don't reintroduce a separate `nginx:…` image — it had
  no application files and 404'd.

## Database / domain (things that bit us)

- Models with a `ulid` column are **NOT NULL** — every create path (seeders,
  admin create, tree create) must generate one. The admin category/location
  tree forgot it once; `TreeEditor::create` handles it.
- Tree nodes (`categories`, `locations`) store id-based `path`/`depth`. When a
  parent changes, **set `parent_id` too and reindex the subtree** (see
  `app/Support/TreeEditor`); forgetting `parent_id` makes `children()` and
  leaf-deletion checks silently wrong.
- Copy status changes only via `CopyStateMachine` (never `$copy->update([...])`).
- Loan due dates come only from `LoanPolicyResolver`/`BusinessCalendar`.
- Tests/CI: `phpunit.xml` defines a **test-only `APP_KEY`** and
  `APP_TIMEZONE=America/Bogota` so the suite passes without a local `.env`
  (that was a CI-only failure). Pest runs against real Postgres — never SQLite.
- `Http::fake` state is shared across tests in the same file (same app
  instance): count external calls with a **scoped closure counter**, not the
  global assertion log.

## i18n

- `lang/en.json` + `lang/es.json` are the single source. PHP uses `__('key')`;
  the frontend loads the same files via `resources/js/i18n.js` (`t('key', {p})`).
  **Add the key to both files before using `t()`**, or you get a raw key on screen.
- Interpolation placeholder format is `:name`. Server flash/validation messages
  already go through `__()`.

## Git / releases

- Work on feature branches from `develop`; merge to `develop` when a phase is
  done (checkpoint with the user). `main` is only written by merges from
  `develop`, and **pushing `main` triggers the production deploy** — don't push
  docs to `main` unless a release is intended.
- Public-anonymous payloads are built by allow-list resources; never pass a full
  Eloquent model to a public Inertia page.

## Production

- `deploy.yml` runs only on push to `main`: SSH → `git fetch && git checkout main
  && git pull` → `down` → `up -d --build` (image built on the VPS) → `migrate
  --force` (fail-closed) → **seed only if `users` is empty** (idempotent) →
  `optimize` → `scripts/smoke-test.sh` (`curl /up`).
- Runtime `.env` lives on the VPS at `~/dev/bet-sefer/backend/.env`; the repo
  carries only `.env.example` (production values come from GitHub secrets
  `VPS_HOST`/`VPS_USER`/`VPS_SSH_KEY` + the server-side `.env`).
- `ops/backup.sh` (pg_dump, 7-day retention) is committed; its cron is not yet
  configured on the host (see README for the crontab line).
- Demo credentials are seeded by `database/seeders/DemoUsersSeeder.php`; all
  demo accounts share the same test password.

