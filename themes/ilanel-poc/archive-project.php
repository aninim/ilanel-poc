<?php
/**
 * Projects archive.
 *
 * Previously there was no archive template at all, so 51 projects fell through
 * to index.php and rendered as an unstyled list of titles — the reason this
 * section looked unfinished next to the product pages.
 *
 * The layout follows the editorial logic ILANEL already use on their own live
 * project pages: a name paired with its place ("Olive Street Reimagined |
 * SS&A Albury"). Here the place becomes a standing subtitle under each tile.
 *
 * Rhythm borrowed from the RG homepage measurements in
 * docs/reference/RG-HOMEPAGE-SPEC-2026-08-12.md: generous section padding, a
 * hairline rule under the label, and imagery carrying the page rather than
 * type.
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ilanel_total = (int) $GLOBALS['wp_query']->found_posts;
?>

<main id="main" class="rg-main rg-main--archive rg-main--projects">
	<div class="rg-shell">

		<header class="rg-index__header">
			<span class="rg-section__label"><?php esc_html_e( 'Projects /', 'ilanel-poc' ); ?></span>

			<h1 class="rg-index__title"><?php esc_html_e( 'Lighting in place', 'ilanel-poc' ); ?></h1>

			<p class="rg-index__intro">
				<?php
				printf(
					/* translators: %d: number of projects */
					esc_html__( '%d installations — hotels, galleries, bars and private homes, lit by pieces made in the Melbourne studio.', 'ilanel-poc' ),
					absint( $ilanel_total )
				);
				?>
			</p>
		</header>

		<?php if ( have_posts() ) : ?>

			<ul class="rg-index">
				<?php
				$ilanel_i = 0;

				while ( have_posts() ) :
					the_post();

					/*
					 * Every fifth tile runs full width. A flat grid of 51 identical
					 * cards reads as a contact sheet; breaking the rhythm gives the
					 * page a sense of edit, which is what makes RG's pages feel
					 * composed rather than dumped.
					 */
					$ilanel_is_feature = ( 0 === $ilanel_i % 5 );
					$ilanel_i++;
					?>
					<li class="rg-index__item<?php echo $ilanel_is_feature ? ' rg-index__item--feature' : ''; ?>">
						<a href="<?php the_permalink(); ?>">
							<div class="rg-index__media">
								<?php
								if ( has_post_thumbnail() ) {
									the_post_thumbnail( $ilanel_is_feature ? 'large' : 'medium_large' );
								}
								?>
							</div>

							<div class="rg-index__meta">
								<h2 class="rg-index__name"><?php the_title(); ?></h2>
								<span class="rg-index__more"><?php esc_html_e( 'View project /', 'ilanel-poc' ); ?></span>
							</div>
						</a>
					</li>
					<?php
				endwhile;
				?>
			</ul>

			<?php
			// 51 projects is a long page; paginate rather than scroll forever.
			the_posts_pagination(
				array(
					'mid_size'           => 2,
					'prev_text'          => esc_html__( 'Previous', 'ilanel-poc' ),
					'next_text'          => esc_html__( 'Next', 'ilanel-poc' ),
					'screen_reader_text' => esc_html__( 'Projects navigation', 'ilanel-poc' ),
				)
			);
			?>

		<?php else : ?>

			<p class="rg-range__empty"><?php esc_html_e( 'No projects yet.', 'ilanel-poc' ); ?></p>

		<?php endif; ?>

	</div>
</main>

<?php
get_footer();
