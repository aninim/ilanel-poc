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

	/*
	 * product.js carries the hero carousel as well as the configurator, and
	 * the homepage runs the same carousel — so it is needed on both. Its
	 * initialisers each no-op when their markup is absent, so loading it on
	 * the front page costs nothing beyond the request.
	 */
	if ( ( function_exists( 'is_product' ) && is_product() ) || is_front_page() ) {
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
		 * it on products without a model. Guarded on is_product() as well:
		 * on the front page get_the_ID() is the page, not a product.
		 */
		if ( function_exists( 'is_product' ) && is_product()
			&& class_exists( 'ILANEL_Product_Meta' )
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
 * Mark pages that open on a hero so the header can overlay it.
 *
 * The product template, homepage and shop archive all lead with .rg-hero,
 * so all three need the header-overlay treatment (2026-08-15: added
 * is_shop() when the shop archive gained its own Comet hero — previously
 * the header sat in normal flow above it, inconsistent with how the
 * homepage/product-page heroes already render full-bleed to the top).
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function ilanel_poc_body_class( $classes ) {
	if ( ( function_exists( 'is_product' ) && is_product() ) || is_front_page() || ( function_exists( 'is_shop' ) && is_shop() ) ) {
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

	/*
	 * Two paths, decided per product by whether it has a real price.
	 *
	 * Pieces with Commerce data (variants, prices, SKUs) can be bought:
	 * WooCommerce's own add-to-cart runs and the AU checkout applies. Pieces
	 * without one are genuinely price-on-application — made-to-order lighting
	 * quoted per project — and keep the enquiry route, which is how ILANEL
	 * sell them today.
	 *
	 * The removal is therefore conditional rather than blanket; see
	 * ilanel_poc_is_purchasable().
	 */
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
}

/**
 * Can this product be bought, or is it enquiry-only?
 *
 * The catalogue currently splits: four products carry real prices from the
 * authenticated Commerce export; the other nineteen await an API key. Rather
 * than guess, the price itself is the signal — a product with no price cannot
 * meaningfully be added to a cart.
 *
 * @param WC_Product|null $product Product to test. Defaults to global.
 * @return bool
 */
function ilanel_poc_is_purchasable( $product = null ) {
	if ( ! $product instanceof WC_Product ) {
		global $product;
	}

	if ( ! $product instanceof WC_Product ) {
		return false;
	}

	if ( $product->is_type( 'variable' ) ) {
		return '' !== $product->get_variation_price( 'min' );
	}

	return '' !== $product->get_price();
}

/**
 * Strip add-to-cart only for products that have no price.
 *
 * Runs late on wp so the queried product is known.
 */
function ilanel_poc_conditional_cart() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	if ( ! ilanel_poc_is_purchasable() ) {
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
	}
}
add_action( 'wp', 'ilanel_poc_conditional_cart' );

/**
 * Archive tiles: cart button for priced pieces, enquiry link otherwise.
 *
 * @param string     $html    Default button markup.
 * @param WC_Product $product Product.
 * @return string
 */
function ilanel_poc_loop_add_to_cart( $html, $product ) {
	if ( ilanel_poc_is_purchasable( $product ) ) {
		return $html;
	}

	return '';
}
add_filter( 'woocommerce_loop_add_to_cart_link', 'ilanel_poc_loop_add_to_cart', 10, 2 );

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

	/*
	 * Purchasable pieces get WooCommerce's own add-to-cart instead — showing
	 * both a cart button and an "Enquire" CTA would be two competing calls to
	 * action on the same page.
	 */
	if ( ilanel_poc_is_purchasable( $product ) ) {
		return;
	}

	echo '<p class="ilanel-enquire">';
	echo '<a class="single_add_to_cart_button" href="' . esc_url( home_url( '/contact/' ) ) . '">';
	echo esc_html__( 'Enquire for price', 'ilanel-poc' );
	echo '</a></p>';
}
add_action( 'woocommerce_single_product_summary', 'ilanel_poc_render_enquiry', 25 );
add_action( 'init', 'ilanel_poc_strip_woocommerce_defaults' );

/**
 * Shop hero — Comet, ILANEL's flagship real-Commerce-data product.
 *
 * The main /shop/ archive had no hero at all: WooCommerce's default
 * archive-product.php just prints a bare <h1>Shop</h1>. Range archives
 * (Pendants, Chandeliers…) already have their own header pattern
 * (taxonomy-ilanel_range.php's rg-range__title) and are deliberately left
 * alone — this is is_shop() only, the top-level catalogue landing page.
 *
 * Reuses the same rg-hero--project markup/CSS as single-light_art.php's
 * single-image hero (not the homepage's rotating rg-hero--home carousel —
 * this is one fixed image, not a slideshow). Comet is the deliberate,
 * fixed choice here (Oren, 2026-08-15) rather than "whichever product is
 * newest" the way the homepage hero works, since it's ILANEL's most
 * complete real product: 36 real Commerce variants, real photography,
 * not one of the 30 still on a placeholder price.
 */
function ilanel_poc_render_shop_hero() {
	if ( ! is_shop() ) {
		return;
	}

	$comet = get_page_by_path( 'comet-pendant-light', OBJECT, 'product' );

	if ( ! $comet ) {
		return;
	}

	$hero_image = get_the_post_thumbnail_url( $comet->ID, 'full' );

	if ( ! $hero_image ) {
		return;
	}
	?>
	<link rel="preload" as="image" fetchpriority="high" href="<?php echo esc_url( $hero_image ); ?>">

	<section class="rg-hero rg-hero--project rg-hero--shop" aria-label="<?php esc_attr_e( 'Shop the collection', 'ilanel-poc' ); ?>">
		<div class="rg-hero__slide is-active"
			style="background-image:url('<?php echo esc_url( $hero_image ); ?>')"
			role="img"
			aria-label="<?php esc_attr_e( 'Comet pendant light', 'ilanel-poc' ); ?>"></div>

		<div class="rg-hero__overlay">
			<div class="rg-shell">
				<div class="rg-hero__copy">
					<span class="rg-hero__eyebrow"><?php esc_html_e( 'The collection', 'ilanel-poc' ); ?></span>
					<h1 class="rg-hero__title rg-hero__title--project"><?php esc_html_e( 'Sculptural lighting, made to order.', 'ilanel-poc' ); ?></h1>
				</div>
			</div>
		</div>
	</section>
	<?php
}
add_action( 'woocommerce_before_main_content', 'ilanel_poc_render_shop_hero', 5 );

/**
 * Drop WooCommerce's default "Shop" <h1> on the main archive only.
 *
 * The Comet hero above already states the page's purpose (headline +
 * eyebrow) — a second plain-text "Shop" heading directly beneath it read
 * as redundant, and clashed visually against the hero's sans-serif
 * treatment. Range archives (Pendants, Chandeliers…) have no hero of
 * their own, so they keep this heading — only is_shop() removes it.
 */
function ilanel_poc_remove_shop_page_title( $title ) {
	if ( is_shop() ) {
		return '';
	}

	return $title;
}
add_filter( 'woocommerce_page_title', 'ilanel_poc_remove_shop_page_title' );

/**
 * Wrap Woo content in the theme's container.
 *
 * 2026-08-15: added .rg-shell around the shop archive specifically — Oren
 * flagged the filter row and product grid on /shop/ sitting flush against
 * the browser edge with no gutter at all. taxonomy-ilanel_range.php (the
 * range archives — Pendants, Chandeliers…) is a real theme template file
 * that already wraps its own content in .rg-shell by hand; /shop/ renders
 * through WooCommerce's own archive-product.php instead, which this theme
 * never overrides, so it never got the same wrapper. Scoped to is_shop()
 * only — the hero above already sits outside <main> and needs full-bleed
 * width, and this must not double up with taxonomy-ilanel_range.php's own
 * wrapper on range archives.
 */
function ilanel_poc_wrapper_start() {
	echo '<main id="main" class="ilanel-main">';

	if ( function_exists( 'is_shop' ) && is_shop() ) {
		echo '<div class="rg-shell">';
	}
}
add_action( 'woocommerce_before_main_content', 'ilanel_poc_wrapper_start', 10 );

/**
 * Close the theme's container.
 */
function ilanel_poc_wrapper_end() {
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		echo '</div>';
	}

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

/**
 * Real bugs found 2026-08-15 from a screenshot of /shop/page/2/ — the theme
 * has no sidebar.php and never removed WooCommerce's default sidebar hook,
 * so woocommerce_get_sidebar() fell all the way through to WordPress core's
 * raw, completely unstyled fallback widgets (a plain search box, a full
 * page list including "Sample Page" — another leftover default artifact —
 * and a month-by-month post archive back to 2021). None of this was ever
 * meant to render; nothing in this theme's design has a sidebar concept.
 * Same page also showed WooCommerce's default numbered pagination
 * (unstyled prev/1/2 links) — Oren preferred "Load more" once shown the
 * alternative, matching the pattern archive-project.php/home.php already
 * use elsewhere on the site (get_next_posts_link()).
 */
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
remove_action( 'woocommerce_after_shop_loop', 'woocommerce_pagination', 10 );

/**
 * "Load more" in place of the removed default pagination, same visual
 * pattern as .rg-journal__more (home.php, archive-project.php).
 */
function ilanel_poc_render_shop_load_more() {
	if ( ! is_shop() && ! is_tax( ILANEL_Taxonomies::RANGE_TAXONOMY ) ) {
		return;
	}

	$next = get_next_posts_link( esc_html__( 'Load more', 'ilanel-poc' ) );

	if ( $next ) {
		echo '<div class="rg-journal__more">' . wp_kses_post( $next ) . '</div>';
	}
}
add_action( 'woocommerce_after_shop_loop', 'ilanel_poc_render_shop_load_more', 10 );
