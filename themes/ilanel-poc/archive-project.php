<?php
/**
 * Projects archive.
 *
 * Rebuilt against a measured reference rather than invention. Ross Gardam has
 * no dedicated Projects section — confirmed 2026-08-12: /projects/ 404s,
 * /journal/projects/ redirects to the unfiltered index, and their sitemap
 * lists no project archive. Their installation work is published inside the
 * Journal, and that page's structure is what this template follows:
 *
 *   - one large FEATURED item, a 5:3 background crop, set apart above a rule
 *   - a uniform 3-up grid beneath it (2-up at ~1024px, 1-up mobile)
 *   - grid cards carry SYNOPSIS COPY, not a title — RG's ordinary tiles have
 *     no distinct name/location/year field, only body text and an
 *     "EXPLORE PROJECT /" link
 *   - "LOAD MORE" rather than numbered pagination
 *
 * See docs/reference/PROJECTS-DESIGN-SPEC-2026-08-12.md for the full
 * measurement set (column widths, gaps, hover behaviour) this was built from.
 *
 * ILANEL's own project pages don't have synopsis blurbs distinct from body
 * copy, so the excerpt is taken from the first ~140 characters of post
 * content — the same lede the single template uses as its standfirst.
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ilanel_total = (int) $GLOBALS['wp_query']->found_posts;
?>

<main id="main" class="rg-main rg-main--archive rg-main--journal">
	<div class="rg-shell">

		<header class="rg-journal__header">
			<h1 class="rg-journal__title"><?php esc_html_e( 'Projects', 'ilanel-poc' ); ?></h1>
		</header>

		<?php if ( have_posts() ) : ?>

			<?php
			the_post();
			$ilanel_featured_excerpt = wp_trim_words( wp_strip_all_tags( get_the_content() ), 24, '…' );
			?>

			<section class="rg-journal__featured">
				<a href="<?php the_permalink(); ?>">
					<span class="rg-journal__featured-label"><?php esc_html_e( 'Featured project /', 'ilanel-poc' ); ?></span>

					<div class="rg-journal__featured-media">
						<?php
						if ( has_post_thumbnail() ) {
							the_post_thumbnail( 'large' );
						}
						?>
					</div>

					<div class="rg-journal__featured-copy">
						<h2><?php the_title(); ?></h2>
						<span class="rg-index__more"><?php esc_html_e( 'Explore project /', 'ilanel-poc' ); ?></span>
					</div>
				</a>
			</section>

			<ul class="rg-journal__grid">
				<?php
				while ( have_posts() ) :
					the_post();
					$ilanel_excerpt = wp_trim_words( wp_strip_all_tags( get_the_content() ), 26, '…' );
					?>
					<li class="rg-journal__tile">
						<a href="<?php the_permalink(); ?>">
							<div class="rg-journal__tile-media">
								<?php
								if ( has_post_thumbnail() ) {
									the_post_thumbnail( 'medium_large' );
								}
								?>
							</div>

							<p class="rg-journal__synopsis"><?php echo esc_html( $ilanel_excerpt ); ?></p>
							<span class="rg-index__more rg-index__more--static"><?php esc_html_e( 'Explore project /', 'ilanel-poc' ); ?></span>
						</a>
					</li>
					<?php
				endwhile;
				?>
			</ul>

			<?php
			$ilanel_next = get_next_posts_link( esc_html__( 'Load more', 'ilanel-poc' ) );

			if ( $ilanel_next ) :
				?>
				<div class="rg-journal__more"><?php echo wp_kses_post( $ilanel_next ); ?></div>
				<?php
			endif;
			?>

		<?php else : ?>

			<p class="rg-range__empty"><?php esc_html_e( 'No projects yet.', 'ilanel-poc' ); ?></p>

		<?php endif; ?>

	</div>
</main>

<?php
get_footer();
