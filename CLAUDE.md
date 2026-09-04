# Bet-Sefer — Library Management System

Technical assessment for Valsoft. A working library management system: catalogue,
circulation (check-out / check-in), search, role-based access, and a public
information point reachable by QR.

**Delivery is today.** Prefer a small, polished, well-tested product over a large
unfinished one. When in doubt, cut scope and document it in `docs/09-roadmap.md`.

---

## Stack

| Layer | Choice |
|---|---|
| Runtime | PHP 8.4 |
| Framework | Laravel 13 |
| Database | PostgreSQL 17 |
| Cache / queue | Redis |
| Frontend | Inertia + Vue 3 (Composition API) + Tailwind 4 |
| Object storage | Cloudflare R2 (S3 driver); MinIO in dev |
| Mail | Brevo SMTP in prod; Mailpit in dev |
| Auth | Laravel session auth + Google SSO (Socialite) |
| Permissions | `spatie/laravel-permission` |
| Audit | `spatie/laravel-activitylog` |
| Tests | Pest |
| Static analysis | Larastan (level 6+), Pint |
| Deploy | Docker + GitHub Actions → Hetzner VPS, Caddy for TLS |

Production URL: `https://betsefer.appenlaweb.com`
Timezone: `America/Bogota`. Store UTC, present in local time.

---

## Language rules

- **All code, identifiers, comments, commit messages, UI strings and docs are in English.**
- UI ships with i18n (`en` default, `es` available). Never hardcode user-facing
  strings in components — always through the translation layer.

---

## Architecture rules

- Controllers are thin: validate via Form Request → call an Action → return an
  Inertia response. No business logic in controllers.
- Business logic lives in `app/Actions/{Domain}/{VerbNoun}Action.php`, single
  public `handle()` method.
- Domain enums in `app/Enums`, backed by string. **Never use native Postgres
  enum types** — use `varchar` + `CHECK` constraint.
- Authorization is always through Policies and permission names, never by
  checking a role name in code (`$user->can('loans.create')`, not
  `$user->hasRole('librarian')`).
- Anything that mutates copy state goes through `CopyStateMachine`. No direct
  `$copy->update(['status' => ...])` anywhere else.
- Loan due dates are only ever computed by `LoanPolicyResolver`. No date maths
  scattered in controllers or models.
- External identifiers in URLs are ULIDs or human codes, never auto-increment IDs.
- Money and durations: durations in **hours** (integers), never days.

## Directory layout

```
app/
  Actions/{Catalog,Circulation,Inventory,Users,Metadata}/
  Enums/
  Http/Controllers/{Staff,Public,Auth}/
  Http/Requests/
  Models/
  Policies/
  Services/
    Metadata/          # ISBN providers + merger
    Circulation/       # LoanPolicyResolver, CopyStateMachine, BusinessCalendar
    Classification/    # AI tag/category suggestion
  Support/
resources/js/
  Pages/{Staff,Public,Auth}/
  Components/
  Composables/
  layouts/
docs/
tests/{Unit,Feature}/
```

---

## Commands

```bash
make up          # start dev stack (app, postgres, redis, mailpit, minio)
make down
make fresh       # migrate:fresh --seed
make test        # pest
make check       # pint --test && larastan && pest
make shell       # bash into app container
```

Run `make check` before every commit. CI runs the same command.

---

## Hard rules — do not violate

1. **Never** expose a reader's identity through any unauthenticated endpoint.
   The public info point shows aggregates only. See `docs/04-public-info-point.md`.
2. **Never** log or dump `document_number`, `phone`, tokens, or mail credentials.
3. `loans` must keep the partial unique index
   `UNIQUE (copy_id) WHERE returned_at IS NULL`. Do not drop it "to simplify".
4. Do not add a `works` table. Editions and copies only. See `docs/01-domain-model.md`.
5. Do not use `localStorage` for domain state; server is the source of truth.
6. Do not introduce Meilisearch/Scout. Search is Postgres `tsvector` + `pg_trgm`.
7. Never call an external metadata API synchronously without a timeout and a
   manual-entry fallback path.
8. Every new permission-protected route needs a **negative** test proving the
   wrong role gets a 403.
9. Deployment happens **only** on push to `main`. `deploy.yml` triggers on
   nothing else — no `workflow_dispatch`, no other branches, no tags. Day-to-day
   work goes to `develop` and feature branches.

---

## Definition of done for any feature

- Action + Form Request + Policy in place.
- Feature test for the happy path.
- Feature test for at least one unauthorized role (403).
- Unit test if it touches the policy resolver, state machine, or calendar.
- `make check` passes.
- User-facing strings added to `lang/en.json` and `lang/es.json`.

---

## Documentation map

| File | Read it when |
|---|---|
| `docs/00-brief.md` | Understanding scope, roles, what is in/out |
| `docs/01-domain-model.md` | Writing migrations or models |
| `docs/02-business-rules.md` | Loans, state machine, permissions, user lifecycle |
| `docs/03-isbn-and-ai.md` | Metadata lookup or AI classification |
| `docs/04-public-info-point.md` | Public routes, QR |
| `docs/05-design-system.md` | Any UI work |
| `docs/06-security.md` | Auth, PII, rate limits, headers |
| `docs/07-testing.md` | Writing tests |
| `docs/08-infrastructure.md` | Docker, CI/CD, env vars, deploy |
| `docs/09-roadmap.md` | Something is deliberately not built |
