# ILANEL — WordPress + WooCommerce

Building ILANEL's real site: WordPress + WooCommerce, replacing Squarespace
entirely, with real online checkout. **This started as a proof-of-concept
and is now the actual launch build** — see `docs/LAUNCH-PLAN.md` for the
full plan, phases, and what's still open.

## ▶ See it live

**Working demo, real content:** <https://ilanel.dads42.com> — a real,
persistent WordPress + WooCommerce install on Cloudways, not a static
mockup. 34 products, 51 projects, 13 Light Art works, 42 News posts, all
real ILANEL content and (for 4 products so far) real prices.

This runs on Oren's own domain (`dads42.com`), deliberately not ILANEL's —
it's the pre-launch build environment. `docs/LAUNCH-PLAN.md` Phase 5 covers
the actual cutover to `ilanel.com`.

One-click ephemeral demo (no login, nothing persists — for a quick look,
not for review):

```
https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/aninim/ilanel-poc/master/dist/blueprint.json
```

---

## Why WordPress + WooCommerce, not Squarespace

ILANEL's Squarespace product pages are **`portfolio-item` records in a
`portfolio-grid-overlay` collection**, not native store products.
Squarespace emits Product schema only for native store pages, so on
Squarespace:

| Capability | Ross Gardam (WooCommerce) | ILANEL (Squarespace portfolio) |
|---|---|---|
| Product schema | ✅ | ❌ **impossible** |
| Offer / real checkout | ✅ | ❌ **impossible** |
| Filters | ✅ | ❌ |
| Breadcrumbs | ✅ | ❌ |

Page anatomy, type scale and spacing are read from Ross Gardam's live
markup and stylesheet (see `docs/ARCHITECTURE.md`) — not approximated —
because the studio's brief was that the site look and feel like a real,
established design-lighting brand.

---

## What's here

```
plugins/ilanel-poc-core/     Taxonomy, product meta, schema, breadcrumbs,
                              journal (News), product URL routing
themes/ilanel-poc/           Theme with Woo template overrides
scripts/seed-products.php    WP-CLI seeder — products, real Commerce prices
scripts/seed-journal.php     WP-CLI seeder — 42 real News posts
data/                        Real scraped content: products, projects,
                              light-art, journal
docs/                        Launch plan, architecture, install, open
                              questions
```

### What's demonstrated and working, live

1. **Product schema** — `class-ilanel-schema.php` emits `Product` with a
   full `Offer` (simple products) or `AggregateOffer` (variable products —
   e.g. Comet's real $3,195.50–$7,437.72 range).
2. **Range filters** — plain `<a>` links, works without JS.
3. **Breadcrumbs** — visible trail and `BreadcrumbList` JSON-LD from one
   source (also live on Projects and Light Art, not just products) —
   can't drift apart.
4. **Single-source specs** — finishes, lead time and origin come from
   product meta and render on the page; change once, updates everywhere.
5. **News** (`/news/`) — 42 real posts migrated from Squarespace, with
   pagination, matching ilanel.com's real URL structure.
6. **Product ↔ project cross-linking** — Kahdu's page links to Chatswood
   Hill Tavern; that project page links back — one relation, read from
   both ends, can't disagree.

Plus: **exactly one `<h1>` per page, by construction.** The live
Squarespace site has 148 surplus `<h1>` tags across 79 pages (section
headers formatted as Heading 1); here every non-title heading is `<h2>` in
the template, so content entry can't reintroduce the defect.

### What's still open

Real checkout (payment gateway), real prices for 30 of 34 products
(needs an authenticated Squarespace Commerce API key), a handful of
static pages (`/about`, `/faq`, `/trade`, etc.), and the actual `ilanel.com`
cutover. Full detail and sequencing: **`docs/LAUNCH-PLAN.md`**.

---

## Install

See `docs/INSTALL.md` for the full walkthrough including the config steps
the seeders don't automate (product URL base, front-page setup). Short
version, once you have WP + WooCommerce:

```bash
cp -r /path/to/ilanel-poc/plugins/ilanel-poc-core wp-content/plugins/
cp -r /path/to/ilanel-poc/themes/ilanel-poc       wp-content/themes/

wp plugin activate ilanel-poc-core
wp theme activate ilanel-poc
wp eval-file /path/to/ilanel-poc/scripts/seed-products.php
wp eval-file /path/to/ilanel-poc/scripts/seed-journal.php
wp rewrite flush
```

Then visit `/our-range/pendants/` and any product page, and view source for
the JSON-LD.

---

## Hard locks — still apply

- Never write to Squarespace — every read stays read-only until DNS
  actually repoints at cutover (`docs/LAUNCH-PLAN.md` Phase 5).
- Never change a live URL, slug or canonical without a 301 in place first
  — 1,110 of ~1,130 backlinks point at the `ilanel.com` homepage.
- Never edit live Commerce inventory — 73 records with real order history.
- Photography is ILANEL's own, pulled from their CDN or sideloaded onto
  this install. Not a stock-photo substitute.
