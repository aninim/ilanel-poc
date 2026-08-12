#!/usr/bin/env node
/**
 * Read-only scraper for ILANEL's full product catalogue.
 *
 * Why this exists: the four products in data/products.json came from an
 * authenticated Squarespace Commerce API pull, which carries real variants,
 * prices and SKUs. That key is not available, and NO unauthenticated endpoint
 * exposes variant data — verified:
 *
 *   /lighting-design-collections?format=json  -> all 24 products, 0 variants
 *   product page HTML                         -> no embedded variants JSON
 *   ld+json                                   -> WebSite/Organization only,
 *                                                no Product schema
 *
 * So this recovers everything that IS public — title, type label, slug, body
 * copy, hero and gallery imagery — and deliberately leaves `commerce` empty.
 * Products with real variant data keep it; the rest seed as simple products
 * until a Products-Read API key is available.
 *
 * READ-ONLY. GETs only, ~900ms apart, against a live production site.
 *
 * Usage:
 *   node scripts/scrape-catalogue.js               # write data/catalogue.json
 *   node scripts/scrape-catalogue.js --limit 3     # sample first
 *   node scripts/scrape-catalogue.js --dry-run
 */

'use strict';

const fs = require('fs');
const path = require('path');
const https = require('https');

const SITE = 'https://www.ilanel.com';
/*
 * ILANEL's saleable work lives in TWO collections, not one:
 *
 *   /lighting-design-collections  the main catalogue (23 after skips)
 *   /editions                     limited editions (11) — Ripple, Droplet,
 *                                 Atlas, Matariki, Brass Supernova
 *
 * Scraping only the first missed a third of the range. Both are pulled and
 * merged into one catalogue, with editions tagged so they can carry their own
 * range and messaging.
 */
const COLLECTIONS = [
  { path: 'lighting-design-collections', range: null },
  { path: 'editions', range: 'editions' },
];
const UA = 'Mozilla/5.0 (compatible; ilanel-migration-audit/1.0; read-only)';
const DELAY_MS = 900;

const ROOT = path.resolve(__dirname, '..');

// Squarespace demo scaffolding and non-product pages that live in the same
// collection. Confirmed against the live site, not guessed.
const SKIP_SLUGS = new Set(['finishes']);

function parseArgs(argv) {
  const args = { limit: 0, dryRun: false };

  for (let i = 2; i < argv.length; i++) {
    if (argv[i] === '--limit') args.limit = parseInt(argv[++i], 10) || 0;
    else if (argv[i] === '--dry-run') args.dryRun = true;
  }

  return args;
}

function get(url, redirects = 0) {
  return new Promise((resolve, reject) => {
    if (redirects > 5) return reject(new Error('too many redirects'));

    https
      .get(url, { headers: { 'User-Agent': UA } }, (res) => {
        if (res.statusCode >= 300 && res.statusCode < 400 && res.headers.location) {
          res.resume();
          const next = res.headers.location.startsWith('http')
            ? res.headers.location
            : SITE + res.headers.location;
          return resolve(get(next, redirects + 1));
        }

        if (res.statusCode !== 200) {
          res.resume();
          return reject(new Error('HTTP ' + res.statusCode));
        }

        let d = '';
        res.setEncoding('utf8');
        res.on('data', (c) => (d += c));
        res.on('end', () => resolve(d));
      })
      .on('error', reject);
  });
}

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

const decode = (s) =>
  s
    .replace(/&amp;/g, '&')
    .replace(/&nbsp;/g, ' ')
    .replace(/&#8217;/g, '’')
    .replace(/&#8216;/g, '‘')
    .replace(/&quot;/g, '"')
    .replace(/&#39;/g, "'")
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>');

/**
 * Split ILANEL's "NAME / TYPE" H1 convention into its two parts.
 *
 * Live H1s read "PIPI / PENDANT", "COMET / LINEAR PENDANT". Title-cases them,
 * since the uppercase is a CSS treatment rather than the real name.
 */
function splitHeading(h1, fallbackTitle) {
  const raw = decode((h1 || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim());

  if (!raw) return { name: fallbackTitle, type: '' };

  const titleCase = (s) =>
    s
      .split(' ')
      .filter(Boolean)
      // Every live H1 is fully uppercase as a house style, so there is no
      // acronym signal to preserve here — "DOT" and "COMET" look identical.
      // Title-case everything and let NAME_OVERRIDES handle the exceptions.
      .map((w) => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase())
      .join(' ');

  const parts = raw.split('/').map((s) => s.trim());

  if (parts.length >= 2) {
    return { name: titleCase(parts[0]) || fallbackTitle, type: titleCase(parts[1]) };
  }

  return { name: titleCase(raw) || fallbackTitle, type: '' };
}

function extract(html, fallbackTitle) {
  const main = (html.match(/<main[\s\S]*?<\/main>/i) || [''])[0] || html;

  const clean = main
    .replace(/<script[\s\S]*?<\/script>/gi, ' ')
    .replace(/<style[\s\S]*?<\/style>/gi, ' ')
    .replace(/<noscript[\s\S]*?<\/noscript>/gi, ' ');

  const h1 = (html.match(/<h1[^>]*>([\s\S]*?)<\/h1>/i) || [, ''])[1];
  let { name, type } = splitHeading(h1, fallbackTitle);

  /*
   * Explicit overrides for names the live H1 renders as a typographic
   * treatment rather than as the real name.
   *
   * Only XYZ needs it: its H1 is literally "X Y Z / Wall Light", spaced by
   * hand in the content, while <title> and the collection index say "XYZ".
   * A heuristic was tried first (compare against <title> with spaces
   * collapsed) and rejected — it also rewrote "Dot" to "DOT", because the
   * title tag happens to shout. Three known cases are better handled by a
   * list than by a rule that guesses wrong elsewhere.
   */
  const NAME_OVERRIDES = { 'X Y Z': 'XYZ' };

  if (NAME_OVERRIDES[name]) name = NAME_OVERRIDES[name];

  const text = decode(
    clean
      .replace(/<[^>]+>/g, ' ')
      .replace(/\s+/g, ' ')
      .trim()
  );

  // Drop the heading itself from the body copy — it is rendered separately.
  const headingText = decode((h1 || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim());
  const body = text.startsWith(headingText) ? text.slice(headingText.length).trim() : text;

  // data-src first: Squarespace's src is often a placeholder.
  const images = [
    ...new Set(
      [...clean.matchAll(/<img[^>]+(?:data-src|src)="([^"]+)"/gi)]
        .map((m) => m[1])
        .filter((u) => /^https?:\/\//.test(u) && !/data:image/.test(u))
    ),
  ];

  /*
   * Spec sheet, if the page links one. These are real ILANEL PDFs
   * (/s/ILANEL_PIPI_600300_SpecSheet_2026.pdf) and drive the Downloads
   * section, which is otherwise empty on every scraped product.
   */
  const pdfMatch = html.match(/href="([^"]*\.pdf[^"]*)"/i);
  const specPdf = pdfMatch ? (pdfMatch[1].startsWith('http') ? pdfMatch[1] : SITE + pdfMatch[1]) : '';

  const paragraphs = body
    .split(/(?<=[.!?])\s+(?=[A-Z])/)
    .reduce((acc, s) => {
      if (!acc.length) return [s];
      const last = acc[acc.length - 1];
      if (last.length < 240) acc[acc.length - 1] = last + ' ' + s;
      else acc.push(s);
      return acc;
    }, [])
    .map((p) => p.trim())
    .filter(Boolean);

  return { name, type, body, paragraphs, images, specPdf, chars: body.length };
}

async function main() {
  const args = parseArgs(process.argv);
  const started = Date.now();

  console.log('Reading collection index…');

  let items = [];

  for (const collection of COLLECTIONS) {
    const raw = await get(SITE + '/' + collection.path + '?format=json');
    const index = JSON.parse(raw);

    const found = (index.items || []).map((i) => ({
      title: decode(i.title || ''),
      urlId: i.urlId || '',
      fullUrl: i.fullUrl || '/' + collection.path + '/' + i.urlId,
      assetUrl: i.assetUrl || '',
      range: collection.range,
    }));

    console.log('  ' + collection.path + ': ' + found.length);
    items = items.concat(found);
    await sleep(DELAY_MS);
  }

  console.log(`  ${items.length} entries listed`);

  const skipped = items.filter((i) => SKIP_SLUGS.has(i.urlId));
  items = items.filter((i) => !SKIP_SLUGS.has(i.urlId));

  if (skipped.length) {
    console.log(`  ${skipped.length} skipped as non-product: ${skipped.map((s) => s.urlId).join(', ')}`);
  }

  if (args.limit) items = items.slice(0, args.limit);

  console.log(`\nFetching ${items.length} product pages (${DELAY_MS}ms apart, read-only)…`);

  const products = [];
  const failures = [];

  for (let i = 0; i < items.length; i++) {
    const it = items[i];

    try {
      const html = await get(SITE + it.fullUrl);
      const data = extract(html, it.title);

      products.push({
        /*
         * One live urlId is "snowball/walllight" — a slash inside the slug,
         * which WordPress cannot use as a post_name. Flattened for the POC;
         * live_url below keeps the real address, so nothing is lost and the
         * URL-preservation lock is still satisfiable at cutover.
         */
        slug: it.urlId.replace(/\//g, '-'),
        // Set for editions, null for the main catalogue (where the range is
        // derived from the type label instead).
        source_range: it.range,
        name: data.name,
        type: data.type,
        live_url: SITE + it.fullUrl,
        image: it.assetUrl || data.images[0] || '',
        gallery: data.images.slice(0, 4),
        /*
         * Story rows are the editorial image+copy blocks the product template
         * renders below the configurator. Prefer later page imagery — that is
         * the in-situ photography rather than the cut-outs used up top.
         *
         * Products with few images (XYZ has 3, Nimbus 4) would otherwise get
         * none at all and render a visibly thinner page than Comet. Falling
         * back to the tail of the gallery reuses a shot rather than leaving
         * the row empty; a repeated image beats a missing section.
         */
        story: data.images.length > 5
          ? data.images.slice(4, 6)
          : data.images.slice(-2),
        spec_pdf: data.specPdf,
        paragraphs: data.paragraphs.slice(0, 4),
      });

      const flag = data.chars < 120 ? '  ⚠ THIN' : '';
      console.log(
        `  [${String(i + 1).padStart(2)}/${items.length}] ${String(data.chars).padStart(5)}ch ` +
          `${String(data.images.length).padStart(2)}img  ${it.urlId.slice(0, 38)}${flag}`
      );
    } catch (e) {
      failures.push({ urlId: it.urlId, error: e.message });
      console.log(`  [${String(i + 1).padStart(2)}/${items.length}] FAILED ${it.urlId}: ${e.message}`);
    }

    if (i < items.length - 1) await sleep(DELAY_MS);
  }

  const thin = products.filter((p) => p.paragraphs.join(' ').length < 120);
  const noImage = products.filter((p) => !p.image);

  console.log('\n' + '='.repeat(62));
  console.log('CATALOGUE SCRAPE REPORT');
  console.log('='.repeat(62));
  console.log(`Listed              : ${items.length}`);
  console.log(`Fetched             : ${products.length}`);
  console.log(`Failed              : ${failures.length}`);
  console.log(`Thin copy           : ${thin.length}`);
  console.log(`Missing hero image  : ${noImage.length}`);
  console.log(`Elapsed             : ${((Date.now() - started) / 60000).toFixed(1)} min`);

  if (thin.length) {
    console.log('\nThin — check by hand:');
    thin.forEach((p) => console.log(`  ${p.slug}`));
  }

  if (failures.length) {
    console.log('\nFailures:');
    failures.forEach((f) => console.log(`  ${f.urlId}: ${f.error}`));
  }

  console.log('\nNOTE: no variant/price data — that needs a Products-Read API key.');

  if (args.dryRun) {
    console.log('\nDry run — nothing written.');
    return;
  }

  const out = path.join(ROOT, 'data', 'catalogue.json');
  fs.writeFileSync(out, JSON.stringify(products, null, 2));
  console.log(`\nWrote ${out} (${(fs.statSync(out).size / 1024).toFixed(1)} KB)`);
}

main().catch((e) => {
  console.error('\nFATAL:', e.message);
  process.exit(1);
});
