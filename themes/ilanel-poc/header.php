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
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="rg-header">
	<div class="rg-header__inner">
		<button class="rg-navbtn" type="button" aria-label="<?php esc_attr_e( 'Menu', 'ilanel-poc' ); ?>">
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
