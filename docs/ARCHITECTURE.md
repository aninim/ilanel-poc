# Architecture

Structural **and visual** patterns taken from Ross Gardam's WooCommerce build.
The studio's brief was explicit — *"the sample pages must look Ross Gardam"* —
so page anatomy, type scale and spacing were read from their served HTML and
stylesheet rather than approximated.

RG also validates the stack at ILANEL's scale and price point (67 products,
137 journal posts, 14 ranges, 24 collections).

> **Fonts:** RG use ABCFavorit + Lyon Display, both licensed. This substitutes
> Inter + Playfair Display. Swapping in the licensed faces is the single
> biggest remaining step toward an exact match.

---

## What RG runs, and what we took

| RG | Here | Why |
|---|---|---|
| WordPress + WooCommerce | Same | Content + commerce in one system |
| Custom theme, overrides most Woo templates | Same | Deliberate markup, no default furniture |
| `/product/{name}/` — name only, no keywords | `/lighting-design-collections/{name}/`, Editions at `/editions/{name}/` | Matches ilanel.com's real live URLs, not RG's — see INSTALL.md §Product base, 2026-08-13 |
| `/our-range/{cat}/{sub}/` | `ilanel_range` taxonomy, `our-range` base | Hierarchical ranges |
| Product schema with full Offer | Same field set | The capability Squarespace can't provide |
| Titles rigidly `{Product} \| Ross Gardam` | Theme uses `title-tag` | Live ILANEL has 31 pages with duplicated site name |
| **Zero H1s on product pages** | **Exactly one H1** | See below — we deliberately diverge |

### Where we deliberately diverge from RG

**RG product pages have no `<h1>` at all** and rank fine. That's useful
calibration — it says H1 hygiene is real but second-order. We still emit
exactly one H1 (the product name), because:

- it costs nothing,
- it's correct for accessibility, not just SEO, and
- the live ILANEL site's actual problem is the *opposite* extreme (4 H1s per
  page), so "one, always" is the clearer rule to hand the studio.

`per-country pricing` (RG runs WooCommerce Price Based on Country Pro) is out
of POC scope. Worth revisiting: Australia is 78% of ILANEL's clicks but the US
has 9,584 impressions at 0.97% CTR — the worst gap in Search Console.

---

## Component map

```
plugins/ilanel-poc-core/
├── ilanel-poc-core.php              Bootstrap; hard-fails without WooCommerce
└── includes/
    ├── class-ilanel-taxonomies.php  ilanel_range taxonomy (hierarchical)
    ├── class-ilanel-product-meta.php Spec PDF, finishes, lead time, origin,
    │                                 type label, gallery, story images,
    │                                 swatches, lengths, .glb / .usdz
    ├── class-ilanel-schema.php      Product+Offer, CollectionPage+ItemList
    └── class-ilanel-breadcrumbs.php Visible trail + BreadcrumbList, one source

themes/ilanel-poc/
├── functions.php                    Strips Woo defaults; filters; enqueues
├── header.php / footer.php          RG header (overlaid on hero) and footer
├── taxonomy-ilanel_range.php        Range archive (the collection page)
├── woocommerce/single-product.php   Full RG product anatomy
├── woocommerce/single-product/title.php   The one H1
└── assets/
    ├── css/main.css                 RG design language
    └── js/
        ├── product.js               Carousel, configurator, scale drawing,
        │                            lit/unlit, lightbox, sticky bar
        └── transitions.js           Soft page-to-page fades
```

## Design decisions worth knowing

**Woo's own structured data is removed.** `ILANEL_Schema` unhooks
`WC_Structured_Data` so there's exactly one Product node per page. Two
competing nodes is a common source of Search Console "duplicate field"
warnings.

**Offer is omitted when there's no price.** An Offer without a price is invalid
schema and triggers GSC errors, so the generator returns null rather than
emitting a broken node. This is why placeholder-priced seed products currently
render Product-without-Offer.

**Availability is `BackOrder`, not `InStock`.** ILANEL pieces are made to order
on a 4–12 week lead time. `InStock` would be a false signal to both Google and
buyers.

**Specs are meta, not content.** Finishes, lead time and origin live in product
meta so they're single-source. On Squarespace the same text is retyped per
page, which is exactly how the live site ended up with "ColoUr Disclaimer"
duplicated across 6 pages and drifting.

**Filters work without JavaScript.** Plain links to term archives. No JS
dependency, no layout shift, crawlable.

**One H1 by construction.** The product/range name is the only `<h1>` in any
template; every other heading is `<h2>` or lower. Content entry can't
reintroduce the live site's 148-surplus-tag problem because there's no UI path
to add an H1.

---

## What this does not cover

Out of the agreed POC scope (product + range pages):

- Home page — this is where the studio's visual design matters most, and it
  isn't supplied yet.
- Journal/news (85 posts on live) and Projects (52) — the migration path for
  these is the strongest argument for WordPress, but they're not in scope.
- Cart, checkout, payments.
- Migration tooling from Squarespace.
- Per-country pricing.
