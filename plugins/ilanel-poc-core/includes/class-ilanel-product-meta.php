<?php
/**
 * Product meta fields — spec sheet, finishes, lead time, related projects.
 *
 * These are the fields that make a product page single-source: change a
 * finish here and every surface that renders it updates. On Squarespace the
 * same content is duplicated per page as free text, which is why the live
 * site has drifted (e.g. "ColoUr Disclaimer" repeated on 6 pages).
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers and renders custom product meta.
 */
class ILANEL_Product_Meta {

	const FIELD_SPEC_PDF   = '_ilanel_spec_pdf';
	const FIELD_FINISHES   = '_ilanel_finishes';
	const FIELD_LEAD_TIME  = '_ilanel_lead_time';
	const FIELD_MADE_IN    = '_ilanel_made_in';
	const FIELD_TYPE_LABEL = '_ilanel_product_type_label';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'render_fields' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_fields' ) );
	}

	/**
	 * Render the admin fields on the product edit screen.
	 */
	public static function render_fields() {
		echo '<div class="options_group">';

		woocommerce_wp_text_input(
			array(
				'id'          => self::FIELD_SPEC_PDF,
				'label'       => __( 'Spec sheet URL', 'ilanel-poc' ),
				'description' => __( 'Link to the product specification PDF.', 'ilanel-poc' ),
				'desc_tip'    => true,
				'type'        => 'url',
			)
		);

		woocommerce_wp_textarea_input(
			array(
				'id'          => self::FIELD_FINISHES,
				'label'       => __( 'Finishes', 'ilanel-poc' ),
				'description' => __( 'One finish per line. Rendered as the finish list on the product page.', 'ilanel-poc' ),
				'desc_tip'    => true,
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'          => self::FIELD_LEAD_TIME,
				'label'       => __( 'Lead time', 'ilanel-poc' ),
				'placeholder' => __( '4–12 weeks', 'ilanel-poc' ),
				'description' => __( 'Shown on the product page and in the enquiry form.', 'ilanel-poc' ),
				'desc_tip'    => true,
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'          => self::FIELD_MADE_IN,
				'label'       => __( 'Made in', 'ilanel-poc' ),
				'placeholder' => __( 'Melbourne, Australia', 'ilanel-poc' ),
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'          => self::FIELD_TYPE_LABEL,
				'label'       => __( 'Type label', 'ilanel-poc' ),
				'placeholder' => __( 'Linear Pendant', 'ilanel-poc' ),
				'description' => __( 'Qualifier shown beside the product name, e.g. the "Linear Pendant" in "Comet / Linear Pendant".', 'ilanel-poc' ),
				'desc_tip'    => true,
			)
		);

		echo '</div>';
	}

	/**
	 * Persist the fields.
	 *
	 * @param int $post_id Product post ID.
	 */
	public static function save_fields( $post_id ) {
		// WooCommerce verifies the nonce before firing this hook.
		$url_fields = array( self::FIELD_SPEC_PDF );
		foreach ( $url_fields as $field ) {
			$value = isset( $_POST[ $field ] ) ? esc_url_raw( wp_unslash( $_POST[ $field ] ) ) : '';
			update_post_meta( $post_id, $field, $value );
		}

		$text_fields = array( self::FIELD_LEAD_TIME, self::FIELD_MADE_IN, self::FIELD_TYPE_LABEL );
		foreach ( $text_fields as $field ) {
			$value = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';
			update_post_meta( $post_id, $field, $value );
		}

		$finishes = isset( $_POST[ self::FIELD_FINISHES ] )
			? sanitize_textarea_field( wp_unslash( $_POST[ self::FIELD_FINISHES ] ) )
			: '';
		update_post_meta( $post_id, self::FIELD_FINISHES, $finishes );
	}

	/**
	 * Get the finishes as an array.
	 *
	 * @param int $product_id Product post ID.
	 * @return string[]
	 */
	public static function get_finishes( $product_id ) {
		$raw = get_post_meta( $product_id, self::FIELD_FINISHES, true );

		if ( ! $raw ) {
			return array();
		}

		$lines = preg_split( '/\r\n|\r|\n/', $raw );

		return array_values( array_filter( array_map( 'trim', $lines ) ) );
	}

	/**
	 * Get a single meta value with a fallback default.
	 *
	 * @param int    $product_id Product post ID.
	 * @param string $field      Meta key.
	 * @param string $default    Fallback when unset.
	 * @return string
	 */
	public static function get( $product_id, $field, $default = '' ) {
		$value = get_post_meta( $product_id, $field, true );

		return $value ? $value : $default;
	}
}
