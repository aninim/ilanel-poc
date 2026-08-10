<?php
/**
 * Theme footer.
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;
?>

<footer class="ilanel-sitefooter">
	<div class="ilanel-sitefooter__inner">
		<p>
			<?php
			printf(
				/* translators: %s: studio name */
				esc_html__( '%s — handmade in Melbourne, Australia.', 'ilanel-poc' ),
				esc_html( get_bloginfo( 'name' ) )
			);
			?>
		</p>
		<p class="ilanel-sitefooter__note">
			<?php esc_html_e( 'Proof of concept. Placeholder styling — not the final design.', 'ilanel-poc' ); ?>
		</p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
