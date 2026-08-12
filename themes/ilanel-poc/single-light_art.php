<?php
/**
 * Single Light Art work.
 *
 * Rebuilt against a measured reference — ILANEL's own live page for
 * "Form & Phenomenon" (docs/reference/PROJECTS-DESIGN-SPEC-2026-08-12.md) —
 * rather than reused project markup. Their real pattern is genuinely
 * different from a project page, not just a re-skin:
 *
 *   1. Full-bleed 80vh hero, title centred OVER the image (kept as-is; this
 *      part of the earlier template already matched)
 *   2. Intro copy in a single wide column, followed by exhibition facts
 *      (dates, venue, address) as plain text, not a metadata rail
 *   3. "Studies" — alternating 50/50 image/text sections, each with its own
 *      Materials / Dimensions / Technology line
 *   4. A horizontal gallery reel near the bottom
 *   5. BACK TO LIGHT ART / HOME / next-work exit — not a single next-work
 *      band with a background image
 *
 * No product relation, deliberately: this is exhibition and commission work
 * (Melbourne Design Week, Goldstone Gallery, JAHM, City of Melbourne), not
 * assembled from catalogue pieces, so there is no "Lighting used" section.
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
	$ilanel_intro   = isset( $ilanel_paras[0] ) ? $ilanel_paras[0] : '';
	$ilanel_studies = array_slice( $ilanel_paras, 1 );

	/*
	 * ILANEL title these "Work / Venue / Occasion" —
	 * "Form & Phenomenon / Goldstone Gallery / Melbourne Design Week 2026".
	 * Split on the first slash so the venue sits as an eyebrow rather than
	 * crowding the headline.
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

	<main id="main" class="rg-main rg-main--project rg-main--art">

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

		<?php // 2. Intro: single wide column, then plain-text exhibition facts. ?>
		<section class="rg-art__intro">
			<div class="rg-shell">
				<div class="rg-art__intro-measure">
					<?php echo wp_kses_post( $ilanel_intro ); ?>
				</div>

				<?php if ( $ilanel_venue ) : ?>
					<div class="rg-art__facts">
						<p><strong><?php esc_html_e( 'Shown at', 'ilanel-poc' ); ?></strong><br /><?php echo esc_html( $ilanel_venue ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</section>

		<?php
		/*
		 * 3. Studies: alternating 50/50 image/text, one per remaining
		 * paragraph. ILANEL's real page gives each study its own Materials /
		 * Dimensions / Technology line; the seed data does not carry those
		 * per-work facts, so this omits inventing them rather than showing a
		 * plausible-looking fabrication.
		 */
		?>
		<?php if ( $ilanel_studies ) : ?>
			<div class="rg-art__studies">
				<?php foreach ( $ilanel_studies as $ilanel_i => $ilanel_para ) : ?>
					<?php
					if ( ! trim( wp_strip_all_tags( $ilanel_para ) ) ) {
						continue;
					}

					$ilanel_img  = isset( $ilanel_gallery[ $ilanel_i ] ) ? $ilanel_gallery[ $ilanel_i ] : '';
					$ilanel_flip = ( 1 === $ilanel_i % 2 );
					?>
					<div class="rg-art__study<?php echo $ilanel_flip ? ' rg-art__study--reversed' : ''; ?>">
						<div class="rg-shell">
							<div class="rg-art__study-inner">
								<?php if ( $ilanel_img ) : ?>
									<div class="rg-art__study-media">
										<img src="<?php echo esc_url( $ilanel_img ); ?>"
											alt="<?php
											/* translators: %s: work name */
											printf( esc_attr__( '%s — detail', 'ilanel-poc' ), esc_attr( $ilanel_name ) );
											?>"
											loading="lazy" />
									</div>
								<?php endif; ?>

								<div class="rg-art__study-copy">
									<?php echo wp_kses_post( $ilanel_para ); ?>
								</div>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php
		/*
		 * 4. Gallery reel — a horizontal strip, not paired thumbnails. Any
		 * gallery images beyond what the studies consumed.
		 */
		$ilanel_reel = array_slice( $ilanel_gallery, count( $ilanel_studies ) );
		?>
		<?php if ( $ilanel_reel ) : ?>
			<section class="rg-art__reel">
				<ul class="rg-art__reel-track">
					<?php foreach ( $ilanel_reel as $ilanel_index => $ilanel_src ) : ?>
						<li>
							<img src="<?php echo esc_url( $ilanel_src ); ?>"
								alt="<?php
								/* translators: 1: work name, 2: image number */
								printf( esc_attr__( '%1$s — exhibition photograph %2$d', 'ilanel-poc' ), esc_attr( $ilanel_name ), absint( $ilanel_index ) + 1 );
								?>"
								loading="lazy" />
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<?php
		// 5. Back to Light Art / Home / next work.
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

		<section class="rg-art__exit">
			<div class="rg-shell">
				<div class="rg-art__exit-buttons">
					<a class="rg-art__button" href="<?php echo esc_url( get_post_type_archive_link( 'light_art' ) ); ?>">
						<?php esc_html_e( 'Back to Light Art', 'ilanel-poc' ); ?>
					</a>
					<a class="rg-art__button" href="<?php echo esc_url( home_url( '/' ) ); ?>">
						<?php esc_html_e( 'Home', 'ilanel-poc' ); ?>
					</a>
				</div>

				<?php if ( $ilanel_next ) : ?>
					<a class="rg-art__next" href="<?php echo esc_url( get_permalink( $ilanel_next->ID ) ); ?>">
						<span><?php echo esc_html( trim( explode( '/', get_the_title( $ilanel_next->ID ) )[0] ) ); ?></span>
						<span class="rg-art__next-arrow" aria-hidden="true">&#8250;</span>
					</a>
				<?php endif; ?>
			</div>
		</section>

	</main>

	<?php
endwhile;

get_footer();
