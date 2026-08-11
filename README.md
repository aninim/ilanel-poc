# ILANEL POC — WooCommerce

Proof-of-concept for moving ILANEL from Squarespace to WordPress +
WooCommerce. Two pages: **product page + collection/range page**.

**The goal is visual:** show that ILANEL can be built to look like
[Ross Gardam](https://www.rossgardam.com.au/). The layout, type scale and
page anatomy are taken from their live markup and stylesheet, not
approximated.

## ▶ Run it — one click, no install

```
https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/aninim/ilanel-poc/master/dist/blueprint.json
```

Boots WordPress + WooCommerce in your browser (WebAssembly), installs the
theme and plugin, and seeds four real ILANEL products. **Nothing persists** —
it is a demo, not a host.

After changing code: `node scripts/build-playground-blueprint.js` then push.

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
| Plugin + theme code | ✅ Complete, `php -l` clean |
| RG-style product + range pages | ✅ Hero carousel, storytelling, configurator, downloads, discover |
| Product / CollectionPage / Breadcrumb schema | ✅ Verified valid JSON-LD |
| All four products fully dressed | ✅ Comet, Comet Stardust, Kahdu, Dais |
| Real ILANEL photography | ✅ Sideloaded from their CDN |
| Running instance | ✅ **WordPress Playground — see above** |
| 3D viewer | ⚙️ `<model-viewer>` wired; needs a `.glb` from the studio |
| Prices / SKUs | ⚠️ **Invented for the demo — do not quote** |

Schema output was verified by running the generators against stubbed
WordPress/WooCommerce in PHP 8.3 — not by inspection. See `docs/VERIFICATION.md`.

---

## Hosting a real instance

Playground is the demo vehicle and needs nothing. A **persistent** install
still needs PHP + MySQL, which neither `dads42.com` (Firebase, static) nor
Vercel (Node) provides. Options, cheapest first:

1. **Managed WP host** (~AU$10–30/mo) — SiteGround, Cloudways, Kinsta.
2. **A VPS you already own.**
3. **Local install** — LocalWP. See `docs/INSTALL.md`.

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
