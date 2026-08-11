<?php
/**
 * Single product — Ross Gardam page anatomy.
 *
 * Structure verified against rossgardam.com.au/product/liminal-pendant-light/
 * by reading their served HTML and stylesheet:
 *
 *   1. Hero carousel, full-bleed behind a transparent overlaid header
 *      (RG: .slider--hero.slider--fade)
 *   2. Breadcrumbs
 *   3. Lead article: serif name + uppercase type qualifier
 *   4. Storytelling: two alternating rows — portrait image, then
 *      .article--reversed with a landscape image
 *   5. Configurator: "Configure your" + accordions of radio swatches,
 *      live price, BUY NOW / SEND ENQUIRY (RG: .section-configure)
 *   6. DOWNLOADS / section
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

global $product;

if ( ! $product instanceof WC_Product ) {
	$product = wc_get_product( get_the_ID() );
}

$ilanel_id      = $product->get_id();
$ilanel_gallery = ILANEL_Product_Meta::get_gallery( $ilanel_id );
$ilanel_story   = ILANEL_Product_Meta::get_story_images( $ilanel_id );
$ilanel_swatches = ILANEL_Product_Meta::get_swatches( $ilanel_id );
$ilanel_lengths = ILANEL_Product_Meta::get_lengths( $ilanel_id );
$ilanel_spec    = ILANEL_Product_Meta::get( $ilanel_id, ILANEL_Product_Meta::FIELD_SPEC_PDF );
$ilanel_lead    = ILANEL_Product_Meta::get( $ilanel_id, ILANEL_Product_Meta::FIELD_LEAD_TIME, '4–12 weeks' );
$ilanel_type    = ILANEL_Product_Meta::get( $ilanel_id, ILANEL_Product_Meta::FIELD_TYPE_LABEL );

// Fall back to the featured image when no gallery is set.
if ( ! $ilanel_gallery ) {
	$ilanel_thumb = get_the_post_thumbnail_url( $ilanel_id, 'full' );
	if ( $ilanel_thumb ) {
		$ilanel_gallery = array( $ilanel_thumb );
	}
}

while ( have_posts() ) :
	the_post();
	?>

	<?php // 1. Hero carousel — sits beneath the overlaid header. ?>
	<section class="rg-hero js-hero" aria-label="<?php esc_attr_e( 'Product gallery', 'ilanel-poc' ); ?>">
		<?php foreach ( $ilanel_gallery as $ilanel_i => $ilanel_src ) : ?>
			<div class="rg-hero__slide<?php echo 0 === $ilanel_i ? ' is-active' : ''; ?>"
				style="background-image:url('<?php echo esc_url( $ilanel_src ); ?>')"
				role="img"
				aria-label="<?php echo esc_attr( $product->get_name() ); ?>"></div>
		<?php endforeach; ?>

		<?php if ( count( $ilanel_gallery ) > 1 ) : ?>
			<button class="rg-hero__nav rg-hero__nav--prev js-hero-prev" type="button"
				aria-label="<?php esc_attr_e( 'Previous image', 'ilanel-poc' ); ?>">&#8249;</button>
			<button class="rg-hero__nav rg-hero__nav--next js-hero-next" type="button"
				aria-label="<?php esc_attr_e( 'Next image', 'ilanel-poc' ); ?>">&#8250;</button>

			<div class="rg-hero__dots js-hero-dots">
				<?php foreach ( $ilanel_gallery as $ilanel_i => $ilanel_src ) : ?>
					<button type="button" class="rg-hero__dot<?php echo 0 === $ilanel_i ? ' is-active' : ''; ?>"
						data-index="<?php echo absint( $ilanel_i ); ?>"
						aria-label="<?php
						/* translators: %d: slide number */
						printf( esc_attr__( 'Go to image %d', 'ilanel-poc' ), absint( $ilanel_i ) + 1 );
						?>"></button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</section>

	<?php // 2. Breadcrumbs ?>
	<div class="rg-breadcrumbs">
		<div class="rg-shell"><?php ILANEL_Breadcrumbs::render(); ?></div>
	</div>

	<main id="main" class="rg-main">
		<article <?php wc_product_class( 'rg-product', $product ); ?>>

			<?php // 3. Lead article ?>
			<section class="rg-article rg-shell">
				<div class="rg-article__row">
					<div class="rg-article__col">
						<?php wc_get_template( 'single-product/title.php' ); ?>
					</div>

					<div class="rg-article__col rg-article__col--body">
						<div class="rg-article__content">
							<?php echo wp_kses_post( wpautop( $product->get_short_description() ) ); ?>
						</div>

						<p class="rg-cta">
							<a class="rg-link" href="#configure"><?php esc_html_e( 'Configure yours', 'ilanel-poc' ); ?> /</a>
						</p>
					</div>
				</div>
			</section>

			<?php // 4. Storytelling — alternating rows, RG's .article--reversed pattern. ?>
			<?php if ( $ilanel_story ) : ?>
				<section class="rg-story">

					<div class="rg-article rg-shell">
						<div class="rg-article__row rg-article__row--middle">
							<div class="rg-article__col rg-article__col--media">
								<div class="rg-feature rg-feature--portrait">
									<img src="<?php echo esc_url( $ilanel_story[0] ); ?>"
										alt="<?php echo esc_attr( $product->get_name() ); ?>" loading="lazy">
								</div>
							</div>

							<div class="rg-article__col">
								<div class="rg-article__head">
									<h2 class="rg-story__title"><?php esc_html_e( 'Made in Melbourne', 'ilanel-poc' ); ?></h2>
								</div>
								<div class="rg-article__content">
									<p><?php esc_html_e( 'Every piece is assembled by hand in the studio\'s Melbourne workshop. Glass is blown, metal is spun and finished, and each fixture is built to order — which is why no two are identical and why lead times are measured in weeks, not days.', 'ilanel-poc' ); ?></p>
								</div>
							</div>
						</div>
					</div>

					<?php if ( isset( $ilanel_story[1] ) ) : ?>
						<div class="rg-article rg-article--reversed rg-shell">
							<div class="rg-article__row rg-article__row--middle">
								<div class="rg-article__col">
									<div class="rg-article__head">
										<h2 class="rg-story__title"><?php esc_html_e( 'Specified with confidence', 'ilanel-poc' ); ?></h2>
									</div>
									<div class="rg-article__content">
										<p><?php esc_html_e( 'Selected for the National Gallery of Victoria, the Australian War Memorial, Four Seasons and The Hour Glass. Custom lengths, finishes and colourways are available on enquiry, with 3D files and full documentation supplied to the trade.', 'ilanel-poc' ); ?></p>
									</div>
									<?php if ( $ilanel_spec ) : ?>
										<p class="rg-cta">
											<a class="rg-link" href="<?php echo esc_url( $ilanel_spec ); ?>" target="_blank" rel="noopener">
												<?php esc_html_e( 'Explore catalogue', 'ilanel-poc' ); ?> /
											</a>
										</p>
									<?php endif; ?>
								</div>

								<div class="rg-article__col rg-article__col--media">
									<div class="rg-feature rg-feature--landscape">
										<img src="<?php echo esc_url( $ilanel_story[1] ); ?>"
											alt="<?php echo esc_attr( $product->get_name() ); ?>" loading="lazy">
									</div>
								</div>
							</div>
						</div>
					<?php endif; ?>

				</section>
			<?php endif; ?>

			<?php // 5. Configurator — end of funnel. ?>
			<section class="rg-configure" id="configure">
				<div class="rg-shell">
					<div class="rg-configure__inner">

						<div class="rg-configure__preview">
							<img class="js-config-image"
								src="<?php echo esc_url( $ilanel_swatches ? $ilanel_swatches[0]['image'] : ( $ilanel_gallery[0] ?? '' ) ); ?>"
								alt="<?php echo esc_attr( $product->get_name() ); ?>" loading="lazy">
						</div>

						<div class="rg-configure__panel">
							<div class="rg-configure__head">
								<h2 class="rg-configure__eyebrow"><?php esc_html_e( 'Configure your', 'ilanel-poc' ); ?></h2>
								<p class="rg-configure__title"><?php echo esc_html( $product->get_name() ); ?>
									<?php if ( $ilanel_type ) : ?>
										<span><?php echo esc_html( $ilanel_type ); ?></span>
									<?php endif; ?>
								</p>
							</div>

							<form class="rg-config" data-base-price="<?php echo esc_attr( $product->get_price() ); ?>">

								<?php if ( $ilanel_lengths ) : ?>
									<fieldset class="rg-config__group">
										<legend class="rg-config__label"><?php esc_html_e( 'Configurations', 'ilanel-poc' ); ?></legend>
										<div class="rg-config__options">
											<?php foreach ( $ilanel_lengths as $ilanel_i => $ilanel_length ) : ?>
												<label class="rg-config__pill">
													<input type="radio" name="ilanel_length"
														value="<?php echo esc_attr( $ilanel_length ); ?>"
														data-increment="<?php echo esc_attr( $ilanel_i * 320 ); ?>"
														<?php checked( 0, $ilanel_i ); ?>>
													<span><?php echo esc_html( $ilanel_length ); ?></span>
												</label>
											<?php endforeach; ?>
										</div>
									</fieldset>
								<?php endif; ?>

								<?php if ( $ilanel_swatches ) : ?>
									<fieldset class="rg-config__group">
										<legend class="rg-config__label"><?php esc_html_e( 'Colourway', 'ilanel-poc' ); ?></legend>
										<div class="rg-config__options rg-config__options--swatches">
											<?php foreach ( $ilanel_swatches as $ilanel_i => $ilanel_swatch ) : ?>
												<label class="rg-config__swatch">
													<input type="radio" name="ilanel_finish"
														value="<?php echo esc_attr( $ilanel_swatch['name'] ); ?>"
														data-image="<?php echo esc_url( $ilanel_swatch['image'] ); ?>"
														<?php checked( 0, $ilanel_i ); ?>>
													<span class="rg-config__swatch-media">
														<img src="<?php echo esc_url( $ilanel_swatch['image'] ); ?>"
															alt="<?php echo esc_attr( $ilanel_swatch['name'] ); ?>" loading="lazy">
													</span>
													<span class="rg-config__swatch-name"><?php echo esc_html( $ilanel_swatch['name'] ); ?></span>
												</label>
											<?php endforeach; ?>
										</div>
									</fieldset>
								<?php endif; ?>

								<div class="rg-config__summary">
									<p class="rg-config__selection">
										<span class="rg-config__label"><?php esc_html_e( 'Your selection', 'ilanel-poc' ); ?></span>
										<span class="js-config-summary"></span>
									</p>

									<p class="rg-config__price">
										<span class="rg-config__label"><?php esc_html_e( 'Price', 'ilanel-poc' ); ?></span>
										<span class="js-config-price"><?php echo wp_kses_post( wc_price( $product->get_price() ) ); ?></span>
									</p>

									<p class="rg-config__lead">
										<span class="rg-config__label"><?php esc_html_e( 'Lead time', 'ilanel-poc' ); ?></span>
										<?php echo esc_html( $ilanel_lead ); ?>
									</p>
								</div>

								<div class="rg-config__actions">
									<a class="rg-btn rg-btn--solid" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">
										<?php esc_html_e( 'Buy now', 'ilanel-poc' ); ?>
									</a>
									<a class="rg-btn" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">
										<?php esc_html_e( 'Send enquiry', 'ilanel-poc' ); ?>
									</a>
								</div>

								<?php if ( $ilanel_spec ) : ?>
									<p class="rg-config__download">
										<a class="rg-link" href="<?php echo esc_url( $ilanel_spec ); ?>" target="_blank" rel="noopener">
											<?php esc_html_e( 'Download configuration', 'ilanel-poc' ); ?> /
										</a>
									</p>
								<?php endif; ?>
							</form>
						</div>

					</div>
				</div>
			</section>

			<?php // 6. Downloads ?>
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
								<?php if ( $ilanel_spec ) : ?>
									<li><a href="<?php echo esc_url( $ilanel_spec ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Data sheet', 'ilanel-poc' ); ?></a></li>
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
