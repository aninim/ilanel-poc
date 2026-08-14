<?php
/**
 * Configure Australian tax and shipping for the real ILANEL store.
 *
 * Run via WP-CLI from the WordPress root:
 *
 *   wp eval-file scripts/configure-commerce.php
 *
 * Idempotent: re-running refreshes the GST rate and creates the Australian
 * shipping zone only when a zone with that name does not already exist.
 *
 * Payment gateways are deliberately outside this script. The real gateway is
 * a separate launch decision; the offline Playground gateway is demo-only.
 *
 * @package ILANEL_POC
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	echo "This script must be run through WP-CLI: wp eval-file scripts/configure-commerce.php\n";
	exit( 1 );
}

if ( ! class_exists( 'WooCommerce' ) ) {
	WP_CLI::error( 'WooCommerce is not active.' );
}

/*
 * Australian commerce settings. Prices include 10% GST, which is calculated
 * from the shipping address and displayed as included throughout the store.
 */
update_option( 'woocommerce_currency', 'AUD' );
update_option( 'woocommerce_default_country', 'AU:VIC' );
update_option( 'woocommerce_calc_taxes', 'yes' );
update_option( 'woocommerce_prices_include_tax', 'yes' );
update_option( 'woocommerce_tax_based_on', 'shipping' );
update_option( 'woocommerce_tax_display_shop', 'incl' );
update_option( 'woocommerce_tax_display_cart', 'incl' );
update_option( 'woocommerce_price_display_suffix', 'incl. GST' );

// Sell and ship to Australia only for now.
update_option( 'woocommerce_allowed_countries', 'specific' );
update_option( 'woocommerce_specific_allowed_countries', array( 'AU' ) );
update_option( 'woocommerce_ship_to_countries', 'specific' );
update_option( 'woocommerce_specific_ship_to_countries', array( 'AU' ) );

// Replace the named rate so re-running cannot create duplicate GST rates.
$GLOBALS['wpdb']->query( "DELETE FROM {$GLOBALS['wpdb']->prefix}woocommerce_tax_rates WHERE tax_rate_name = 'GST'" );

WC_Tax::_insert_tax_rate(
	array(
		'tax_rate_country'  => 'AU',
		'tax_rate_state'    => '',
		'tax_rate'          => '10.0000',
		'tax_rate_name'     => 'GST',
		'tax_rate_priority' => 1,
		'tax_rate_compound' => 0,
		'tax_rate_shipping' => 1,
		'tax_rate_order'    => 0,
		'tax_rate_class'    => '',
	)
);

/*
 * Add domestic freight and Melbourne studio pickup. The freight cost remains
 * zero until ILANEL confirms its real shipping schedule; the customer-facing
 * title makes that unresolved launch task explicit.
 */
$ilanel_zones       = WC_Shipping_Zones::get_zones();
$ilanel_has_au_zone = false;

foreach ( $ilanel_zones as $ilanel_zone_data ) {
	if ( 'Australia' === $ilanel_zone_data['zone_name'] ) {
		$ilanel_has_au_zone = true;
	}
}

if ( ! $ilanel_has_au_zone ) {
	$ilanel_zone = new WC_Shipping_Zone();
	$ilanel_zone->set_zone_name( 'Australia' );
	$ilanel_zone->add_location( 'AU', 'country' );
	$ilanel_zone->save();

	$ilanel_instance_id = $ilanel_zone->add_shipping_method( 'flat_rate' );

	if ( $ilanel_instance_id ) {
		update_option(
			'woocommerce_flat_rate_' . $ilanel_instance_id . '_settings',
			array(
				'title'      => 'Crated freight (Australia) — TBC, confirm with studio',
				'tax_status' => 'taxable',
				'cost'       => '0.00',
			)
		);
	}

	$ilanel_pickup_id = $ilanel_zone->add_shipping_method( 'local_pickup' );

	if ( $ilanel_pickup_id ) {
		update_option(
			'woocommerce_local_pickup_' . $ilanel_pickup_id . '_settings',
			array(
				'title'      => 'Collect from the Melbourne studio',
				'tax_status' => 'taxable',
				'cost'       => '0',
			)
		);
	}
}

WP_CLI::success( 'Configured Australian currency, GST, sales restrictions and shipping.' );
