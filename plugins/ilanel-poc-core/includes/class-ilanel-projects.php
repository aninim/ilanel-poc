<?php
/**
 * Projects — the installations ILANEL's pieces appear in.
 *
 * This is the WooCommerce argument made concrete. ILANEL sell on provenance
 * (NGV, the Australian War Memorial, Four Seasons), and on a hosted store that
 * relationship lives in a page builder at best. Here products and projects are
 * both posts in one database, so "which projects used this light" and "which
 * lights are in this project" are the same join read from either end.
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the project post type and the product <-> project relation.
 */
class ILANEL_Projects {

	const POST_TYPE = 'project';

	/**
	 * Product-side meta key: an array of related project post IDs.
	 *
	 * Deliberately stored on the product only. The reverse direction is a
	 * query rather than a second stored list, so the two can never disagree —
	 * a mirrored list is the classic source of drift here.
	 */
	const META_PRODUCTS = '_ilanel_related_projects';

	/**
	 * Light Art — exhibitions and commissions, not saleable products.
	 *
	 * ILANEL show at Melbourne Design Week, Goldstone Gallery, JAHM and have
	 * done City of Melbourne commissions. It is a distinct body of work from
	 * the catalogue and from client installations, so it gets its own type
	 * rather than being flattened into either.
	 */
	const LIGHT_ART_POST_TYPE = 'light_art';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'init', array( __CLASS__, 'register_light_art' ) );
	}

	/**
	 * Register the Light Art post type.
	 *
	 * Slug matches the live URLs (ilanel.com/light-art/<slug>) — URL
	 * preservation is a hard lock.
	 */
	public static function register_light_art() {
		$labels = array(
			'name'          => __( 'Light Art', 'ilanel-poc' ),
			'singular_name' => __( 'Light Art', 'ilanel-poc' ),
			'menu_name'     => __( 'Light Art', 'ilanel-poc' ),
			'all_items'     => __( 'All Light Art', 'ilanel-poc' ),
			'add_new_item'  => __( 'Add New Light Art', 'ilanel-poc' ),
			'edit_item'     => __( 'Edit Light Art', 'ilanel-poc' ),
			'not_found'     => __( 'No light art found.', 'ilanel-poc' ),
		);

		register_post_type(
			self::LIGHT_ART_POST_TYPE,
			array(
				'labels'        => $labels,
				'public'        => true,
				'has_archive'   => 'light-art',
				'show_in_rest'  => true,
				'menu_icon'     => 'dashicons-lightbulb',
				'menu_position' => 22,
				'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
				'rewrite'       => array(
					'slug'       => 'light-art',
					'with_front' => false,
				),
			)
		);
	}

	/**
	 * Register the project post type.
	 *
	 * `has_archive` is on so /projects/ lists them; the rewrite slug matches
	 * the live Squarespace URLs (ilanel.com/projects/<slug>), which is a hard
	 * lock — those URLs carry the site's backlinks and must not drift.
	 */
	public static function register_post_type() {
		$labels = array(
			'name'          => __( 'Projects', 'ilanel-poc' ),
			'singular_name' => __( 'Project', 'ilanel-poc' ),
			'menu_name'     => __( 'Projects', 'ilanel-poc' ),
			'all_items'     => __( 'All Projects', 'ilanel-poc' ),
			'add_new_item'  => __( 'Add New Project', 'ilanel-poc' ),
			'edit_item'     => __( 'Edit Project', 'ilanel-poc' ),
			'search_items'  => __( 'Search Projects', 'ilanel-poc' ),
			'not_found'     => __( 'No projects found.', 'ilanel-poc' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'       => $labels,
				'public'       => true,
				'has_archive'  => 'projects',
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-portfolio',
				'menu_position' => 21,
				'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
				'rewrite'      => array(
					'slug'       => 'projects',
					'with_front' => false,
				),
			)
		);
	}

	/**
	 * Projects that feature a given product.
	 *
	 * @param int $product_id Product post ID.
	 * @return WP_Post[]
	 */
	public static function get_projects_for_product( $product_id ) {
		$ids = get_post_meta( $product_id, self::META_PRODUCTS, true );

		if ( empty( $ids ) || ! is_array( $ids ) ) {
			return array();
		}

		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post__in'       => array_map( 'absint', $ids ),
				'orderby'        => 'post__in',
				'posts_per_page' => 12,
				'post_status'    => 'publish',
			)
		);

		return $posts;
	}

	/**
	 * Products used in a given project.
	 *
	 * The reverse of get_projects_for_product(), resolved by querying the
	 * product-side meta rather than reading a mirrored list — one source of
	 * truth, so the two directions cannot fall out of step.
	 *
	 * LIKE on a serialised meta value is not elegant, but the alternative is
	 * a second stored list that can drift. At POC scale (four products) the
	 * cost is nil; at production scale this becomes a proper relations table
	 * or a taxonomy, and the call sites do not change.
	 *
	 * @param int $project_id Project post ID.
	 * @return WC_Product[]
	 */
	public static function get_products_for_project( $project_id ) {
		global $wpdb;

		$needle = '%' . $wpdb->esc_like( ':' . (int) $project_id . ';' ) . '%';

		$product_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta}
				 WHERE meta_key = %s AND meta_value LIKE %s",
				self::META_PRODUCTS,
				$needle
			)
		);

		if ( empty( $product_ids ) ) {
			return array();
		}

		$products = array();

		foreach ( $product_ids as $product_id ) {
			// Re-read the meta and check properly: LIKE can match a substring
			// of a longer id (project 12 inside 123), so the SQL is a coarse
			// filter and this is the real test.
			$related = get_post_meta( $product_id, self::META_PRODUCTS, true );

			if ( ! is_array( $related ) || ! in_array( (int) $project_id, array_map( 'absint', $related ), true ) ) {
				continue;
			}

			$product = wc_get_product( $product_id );

			if ( $product && 'publish' === get_post_status( $product_id ) ) {
				$products[] = $product;
			}
		}

		return $products;
	}

	/**
	 * Link a product to a project, keeping the list unique.
	 *
	 * @param int $product_id Product post ID.
	 * @param int $project_id Project post ID.
	 */
	public static function link( $product_id, $project_id ) {
		$ids = get_post_meta( $product_id, self::META_PRODUCTS, true );

		if ( ! is_array( $ids ) ) {
			$ids = array();
		}

		$ids[] = (int) $project_id;

		update_post_meta( $product_id, self::META_PRODUCTS, array_values( array_unique( array_map( 'absint', $ids ) ) ) );
	}
}
