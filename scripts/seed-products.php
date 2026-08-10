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
 * Prices and SKUs in the seed data are PLACEHOLDER values — see
 * docs/OPEN-QUESTIONS.md. Real figures must come from Commerce.
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

	if ( $ilanel_existing ) {
		$ilanel_product = wc_get_product( $ilanel_existing->ID );
		$ilanel_updated++;
	} else {
		$ilanel_product = new WC_Product_Simple();
		$ilanel_created++;
	}

	$ilanel_product->set_name( $ilanel_item['name'] );
	$ilanel_product->set_slug( $ilanel_item['slug'] );
	$ilanel_product->set_status( 'publish' );
	$ilanel_product->set_catalog_visibility( 'visible' );
	$ilanel_product->set_short_description( $ilanel_item['meta_description'] );

	// Made to order: in stock, but on backorder — matches the 4–12 week lead time.
	$ilanel_product->set_manage_stock( false );
	$ilanel_product->set_stock_status( 'onbackorder' );

	if ( ! empty( $ilanel_item['sku'] ) && 0 !== strpos( $ilanel_item['sku'], 'PLACEHOLDER' ) ) {
		$ilanel_product->set_sku( $ilanel_item['sku'] );
	}

	// Only set a price when the seed data carries a real one. An Offer node
	// without a price is invalid schema, so we leave it absent instead.
	if ( ! empty( $ilanel_item['price'] ) && 'PLACEHOLDER' !== $ilanel_item['price'] ) {
		$ilanel_product->set_regular_price( (string) $ilanel_item['price'] );
	}

	$ilanel_product_id = $ilanel_product->save();

	if ( ! $ilanel_product_id ) {
		WP_CLI::warning( 'Failed to save product: ' . $ilanel_item['name'] );
		continue;
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

WP_CLI::log( '' );
WP_CLI::log( 'NOTE: prices and SKUs are placeholders. Confirm against Commerce' );
WP_CLI::log( 'before treating any figure here as real. See docs/OPEN-QUESTIONS.md.' );
