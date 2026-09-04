# 00 — Product brief

## What this is

A library management system for a single library branch. Four kinds of people use it:

| Role | What they do |
|---|---|
| **Administrator** | Configures the system, manages users, roles, categories, locations and loan policies. Full control over inventory, loans and reports. |
| **Librarian** | Works the front desk. Checks books out and in, registers new books, updates metadata, verifies reader identity. |
| **Shelver** (`shelver`) | Takes returned books from reception and puts them back on the shelf. Works from a phone. |
| **Reader** (`reader`) | Browses the catalogue, sees availability, sees their own loan history. |

## Assessment requirements (from the brief)

Required:
- Book management: add, edit, delete books with metadata.
- Check-in / check-out.
- Search by title, author, or other fields.
- Working product, source code, README with run instructions.

Bonus targeted:
- Deployed with a live URL. ✅
- Authentication with SSO and roles/permissions. ✅
- AI feature. ✅ (automatic classification on ISBN import)
- Creative extras. ✅ (public QR information point, shelver workflow, demand
  tracking, physical location tree, policy engine with opening hours)

Evaluation criteria to optimise for, in order: **completeness → usability →
product quality → creativity**. A reviewer will spend maybe 15 minutes in the
app. Seeded data and an obvious happy path matter more than breadth.

## In scope

- Editions and copies, with soft delete (hard delete only when no history).
- ISBN-driven acquisition (Open Library + Google Books merged) with AI-suggested
  category and tags.
- Full-text search across title, authors, tags, ISBN; filters by status,
  category, location.
- Loan policy engine: three loan types, configurable hours, special-material
  halving, opening hours and holidays.
- Copy state machine with seven states and role-gated transitions.
- Check-out / check-in front desk, keyboard-first.
- Shelver queue: reception → in transit → available.
- Four roles with granular permissions.
- Local login + Google SSO. Self-registration with in-person identity verification.
- Public information point by ISBN and QR, no authentication.
- QR labels for copies and a member card QR for readers.
- Audit log.
- Docker dev + prod, GitHub Actions deploy, seeded demo data.

## Out of scope today

Documented in `docs/09-roadmap.md`. Summary: reservation queue, monthly
acquisition report UI, fines logic, 2FA, ISBNdb/Apify providers, semantic
search, dark mode, notification emails beyond account verification.

The database schema *prepares* for fines and reservations (columns and states
exist); the logic does not.

## Non-negotiables

Security and tests are the stated priorities of this build. A feature without a
negative-authorization test is not done. See `docs/06-security.md` and
`docs/07-testing.md`.
