# 09 — Roadmap

Everything here was designed for and deliberately deferred. The schema already
carries the columns and states each item needs, so none of it requires a
rewrite. This file doubles as the roadmap section of the README.

## Delivery status

- Runs locally through the shared Traefik at `betsefer.local` (HTTP + HTTPS).
- **Production deployment is deliberately deferred** until the product is
  validated locally by the maintainer. Nothing has been pushed to `main`.
  When the gate opens, follow `docs/08-infrastructure.md`.

## Object storage (Cloudflare R2)

The MVP stores covers on the local `public` disk. R2 drops in later behind the
same `Storage` facade via the S3 driver — the `.env.example` already carries the
commented variables. Migration steps: add the S3 driver config, flip
`FILESYSTEM_DISK=r2`, copy the existing local covers over, and re-run the
`storage:link`-equivalent for private keys. Signed URLs are prepared for the
follow-up; covers stay public.

## Deferred in this build (delivery-day cuts)

Everything below is designed in the docs and ready in the schema; only the
logic/UI is missing. See the phase notes in `docs/08-infrastructure.md` for the
local-first delivery gate.

- **ISBN ingestion UI with AI classification** — the librarian screens that
  fetch Open Library / Google Books and run the OpenRouter classifier. The
  domain services are specified in `docs/03-isbn-and-ai.md`; the metadata
  cache tables exist.
- **Staff catalogue management** (add/edit/delete editions and copies,
  taxonomy trees, loan policies, settings) — permissions exist
  (`editions.*`, `copies.*`, `taxonomy.manage`, `settings.manage`,
  `policies.manage`); the admin screens are not built. Editions can be
  browsed publicly, borrowed and returned today.
- **Printable QR label sheets** (`/staff/copies/labels`) — the generator is in
  place for member cards and copy codes; the A4 print sheet is not.
- **Reports / dashboards UI** — `demand_events` are recorded on empty searches
  and acquisition suggestions so the data exists; the admin report screens are
  not built.
- **Password reset** and notification emails beyond account verification.
- **Reservations** — see below (the `reserved` copy state stays unreachable).

## Shipped after the first delivery cut

- **Public external ISBN lookup** — when an ISBN is not in the catalogue, the
  lookup page queries Open Library (free) and, if a `GOOGLE_BOOKS_API_KEY` is
  set, Google Books, cached 90 days in `metadata_lookups`; it shows the title
  framed as external and still records suggestions. Never creates editions.
- **Acquisition suggestions area** — `/staff/demand` (admin only,
  `demand.manage`) lists `acquisition_suggestion` events, lets an admin mark
  one as handled (`resolved_at`), and shows pending/total counts.

## Reservations

The `reserved` copy status and the `demand_events` table exist. Missing: a
`reservations` table (FIFO queue per edition), assignment of a freed copy to the
first in line, a 48-hour hold-shelf window, and a daily expiry job.

## Monthly acquisition report

`demand_events` is already being recorded, which is the part that cannot be
reconstructed retroactively. Missing: the scheduled job and the report UI.

Intended metrics:
- turnover per edition (`loans ÷ copies`)
- **holds-per-copy ratio** — the classic library trigger; above ~3, buy another copy
- average queue wait
- titles searched for that the library does not own, from `search_miss` and
  `acquisition_suggestion` events
- an AI-written narrative summary layered on top of figures computed in SQL —
  never figures produced by the model

## Fines

`loans.fine_amount`, `loans.fine_status`, `users.blocked_until`,
`users.suspension_reason` and `loan_policies.daily_fine_amount` are in place and
unused. Missing: accrual job, payment recording, and the suspension workflow.

## Additional metadata providers

`MetadataProvider` is an interface with two implementations. ISBNdb and the
Apify ISBN decoder plug in behind it, but must run in a **queued enrichment
job**, never in the request path — Apify is an actor platform that has to be
started and polled, which is unacceptable in front of a waiting librarian.

## Semantic search

Postgres full-text search covers the assessment. `pgvector` with embeddings over
title, summary and tags would enable natural-language queries such as
"books about the fall of the Roman empire" with no literal keyword match, plus
content-based recommendations for readers.

## Other

- Two-factor authentication for administrators and librarians.
- Overdue and hold-ready notification emails.
- Dark mode — all tokens are already CSS variables, so this is a scoped change.
- Multi-branch support (a `branches` table above `locations`).
- Reader-facing loan history export.
- Barcode support alongside QR for libraries with existing scanners.
- A `works` table grouping editions of the same work, for "we have this in
  another edition".
