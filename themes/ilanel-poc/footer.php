<?php
/**
 * Theme footer — Ross Gardam layout.
 *
 * Structure verified against rossgardam.com.au:
 *   address block · four nav columns (About / Support / Resources / Social)
 *   · Acknowledgement of Country · legal row
 *
 * ILANEL's real studio details are used where known; the rest are
 * plausible placeholders for the demo.
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;

$ilanel_footer_nav = array(
	'About'     => array( 'Journal', 'Studio', 'Projects', 'Light Art', 'Contact' ),
	'Support'   => array( 'Trade programme', 'FAQ', 'Shipping & returns', 'Warranty', 'Care & maintenance' ),
	'Resources' => array( 'Catalogue', 'Lookbook', 'Materials & finishes', '3D models', 'Product instructions' ),
	'Social'    => array( 'Subscribe', 'Instagram', 'Pinterest', 'LinkedIn' ),
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
						<span class="rg-footer__spacer">Phone — +61 3 9000 0000</span>
						<a href="mailto:studio@ilanel.com">STUDIO@ILANEL.COM</a>
					</address>
				</div>

				<div class="rg-footer__navbar">
					<?php foreach ( $ilanel_footer_nav as $ilanel_heading => $ilanel_items ) : ?>
						<div class="rg-footer__nav">
							<h2 class="rg-footer__navtitle"><?php echo esc_html( $ilanel_heading ); ?></h2>
							<ul>
								<?php foreach ( $ilanel_items as $ilanel_item ) : ?>
									<li><a href="#"><?php echo esc_html( $ilanel_item ); ?></a></li>
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
					<li><a href="#"><?php esc_html_e( 'Privacy Policy', 'ilanel-poc' ); ?></a></li>
					<li><a href="#"><?php esc_html_e( 'Terms &amp; Conditions', 'ilanel-poc' ); ?></a></li>
				</ul>
			</div>

		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
