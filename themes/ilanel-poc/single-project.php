<?php
/**
 * Single project.
 *
 * Rebuilt against a measured reference (docs/reference/
 * PROJECTS-DESIGN-SPEC-2026-08-12.md), not invented. RG's real project item
 * — measured on Orrong by Studio Cobe, the nearest equivalent since RG has
 * no dedicated Projects section — differs from the first version of this
 * template in three ways that matter:
 *
 *   1. The hero is CONTAINED inside the shell, not full-bleed, and the title
 *      sits ABOVE it, not overlaid on the image.
 *   2. The story alternates image/copy in ASYMMETRIC two-column rows
 *      (35.5/64.5 reversed, 40.5/59.5 forward) rather than a uniform gallery
 *      grid — every row is a deliberate change of mass.
 *   3. Credits (architect, photography, products used) are woven into the
 *      copy near the top, not held in a separate metadata rail.
 *   4. The page exits on a "Discover More" related-items carousel, not a
 *      single next-project link.
 *
 * A project page names the pieces installed in it, and each product page
 * names the projects it appears in — the relation is read from opposite ends
 * of the same stored data, so the two directions cannot fall out of step.
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

	$ilanel_hero = get_the_post_thumbnail_url( $ilanel_project_id, 'large' );

	/*
	 * Split content into paragraphs so it can alternate against gallery
	 * images row by row, RG-style, instead of running as one block followed
	 * by a separate image grid.
	 */
	$ilanel_content = apply_filters( 'the_content', get_the_content() );
	$ilanel_paras   = array_values( array_filter( preg_split( '#(?=<p)#', $ilanel_content ) ) );
	?>

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

		<?php // 1. Title above the hero, both inside the shell. ?>
		<header class="rg-project__head">
			<div class="rg-shell">
				<h1 class="rg-product__name"><?php the_title(); ?></h1>
			</div>
		</header>

		<?php if ( $ilanel_hero ) : ?>
			<link rel="preload" as="image" fetchpriority="high" href="<?php echo esc_url( $ilanel_hero ); ?>">

			<div class="rg-shell">
				<figure class="rg-project__hero-figure">
					<img src="<?php echo esc_url( $ilanel_hero ); ?>"
						alt="<?php echo esc_attr( get_the_title() ); ?>"
						fetchpriority="high" />
				</figure>
			</div>
		<?php endif; ?>

		<?php
		/*
		 * 2. Credits, woven into the intro rather than a metadata rail.
		 *
		 * RG print title / date-ish category / architect / photography as an
		 * inline uppercase list directly beneath the hero. ILANEL's own
		 * project pages carry no such credit block, so the studio's own facts
		 * fill that slot — still a small inline list under the hero, same
		 * position and treatment as RG's.
		 */
		?>
		<div class="rg-shell">
			<ul class="rg-project__credits">
				<li><?php the_title(); ?></li>
				<li><?php esc_html_e( 'Made in Melbourne, Australia', 'ilanel-poc' ); ?></li>

				<?php if ( $ilanel_products ) : ?>
					<li>
						<?php esc_html_e( 'Lighting', 'ilanel-poc' ); ?>
						<?php
						$ilanel_names = array();

						foreach ( $ilanel_products as $ilanel_product ) {
							$ilanel_names[] = $ilanel_product->get_name();
						}
						?>
						<?php echo esc_html( implode( ', ', $ilanel_names ) ); ?>
					</li>
				<?php endif; ?>
			</ul>
		</div>

		<?php
		/*
		 * 3. The story: asymmetric alternating rows.
		 *
		 * Each paragraph pairs with one gallery image, alternating text-left
		 * and text-right so the copy ratio flips row to row (35.5/64.5,
		 * 40.5/59.5) — the specific device the measurement identified as what
		 * keeps RG's pages from feeling like a stack of equal blocks.
		 */
		?>
		<div class="rg-project__story">
			<?php
			$ilanel_row = 0;

			foreach ( $ilanel_paras as $ilanel_i => $ilanel_para ) :
				if ( ! trim( wp_strip_all_tags( $ilanel_para ) ) ) {
					continue;
				}

				$ilanel_img  = isset( $ilanel_gallery[ $ilanel_i ] ) ? $ilanel_gallery[ $ilanel_i ] : '';
				$ilanel_flip = ( 1 === $ilanel_row % 2 );
				$ilanel_row++;
				?>
				<div class="rg-project__row<?php echo $ilanel_flip ? ' rg-project__row--reversed' : ''; ?>">
					<div class="rg-shell">
						<div class="rg-project__row-inner">
							<div class="rg-project__row-copy">
								<?php echo wp_kses_post( $ilanel_para ); ?>
							</div>

							<?php if ( $ilanel_img ) : ?>
								<div class="rg-project__row-media">
									<img src="<?php echo esc_url( $ilanel_img ); ?>"
										alt="<?php
										/* translators: %s: project name */
										printf( esc_attr__( '%s — installation', 'ilanel-poc' ), esc_attr( get_the_title() ) );
										?>"
										loading="lazy" />
								</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<?php
			endforeach;

			// Any gallery images beyond what the copy consumed still deserve a place.
			$ilanel_remaining = array_slice( $ilanel_gallery, count( $ilanel_paras ) );
			?>

			<?php foreach ( $ilanel_remaining as $ilanel_index => $ilanel_src ) : ?>
				<?php $ilanel_flip = ( 1 === $ilanel_row % 2 ); ?>
				<div class="rg-project__row rg-project__row--image-only<?php echo $ilanel_flip ? ' rg-project__row--reversed' : ''; ?>">
					<div class="rg-shell">
						<div class="rg-project__row-inner">
							<div class="rg-project__row-media">
								<img src="<?php echo esc_url( $ilanel_src ); ?>"
									alt="<?php
									/* translators: 1: project name, 2: image number */
									printf( esc_attr__( '%1$s — installation photograph %2$d', 'ilanel-poc' ), esc_attr( get_the_title() ), absint( $ilanel_index ) + count( $ilanel_paras ) + 1 );
									?>"
									loading="lazy" />
							</div>
						</div>
					</div>
				</div>
				<?php
				$ilanel_row++;
			endforeach;
			?>
		</div>

		<?php // 4. Lighting used — the reverse of "Featured in" on the product page. ?>
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
		/*
		 * 5. Discover More — a related-projects exit, not a single next link.
		 *
		 * RG close every article on a "Discover More" band with several
		 * related cards, not one next-item pointer. Pull up to 3 other
		 * projects, most recent first.
		 */
		$ilanel_discover = get_posts(
			array(
				'post_type'      => 'project',
				'posts_per_page' => 3,
				'post__not_in'   => array( $ilanel_project_id ),
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		?>

		<?php if ( $ilanel_discover ) : ?>
			<section class="rg-discover rg-discover--projects">
				<div class="rg-shell">
					<h2 class="rg-discover__title"><?php esc_html_e( 'Discover More', 'ilanel-poc' ); ?></h2>

					<ul class="rg-discover__grid">
						<?php foreach ( $ilanel_discover as $ilanel_related ) : ?>
							<li>
								<a href="<?php echo esc_url( get_permalink( $ilanel_related->ID ) ); ?>">
									<div class="rg-discover__media">
										<?php
										$ilanel_related_img = get_the_post_thumbnail_url( $ilanel_related->ID, 'medium_large' );

										if ( $ilanel_related_img ) {
											echo '<img src="' . esc_url( $ilanel_related_img ) . '" alt="' . esc_attr( get_the_title( $ilanel_related->ID ) ) . '" loading="lazy" />';
										}
										?>
									</div>
									<span class="rg-index__more rg-index__more--static">
										<?php echo esc_html( get_the_title( $ilanel_related->ID ) ); ?>
									</span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</section>
		<?php endif; ?>

	</main>

	<?php
endwhile;

get_footer();
