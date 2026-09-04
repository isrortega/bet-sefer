# Bet-Sefer

> A library management system. *Bet-Sefer* (בית ספר) — "house of the book".

**Live demo:** https://betsefer.appenlaweb.com

<!-- One screenshot of the front desk goes here. Nothing else above the fold. -->

## What it does

Catalogue management driven by ISBN lookup, circulation with a configurable loan
policy engine, a seven-state inventory workflow, four roles with granular
permissions, and a public QR information point that needs no account.

## Try it in five minutes

| Role | Email | Password |
|---|---|---|
| Administrator | `admin@betsefer.test` | *(see below)* |
| Librarian | `librarian@betsefer.test` | |
| Shelver | `shelver@betsefer.test` | |
| Reader | `reader@betsefer.test` | |

Google sign-in also works. New accounts start as readers awaiting in-person
identity verification, which mirrors how a real library issues a card.

**Suggested path:**
1. Sign in as the librarian. Open the front desk, scan or paste a copy code from
   the catalogue, then a member code. Check the book out.
2. Check it back in. The copy moves to *At reception*.
3. Sign in as the shelver. The copy is waiting in the shelving queue with its
   destination shelf.
4. Open `/i/{copy-code}` in a private window — the public information point,
   with no borrower identity anywhere on the page.
5. As the librarian, add a book by ISBN. Metadata is fetched and merged from two
   sources and the category and tags are suggested automatically.

## Features

- **Catalogue** — editions and physical copies modelled separately, so the same
  title can have many copies in different states and locations.
- **ISBN acquisition** — Open Library and Google Books queried in parallel and
  merged field by field, cached, with a manual fallback that always works.
- **AI classification** — suggests category and tags from the summary. It
  suggests; the librarian decides.
- **Loan policies** — three loan types configurable in hours, special material
  halves the period, due dates respect opening hours and holidays.
- **Inventory states** — seven states with role-gated transitions and full
  history, including a shelver workflow that a real library actually needs.
- **Search** — Postgres full-text across titles, authors, tags and ISBN, with
  filters by category tree, location tree, status and availability.
- **Roles** — administrator, librarian, shelver, reader, on granular permissions.
- **Public information point** — QR on every copy, one URL, four experiences
  depending on who scans it. Anonymous visitors see availability and location,
  never who borrowed anything.
- **Demand tracking** — unmet demand is recorded as it happens, so acquisition
  decisions can be made from data rather than guesses.
- **Audit log** — every state change, loan and permission change.

## Running locally

```bash
git clone <repo> && cd bet-sefer
cp .env.example .env
make up
make fresh
```

The app is at http://localhost, Mailpit at http://localhost:8025, MinIO at
http://localhost:9001.

Requirements: Docker and Docker Compose. Nothing else — PHP, Node and Postgres
all run in containers.

```bash
make test     # test suite
make check    # formatting, static analysis, tests — same as CI
```

## Architecture

Laravel 13 on PHP 8.4, PostgreSQL 17, Redis, Inertia with Vue 3 and Tailwind 4.
Covers on Cloudflare R2. Deployed as a Docker image built by GitHub Actions and
pulled onto a Hetzner VPS behind Caddy.

Business logic lives in single-purpose action classes. Three services carry the
rules worth protecting: `LoanPolicyResolver` computes every due date,
`CopyStateMachine` owns every status change, and `BusinessCalendar` keeps due
dates inside opening hours.

Design notes are in [`docs/`](docs/):

| | |
|---|---|
| [Product brief](docs/00-brief.md) | scope and roles |
| [Domain model](docs/01-domain-model.md) | schema and reasoning |
| [Business rules](docs/02-business-rules.md) | loans, states, permissions |
| [ISBN and AI](docs/03-isbn-and-ai.md) | metadata pipeline |
| [Public info point](docs/04-public-info-point.md) | the QR surface |
| [Design system](docs/05-design-system.md) | palette, type, layout |
| [Security](docs/06-security.md) | authz, PII, hardening |
| [Testing](docs/07-testing.md) | strategy and gates |
| [Infrastructure](docs/08-infrastructure.md) | Docker, CI/CD, deploy |
| [Roadmap](docs/09-roadmap.md) | what was deferred and why |

## Notable decisions

**Editions and copies are separate tables.** Status, location and condition
belong to the physical object, not to the bibliographic record. Without this
split, "we have four copies, one is on loan and one is being repaired" cannot be
expressed.

**Loans freeze the policy that created them.** `policy_snapshot` means an
administrator changing the rules tomorrow does not rewrite the due dates of
loans already in progress.

**A partial unique index guarantees one active loan per copy.** Application
logic locks the row, but `UNIQUE (copy_id) WHERE returned_at IS NULL` is what
makes double lending impossible even under a race.

**Reader documents are encrypted, and searchable anyway.** An HMAC hash column
alongside the encrypted value carries the unique index and serves front-desk
lookups, since an encrypted column cannot be indexed.

**Demand is recorded as it happens.** "Which books should we buy more of" cannot
be answered retroactively, so unmet demand is captured at the moment a reader
finds nothing available.

## Testing

<!-- Fill in the real numbers after the final run. -->
Pest, running against real Postgres because the schema depends on partial unique
indexes, generated columns and `tsvector`. Coverage concentrates on the loan
policy engine, the state machine, the authorization matrix — including a
negative test per role per protected route — the public payload privacy
guarantees, and a concurrency test proving two simultaneous check-outs of one
copy produce exactly one loan.

## Scope

Built in one day for a technical assessment. The
[roadmap](docs/09-roadmap.md) lists what was designed for and deliberately
deferred: reservations, the monthly acquisition report, fines, semantic search
and two further metadata providers. The schema already carries the columns and
states each of them needs.
