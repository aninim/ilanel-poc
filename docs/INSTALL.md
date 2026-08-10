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
wp rewrite flush --hard
```

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

**Product base** — to match Ross Gardam's `/product/{name}/`:

```bash
wp option update woocommerce_permalinks '{"product_base":"/product","category_base":"product-category","tag_base":"product-tag","attribute_base":""}'
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
curl -s http://<site>/product/comet-pendant/ | grep -A5 'application/ld+json'

# Exactly one h1 — expect 1, not 4
curl -s http://<site>/product/comet-pendant/ | grep -o '<h1' | wc -l
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
