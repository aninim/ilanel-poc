<?php
/**
 * Range archive — the collection/range page.
 *
 * Demonstrates what a Squarespace portfolio collection cannot do:
 * breadcrumbs, filters, CollectionPage + ItemList schema, and a single h1
 * that names the range.
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

$ilanel_term = get_queried_object();
?>

<div class="ilanel-range">

	<?php
	/**
	 * woocommerce_before_main_content renders the wrapper and breadcrumbs.
	 */
	do_action( 'woocommerce_before_main_content' );
	?>

	<header class="ilanel-range__header">
		<?php // The range name is this page's only h1. ?>
		<h1 class="ilanel-range__title"><?php echo esc_html( $ilanel_term->name ); ?></h1>

		<?php if ( term_description() ) : ?>
			<div class="ilanel-range__intro">
				<?php echo wp_kses_post( term_description() ); ?>
			</div>
		<?php endif; ?>
	</header>

	<?php if ( woocommerce_product_loop() ) : ?>

		<?php
		/**
		 * Renders the filter nav (hooked at priority 15).
		 */
		do_action( 'woocommerce_before_shop_loop' );
		?>

		<?php woocommerce_product_loop_start(); ?>

		<?php
		while ( have_posts() ) :
			the_post();

			/**
			 * Standard Woo loop hook so product cards stay consistent
			 * with any other archive.
			 */
			do_action( 'woocommerce_shop_loop' );

			wc_get_template_part( 'content', 'product' );
		endwhile;
		?>

		<?php woocommerce_product_loop_end(); ?>

		<?php do_action( 'woocommerce_after_shop_loop' ); ?>

	<?php else : ?>

		<p class="ilanel-range__empty">
			<?php esc_html_e( 'No products in this range yet.', 'ilanel-poc' ); ?>
		</p>

	<?php endif; ?>

	<?php do_action( 'woocommerce_after_main_content' ); ?>

</div>

<?php
get_footer( 'shop' );
