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

const seedByLiveSlug = {};

seed.products.forEach((p) => {
  const live = SEED_TO_LIVE[p.slug];
  if (live) seedByLiveSlug[live] = p;
});

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
    spec_pdf: s ? s.spec_pdf : '',
    range: s ? s.range : rangeFor(c.type),
    image: c.image || (s ? s.image : ''),
    gallery: c.gallery && c.gallery.length ? c.gallery : s ? s.gallery : [],
    story: s ? s.story : {},
    paragraphs: c.paragraphs,
    related_projects: s ? s.related_projects : [],
  };

  // Commercial data only ever comes from the authenticated record.
  if (s) {
    out.sku = s.sku;
    out.price = s.price;
    out.finishes = s.finishes;
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
