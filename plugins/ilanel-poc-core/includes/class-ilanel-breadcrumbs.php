<?php
/**
 * Breadcrumbs with BreadcrumbList JSON-LD.
 *
 * Another capability the Squarespace portfolio page type can't express.
 * Renders visible breadcrumbs and the matching structured data from one
 * source, so they cannot drift apart.
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds and renders breadcrumb trails.
 */
class ILANEL_Breadcrumbs {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'output_schema' ), 21 );
	}

	/**
	 * Build the trail for the current request.
	 *
	 * @return array[] List of array{name: string, url: string}.
	 */
	public static function get_trail() {
		$trail = array(
			array(
				'name' => __( 'Home', 'ilanel-poc' ),
				'url'  => home_url( '/' ),
			),
		);

		if ( is_product() ) {
			$product_id = get_the_ID();
			$range      = ILANEL_Taxonomies::get_primary_range( $product_id );

			if ( $range ) {
				$ancestors = array_reverse( get_ancestors( $range->term_id, ILANEL_Taxonomies::RANGE_TAXONOMY ) );

				foreach ( $ancestors as $ancestor_id ) {
					$ancestor = get_term( $ancestor_id, ILANEL_Taxonomies::RANGE_TAXONOMY );
					if ( $ancestor && ! is_wp_error( $ancestor ) ) {
						$trail[] = array(
							'name' => $ancestor->name,
							'url'  => get_term_link( $ancestor ),
						);
					}
				}

				$trail[] = array(
					'name' => $range->name,
					'url'  => get_term_link( $range ),
				);
			}

			$trail[] = array(
				'name' => get_the_title( $product_id ),
				'url'  => get_permalink( $product_id ),
			);

			return $trail;
		}

		if ( is_tax( ILANEL_Taxonomies::RANGE_TAXONOMY ) ) {
			$term = get_queried_object();

			if ( $term instanceof WP_Term ) {
				$ancestors = array_reverse( get_ancestors( $term->term_id, ILANEL_Taxonomies::RANGE_TAXONOMY ) );

				foreach ( $ancestors as $ancestor_id ) {
					$ancestor = get_term( $ancestor_id, ILANEL_Taxonomies::RANGE_TAXONOMY );
					if ( $ancestor && ! is_wp_error( $ancestor ) ) {
						$trail[] = array(
							'name' => $ancestor->name,
							'url'  => get_term_link( $ancestor ),
						);
					}
				}

				$trail[] = array(
					'name' => $term->name,
					'url'  => get_term_link( $term ),
				);
			}

			return $trail;
		}

		if ( is_singular( 'project' ) || is_singular( 'light_art' ) ) {
			$post_type    = get_post_type();
			$archive_link = get_post_type_archive_link( $post_type );

			if ( $archive_link ) {
				$trail[] = array(
					'name' => 'project' === $post_type
						? __( 'Projects', 'ilanel-poc' )
						: __( 'Light Art', 'ilanel-poc' ),
					'url'  => $archive_link,
				);
			}

			$trail[] = array(
				'name' => get_the_title(),
				'url'  => get_permalink(),
			);
		}

		return $trail;
	}

	/**
	 * Render the visible breadcrumb trail.
	 *
	 * The last crumb is the current page, so it renders as plain text
	 * rather than a self-link.
	 */
	public static function render() {
		$trail = self::get_trail();

		if ( count( $trail ) < 2 ) {
			return;
		}

		$last_index = count( $trail ) - 1;

		echo '<nav class="ilanel-breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'ilanel-poc' ) . '">';
		echo '<ol>';

		foreach ( $trail as $index => $crumb ) {
			echo '<li>';

			if ( $index === $last_index ) {
				echo '<span aria-current="page">' . esc_html( $crumb['name'] ) . '</span>';
			} else {
				echo '<a href="' . esc_url( $crumb['url'] ) . '">' . esc_html( $crumb['name'] ) . '</a>';
			}

			echo '</li>';
		}

		echo '</ol>';
		echo '</nav>';
	}

	/**
	 * Emit BreadcrumbList JSON-LD.
	 */
	public static function output_schema() {
		$applies = is_product()
			|| is_tax( ILANEL_Taxonomies::RANGE_TAXONOMY )
			|| is_singular( 'project' )
			|| is_singular( 'light_art' );

		if ( ! $applies ) {
			return;
		}

		$trail = self::get_trail();

		if ( count( $trail ) < 2 ) {
			return;
		}

		$items = array();

		foreach ( $trail as $index => $crumb ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $index + 1,
				'name'     => $crumb['name'],
				'item'     => $crumb['url'],
			);
		}

		$schema = array(
			'@context'        => 'https://schema.org/',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $items,
		);

		echo "\n" . '<script type="application/ld+json">' . "\n";
		echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
		echo "\n" . '</script>' . "\n";
	}
}
