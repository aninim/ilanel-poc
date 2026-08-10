# Verification — what was actually tested

Written so nobody has to take the README's word for it. Every claim below was
produced by running code, not by reading it.

**Environment:** PHP 8.3.33 (NTS, x64), downloaded portable to the session
scratchpad. No admin install, nothing added to the company PC's system state.

---

## 1. Syntax — all 12 PHP files

`php -l` on every file:

```
PASS  plugins/ilanel-poc-core/ilanel-poc-core.php
PASS  plugins/ilanel-poc-core/includes/class-ilanel-breadcrumbs.php
PASS  plugins/ilanel-poc-core/includes/class-ilanel-product-meta.php
PASS  plugins/ilanel-poc-core/includes/class-ilanel-schema.php
PASS  plugins/ilanel-poc-core/includes/class-ilanel-taxonomies.php
PASS  scripts/seed-products.php
PASS  themes/ilanel-poc/footer.php
PASS  themes/ilanel-poc/functions.php
PASS  themes/ilanel-poc/header.php
PASS  themes/ilanel-poc/index.php
PASS  themes/ilanel-poc/taxonomy-ilanel_range.php
PASS  themes/ilanel-poc/woocommerce/single-product/title.php
```

## 2. Product schema — real output

`ILANEL_Schema::output_schema()` run against stubbed WP/Woo objects:

```json
{
    "@context": "https://schema.org/",
    "@type": "Product",
    "name": "Comet",
    "url": "https://ilanel.test/product/comet-pendant/",
    "description": "Illuminate your space with ILANEL's Comet Pendant, ...",
    "sku": "ILN-COMET-01",
    "image": "https://ilanel.test/img/comet.jpg",
    "brand": { "@type": "Brand", "name": "ILANEL" },
    "offers": {
        "@type": "Offer",
        "url": "https://ilanel.test/product/comet-pendant/",
        "price": "2450.00",
        "priceCurrency": "AUD",
        "availability": "https://schema.org/BackOrder",
        "priceValidUntil": "2027-08-10",
        "seller": { "@type": "Organization", "name": "ILANEL" }
    }
}
```

**Checked:** valid JSON; `name`, `url`, `description`, `sku`, `image`, `offers`
all present; offer carries `price`, `priceCurrency`, `availability`,
`priceValidUntil`. Matches the field set Ross Gardam emits.

`BackOrder` is deliberate — ILANEL pieces are made to order on a 4–12 week
lead time, so `InStock` would be a false signal.

## 3. CollectionPage schema — range page

```json
{
    "@context": "https://schema.org/",
    "@type": "CollectionPage",
    "name": "Pendants",
    "url": "https://ilanel.test/our-range/pendants/",
    "description": "Sculptural pendant lighting, handmade in Melbourne.",
    "mainEntity": {
        "@type": "ItemList",
        "numberOfItems": 1,
        "itemListElement": [
            { "@type": "ListItem", "position": 1, "url": "...", "name": "Comet" }
        ]
    }
}
```

**Checked:** valid JSON, ItemList present and populated from the loop.

## 4. Breadcrumbs — visible trail and schema agree

Rendered HTML:

```html
<nav class="ilanel-breadcrumbs" aria-label="Breadcrumb"><ol>
  <li><a href="https://ilanel.test/">Home</a></li>
  <li><a href="https://ilanel.test/our-range/pendants/">Pendants</a></li>
  <li><span aria-current="page">Comet</span></li>
</ol></nav>
```

Matching JSON-LD: `BreadcrumbList`, 3 items, positions 1–3 sequential,
same names and URLs as the visible trail — because both come from
`get_trail()`.

**Checked:** valid JSON; positions sequential from 1; current page rendered as
text with `aria-current="page"` rather than a self-link.

---

## What was NOT verified

Stated plainly so the POC isn't oversold:

- **No running WordPress.** Everything above used stubbed WP/Woo functions.
  Behaviour inside a real install — rewrite rules, template resolution, Woo
  hook order — is unverified until it's on a host.
- **The seeder has never run.** It's syntax-clean and idempotent by design,
  but untested against a real database.
- **No visual check.** No screenshots, no browser. Placeholder CSS is
  unreviewed in a viewport.
- **Prices and SKUs are invented** for the harness. Real values must come from
  Commerce — see `docs/OPEN-QUESTIONS.md`.
- **No images.** Product photography is the studio's to supply.

The gap between "schema generator verified" and "POC demonstrably working" is
exactly the hosting decision in the README.

---

## Reproducing

```bash
# any machine with PHP 8+
php -l plugins/ilanel-poc-core/includes/class-ilanel-schema.php
```

The stub harnesses live in the session scratchpad and are not committed — they
depend on absolute local paths and would rot. Rebuilding one takes a few
minutes; the pattern is: define `ABSPATH`, stub the WP functions the class
calls, instantiate, capture `ob_start()`/`ob_get_clean()`, `json_decode` the
script tag.
