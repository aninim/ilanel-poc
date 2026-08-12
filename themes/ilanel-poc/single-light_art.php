<?php
/**
 * Single Light Art work.
 *
 * Same editorial anatomy as a project — hero, standfirst beside facts, body at
 * a readable measure, sequenced gallery, next work — with two differences that
 * matter:
 *
 *   - No product relation. This is exhibition and commission work (Melbourne
 *     Design Week, Goldstone Gallery, JAHM, City of Melbourne), not something
 *     assembled from catalogue pieces, so there is no "Lighting used".
 *   - The facts column names the exhibition context rather than the fitting.
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$ilanel_art_id = get_the_ID();

	$ilanel_gallery = get_post_meta( $ilanel_art_id, '_ilanel_project_gallery', true );
	$ilanel_gallery = is_array( $ilanel_gallery ) ? $ilanel_gallery : array();

	$ilanel_hero = get_the_post_thumbnail_url( $ilanel_art_id, 'full' );

	$ilanel_content = apply_filters( 'the_content', get_the_content() );
	$ilanel_paras   = array_values( array_filter( preg_split( '#(?=<p)#', $ilanel_content ) ) );
	$ilanel_lede    = isset( $ilanel_paras[0] ) ? $ilanel_paras[0] : '';
	$ilanel_body    = implode( '', array_slice( $ilanel_paras, 1 ) );

	/*
	 * ILANEL title these "Work / Venue" — "Form & Phenomenon / Goldstone
	 * Gallery / Melbourne Design Week 2026". Split on the first slash so the
	 * venue can sit as an eyebrow rather than crowding the headline.
	 */
	$ilanel_full  = get_the_title();
	$ilanel_parts = array_map( 'trim', explode( '/', $ilanel_full ) );
	$ilanel_name  = $ilanel_parts[0];
	$ilanel_venue = count( $ilanel_parts ) > 1 ? implode( ' · ', array_slice( $ilanel_parts, 1 ) ) : '';
	?>

	<?php if ( $ilanel_hero ) : ?>
		<link rel="preload" as="image" fetchpriority="high" href="<?php echo esc_url( $ilanel_hero ); ?>">

		<section class="rg-hero rg-hero--project rg-hero--art" aria-label="<?php echo esc_attr( $ilanel_full ); ?>">
			<div class="rg-hero__slide is-active"
				style="background-image:url('<?php echo esc_url( $ilanel_hero ); ?>')"
				role="img"
				aria-label="<?php echo esc_attr( $ilanel_full ); ?>"></div>

			<div class="rg-hero__overlay">
				<div class="rg-shell">
					<div class="rg-hero__copy">
						<span class="rg-hero__eyebrow"><?php esc_html_e( 'Light art', 'ilanel-poc' ); ?></span>
						<h1 class="rg-hero__title rg-hero__title--project"><?php echo esc_html( $ilanel_name ); ?></h1>

						<?php if ( $ilanel_venue ) : ?>
							<p class="rg-hero__venue"><?php echo esc_html( $ilanel_venue ); ?></p>
						<?php endif; ?>
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
						<li><a href="<?php echo esc_url( get_post_type_archive_link( 'light_art' ) ); ?>"><?php esc_html_e( 'Light Art', 'ilanel-poc' ); ?></a></li>
						<li><span aria-current="page"><?php echo esc_html( $ilanel_name ); ?></span></li>
					</ol>
				</nav>
			</div>
		</div>

		<?php if ( ! $ilanel_hero ) : ?>
			<div class="rg-shell">
				<h1 class="rg-product__name"><?php echo esc_html( $ilanel_name ); ?></h1>
			</div>
		<?php endif; ?>

		<section class="rg-project__intro">
			<div class="rg-shell">
				<div class="rg-project__intro-row">
					<div class="rg-project__lede">
						<?php echo wp_kses_post( $ilanel_lede ); ?>
					</div>

					<aside class="rg-project__facts">
						<dl>
							<?php if ( $ilanel_venue ) : ?>
								<div class="rg-meta">
									<dt class="rg-meta__label"><?php esc_html_e( 'Shown at', 'ilanel-poc' ); ?></dt>
									<dd class="rg-meta__value"><?php echo esc_html( $ilanel_venue ); ?></dd>
								</div>
							<?php endif; ?>

							<div class="rg-meta">
								<dt class="rg-meta__label"><?php esc_html_e( 'Studio', 'ilanel-poc' ); ?></dt>
								<dd class="rg-meta__value"><?php esc_html_e( 'Melbourne, Australia', 'ilanel-poc' ); ?></dd>
							</div>

							<div class="rg-meta">
								<dt class="rg-meta__label"><?php esc_html_e( 'Discipline', 'ilanel-poc' ); ?></dt>
								<dd class="rg-meta__value"><?php esc_html_e( 'Light art & commission', 'ilanel-poc' ); ?></dd>
							</div>
						</dl>

						<p class="rg-cta">
							<a class="rg-link" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">
								<?php esc_html_e( 'Commission a work /', 'ilanel-poc' ); ?>
							</a>
						</p>
					</aside>
				</div>
			</div>
		</section>

		<?php if ( trim( wp_strip_all_tags( $ilanel_body ) ) ) : ?>
			<section class="rg-project__body">
				<div class="rg-shell">
					<div class="rg-project__measure">
						<?php echo wp_kses_post( $ilanel_body ); ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $ilanel_gallery ) : ?>
			<section class="rg-project__gallery">
				<?php if ( isset( $ilanel_gallery[0] ) ) : ?>
					<figure class="rg-project__wide">
						<img src="<?php echo esc_url( $ilanel_gallery[0] ); ?>"
							alt="<?php
							/* translators: %s: work name */
							printf( esc_attr__( '%s — exhibition view', 'ilanel-poc' ), esc_attr( $ilanel_name ) );
							?>"
							loading="lazy" />
					</figure>
				<?php endif; ?>

				<?php if ( count( $ilanel_gallery ) > 1 ) : ?>
					<div class="rg-shell">
						<ul class="rg-project__pairs">
							<?php foreach ( array_slice( $ilanel_gallery, 1 ) as $ilanel_index => $ilanel_src ) : ?>
								<li>
									<img src="<?php echo esc_url( $ilanel_src ); ?>"
										alt="<?php
										/* translators: 1: work name, 2: image number */
										printf( esc_attr__( '%1$s — exhibition photograph %2$d', 'ilanel-poc' ), esc_attr( $ilanel_name ), absint( $ilanel_index ) + 2 );
										?>"
										loading="lazy" />
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
			</section>
		<?php endif; ?>

		<?php
		$ilanel_next = get_previous_post();

		if ( ! $ilanel_next ) {
			$ilanel_recent = get_posts(
				array(
					'post_type'      => 'light_art',
					'posts_per_page' => 1,
					'post__not_in'   => array( $ilanel_art_id ),
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
						<span class="rg-section__label"><?php esc_html_e( 'Next work /', 'ilanel-poc' ); ?></span>
						<h2 class="rg-project__next-title">
							<?php echo esc_html( trim( explode( '/', get_the_title( $ilanel_next->ID ) )[0] ) ); ?>
						</h2>
					</div>
				</a>
			</section>
		<?php endif; ?>

	</main>

	<?php
endwhile;

get_footer();
