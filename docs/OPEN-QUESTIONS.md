# Open questions

Things this build needs that can't be answered from the code or the scraped
data alone — most need a studio decision, a credential only ILANEL/Oren
holds, or new photography. **Superseded in priority/sequencing by
`docs/LAUNCH-PLAN.md`** — that's the plan; this file is the detail behind
each open item it references.

---

## 1. Prices — ✅ RESOLVED (2026-08-11)

Real prices now come from the authenticated Squarespace Commerce export and
live in `data/products.json` → `commerce.variants[].price`:

| Product | Variants | Real range |
|---|---|---|
| Comet | 36 (Size × Color × Glass) | $3,195.50 – $7,437.72 |
| Kahdu | 24 (Color × Shape) | $1,870.00 – $3,080.00 |
| Dais | 4 (Glass) | $2,094.40 – $2,818.20 |
| Comet Stardust | 3 (Size) | $3,572.80 – $4,188.80 |

The old invented figures (`2450.00` etc.) are gone from both seeders.

`scripts/seed-products.php` (the WP-CLI seeder) was stale until 2026-08-12 —
it still built `WC_Product_Simple` with the demo prices while the blueprint
seeder built variable products. It now mirrors the blueprint generator:
variable products, real attributes and variants, `WC_Product_Variable::sync()`,
and it seeds projects and the product↔project relation too.

## 2. SKUs — ✅ RESOLVED (2026-08-11)

Real SKUs ship with the variant data (e.g. `SQ0540475`). The `DEMO-*` override
was removed from the blueprint seeder, and as of 2026-08-12 the WP-CLI seeder
matches — `DEMO-*` is now only used for products with no variant data, of
which there are currently none.

## 3. Finishes — not captured

The live pages reference a "ColoUr Disclaimer" but the finish lists themselves
are in the spec PDFs, which the crawl recorded as URLs but did not parse:

- `ILANEL_COMET_PENDANT_2026_V2-c772.pdf`
- `ILANEL_COMET_STARDUST_PENDANT_23V1.pdf`
- `ILANEL_KAHDU_PENDANT_2026_V1.pdf`
- `ILANEL_DAIS_WALL_2026_V2.pdf`

**Needed:** the finish list per product. Extracting from the PDFs is
straightforward once someone confirms they're current.

### 3a. Per-finish photography keyed to the Commerce attributes — ✅ MOSTLY RESOLVED (2026-08-13)

Found while fixing the configurator preview (2026-08-12). The swatch images in
`finishes` and the Commerce `Color` axis are **different vocabularies** — an
*exact*-string match usually cannot pair a variation with a photograph of its
own finish:

| Product | Swatch names | Commerce colour axis | Exact matches |
|---|---|---|---|
| Kahdu | Natural, Black, Brown | Black, Brown, … | 8/24 |
| Comet | Golds, Teals, Whites, Grey & Gold, Amber & Bronze | "Brushed Brass - Patina (Bronze)" etc. | 0/36 |
| Dais | Brass, Bronze | Glass axis only, no colour axis | 0/4 |
| Comet Stardust | Black, Brass | Size axis only | n/a |

A substring match was tried and **deliberately rejected** (2026-08-12):
"Amber" matched Comet's *Glass* axis and gave 18 variations the same wrong
photograph. A confident wrong picture is worse than no change.

**2026-08-13 — actually fixed, not just diagnosed.** The plugin already had
`ILANEL_Product_Meta::swatch_for_option()`: a token match restricted to
colour/finish axes specifically (the axis restriction is what makes the
looser matching safe — it's what the rejected 2026-08-12 substring attempt
was missing). It tokenises both sides, strips shared boilerplate words
("brushed", "brass", "patina"…), and matches on what's left — "Amber &
**Bronze**" against "Brushed Brass - Patina (**Bronze**)" shares "bronze"
as a real, meaningful token, not a coincidence. The function was already
wired into the live template's swatch picker, but **neither seed script
ever called it** for the variation preview image — both used plain exact
match. Fixed in `scripts/seed-products.php`
(`ILANEL_Product_Meta::swatch_for_option()` now resolves each variation's
image instead of `isset($map[$exact_value])`).

Verified live: Comet went from 0/36 to **12/36** variations with a real
per-finish photo. The remaining 24 have no shared token with any of
Comet's 3 real Color values and correctly fall back to the parent hero
image — that residual gap **is** a genuine photography gap (some finishes
plausibly have no dedicated photograph at all yet), not a matching bug.
`build-playground-blueprint.js` (the Playground-only generator) still uses
exact match — worth porting the same fix there for consistency, though it
doesn't affect the real install.

## 4. Photography — ✅ RESOLVED (superseded)

No longer accurate. The POC renders **ILANEL's own photography**, pulled from
their Squarespace CDN — product heroes, galleries, story rows, finish swatches
and project installation shots. Nothing renders as a grey placeholder.

⚠️ That photography is ILANEL's, served from their CDN and used here for a
demo. Flag it before this repo is shared any wider, and host the assets
properly for anything beyond a POC.

The outstanding gap is narrower and lives in §3a: photography **per finish**,
named to match the Commerce attribute values.

## 5. Range structure — assumed

The POC seeds five ranges (Pendants, Wall Lights, Chandeliers, Lamps,
Editions), inferred from product type labels and the live site's own
`/editions/` split — not from a studio taxonomy.

Ross Gardam runs **14 ranges and 24 collections** across 67 products. ILANEL's
real range structure is a studio decision and will change the archive pages.

**2026-08-13 — Editions confirmed as a genuinely separate section, not
just a range filter.** Reading ilanel.com's live nav and product links
directly: 23 products live at `/lighting-design-collections/{slug}`, and
the 11 Editions pieces live at a wholly separate top-level
`/editions/{slug}` — not nested under the main catalogue path at all. The
POC's product URLs matched neither before this was found (everything sat
on WooCommerce's default `/product/` base). Fixed: `product_base` is now
`lighting-design-collections`, and `class-ilanel-product-urls.php`
overrides Editions products onto `/editions/{slug}/` via the `editions`
range term. See INSTALL.md §Product base for the install-time config this
depends on (not automated by the seeder — a manual `wp option update`
step, same as the front-page setup below).

## 6. Product vs variant — unresolved

Bears directly on the POC's data model:

- Live has **two** Supernova products (Round and Linear) where staging has one.
- **Stiria vs Stella** may be a rename or two products.
- 73 inventory records vs 25 pages suggests many records are variants.

If ILANEL's catalogue is largely **variable products** (one product, many
finishes/sizes) rather than simple ones, the schema needs `AggregateOffer`
instead of a single `Offer`, and the seeder needs rewriting for
`WC_Product_Variable`.

**This is the one open question that could change the code**, not just the
data. Codex's browser pass (A1, A2, A6 in `CODEX-BRIEF-2026-08-10.md`) should
settle it.

## 7. Journal / projects cross-linking — ✅ RESOLVED

Both are built and live. Products↔projects cross-linking: Kahdu ↔ Chatswood
Hill Tavern round-trips correctly (`ILANEL_Projects`, verified in a real
WordPress, not just seed data). Journal/News: 42 posts migrated from the
scrape, live at `/news/` — see the 2026-08-13 entries below and
`docs/HANDOVER-2026-08-13.md`.

## 8. Hosting — ✅ RESOLVED (2026-08-13)

Live at `https://ilanel.dads42.com` on Cloudways (DigitalOcean, Sydney,
WordPress + WooCommerce). `dads42.com` is Oren's own domain, deliberately
not ILANEL's — this is a review/demo instance, not the production
deployment, chosen so nothing here risks ilanel.com's real SEO while
product prices are still 30/34 placeholder. Moving to a real ILANEL
subdomain later is a domain swap on the same server, not a migration.

## 9. Static pages — ✅ RESOLVED (2026-08-14)

Built and live at `/about/`, `/faq/`, `/trade/`, `/warranty/`, `/privacy-policy/`,
`/terms-and-conditions/`, `/contact/` on `ilanel.dads42.com`. Real copy scraped
read-only from ilanel.com (`scripts/scrape-static-pages.js` → `data/static-pages.json`),
seeded as native WordPress pages (`scripts/seed-static-pages.php`), rendered via
a new `page.php` template matching the site's own type system. FAQ's Squarespace
accordion block (question/answer pairs, not plain paragraphs — a different
extraction path than every other scraped content type here) renders as native
`<details>/<summary>`, no JS dependency. Footer and product-page `href="#"`
links now point to these. `/3d-models`, `/linktree`,
`/customer-satisfaction-survey` still not built — orphaned on ilanel.com's own
nav too, lower priority per the original note below.

### 9a. Static pages — original gap note (superseded, kept for context)

Cross-checked ilanel.com's admin "Not Linked" list (pages that exist but
aren't in any nav menu) against what's live: `/services`, `/warranty`,
`/trade`, `/faq`, `/terms-and-conditions`, `/3d-models`, `/linktree`,
`/customer-satisfaction-survey` all return real content on ilanel.com
right now, just orphaned from navigation there too — so this isn't a case
of the POC forgetting pages ilanel.com surfaces prominently.

This POC's footer already lists several of these (Trade programme, FAQ,
Warranty, Care & maintenance, Services) as nav items, but every one is a
dead `href="#"` — no pages exist here at all. Out of the original
product/range demonstration scope; worth a real content pass if the POC
extends toward a fuller site rather than the current focused demo.
