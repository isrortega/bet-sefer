# 01 — Domain model

## Core decision: Edition ≠ Copy

- **Edition** — the bibliographic record. One per ISBN. Title, authors,
  publisher, year, language, pages, format, dimensions, cover, summary,
  category, tags, loan type, special-material flag.
- **Copy** — the physical object that gets lent. Code (QR), location, status,
  condition, acquisition data, internal notes.

Status, location and internal condition belong to the **copy**. An edition never
has a status. There is deliberately **no `works` table**.

## Conventions

- `id` bigint primary key, plus a `ulid char(26)` column for external exposure.
- Enums stored as `varchar` with a `CHECK` constraint, mirrored by a PHP backed
  enum. No native Postgres enum types.
- `timestamptz` everywhere, stored in UTC.
- Soft deletes on `users`, `editions`, `copies`.
- All money as `numeric(10,2)`, all durations as integer **hours**.

## Codes

One generator (`App\Support\CrockfordCode`), three prefixes:

- Copy code: `BS-` + 8 Crockford chars.
- Member code: 8 Crockford chars, no prefix.
- Loan receipt code: `LN-` + 8 Crockford chars.

Each code is **7 random characters + 1 check digit**, drawn from the Crockford
base32 alphabet `0123456789ABCDEFGHJKMNPQRSTVWXYZ` (which excludes I, L, O and U
so it survives handwriting and OCR). The check digit is

```
Σ(value_i × weight_i) mod 32   with weights alternating 1, 3 (1 for the first char)
```

mapped back to a Crockford symbol. Examples: `BS-4F7K2Q91`, `LN-4F7K2Q91`.
Codes are **random, never sequential** — sequential codes would let anyone
enumerate the collection. Uniqueness is always enforced by the database on the
full code (copy `code`, `users.member_code`, `loans.code`).

---

## Block 1 — Identity

### `users`

| Column | Type | Notes |
|---|---|---|
| `id`, `ulid` | bigint, char(26) | |
| `name` | varchar(160) | |
| `email` | varchar(190) | unique |
| `email_verified_at` | timestamptz null | |
| `password` | varchar null | null for SSO-only accounts |
| `google_id` | varchar(64) null | unique |
| `avatar_url` | varchar null | |
| `status` | varchar(24) | `pending_email` \| `pending_identity` \| `active` \| `suspended` |
| `member_code` | varchar(16) | unique. 8-char Crockford base32 + check digit (same generator as copy codes, no prefix). Drives the member QR |
| `document_type` | varchar(16) null | `CC` \| `CE` \| `passport` |
| `document_number` | text null | **encrypted cast** |
| `document_hash` | char(64) null | unique. HMAC-SHA256 of normalised document number with `APP_KEY`. Enables lookup and uniqueness over an encrypted value |
| `phone` | text null | encrypted cast |
| `identity_verified_at` | timestamptz null | |
| `identity_verified_by_id` | bigint null | FK → users, nullOnDelete |
| `blocked_until` | timestamptz null | prepared for fines |
| `suspension_reason` | varchar null | |
| `locale` | varchar(5) | default `en` |
| timestamps, `deleted_at` | | |

Indexes: `email`, `google_id`, `document_hash`, `member_code`, `status`.

Plus the five `spatie/laravel-permission` tables and Laravel's standard
`sessions`, `password_reset_tokens`, `jobs`, `failed_jobs`, `cache`.

---

## Block 2 — Bibliographic catalogue

### `authors`
`id`, `ulid`, `name` varchar(160), `slug` unique, `sort_name`, `birth_year`
smallint null, `death_year` smallint null, `bio` text null, `external_ids`
jsonb default `'{}'`, timestamps.

### `publishers`
`id`, `name` varchar(160), `slug` unique, timestamps.

### `categories`
`id`, `ulid`, `parent_id` FK self `restrictOnDelete`, `name`, `slug`,
`path` text (e.g. `/1/7/23/`), `depth` smallint, `description` text null,
timestamps.
Unique `(parent_id, slug)`. Index on `path` with `text_pattern_ops` so a subtree
is `WHERE path LIKE '/1/7/%'`.

### `tags`
`id`, `name` varchar(80), `slug` unique, `source` varchar(16)
(`manual` \| `ai` \| `import`), timestamps.

### `editions`

| Column | Type | Notes |
|---|---|---|
| `id`, `ulid` | | |
| `isbn_13` | char(13) null | canonical, normalised. **Partial unique index** `WHERE deleted_at IS NULL` |
| `isbn_10` | char(10) null | |
| `title` | varchar(500) | |
| `subtitle` | varchar(500) null | |
| `edition_statement` | varchar(120) null | e.g. "2nd revised edition" |
| `publisher_id` | bigint null | FK, nullOnDelete |
| `category_id` | bigint null | FK, nullOnDelete |
| `published_year` | smallint null | |
| `language` | varchar(5) | default `en` |
| `page_count` | integer null | |
| `format` | varchar(24) | `hardcover` \| `paperback` \| `spiral` \| `magazine` \| `other` |
| `height_mm`, `width_mm`, `depth_mm` | smallint null | |
| `summary` | text null | |
| `cover_path` | varchar null | key on the local `public` disk (R2 in a later phase) |
| `cover_source` | varchar(32) null | |
| `loan_type` | varchar(24) | `general` \| `reference` \| `periodical` |
| `special_material` | boolean | default false. **Edition level only** |
| `loan_restricted_default` | boolean | default false |
| `internal_notes` | text null | never exposed to readers |
| `metadata_source` | varchar(24) | `open_library` \| `google_books` \| `merged` \| `manual` |
| `ai_classified_at` | timestamptz null | |
| `ai_model` | varchar(64) null | |
| `search_vector` | tsvector | **generated column**, GIN index |
| `created_by_id`, `updated_by_id` | bigint null | FK users |
| timestamps, `deleted_at` | | |

`search_vector` is a stored generated column built from title, subtitle and
ISBNs with `unaccent`. Author and tag terms are joined in at query time via the
pivots — do not try to denormalise them into the generated column, Postgres
cannot reference other tables there.

### `edition_author`
`edition_id`, `author_id`, `role` varchar(16)
(`author` \| `editor` \| `translator` \| `illustrator`), `position` smallint.
Primary key `(edition_id, author_id, role)`.

### `edition_tag`
`edition_id`, `tag_id`. Primary key on both.

---

## Block 3 — Physical inventory

### `locations`
`id`, `ulid`, `parent_id` FK self restrict, `name`, `code` varchar(24),
`type` varchar(16) (`floor` \| `room` \| `aisle` \| `shelf` \| `section`),
`path` text, `depth` smallint, `capacity` integer null, timestamps.
Same tree pattern as `categories`.

### `copies`

| Column | Type | Notes |
|---|---|---|
| `id`, `ulid` | | |
| `code` | varchar(16) | unique. `BS-` + 8 Crockford chars (7 random + 1 check digit). This is what the QR encodes |
| `edition_id` | bigint | FK, `restrictOnDelete` |
| `location_id` | bigint null | FK, nullOnDelete |
| `status` | varchar(24) | see state machine |
| `condition` | varchar(16) | `new` \| `good` \| `fair` \| `poor` |
| `loan_restricted` | boolean **null** | null means inherit from the edition |
| `acquisition_date` | date null | |
| `acquisition_cost` | numeric(10,2) null | |
| `internal_notes` | text null | |
| `status_changed_at` | timestamptz | drives the shelver FIFO queue |
| timestamps, `deleted_at` | | |

Effective restriction is
`COALESCE(copies.loan_restricted, editions.loan_restricted_default)`.
Expose this as `Copy::isLoanRestricted()`; never read the raw column in a
controller.

Indexes: `code`, `(edition_id, status)`, `(status, status_changed_at)`,
`location_id`.

### `copy_status_transitions`
`id`, `copy_id` FK cascade, `from_status`, `to_status`, `user_id` FK null,
`loan_id` FK null, `from_location_id` null, `to_location_id` null,
`note` varchar null, `created_at`.
Index `(copy_id, created_at)`.

This is queryable history, separate from the generic activity log.

---

## Block 4 — Circulation

### `loan_policies`
`id`, `loan_type` varchar(24) unique, `default_hours` int, `min_hours` int,
`max_hours` int, `renewals_allowed` smallint default 0,
`special_material_factor` numeric(3,2) default 0.50, `grace_hours` int default 24,
`daily_fine_amount` numeric(10,2) default 0 *(prepared, unused)*,
`max_active_loans_per_user` smallint default 5, `is_active` boolean, timestamps.

Seeded values:

| loan_type | default | min | max | renewals |
|---|---|---|---|---|
| `general` | 240h (10d) | 168h | 360h | 2 |
| `reference` | 36h | 24h | 48h | 0 |
| `periodical` | 96h | 24h | 168h | 1 |

### `loans`

| Column | Type | Notes |
|---|---|---|
| `id`, `ulid`, `code` | | `code` unique, receipt number, format `LN-` + 8 Crockford chars |
| `copy_id` | bigint | FK restrict |
| `user_id` | bigint | FK restrict — the reader |
| `checked_out_by_id` | bigint | FK — the librarian |
| `checked_in_by_id` | bigint null | FK |
| `checked_out_at` | timestamptz | |
| `due_at` | timestamptz | |
| `returned_at` | timestamptz null | |
| `renewals_count` | smallint default 0 | |
| `policy_snapshot` | jsonb | frozen copy of the policy applied |
| `fine_amount` | numeric(10,2) default 0 | *prepared, unused* |
| `fine_status` | varchar(16) default `none` | *prepared, unused* |
| `notes` | text null | |
| timestamps | | |

**Critical constraint**, added with raw SQL:

```sql
CREATE UNIQUE INDEX loans_one_active_per_copy
  ON loans (copy_id) WHERE returned_at IS NULL;
```

`policy_snapshot` freezes the policy at check-out time so that an administrator
changing the rules tomorrow does not rewrite the past. Renewal recomputes from
the snapshot, not from the live policy.

Indexes: `(user_id, returned_at)`, `(due_at) WHERE returned_at IS NULL`.

### `holidays`
`id`, `date` date unique, `name`, `is_recurring` boolean.
Recurring rows match on month+day regardless of year.

### `library_hours`
`id`, `weekday` smallint 0–6 unique (0 = Monday), `opens_at` time null, `closes_at` time null,
`is_closed` boolean.

---

## Block 5 — Demand, metadata, settings, audit

### `demand_events`
`id`, `type` varchar(32)
(`unavailable_hit` \| `search_miss` \| `public_lookup_unavailable` \| `acquisition_suggestion`),
`edition_id` null FK, `isbn` char(13) null, `query_text` varchar(255) null,
`user_id` null FK, `ip_hash` char(64) null, `created_at`.
Indexes `(type, created_at)`, `edition_id`.

Demand cannot be reconstructed later — it must be recorded when it happens.

### `metadata_lookups`
`id`, `isbn_13` char(13), `provider` varchar(32), `status` varchar(16)
(`hit` \| `miss` \| `error`), `payload` jsonb, `fetched_at`, `expires_at`.
Unique `(isbn_13, provider)`. Persistent cache and audit trail behind Redis.

### `settings`
`id`, `key` varchar(80) unique, `value` jsonb, timestamps.

### `activity_log`
From `spatie/laravel-activitylog`, default schema.

---

## Migration plan

Eight batches. Each one must run cleanly on its own.

1. **Base** — extend `users`, Spatie tables, `settings`, `holidays`, `library_hours`.
2. **Taxonomies** — `authors`, `publishers`, `categories`, `tags`, `locations`.
3. **Catalogue** — `editions`, `edition_author`, `edition_tag`.
4. **Search** — generated `search_vector` column + GIN index, via `DB::statement`.
5. **Inventory** — `copies`, `copy_status_transitions`.
6. **Circulation** — `loan_policies`, `loans`, then the partial unique index via
   `DB::statement`.
7. **Analytics** — `demand_events`, `metadata_lookups`.
8. **Extensions** — `CREATE EXTENSION IF NOT EXISTS unaccent; pg_trgm;` — put
   this batch **first** in filename order, extensions must exist before the
   generated column.

## Seeders, in order

1. Roles and permissions (`docs/02-business-rules.md` matrix).
2. Loan policies.
3. Colombian public holidays for the current year + `library_hours`
   (Mon–Fri 08:00–18:00, Sat 09:00–13:00, Sun closed).
4. Category tree (3 levels) and location tree (Floor → Room → Aisle → Shelf).
5. Four demo users, one per role, `status = active`, identity verified.
   Credentials go in the README.
6. 40 editions from real ISBNs with 1–4 copies each.
7. ~120 historical loans across the last 6 months: mostly returned, a few
   active, a few overdue, so the catalogue and dashboards are not empty.
8. A handful of `demand_events` so demand reporting has something to show.
