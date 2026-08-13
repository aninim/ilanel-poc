# Install

Prerequisites: PHP 8.0+, MySQL/MariaDB, WordPress 6.0+, WooCommerce 8.0+,
WP-CLI. None of these are on the work PC — see the hosting section in the
README.

---

## Option A — WordPress Playground (fastest demo, no install)

Runs WP+Woo in the browser via WebAssembly. Good enough to *see* the pages
working; state does not persist and it is not a host.

1. Open <https://playground.wordpress.net/>
2. Install and activate WooCommerce from the plugin directory.
3. Upload `plugins/ilanel-poc-core/` and `themes/ilanel-poc/` via the admin.
4. Activate both, then create products by hand (the WP-CLI seeder is not
   available in Playground).

## Option B — Local install (personal machine)

Use LocalWP (<https://localwp.com/>) — bundles PHP, MySQL and WP-CLI, no
manual server config.

1. Create a site in LocalWP.
2. Install and activate WooCommerce.
3. Copy the code in:

```bash
cp -r ilanel-poc/plugins/ilanel-poc-core  <site>/app/public/wp-content/plugins/
cp -r ilanel-poc/themes/ilanel-poc        <site>/app/public/wp-content/themes/
```

4. Open LocalWP's site shell and run:

```bash
wp plugin activate woocommerce
wp plugin activate ilanel-poc-core
wp theme activate ilanel-poc
wp eval-file /path/to/ilanel-poc/scripts/seed-products.php
wp eval-file /path/to/ilanel-poc/scripts/seed-journal.php
wp rewrite flush --hard
```

Then apply the **Configuration after install** steps below — none of them
are automated by the seeders, and skipping them leaves real gaps (product
pages 404 or point at the wrong URL, `/news/` collides with the homepage).

## Option C — Managed WP host

Same as B, but over SSH/SFTP on the host. This is the right option if the POC
proceeds — see the README for candidates.

---

## Configuration after install

**Currency** — set to AUD, or the Offer schema will emit the wrong
`priceCurrency`:

```bash
wp option update woocommerce_currency AUD
```

**Permalinks** — must be a pretty structure for `/our-range/…` to resolve:

```bash
wp option update permalink_structure '/%postname%/'
wp rewrite flush --hard
```

**Product base** — updated 2026-08-13. `/product/{name}/` matched Ross
Gardam's page *anatomy* reference, but not ilanel.com's actual live URLs,
confirmed by reading their real nav and product links directly: regular
products live at `/lighting-design-collections/{slug}`, and **Editions are
a separate top-level path**, `/editions/{slug}` — not nested under the main
catalogue base at all. Matching this protects real backlinks if the POC
becomes an actual migration; the two are otherwise unrelated URL spaces
that happen to share a WooCommerce `product` post type.

```bash
wp option update woocommerce_permalinks '{"product_base":"lighting-design-collections","category_base":"product-category","tag_base":"product-tag","attribute_base":""}'
wp rewrite flush --hard
```

`class-ilanel-product-urls.php` then overrides the permalink for any
product carrying the `editions` range term onto `/editions/{slug}/`
instead — no further manual step needed, it activates with the plugin.

**Front page** — `ilanel-poc-core` does not set this; only
`scripts/build-playground-blueprint.js` does, for the Playground demo.
Skipping it on a real install makes `/news/` (and any other custom archive
route) resolve to `front-page.php` instead of its own template, because
WordPress's `is_front_page()` is true for the "posts" query whenever
`show_on_front` is still at its default:

```bash
wp post create --post_type=page --post_title=Home --post_name=home --post_status=publish
wp option update show_on_front page
wp option update page_on_front <the ID printed above>
wp rewrite flush --hard
```

---

## Verify it worked

```bash
# Ranges exist
wp term list ilanel_range --fields=name,slug,count

# Products seeded
wp post list --post_type=product --fields=post_title,post_name

# Product schema present (expect a Product JSON-LD block)
curl -s http://<site>/lighting-design-collections/comet-pendant-light/ | grep -A5 'application/ld+json'

# Exactly one h1 — expect 1, not 4
curl -s http://<site>/lighting-design-collections/comet-pendant-light/ | grep -o '<h1' | wc -l

# Editions land on their own base, not lighting-design-collections
curl -s -o /dev/null -w '%{http_code}\n' http://<site>/editions/ripple-pendant-spun-metal-shade/
```

That last check is the direct comparison against live: the same command on
`https://www.ilanel.com/lighting-design-collections/comet-pendant-light`
returns **4**.

---

## Troubleshooting

**`/our-range/pendants/` 404s** — rewrite rules not flushed.
`wp rewrite flush --hard`, then deactivate/reactivate the plugin.

**No JSON-LD in source** — check WooCommerce is active; the plugin no-ops
without it and shows an admin notice.

**Offer node missing from Product schema** — the product has no price. This is
intentional: an Offer without a price is invalid schema and triggers Search
Console errors. Set a price, or accept Product-without-Offer.

**Seeder says "WooCommerce is not active"** — activate Woo before running it.
