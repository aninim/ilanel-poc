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
    image: p.image,
    gallery: p.gallery || [],
    story: p.story || [],
    swatches: (p.finishes || []).filter((f) => Array.isArray(f)).map(([name, image]) => ({ name, image })),

    // Real catalogue structure from the authenticated Squarespace Commerce
    // export. Drives variable-product creation; see docs/PHASE-2-PLAN.md.
    attributes: (p.commerce && p.commerce.attributes) || {},
    variants: (p.commerce && p.commerce.variants) || [],
  }));

  /*
   * Projects that feature a seeded product, with real copy and photography
   * recovered by scripts/scrape-squarespace.js (Squarespace's own WXR export
   * omitted projects entirely).
   *
   * Only the linking four are inlined — the blueprint is already ~190KB and
   * all 51 would bloat it for no demonstrative gain. The scraper produces the
   * full set for a real import.
   */
  let projects = [];

  try {
    projects = JSON.parse(fs.readFileSync(path.join(ROOT, 'data', 'projects.json'), 'utf8'));
  } catch (e) {
    console.warn('  ! data/projects.json missing — seeding no projects');
  }

  const payload = JSON.stringify({ products, projects }, null, 2);

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

/**
 * Attribute names are used as meta keys on variations, so they must be
 * normalised identically when the attribute is declared and when each
 * variation is matched. "Drop Height" -> "drop-height".
 */
function ilanel_attr_key( $name ) {
    return sanitize_title( $name );
}

foreach ( $data['products'] as $item ) {
    $existing  = get_page_by_path( $item['slug'], OBJECT, 'product' );
    $has_variants = ! empty( $item['variants'] );

    /*
     * Products with real variant data become variable products; everything
     * else stays simple. Real ILANEL data: Comet is 36 variations across
     * Size x Color x Glass, Kahdu 24 across Color x Shape.
     */
    if ( $existing ) {
        $product = wc_get_product( $existing->ID );

        // A previously-seeded simple product cannot gain variations; rebuild
        // it as the right class rather than silently keeping the old type.
        if ( $has_variants && ! $product->is_type( 'variable' ) ) {
            $product = new WC_Product_Variable( $existing->ID );
        }
    } else {
        $product = $has_variants ? new WC_Product_Variable() : new WC_Product_Simple();
    }

    $product->set_name( $item['name'] );
    $product->set_slug( $item['slug'] );
    $product->set_status( 'publish' );
    $product->set_catalog_visibility( 'visible' );
    $product->set_short_description( $item['description'] );

    if ( $has_variants ) {
        /*
         * Local attributes (set_id(0)), not global pa_* taxonomies.
         * Global attributes need their taxonomy registered and rewrite rules
         * flushed before terms can be assigned, which is unreliable inside
         * Playground's single runPHP step. The trade-off is no attribute
         * archive pages, which this POC does not use.
         */
        $attributes = array();
        $position   = 0;

        foreach ( $item['attributes'] as $attr_name => $attr_values ) {
            $attribute = new WC_Product_Attribute();
            $attribute->set_id( 0 );
            $attribute->set_name( $attr_name );
            $attribute->set_options( array_values( $attr_values ) );
            $attribute->set_position( $position++ );
            $attribute->set_visible( true );
            $attribute->set_variation( true );

            $attributes[] = $attribute;
        }

        $product->set_attributes( $attributes );
    } else {
        $product->set_manage_stock( false );
        $product->set_stock_status( 'onbackorder' );
        $product->set_sku( 'DEMO-' . strtoupper( str_replace( '-', '', $item['slug'] ) ) );
    }

    $product_id = $product->save();

    if ( ! $product_id ) {
        continue;
    }

    if ( $has_variants ) {
        // Re-seeding must not multiply children.
        foreach ( $product->get_children() as $old_child_id ) {
            $old_child = wc_get_product( $old_child_id );
            if ( $old_child ) {
                $old_child->delete( true );
            }
        }

        foreach ( $item['variants'] as $variant ) {
            $variation = new WC_Product_Variation();
            $variation->set_parent_id( $product_id );
            $variation->set_status( 'publish' );

            $variation_attributes = array();
            foreach ( $variant['options'] as $opt_name => $opt_value ) {
                $variation_attributes[ ilanel_attr_key( $opt_name ) ] = $opt_value;
            }
            $variation->set_attributes( $variation_attributes );

            $variation->set_regular_price( (string) $variant['price'] );

            if ( ! empty( $variant['sku'] ) ) {
                $variation->set_sku( $variant['sku'] );
            }

            // Made to order — every variation is a backorder, matching the
            // 4-12 week lead time shown on the product page.
            $variation->set_manage_stock( false );
            $variation->set_stock_status( 'onbackorder' );

            $variation->save();
        }

        // Without this the parent has no price range and shows no price.
        WC_Product_Variable::sync( $product_id );
    }

    if ( ! empty( $range_ids[ $item['range'] ] ) ) {
        wp_set_object_terms( $product_id, array( (int) $range_ids[ $item['range'] ] ), 'ilanel_range' );
    }

    // Sideload the product photograph from ILANEL's CDN so the demo shows
    // the real piece rather than a placeholder tile. Skipped if already
    // attached, so re-running does not duplicate media.
    if ( ! empty( $item['image'] ) && ! get_post_thumbnail_id( $product_id ) ) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment_id = media_sideload_image( $item['image'], $product_id, $item['name'], 'id' );

        if ( ! is_wp_error( $attachment_id ) ) {
            set_post_thumbnail( $product_id, $attachment_id );
        }
    }

    // Presentation data for the hero carousel, storytelling rows and
    // configurator. Stored as arrays; see ILANEL_Product_Meta.
    if ( ! empty( $item['gallery'] ) ) {
        update_post_meta( $product_id, '_ilanel_gallery', array_map( 'esc_url_raw', $item['gallery'] ) );
    }
    if ( ! empty( $item['story'] ) ) {
        update_post_meta( $product_id, '_ilanel_story', array_map( 'esc_url_raw', $item['story'] ) );
    }
    if ( ! empty( $item['swatches'] ) ) {
        $clean = array();
        foreach ( $item['swatches'] as $swatch ) {
            $clean[] = array(
                'name'  => sanitize_text_field( $swatch['name'] ),
                'image' => esc_url_raw( $swatch['image'] ),
            );
        }
        update_post_meta( $product_id, '_ilanel_swatches', $clean );
    }

    update_post_meta( $product_id, '_ilanel_spec_pdf', $item['spec_pdf'] );
    update_post_meta( $product_id, '_ilanel_product_type_label', $item['type'] );
    update_post_meta( $product_id, '_ilanel_lead_time', '4–12 weeks' );
    update_post_meta( $product_id, '_ilanel_made_in', 'Melbourne, Australia' );
    // Text finish list. Where swatches exist we derive it from them so the
    // two cannot disagree.
    if ( ! empty( $item['swatches'] ) ) {
        $names = wp_list_pluck( $item['swatches'], 'name' );
        update_post_meta( $product_id, '_ilanel_finishes', implode( "\\n", array_map( 'sanitize_text_field', $names ) ) );
    } else {
        update_post_meta(
            $product_id,
            '_ilanel_finishes',
            "Brushed Brass\\nBlackened Steel\\nPolished Nickel\\n(placeholder — confirm from spec sheet)"
        );
    }
}

/*
 * Projects, and the product <-> project relation.
 *
 * This is the WooCommerce argument made concrete: products and projects are
 * both posts in one database, so the join is native rather than a page-builder
 * embed. Copy and photography are ILANEL's own, recovered from the live site.
 */
if ( ! empty( $data['projects'] ) && post_type_exists( 'project' ) ) {

    foreach ( $data['projects'] as $proj ) {
        $existing = get_page_by_path( $proj['slug'], OBJECT, 'project' );

        $content = '';
        foreach ( $proj['paragraphs'] as $para ) {
            $content .= '<p>' . wp_kses_post( $para ) . "</p>\\n\\n";
        }

        if ( $existing ) {
            $project_id = $existing->ID;
        } else {
            $project_id = wp_insert_post(
                array(
                    'post_title'   => sanitize_text_field( $proj['title'] ),
                    'post_name'    => sanitize_title( $proj['slug'] ),
                    'post_type'    => 'project',
                    'post_status'  => 'publish',
                    'post_content' => $content,
                )
            );
        }

        if ( ! $project_id || is_wp_error( $project_id ) ) {
            continue;
        }

        // Featured image, sideloaded from ILANEL's CDN.
        if ( ! empty( $proj['image'] ) && ! get_post_thumbnail_id( $project_id ) ) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            $att_id = media_sideload_image( $proj['image'], $project_id, $proj['title'], 'id' );

            if ( ! is_wp_error( $att_id ) ) {
                set_post_thumbnail( $project_id, $att_id );
            }
        }

        if ( ! empty( $proj['gallery'] ) ) {
            update_post_meta( $project_id, '_ilanel_project_gallery', array_map( 'esc_url_raw', $proj['gallery'] ) );
        }

        // The relation itself. Stored on the product only — the reverse
        // direction is a query, so the two can never disagree.
        foreach ( $proj['products'] as $product_slug ) {
            $product_post = get_page_by_path( $product_slug, OBJECT, 'product' );

            if ( $product_post && class_exists( 'ILANEL_Projects' ) ) {
                ILANEL_Projects::link( $product_post->ID, $project_id );
            }
        }
    }

    echo "Seeded " . count( $data['projects'] ) . " projects\\n";
}

/*
 * Front page.
 *
 * front-page.php takes precedence over the page content in the template
 * hierarchy, so this page exists purely to give WordPress something to set
 * as "show on front" — the markup all comes from the template. Guarded by
 * slug so re-running does not create duplicates.
 */
$home = get_page_by_path( 'home' );

if ( ! $home ) {
    $home_id = wp_insert_post(
        array(
            'post_title'   => 'Home',
            'post_name'    => 'home',
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_content' => '',
        )
    );
} else {
    $home_id = $home->ID;
}

if ( $home_id && ! is_wp_error( $home_id ) ) {
    update_option( 'show_on_front', 'page' );
    update_option( 'page_on_front', $home_id );
}

flush_rewrite_rules();

echo "Seeded " . count( $data['products'] ) . " products\\n";
`;
}

// --- Assemble the blueprint ------------------------------------------------

const blueprint = {
  $schema: 'https://playground.wordpress.net/blueprint-schema.json',
  // The homepage is now the front door of the demo; the range archive is one
  // click away from it.
  landingPage: '/',
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

// Single-line copy, easier to select and paste into the Playground editor
// than 1,400 pretty-printed lines.
fs.writeFileSync(path.join(DIST, 'blueprint.min.json'), JSON.stringify(blueprint));

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
  console.log('  1. playground.wordpress.net -> bottom toolbar "New" ->');
  console.log('     "Write a Blueprint" -> paste dist/blueprint.min.json');
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

### Option 1 — paste the JSON (no hosting, works now)

1. Open <https://playground.wordpress.net/>
2. In the **bottom toolbar**, click **New** → **Write a Blueprint**
3. Select all the example JSON in the editor and replace it with the
   contents of **\`dist/blueprint.min.json\`** (one line — far easier to
   copy than the pretty-printed \`blueprint.json\`)
4. Click **Create Playground**

To copy it to the clipboard on Windows:

\`\`\`powershell
Get-Content dist/blueprint.min.json -Raw | Set-Clipboard
\`\`\`

The editor holds the whole ${(blueprintJson.length / 1024).toFixed(0)} KB fine — the size limit only affects
URLs, not pasted text.

> Older docs describe a "Dock → Load Blueprint" file upload. The current UI
> puts **New** in the bottom toolbar instead; there is no separate upload
> button. Use **Write a Blueprint** and paste.

### Option 2 — one-click link (needs the JSON hosted)

Host \`blueprint.json\` anywhere public that sends
\`Access-Control-Allow-Origin: *\` (a GitHub gist raw URL works), then:

\`\`\`
https://playground.wordpress.net/?blueprint-url=<RAW_JSON_URL>
\`\`\`

This is the only path that gives a genuine one-click link.

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
