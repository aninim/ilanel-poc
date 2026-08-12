# ILANEL POC — Phase 2 Action Plan

Four extensions. **Do them in this order** — each depends on the one before.

Everything here is written to be **reusable in production**. Only
`dist/` and the Playground blueprint are disposable; the theme and plugin are
the deliverable.

**Rebuild + preview after any change:**
```bash
node scripts/build-playground-blueprint.js && git push
# then reload:
# https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/aninim/ilanel-poc/master/dist/blueprint.json
```

---

## Know before you start

### The data is already here

`data/products.json` → each product has a `commerce` block from the
authenticated Squarespace export:

```json
"commerce": {
  "source_title": "Comet - Pendant",
  "attributes": { "Size": ["1800mm","2100mm","2400mm"],
                  "Color": [...3], "Glass": [...4] },
  "variants": [ { "options": {"Size":"1800mm","Color":"...","Glass":"..."},
                  "price": 3195.5, "sku": "...", "stock": "..." } ]
}
```

| Product | Axes | Variants | Real price range |
|---|---|---|---|
| Comet | Size × Color × Glass | 36 | $3,195 – $7,437 |
| Kahdu | Color × Shape | 24 | $1,870 – $3,080 |
| Dais | Glass | 4 | $2,094 – $2,818 |
| Comet Stardust | Size | 3 | $3,572 – $4,188 |

**Prices and SKUs in `commerce` are real.** Everything else in the seeders is
demo data — see the "two sources of truth" bug below.

### Current architecture, in one screen

```
plugins/ilanel-poc-core/
  ilanel-poc-core.php              boots on plugins_loaded; hard-gates on WooCommerce
  includes/
    class-ilanel-taxonomies.php    ilanel_range taxonomy (hierarchical, slug `our-range`)
    class-ilanel-product-meta.php  5 scalar fields w/ admin UI + 4 array fields (seed-only)
    class-ilanel-schema.php        Product+Offer, CollectionPage+ItemList
    class-ilanel-breadcrumbs.php   visible trail + BreadcrumbList, one source

themes/ilanel-poc/
  functions.php                    strips 11 Woo defaults; enqueues; filters
  header.php / footer.php          RG header (overlaid on hero) + 4-column footer
  taxonomy-ilanel_range.php        range archive
  woocommerce/single-product.php   full RG anatomy, calls NO Woo summary hooks
  woocommerce/single-product/title.php   the one H1
  assets/css/main.css              ~1,500 lines, RG design language
  assets/js/product.js             carousel, configurator, scale, lightbox, sticky bar
  assets/js/transitions.js         site-wide page fades

scripts/build-playground-blueprint.js   inlines every file + generates the seed PHP
```

### Five traps that will cost you time

1. **`single-product.php` fires no Woo summary hooks.** It is a from-scratch
   template inside `while (have_posts())`. `ilanel_poc_render_enquiry()`
   (hooked at `woocommerce_single_product_summary` 25) **never runs**.
   Un-removing a Woo action will not make it render — call it explicitly.
2. **`functions.php` removes `woocommerce_template_single_add_to_cart`** — the
   dispatcher for `woocommerce_variable_add_to_cart`.
3. **`ILANEL_Product_Meta::init()` hooks
   `woocommerce_product_options_general_product_data`** — the General tab,
   which **Woo hides for variable products**. All 7 custom fields disappear
   from admin the moment you flip the type.
4. **Two diverging price sources.** `build-playground-blueprint.js` has its own
   hardcoded `$demo_prices` map (2450/2680/1890/1240) that contradicts the real
   `price` in `products.json` (3195.5/3572.8/1870/2094.4). Kill the demo map.
5. **The projection drops `commerce`.** `buildSeedPhp()` reshapes each product
   into a smaller object and discards the whole `commerce` block. That
   projection is where variant support starts.

### Two live bugs to fix on the way past

- `class-ilanel-product-meta.php` → `get_spin_frames()` references
  **undefined constant `self::FIELD_SPIN`**. Fatal if called; currently
  unreferenced. **Delete the method.**
- `single-product.php` Discover More runs `get_posts()` with **no
  `tax_query`**, so "related" products are random across the catalogue while
  the CTA says "View all {range}". Add the range filter.

### Verification loop (use it every time)

```bash
# PHP lint — no PHP installed; portable build lives in the scratchpad
for f in $(find themes plugins scripts -name "*.php"); do "$PHP" -l "$f"; done
node --check themes/ilanel-poc/assets/js/product.js

# See it. Chrome is at /c/Program Files/Google/Chrome/Application/chrome.exe
chrome --headless --disable-gpu --hide-scrollbars \
  --window-size=1440,2000 --virtual-time-budget=25000 \
  --screenshot=out.png "file:///path/to/preview.html"
```
**Always screenshot before shipping.** Static review missed a clipped product
title and a purple default Woo button in earlier passes.

---

## Task 1 — Variable products *(do first; 2–4 & schema depend on it)*

**Goal:** Comet becomes a real `WC_Product_Variable` with 36 variations, driven
by real attributes and prices. The configurator stops being decorative.

### 1a. Seed variable products

`scripts/build-playground-blueprint.js`:

- In the projection (~line 93), stop dropping `commerce`:
  `attributes: p.commerce?.attributes ?? {}`, `variants: p.commerce?.variants ?? []`
- At the type branch (~line 156), split:
  `variants` non-empty → `new WC_Product_Variable()`, else keep `WC_Product_Simple`
- Register attributes as **local** (`WC_Product_Attribute` with `set_id(0)`),
  not global `pa_*`. Global taxonomies need registration + `flush_rewrite_rules()`
  *before* terms are set, which is fiddly inside Playground's single `runPHP`
  step. Local attributes avoid it entirely; the cost is no archive/filter pages,
  which the POC does not use.
- Per attribute: `set_name()`, `set_options()`, `set_visible(true)`,
  `set_variation(true)`.
- Per variant: `new WC_Product_Variation()`, `set_parent_id()`,
  `set_attributes([ 'size' => '1800mm', ... ])` (lowercased, sanitised keys),
  `set_regular_price((string) $v['price'])`, `set_sku()`,
  `set_stock_status('onbackorder')`.
- Finish with **`WC_Product_Variable::sync($parent_id)`** — without it the
  parent has no price range.
- **Idempotency:** the current `get_page_by_path($slug)` guard does not cover
  children. Before creating variations, delete existing ones
  (`$parent->get_children()`), or re-running multiplies them.
- Delete the `$demo_prices` map and the `DEMO-*` SKU line, or demote them to
  the no-variants branch only.

Mirror the same logic in `scripts/seed-products.php` (the WP-CLI seeder) so the
two do not drift.

### 1b. Render real variation controls

`woocommerce/single-product.php`, configurator section (~lines 227–365):

Replace the two hardcoded fieldsets (`ilanel_length`, `ilanel_finish`) with a
loop over `$product->get_attributes()` — Comet needs three axes, Kahdu two, so
hardcoding two will not do.

Keep the RG-styled pills and swatches. Recommended approach: **bespoke markup +
your own matching JS**, not Woo's `wc-add-to-cart-variation` (which is
jQuery-dependent and expects `<select>`s inside `form.variations_form`).
Print variation data yourself:

```php
$ilanel_variations = $product->get_available_variations();
// -> data-variations="<?php echo esc_attr( wp_json_encode( $ilanel_variations ) ); ?>"
```

Each entry gives `attributes`, `display_price`, `price_html`, `image`,
`is_in_stock`, `sku` — everything the panel needs.

Input naming must be Woo-compatible if you ever POST to cart:
`name="attribute_<sanitised attribute name>"`, `value="<option value>"`.

### 1c. Rewire the JS

`assets/js/product.js` → `initConfigurator()`:

- Delete `data-base-price`, `data-increment` (the `index * 320` fake ladder),
  the scraped currency symbol, and `money()`.
- Read `data-variations`, match the checked inputs across **all** axes, and on
  a hit set price from `variation.price_html`, image from `variation.image`,
  and stock state.
- No match (a real possibility — 3×3×4 = 36 of 36 here, but sparse matrices
  are normal) → show an "unavailable combination" state rather than a stale
  price.
- Keep localStorage persistence; the key already scrapes `postid-\d+` from
  `body.className`. Generalise the saved shape from `{length, finish}` to a map
  of attribute → value.
- Keep the scale drawing; feed it from the Size axis (`data-mm` already strips
  digits from labels like `"1800mm"`).

### 1d. AggregateOffer schema

`class-ilanel-schema.php` → `build_offers()` (~line 118) currently returns a
single flat `Offer`. On a variable product `get_price()` silently returns the
**minimum**, so it emits one Offer at the "from" price — valid but misleading.

Add a branch: if `$product->is_type('variable')`, return

```php
[ '@type' => 'AggregateOffer',
  'lowPrice' => $product->get_variation_price('min'),
  'highPrice' => $product->get_variation_price('max'),
  'offerCount' => count( $product->get_children() ),
  'priceCurrency' => get_woocommerce_currency(),
  'availability' => ..., 'url' => ..., 'seller' => ... ]
```

Keep the existing `null` return when there is no price — that guard is correct.

### 1e. Fix `get_price()` call sites

Seven sites assume simple products. Replace `wc_price($product->get_price())`
with **`$product->get_price_html()`** (handles ranges, currency, tax display)
at: `single-product.php` 310 / 437 / 443 / 485 / 491, and
`taxonomy-ilanel_range.php` 69 / 75.

Watch the truthiness gate `if ($product->get_price())` in the archive — a
variable product with no purchasable variation returns `''` and the price line
vanishes silently.

### 1f. Move the admin fields

`ILANEL_Product_Meta::init()` — swap
`woocommerce_product_options_general_product_data` for
`woocommerce_product_options_inventory_product_data` (or a custom tab via
`woocommerce_product_data_panels`), otherwise the 7 fields vanish from admin on
variable products.

**Done when:** Comet shows a real price range, selecting Size + Color + Glass
updates price and image from real variation data, and the page emits
`AggregateOffer` with `lowPrice: 3195.5` / `highPrice: 7437.72`.

---

## Task 2 — Homepage

**Goal:** the page where conversion is won. Currently there is **no
`front-page.php`** — `index.php` is the only fallback, so this is a clean
addition with no collisions.

- Create `themes/ilanel-poc/front-page.php`.
- **Read RG's homepage first**, the same way the product page was built:
  ```bash
  curl -s -L -A "Mozilla/5.0" https://www.rossgardam.com.au/ -o rg-home.html
  # then grep the DOM skeleton for class names + section order
  ```
  Do not approximate from memory or a screenshot.
- Reuse existing components — `.rg-hero` (carousel JS already generalises),
  `.rg-products` grid, `.rg-section__label` with the trailing `/`,
  `.rg-article--reversed` storytelling rows.
- Real content is available: 52 projects and 79 news posts via
  `?format=json` (see Task 4), plus the provenance list already in
  `data/products.json` → `studio_constants.provenance` (NGV, Australian War
  Memorial, The Hour Glass, Four Seasons, Ritz-Carlton).
- Set it as the front page in the blueprint's `setSiteOptions`:
  `show_on_front: 'page'`, `page_on_front: <id>` — create the page in the seed
  step first.
- **One H1 only** (the site's proposition), every other heading `h2`+.

**Done when:** the homepage screenshots convincingly next to RG's, and links
into the range and a product.

---

## Task 3 — Projects ↔ products cross-linking

**Goal:** demonstrate the argument for WooCommerce over Shopify. Currently
*asserted*, never *shown*. ILANEL sells on provenance — NGV, War Memorial,
Four Seasons — and one database makes that native.

- The seed already exists: `data/products.json` → every product has
  `related_projects[]`, populated for Kahdu → Chatswood Hill Tavern with
  `name`/`url`/`description`. **Nothing reads it today.**
- Register a `project` **custom post type** in a new
  `plugins/ilanel-poc-core/includes/class-ilanel-projects.php` — follow the
  shape of `class-ilanel-taxonomies.php`. Add it to the `require_once` list and
  the `init()` chain in `ilanel-poc-core.php`.
- Relate them. Simplest durable option: a `_ilanel_related_projects` post-meta
  array of project IDs on the product, plus a reciprocal query for the reverse
  direction (project → products) so there is one source of truth.
- Render both directions:
  - product page → a "Featured in" section (reuse `.rg-section` +
    `.rg-grid` markup)
  - project page → "Lighting used" cards (reuse `.rg-product-card`)
- Add a `single-project.php` template; the RG article rows work as-is.
- Seed 2–3 real projects. Chatswood Hill Tavern already has copy and an image
  in the repo (`Cafe+Brass...jpg` is the café install used in the Comet story
  row).

**Done when:** Kahdu's page links to Chatswood Hill Tavern and that project
page links back to Kahdu — a round trip, with no manual join.

---

## Task 4 — Real migration test

**Goal:** measure migration cost instead of estimating it. This is one of
Oren's two stated decision criteria.

### What is already known

- **`?format=json` works** on ilanel.com and returns structured items:
  `title`, `urlId`, `body`, `assetUrl`, `publishOn`, `id`, `recordType`.
  - `/projects?format=json` → **all 51 in one page**
  - `/news?format=json` → paginates at 20 (`pagination.nextPage`)
- ⚠️ **Project bodies come back empty** (`body: ""`, no tags/categories) —
  the content lives in Squarespace **blocks**, not the JSON body. Do not
  assume JSON alone is sufficient; you will need a second pass (rendered HTML
  parse, or the `/collection-item?format=json` per-item endpoint) for project
  copy.
- **Reusable:** `ilanel-studio/scripts/sqsp_catalogue.py` — read-only
  Squarespace Commerce API reader, already written and documented.
- Live content volumes: 79 news, 52 projects, 26 lighting-collection pages,
  14 light-art, 12 editions.

### Build

`scripts/migrate-from-squarespace.js` (or `.py`):

1. Pull `/news` and `/projects` via `?format=json`, following pagination.
2. Detect and report the empty-body problem per item rather than silently
   importing blanks.
3. Map to WP: news → `post`, projects → the `project` CPT from Task 3,
   preserving `urlId` as the slug (**URL preservation is a hard lock** — 1,110
   of ~1,130 backlinks point at the homepage, and slugs must not drift).
4. Sideload `assetUrl` as the featured image.
5. **Output a report, not just an import**: counts, items needing manual work,
   estimated hours. That report is the actual deliverable — it answers "what
   does migration cost?"

Keep it **read-only against Squarespace**. Never write to the live site.

**Done when:** there is a written migration-cost estimate backed by a real
import run, not a guess.

---

## Hard locks — never break

- **Never change a live URL, slug or canonical.** 1,110 of ~1,130 backlinks
  point at the homepage; search position improving 18.5 → 10.4.
- **Never edit live Commerce inventory** — 73 records with real order history.
- **Never write to Squarespace.** Migration tooling is read-only.
- POC photography is **ILANEL's own**, pulled from their CDN. Fine for a demo;
  flag it if this repo is shared more widely.
- Real prices are in `data/products.json` → `commerce`. The demo figures still
  hardcoded in the seeders are **not** real — remove them in Task 1.

---

## Doc drift to clean up

`docs/OPEN-QUESTIONS.md` §1–2 and `README.md` still say prices and SKUs are
invented placeholders. That predates the Commerce export and is now wrong for
the `commerce` block (though still true for what the seeders write). Fix once
Task 1 lands, so nobody quotes the wrong number.
