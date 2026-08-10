# ILANEL POC — WooCommerce

Engineering proof-of-concept for moving ILANEL from Squarespace to
WordPress + WooCommerce. Scope agreed with the studio: **product page +
collection/range page**, engineering only.

**The studio supplies visual design, photography and final copy.** Styling here
is deliberately neutral placeholder — judge the structure, not the appearance.

---

## Why this exists

ILANEL's Squarespace product pages are **`portfolio-item` records in a
`portfolio-grid-overlay` collection**, not native store products. Squarespace
emits Product schema only for native store pages, so on the current site:

| Capability | Ross Gardam (WooCommerce) | ILANEL (Squarespace portfolio) |
|---|---|---|
| Product schema | ✅ | ❌ **impossible** |
| Offer schema (price/availability) | ✅ | ❌ **impossible** |
| Filters | ✅ | ❌ |
| Breadcrumbs | ✅ | ❌ |

This is a **page-type limit, not a settings toggle.** No amount of
configuration fixes it. This POC demonstrates all four capabilities working.

---

## What's here

```
plugins/ilanel-poc-core/     Taxonomy, product meta, schema, breadcrumbs
themes/ilanel-poc/           Theme with Woo template overrides
scripts/seed-products.php    WP-CLI seeder using real ILANEL content
data/products.json           Real copy from the live site (2026-08-10 crawl)
docs/                        Architecture, install, open questions
```

### The four demonstrations

1. **Product schema** — `class-ilanel-schema.php` emits `Product` with a full
   `Offer` node (price, currency, availability, `priceValidUntil`, seller).
2. **Range filters** — `ilanel_poc_render_range_filters()`, works without JS.
3. **Breadcrumbs** — visible trail and `BreadcrumbList` JSON-LD from one
   source, so they can't drift.
4. **Single-source specs** — finishes, lead time and origin are product meta,
   not per-page free text. Change once, updates everywhere.

Plus one structural fix: **exactly one `<h1>` per page, by construction.** The
live site has 148 surplus `<h1>` tags across 79 pages because section headers
were formatted as Heading 1. Here every non-title heading is `<h2>` in the
template, so content entry cannot reintroduce the defect.

---

## Status

| Item | State |
|---|---|
| Plugin + theme code | ✅ Complete, `php -l` clean on all 12 files |
| Product schema | ✅ Verified — valid JSON-LD, all required fields |
| CollectionPage schema | ✅ Verified — valid JSON-LD with ItemList |
| BreadcrumbList schema | ✅ Verified — sequential positions |
| Real content seeded | ✅ Comet, Comet Stardust, Kahdu, Dais |
| **Running instance** | ❌ **Blocked — no host. See below.** |
| Prices / SKUs | ⚠️ Placeholder — must come from Commerce |
| Product images | ⚠️ Not included — studio supplies photography |

Schema output was verified by running the generators against stubbed
WordPress/WooCommerce in PHP 8.3 — not by inspection. See `docs/VERIFICATION.md`.

---

## ⚠️ Hosting is unresolved

The handoff assumed `dads42.com` could host this. **It can't as configured.**

`dads42.com` is **Firebase Hosting + Cloudflare DNS** — static hosting only.
Firebase Hosting serves static files; it does not run PHP or MySQL. The other
documented pattern (Netlify) doesn't run WordPress either.

So the "full local WP+Woo so it actually runs and is clickable" requirement is
**not met**, and can't be met by either existing hosting pattern. This machine
also has no PHP, MySQL or Docker, and no admin rights to install a database
server — and putting one on a company PC would cross the safety boundary in
`CLAUDE.md` regardless.

**Options, cheapest first — needs a decision:**

1. **Managed WP host** (~AU$10–30/mo) — e.g. SiteGround, Cloudways, Kinsta.
   Fastest path to clickable; deploy this repo and run the seeder.
2. **A VPS you already own** — if there's a real server behind `dads42.com`
   beyond the Firebase static sites, WP+Woo installs there normally.
3. **Local install on a personal machine** — free, avoids the company-PC
   issue entirely. `docs/INSTALL.md` has the steps.
4. **WordPress Playground** (`playground.wordpress.net`) — runs WP+Woo in the
   browser via WASM, zero install. Good enough to *demo* the pages; not a real
   host, and state doesn't persist.

Option 4 is the fastest way to see it working; option 1 is the right answer if
this proceeds past POC.

---

## Install

See `docs/INSTALL.md`. Short version, once you have WP+Woo:

```bash
# from the WordPress root
cp -r /path/to/ilanel-poc/plugins/ilanel-poc-core wp-content/plugins/
cp -r /path/to/ilanel-poc/themes/ilanel-poc       wp-content/themes/

wp plugin activate ilanel-poc-core
wp theme activate ilanel-poc
wp eval-file /path/to/ilanel-poc/scripts/seed-products.php
wp rewrite flush
```

Then visit `/our-range/pendants/` and any product page, and view source for
the JSON-LD.

---

## Hard locks (inherited from `ilanel-studio`)

- **Nothing here touches the live site.** This is a separate stack.
- Live URLs, slugs and canonicals are never to change — 1,110 of ~1,130
  backlinks point at the ilanel.com homepage.
- Live Commerce inventory (73 records with real order history) is never edited.
- Prices in `data/products.json` are **placeholders**. Do not quote them.
