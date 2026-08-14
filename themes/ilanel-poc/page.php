<?php
/**
 * Static content page template — About, FAQ, Trade, Warranty, Privacy
 * Policy, Terms & Conditions, Contact (Phase 3a of docs/LAUNCH-PLAN.md).
 *
 * A centred prose column, not the alternating story-row layout used for
 * projects/journal — these are reference/legal pages, not editorial ones.
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="main" class="rg-page">
	<div class="rg-shell rg-page__shell">
		<?php while ( have_posts() ) : ?>
			<?php the_post(); ?>
			<article <?php post_class( 'rg-page__article' ); ?>>
				<h1 class="rg-page__title"><?php the_title(); ?></h1>
				<div class="rg-page__content">
					<?php the_content(); ?>
				</div>
			</article>
		<?php endwhile; ?>
	</div>
</main>

<?php
get_footer();
