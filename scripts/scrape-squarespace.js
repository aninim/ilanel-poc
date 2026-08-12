#!/usr/bin/env node
/**
 * Read-only Squarespace content scraper.
 *
 * Fills the gaps left by Squarespace's own WordPress (WXR) export, which for
 * ilanel.com produced pages and 45 of 79 news posts but **zero of the 51
 * projects** — Squarespace exports a single blog collection, and projects are
 * a separate one.
 *
 * Why HTML and not JSON: `?format=json` returns all 51 projects with title,
 * urlId and assetUrl, but `body` and `excerpt` are **empty for every item**,
 * and the per-item endpoint is empty too. The copy lives in Squarespace blocks
 * that JSON does not expose. It is present in the rendered DOM, so that is
 * what we read.
 *
 * Output is WXR (WordPress eXtended RSS) so it imports with WordPress's own
 * importer — the same path as the official export, and no bespoke import code.
 *
 * READ-ONLY. This never writes to Squarespace. It issues GETs and nothing else.
 *
 * Usage:
 *   node scripts/scrape-squarespace.js --collection projects
 *   node scripts/scrape-squarespace.js --collection news --exclude-from data/Squarespace-Wordpress-Export-08-12-2026.xml
 *   node scripts/scrape-squarespace.js --collection projects --limit 3 --dry-run
 */

'use strict';

const fs = require('fs');
const path = require('path');
const https = require('https');

const SITE = 'https://www.ilanel.com';
const UA = 'Mozilla/5.0 (compatible; ilanel-migration-audit/1.0; read-only)';

// Be a good citizen: this is a live production site with real traffic.
const DELAY_MS = 900;

// --- CLI -------------------------------------------------------------------

function parseArgs(argv) {
  const args = { collection: 'projects', limit: 0, dryRun: false, excludeFrom: '', out: '' };

  for (let i = 2; i < argv.length; i++) {
    const a = argv[i];
    if (a === '--collection') args.collection = argv[++i];
    else if (a === '--limit') args.limit = parseInt(argv[++i], 10) || 0;
    else if (a === '--dry-run') args.dryRun = true;
    else if (a === '--exclude-from') args.excludeFrom = argv[++i];
    else if (a === '--out') args.out = argv[++i];
    else if (a === '--help' || a === '-h') {
      console.log(fs.readFileSync(__filename, 'utf8').split('*/')[0]);
      process.exit(0);
    }
  }

  return args;
}

// --- HTTP ------------------------------------------------------------------

function get(url, redirects = 0) {
  return new Promise((resolve, reject) => {
    if (redirects > 5) return reject(new Error('too many redirects: ' + url));

    https
      .get(url, { headers: { 'User-Agent': UA, Accept: '*/*' } }, (res) => {
        const { statusCode, headers } = res;

        if (statusCode >= 300 && statusCode < 400 && headers.location) {
          res.resume();
          const next = headers.location.startsWith('http')
            ? headers.location
            : SITE + headers.location;
          return resolve(get(next, redirects + 1));
        }

        if (statusCode !== 200) {
          res.resume();
          return reject(new Error(`HTTP ${statusCode} for ${url}`));
        }

        let data = '';
        res.setEncoding('utf8');
        res.on('data', (c) => (data += c));
        res.on('end', () => resolve(data));
      })
      .on('error', reject);
  });
}

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

// --- Extraction ------------------------------------------------------------

/**
 * Pull the item list from the collection's JSON endpoint.
 *
 * JSON is reliable for the *index* (ids, slugs, titles, hero images) — it is
 * only the body copy it withholds, which we take from the HTML per item.
 */
async function fetchIndex(collection) {
  const items = [];
  let url = `${SITE}/${collection}?format=json`;

  for (let page = 0; page < 20; page++) {
    const raw = await get(url);
    let data;

    try {
      data = JSON.parse(raw);
    } catch (e) {
      throw new Error(`${collection}: response was not JSON (${e.message})`);
    }

    (data.items || []).forEach((it) => {
      items.push({
        id: it.id,
        title: it.title || '',
        urlId: it.urlId || '',
        fullUrl: it.fullUrl || `/${collection}/${it.urlId}`,
        assetUrl: it.assetUrl || '',
        publishOn: it.publishOn || it.addedOn || Date.now(),
        recordType: it.recordType,
      });
    });

    const next = data.pagination && data.pagination.nextPageUrl;
    if (!next) break;
    url = SITE + next + (next.includes('?') ? '&' : '?') + 'format=json';
    await sleep(DELAY_MS);
  }

  return items;
}

/**
 * Extract the body copy and images for one item from its rendered page.
 *
 * Squarespace wraps post content in `.sqs-block-content`; we take those inside
 * <main> and drop nav/header/footer chrome. Falling back to <main> as a whole
 * is deliberate — a thin result is reported rather than silently accepted, so
 * a template change surfaces as a warning instead of a blank import.
 */
function extractBody(html) {
  const mainMatch = html.match(/<main[\s\S]*?<\/main>/i);
  const scope = mainMatch ? mainMatch[0] : html;

  const clean = (s) =>
    s
      .replace(/<script[\s\S]*?<\/script>/gi, '')
      .replace(/<style[\s\S]*?<\/style>/gi, '')
      .replace(/<noscript[\s\S]*?<\/noscript>/gi, '');

  const scoped = clean(scope);

  const blocks = [...scoped.matchAll(/<div class="sqs-block-content"[^>]*>([\s\S]*?)<\/div>/gi)]
    .map((m) => m[1].trim())
    .filter(Boolean);

  const html_out = blocks.length ? blocks.join('\n\n') : scoped;

  const text = html_out
    .replace(/<[^>]+>/g, ' ')
    .replace(/&nbsp;/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();

  // Squarespace serves responsive images via data-src; src is often a
  // placeholder, so prefer the former.
  const images = [
    ...new Set(
      [...scoped.matchAll(/<img[^>]+(?:data-src|src)="([^"]+)"/gi)]
        .map((m) => m[1])
        .filter((u) => /^https?:\/\//.test(u) && !/data:image/.test(u))
    ),
  ];

  return { html: html_out, text, textLen: text.length, images };
}

// --- WXR emission ----------------------------------------------------------

const esc = (s) =>
  String(s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');

const cdata = (s) => `<![CDATA[${String(s).replace(/\]\]>/g, ']]&gt;')}]]>`;

function toWxr(items, collection) {
  const now = new Date().toUTCString();

  // Projects become a `project` CPT (Task 3 registers it); news becomes posts.
  const postType = collection === 'projects' ? 'project' : 'post';

  const body = items
    .map((it, i) => {
      const date = new Date(it.publishOn);
      const iso = date.toISOString().replace('T', ' ').slice(0, 19);

      return `	<item>
		<title>${cdata(it.title)}</title>
		<link>${esc(SITE + it.fullUrl)}</link>
		<pubDate>${date.toUTCString()}</pubDate>
		<dc:creator>${cdata('ilanel')}</dc:creator>
		<guid isPermaLink="false">${esc(SITE + it.fullUrl)}</guid>
		<description></description>
		<content:encoded>${cdata(it.body.html)}</content:encoded>
		<excerpt:encoded>${cdata('')}</excerpt:encoded>
		<wp:post_id>${90000 + i}</wp:post_id>
		<wp:post_date>${cdata(iso)}</wp:post_date>
		<wp:post_date_gmt>${cdata(iso)}</wp:post_date_gmt>
		<wp:comment_status>${cdata('closed')}</wp:comment_status>
		<wp:ping_status>${cdata('closed')}</wp:ping_status>
		<wp:post_name>${cdata(it.urlId)}</wp:post_name>
		<wp:status>${cdata('publish')}</wp:status>
		<wp:post_parent>0</wp:post_parent>
		<wp:menu_order>0</wp:menu_order>
		<wp:post_type>${cdata(postType)}</wp:post_type>
		<wp:post_password>${cdata('')}</wp:post_password>
		<wp:is_sticky>0</wp:is_sticky>
${it.body.images
  .slice(0, 1)
  .map(
    (u) => `		<wp:postmeta>
			<wp:meta_key>${cdata('_ilanel_source_featured_image')}</wp:meta_key>
			<wp:meta_value>${cdata(u)}</wp:meta_value>
		</wp:postmeta>`
  )
  .join('\n')}
		<wp:postmeta>
			<wp:meta_key>${cdata('_ilanel_source_url')}</wp:meta_key>
			<wp:meta_value>${cdata(SITE + it.fullUrl)}</wp:meta_value>
		</wp:postmeta>
	</item>`;
    })
    .join('\n');

  return `<?xml version="1.0" encoding="UTF-8" ?>
<!--
	Generated by scripts/scrape-squarespace.js on ${now}
	Source: ${SITE}/${collection} (read-only)
	Items: ${items.length}

	These are the items Squarespace's own WXR export omitted. Import with the
	standard WordPress importer. URL slugs are preserved from urlId — do not
	let them drift, live URLs are a hard lock.
-->
<rss version="2.0"
	xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:wp="http://wordpress.org/export/1.2/">
<channel>
	<title>ILANEL — ${esc(collection)}</title>
	<link>${esc(SITE)}</link>
	<description>Scraped ${esc(collection)} not covered by the Squarespace export</description>
	<pubDate>${now}</pubDate>
	<language>en-AU</language>
	<wp:wxr_version>1.2</wp:wxr_version>
	<wp:base_site_url>${esc(SITE)}</wp:base_site_url>
	<wp:base_blog_url>${esc(SITE)}</wp:base_blog_url>
	<wp:author>
		<wp:author_id>1</wp:author_id>
		<wp:author_login>${cdata('ilanel')}</wp:author_login>
		<wp:author_display_name>${cdata('ILANEL')}</wp:author_display_name>
	</wp:author>
${body}
</channel>
</rss>
`;
}

/** Slugs already present in an existing WXR file, so we never import twice. */
function slugsFrom(file) {
  if (!file || !fs.existsSync(file)) return new Set();

  const xml = fs.readFileSync(file, 'utf8');
  const slugs = [...xml.matchAll(/<wp:post_name><!\[CDATA\[([\s\S]*?)\]\]><\/wp:post_name>/g)].map(
    (m) => m[1]
  );

  return new Set(slugs);
}

// --- Main ------------------------------------------------------------------

async function main() {
  const args = parseArgs(process.argv);
  const started = Date.now();

  console.log(`Collection : ${args.collection}`);
  console.log(`Mode       : ${args.dryRun ? 'DRY RUN (no file written)' : 'write'}`);

  const skip = slugsFrom(args.excludeFrom);
  if (skip.size) console.log(`Excluding  : ${skip.size} slugs already in ${path.basename(args.excludeFrom)}`);

  console.log('\nFetching index…');
  let index = await fetchIndex(args.collection);
  console.log(`  ${index.length} items listed`);

  const before = index.length;
  index = index.filter((it) => !skip.has(it.urlId));
  if (before !== index.length) console.log(`  ${before - index.length} skipped as already exported`);

  if (args.limit) index = index.slice(0, args.limit);

  console.log(`\nFetching ${index.length} pages (${DELAY_MS}ms apart, read-only)…`);

  const results = [];
  const failures = [];

  for (let i = 0; i < index.length; i++) {
    const it = index[i];
    const url = SITE + it.fullUrl;

    try {
      const html = await get(url);
      const body = extractBody(html);
      results.push({ ...it, body });

      const flag = body.textLen < 150 ? '  ⚠ THIN' : '';
      console.log(
        `  [${String(i + 1).padStart(3)}/${index.length}] ${String(body.textLen).padStart(5)}ch ` +
          `${String(body.images.length).padStart(2)}img  ${it.urlId.slice(0, 40)}${flag}`
      );
    } catch (e) {
      failures.push({ urlId: it.urlId, error: e.message });
      console.log(`  [${String(i + 1).padStart(3)}/${index.length}] FAILED ${it.urlId}: ${e.message}`);
    }

    if (i < index.length - 1) await sleep(DELAY_MS);
  }

  // --- Report: this is the actual deliverable ------------------------------

  const thin = results.filter((r) => r.body.textLen < 150);
  const totalChars = results.reduce((n, r) => n + r.body.textLen, 0);
  const totalImages = results.reduce((n, r) => n + r.body.images.length, 0);
  const mins = ((Date.now() - started) / 60000).toFixed(1);

  console.log('\n' + '='.repeat(64));
  console.log('MIGRATION REPORT');
  console.log('='.repeat(64));
  console.log(`Collection          : ${args.collection}`);
  console.log(`Listed              : ${before}`);
  console.log(`Fetched             : ${results.length}`);
  console.log(`Failed              : ${failures.length}`);
  console.log(`Needing manual work : ${thin.length} (under 150 chars of copy)`);
  console.log(`Body copy recovered : ${totalChars.toLocaleString()} chars`);
  console.log(`Images referenced   : ${totalImages}`);
  console.log(`Elapsed             : ${mins} min`);

  if (thin.length) {
    console.log('\nThin items — check these by hand:');
    thin.forEach((r) => console.log(`  ${r.urlId} (${r.body.textLen}ch) ${SITE}${r.fullUrl}`));
  }

  if (failures.length) {
    console.log('\nFailures:');
    failures.forEach((f) => console.log(`  ${f.urlId}: ${f.error}`));
  }

  if (args.dryRun) {
    console.log('\nDry run — no file written.');
    return;
  }

  const out = args.out || path.join('data', `scraped-${args.collection}.wxr.xml`);
  fs.mkdirSync(path.dirname(out), { recursive: true });
  fs.writeFileSync(out, toWxr(results, args.collection));

  console.log(`\nWrote ${out} (${(fs.statSync(out).size / 1024).toFixed(1)} KB)`);
  console.log('Import with: WordPress admin → Tools → Import → WordPress');
}

main().catch((e) => {
  console.error('\nFATAL:', e.message);
  process.exit(1);
});
