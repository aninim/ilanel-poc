<?php
/**
 * Single product — Ross Gardam page anatomy.
 *
 * RG's structure, verified against rossgardam.com.au/product/liminal-pendant-light/:
 *
 *   1. Full-bleed hero image (they run a slider; we show the hero still)
 *   2. Breadcrumbs — PRODUCTS / Lighting / {range} / {type}
 *   3. Editorial article: serif name + uppercase type, then body copy
 *      in a two-column reversed layout with a feature image
 *   4. DOWNLOADS / section — labelled list of spec assets
 *
 * Deliberately no cart. RG sell made-to-order through enquiry, and so
 * does ILANEL.
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

global $product;

if ( ! $product instanceof WC_Product ) {
	$product = wc_get_product( get_the_ID() );
}

$ilanel_id       = $product->get_id();
$ilanel_image_id = $product->get_image_id();
$ilanel_hero     = $ilanel_image_id ? wp_get_attachment_image_url( $ilanel_image_id, 'full' ) : '';
$ilanel_spec_pdf = ILANEL_Product_Meta::get( $ilanel_id, ILANEL_Product_Meta::FIELD_SPEC_PDF );
$ilanel_finishes = ILANEL_Product_Meta::get_finishes( $ilanel_id );
$ilanel_lead     = ILANEL_Product_Meta::get( $ilanel_id, ILANEL_Product_Meta::FIELD_LEAD_TIME, '4–12 weeks' );
$ilanel_made_in  = ILANEL_Product_Meta::get( $ilanel_id, ILANEL_Product_Meta::FIELD_MADE_IN, 'Melbourne, Australia' );

while ( have_posts() ) :
	the_post();
	?>

	<?php if ( $ilanel_hero ) : ?>
		<div class="rg-hero" role="img"
			aria-label="<?php echo esc_attr( $product->get_name() ); ?>"
			style="background-image:url('<?php echo esc_url( $ilanel_hero ); ?>')"></div>
	<?php endif; ?>

	<div class="rg-breadcrumbs">
		<div class="rg-shell">
			<?php ILANEL_Breadcrumbs::render(); ?>
		</div>
	</div>

	<main id="main" class="rg-main">
		<article <?php wc_product_class( 'rg-product', $product ); ?>>

			<section class="rg-article rg-shell">
				<div class="rg-article__row">
					<div class="rg-article__col">
						<?php wc_get_template( 'single-product/title.php' ); ?>
					</div>

					<div class="rg-article__col rg-article__col--body">
						<div class="rg-article__content">
							<?php echo wp_kses_post( wpautop( $product->get_short_description() ) ); ?>
						</div>

						<p class="rg-meta">
							<span class="rg-meta__label"><?php esc_html_e( 'Lead time', 'ilanel-poc' ); ?></span>
							<?php echo esc_html( $ilanel_lead ); ?>
						</p>
						<p class="rg-meta">
							<span class="rg-meta__label"><?php esc_html_e( 'Made in', 'ilanel-poc' ); ?></span>
							<?php echo esc_html( $ilanel_made_in ); ?>
						</p>

						<?php if ( $product->get_sku() ) : ?>
							<p class="rg-meta">
								<span class="rg-meta__label"><?php esc_html_e( 'SKU', 'ilanel-poc' ); ?></span>
								<?php echo esc_html( $product->get_sku() ); ?>
							</p>
						<?php endif; ?>

						<p class="rg-cta">
							<a class="rg-link" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">
								<?php esc_html_e( 'Enquire', 'ilanel-poc' ); ?> /
							</a>
						</p>
					</div>
				</div>
			</section>

			<?php if ( $ilanel_finishes ) : ?>
				<section class="rg-section rg-section--finishes">
					<div class="rg-shell">
						<span class="rg-section__label"><?php esc_html_e( 'Finishes', 'ilanel-poc' ); ?> /</span>

						<div class="rg-grid">
							<div class="rg-grid__col">
								<h2 class="rg-section__title">
									<?php esc_html_e( 'Each piece is hand finished to order in a choice of metals.', 'ilanel-poc' ); ?>
								</h2>
							</div>

							<div class="rg-grid__col">
								<ul class="rg-section__list">
									<?php foreach ( $ilanel_finishes as $ilanel_finish ) : ?>
										<li><?php echo esc_html( $ilanel_finish ); ?></li>
									<?php endforeach; ?>
								</ul>
							</div>
						</div>
					</div>
				</section>
			<?php endif; ?>

			<section class="rg-section rg-section--details">
				<div class="rg-shell">
					<span class="rg-section__label"><?php esc_html_e( 'Downloads', 'ilanel-poc' ); ?> /</span>

					<div class="rg-grid">
						<div class="rg-grid__col">
							<h2 class="rg-section__title">
								<?php esc_html_e( 'All product details are located in one place to easily access & download.', 'ilanel-poc' ); ?>
							</h2>
						</div>

						<div class="rg-grid__col">
							<ul class="rg-section__list rg-section__list--links">
								<?php if ( $ilanel_spec_pdf ) : ?>
									<li>
										<a href="<?php echo esc_url( $ilanel_spec_pdf ); ?>" target="_blank" rel="noopener">
											<?php esc_html_e( 'Data sheet', 'ilanel-poc' ); ?>
										</a>
									</li>
								<?php endif; ?>
								<li><a href="#"><?php esc_html_e( 'Materials &amp; finishes', 'ilanel-poc' ); ?></a></li>
								<li><a href="#"><?php esc_html_e( '3D models', 'ilanel-poc' ); ?></a></li>
								<li><a href="#"><?php esc_html_e( 'Care &amp; maintenance', 'ilanel-poc' ); ?></a></li>
							</ul>
						</div>
					</div>
				</div>
			</section>

		</article>
	</main>

	<?php
endwhile;

get_footer( 'shop' );
