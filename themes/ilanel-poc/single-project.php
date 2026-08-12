<?php
/**
 * Single project.
 *
 * A project page names the pieces installed in it, and each product page names
 * the projects it appears in. The two directions read the same relation from
 * opposite ends, so they cannot fall out of step.
 *
 * Rebuilt as an editorial page rather than "hero, paragraph, image grid":
 *
 *   1. Full-bleed hero with the title overlaid
 *   2. Standfirst + a metadata column (studio facts, lighting used)
 *   3. Body copy at a readable measure, not full width
 *   4. Gallery that alternates full-bleed and paired, so it reads as a
 *      sequence instead of a contact sheet
 *   5. Lighting used — the reverse of the product page's "Featured in"
 *   6. Next project
 *
 * Spacing follows the RG rhythm measured in
 * docs/reference/RG-HOMEPAGE-SPEC-2026-08-12.md.
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$ilanel_project_id = get_the_ID();

	$ilanel_products = class_exists( 'ILANEL_Projects' )
		? ILANEL_Projects::get_products_for_project( $ilanel_project_id )
		: array();

	$ilanel_gallery = get_post_meta( $ilanel_project_id, '_ilanel_project_gallery', true );
	$ilanel_gallery = is_array( $ilanel_gallery ) ? $ilanel_gallery : array();

	$ilanel_hero = get_the_post_thumbnail_url( $ilanel_project_id, 'full' );

	// The first paragraph carries the page as a standfirst; the rest is body.
	$ilanel_content = apply_filters( 'the_content', get_the_content() );
	$ilanel_paras   = array_values( array_filter( preg_split( '#(?=<p)#', $ilanel_content ) ) );
	$ilanel_lede    = isset( $ilanel_paras[0] ) ? $ilanel_paras[0] : '';
	$ilanel_body    = implode( '', array_slice( $ilanel_paras, 1 ) );
	?>

	<?php if ( $ilanel_hero ) : ?>
		<link rel="preload" as="image" fetchpriority="high" href="<?php echo esc_url( $ilanel_hero ); ?>">

		<section class="rg-hero rg-hero--project" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
			<div class="rg-hero__slide is-active"
				style="background-image:url('<?php echo esc_url( $ilanel_hero ); ?>')"
				role="img"
				aria-label="<?php echo esc_attr( get_the_title() ); ?>"></div>

			<div class="rg-hero__overlay">
				<div class="rg-shell">
					<div class="rg-hero__copy">
						<span class="rg-hero__eyebrow"><?php esc_html_e( 'Project', 'ilanel-poc' ); ?></span>
						<h1 class="rg-hero__title rg-hero__title--project"><?php the_title(); ?></h1>
					</div>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<main id="main" class="rg-main rg-main--project">

		<div class="rg-breadcrumbs">
			<div class="rg-shell">
				<nav class="ilanel-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'ilanel-poc' ); ?>">
					<ol>
						<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'ilanel-poc' ); ?></a></li>
						<li><a href="<?php echo esc_url( get_post_type_archive_link( 'project' ) ); ?>"><?php esc_html_e( 'Projects', 'ilanel-poc' ); ?></a></li>
						<li><span aria-current="page"><?php the_title(); ?></span></li>
					</ol>
				</nav>
			</div>
		</div>

		<?php // Title fallback when there is no hero to carry it. ?>
		<?php if ( ! $ilanel_hero ) : ?>
			<div class="rg-shell">
				<h1 class="rg-product__name"><?php the_title(); ?></h1>
			</div>
		<?php endif; ?>

		<?php // 2. Standfirst beside the facts. ?>
		<section class="rg-project__intro">
			<div class="rg-shell">
				<div class="rg-project__intro-row">
					<div class="rg-project__lede">
						<?php echo wp_kses_post( $ilanel_lede ); ?>
					</div>

					<aside class="rg-project__facts">
						<dl>
							<div class="rg-meta">
								<dt class="rg-meta__label"><?php esc_html_e( 'Studio', 'ilanel-poc' ); ?></dt>
								<dd class="rg-meta__value"><?php esc_html_e( 'Melbourne, Australia', 'ilanel-poc' ); ?></dd>
							</div>

							<?php if ( $ilanel_products ) : ?>
								<div class="rg-meta">
									<dt class="rg-meta__label"><?php esc_html_e( 'Lighting', 'ilanel-poc' ); ?></dt>
									<dd class="rg-meta__value">
										<?php
										$ilanel_names = array();

										foreach ( $ilanel_products as $ilanel_product ) {
											$ilanel_names[] = $ilanel_product->get_name();
										}

										echo esc_html( implode( ', ', $ilanel_names ) );
										?>
									</dd>
								</div>
							<?php endif; ?>

							<div class="rg-meta">
								<dt class="rg-meta__label"><?php esc_html_e( 'Made to order', 'ilanel-poc' ); ?></dt>
								<dd class="rg-meta__value"><?php esc_html_e( '4–12 weeks', 'ilanel-poc' ); ?></dd>
							</div>
						</dl>

						<p class="rg-cta">
							<a class="rg-link" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">
								<?php esc_html_e( 'Enquire about a project /', 'ilanel-poc' ); ?>
							</a>
						</p>
					</aside>
				</div>
			</div>
		</section>

		<?php // 3. Body copy, held to a readable measure. ?>
		<?php if ( trim( wp_strip_all_tags( $ilanel_body ) ) ) : ?>
			<section class="rg-project__body">
				<div class="rg-shell">
					<div class="rg-project__measure">
						<?php echo wp_kses_post( $ilanel_body ); ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<?php
		/*
		 * 4. Gallery as a sequence.
		 *
		 * The first image runs full-bleed edge to edge; the remainder pair up.
		 * A uniform grid of identical thumbnails is what made this page read as
		 * a contact sheet rather than a story.
		 */
		?>
		<?php if ( $ilanel_gallery ) : ?>
			<section class="rg-project__gallery">
				<?php foreach ( $ilanel_gallery as $ilanel_index => $ilanel_src ) : ?>
					<?php if ( 0 === $ilanel_index ) : ?>
						<figure class="rg-project__wide">
							<img src="<?php echo esc_url( $ilanel_src ); ?>"
								alt="<?php
								/* translators: %s: project name */
								printf( esc_attr__( '%s — installation', 'ilanel-poc' ), esc_attr( get_the_title() ) );
								?>"
								loading="lazy" />
						</figure>
					<?php endif; ?>
				<?php endforeach; ?>

				<?php if ( count( $ilanel_gallery ) > 1 ) : ?>
					<div class="rg-shell">
						<ul class="rg-project__pairs">
							<?php foreach ( array_slice( $ilanel_gallery, 1 ) as $ilanel_index => $ilanel_src ) : ?>
								<li>
									<img src="<?php echo esc_url( $ilanel_src ); ?>"
										alt="<?php
										/* translators: 1: project name, 2: image number */
										printf( esc_attr__( '%1$s — installation photograph %2$d', 'ilanel-poc' ), esc_attr( get_the_title() ), absint( $ilanel_index ) + 2 );
										?>"
										loading="lazy" />
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
			</section>
		<?php endif; ?>

		<?php // 5. Lighting used — the reverse of "Featured in". ?>
		<?php if ( $ilanel_products ) : ?>
			<section class="rg-section rg-section--used">
				<div class="rg-shell">
					<span class="rg-section__label"><?php esc_html_e( 'Lighting used /', 'ilanel-poc' ); ?></span>

					<h2 class="rg-section__heading"><?php esc_html_e( 'Pieces in this project', 'ilanel-poc' ); ?></h2>

					<ul class="rg-products rg-products--used">
						<?php foreach ( $ilanel_products as $ilanel_product ) : ?>
							<?php $ilanel_type = get_post_meta( $ilanel_product->get_id(), '_ilanel_product_type_label', true ); ?>
							<li class="rg-product-card">
								<a href="<?php echo esc_url( $ilanel_product->get_permalink() ); ?>">
									<div class="rg-product-card__media">
										<?php echo wp_kses_post( $ilanel_product->get_image( 'large' ) ); ?>
									</div>

									<h3 class="rg-product-card__title"><?php echo esc_html( $ilanel_product->get_name() ); ?></h3>

									<?php if ( $ilanel_type ) : ?>
										<p class="rg-product-card__type"><?php echo esc_html( $ilanel_type ); ?></p>
									<?php endif; ?>

									<?php $ilanel_price_html = $ilanel_product->get_price_html(); ?>
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

		<?php
		// 6. Next project — keeps people moving through the portfolio.
		$ilanel_next = get_previous_post();

		if ( ! $ilanel_next ) {
			// Wrap to the newest so the last project is not a dead end.
			$ilanel_recent = get_posts(
				array(
					'post_type'      => 'project',
					'posts_per_page' => 1,
					'post__not_in'   => array( $ilanel_project_id ),
					'orderby'        => 'date',
					'order'          => 'DESC',
				)
			);

			$ilanel_next = $ilanel_recent ? $ilanel_recent[0] : null;
		}
		?>

		<?php if ( $ilanel_next ) : ?>
			<section class="rg-project__next">
				<a href="<?php echo esc_url( get_permalink( $ilanel_next->ID ) ); ?>">
					<?php $ilanel_next_img = get_the_post_thumbnail_url( $ilanel_next->ID, 'large' ); ?>
					<?php if ( $ilanel_next_img ) : ?>
						<div class="rg-project__next-media"
							style="background-image:url('<?php echo esc_url( $ilanel_next_img ); ?>')"
							role="presentation"></div>
					<?php endif; ?>

					<div class="rg-project__next-copy">
						<span class="rg-section__label"><?php esc_html_e( 'Next project /', 'ilanel-poc' ); ?></span>
						<h2 class="rg-project__next-title"><?php echo esc_html( get_the_title( $ilanel_next->ID ) ); ?></h2>
					</div>
				</a>
			</section>
		<?php endif; ?>

	</main>

	<?php
endwhile;

get_footer();
