# 04 — Public information point

An unauthenticated page a visitor reaches by scanning the QR on a book, or by
typing an ISBN at a kiosk. It answers one question: *can I take this book, and
where is it?*

## Routes

| Route | Purpose |
|---|---|
| `GET /i/{code}` | A specific copy, by its `BS-XXXXXXX` code. This is the QR target |
| `GET /lookup` | ISBN search box |
| `GET /lookup/{isbn}` | Edition view by ISBN |

All are mobile-first. The QR is scanned with a phone.

## What is shown

- Cover, title, subtitle, authors, publisher, year, edition statement, language.
- Category and tags.
- Physical data: pages, format, dimensions.
- Summary.
- Status chip and human-readable location ("Floor 2 · Room B · Aisle 4 · Shelf 12").
- Loan type and whether the item is for loan at all.
- For an ISBN lookup: how many copies exist and how many are available.
- If nothing is available: **estimated availability date**, computed from the
  earliest `due_at` among active loans on that edition.
- Aggregate circulation only: "Borrowed 12 times in the last year."

## What is never shown

- Any borrower's name, email, document, member code, or any identifier.
- Individual loan dates. **Not** "returned on 3 August" — that is
  pseudonymous at best; in a small library it identifies people.
- Internal librarian notes.
- Acquisition cost.
- Any control that creates, modifies or reserves anything.

The response for public routes is built by a dedicated
`PublicEditionResource` / `PublicCopyResource`. Do not reuse the staff
resources with fields hidden — build the public payload from an allow-list, so
that adding a column to the model can never leak it.

## ISBN not in the catalogue

Look the ISBN up through the normal provider pipeline and show what it finds,
clearly framed:

> **Not available at this library.**
> We found this title in public catalogues. You can suggest we acquire it.

A "Suggest acquisition" button records an `acquisition_suggestion` demand event
with the ISBN. No account required, rate-limited by hashed IP.

## Role-aware enrichment

The QR always points at the same URL. If the visitor happens to be
authenticated, the same page renders more:

| Viewer | Extra |
|---|---|
| Anonymous | nothing beyond the above |
| Reader | "In your current loans" marker if applicable |
| Shelver | destination location, "Mark as shelved" action if the copy is in the shelving queue |
| Librarian / admin | internal notes, current borrower, check-in / check-out actions, edit link |

One physical label, four experiences.

## Codes and QR

- Copy code format `BS-` + 7 Crockford base32 characters + 1 check digit.
  Crockford excludes I, L, O and U, so it survives handwriting and OCR.
- Codes are **random, never sequential** — sequential codes let anyone enumerate
  the entire collection.
- QR encodes the absolute URL `https://betsefer.appenlaweb.com/i/{code}`.
- Generated with `simplesoftwareio/simple-qrcode` (SVG output).
- A printable label sheet at `GET /staff/copies/labels?ids=...` renders an A4
  grid of QR + code + short title, sized for standard adhesive label stock, with
  a print stylesheet.
- Reader member cards use the same generator against `member_code`, on the
  reader's own profile page only.

## Hardening

- Rate limit: 60 requests/minute per IP on public routes, 10/minute on the
  external ISBN lookup path.
- No PII in query strings.
- Responses cached 60 seconds; status and availability are recomputed on cache
  miss only, which is acceptable for a browsing view.
- `X-Robots-Tag: noindex` on `/i/{code}` (copy-level pages are not content), but
  `/lookup` may be indexable.
- A wrong or unknown code returns a friendly 404 page, never a hint about
  whether the code format was valid.
