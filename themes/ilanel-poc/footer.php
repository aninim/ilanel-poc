<?php
/**
 * Theme footer — Ross Gardam layout.
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;
?>

<footer class="rg-footer">
	<div class="rg-shell">
		<div class="rg-footer__inner">
			<p><?php esc_html_e( 'Handmade in Melbourne, Australia', 'ilanel-poc' ); ?></p>
			<p><?php esc_html_e( 'Trade enquiries welcome', 'ilanel-poc' ); ?></p>
			<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></p>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
