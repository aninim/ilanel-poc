<?php
/**
 * Journal (news) listing — /news/.
 *
 * WordPress's template hierarchy routes the native post archive here
 * (home.php), not to an archive-*.php file — there is no custom post type
 * for journal entries; see class-ilanel-journal.php for why native `post`
 * was reused instead of a fourth CPT, and for the /news/ permalink rewrite
 * this page depends on.
 *
 * Layout follows archive-project.php's pattern (large featured entry, then
 * a uniform grid, "Load more" rather than numbered pagination) — the same
 * Ross Gardam Journal structure this whole site borrows its page anatomy
 * from, applied here to its most literal match: an actual editorial index.
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="main" class="rg-main rg-main--archive rg-main--journal">
	<div class="rg-shell">

		<header class="rg-journal__header">
			<h1 class="rg-journal__title"><?php esc_html_e( 'News', 'ilanel-poc' ); ?></h1>
		</header>

		<?php if ( have_posts() ) : ?>

			<?php
			the_post();
			$ilanel_featured_excerpt = wp_trim_words( wp_strip_all_tags( get_the_content() ), 24, '…' );
			?>

			<section class="rg-journal__featured">
				<a href="<?php the_permalink(); ?>">
					<span class="rg-journal__featured-label"><?php esc_html_e( 'Latest /', 'ilanel-poc' ); ?></span>

					<div class="rg-journal__featured-media">
						<?php
						if ( has_post_thumbnail() ) {
							the_post_thumbnail( 'large' );
						}
						?>
					</div>

					<div class="rg-journal__featured-copy">
						<h2><?php the_title(); ?></h2>
						<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
						<span class="rg-index__more"><?php esc_html_e( 'Read more /', 'ilanel-poc' ); ?></span>
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

							<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>" class="rg-journal__tile-date"><?php echo esc_html( get_the_date() ); ?></time>
							<h3 class="rg-journal__tile-title"><?php the_title(); ?></h3>
							<p class="rg-journal__synopsis"><?php echo esc_html( $ilanel_excerpt ); ?></p>
							<span class="rg-index__more rg-index__more--static"><?php esc_html_e( 'Read more /', 'ilanel-poc' ); ?></span>
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

			<p class="rg-range__empty"><?php esc_html_e( 'No journal entries yet.', 'ilanel-poc' ); ?></p>

		<?php endif; ?>

	</div>
</main>

<?php
get_footer();
