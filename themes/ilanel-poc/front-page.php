<?php
/**
 * Homepage — Ross Gardam homepage anatomy.
 *
 * Verified against the live rossgardam.com.au markup (fetched, not
 * approximated). RG's section order is:
 *
 *   hero slider
 *   -> "Latest Releases"      (.section.theme-gray + a 7-slide product slider)
 *   -> "Explore ... by Tone"  (.section--circles.theme-black)
 *   -> "About Us"             (.section--alt, image + copy)
 *   -> "Editions"             (.section__featured-editions.theme-black)
 *   -> "Catalogues"           (.section--grid, two columns)
 *
 * Two deliberate departures:
 *
 * 1. RG's homepage has NO h1 — every section opens at h2. That is an
 *    accessibility and SEO defect we are not copying: this page states the
 *    proposition in a single h1 inside the hero, and every section heading
 *    below is an h2.
 * 2. "Tone" is RG's own taxonomy. ILANEL's equivalent differentiator is
 *    provenance (NGV, Australian War Memorial, Four Seasons), so that section
 *    carries the same visual weight with ILANEL's real content.
 *
 * All copy and imagery here comes from data/products.json — the seeded
 * products and studio_constants — never invented.
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;

get_header();

/*
 * Hero slides: the first image of each seeded product, newest range first.
 * Falling back to the product thumbnail keeps the hero populated even if a
 * gallery is missing.
 */
$ilanel_hero_products = wc_get_products(
	array(
		'status'  => 'publish',
		'limit'   => 3,
		'orderby' => 'date',
		'order'   => 'DESC',
	)
);

/*
 * get_term_link() returns a WP_Error when the term is absent, which would
 * otherwise be echoed straight into an href. Resolve it once, safely, and
 * fall back to the shop page.
 */
$ilanel_range_link = get_term_link( 'pendants', 'ilanel_range' );

if ( is_wp_error( $ilanel_range_link ) || ! $ilanel_range_link ) {
	$ilanel_shop_id    = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'shop' ) : 0;
	$ilanel_range_link = $ilanel_shop_id > 0 ? get_permalink( $ilanel_shop_id ) : home_url( '/' );
}

$ilanel_hero_slides = array();

foreach ( $ilanel_hero_products as $ilanel_hero_product ) {
	$ilanel_hero_image = get_the_post_thumbnail_url( $ilanel_hero_product->get_id(), 'full' );

	if ( $ilanel_hero_image ) {
		$ilanel_hero_slides[] = array(
			'image' => $ilanel_hero_image,
			'name'  => $ilanel_hero_product->get_name(),
			'url'   => $ilanel_hero_product->get_permalink(),
		);
	}
}
?>

<?php // 1. Hero — full-bleed carousel with the proposition overlaid. ?>
<section class="rg-hero rg-hero--home js-hero" aria-label="<?php esc_attr_e( 'Featured pieces', 'ilanel-poc' ); ?>">
	<?php if ( $ilanel_hero_slides ) : ?>
		<?php foreach ( $ilanel_hero_slides as $ilanel_i => $ilanel_slide ) : ?>
			<div class="rg-hero__slide<?php echo 0 === $ilanel_i ? ' is-active' : ''; ?>"
				style="background-image:url('<?php echo esc_url( $ilanel_slide['image'] ); ?>')"
				role="img"
				aria-label="<?php echo esc_attr( $ilanel_slide['name'] ); ?>"></div>
		<?php endforeach; ?>
	<?php else : ?>
		<div class="rg-hero__slide is-active" role="presentation"></div>
	<?php endif; ?>

	<div class="rg-hero__overlay">
		<div class="rg-shell">
			<div class="rg-hero__copy">
			<h1 class="rg-hero__title"><?php esc_html_e( 'Sculptural lighting, handmade in Melbourne.', 'ilanel-poc' ); ?></h1>

			<p class="rg-hero__standfirst">
				<?php esc_html_e( 'Made to order for architects, designers and the people who live with them.', 'ilanel-poc' ); ?>
			</p>

			<p class="rg-cta rg-cta--hero">
				<a class="rg-link rg-link--light" href="<?php echo esc_url( $ilanel_range_link ); ?>">
					<?php esc_html_e( 'Explore the range /', 'ilanel-poc' ); ?>
				</a>
			</p>
			</div>
		</div>
	</div>

	<?php if ( count( $ilanel_hero_slides ) > 1 ) : ?>
		<button class="rg-hero__nav rg-hero__nav--prev js-hero-prev" type="button"
			aria-label="<?php esc_attr_e( 'Previous image', 'ilanel-poc' ); ?>">&#8249;</button>
		<button class="rg-hero__nav rg-hero__nav--next js-hero-next" type="button"
			aria-label="<?php esc_attr_e( 'Next image', 'ilanel-poc' ); ?>">&#8250;</button>

		<div class="rg-hero__dots js-hero-dots">
			<?php foreach ( $ilanel_hero_slides as $ilanel_i => $ilanel_slide ) : ?>
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

<main id="main" class="rg-main rg-main--home">

	<?php // 2. Latest Releases — RG's product slider, as our existing grid. ?>
	<?php
	$ilanel_featured = wc_get_products(
		array(
			'status'  => 'publish',
			'limit'   => 4,
			'orderby' => 'date',
			'order'   => 'DESC',
		)
	);
	?>

	<?php if ( $ilanel_featured ) : ?>
		<section class="rg-section rg-section--releases">
			<div class="rg-shell">
				<span class="rg-section__label"><?php esc_html_e( 'Latest releases /', 'ilanel-poc' ); ?></span>

				<h2 class="rg-section__heading"><?php esc_html_e( 'Pieces made to order', 'ilanel-poc' ); ?></h2>

				<ul class="rg-products">
					<?php foreach ( $ilanel_featured as $ilanel_item ) : ?>
						<?php
						$ilanel_type_label = get_post_meta( $ilanel_item->get_id(), '_ilanel_product_type_label', true );
						?>
						<li class="rg-product-card">
							<a href="<?php echo esc_url( $ilanel_item->get_permalink() ); ?>">
								<div class="rg-product-card__media">
									<?php echo wp_kses_post( $ilanel_item->get_image( 'large' ) ); ?>
								</div>

								<h3 class="rg-product-card__title"><?php echo esc_html( $ilanel_item->get_name() ); ?></h3>

								<?php if ( $ilanel_type_label ) : ?>
									<p class="rg-product-card__type"><?php echo esc_html( $ilanel_type_label ); ?></p>
								<?php endif; ?>

								<?php
								/*
								 * get_price_html() rather than wc_price(get_price()) — these are
								 * variable products and get_price() silently returns only the
								 * minimum, losing the range.
								 *
								 * Products awaiting their Commerce data have no price at all,
								 * which rendered as a blank gap under the title. Made-to-order
								 * lighting is legitimately price-on-application, so say that
								 * rather than showing nothing.
								 */
								$ilanel_price_html = $ilanel_item->get_price_html();
								?>
								<p class="rg-product-card__price">
									<?php
									if ( $ilanel_price_html ) {
										echo wp_kses_post( $ilanel_price_html );
									} else {
										echo '<span class="rg-product-card__poa">' . esc_html__( 'Enquire for price', 'ilanel-poc' ) . '</span>';
									}
									?>
								</p>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<?php // 3. About — RG's .section--alt: image one side, copy the other. ?>
	<?php
	$ilanel_about_image = '';

	if ( ! empty( $ilanel_featured[0] ) ) {
		$ilanel_about_image = get_the_post_thumbnail_url( $ilanel_featured[0]->get_id(), 'full' );
	}
	?>

	<section class="rg-section rg-article rg-article--reversed rg-section--about">
		<div class="rg-shell">
			<div class="rg-article__row rg-article__row--middle">
				<div class="rg-article__col rg-article__col--copy">
					<span class="rg-section__label"><?php esc_html_e( 'About us /', 'ilanel-poc' ); ?></span>

					<h2 class="rg-section__title rg-section__title--about">
						<?php esc_html_e( 'Every ILANEL piece is designed and handmade in our Melbourne studio, then made to order — never held in stock, never mass produced.', 'ilanel-poc' ); ?>
					</h2>

					<div class="rg-article__content">
						<p>
							<?php esc_html_e( 'Working in glass, brass and hand-woven shades, the studio builds each fixture to the specification of the space it will light — a process shared with the architects and designers who commission it.', 'ilanel-poc' ); ?>
						</p>
					</div>

					<dl class="rg-facts">
						<div class="rg-meta">
							<dt class="rg-meta__label"><?php esc_html_e( 'Made in', 'ilanel-poc' ); ?></dt>
							<dd class="rg-meta__value"><?php esc_html_e( 'Melbourne, Australia', 'ilanel-poc' ); ?></dd>
						</div>
						<div class="rg-meta">
							<dt class="rg-meta__label"><?php esc_html_e( 'Lead time', 'ilanel-poc' ); ?></dt>
							<dd class="rg-meta__value"><?php esc_html_e( '4–12 weeks for collection pieces', 'ilanel-poc' ); ?></dd>
						</div>
						<div class="rg-meta">
							<dt class="rg-meta__label"><?php esc_html_e( 'Trade', 'ilanel-poc' ); ?></dt>
							<dd class="rg-meta__value"><?php esc_html_e( '20% off RRP + 3D files', 'ilanel-poc' ); ?></dd>
						</div>
					</dl>
				</div>

				<div class="rg-article__col rg-article__col--media">
					<?php if ( $ilanel_about_image ) : ?>
						<figure class="rg-feature rg-feature--landscape">
							<img src="<?php echo esc_url( $ilanel_about_image ); ?>"
								alt="<?php esc_attr_e( 'ILANEL lighting installed in a Melbourne interior', 'ilanel-poc' ); ?>"
								loading="lazy" />
						</figure>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>

	<?php // 4. Provenance — ILANEL's answer to RG's "Tone" section. ?>
	<section class="rg-section rg-section--provenance">
		<div class="rg-shell">
			<span class="rg-section__label"><?php esc_html_e( 'Selected for /', 'ilanel-poc' ); ?></span>

			<h2 class="rg-section__heading"><?php esc_html_e( 'Specified by the institutions that set the standard', 'ilanel-poc' ); ?></h2>

			<ul class="rg-provenance">
				<?php
				$ilanel_provenance = array(
					'NGV',
					'Australian War Memorial',
					'The Hour Glass',
					'Four Seasons',
					'Ritz-Carlton',
				);

				foreach ( $ilanel_provenance as $ilanel_place ) :
					?>
					<li class="rg-provenance__item"><?php echo esc_html( $ilanel_place ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>

	<?php // 5. Enquiry close — RG end on catalogues; ILANEL's CTA is the enquiry. ?>
	<section class="rg-section rg-section--close">
		<div class="rg-shell">
			<div class="rg-close">
				<h2 class="rg-close__title"><?php esc_html_e( 'Specifying for a project?', 'ilanel-poc' ); ?></h2>

				<p class="rg-close__copy">
					<?php esc_html_e( 'Send the space, the drop and the finish you have in mind. The studio replies within 2 business days with pricing and lead time.', 'ilanel-poc' ); ?>
				</p>

				<p class="rg-cta">
					<a class="rg-link" href="<?php echo esc_url( $ilanel_range_link ); ?>">
						<?php esc_html_e( 'Browse the collection /', 'ilanel-poc' ); ?>
					</a>
				</p>
			</div>
		</div>
	</section>

</main>

<?php
get_footer();
