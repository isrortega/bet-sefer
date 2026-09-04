# 06 — Security

Security is a stated priority of this build. These are requirements, not
suggestions.

## Authentication

- Local email + password, plus Google SSO via Laravel Socialite.
- Google OAuth needs no Workspace account. Redirect URIs to register:
  - `https://betsefer.appenlaweb.com/auth/google/callback`
  - `http://localhost/auth/google/callback`
- SSO accounts are matched to an existing user **by verified email only**. If a
  local account exists with that email, link it; never create a duplicate.
- **Never trust the identity provider for roles.** Every SSO account is created
  with the `reader` role and `pending_identity` status. Privilege comes from an
  administrator inside the app.
- Session driver: database. Regenerate the session ID on login and on privilege
  change. Invalidate other sessions on password change.
- Password rules: Laravel's `Password::defaults()` with `min(12)` and
  `uncompromised()` in production.

## Authorization

- Every route with side effects has a Policy. No route relies on the UI hiding a
  button.
- Check permissions, never role names.
- Copy state transitions are gated inside `CopyStateMachine`, not in the
  controller — the shelver's partial permission is meaningless anywhere else.
- Route model binding resolves by `ulid` or human code, never by numeric id, so
  IDs cannot be enumerated (IDOR).
- Mass assignment: models declare `$fillable` explicitly. No `$guarded = []`.

## Personal data

- `document_number` and `phone` use Laravel's `encrypted` cast.
- `document_hash` is `hash_hmac('sha256', $normalised, config('app.key'))` and
  carries the unique index. Front-desk search queries the hash.
- Never log, dump or serialise these fields. Add them to
  `$hidden` on the model.
- Public IP addresses are stored only as a salted hash in `demand_events`.
- Reader loan history is visible to the reader, librarians and admins — nobody
  else, ever.

## Public surface

The unauthenticated information point is the highest-risk area because it is the
only place an anonymous visitor touches the database. See
`docs/04-public-info-point.md`.

- Public payloads are built from explicit allow-lists, not by hiding fields.
- No individual loan dates. Aggregates only.
- Copy codes are random, never sequential.

## Rate limits

| Surface | Limit |
|---|---|
| Login | 5 per minute per email + IP |
| Password reset request | 3 per hour per email |
| Public info point | 60 per minute per IP |
| External ISBN lookup (public) | 10 per minute per IP |
| ISBN lookup (staff) | 30 per minute per user |
| Acquisition suggestion | 5 per hour per IP |
| API/general authenticated | 120 per minute per user |

## HTTP hardening

- HTTPS enforced, HSTS with `includeSubDomains`.
- Content-Security-Policy: `default-src 'self'`, no `unsafe-inline` for scripts.
  Fonts self-hosted, so no third-party font origin is needed.
- `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`,
  `Referrer-Policy: strict-origin-when-cross-origin`.
- CSRF on all state-changing routes (Laravel default — do not exclude routes).
- Signed URLs for private R2 objects. Covers may be public; nothing else is.

## Outbound requests

The ISBN lookup takes user input and triggers an HTTP request. Treat it as an
SSRF surface: validate the ISBN checksum first, build URLs from constants, never
follow redirects to arbitrary hosts, cap response size, enforce timeouts.

## Uploads

Cover images only. Validate MIME by content, not by extension. Re-encode with
Intervention Image before storing — that strips EXIF and any embedded payload.
Max 5MB. Store under a generated key, never the original filename.

## Audit

`spatie/laravel-activitylog` records: loan creation and return, copy state
transitions, edition create/update/delete, user role changes, identity
verification, policy and settings changes. Each entry keeps actor, timestamp and
before/after values. The log is append-only — no UI or route may delete from it.

## Secrets

- Nothing secret in the image or the repository. `.env.example` carries empty
  placeholders only.
- Production secrets are GitHub Actions secrets injected at deploy time.
- `APP_DEBUG=false` in production. Verify this before the deploy is considered
  done — a debug page leaks environment variables.
- Containers run as a non-root user.
- `composer audit` and `npm audit` run in CI and fail the build on high severity.

## Deliberate exposure

Nothing is exposed for convenience. Mailpit is **not deployed to production**;
the demo relies on seeded, already-active accounts plus Brevo for real mail.
