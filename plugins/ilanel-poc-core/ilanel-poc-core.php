<?php
/**
 * Plugin Name: ILANEL POC Core
 * Description: Taxonomy, schema and product-meta layer for the ILANEL WooCommerce POC.
 * Version:     0.1.0
 * Author:      ILANEL POC
 * Text Domain: ilanel-poc
 *
 * Structural patterns follow Ross Gardam's WooCommerce architecture
 * (see docs/ARCHITECTURE.md). This is engineering only — visual design,
 * photography and final copy are supplied by the studio.
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;

define( 'ILANEL_POC_VERSION', '0.1.0' );
define( 'ILANEL_POC_PATH', plugin_dir_path( __FILE__ ) );

require_once ILANEL_POC_PATH . 'includes/class-ilanel-taxonomies.php';
require_once ILANEL_POC_PATH . 'includes/class-ilanel-product-meta.php';
require_once ILANEL_POC_PATH . 'includes/class-ilanel-schema.php';
require_once ILANEL_POC_PATH . 'includes/class-ilanel-breadcrumbs.php';
require_once ILANEL_POC_PATH . 'includes/class-ilanel-projects.php';
require_once ILANEL_POC_PATH . 'includes/class-ilanel-journal.php';
require_once ILANEL_POC_PATH . 'includes/class-ilanel-product-urls.php';

/**
 * Boot the plugin once all plugins are loaded.
 *
 * WooCommerce is a hard dependency: every feature here either extends a Woo
 * object or renders inside a Woo template, so we fail loudly rather than
 * half-initialising.
 */
function ilanel_poc_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'ilanel_poc_missing_woocommerce_notice' );
		return;
	}

	ILANEL_Taxonomies::init();
	ILANEL_Product_Meta::init();
	ILANEL_Schema::init();
	ILANEL_Breadcrumbs::init();
	ILANEL_Projects::init();
	ILANEL_Journal::init();
	ILANEL_Product_URLs::init();
}
add_action( 'plugins_loaded', 'ilanel_poc_init' );

/**
 * Admin notice shown when WooCommerce is not active.
 */
function ilanel_poc_missing_woocommerce_notice() {
	echo '<div class="notice notice-error"><p><strong>ILANEL POC Core</strong> requires WooCommerce to be installed and active.</p></div>';
}

/**
 * Register taxonomies on activation, then flush rewrites once.
 *
 * Flushing is expensive, so it happens here rather than on every load.
 */
function ilanel_poc_activate() {
	require_once ILANEL_POC_PATH . 'includes/class-ilanel-taxonomies.php';
	require_once ILANEL_POC_PATH . 'includes/class-ilanel-projects.php';
	require_once ILANEL_POC_PATH . 'includes/class-ilanel-journal.php';
	require_once ILANEL_POC_PATH . 'includes/class-ilanel-product-urls.php';
	ILANEL_Taxonomies::register_range_taxonomy();
	// Without this the /projects/<slug> rewrite does not exist until the next
	// permalink save, and every project page 404s.
	ILANEL_Projects::register_post_type();
	ILANEL_Projects::register_light_art();
	ILANEL_Journal::add_rewrite_rules();
	ILANEL_Product_URLs::add_rewrite_rules();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'ilanel_poc_activate' );

/**
 * Clean up rewrite rules on deactivation.
 */
function ilanel_poc_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'ilanel_poc_deactivate' );
