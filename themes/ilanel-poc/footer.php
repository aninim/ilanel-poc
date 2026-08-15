<?php
/**
 * Theme footer — Ross Gardam layout.
 *
 * Structure verified against rossgardam.com.au:
 *   address block · four nav columns (About / Support / Resources / Social)
 *   · Acknowledgement of Country · legal row
 *
 * Address, phone and email come from the real scraped Contact page
 * (data/static-pages.json) — confirmed live 2026-08-15 after the phone
 * number was found still showing a placeholder (+61 3 9000 0000) despite
 * this comment's prior claim that real details were already in use.
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;

$ilanel_footer_nav = array(
	'About'     => array( 'News', 'Studio', 'Projects', 'Light Art', 'Contact' ),
	'Support'   => array( 'Trade programme', 'FAQ', 'Shipping & returns', 'Warranty', 'Care & maintenance' ),
	'Resources' => array( 'Catalogue', 'Lookbook', 'Materials & finishes', '3D models', 'Product instructions' ),
	'Social'    => array( 'Subscribe', 'Instagram', 'Pinterest', 'LinkedIn' ),
);

// Maps footer nav labels (above) and the legal row to real page slugs
// built by scripts/seed-static-pages.php (Phase 3a of docs/LAUNCH-PLAN.md).
// Items not in this map keep the '#' placeholder — either not built yet
// (Catalogue, Lookbook, 3D models…) or intentionally external (social).
$ilanel_footer_page_slugs = array(
	'Studio'             => 'about',
	'Contact'            => 'contact',
	'Trade programme'    => 'trade',
	'FAQ'                => 'faq',
	'Warranty'           => 'warranty',
	'Care & maintenance' => 'faq', // Live on ilanel.com as a FAQ entry, not a separate page.
);

// Archives with their own post type (not a plain `page`), so home_url()
// alone is enough — no lookup needed the way the map above resolves a
// page's slug. Found missing 2026-08-14: 'News' had this treatment
// already (see below), but 'Projects' and 'Light Art' were left on '#'
// even though both archives have existed and worked since last session.
$ilanel_footer_archive_slugs = array(
	'Projects'  => 'projects',
	'Light Art' => 'light-art',
);
?>

<footer class="rg-footer">
	<div class="rg-shell">
		<div class="rg-footer__inner">

			<div class="rg-footer__content">

				<?php // Identity column — sits beside the nav, as RG do. ?>
				<div class="rg-footer__identity">
					<p class="rg-footer__logo"><?php bloginfo( 'name' ); ?></p>

					<address class="rg-footer__address">
						<span>Melbourne</span>
						<span>Victoria, Australia</span>
						<span class="rg-footer__spacer">Phone — +61 3 9534 1164</span>
						<a href="mailto:info@ilanel.com">INFO@ILANEL.COM</a>
					</address>
				</div>

				<div class="rg-footer__navbar">
					<?php foreach ( $ilanel_footer_nav as $ilanel_heading => $ilanel_items ) : ?>
						<div class="rg-footer__nav">
							<h2 class="rg-footer__navtitle"><?php echo esc_html( $ilanel_heading ); ?></h2>
							<ul>
								<?php
								foreach ( $ilanel_items as $ilanel_item ) :
									$ilanel_href = '#';

									if ( 'News' === $ilanel_item ) {
										$ilanel_href = home_url( '/news/' );
									} elseif ( isset( $ilanel_footer_archive_slugs[ $ilanel_item ] ) ) {
										$ilanel_href = home_url( '/' . $ilanel_footer_archive_slugs[ $ilanel_item ] . '/' );
									} elseif ( isset( $ilanel_footer_page_slugs[ $ilanel_item ] ) ) {
										$ilanel_href = home_url( '/' . $ilanel_footer_page_slugs[ $ilanel_item ] . '/' );
									}
									?>
									<li><a href="<?php echo esc_url( $ilanel_href ); ?>"><?php echo esc_html( $ilanel_item ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endforeach; ?>
				</div>

			</div>

			<div class="rg-footer__inline">
				<p class="rg-footer__acknowledgement">
					<?php esc_html_e( 'We are proud to acknowledge the Wurundjeri Woi-wurrung people as the traditional custodians of this land. We pay our respects to their Elders, past, present and emerging.', 'ilanel-poc' ); ?>
				</p>

				<ul class="rg-footer__legal">
					<li>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></li>
					<li><a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>"><?php esc_html_e( 'Privacy Policy', 'ilanel-poc' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/terms-and-conditions/' ) ); ?>"><?php esc_html_e( 'Terms &amp; Conditions', 'ilanel-poc' ); ?></a></li>
				</ul>
			</div>

		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
