#!/usr/bin/env node
/**
 * Build a self-contained WordPress Playground blueprint from the POC source.
 *
 *   node scripts/build-playground-blueprint.js
 *
 * Emits:
 *   dist/blueprint.json  — the blueprint
 *   dist/playground.url  — the one-click URL
 *   dist/DEMO.md         — instructions with the link
 *
 * Every theme/plugin file is inlined as a writeFile step with literal
 * contents, so the blueprint depends on no hosting at all — the whole POC
 * travels inside the URL fragment.
 */

const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');
const DIST = path.join(ROOT, 'dist');

const PLUGIN_SRC = path.join(ROOT, 'plugins', 'ilanel-poc-core');
const THEME_SRC = path.join(ROOT, 'themes', 'ilanel-poc');

const WP_PLUGIN_DIR = '/wordpress/wp-content/plugins/ilanel-poc-core';
const WP_THEME_DIR = '/wordpress/wp-content/themes/ilanel-poc';

/**
 * Recursively collect files under `dir`, returned as paths relative to it.
 */
function collectFiles(dir, base = dir) {
  const out = [];

  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, entry.name);

    if (entry.isDirectory()) {
      out.push(...collectFiles(full, base));
    } else if (entry.isFile()) {
      out.push(path.relative(base, full).split(path.sep).join('/'));
    }
  }

  return out.sort();
}

/**
 * Build writeFile steps for every file in a source tree.
 */
function writeFileSteps(srcDir, wpDir) {
  return collectFiles(srcDir).map((rel) => ({
    step: 'writeFile',
    path: `${wpDir}/${rel}`,
    data: fs.readFileSync(path.join(srcDir, rel), 'utf8'),
  }));
}

/**
 * Directories that must exist before their files are written.
 */
function mkdirSteps(srcDir, wpDir) {
  const dirs = new Set([wpDir]);

  for (const rel of collectFiles(srcDir)) {
    const parts = rel.split('/');
    parts.pop();

    let acc = wpDir;
    for (const part of parts) {
      acc = `${acc}/${part}`;
      dirs.add(acc);
    }
  }

  return [...dirs].sort().map((dir) => ({ step: 'mkdir', path: dir }));
}

// --- Seed data -------------------------------------------------------------

const seed = JSON.parse(
  fs.readFileSync(path.join(ROOT, 'data', 'products.json'), 'utf8')
);

/**
 * PHP that seeds ranges and products.
 *
 * Mirrors scripts/seed-products.php, but written for runPHP (no WP-CLI in
 * Playground). Kept deliberately close to the CLI seeder so the two don't
 * drift in behaviour.
 */
function buildSeedPhp() {
  const products = seed.products.map((p) => ({
    slug: p.slug,
    name: p.name,
    type: p.type,
    description: p.meta_description,
    spec_pdf: p.spec_pdf,
    range: p.range,
  }));

  const payload = JSON.stringify({ products }, null, 2);

  return `<?php
require_once '/wordpress/wp-load.php';

if ( ! class_exists( 'WooCommerce' ) ) {
    echo "WooCommerce not active\\n";
    return;
}

$data = json_decode( <<<'JSON'
${payload}
JSON
, true );

$ranges = array(
    'pendants'    => 'Pendants',
    'wall-lights' => 'Wall Lights',
);

$range_ids = array();

foreach ( $ranges as $slug => $name ) {
    $existing = get_term_by( 'slug', $slug, 'ilanel_range' );

    if ( $existing ) {
        $range_ids[ $slug ] = $existing->term_id;
        continue;
    }

    $created = wp_insert_term( $name, 'ilanel_range', array( 'slug' => $slug ) );

    if ( ! is_wp_error( $created ) ) {
        $range_ids[ $slug ] = $created['term_id'];
    }
}

// Give the Pendants range a description so CollectionPage schema has one.
if ( ! empty( $range_ids['pendants'] ) ) {
    wp_update_term(
        $range_ids['pendants'],
        'ilanel_range',
        array( 'description' => ${JSON.stringify(seed.range.description)} )
    );
}

foreach ( $data['products'] as $item ) {
    $existing = get_page_by_path( $item['slug'], OBJECT, 'product' );

    $product = $existing ? wc_get_product( $existing->ID ) : new WC_Product_Simple();

    $product->set_name( $item['name'] );
    $product->set_slug( $item['slug'] );
    $product->set_status( 'publish' );
    $product->set_catalog_visibility( 'visible' );
    $product->set_short_description( $item['description'] );
    $product->set_manage_stock( false );
    $product->set_stock_status( 'onbackorder' );

    // DEMO ONLY — illustrative prices so the Offer node renders.
    // Real figures must come from Commerce. See docs/OPEN-QUESTIONS.md.
    $demo_prices = array(
        'comet-pendant'          => '2450.00',
        'comet-stardust-pendant' => '2680.00',
        'kahdu-pendant'          => '1890.00',
        'dais-wall-light'        => '1240.00',
    );

    if ( isset( $demo_prices[ $item['slug'] ] ) ) {
        $product->set_regular_price( $demo_prices[ $item['slug'] ] );
    }

    $product->set_sku( 'DEMO-' . strtoupper( str_replace( '-', '', $item['slug'] ) ) );

    $product_id = $product->save();

    if ( ! $product_id ) {
        continue;
    }

    if ( ! empty( $range_ids[ $item['range'] ] ) ) {
        wp_set_object_terms( $product_id, array( (int) $range_ids[ $item['range'] ] ), 'ilanel_range' );
    }

    update_post_meta( $product_id, '_ilanel_spec_pdf', $item['spec_pdf'] );
    update_post_meta( $product_id, '_ilanel_product_type_label', $item['type'] );
    update_post_meta( $product_id, '_ilanel_lead_time', '4–12 weeks' );
    update_post_meta( $product_id, '_ilanel_made_in', 'Melbourne, Australia' );
    update_post_meta(
        $product_id,
        '_ilanel_finishes',
        "Brushed Brass\\nBlackened Steel\\nPolished Nickel\\n(placeholder — confirm from spec sheet)"
    );
}

flush_rewrite_rules();

echo "Seeded " . count( $data['products'] ) . " products\\n";
`;
}

// --- Assemble the blueprint ------------------------------------------------

const blueprint = {
  $schema: 'https://playground.wordpress.net/blueprint-schema.json',
  landingPage: '/our-range/pendants/',
  preferredVersions: {
    php: '8.2',
    wp: 'latest',
  },
  features: {
    networking: true,
  },
  steps: [
    { step: 'login', username: 'admin', password: 'password' },

    {
      step: 'installPlugin',
      pluginData: { resource: 'wordpress.org/plugins', slug: 'woocommerce' },
      options: { activate: true },
    },

    ...mkdirSteps(PLUGIN_SRC, WP_PLUGIN_DIR),
    ...writeFileSteps(PLUGIN_SRC, WP_PLUGIN_DIR),

    ...mkdirSteps(THEME_SRC, WP_THEME_DIR),
    ...writeFileSteps(THEME_SRC, WP_THEME_DIR),

    { step: 'activatePlugin', pluginPath: 'ilanel-poc-core/ilanel-poc-core.php' },
    { step: 'activateTheme', themeFolderName: 'ilanel-poc' },

    {
      step: 'setSiteOptions',
      options: {
        blogname: 'ILANEL',
        blogdescription: 'Light . Art . Design',
        woocommerce_currency: 'AUD',
        woocommerce_default_country: 'AU:VIC',
        permalink_structure: '/%postname%/',
        woocommerce_permalinks:
          '{"product_base":"/product","category_base":"product-category","tag_base":"product-tag","attribute_base":""}',
      },
    },

    { step: 'runPHP', code: buildSeedPhp() },
  ],
};

// --- Emit ------------------------------------------------------------------

fs.mkdirSync(DIST, { recursive: true });

const blueprintJson = JSON.stringify(blueprint, null, 2);
fs.writeFileSync(path.join(DIST, 'blueprint.json'), blueprintJson);

const fileCount = blueprint.steps.filter((s) => s.step === 'writeFile').length;
const fragmentLength =
  'https://playground.wordpress.net/#'.length +
  encodeURIComponent(JSON.stringify(blueprint)).length;

// Deliberately NOT emitting a fragment URL.
//
// A fragment this large gets truncated in transit — the JSON is cut
// mid-string and Playground parses the remainder as PHP, failing with
// "unexpected fully qualified name" in /wordpress/code.php. That is a
// confusing error that looks like a code bug but is purely a transport
// limit, so we don't ship the footgun. Use file upload or ?blueprint-url=.
const FRAGMENT_SAFE_LIMIT = 32000;

fs.writeFileSync(
  path.join(DIST, 'DEMO.md'),
  buildDemoDoc(fragmentLength, fileCount, blueprint.steps.length)
);

console.log(`blueprint.json    ${(blueprintJson.length / 1024).toFixed(1)} KB`);
console.log(`steps             ${blueprint.steps.length}`);
console.log(`files inlined     ${fileCount}`);
console.log(`as fragment URL   ${fragmentLength} chars`);

if (fragmentLength > FRAGMENT_SAFE_LIMIT) {
  console.log('');
  console.log(`Too large for a #fragment URL (limit ~${FRAGMENT_SAFE_LIMIT}).`);
  console.log('Load it one of these two ways — see dist/DEMO.md:');
  console.log('  1. playground.wordpress.net -> "Load Blueprint" -> upload blueprint.json');
  console.log('  2. host blueprint.json, then ?blueprint-url=<raw-url>');
}

/**
 * Instructions written alongside the blueprint, so the demo is
 * reproducible without reading this script.
 */
function buildDemoDoc(fragmentLength, fileCount, stepCount) {
  return `# ILANEL POC — Playground demo

Generated by \`scripts/build-playground-blueprint.js\`. Do not edit by hand;
re-run the script instead.

**${stepCount} steps · ${fileCount} files inlined · ${(blueprintJson.length / 1024).toFixed(1)} KB**

---

## How to run it

### Option 1 — file upload (no hosting, works now)

1. Open <https://playground.wordpress.net/>
2. Open the Playground **Dock** → **Load Blueprint**
3. Upload \`dist/blueprint.json\`

### Option 2 — one-click link (needs the JSON hosted)

Host \`blueprint.json\` anywhere public that sends
\`Access-Control-Allow-Origin: *\` (a GitHub gist raw URL works), then:

\`\`\`
https://playground.wordpress.net/?blueprint-url=<RAW_JSON_URL>
\`\`\`

### Why there is no #fragment URL

As a URL fragment this blueprint is **${fragmentLength} characters**, well past
the ~${FRAGMENT_SAFE_LIMIT} browsers handle reliably. The fragment gets truncated
mid-string, and Playground then parses the leftover text as PHP:

\`\`\`
Parse error: syntax error, unexpected fully qualified name "\\n\\ndefined"
in /wordpress/code.php on line 38
\`\`\`

That error looks like broken code but is purely a transport limit — the
blueprint itself is valid. Use option 1 or 2.

---

## What you should see

Lands on **\`/our-range/pendants/\`**, the range page.

| Check | Where | Expected |
|---|---|---|
| Breadcrumbs | top of page | Home / Pendants |
| Filters | above the grid | Pendants (3) · Wall Lights (1) |
| Products | grid | Comet · Comet Stardust · Kahdu |
| CollectionPage schema | view source | \`"@type": "CollectionPage"\` with ItemList |

Then open **Comet**:

| Check | Expected |
|---|---|
| \`<h1>\` count | **exactly 1** — live ilanel.com has 4 on this page |
| Specification block | SKU, finishes, lead time, made in, spec PDF link |
| Product schema | \`"@type": "Product"\` with full \`offers\` node |
| Breadcrumb schema | \`BreadcrumbList\`, 3 items |

Count H1s in the browser console:

\`\`\`js
document.querySelectorAll('h1').length   // 1
\`\`\`

The same check on the live site returns **4**:
<https://www.ilanel.com/lighting-design-collections/comet-pendant-light>

---

## What is fake here

- **Prices are invented** (\`2450.00\`, \`2680.00\`, \`1890.00\`, \`1240.00\`) so the
  Offer node renders. **Not ILANEL prices — do not quote them.**
- **SKUs are \`DEMO-*\`** placeholders.
- **Finishes are placeholder** — real ones are in the spec PDFs.
- **No photography** — grey blocks. The studio supplies images.
- **Styling is deliberately neutral.** Judge the structure, not the look.

See \`docs/OPEN-QUESTIONS.md\`.

---

## Playground caveats

- **Nothing persists.** Close the tab and it is gone. It is a demo, not a host.
- Admin login is \`admin\` / \`password\`.
- Runs entirely in the browser via WebAssembly; nothing is deployed anywhere.
`;
}
