#!/usr/bin/env node
/**
 * Read-only scraper for ilanel.com's plain content pages (About, FAQ, Trade,
 * Warranty, Privacy Policy, Terms & Conditions, Contact) — Phase 3a of
 * docs/LAUNCH-PLAN.md. These aren't a Squarespace collection (no ?format=json
 * index), just individual pages, so this fetches each URL directly and reuses
 * the same `.sqs-block-content` extraction as scrape-squarespace.js.
 *
 * READ-ONLY. Issues GETs only, never writes to Squarespace.
 *
 * Usage:
 *   node scripts/scrape-static-pages.js
 *   node scripts/scrape-static-pages.js --dry-run
 */

'use strict';

const fs = require('fs');
const path = require('path');
const https = require('https');

const SITE = 'https://www.ilanel.com';
const UA = 'Mozilla/5.0 (compatible; ilanel-migration-audit/1.0; read-only)';
const DELAY_MS = 900;

// slug here is what this site will use; source is ilanel.com's own path,
// which occasionally differs (privacy-policy redirects to /privacy live).
const PAGES = [
  { slug: 'about', source: '/about', title: 'Our Story' },
  { slug: 'faq', source: '/faq', title: 'FAQ' },
  { slug: 'trade', source: '/trade', title: 'Trade Programme' },
  { slug: 'warranty', source: '/warranty', title: 'Warranty' },
  { slug: 'privacy-policy', source: '/privacy', title: 'Privacy Policy' },
  { slug: 'terms-and-conditions', source: '/terms-and-conditions', title: 'Terms & Conditions' },
  { slug: 'contact', source: '/contact', title: 'Contact' },
];

function get(url, redirects = 0) {
  return new Promise((resolve, reject) => {
    if (redirects > 5) return reject(new Error('too many redirects: ' + url));

    https
      .get(url, { headers: { 'User-Agent': UA, Accept: '*/*' } }, (res) => {
        const { statusCode, headers } = res;

        if (statusCode >= 300 && statusCode < 400 && headers.location) {
          res.resume();
          const next = headers.location.startsWith('http') ? headers.location : SITE + headers.location;
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

const decodeEntities = (s) =>
  s
    .replace(/&amp;/g, '&')
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/&#39;/g, "'")
    .replace(/&quot;/g, '"')
    .replace(/&nbsp;/g, ' ');

const stripTags = (s) => decodeEntities(s.replace(/<[^>]+>/g, ' ')).replace(/\s+/g, ' ').trim();

/**
 * Squarespace's FAQ accordion block doesn't use plain sqs-block-content
 * paragraphs — question/answer pairs live in accordion-item__title /
 * accordion-item__description spans instead (found by inspecting the raw
 * FAQ page: the generic extractor below returned 34 chars for a page with
 * real content). Detected and handled separately so the output is real
 * <details>/<summary> markup, not lost content.
 */
function extractAccordion(scope) {
  const items = [];
  const itemRe = /<li class="accordion-item"[\s\S]*?<\/li>\s*(?=<li class="accordion-item"|<\/ul>)/g;

  for (const m of scope.matchAll(itemRe)) {
    const chunk = m[0];
    const titleMatch = chunk.match(/<span class="accordion-item__title">([\s\S]*?)<\/span>/);
    const descMatch = chunk.match(
      /accordion-item__description[\s\S]*?>\s*([\s\S]*?)<\/div>\s*<\/div>/
    );

    if (!titleMatch || !descMatch) continue;

    const question = stripTags(titleMatch[1]);
    const answerHtml = descMatch[1].trim();
    const answerText = stripTags(answerHtml);

    if (question && answerText) {
      items.push({ question, answerHtml, answerText });
    }
  }

  return items;
}

/**
 * These pages get real prose in this theme's own typography, not a replica
 * of Squarespace's block chrome — so unlike scrape-squarespace.js (which
 * keeps images/embeds for projects/news), this keeps only paragraphs,
 * headings and links, and drops image wrappers, video embeds and forms
 * entirely. sqs-block-content divs nest arbitrarily deep (image blocks
 * wrap other blocks), so a non-greedy "first </div>" match truncates or
 * bleeds into unrelated blocks — instead this walks each block's own
 * innermost <p>/<h1-4>/<ul>/<ol> tags directly, which are never nested
 * inside each other in Squarespace's own output.
 */
function extractProse(scope) {
  const tagRe = /<(p|h[1-4]|ul|ol)\b[^>]*>[\s\S]*?<\/\1>/gi;
  const seen = new Set();
  const out = [];

  for (const m of scope.matchAll(tagRe)) {
    let chunk = m[0];

    // Drop empty/whitespace-only paragraphs (Squarespace leaves many as spacers).
    const text = stripTags(chunk);
    if (!text) continue;

    // De-dupe: nested list/heading matches inside a matched <ul> etc. can
    // recur if the same chunk is matched twice at different scan positions.
    if (seen.has(chunk)) continue;
    seen.add(chunk);

    // Strip inline attributes Squarespace adds (style, data-*, class) but
    // keep the tag and href for links. "/s/..." is Squarespace's own path
    // convention for uploaded files (PDFs, etc.) — those aren't sideloaded
    // by this scraper, so a relative "/s/..." link 404s on this site. Other
    // relative links are ordinary page links ("/trade", "/warranty") that
    // resolve fine here once the matching page exists, so only "/s/" paths
    // get absolutized against the real ilanel.com, which still serves them.
    chunk = chunk
      .replace(/<(p|h[1-4]|ul|ol|li)\b[^>]*>/gi, '<$1>')
      .replace(/<a\b[^>]*href="([^"]*)"[^>]*>/gi, (m, href) => {
        const abs = href.startsWith('/s/') ? SITE + href : href;
        return `<a href="${abs}">`;
      })
      .replace(/<span[^>]*>/gi, '')
      .replace(/<\/span>/gi, '')
      .replace(/\sstyle="[^"]*"/gi, '')
      .replace(/\sdata-[a-z-]+="[^"]*"/gi, '')
      .replace(/\sclass="[^"]*"/gi, '');

    out.push(chunk);
  }

  return out;
}

/** Same top-level scoping as scrape-squarespace.js extractBody(), plus accordion support. */
function extractBody(html) {
  const mainMatch = html.match(/<main[\s\S]*?<\/main>/i);
  const scope = mainMatch ? mainMatch[0] : html;

  const clean = (s) =>
    s
      .replace(/<script[\s\S]*?<\/script>/gi, '')
      .replace(/<style[\s\S]*?<\/style>/gi, '')
      .replace(/<noscript[\s\S]*?<\/noscript>/gi, '')
      .replace(/<form[\s\S]*?<\/form>/gi, '')
      .replace(/<iframe[\s\S]*?<\/iframe>/gi, '');

  const scoped = clean(scope);

  const accordionItems = extractAccordion(scoped);

  let html_out;
  if (accordionItems.length) {
    html_out = accordionItems
      .map((it) => `<details><summary>${it.question}</summary>\n${it.answerHtml}\n</details>`)
      .join('\n\n');
  } else {
    const paras = extractProse(scoped);
    html_out = paras.join('\n\n');
  }

  const text = accordionItems.length
    ? accordionItems.map((it) => `${it.question} ${it.answerText}`).join(' ')
    : stripTags(html_out);

  return { html: html_out, text, textLen: text.length, accordionCount: accordionItems.length };
}

async function main() {
  const dryRun = process.argv.includes('--dry-run');
  const out = [];

  for (const page of PAGES) {
    const url = SITE + page.source;
    process.stderr.write(`fetching ${url} ... `);

    let html;
    try {
      html = await get(url);
    } catch (e) {
      process.stderr.write(`FAILED (${e.message})\n`);
      continue;
    }

    const body = extractBody(html);
    // Squarespace's <title> tag is noisy ("Page Name : subtitle | Ilanel",
    // sometimes duplicated) — the curated PAGES list above is more reliable.
    const title = page.title;

    process.stderr.write(`ok, ${body.textLen} chars\n`);

    if (body.textLen < 40) {
      process.stderr.write(`  WARNING: ${page.slug} extracted suspiciously little text — check template match\n`);
    }

    out.push({
      slug: page.slug,
      title,
      content_html: body.html,
      source_url: url,
    });

    await sleep(DELAY_MS);
  }

  if (dryRun) {
    console.log(JSON.stringify(out, null, 2));
    return;
  }

  const outPath = path.join(__dirname, '..', 'data', 'static-pages.json');
  fs.writeFileSync(outPath, JSON.stringify(out, null, 2));
  process.stderr.write(`\nWrote ${out.length} pages to ${outPath}\n`);
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
