<?php
/**
 * Theme header — Ross Gardam layout.
 *
 * RG's header is a three-column bar: hamburger left, centred serif
 * wordmark, search + account right. No h1 here — the h1 belongs to the
 * page content.
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<?php
	/*
	 * Opt into page transitions before first paint.
	 *
	 * Inline and synchronous on purpose: if this ran later the page would
	 * paint opaque and then jump to the faded state. Because the class is
	 * only ever added by script, a browser without JavaScript never hides
	 * the body — no blank page, ever.
	 */
	?>
	<script>document.documentElement.classList.add('is-transitions');</script>

	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="rg-header">
	<div class="rg-header__inner">
		<button class="rg-navbtn js-navbtn" type="button"
			aria-label="<?php esc_attr_e( 'Menu', 'ilanel-poc' ); ?>"
			aria-expanded="false"
			aria-controls="rg-menu">
			<span></span><span></span><span></span>
		</button>

		<p class="rg-header__logo">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
		</p>

		<nav class="rg-utilities" aria-label="<?php esc_attr_e( 'Utilities', 'ilanel-poc' ); ?>">
			<a href="#" aria-label="<?php esc_attr_e( 'Search', 'ilanel-poc' ); ?>">
				<svg width="17" height="17" viewBox="0 0 17 17" fill="none" aria-hidden="true">
					<circle cx="7" cy="7" r="5.6" stroke="currentColor" stroke-width="1.1"/>
					<line x1="11.2" y1="11.2" x2="16" y2="16" stroke="currentColor" stroke-width="1.1"/>
				</svg>
			</a>
			<a href="#" aria-label="<?php esc_attr_e( 'Account', 'ilanel-poc' ); ?>">
				<svg width="17" height="17" viewBox="0 0 17 17" fill="none" aria-hidden="true">
					<circle cx="8.5" cy="5.4" r="3.1" stroke="currentColor" stroke-width="1.1"/>
					<path d="M2.4 15.4c0-3.4 2.7-5.4 6.1-5.4s6.1 2 6.1 5.4" stroke="currentColor" stroke-width="1.1"/>
				</svg>
			</a>
		</nav>
	</div>
</header>

<?php
/*
 * Full-screen menu overlay, following RG's own pattern (their .menu links are
 * 4rem, dropping to 3rem at ≤1023px — measured from their stylesheet, see
 * docs/reference/RG-HOMEPAGE-SPEC-2026-08-12.md).
 *
 * Built from real routes rather than a WP nav menu location: the POC seeds
 * its own content, so a registered menu would be empty until someone
 * populated it by hand in admin.
 */
/*
 * Ranges are queried, not hardcoded.
 *
 * The previous version listed Pendants and Wall Lights by name, so when the
 * full catalogue added Chandeliers and Lamps they were unreachable from the
 * menu — the products existed and their archives resolved, but nothing linked
 * to them. Querying the taxonomy means a new range appears automatically.
 */
$ilanel_menu_ranges = get_terms(
	array(
		'taxonomy'   => 'ilanel_range',
		'hide_empty' => true,
		'orderby'    => 'count',
		'order'      => 'DESC',
	)
);

if ( is_wp_error( $ilanel_menu_ranges ) ) {
	$ilanel_menu_ranges = array();
}

$ilanel_menu_projects  = post_type_exists( 'project' ) ? get_post_type_archive_link( 'project' ) : '';
$ilanel_menu_light_art = post_type_exists( 'light_art' ) ? get_post_type_archive_link( 'light_art' ) : '';
$ilanel_menu_journal   = home_url( '/news/' );
$ilanel_shop_id        = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'shop' ) : 0;
$ilanel_menu_shop     = $ilanel_shop_id > 0 ? get_permalink( $ilanel_shop_id ) : '';
?>
<div class="rg-menu" id="rg-menu" hidden>
	<div class="rg-menu__inner rg-shell">
		<nav class="rg-menu__nav" aria-label="<?php esc_attr_e( 'Main', 'ilanel-poc' ); ?>">
			<ul class="rg-menu__list">
				<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'ilanel-poc' ); ?></a></li>

				<?php if ( $ilanel_menu_shop ) : ?>
					<li><a href="<?php echo esc_url( $ilanel_menu_shop ); ?>"><?php esc_html_e( 'Products', 'ilanel-poc' ); ?></a></li>
				<?php endif; ?>

				<?php // Ranges sit beneath Products as a secondary tier. ?>
				<?php if ( $ilanel_menu_ranges ) : ?>
					<li class="rg-menu__group">
						<ul class="rg-menu__sublist">
							<?php foreach ( $ilanel_menu_ranges as $ilanel_range_term ) : ?>
								<li>
									<a href="<?php echo esc_url( get_term_link( $ilanel_range_term ) ); ?>">
										<?php echo esc_html( $ilanel_range_term->name ); ?>
										<span class="rg-menu__count"><?php echo absint( $ilanel_range_term->count ); ?></span>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</li>
				<?php endif; ?>

				<?php if ( $ilanel_menu_projects ) : ?>
					<li><a href="<?php echo esc_url( $ilanel_menu_projects ); ?>"><?php esc_html_e( 'Projects', 'ilanel-poc' ); ?></a></li>
				<?php endif; ?>

				<?php if ( $ilanel_menu_light_art ) : ?>
					<li><a href="<?php echo esc_url( $ilanel_menu_light_art ); ?>"><?php esc_html_e( 'Light Art', 'ilanel-poc' ); ?></a></li>
				<?php endif; ?>

				<li><a href="<?php echo esc_url( $ilanel_menu_journal ); ?>"><?php esc_html_e( 'News', 'ilanel-poc' ); ?></a></li>
			</ul>
		</nav>

		<div class="rg-menu__aside">
			<p class="rg-menu__label"><?php esc_html_e( 'Studio', 'ilanel-poc' ); ?></p>
			<p class="rg-menu__detail"><?php esc_html_e( 'Melbourne, Australia', 'ilanel-poc' ); ?></p>
			<p class="rg-menu__detail"><?php esc_html_e( 'Made to order — 4–12 weeks', 'ilanel-poc' ); ?></p>
			<p class="rg-menu__detail"><?php esc_html_e( 'Trade: 20% off RRP + 3D files', 'ilanel-poc' ); ?></p>
		</div>
	</div>
</div>
