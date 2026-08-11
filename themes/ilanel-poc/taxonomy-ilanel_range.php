<?php
/**
 * Range archive — Ross Gardam products-grid anatomy.
 *
 * Verified against rossgardam.com.au/lighting-furniture-products/:
 *   FILTERS label over a hairline rule, filter row beneath, then a
 *   three-up grid of flat grey tiles with the name and "FROM AU$ x"
 *   underneath each.
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

$ilanel_term = get_queried_object();
?>

<main id="main" class="rg-main rg-main--archive">
	<div class="rg-shell">

		<div class="rg-breadcrumbs rg-breadcrumbs--inline">
			<?php ILANEL_Breadcrumbs::render(); ?>
		</div>

		<header class="rg-range__header">
			<h1 class="rg-range__title"><?php echo esc_html( $ilanel_term->name ); ?></h1>

			<?php if ( term_description() ) : ?>
				<div class="rg-range__intro"><?php echo wp_kses_post( term_description() ); ?></div>
			<?php endif; ?>
		</header>

		<?php
		/**
		 * Filter row, hooked at priority 15.
		 */
		do_action( 'woocommerce_before_shop_loop' );
		?>

		<?php if ( woocommerce_product_loop() ) : ?>

			<ul class="rg-products">
				<?php
				while ( have_posts() ) :
					the_post();

					global $product;

					$ilanel_type = get_post_meta( $product->get_id(), '_ilanel_product_type_label', true );
					?>
					<li class="rg-product-card">
						<a href="<?php the_permalink(); ?>">
							<div class="rg-product-card__media">
								<?php
								if ( has_post_thumbnail() ) {
									the_post_thumbnail( 'large' );
								}
								?>
							</div>

							<h2 class="rg-product-card__title"><?php the_title(); ?></h2>

							<?php if ( $ilanel_type ) : ?>
								<p class="rg-product-card__type"><?php echo esc_html( $ilanel_type ); ?></p>
							<?php endif; ?>

							<?php if ( $product->get_price() ) : ?>
								<p class="rg-product-card__price">
									<?php
									printf(
										/* translators: %s: formatted price */
										esc_html__( 'From %s', 'ilanel-poc' ),
										wp_kses_post( wc_price( $product->get_price() ) )
									);
									?>
								</p>
							<?php endif; ?>
						</a>
					</li>
					<?php
				endwhile;
				?>
			</ul>

		<?php else : ?>

			<p class="rg-range__empty"><?php esc_html_e( 'No products in this range yet.', 'ilanel-poc' ); ?></p>

		<?php endif; ?>

	</div>
</main>

<?php
get_footer( 'shop' );
