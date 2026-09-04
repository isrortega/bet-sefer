# 02 — Business rules

## 1. Loan policy engine

`LoanPolicyResolver::resolve(Copy $copy, ?int $requestedHours): LoanTerms`

Steps:

1. Read the policy for `edition.loan_type`.
2. If `edition.special_material` is true, multiply `default_hours`, `min_hours`
   and `max_hours` by `special_material_factor` (0.50), rounding **down** to the
   nearest hour, with a floor of 1 hour.
3. If `$requestedHours` is given it must fall within `[min, max]` after the
   factor is applied, otherwise throw `LoanTermsOutOfRange`.
4. Add the resulting hours to `now()`.
5. Push the due date forward to the next opening moment via `BusinessCalendar`.
6. Return `LoanTerms { hours, dueAt, policySnapshot }`.

### BusinessCalendar

`nextOpeningMoment(CarbonImmutable $at): CarbonImmutable`

- If `$at` falls inside `library_hours` for that weekday and the date is not a
  holiday, return `$at` unchanged.
- Otherwise advance to the next `opens_at` of the next open, non-holiday day.
- Recurring holidays match on month and day, ignoring year.
- Guard against infinite loops: if no open day is found within 14 days, return
  `$at` unchanged and log a warning.

Rationale: a 24-hour reference loan started Saturday 17:00 would otherwise fall
due while the library is shut.

### Blocking rules at check-out

Refuse, with a specific message each, when:

- The copy's effective `loan_restricted` is true → "This copy is not for loan."
- The copy status is not `available`.
- The reader's `status` is not `active` → "Reader identity has not been verified yet."
- `blocked_until` is in the future.
- The reader already has `max_active_loans_per_user` active loans.
- The reader has any overdue loan.

### Renewal

Librarian only. Allowed while `renewals_count < policy_snapshot.renewals_allowed`
and the loan is not overdue. Recomputes from `policy_snapshot`, not the live policy.

### Concurrency

Check-out runs inside a transaction with `Copy::whereKey($id)->lockForUpdate()`.
The partial unique index on `loans` is the last line of defence — catch the
unique violation and translate it to a friendly "This copy was just checked out
by someone else." message.

---

## 2. Copy state machine

States: `available`, `on_loan`, `reserved`, `in_repair`, `lost`,
`at_reception`, `in_transit`.

`reserved` exists in the enum but is unreachable today (reservations are out of
scope). Keep it.

| From | To | Who | Trigger |
|---|---|---|---|
| `available` | `on_loan` | librarian, admin | check-out |
| `on_loan` | `at_reception` | librarian, admin | check-in |
| `at_reception` | `in_transit` | shelver, librarian, admin | picks up the batch |
| `in_transit` | `available` | shelver, librarian, admin | confirms shelf location |
| `at_reception` | `available` | librarian, admin | shelved directly at the desk |
| any | `in_repair` | librarian, admin | |
| any | `lost` | librarian, admin | |
| `in_repair` | `available` | librarian, admin | |
| `lost` | `available` | admin | recovered |

Everything else is rejected with `InvalidCopyTransition`.

Rules:
- Every transition writes a `copy_status_transitions` row and touches
  `status_changed_at`.
- `in_transit → available` may also change `location_id`; the shelver is allowed
  to correct a wrong shelf, and both locations are recorded.
- A copy with an active loan can never leave `on_loan` except through check-in
  or an explicit `lost` marking by a librarian.

### Shelver queue

`GET /shelving` — copies where `status IN ('at_reception','in_transit')`,
ordered by `status_changed_at ASC`. Mobile-first. Each card shows the copy code,
title and destination location. Scanning a code advances it one step.

---

## 3. User lifecycle

```
self-registration ──▶ pending_email
      │  email link
      ▼
  pending_identity ──▶ active ──▶ suspended
       librarian verifies      (prepared, fines)
       document in person
```

- Google SSO accounts land in `pending_identity` directly — the email is already
  proven by Google.
- `pending_*` users can browse the catalogue and see availability. They cannot
  borrow. The UI must tell them exactly what to do: "Bring your ID to the front
  desk to activate borrowing."
- Identity verification is a librarian action: search the reader, enter document
  type and number, confirm. Sets `identity_verified_at`,
  `identity_verified_by_id`, `status = active`.
- `document_number` is stored encrypted; `document_hash` = HMAC-SHA256 of the
  normalised number (uppercase, no spaces or punctuation) keyed by `APP_KEY`.
  Front-desk lookup queries the hash, never the encrypted column.
- `member_code` is generated at registration and rendered as a QR on the
  reader's profile. Scanning it at the desk loads the reader.
- Soft-deleted users keep `email`, `google_id` and `document_hash` occupied by
  their plain unique indexes (there is **no** partial unique index on `users`).
  Registration with an email that belongs to a soft-deleted user answers with a
  specific message — "This account was closed. Contact the library
  administrator." — never a generic "taken". The admin user list shows
  soft-deleted users with a "closed" badge and a **restore** action. Restoring
  sets `deleted_at = null` and resets `status` to `pending_identity`, so the
  identity is re-verified in person before the account can borrow again.
- The same rule applies when the librarian verifies identity and the submitted
  document number collides with an existing (active or soft-deleted)
  `document_hash`: the desk gets a conflict message instead of a silent unique
  violation.

---

## 4. Roles and permissions

Roles: `administrator`, `librarian`, `shelver`, `reader`.

Check permissions, never roles.

| Permission | admin | librarian | shelver | reader |
|---|:-:|:-:|:-:|:-:|
| `catalog.view` | ✓ | ✓ | ✓ | ✓ |
| `catalog.view_internal_notes` | ✓ | ✓ | — | — |
| `editions.create` | ✓ | ✓ | — | — |
| `editions.update` | ✓ | ✓ | — | — |
| `editions.delete` | ✓ | — | — | — |
| `copies.create` | ✓ | ✓ | — | — |
| `copies.update` | ✓ | ✓ | — | — |
| `copies.delete` | ✓ | — | — | — |
| `copies.move` | ✓ | ✓ | ✓ | — |
| `copies.transition` | ✓ | ✓ | partial¹ | — |
| `loans.create` | ✓ | ✓ | — | — |
| `loans.return` | ✓ | ✓ | — | — |
| `loans.renew` | ✓ | ✓ | — | — |
| `loans.view_any` | ✓ | ✓ | — | — |
| `loans.view_own` | ✓ | ✓ | ✓ | ✓ |
| `users.manage` | ✓ | — | — | — |
| `users.verify_identity` | ✓ | ✓ | — | — |
| `roles.manage` | ✓ | — | — | — |
| `settings.manage` | ✓ | — | — | — |
| `policies.manage` | ✓ | — | — | — |
| `taxonomy.manage` | ✓ | — | — | — |
| `reports.view` | ✓ | partial² | — | — |

¹ The shelver holds `copies.transition` but the state machine only allows them
`at_reception → in_transit → available`. Enforce this in `CopyStateMachine`,
not in the UI.

² Librarian sees circulation figures, not user management or financial data.

Deletion rules:
- An edition with any copy that has loan history: soft delete only, and only
  when no copy is currently on loan.
- An edition whose copies have no loan history at all: hard delete allowed
  (covers the "created by mistake" case).
- The delete confirmation must state which of the two will happen.

---

## 5. Search

Postgres full text. Query pipeline:

1. Normalise the term. If it looks like an ISBN (10 or 13 digits after stripping
   hyphens), go straight to an exact ISBN match.
2. Otherwise `websearch_to_tsquery('simple', unaccent(:q))` against
   `editions.search_vector`, plus `ILIKE`/`pg_trgm` similarity against author
   names and tag names, unioned and ranked by `ts_rank_cd`.
3. Filters, all optional and combinable: category subtree
   (`path LIKE '/x/%'`), location subtree, copy status, loan type, language,
   availability (`has at least one available copy`).
4. If the result set is empty, record a `search_miss` demand event.
5. If the reader opens an edition with zero available copies, record an
   `unavailable_hit`.

Sort options: relevance (default), title, newest.
