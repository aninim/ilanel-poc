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
 *   3. Storytelling — the product name and copy open the journey paired
 *      with a detail image, then an .article--reversed row. RG do not
 *      separate the name into its own block above the story.
 *   4. Configurator: "Configure your" + radio swatches, live price,
 *      stacked BUY NOW / SEND ENQUIRY / SPECIFIER ENQUIRY
 *   5. DOWNLOADS / section
 *   6. Discover More — related products
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

			<?php
			/*
			 * 3. Storytelling.
			 *
			 * The product name and description are the FIRST row of the
			 * journey, paired with a detail image — exactly as RG do it
			 * (image left, "Liminal / LINEAR PENDANT" + copy right). It is
			 * not a separate block above the story; it is the story's
			 * opening beat.
			 */
			?>
			<section class="rg-story">

				<div class="rg-article rg-shell">
					<div class="rg-article__row rg-article__row--middle">
						<div class="rg-article__col rg-article__col--media">
							<?php if ( isset( $ilanel_story[0] ) ) : ?>
								<div class="rg-feature rg-feature--portrait">
									<img src="<?php echo esc_url( $ilanel_story[0] ); ?>"
										alt="<?php echo esc_attr( $product->get_name() ); ?>" loading="lazy">
								</div>
							<?php endif; ?>
						</div>

						<div class="rg-article__col">
							<?php wc_get_template( 'single-product/title.php' ); ?>

							<div class="rg-article__content">
								<?php echo wp_kses_post( wpautop( $product->get_short_description() ) ); ?>
							</div>

							<p class="rg-cta">
								<a class="rg-link" href="#configure"><?php esc_html_e( 'Configure yours', 'ilanel-poc' ); ?> /</a>
							</p>
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
									<p><?php esc_html_e( 'Selected for the National Gallery of Victoria, the Australian War Memorial, Four Seasons and The Hour Glass. Every piece is assembled by hand in the studio\'s Melbourne workshop and built to order, with custom lengths and finishes available on enquiry.', 'ilanel-poc' ); ?></p>
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

			<?php // 5. Configurator — end of funnel. ?>
			<section class="rg-configure" id="configure">
				<div class="rg-shell">
					<div class="rg-configure__inner">

						<div class="rg-configure__preview">
							<?php
							$ilanel_model = ILANEL_Product_Meta::get( $ilanel_id, ILANEL_Product_Meta::FIELD_MODEL_GLB );
							$ilanel_usdz  = ILANEL_Product_Meta::get( $ilanel_id, ILANEL_Product_Meta::FIELD_MODEL_USDZ );
							$ilanel_spin  = ILANEL_Product_Meta::get_spin_frames( $ilanel_id );

							if ( $ilanel_model ) :
								/*
								 * Real 3D. Follows the studio's existing AR spec
								 * (SPEC-ar-viewer.md): Google <model-viewer>, .glb for
								 * web/Android, .usdz for iOS Quick Look.
								 */
								?>
								<model-viewer
									class="rg-model"
									src="<?php echo esc_url( $ilanel_model ); ?>"
									<?php if ( $ilanel_usdz ) : ?>
										ios-src="<?php echo esc_url( $ilanel_usdz ); ?>"
									<?php endif; ?>
									poster="<?php echo esc_url( $ilanel_gallery[0] ?? '' ); ?>"
									alt="<?php echo esc_attr( $product->get_name() ); ?>"
									camera-controls
									auto-rotate
									ar
									ar-modes="webxr scene-viewer quick-look"
									shadow-intensity="1"
									exposure="1.05"
									loading="lazy"></model-viewer>
							<?php else : ?>
								<img class="js-config-image"
									src="<?php echo esc_url( $ilanel_swatches ? $ilanel_swatches[0]['image'] : ( $ilanel_gallery[0] ?? '' ) ); ?>"
									alt="<?php echo esc_attr( $product->get_name() ); ?>" loading="lazy">

							<?php
							/*
							 * Lit / unlit toggle.
							 *
							 * ILANEL photograph most colourways twice — with the
							 * fixture on and off (the "_NB" suffix on their CDN means
							 * no bulb). Buyers of decorative lighting care enormously
							 * about how a piece reads when it is off, since it hangs
							 * in the room all day. The toggle dims the preview and
							 * lifts a warm glow to simulate it.
							 */
							?>
								<?php
								/*
								 * 360° spin.
								 *
								 * Drag-to-rotate built from the studio's existing
								 * renders — real rotation, no 3D asset required.
								 * Swap in a .glb via the Model field above and the
								 * true 3D viewer takes over automatically.
								 */
								if ( $ilanel_spin ) :
									?>
									<div class="rg-spin js-spin" hidden
										data-frames="<?php echo esc_attr( wp_json_encode( $ilanel_spin ) ); ?>">
										<img class="js-spin-frame" src="<?php echo esc_url( $ilanel_spin[0] ); ?>"
											alt="<?php echo esc_attr( $product->get_name() ); ?>" draggable="false">
										<span class="rg-spin__hint"><?php esc_html_e( 'Drag to rotate', 'ilanel-poc' ); ?></span>
									</div>

									<button type="button" class="rg-spin-toggle js-spin-toggle" aria-pressed="false">
										<?php esc_html_e( '360°', 'ilanel-poc' ); ?>
									</button>
								<?php endif; ?>
							<?php endif; ?>

							<button type="button" class="rg-lightswitch js-lightswitch"
								aria-pressed="true"
								aria-label="<?php esc_attr_e( 'Toggle light', 'ilanel-poc' ); ?>">
								<span class="rg-lightswitch__dot"></span>
								<span class="rg-lightswitch__text"><?php esc_html_e( 'Lit', 'ilanel-poc' ); ?></span>
							</button>
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

								<?php // Shown only when a previous selection is restored. ?>
								<p class="rg-config__restored js-config-restored" hidden>
									<?php esc_html_e( 'We’ve restored your last selection.', 'ilanel-poc' ); ?>
								</p>


								<?php if ( $ilanel_lengths ) : ?>
									<fieldset class="rg-config__group">
										<legend class="rg-config__label"><?php esc_html_e( 'Configurations', 'ilanel-poc' ); ?></legend>
										<div class="rg-config__options">
											<?php
											foreach ( $ilanel_lengths as $ilanel_i => $ilanel_length ) :
												// Numeric mm, used by the scale drawing.
												$ilanel_mm = (int) preg_replace( '/\D/', '', $ilanel_length );
												?>
												<label class="rg-config__pill">
													<input type="radio" name="ilanel_length"
														value="<?php echo esc_attr( $ilanel_length ); ?>"
														data-increment="<?php echo esc_attr( $ilanel_i * 320 ); ?>"
														data-mm="<?php echo esc_attr( $ilanel_mm ); ?>"
														<?php checked( 0, $ilanel_i ); ?>>
													<span><?php echo esc_html( $ilanel_length ); ?></span>
												</label>
											<?php endforeach; ?>
										</div>

										<?php
										/*
										 * Scale drawing.
										 *
										 * "1800 mm" means nothing to most buyers. Drawn against a
										 * 2400 mm dining table and a 1.7 m human silhouette, the
										 * choice becomes obvious at a glance. This is the single
										 * biggest cause of wrong-size orders in lighting.
										 */
										?>
										<div class="rg-scale" aria-hidden="true">
											<div class="rg-scale__stage">
												<div class="rg-scale__fixture js-scale-fixture"></div>
												<div class="rg-scale__table">
													<span class="rg-scale__tablelabel"><?php esc_html_e( 'Dining table 2400 mm', 'ilanel-poc' ); ?></span>
												</div>
												<div class="rg-scale__person"></div>
											</div>
											<p class="rg-scale__caption">
												<span class="js-scale-caption"></span>
												<?php esc_html_e( 'shown over a 2400 mm table', 'ilanel-poc' ); ?>
											</p>
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

									<?php
									// Concrete dispatch date beats an abstract lead time — RG show
									// "ETA DISPATCH Thu 22 Oct 2026". Derived from the lead time's
									// upper bound so it stays honest as the date moves.
									$ilanel_weeks = 12;
									if ( preg_match( '/(\d+)\s*(?:–|-|to)\s*(\d+)/u', $ilanel_lead, $ilanel_m ) ) {
										$ilanel_weeks = (int) $ilanel_m[2];
									}
									$ilanel_eta = date_i18n( 'D j M Y', strtotime( '+' . $ilanel_weeks . ' weeks' ) );
									?>
									<p class="rg-config__eta">
										<span class="rg-config__label"><?php esc_html_e( 'ETA dispatch', 'ilanel-poc' ); ?></span>
										<?php echo esc_html( $ilanel_eta ); ?>
									</p>
								</div>

								<div class="rg-config__actions">
									<a class="rg-btn rg-btn--solid" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">
										<?php esc_html_e( 'Buy now', 'ilanel-poc' ); ?>
									</a>
									<a class="rg-btn" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">
										<?php esc_html_e( 'Send enquiry', 'ilanel-poc' ); ?>
									</a>
									<a class="rg-btn rg-btn--quiet" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">
										<?php esc_html_e( 'Specifier enquiry', 'ilanel-poc' ); ?>
									</a>
								</div>

								<?php if ( $ilanel_spec ) : ?>
									<p class="rg-config__download">
										<a class="rg-link" href="<?php echo esc_url( $ilanel_spec ); ?>" target="_blank" rel="noopener">
											<?php esc_html_e( 'Download configuration', 'ilanel-poc' ); ?> /
										</a>
									</p>
								<?php endif; ?>

								<?php // RG show a share row under the configurator. ?>
								<div class="rg-share">
									<span class="rg-config__label"><?php esc_html_e( 'Share', 'ilanel-poc' ); ?></span>
									<?php
									$ilanel_url   = rawurlencode( get_permalink( $ilanel_id ) );
									$ilanel_title = rawurlencode( $product->get_name() );
									?>
									<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_attr( $ilanel_url ); ?>" target="_blank" rel="noopener">Facebook</a>
									<a href="https://pinterest.com/pin/create/button/?url=<?php echo esc_attr( $ilanel_url ); ?>&amp;description=<?php echo esc_attr( $ilanel_title ); ?>" target="_blank" rel="noopener">Pin it</a>
									<a href="mailto:?subject=<?php echo esc_attr( $ilanel_title ); ?>&amp;body=<?php echo esc_attr( $ilanel_url ); ?>">Email</a>
								</div>
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

			<?php
			// 7. Discover More — RG's related-products section, centred cards.
			$ilanel_range_term = ILANEL_Taxonomies::get_primary_range( $ilanel_id );

			$ilanel_related = get_posts(
				array(
					'post_type'      => 'product',
					'posts_per_page' => 3,
					'post__not_in'   => array( $ilanel_id ),
					'orderby'        => 'rand',
				)
			);

			if ( $ilanel_related ) :
				?>
				<section class="rg-section rg-discover">
					<div class="rg-shell">
						<h2 class="rg-discover__title"><?php esc_html_e( 'Discover More', 'ilanel-poc' ); ?></h2>

						<ul class="rg-products rg-products--discover">
							<?php
							foreach ( $ilanel_related as $ilanel_post ) :
								$ilanel_rel = wc_get_product( $ilanel_post->ID );

								if ( ! $ilanel_rel ) {
									continue;
								}

								$ilanel_rel_type = get_post_meta( $ilanel_post->ID, '_ilanel_product_type_label', true );
								?>
								<li class="rg-product-card">
									<a href="<?php echo esc_url( get_permalink( $ilanel_post->ID ) ); ?>">
										<div class="rg-product-card__media">
											<?php echo get_the_post_thumbnail( $ilanel_post->ID, 'large' ); ?>
										</div>
										<h3 class="rg-product-card__title"><?php echo esc_html( $ilanel_rel->get_name() ); ?></h3>
										<?php if ( $ilanel_rel_type ) : ?>
											<p class="rg-product-card__type"><?php echo esc_html( $ilanel_rel_type ); ?></p>
										<?php endif; ?>
										<?php if ( $ilanel_rel->get_price() ) : ?>
											<p class="rg-product-card__price">
												<?php
												printf(
													/* translators: %s: formatted price */
													esc_html__( 'From %s', 'ilanel-poc' ),
													wp_kses_post( wc_price( $ilanel_rel->get_price() ) )
												);
												?>
											</p>
										<?php endif; ?>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>

						<?php if ( $ilanel_range_term ) : ?>
							<p class="rg-discover__cta">
								<a class="rg-link" href="<?php echo esc_url( get_term_link( $ilanel_range_term ) ); ?>">
									<?php
									printf(
										/* translators: %s: range name */
										esc_html__( 'View all %s', 'ilanel-poc' ),
										esc_html( $ilanel_range_term->name )
									);
									?> /
								</a>
							</p>
						<?php endif; ?>
					</div>
				</section>
			<?php endif; ?>

		</article>
	</main>

	<?php
	/*
	 * Sticky enquiry bar — appears once the configurator is scrolled past,
	 * so the CTA stays reachable on a long page without a second visit to
	 * the top.
	 */
	?>
	<div class="rg-stickybar js-stickybar" aria-hidden="false">
		<div class="rg-shell">
			<div class="rg-stickybar__inner">
				<div class="rg-stickybar__meta">
					<span class="rg-stickybar__name"><?php echo esc_html( $product->get_name() ); ?></span>
					<?php if ( $product->get_price() ) : ?>
						<span class="rg-stickybar__price">
							<?php
							printf(
								/* translators: %s: formatted price */
								esc_html__( 'From %s', 'ilanel-poc' ),
								wp_kses_post( wc_price( $product->get_price() ) )
							);
							?>
						</span>
					<?php endif; ?>
				</div>

				<div class="rg-stickybar__actions">
					<a class="rg-link" href="#configure"><?php esc_html_e( 'Configure', 'ilanel-poc' ); ?> /</a>
					<a class="rg-btn rg-btn--solid rg-btn--small" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">
						<?php esc_html_e( 'Enquire', 'ilanel-poc' ); ?>
					</a>
				</div>
			</div>
		</div>
	</div>

	<?php
endwhile;

get_footer( 'shop' );
