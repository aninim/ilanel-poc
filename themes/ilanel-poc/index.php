<?php
/**
 * Fallback template.
 *
 * The POC only exercises product and range pages; this exists because
 * WordPress requires it.
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="main" class="ilanel-main">
	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : ?>
			<?php the_post(); ?>
			<article <?php post_class(); ?>>
				<h1><?php the_title(); ?></h1>
				<?php the_content(); ?>
			</article>
		<?php endwhile; ?>
	<?php else : ?>
		<p><?php esc_html_e( 'Nothing found.', 'ilanel-poc' ); ?></p>
	<?php endif; ?>
</main>

<?php
get_footer();
