<?php
/**
 * Single project — the other half of the cross-link.
 *
 * A project page names the pieces installed in it, and each product page names
 * the projects it appears in. The two directions read the same relation from
 * opposite ends, so they cannot fall out of step.
 *
 * Follows the RG article anatomy already used by the product template: hero,
 * then a copy/media row, then the related items.
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$ilanel_project_id = get_the_ID();

	$ilanel_products = class_exists( 'ILANEL_Projects' )
		? ILANEL_Projects::get_products_for_project( $ilanel_project_id )
		: array();

	$ilanel_gallery = get_post_meta( $ilanel_project_id, '_ilanel_project_gallery', true );
	$ilanel_gallery = is_array( $ilanel_gallery ) ? $ilanel_gallery : array();

	$ilanel_hero = get_the_post_thumbnail_url( $ilanel_project_id, 'full' );
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
						<li><a href="<?php echo esc_url( get_post_type_archive_link( 'project' ) ); ?>"><?php esc_html_e( 'Projects', 'ilanel-poc' ); ?></a></li>
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
						<p class="rg-product__type"><?php esc_html_e( 'Project', 'ilanel-poc' ); ?></p>
					</header>
				</div>

				<div class="rg-article__col rg-article__col--content">
					<div class="rg-article__content">
						<?php the_content(); ?>
					</div>
				</div>
			</div>
		</article>

		<?php // 3. Lighting used — the reverse of the product page's "Featured in". ?>
		<?php if ( $ilanel_products ) : ?>
			<section class="rg-section rg-section--used">
				<div class="rg-shell">
					<span class="rg-section__label"><?php esc_html_e( 'Lighting used /', 'ilanel-poc' ); ?></span>

					<h2 class="rg-section__heading"><?php esc_html_e( 'Pieces in this project', 'ilanel-poc' ); ?></h2>

					<ul class="rg-products rg-products--used">
						<?php foreach ( $ilanel_products as $ilanel_product ) : ?>
							<?php $ilanel_type = get_post_meta( $ilanel_product->get_id(), '_ilanel_product_type_label', true ); ?>
							<li class="rg-product-card">
								<a href="<?php echo esc_url( $ilanel_product->get_permalink() ); ?>">
									<div class="rg-product-card__media">
										<?php echo wp_kses_post( $ilanel_product->get_image( 'large' ) ); ?>
									</div>

									<h3 class="rg-product-card__title"><?php echo esc_html( $ilanel_product->get_name() ); ?></h3>

									<?php if ( $ilanel_type ) : ?>
										<p class="rg-product-card__type"><?php echo esc_html( $ilanel_type ); ?></p>
									<?php endif; ?>

									<?php
									// get_price_html(), not get_price(): these are
									// variable products and get_price() returns only
									// the minimum, losing the range.
									$ilanel_price_html = $ilanel_product->get_price_html();
									?>
									<?php if ( $ilanel_price_html ) : ?>
										<p class="rg-product-card__price"><?php echo wp_kses_post( $ilanel_price_html ); ?></p>
									<?php endif; ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</section>
		<?php endif; ?>

		<?php // 4. Further photography from the install. ?>
		<?php if ( $ilanel_gallery ) : ?>
			<section class="rg-section rg-section--project-gallery">
				<div class="rg-shell">
					<ul class="rg-project-gallery">
						<?php foreach ( $ilanel_gallery as $ilanel_index => $ilanel_src ) : ?>
							<li>
								<img src="<?php echo esc_url( $ilanel_src ); ?>"
									alt="<?php
									/* translators: 1: project name, 2: image number */
									printf( esc_attr__( '%1$s — installation photograph %2$d', 'ilanel-poc' ), esc_attr( get_the_title() ), absint( $ilanel_index ) + 2 );
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
