<?php
/**
 * Light Art archive.
 *
 * Rebuilt against ilanel.com/light-art measured 2026-08-12
 * (docs/reference/PROJECTS-DESIGN-SPEC-2026-08-12.md) rather than reusing the
 * projects grid. The live page is **not a grid at all** — it is a one-column
 * full-bleed sequence: every exhibition is its own 66vh band with the title
 * and an EXPLORE cta centred over the image, separated by a 10vh band of
 * silence. That rhythm, repeated across every work, is what makes the page
 * read as an exhibition walk-through rather than a card list — the single
 * most transferable idea the measurement found.
 *
 * A 3-up portrait grid was tried first and discarded once the real page was
 * measured: it made the studio's exhibition work look like catalogue stock,
 * which is the opposite of what this section is for.
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="main" class="rg-main rg-main--lightart-index">

	<header class="rg-lightart__head">
		<div class="rg-shell">
			<span class="rg-section__label"><?php esc_html_e( 'Light art /', 'ilanel-poc' ); ?></span>
			<h1 class="rg-lightart__title"><?php esc_html_e( 'Light as the material', 'ilanel-poc' ); ?></h1>
		</div>
	</header>

	<?php if ( have_posts() ) : ?>

		<?php
		while ( have_posts() ) :
			the_post();

			/*
			 * Split "Work / Venue / Occasion" so the venue can sit as a
			 * smaller line under the work name — same convention used on the
			 * single template and the earlier grid version of this archive.
			 */
			$ilanel_full  = get_the_title();
			$ilanel_parts = array_map( 'trim', explode( '/', $ilanel_full ) );
			$ilanel_name  = $ilanel_parts[0];
			$ilanel_venue = count( $ilanel_parts ) > 1 ? implode( ' · ', array_slice( $ilanel_parts, 1 ) ) : '';

			$ilanel_bg = get_the_post_thumbnail_url( get_the_ID(), 'large' );
			?>

			<section class="rg-lightart__band" aria-label="<?php echo esc_attr( $ilanel_full ); ?>">
				<a href="<?php the_permalink(); ?>" class="rg-lightart__band-link">
					<?php if ( $ilanel_bg ) : ?>
						<div class="rg-lightart__band-media"
							style="background-image:url('<?php echo esc_url( $ilanel_bg ); ?>')"
							role="img"
							aria-label="<?php echo esc_attr( $ilanel_full ); ?>"></div>
					<?php endif; ?>

					<div class="rg-lightart__band-copy">
						<h2 class="rg-lightart__band-name"><?php echo esc_html( $ilanel_name ); ?></h2>

						<?php if ( $ilanel_venue ) : ?>
							<p class="rg-lightart__band-venue"><?php echo esc_html( $ilanel_venue ); ?></p>
						<?php endif; ?>

						<span class="rg-lightart__band-cta"><?php esc_html_e( 'Explore', 'ilanel-poc' ); ?></span>
					</div>
				</a>
			</section>

			<div class="rg-lightart__gap" aria-hidden="true"></div>

			<?php
		endwhile;
		?>

		<div class="rg-shell">
			<?php
			the_posts_pagination(
				array(
					'mid_size'           => 2,
					'prev_text'          => esc_html__( 'Previous', 'ilanel-poc' ),
					'next_text'          => esc_html__( 'Next', 'ilanel-poc' ),
					'screen_reader_text' => esc_html__( 'Light art navigation', 'ilanel-poc' ),
				)
			);
			?>
		</div>

	<?php else : ?>

		<div class="rg-shell">
			<p class="rg-range__empty"><?php esc_html_e( 'No works yet.', 'ilanel-poc' ); ?></p>
		</div>

	<?php endif; ?>

</main>

<?php
get_footer();
