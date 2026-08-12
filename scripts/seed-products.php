<?php
/**
 * Seed the POC with real ILANEL product data.
 *
 * Run via WP-CLI from the WordPress root:
 *
 *   wp eval-file scripts/seed-products.php
 *
 * Idempotent: re-running updates existing products by slug rather than
 * creating duplicates, so it is safe to run after editing data/products.json.
 *
 * Prices, SKUs and variations come from the authenticated Squarespace Commerce
 * export and are REAL. (They were placeholders before Task 1; that note is now
 * obsolete.) Photography is ILANEL's own, served from their CDN.
 *
 * Kept deliberately in step with scripts/build-playground-blueprint.js — the
 * demo and a real install must agree about what a product is.
 *
 * @package ILANEL_POC
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	echo "This script must be run through WP-CLI: wp eval-file scripts/seed-products.php\n";
	exit( 1 );
}

if ( ! class_exists( 'WooCommerce' ) ) {
	WP_CLI::error( 'WooCommerce is not active.' );
}

// media_sideload_image() and its dependencies live in wp-admin and are not
// loaded in a WP-CLI context.
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$ilanel_data_file = __DIR__ . '/../data/products.json';

if ( ! file_exists( $ilanel_data_file ) ) {
	WP_CLI::error( 'Seed data not found at ' . $ilanel_data_file );
}

$ilanel_data = json_decode( file_get_contents( $ilanel_data_file ), true );

if ( ! is_array( $ilanel_data ) || empty( $ilanel_data['products'] ) ) {
	WP_CLI::error( 'Seed data is malformed — expected a "products" array.' );
}

/**
 * Ensure a range term exists and return its ID.
 *
 * @param string $slug Term slug.
 * @param string $name Term name.
 * @param string $description Term description.
 * @return int
 */
function ilanel_seed_range( $slug, $name, $description = '' ) {
	$existing = get_term_by( 'slug', $slug, ILANEL_Taxonomies::RANGE_TAXONOMY );

	if ( $existing instanceof WP_Term ) {
		if ( $description ) {
			wp_update_term( $existing->term_id, ILANEL_Taxonomies::RANGE_TAXONOMY, array( 'description' => $description ) );
		}
		return $existing->term_id;
	}

	$created = wp_insert_term(
		$name,
		ILANEL_Taxonomies::RANGE_TAXONOMY,
		array(
			'slug'        => $slug,
			'description' => $description,
		)
	);

	if ( is_wp_error( $created ) ) {
		WP_CLI::warning( 'Could not create range "' . $name . '": ' . $created->get_error_message() );
		return 0;
	}

	WP_CLI::log( '  + range: ' . $name );

	return $created['term_id'];
}

// Ranges referenced by the seed products.
$ilanel_range_names = array(
	'pendants'    => 'Pendants',
	'wall-lights' => 'Wall Lights',
	'chandeliers' => 'Chandeliers',
	'lamps'       => 'Lamps',
	'editions'    => 'Editions',
);

$ilanel_range_ids = array();

foreach ( $ilanel_range_names as $ilanel_slug => $ilanel_name ) {
	$ilanel_description = '';

	if ( isset( $ilanel_data['range']['slug'] ) && $ilanel_data['range']['slug'] === $ilanel_slug ) {
		$ilanel_description = $ilanel_data['range']['description'];
	}

	$ilanel_range_ids[ $ilanel_slug ] = ilanel_seed_range( $ilanel_slug, $ilanel_name, $ilanel_description );
}

$ilanel_created = 0;
$ilanel_updated = 0;

foreach ( $ilanel_data['products'] as $ilanel_item ) {
	$ilanel_existing = get_page_by_path( $ilanel_item['slug'], OBJECT, 'product' );

	/*
	 * Products carrying real variant data become variable products; the rest
	 * stay simple. This mirrors scripts/build-playground-blueprint.js —
	 * the two seeders must not drift, or the demo and a real install
	 * disagree about what a product even is.
	 *
	 * Real ILANEL data: Comet is 36 variations across Size x Color x Glass,
	 * Kahdu 24 across Color x Shape, Dais 4, Comet Stardust 3.
	 */
	$ilanel_variants   = isset( $ilanel_item['commerce']['variants'] ) ? $ilanel_item['commerce']['variants'] : array();
	$ilanel_attributes = isset( $ilanel_item['commerce']['attributes'] ) ? $ilanel_item['commerce']['attributes'] : array();
	$ilanel_has_variants = ! empty( $ilanel_variants );

	if ( $ilanel_existing ) {
		$ilanel_product = wc_get_product( $ilanel_existing->ID );

		// A simple product cannot gain variations; rebuild it as the right
		// class rather than silently keeping the old type.
		if ( $ilanel_has_variants && ! $ilanel_product->is_type( 'variable' ) ) {
			$ilanel_product = new WC_Product_Variable( $ilanel_existing->ID );
		}

		$ilanel_updated++;
	} else {
		$ilanel_product = $ilanel_has_variants ? new WC_Product_Variable() : new WC_Product_Simple();
		$ilanel_created++;
	}

	$ilanel_product->set_name( $ilanel_item['name'] );
	$ilanel_product->set_slug( $ilanel_item['slug'] );
	$ilanel_product->set_status( 'publish' );
	$ilanel_product->set_catalog_visibility( 'visible' );
	$ilanel_product->set_short_description( $ilanel_item['meta_description'] );

	// Full body copy scraped from the live product page.
	if ( ! empty( $ilanel_item['paragraphs'] ) ) {
		$ilanel_body = '';

		foreach ( $ilanel_item['paragraphs'] as $ilanel_para ) {
			$ilanel_body .= '<p>' . wp_kses_post( $ilanel_para ) . "</p>\n\n";
		}

		$ilanel_product->set_description( $ilanel_body );
	}

	if ( $ilanel_has_variants ) {
		/*
		 * Local attributes (set_id(0)), not global pa_* taxonomies — global
		 * ones need their taxonomy registered and rewrites flushed before
		 * terms can be assigned. The trade-off is no attribute archive
		 * pages, which this POC does not use.
		 */
		$ilanel_attr_objects = array();
		$ilanel_position     = 0;

		foreach ( $ilanel_attributes as $ilanel_attr_name => $ilanel_attr_values ) {
			$ilanel_attribute = new WC_Product_Attribute();
			$ilanel_attribute->set_id( 0 );
			$ilanel_attribute->set_name( $ilanel_attr_name );
			$ilanel_attribute->set_options( array_values( $ilanel_attr_values ) );
			$ilanel_attribute->set_position( $ilanel_position++ );
			$ilanel_attribute->set_visible( true );
			$ilanel_attribute->set_variation( true );

			$ilanel_attr_objects[] = $ilanel_attribute;
		}

		$ilanel_product->set_attributes( $ilanel_attr_objects );
	} else {
		// Made to order: in stock, but on backorder — matches the lead time.
		$ilanel_product->set_manage_stock( false );
		$ilanel_product->set_stock_status( 'onbackorder' );

		if ( ! empty( $ilanel_item['sku'] ) && 0 !== strpos( $ilanel_item['sku'], 'PLACEHOLDER' ) ) {
			$ilanel_product->set_sku( $ilanel_item['sku'] );
		}

		// Only set a price when the seed data carries a real one. An Offer
		// node without a price is invalid schema, so leave it absent.
		if ( ! empty( $ilanel_item['price'] ) && 'PLACEHOLDER' !== $ilanel_item['price'] ) {
			$ilanel_product->set_regular_price( (string) $ilanel_item['price'] );
		}
	}

	$ilanel_product_id = $ilanel_product->save();

	if ( ! $ilanel_product_id ) {
		WP_CLI::warning( 'Failed to save product: ' . $ilanel_item['name'] );
		continue;
	}

	if ( $ilanel_has_variants ) {
		// Re-seeding must not multiply children.
		foreach ( $ilanel_product->get_children() as $ilanel_old_child_id ) {
			$ilanel_old_child = wc_get_product( $ilanel_old_child_id );

			if ( $ilanel_old_child ) {
				$ilanel_old_child->delete( true );
			}
		}

		/*
		 * Colour name -> attachment id, so each variation can carry the
		 * photograph of its own finish. The Commerce export gives variants no
		 * imagery, so without this every variation inherits the parent image
		 * and the configurator preview never changes — price moves, picture
		 * does not, which reads as broken.
		 */
		$ilanel_swatch_attachments = array();

		foreach ( (array) $ilanel_item['finishes'] as $ilanel_finish ) {
			if ( ! is_array( $ilanel_finish ) || empty( $ilanel_finish[0] ) || empty( $ilanel_finish[1] ) ) {
				continue;
			}

			$ilanel_swatch_att = media_sideload_image(
				$ilanel_finish[1],
				$ilanel_product_id,
				$ilanel_item['name'] . ' - ' . $ilanel_finish[0],
				'id'
			);

			if ( ! is_wp_error( $ilanel_swatch_att ) ) {
				$ilanel_swatch_attachments[ $ilanel_finish[0] ] = $ilanel_swatch_att;
			}
		}

		foreach ( $ilanel_variants as $ilanel_variant ) {
			$ilanel_variation = new WC_Product_Variation();
			$ilanel_variation->set_parent_id( $ilanel_product_id );
			$ilanel_variation->set_status( 'publish' );

			$ilanel_variation_attrs = array();

			foreach ( $ilanel_variant['options'] as $ilanel_axis => $ilanel_value ) {
				$ilanel_variation_attrs[ sanitize_title( $ilanel_axis ) ] = $ilanel_value;
			}

			$ilanel_variation->set_attributes( $ilanel_variation_attrs );

			if ( isset( $ilanel_variant['price'] ) ) {
				$ilanel_variation->set_regular_price( (string) $ilanel_variant['price'] );
			}

			if ( ! empty( $ilanel_variant['sku'] ) ) {
				$ilanel_variation->set_sku( $ilanel_variant['sku'] );
			}

			/*
			 * Attach the finish photograph where one genuinely corresponds —
			 * exact match on a colour/finish axis only. A looser substring
			 * match was tried and rejected: it matched "Amber" from Comet's
			 * Glass axis and gave 18 variations the same wrong photograph.
			 * See the equivalent block in build-playground-blueprint.js.
			 */
			foreach ( $ilanel_variant['options'] as $ilanel_opt_name => $ilanel_opt_value ) {
				if ( ! preg_match( '/colou?r|finish/i', $ilanel_opt_name ) ) {
					continue;
				}

				if ( isset( $ilanel_swatch_attachments[ $ilanel_opt_value ] ) ) {
					$ilanel_variation->set_image_id( $ilanel_swatch_attachments[ $ilanel_opt_value ] );
					break;
				}
			}

			$ilanel_variation->set_manage_stock( false );
			$ilanel_variation->set_stock_status( 'onbackorder' );
			$ilanel_variation->save();
		}

		// Without this the parent has no price range at all.
		WC_Product_Variable::sync( $ilanel_product_id );
	}

	// Range assignment.
	$ilanel_range_slug = $ilanel_item['range'];
	if ( ! empty( $ilanel_range_ids[ $ilanel_range_slug ] ) ) {
		wp_set_object_terms(
			$ilanel_product_id,
			array( (int) $ilanel_range_ids[ $ilanel_range_slug ] ),
			ILANEL_Taxonomies::RANGE_TAXONOMY
		);
	}

	// Custom meta.
	update_post_meta( $ilanel_product_id, ILANEL_Product_Meta::FIELD_SPEC_PDF, esc_url_raw( $ilanel_item['spec_pdf'] ) );
	update_post_meta( $ilanel_product_id, ILANEL_Product_Meta::FIELD_TYPE_LABEL, sanitize_text_field( $ilanel_item['type'] ) );
	update_post_meta( $ilanel_product_id, ILANEL_Product_Meta::FIELD_LEAD_TIME, '4–12 weeks' );
	update_post_meta( $ilanel_product_id, ILANEL_Product_Meta::FIELD_MADE_IN, 'Melbourne, Australia' );

	if ( ! empty( $ilanel_item['finishes'] ) ) {
		update_post_meta(
			$ilanel_product_id,
			ILANEL_Product_Meta::FIELD_FINISHES,
			sanitize_textarea_field( implode( "\n", $ilanel_item['finishes'] ) )
		);
	}

	WP_CLI::log( '  · ' . $ilanel_item['name'] . ' (' . $ilanel_item['slug'] . ')' );
}

WP_CLI::success(
	sprintf(
		'Seeded %d new and updated %d existing products.',
		$ilanel_created,
		$ilanel_updated
	)
);

/*
 * Projects, and the product <-> project relation.
 *
 * Mirrors the blueprint seeder. Skipped silently when data/projects.json is
 * absent — it is generated by scripts/scrape-squarespace.js and is not
 * committed, so a fresh clone will not have it.
 */
$ilanel_projects_file = dirname( __DIR__ ) . '/data/projects.json';

if ( file_exists( $ilanel_projects_file ) && post_type_exists( 'project' ) ) {
	$ilanel_projects = json_decode( file_get_contents( $ilanel_projects_file ), true );

	$ilanel_project_count = 0;

	foreach ( (array) $ilanel_projects as $ilanel_proj ) {
		$ilanel_proj_existing = get_page_by_path( $ilanel_proj['slug'], OBJECT, 'project' );

		$ilanel_proj_content = '';

		foreach ( $ilanel_proj['paragraphs'] as $ilanel_para ) {
			$ilanel_proj_content .= '<p>' . wp_kses_post( $ilanel_para ) . "</p>\n\n";
		}

		if ( $ilanel_proj_existing ) {
			$ilanel_proj_id = $ilanel_proj_existing->ID;
		} else {
			$ilanel_proj_id = wp_insert_post(
				array(
					'post_title'   => sanitize_text_field( $ilanel_proj['title'] ),
					'post_name'    => sanitize_title( $ilanel_proj['slug'] ),
					'post_type'    => 'project',
					'post_status'  => 'publish',
					'post_content' => $ilanel_proj_content,
				)
			);
		}

		if ( ! $ilanel_proj_id || is_wp_error( $ilanel_proj_id ) ) {
			WP_CLI::warning( 'Could not create project: ' . $ilanel_proj['title'] );
			continue;
		}

		if ( ! empty( $ilanel_proj['image'] ) && ! get_post_thumbnail_id( $ilanel_proj_id ) ) {
			$ilanel_att_id = media_sideload_image( $ilanel_proj['image'], $ilanel_proj_id, $ilanel_proj['title'], 'id' );

			if ( ! is_wp_error( $ilanel_att_id ) ) {
				set_post_thumbnail( $ilanel_proj_id, $ilanel_att_id );
			}
		}

		if ( ! empty( $ilanel_proj['gallery'] ) ) {
			update_post_meta( $ilanel_proj_id, '_ilanel_project_gallery', array_map( 'esc_url_raw', $ilanel_proj['gallery'] ) );
		}

		foreach ( $ilanel_proj['products'] as $ilanel_product_slug ) {
			$ilanel_product_post = get_page_by_path( $ilanel_product_slug, OBJECT, 'product' );

			if ( $ilanel_product_post && class_exists( 'ILANEL_Projects' ) ) {
				ILANEL_Projects::link( $ilanel_product_post->ID, $ilanel_proj_id );
			}
		}

		$ilanel_project_count++;
		WP_CLI::log( '  · project: ' . $ilanel_proj['title'] );
	}

	WP_CLI::success( sprintf( 'Seeded %d projects and linked them to products.', $ilanel_project_count ) );
} elseif ( ! post_type_exists( 'project' ) ) {
	WP_CLI::warning( 'Project post type not registered — is ILANEL POC Core active? Skipping projects.' );
} else {
	WP_CLI::log( 'No data/projects.json — run scripts/scrape-squarespace.js to generate it. Skipping projects.' );
}

WP_CLI::log( '' );
WP_CLI::log( 'Prices, SKUs and variations come from the authenticated Squarespace' );
WP_CLI::log( 'Commerce export and are real. Photography is ILANEL\'s own, served' );
WP_CLI::log( 'from their CDN.' );
