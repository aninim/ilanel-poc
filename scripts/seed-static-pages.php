<?php
/**
 * Seed the site with real ILANEL static content pages — About, FAQ, Trade,
 * Warranty, Privacy Policy, Terms & Conditions, Contact. Phase 3a of
 * docs/LAUNCH-PLAN.md: these existed on ilanel.com but only as dead
 * `href="#"` footer links here.
 *
 * Run via WP-CLI from the WordPress root:
 *
 *   wp eval-file scripts/seed-static-pages.php
 *
 * Idempotent: re-running updates existing pages by slug rather than creating
 * duplicates, matching seed-journal.php's pattern.
 *
 * data/static-pages.json comes from scripts/scrape-static-pages.js, a
 * read-only fetch of the live ilanel.com pages.
 *
 * @package ILANEL_POC
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	echo "This script must be run through WP-CLI: wp eval-file scripts/seed-static-pages.php\n";
	exit( 1 );
}

$ilanel_pages_file = __DIR__ . '/../data/static-pages.json';

if ( ! file_exists( $ilanel_pages_file ) ) {
	WP_CLI::error( 'Seed data not found at ' . $ilanel_pages_file . ' — run scripts/scrape-static-pages.js first.' );
}

$ilanel_pages = json_decode( file_get_contents( $ilanel_pages_file ), true );

if ( ! is_array( $ilanel_pages ) ) {
	WP_CLI::error( 'Seed data is malformed — expected a JSON array.' );
}

$ilanel_count = 0;

foreach ( $ilanel_pages as $ilanel_item ) {
	if ( empty( $ilanel_item['slug'] ) || empty( $ilanel_item['title'] ) ) {
		continue;
	}

	$ilanel_existing = get_page_by_path( $ilanel_item['slug'], OBJECT, 'page' );

	$ilanel_post_args = array(
		'post_title'   => sanitize_text_field( $ilanel_item['title'] ),
		'post_name'    => sanitize_title( $ilanel_item['slug'] ),
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_content' => wp_kses_post( $ilanel_item['content_html'] ),
	);

	if ( $ilanel_existing ) {
		$ilanel_post_args['ID'] = $ilanel_existing->ID;
		$ilanel_post_id         = wp_update_post( $ilanel_post_args, true );
	} else {
		$ilanel_post_id = wp_insert_post( $ilanel_post_args, true );
	}

	if ( ! $ilanel_post_id || is_wp_error( $ilanel_post_id ) ) {
		WP_CLI::warning( 'Could not create static page: ' . $ilanel_item['title'] );
		continue;
	}

	if ( ! empty( $ilanel_item['source_url'] ) ) {
		update_post_meta( $ilanel_post_id, '_ilanel_source_url', esc_url_raw( $ilanel_item['source_url'] ) );
	}

	$ilanel_count++;
	WP_CLI::log( '  · page: ' . $ilanel_item['title'] . ' (/' . $ilanel_item['slug'] . '/)' );
}

WP_CLI::success( sprintf( 'Seeded %d static pages.', $ilanel_count ) );
