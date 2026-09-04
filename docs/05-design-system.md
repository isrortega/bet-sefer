# 05 — Design system

## Concept

Minimal, professional, serious but friendly. Colour is drawn from library
binding cloth (buckram) and marbled endpaper pigments — not from the
cream-paper-and-terracotta cliché.

Two principles govern everything:

1. **Colour is information.** The interface is quiet and neutral; colour appears
   almost exclusively to communicate copy status. A librarian must read a
   40-row table at a glance.
2. **The serif is reserved for bibliographic content.** Book titles and author
   names are set in the serif. Everything the system says about itself —
   navigation, buttons, labels, table headers — is sans. Users learn the
   distinction without being told.

Boldness is spent in exactly one place: the scan field on the front desk. Every
other surface stays disciplined.

## Base palette

| Token | Hex | Use |
|---|---|---|
| `ink` | `#14211F` | Primary text, sidebar |
| `buckram` | `#14543F` | Primary: buttons, active nav, links |
| `shelf` | `#F5F6F4` | Application background |
| `paper` | `#FFFFFF` | Cards, tables, surfaces |
| `rule` | `#DFE2DD` | Borders, dividers |
| `brass` | `#A8761C` | Single accent — scan field and focus rings only |

Supporting neutrals: `ink-muted #55625E`, `ink-subtle #7C8783`.

No gradients. No coloured shadows. One neutral shadow for overlays only.

## Status palette

| Status | Text | Background |
|---|---|---|
| `available` | `#1E6B4F` | `#E6F1EB` |
| `on_loan` | `#3A5199` | `#E8ECF8` |
| `reserved` | `#6B4A9E` | `#EFE9F7` |
| `at_reception` | `#8A6212` | `#FAF0DC` |
| `in_transit` | `#14707A` | `#E0F0F1` |
| `in_repair` | `#8C4A1E` | `#F8EADF` |
| `lost` | `#8A2B3B` | `#F8E7EA` |

System: `overdue #B3261E`, `success` reuses the available green.

**Never colour alone.** Every status renders as a chip with an icon *and* a text
label, so it survives colour blindness and black-and-white printing.

## Typography

| Family | Role |
|---|---|
| **IBM Plex Sans** | All interface text. Humanist and institutional, with excellent tabular figures for dates and counters |
| **Literata** | Page titles and bibliographic content only (book titles, authors, summaries) |
| **IBM Plex Mono** | ISBNs and copy codes only. Functional: it disambiguates 0/O and 1/l/I when a librarian types a code by hand |

Scale (major third, 1.25): 12 · 14 · 16 · 20 · 25 · 31 · 39 px.
Body 16px / 1.6. Serif body gets 1.7. Measure under 75 characters.
Tabular figures (`font-variant-numeric: tabular-nums`) on every numeric column.

Avoid: all-caps labels, single-word colour accents inside headings, decorative
eyebrow labels above headings, `→` appended to link text.
Sentence case everywhere.

## Density by role

The same app is tuned differently per audience.

| Surface | Rules |
|---|---|
| **Front desk** | Single column. Scan field pinned at top that always reclaims focus. 56px rows. Enter confirms. Fully operable without a mouse |
| **Administration** | Dense 40px table rows, persistent filters, high information density |
| **Shelver** | Mobile first. 48px touch targets. One card per copy, destination location set large |
| **Public point** | Mobile first. Cover, status chip and location occupy the top half. Everything else is secondary |

## Layout

Left-aligned throughout. Nothing centred except empty states.
Two radii only: 6px for controls, 10px for cards. Borders over shadows.

```
Front desk                           Edition detail
┌──────────────────────────────┐    ┌────────┬────────────────────────┐
│  Scan a copy or member code  │    │        │ Title           (serif)│
│  ┌────────────────────────┐  │    │ cover  │ Author · Publisher     │
│  │ ▏                      │  │    │        │ ISBN             (mono)│
│  └────────────────────────┘  │    ├────────┴────────────────────────┤
├──────────────────────────────┤    │ Copies                          │
│ ▸ Reader   Ana Ríos    active│    │ BS-4F7K2Q91  Available  A-3-12  │
│ ▸ Copy     Dune        avail.│    │ BS-9X2M4R1W  On loan    due 14th│
│                              │    │ BS-7T5N8W3K  In transit         │
│      [ Check out · 240 h ]   │    └─────────────────────────────────┘
└──────────────────────────────┘
```

Staff shell: fixed left rail with icon + label, items filtered by permission.
Content column max 1280px.

## Motion

One orchestrated moment: the **status chip transition** when a copy is checked
out or in — a brief pulse from the old status colour to the new one. It shows
the user what changed.

Nothing else moves. No fade-and-slide section entrances, no card hover
animations. `prefers-reduced-motion: reduce` disables the pulse.

## Voice

- Sentence case, plain verbs, active voice.
- An action keeps its name through the whole flow: the button says
  "Check out", the toast says "Checked out".
- Empty states invite: "No copies yet. Add one by ISBN."
- Errors say what happened and what to do, without apologising:
  "This copy is already on loan. Check it in first."
- Name things as users understand them: "Loan period", not "duration_hours".

## Quality floor

- WCAG AA contrast on all text, verified for every status chip on its background.
- Visible focus: 2px `brass` ring with 2px offset, on every interactive element.
- Full keyboard operation on the front desk, including check-out confirmation.
- Responsive to 360px.
- All tokens declared as CSS custom properties in Tailwind's `@theme` block, so
  the staff app and the public page share one source of truth.

**Dark mode is out of scope today.** Because tokens are CSS variables, adding it
later is a scoped change. Note this in the README.

## Brand

Wordmark only: "Bet-Sefer" set in Literata, with a thin vertical rule to its
left evoking a book spine. No illustration, no generic open-book icon.
Icons throughout: Lucide.
