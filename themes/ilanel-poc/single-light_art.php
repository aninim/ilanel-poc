<?php
/**
 * Single Light Art work — exhibitions and commissions.
 *
 * Shares the project anatomy (hero, copy/media row, gallery) because the
 * content shape is the same. It deliberately has NO product relation: light
 * art is exhibition and commission work — Melbourne Design Week, Goldstone
 * Gallery, City of Melbourne — not something assembled from catalogue pieces,
 * so there is no "Lighting used" section to render.
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
	?>

	<?php // 1. Hero — the installation photograph. ?>
	<?php if ( $ilanel_hero ) : ?>
		<section class="rg-hero rg-hero--project" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
			<div class="rg-hero__slide is-active"
				style="background-image:url('<?php echo esc_url( $ilanel_hero ); ?>')"
				role="img"
				aria-label="<?php echo esc_attr( get_the_title() ); ?>"></div>
		</section>
	<?php endif; ?>

	<main id="main" class="rg-main rg-main--project">

		<div class="rg-breadcrumbs">
			<div class="rg-shell">
				<nav class="ilanel-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'ilanel-poc' ); ?>">
					<ol>
						<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'ilanel-poc' ); ?></a></li>
						<li><a href="<?php echo esc_url( get_post_type_archive_link( 'light_art' ) ); ?>"><?php esc_html_e( 'Light Art', 'ilanel-poc' ); ?></a></li>
						<li><span aria-current="page"><?php the_title(); ?></span></li>
					</ol>
				</nav>
			</div>
		</div>

		<?php // 2. Title and copy. ?>
		<article class="rg-article rg-shell">
			<div class="rg-article__row">
				<div class="rg-article__col rg-article__col--head">
					<header class="rg-article__head">
						<h1 class="rg-product__name"><?php the_title(); ?></h1>
						<p class="rg-product__type"><?php esc_html_e( 'Light Art', 'ilanel-poc' ); ?></p>
					</header>
				</div>

				<div class="rg-article__col rg-article__col--content">
					<div class="rg-article__content">
						<?php the_content(); ?>
					</div>
				</div>
			</div>
		</article>

		<?php // 3. Further photography from the exhibition. ?>
		<?php if ( $ilanel_gallery ) : ?>
			<section class="rg-section rg-section--project-gallery">
				<div class="rg-shell">
					<ul class="rg-project-gallery">
						<?php foreach ( $ilanel_gallery as $ilanel_index => $ilanel_src ) : ?>
							<li>
								<img src="<?php echo esc_url( $ilanel_src ); ?>"
									alt="<?php
									/* translators: 1: work name, 2: image number */
									printf( esc_attr__( '%1$s — exhibition photograph %2$d', 'ilanel-poc' ), esc_attr( get_the_title() ), absint( $ilanel_index ) + 2 );
									?>"
									loading="lazy" />
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
