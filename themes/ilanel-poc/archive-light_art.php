<?php
/**
 * Light Art archive.
 *
 * Shares the projects index anatomy but reads darker and slower: this is
 * exhibition work — Melbourne Design Week, Goldstone Gallery, JAHM, City of
 * Melbourne — and the pieces are shown as artworks rather than as installs.
 *
 * The practical difference is the tile ratio. Project photography is interiors
 * (landscape); light art is objects and installations, often shot portrait, so
 * the grid is taller and runs three-up instead of two.
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ilanel_total = (int) $GLOBALS['wp_query']->found_posts;
?>

<main id="main" class="rg-main rg-main--archive rg-main--lightart">
	<div class="rg-shell">

		<header class="rg-index__header">
			<span class="rg-section__label"><?php esc_html_e( 'Light art /', 'ilanel-poc' ); ?></span>

			<h1 class="rg-index__title"><?php esc_html_e( 'Light as the material', 'ilanel-poc' ); ?></h1>

			<p class="rg-index__intro">
				<?php
				printf(
					/* translators: %d: number of works */
					esc_html__( '%d exhibitions and commissions — gallery shows, Melbourne Design Week and public works, where the studio treats light as a medium rather than a fitting.', 'ilanel-poc' ),
					absint( $ilanel_total )
				);
				?>
			</p>
		</header>

		<?php if ( have_posts() ) : ?>

			<ul class="rg-index rg-index--art">
				<?php while ( have_posts() ) : ?>
					<?php
					the_post();

					/*
					 * ILANEL title these "Work / Venue / Occasion" — e.g.
					 * "Form & Phenomenon / Goldstone Gallery / Melbourne Design
					 * Week 2026", up to 69 characters. Printed whole, the long
					 * ones wrap to three lines and break the baseline that makes
					 * the grid read as composed rather than a list. Only the
					 * work name goes in the tile; the venue becomes a smaller
					 * caption line, same split the single template uses for
					 * the H1/eyebrow.
					 */
					$ilanel_full  = get_the_title();
					$ilanel_parts = array_map( 'trim', explode( '/', $ilanel_full ) );
					$ilanel_name  = $ilanel_parts[0];
					$ilanel_venue = count( $ilanel_parts ) > 1 ? implode( ' · ', array_slice( $ilanel_parts, 1 ) ) : '';
					?>
					<li class="rg-index__item">
						<a href="<?php the_permalink(); ?>">
							<div class="rg-index__media">
								<?php
								if ( has_post_thumbnail() ) {
									the_post_thumbnail( 'medium_large' );
								}
								?>
							</div>

							<div class="rg-index__meta">
								<div>
									<h2 class="rg-index__name"><?php echo esc_html( $ilanel_name ); ?></h2>

									<?php if ( $ilanel_venue ) : ?>
										<p class="rg-index__type"><?php echo esc_html( $ilanel_venue ); ?></p>
									<?php endif; ?>
								</div>

								<span class="rg-index__more"><?php esc_html_e( 'View work /', 'ilanel-poc' ); ?></span>
							</div>
						</a>
					</li>
				<?php endwhile; ?>
			</ul>

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

		<?php else : ?>

			<p class="rg-range__empty"><?php esc_html_e( 'No works yet.', 'ilanel-poc' ); ?></p>

		<?php endif; ?>

	</div>
</main>

<?php
get_footer();
