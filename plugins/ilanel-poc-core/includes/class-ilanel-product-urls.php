<?php
/**
 * Product URL structure — matches live ilanel.com's split catalogue.
 *
 * ilanel.com's Squarespace store is not one flat catalogue: regular
 * products live under /lighting-design-collections/<slug> and Editions
 * live under a wholly separate /editions/<slug> — confirmed 2026-08-13 by
 * reading their live nav and product links directly, not assumed. The
 * WooCommerce default (a single "product" base for every product) would
 * put an Editions piece at the wrong URL if this ever becomes a real
 * migration, breaking exactly the backlinks the "never change a live URL"
 * rule exists to protect.
 *
 * WooCommerce's own settings (woocommerce_permalinks → product_base)
 * change the base for every product uniformly; there is no built-in way to
 * split it by taxonomy term. This filters Editions products onto their own
 * base after that option is set to lighting-design-collections for
 * everything else.
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;

/**
 * Routes Editions products onto /editions/<slug>, everything else stays on
 * WooCommerce's own product_base (set to lighting-design-collections).
 */
class ILANEL_Product_URLs {

	const EDITIONS_BASE = 'editions';
	const EDITIONS_TERM = 'editions';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'add_rewrite_rules' ) );
		add_filter( 'post_type_link', array( __CLASS__, 'filter_product_link' ), 10, 2 );
	}

	/**
	 * Route /editions/<slug>/ to the product query.
	 *
	 * 'top' priority so this is tried before WooCommerce's own
	 * lighting-design-collections/<slug> rule, which would otherwise never
	 * match an /editions/ URL anyway (different first segment) but costs
	 * nothing to be explicit about.
	 */
	public static function add_rewrite_rules() {
		add_rewrite_rule(
			'^' . self::EDITIONS_BASE . '/([^/]+)/?$',
			'index.php?product=$matches[1]',
			'top'
		);
	}

	/**
	 * Point an Editions product's permalink at /editions/<slug>/ instead of
	 * the shared product_base.
	 *
	 * @param string  $post_link Existing permalink.
	 * @param WP_Post $post      Post object.
	 * @return string
	 */
	public static function filter_product_link( $post_link, $post ) {
		if ( 'product' !== $post->post_type ) {
			return $post_link;
		}

		if ( ! self::is_editions_product( $post->ID ) ) {
			return $post_link;
		}

		return home_url( '/' . self::EDITIONS_BASE . '/' . $post->post_name . '/' );
	}

	/**
	 * Whether a product carries the Editions range term.
	 *
	 * @param int $product_id Product post ID.
	 * @return bool
	 */
	protected static function is_editions_product( $product_id ) {
		return has_term( self::EDITIONS_TERM, ILANEL_Taxonomies::RANGE_TAXONOMY, $product_id );
	}
}
