<?php
/**
 * Product and CollectionPage JSON-LD.
 *
 * This is the capability the POC exists to demonstrate. ILANEL's Squarespace
 * product pages are portfolio-item records in a portfolio-grid-overlay
 * collection, and Squarespace emits Product schema only for native store
 * pages — so Product/Offer schema is impossible there by page type, not by
 * configuration. Here it falls out of the platform for free.
 *
 * Field set mirrors what Ross Gardam emits: name, url, description, image,
 * sku, offers{price, priceCurrency, availability, priceValidUntil}.
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;

/**
 * Emits JSON-LD for product and range pages.
 */
class ILANEL_Schema {

	/**
	 * Hook registration.
	 *
	 * We remove Woo's own structured data so there is exactly one Product
	 * node on the page — two competing nodes is a common cause of Search
	 * Console "duplicate field" warnings.
	 */
	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'output_schema' ), 20 );
		add_action( 'init', array( __CLASS__, 'remove_woocommerce_schema' ) );
	}

	/**
	 * Disable WooCommerce's built-in structured data.
	 */
	public static function remove_woocommerce_schema() {
		if ( ! class_exists( 'WC_Structured_Data' ) ) {
			return;
		}

		$structured_data = WC()->structured_data;

		if ( $structured_data ) {
			remove_action( 'woocommerce_before_main_content', array( $structured_data, 'generate_website_data' ), 30 );
			remove_action( 'woocommerce_single_product_summary', array( $structured_data, 'generate_product_data' ), 60 );
		}
	}

	/**
	 * Route the current request to the right schema generator.
	 */
	public static function output_schema() {
		if ( is_product() ) {
			self::output_product_schema();
			return;
		}

		if ( is_tax( ILANEL_Taxonomies::RANGE_TAXONOMY ) ) {
			self::output_range_schema();
		}
	}

	/**
	 * Product + Offer JSON-LD for a single product page.
	 */
	protected static function output_product_schema() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			$product = wc_get_product( get_the_ID() );
		}

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$schema = array(
			'@context'    => 'https://schema.org/',
			'@type'       => 'Product',
			'name'        => $product->get_name(),
			'url'         => get_permalink( $product->get_id() ),
			'description' => self::get_clean_description( $product ),
			'sku'         => $product->get_sku(),
		);

		$image_id = $product->get_image_id();
		if ( $image_id ) {
			$image_src = wp_get_attachment_image_src( $image_id, 'full' );
			if ( $image_src ) {
				$schema['image'] = $image_src[0];
			}
		}

		$brand_name = get_bloginfo( 'name' );
		if ( $brand_name ) {
			$schema['brand'] = array(
				'@type' => 'Brand',
				'name'  => $brand_name,
			);
		}

		$offers = self::build_offers( $product );
		if ( $offers ) {
			$schema['offers'] = $offers;
		}

		self::print_json_ld( $schema );
	}

	/**
	 * Build the Offer node.
	 *
	 * ILANEL pieces are made to order, so availability is normally
	 * BackOrder rather than InStock — an honest signal that matches the
	 * stated 4–12 week lead time. Returns null when no price is set, since
	 * an Offer without a price is invalid and triggers GSC errors.
	 *
	 * @param WC_Product $product Product object.
	 * @return array|null
	 */
	protected static function build_offers( $product ) {
		$price = $product->get_price();

		if ( '' === $price || null === $price ) {
			return null;
		}

		$availability = $product->is_in_stock()
			? 'https://schema.org/BackOrder'
			: 'https://schema.org/OutOfStock';

		return array(
			'@type'           => 'Offer',
			'url'             => get_permalink( $product->get_id() ),
			'price'           => wc_format_decimal( $price, wc_get_price_decimals() ),
			'priceCurrency'   => get_woocommerce_currency(),
			'availability'    => $availability,
			'priceValidUntil' => gmdate( 'Y-m-d', strtotime( '+1 year' ) ),
			'seller'          => array(
				'@type' => 'Organization',
				'name'  => get_bloginfo( 'name' ),
			),
		);
	}

	/**
	 * CollectionPage + ItemList JSON-LD for a range archive.
	 */
	protected static function output_range_schema() {
		$term = get_queried_object();

		if ( ! $term instanceof WP_Term ) {
			return;
		}

		$items = array();
		$position = 1;

		if ( have_posts() ) {
			global $wp_query;

			foreach ( $wp_query->posts as $post_object ) {
				$items[] = array(
					'@type'    => 'ListItem',
					'position' => $position,
					'url'      => get_permalink( $post_object->ID ),
					'name'     => get_the_title( $post_object->ID ),
				);
				$position++;
			}
		}

		$schema = array(
			'@context'    => 'https://schema.org/',
			'@type'       => 'CollectionPage',
			'name'        => $term->name,
			'url'         => get_term_link( $term ),
			'description' => wp_strip_all_tags( term_description( $term ) ),
		);

		if ( $items ) {
			$schema['mainEntity'] = array(
				'@type'           => 'ItemList',
				'numberOfItems'   => count( $items ),
				'itemListElement' => $items,
			);
		}

		self::print_json_ld( $schema );
	}

	/**
	 * Description for schema: short description first, falling back to an
	 * excerpt of the long description. Tags are stripped because schema
	 * values are plain text.
	 *
	 * @param WC_Product $product Product object.
	 * @return string
	 */
	protected static function get_clean_description( $product ) {
		$description = $product->get_short_description();

		if ( ! $description ) {
			$description = $product->get_description();
		}

		$description = wp_strip_all_tags( $description );

		return trim( preg_replace( '/\s+/', ' ', $description ) );
	}

	/**
	 * Print a JSON-LD script tag.
	 *
	 * Unescaped slashes and unicode keep URLs and typographic characters
	 * readable in source, which matters when debugging in GSC.
	 *
	 * @param array $schema Schema array.
	 */
	protected static function print_json_ld( $schema ) {
		$schema = array_filter(
			$schema,
			static function ( $value ) {
				return '' !== $value && null !== $value && array() !== $value;
			}
		);

		echo "\n<!-- ILANEL POC structured data -->\n";
		echo '<script type="application/ld+json">' . "\n";
		echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
		echo "\n" . '</script>' . "\n";
	}
}
