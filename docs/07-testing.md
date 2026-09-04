# 07 — Testing

Tests are a stated priority. Aim for depth on the rules that matter rather than
coverage everywhere.

Framework: Pest. Database: a real Postgres instance in CI, with
`RefreshDatabase`. No SQLite — the schema uses partial unique indexes,
generated columns and `tsvector`, none of which SQLite supports.

## What must be tested

### Unit — `tests/Unit`

**`LoanPolicyResolver`** — the full matrix:
- each loan type at default, min and max
- special material halves each of those, rounds down, floors at 1 hour
- a requested duration outside the range throws
- `policy_snapshot` contains the post-factor values

**`BusinessCalendar`**
- a due date landing inside opening hours is untouched
- Saturday 17:00 + 24h pushes to Monday opening
- a fixed holiday pushes forward
- a recurring holiday matches in any year
- no open day within 14 days returns the input and logs

**`CopyStateMachine`**
- every allowed transition succeeds and writes a transition row
- every disallowed transition throws `InvalidCopyTransition`
- a shelver cannot perform `on_loan → at_reception`
- `in_transit → available` records both locations

**Codes** — `BS-` code and `member_code` generation: format, check digit,
rejection of a mutated character.

**ISBN** — checksum validation for ISBN-10 and 13, normalisation, hyphen
handling.

**Metadata merge** — field precedence with both providers, with only one, with
neither.

### Feature — `tests/Feature`

**Circulation**
- check-out happy path: copy becomes `on_loan`, due date correct, transition
  and audit rows written
- check-out refused for: restricted copy, non-`available` copy, `pending_identity`
  reader, reader at the loan cap, reader with an overdue loan — each with its own
  test and its own message
- check-in moves the copy to `at_reception`
- renewal within limits succeeds; beyond limits and when overdue, fails
- **concurrency**: two simultaneous check-outs of one copy — exactly one wins,
  the other gets a friendly conflict, and no second `loans` row exists

**Catalogue**
- create edition by ISBN with mocked providers
- second lookup of the same ISBN makes zero HTTP calls (cache proven)
- provider timeout still returns a usable manual form
- hard delete allowed with no history; soft delete when history exists; delete
  refused while a copy is on loan

**Authorization — the negative matrix**
For each protected route, assert 403 for every role that should not reach it.
This is the primary evidence that the permission model works. At minimum:
- shelver cannot check out, cannot create editions, cannot manage users
- librarian cannot delete editions, cannot manage roles or settings
- reader cannot reach any `/staff` route
- a reader cannot read another reader's loan history

**Public info point**
- the response body for an anonymous visitor contains **no** user name, email,
  document or member code — assert on the serialised payload, not on the view
- no individual loan dates are present
- internal notes absent
- an unknown code 404s
- rate limit returns 429 after the threshold

**Auth**
- SSO account is created with the `reader` role and `pending_identity`, even if
  the provider payload claims otherwise
- identity verification by a librarian activates the account
- login throttling

### Architecture — Pest arch presets

- controllers do not use `DB::` directly
- models are not referenced in `resources/js`
- no `dd`, `dump`, `ray`, `var_dump` anywhere
- everything in `app/Actions` is final and has a `handle` method

## Test data

Factories for every model. States: `EditionFactory::reference()`,
`::specialMaterial()`, `CopyFactory::onLoan()`, `UserFactory::librarian()`,
`::pendingIdentity()`. HTTP is always faked with `Http::fake()` using recorded
fixtures in `tests/Fixtures/metadata/`. **No test may hit the network.**

## Gates

`make check` = `pint --test` + `larastan` (level 6, rising if time allows) +
`pest`. CI runs the same command and blocks the deploy on failure.

Target: 85% line coverage overall, and 100% on `app/Services/Circulation` and
`app/Policies`.

## If time runs short

Cut in this order: architecture tests → catalogue edge cases → unit breadth.
**Never cut** the authorization negative matrix, the public-payload privacy
tests, or the concurrency test. Those three are what the security priority
actually means in practice.
