#!/usr/bin/env node
/**
 * Merge the scraped catalogue into the seed, preserving real Commerce data.
 *
 * data/products.json holds four products with REAL variants, prices and SKUs
 * from an authenticated Commerce API pull. data/catalogue.json holds all 23
 * live products with copy and imagery but no variants (no unauthenticated
 * endpoint exposes them).
 *
 * The four overlap with the 23, so this joins them rather than concatenating:
 * the authenticated record wins on anything commercial, the scrape fills in
 * copy and imagery, and the live slug wins because URL preservation is a hard
 * lock at cutover.
 *
 * Writes data/products-merged.json. Review it, then move it over
 * data/products.json — this does not overwrite the seed in place.
 */

'use strict';

const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');

const seed = JSON.parse(fs.readFileSync(path.join(ROOT, 'data', 'products.json'), 'utf8'));
const catalogue = JSON.parse(fs.readFileSync(path.join(ROOT, 'data', 'catalogue.json'), 'utf8'));

/*
 * The seed's slugs predate this scrape and differ from the live ones
 * (`comet-pendant` vs `comet-pendant-light`). Joining on slug would create
 * duplicates, so map explicitly — verified by comparing names and live_url,
 * not assumed.
 */
const SEED_TO_LIVE = {
  'comet-pendant': 'comet-pendant-light',
  'comet-stardust-pendant': 'comet-stardust-pendant-light',
  'kahdu-pendant': 'kahdu-light-shades',
  'dais-wall-light': 'dais-wall-light',
};

/*
 * Idempotent by design: this has to survive being run against its own output.
 *
 * products.json starts as the authenticated seed (old slugs) but becomes the
 * merged catalogue (live slugs) after the first run. Keying only on
 * SEED_TO_LIVE meant a second run matched nothing and silently dropped
 * Comet's 36 variations, Kahdu's 24 and Stardust's 3 — 3 of 4 products lost
 * their real prices with no error. So index by BOTH the mapped slug and the
 * product's own slug, and never let a record without commerce data displace
 * one that has it.
 */
const seedByLiveSlug = {};

seed.products.forEach((p) => {
  const keys = [SEED_TO_LIVE[p.slug], p.slug].filter(Boolean);

  keys.forEach((k) => {
    const existing = seedByLiveSlug[k];
    const incomingHasCommerce = !!(p.commerce && (p.commerce.variants || []).length);
    const existingHasCommerce = !!(existing && existing.commerce && (existing.commerce.variants || []).length);

    if (!existing || (incomingHasCommerce && !existingHasCommerce)) {
      seedByLiveSlug[k] = p;
    }
  });
});

/**
 * Constrain a Squarespace CDN image to a sensible width.
 *
 * The raw originals are uncompressed PNGs — Comet's hero is 1.78MB and takes
 * ~2.8s, and the hero carousel loads three of them, which is why product
 * pages felt slow. Squarespace's CDN resizes on demand via ?format=<w>w:
 *
 *   original      1,781,728 bytes   2.79s
 *   ?format=1500w 1,186,794 bytes   2.69s
 *   ?format=1000w   488,870 bytes   1.20s   <- 3.6x smaller
 *
 * 1500w for heroes (they run full-bleed to 1440px+), 1000w for grid tiles
 * and story rows, which never render wider than ~700px.
 */
function sized(url, width) {
  if (!url || !/images\.squarespace-cdn\.com/.test(url)) return url;
  return url.replace(/\?.*$/, '') + '?format=' + width + 'w';
}

/** Assign a range from the product's type label. */
function rangeFor(type) {
  const t = (type || '').toLowerCase();
  if (t.includes('wall') || t.includes('surface')) return 'wall-lights';
  if (t.includes('lamp')) return 'lamps';
  if (t.includes('chandelier')) return 'chandeliers';
  return 'pendants';
}

const merged = catalogue.map((c) => {
  const s = seedByLiveSlug[c.slug];

  // Base record from the scrape — everything here is real and public.
  const out = {
    slug: c.slug,
    name: c.name,
    type: c.type,
    live_url: c.live_url,
    live_h1: `${c.name} / ${c.type}`,
    seo_title: s ? s.seo_title : `${c.name} — ${c.type} · ILANEL`,
    meta_description: s ? s.meta_description : (c.paragraphs[0] || '').slice(0, 155),
    // Prefer the authenticated record, fall back to the scraped spec sheet.
    spec_pdf: (s && s.spec_pdf) || c.spec_pdf || '',
    range: s ? s.range : rangeFor(c.type),
    image: sized(c.image || (s ? s.image : ''), 1500),
    gallery: (c.gallery && c.gallery.length ? c.gallery : s ? s.gallery : []).map((u) => sized(u, 1500)),
    // Seeded products have curated story images; scraped ones use later
    // page photography. Either way the template gets its editorial rows.
    story: (s && Object.keys(s.story || {}).length ? Object.values(s.story) : c.story || []).map((u) =>
      sized(u, 1000)
    ),
    paragraphs: c.paragraphs,
    related_projects: s ? s.related_projects : [],
  };

  // Commercial data only ever comes from the authenticated record.
  if (s) {
    out.sku = s.sku;
    out.price = s.price;
    // Swatches render as small thumbnails and as variation images; 600w is
    // ample and avoids sideloading multi-megabyte originals at seed time.
    out.finishes = (s.finishes || []).map((f) =>
      Array.isArray(f) ? [f[0], sized(f[1], 600)] : f
    );
    out.commerce = s.commerce;
  } else {
    out.finishes = [];
  }

  return out;
});

const withCommerce = merged.filter((p) => p.commerce && (p.commerce.variants || []).length);

const result = {
  _source: seed._source,
  _note:
    'Merged 2026-08-12: four products carry real Commerce data (variants, ' +
    'prices, SKUs) from an authenticated API pull; the rest were scraped ' +
    'read-only from the live site and have copy and imagery but no variants. ' +
    'Regenerate with scripts/scrape-catalogue.js + scripts/merge-catalogue.js.',
  range: seed.range,
  studio_constants: seed.studio_constants,
  products: merged,
};

/*
 * Refuse to write a merge that loses commercial data.
 *
 * Variants only ever come from an authenticated API pull that cannot be
 * re-fetched without a key, so dropping them is unrecoverable from here — and
 * it happened silently once already. Fail loudly instead.
 */
const seedVariantCount = seed.products.filter(
  (p) => p.commerce && (p.commerce.variants || []).length
).length;

if (withCommerce.length < seedVariantCount) {
  console.error(
    `\nREFUSING TO WRITE: seed had ${seedVariantCount} products with variants, ` +
      `merge produced ${withCommerce.length}. Variant data cannot be re-fetched ` +
      `without a Commerce API key — check SEED_TO_LIVE slug mapping.`
  );
  process.exit(1);
}

const out = path.join(ROOT, 'data', 'products-merged.json');
fs.writeFileSync(out, JSON.stringify(result, null, 2));

console.log('merged products      :', merged.length);
console.log('with real variants   :', withCommerce.length, '->', withCommerce.map((p) => p.name).join(', '));
console.log('without variants     :', merged.length - withCommerce.length);
console.log('missing hero image   :', merged.filter((p) => !p.image).length);
console.log('missing copy         :', merged.filter((p) => !p.paragraphs.length).length);

const ranges = {};
merged.forEach((p) => (ranges[p.range] = (ranges[p.range] || 0) + 1));
console.log('ranges               :', JSON.stringify(ranges));

console.log('\nwrote', out);
console.log('Review, then: mv data/products-merged.json data/products.json');
