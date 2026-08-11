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
 *
 * Loaded after WooCommerce's own stylesheets so our rules win without
 * needing !important everywhere. Fonts are enqueued rather than @imported —
 * @import is unreliable inside the Playground sandbox.
 */
function ilanel_poc_enqueue_assets() {
	wp_enqueue_style(
		'ilanel-poc-fonts',
		'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Playfair+Display:wght@400;500&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'ilanel-poc',
		get_stylesheet_directory_uri() . '/assets/css/main.css',
		array( 'ilanel-poc-fonts', 'woocommerce-general' ),
		ILANEL_POC_THEME_VERSION
	);

	// Site-wide: soft page-to-page transitions.
	wp_enqueue_script(
		'ilanel-poc-transitions',
		get_stylesheet_directory_uri() . '/assets/js/transitions.js',
		array(),
		ILANEL_POC_THEME_VERSION,
		true
	);

	if ( function_exists( 'is_product' ) && is_product() ) {
		wp_enqueue_script(
			'ilanel-poc-product',
			get_stylesheet_directory_uri() . '/assets/js/product.js',
			array(),
			ILANEL_POC_THEME_VERSION,
			true
		);

		/*
		 * <model-viewer> is only fetched when the product actually has a
		 * .glb — it is a ~300KB module and there is no reason to pay for
		 * it on products without a model.
		 */
		if ( class_exists( 'ILANEL_Product_Meta' )
			&& ILANEL_Product_Meta::get( get_the_ID(), ILANEL_Product_Meta::FIELD_MODEL_GLB ) ) {
			wp_enqueue_script(
				'model-viewer',
				'https://ajax.googleapis.com/ajax/libs/model-viewer/3.5.0/model-viewer.min.js',
				array(),
				'3.5.0',
				true
			);
			wp_script_add_data( 'model-viewer', 'type', 'module' );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'ilanel_poc_enqueue_assets', 20 );

/**
 * Mark product pages so the header can overlay the hero.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function ilanel_poc_body_class( $classes ) {
	if ( function_exists( 'is_product' ) && is_product() ) {
		$classes[] = 'has-hero';
	}

	return $classes;
}
add_filter( 'body_class', 'ilanel_poc_body_class' );

/**
 * Drop WooCommerce's layout and smallscreen sheets.
 *
 * They fight the RG grid (and supply the default purple button). We keep
 * woocommerce-general as a dependency so our sheet loads after it, then
 * override what we need.
 */
function ilanel_poc_dequeue_woocommerce_layout( $enqueue_styles ) {
	unset( $enqueue_styles['woocommerce-layout'] );
	unset( $enqueue_styles['woocommerce-smallscreen'] );

	return $enqueue_styles;
}
add_filter( 'woocommerce_enqueue_styles', 'ilanel_poc_dequeue_woocommerce_layout' );

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

	// No cart. RG sell made-to-order pieces through enquiry, and so does
	// ILANEL ("ENQUIRE" on the live product page). An Add to Cart button
	// would misrepresent how the studio actually sells.
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
	remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
}

/**
 * Short description, then an Enquire call to action.
 *
 * Replaces the cart flow removed above.
 */
function ilanel_poc_render_enquiry() {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$short = $product->get_short_description();

	if ( $short ) {
		echo '<div class="woocommerce-product-details__short-description">';
		echo wp_kses_post( wpautop( $short ) );
		echo '</div>';
	}

	echo '<p class="ilanel-enquire">';
	echo '<a class="single_add_to_cart_button" href="' . esc_url( home_url( '/contact/' ) ) . '">';
	echo esc_html__( 'Enquire', 'ilanel-poc' );
	echo '</a></p>';
}
add_action( 'woocommerce_single_product_summary', 'ilanel_poc_render_enquiry', 25 );
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
/*
 * NOT hooked. single-product.php renders specs inline as part of the RG
 * article layout, so the standalone block would duplicate it. Kept because
 * it is still the reference for what a Woo-hook version looks like.
 */

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

	// RG's filter block: a small accent label sitting above a hairline
	// rule, with the filter terms spaced widely beneath it.
	echo '<nav class="rg-filters" aria-label="' . esc_attr__( 'Filter by range', 'ilanel-poc' ) . '">';
	echo '<span class="rg-filters__label">' . esc_html__( 'Filters', 'ilanel-poc' ) . '</span>';
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
		echo '</a></li>';
	}

	echo '</ul>';
	echo '</nav>';
}
add_action( 'woocommerce_before_shop_loop', 'ilanel_poc_render_range_filters', 15 );
