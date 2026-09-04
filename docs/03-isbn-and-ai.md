# 03 — ISBN ingestion and AI classification

## Flow

The librarian types or scans an ISBN first. Everything else is pre-filled and
editable.

```
ISBN entered
   → validate checksum (ISBN-10 or ISBN-13), normalise to ISBN-13
   → already in the catalogue?  ── yes ──▶ "Add another copy" shortcut
   → no
   → Redis cache hit?           ── yes ──▶ return
   → metadata_lookups fresh?    ── yes ──▶ return, warm Redis
   → fetch Open Library + Google Books in parallel (Http::pool)
   → merge field by field
   → persist raw payloads to metadata_lookups
   → AI classification (category + tags)
   → return a prefilled, fully editable form
```

## Providers

Interface `App\Services\Metadata\MetadataProvider`:

```php
public function name(): string;
public function fetch(string $isbn13): ?BookMetadata;
```

Implementations today:

| Provider | Endpoint | Key | Timeout |
|---|---|---|---|
| `OpenLibraryProvider` | `https://openlibrary.org/api/books?bibkeys=ISBN:{isbn}&format=json&jscmd=data` | none | 3s |
| `GoogleBooksProvider` | `https://www.googleapis.com/books/v1/volumes?q=isbn:{isbn}` | optional `GOOGLE_BOOKS_API_KEY` | 3s |

Both are called **in parallel**, not as a fallback chain. Overall budget 8
seconds; the UI shows a "Looking up ISBN…" state with a visible
"Enter manually" escape hatch from the first second.

### Merge precedence

| Field | Preferred source |
|---|---|
| title, subtitle | Open Library, fall back to Google Books |
| authors | Open Library (better structured), fall back to Google Books |
| publisher, published_year | Open Library |
| page_count | Open Library, fall back to Google Books |
| language | Google Books |
| summary | **Google Books** (Open Library rarely has one) |
| cover | **Google Books** `imageLinks.thumbnail` at highest available size, fall back to Open Library cover API |
| dimensions | Open Library only |
| subjects → tag candidates | both, union, deduplicated |

Set `metadata_source = merged` when both answered, otherwise the single
provider's name, or `manual` if none did.

### Covers

Download server-side and upload to R2 under `covers/{isbn13}.jpg`. Never
hotlink. Resize to max 600px on the long edge. If the download fails, save the
edition without a cover — this must never block the form.

### Resilience

- Per-provider timeout 3s, one retry with 200ms backoff.
- Circuit breaker in Redis: 5 consecutive failures opens the provider for 5
  minutes; the app degrades to the remaining provider.
- The lookup endpoint is rate-limited per user (see `docs/06-security.md`).
- The ISBN is validated before any outbound request — this is user input driving
  an outbound HTTP call, so treat it as an SSRF surface. URLs are built from
  constants, never from user-supplied hosts.

### Caching

- Redis key `isbn:{isbn13}` TTL 30 days.
- `metadata_lookups` row per `(isbn_13, provider)` with `expires_at` 90 days,
  as persistent cache and audit trail.
- Registering copy #4 of the same book must trigger zero outbound requests.

### Future providers

`IsbnDbProvider` and `ApifyProvider` implement the same interface but run
**asynchronously** in a queued enrichment job, never in the request path. Apify
is an actor platform, not a REST API — a run has to be started and polled, which
is why it must not sit in front of a librarian waiting at the desk.

---

## AI classification

The one AI feature in scope. It suggests, it never decides.

Provider: **OpenRouter** (`POST https://openrouter.ai/api/v1/chat/completions`).
The model is configurable and defaults to `deepseek/deepseek-v4-flash`:

```dotenv
OPENROUTER_API_KEY=
AI_MODEL=deepseek/deepseek-v4-flash
AI_TIMEOUT=6
AI_CLASSIFICATION_ENABLED=true
```

- Enabled only when `OPENROUTER_API_KEY` is set and `AI_CLASSIFICATION_ENABLED`
  is true; otherwise the suggestion step is skipped silently and the manual form
  still works.
- Request uses JSON mode (`response_format: { type: 'json_object' }`); a missing
  key, timeout (6s) or invalid JSON is treated as "no suggestion".
- `ai_model` on the edition stores the model string from the env, so it is
  auditable later.

`App\Services\Classification\ClassifySuggestedTaxonomy`

Input: title, subtitle, summary, publisher, subject strings from providers, plus
the current category tree (leaf paths only) and existing tag vocabulary.

Output, strict JSON:

```json
{
  "category_path": "/Science/Physics/Astrophysics",
  "confidence": 0.82,
  "tags": ["cosmology", "black holes", "popular science"],
  "reasoning": "one short sentence"
}
```

Rules:

- The model must choose from the **existing** category tree. If nothing fits,
  return `null` and let the librarian pick.
- Maximum 6 tags. Prefer reusing existing tags over inventing new ones; new tags
  are created with `source = 'ai'`.
- The suggestion is rendered in the form as pre-selected values with a small
  "Suggested" marker the librarian can clear. Nothing is saved without their
  confirmation.
- Store `ai_classified_at` and `ai_model` on the edition.
- If the model call fails or returns invalid JSON, skip silently — the form
  still works.
- Timeout 6s, run after the metadata merge so it can use the summary.
- No summary and no subjects available → skip the call entirely, do not burn a
  request on a title alone.

Never let the model invent bibliographic facts. It classifies; it does not fill
in title, author, publisher or year.
