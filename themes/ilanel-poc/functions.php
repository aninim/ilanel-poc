<?php
/**
 * ILANEL POC theme functions.
 *
 * Follows Ross Gardam's structural approach: strip WooCommerce's default
 * product furniture (stock summary, tabs, related products, sale flashes)
 * and render a deliberate, minimal page instead.
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;

define( 'ILANEL_POC_THEME_VERSION', '0.1.0' );

/**
 * Theme setup.
 */
function ilanel_poc_theme_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );

	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'ilanel-poc' ),
		)
	);
}
add_action( 'after_setup_theme', 'ilanel_poc_theme_setup' );

/**
 * Enqueue theme styles.
 */
function ilanel_poc_enqueue_assets() {
	wp_enqueue_style(
		'ilanel-poc',
		get_stylesheet_directory_uri() . '/assets/css/main.css',
		array(),
		ILANEL_POC_THEME_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'ilanel_poc_enqueue_assets' );

/*
 * ---------------------------------------------------------------------------
 * Strip WooCommerce defaults.
 *
 * RG override nearly every default Woo template — no stock summary, no tabs,
 * no related-products markup. We do the same so the page contains only what
 * the studio intends. Each removal is deliberate, not cargo-culted.
 * ---------------------------------------------------------------------------
 */

/**
 * Remove default Woo product furniture.
 */
function ilanel_poc_strip_woocommerce_defaults() {
	// Sale flashes: ILANEL pieces are made to order and never discounted.
	remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
	remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );

	// Tabs and related products: replaced by our own template parts.
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

	// Meta (SKU / category listing) is rendered in our own spec block instead.
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );

	// Archive result count and default ordering — the range page uses filters.
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
}
add_action( 'init', 'ilanel_poc_strip_woocommerce_defaults' );

/**
 * Wrap Woo content in the theme's container.
 */
function ilanel_poc_wrapper_start() {
	echo '<main id="main" class="ilanel-main">';
}
add_action( 'woocommerce_before_main_content', 'ilanel_poc_wrapper_start', 10 );

/**
 * Close the theme's container.
 */
function ilanel_poc_wrapper_end() {
	echo '</main>';
}
add_action( 'woocommerce_after_main_content', 'ilanel_poc_wrapper_end', 10 );

/**
 * Remove Woo's default breadcrumb in favour of ours (which carries schema).
 */
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );

/**
 * Render our breadcrumbs.
 */
function ilanel_poc_render_breadcrumbs() {
	if ( class_exists( 'ILANEL_Breadcrumbs' ) ) {
		ILANEL_Breadcrumbs::render();
	}
}
add_action( 'woocommerce_before_main_content', 'ilanel_poc_render_breadcrumbs', 20 );

/**
 * Products per page on range archives.
 *
 * @return int
 */
function ilanel_poc_products_per_page() {
	return 24;
}
add_filter( 'loop_shop_per_page', 'ilanel_poc_products_per_page', 20 );

/**
 * Render the product spec block: finishes, lead time, origin, spec sheet.
 *
 * All values come from product meta, so they are single-source — the point
 * the POC is making against per-page free text on Squarespace.
 */
function ilanel_poc_render_spec_block() {
	global $product;

	if ( ! $product instanceof WC_Product || ! class_exists( 'ILANEL_Product_Meta' ) ) {
		return;
	}

	$product_id = $product->get_id();
	$finishes   = ILANEL_Product_Meta::get_finishes( $product_id );
	$lead_time  = ILANEL_Product_Meta::get( $product_id, ILANEL_Product_Meta::FIELD_LEAD_TIME, '4–12 weeks' );
	$made_in    = ILANEL_Product_Meta::get( $product_id, ILANEL_Product_Meta::FIELD_MADE_IN, 'Melbourne, Australia' );
	$spec_pdf   = ILANEL_Product_Meta::get( $product_id, ILANEL_Product_Meta::FIELD_SPEC_PDF );
	$sku        = $product->get_sku();

	echo '<section class="ilanel-specs">';
	// H2, never H1 — the product name is the page's only H1.
	echo '<h2>' . esc_html__( 'Specification', 'ilanel-poc' ) . '</h2>';
	echo '<dl class="ilanel-specs__list">';

	if ( $sku ) {
		echo '<dt>' . esc_html__( 'SKU', 'ilanel-poc' ) . '</dt>';
		echo '<dd>' . esc_html( $sku ) . '</dd>';
	}

	if ( $finishes ) {
		echo '<dt>' . esc_html__( 'Finishes', 'ilanel-poc' ) . '</dt>';
		echo '<dd><ul class="ilanel-finishes">';
		foreach ( $finishes as $finish ) {
			echo '<li>' . esc_html( $finish ) . '</li>';
		}
		echo '</ul></dd>';
	}

	echo '<dt>' . esc_html__( 'Lead time', 'ilanel-poc' ) . '</dt>';
	echo '<dd>' . esc_html( $lead_time ) . '</dd>';

	echo '<dt>' . esc_html__( 'Made in', 'ilanel-poc' ) . '</dt>';
	echo '<dd>' . esc_html( $made_in ) . '</dd>';

	echo '</dl>';

	if ( $spec_pdf ) {
		echo '<p class="ilanel-specs__download">';
		echo '<a href="' . esc_url( $spec_pdf ) . '" target="_blank" rel="noopener">';
		echo esc_html__( 'Download specification sheet (PDF)', 'ilanel-poc' );
		echo '</a></p>';
	}

	echo '</section>';
}
add_action( 'woocommerce_after_single_product_summary', 'ilanel_poc_render_spec_block', 15 );

/**
 * Range filter UI on range archives.
 *
 * Squarespace portfolio pages cannot filter at all; this is a plain
 * progressive-enhancement form that works without JavaScript.
 */
function ilanel_poc_render_range_filters() {
	if ( ! is_tax( ILANEL_Taxonomies::RANGE_TAXONOMY ) && ! is_shop() ) {
		return;
	}

	$terms = get_terms(
		array(
			'taxonomy'   => ILANEL_Taxonomies::RANGE_TAXONOMY,
			'hide_empty' => true,
		)
	);

	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return;
	}

	$current = is_tax( ILANEL_Taxonomies::RANGE_TAXONOMY ) ? get_queried_object_id() : 0;

	echo '<nav class="ilanel-filters" aria-label="' . esc_attr__( 'Filter by range', 'ilanel-poc' ) . '">';
	echo '<span class="ilanel-filters__label">' . esc_html__( 'Range', 'ilanel-poc' ) . '</span>';
	echo '<ul>';

	foreach ( $terms as $term ) {
		$is_current = ( $term->term_id === $current );
		$classes    = $is_current ? 'is-current' : '';

		echo '<li class="' . esc_attr( $classes ) . '">';
		echo '<a href="' . esc_url( get_term_link( $term ) ) . '"';
		if ( $is_current ) {
			echo ' aria-current="page"';
		}
		echo '>' . esc_html( $term->name );
		echo ' <span class="ilanel-filters__count">(' . absint( $term->count ) . ')</span>';
		echo '</a></li>';
	}

	echo '</ul>';
	echo '</nav>';
}
add_action( 'woocommerce_before_shop_loop', 'ilanel_poc_render_range_filters', 15 );
