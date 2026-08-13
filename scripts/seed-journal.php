<?php
/**
 * Seed the POC with real ILANEL journal/news posts.
 *
 * Run via WP-CLI from the WordPress root:
 *
 *   wp eval-file scripts/seed-journal.php
 *
 * Idempotent: re-running updates existing posts by slug rather than creating
 * duplicates, so it is safe to run after editing data/journal.json.
 *
 * data/journal.json is a cleaned extraction (scripts/../ — see the Node
 * script that produced it) from data/scraped-news.wxr.xml, which itself
 * carries Squarespace's own block markup verbatim. Importing that markup
 * into post_content would drag in Squarespace-specific classes, lightbox
 * buttons and "View fullsize" caption text with no matching CSS on this
 * site. journal.json instead holds plain paragraphs and an ordered image
 * list per post, matching how projects.json / light-art.json already
 * decouple content from the scrape format.
 *
 * Uses native WordPress `post`s, not a custom post type — see
 * class-ilanel-journal.php for why, and for the /news/ permalink rewrite
 * this content depends on.
 *
 * @package ILANEL_POC
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	echo "This script must be run through WP-CLI: wp eval-file scripts/seed-journal.php\n";
	exit( 1 );
}

require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$ilanel_journal_file = __DIR__ . '/../data/journal.json';

if ( ! file_exists( $ilanel_journal_file ) ) {
	WP_CLI::error( 'Seed data not found at ' . $ilanel_journal_file . ' — run the extraction script against data/scraped-news.wxr.xml first.' );
}

$ilanel_posts = json_decode( file_get_contents( $ilanel_journal_file ), true );

if ( ! is_array( $ilanel_posts ) ) {
	WP_CLI::error( 'Seed data is malformed — expected a JSON array.' );
}

$ilanel_count = 0;

foreach ( $ilanel_posts as $ilanel_item ) {
	if ( empty( $ilanel_item['slug'] ) || empty( $ilanel_item['title'] ) ) {
		continue;
	}

	$ilanel_existing = get_page_by_path( $ilanel_item['slug'], OBJECT, 'post' );

	$ilanel_content = '';

	foreach ( (array) $ilanel_item['paragraphs'] as $ilanel_para ) {
		$ilanel_content .= '<p>' . wp_kses_post( $ilanel_para ) . "</p>\n\n";
	}

	$ilanel_post_args = array(
		'post_title'   => sanitize_text_field( $ilanel_item['title'] ),
		'post_name'    => sanitize_title( $ilanel_item['slug'] ),
		'post_type'    => 'post',
		'post_status'  => 'publish',
		'post_content' => $ilanel_content,
	);

	if ( ! empty( $ilanel_item['date'] ) ) {
		$ilanel_timestamp = strtotime( $ilanel_item['date'] );

		if ( $ilanel_timestamp ) {
			$ilanel_post_args['post_date']     = gmdate( 'Y-m-d H:i:s', $ilanel_timestamp );
			$ilanel_post_args['post_date_gmt'] = gmdate( 'Y-m-d H:i:s', $ilanel_timestamp );
		}
	}

	if ( $ilanel_existing ) {
		$ilanel_post_args['ID'] = $ilanel_existing->ID;
		$ilanel_post_id         = wp_update_post( $ilanel_post_args, true );
	} else {
		$ilanel_post_id = wp_insert_post( $ilanel_post_args, true );
	}

	if ( ! $ilanel_post_id || is_wp_error( $ilanel_post_id ) ) {
		WP_CLI::warning( 'Could not create journal post: ' . $ilanel_item['title'] );
		continue;
	}

	if ( ! empty( $ilanel_item['image'] ) && ! get_post_thumbnail_id( $ilanel_post_id ) ) {
		$ilanel_att_id = media_sideload_image( $ilanel_item['image'], $ilanel_post_id, $ilanel_item['title'], 'id' );

		if ( ! is_wp_error( $ilanel_att_id ) ) {
			set_post_thumbnail( $ilanel_post_id, $ilanel_att_id );
		}
	}

	if ( ! empty( $ilanel_item['gallery'] ) ) {
		update_post_meta( $ilanel_post_id, '_ilanel_project_gallery', array_map( 'esc_url_raw', $ilanel_item['gallery'] ) );
	}

	if ( ! empty( $ilanel_item['source_url'] ) ) {
		update_post_meta( $ilanel_post_id, '_ilanel_source_url', esc_url_raw( $ilanel_item['source_url'] ) );
	}

	$ilanel_count++;
	WP_CLI::log( '  · journal: ' . $ilanel_item['title'] );
}

WP_CLI::success( sprintf( 'Seeded %d journal posts.', $ilanel_count ) );
