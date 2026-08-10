<?php
/**
 * Range taxonomy — ILANEL's equivalent of Ross Gardam's "our-range".
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the product Range taxonomy and its rewrite rules.
 */
class ILANEL_Taxonomies {

	const RANGE_TAXONOMY = 'ilanel_range';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_range_taxonomy' ) );
	}

	/**
	 * Register the Range taxonomy.
	 *
	 * Hierarchical so a range can hold sub-ranges, matching RG's
	 * /our-range/{cat}/{sub}/ structure.
	 */
	public static function register_range_taxonomy() {
		$labels = array(
			'name'          => __( 'Ranges', 'ilanel-poc' ),
			'singular_name' => __( 'Range', 'ilanel-poc' ),
			'menu_name'     => __( 'Ranges', 'ilanel-poc' ),
			'all_items'     => __( 'All Ranges', 'ilanel-poc' ),
			'edit_item'     => __( 'Edit Range', 'ilanel-poc' ),
			'add_new_item'  => __( 'Add New Range', 'ilanel-poc' ),
		);

		register_taxonomy(
			self::RANGE_TAXONOMY,
			array( 'product' ),
			array(
				'labels'            => $labels,
				'hierarchical'      => true,
				'public'            => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'query_var'         => true,
				'rewrite'           => array(
					'slug'         => 'our-range',
					'with_front'   => false,
					'hierarchical' => true,
				),
			)
		);
	}

	/**
	 * Return the primary range term for a product.
	 *
	 * Used by breadcrumbs and schema, both of which need exactly one term.
	 * Where a product sits in several ranges we take the first, which is
	 * deterministic because get_the_terms() orders by term_id.
	 *
	 * @param int $product_id Product post ID.
	 * @return WP_Term|null
	 */
	public static function get_primary_range( $product_id ) {
		$terms = get_the_terms( $product_id, self::RANGE_TAXONOMY );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return null;
		}

		return $terms[0];
	}
}
