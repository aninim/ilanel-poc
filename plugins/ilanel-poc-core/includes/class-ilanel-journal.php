<?php
/**
 * Journal — native WordPress posts, served under /news/.
 *
 * Squarespace's own portfolio-item pages (projects, Light Art) got their own
 * post types because they carry structured fields (gallery, credits, product
 * relations) a plain post can't. News/Journal entries don't — they're
 * ordinary editorial posts — so this reuses WordPress's built-in `post` type
 * rather than adding a third custom one, and only changes where its
 * permalinks live.
 *
 * The base is /news/, matching ilanel.com's real URL structure
 * (ilanel.com/news/<slug>), even though the theme's own nav calls the
 * section "Journal" — cheap insurance if this ever becomes a real
 * migration, where 1,110 of ~1,130 live backlinks point at the homepage and
 * URL drift elsewhere is still worth avoiding on principle.
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;

/**
 * Rewrites native post permalinks onto /news/.
 */
class ILANEL_Journal {

	const REWRITE_BASE = 'news';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'add_rewrite_rules' ) );
		add_filter( 'post_type_link', array( __CLASS__, 'filter_post_link' ), 10, 2 );
	}

	/**
	 * Route /news/<slug>/ to the native post query.
	 *
	 * WordPress has no built-in way to move the `post` type's base the way
	 * register_post_type()'s `rewrite` argument does for custom types, so
	 * this adds the rule directly.
	 */
	public static function add_rewrite_rules() {
		add_rewrite_rule(
			'^' . self::REWRITE_BASE . '/([^/]+)/?$',
			'index.php?post_type=post&name=$matches[1]',
			'top'
		);

		add_rewrite_rule(
			'^' . self::REWRITE_BASE . '/?$',
			'index.php?post_type=post',
			'top'
		);
	}

	/**
	 * Prefix native post permalinks with /news/.
	 *
	 * @param string  $post_link Existing permalink.
	 * @param WP_Post $post      Post object.
	 * @return string
	 */
	public static function filter_post_link( $post_link, $post ) {
		if ( 'post' !== $post->post_type ) {
			return $post_link;
		}

		return home_url( '/' . self::REWRITE_BASE . '/' . $post->post_name . '/' );
	}
}
