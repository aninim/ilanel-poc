<?php
/**
 * Theme header.
 *
 * Deliberately minimal — the studio supplies the real navigation and
 * identity. Note there is no h1 here: the h1 belongs to the page content.
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

<header class="ilanel-siteheader">
	<div class="ilanel-siteheader__inner">
		<p class="ilanel-siteheader__brand">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php bloginfo( 'name' ); ?>
			</a>
		</p>

		<?php
		if ( has_nav_menu( 'primary' ) ) {
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => 'nav',
					'menu_class'     => 'ilanel-nav',
				)
			);
		}
		?>
	</div>
</header>
