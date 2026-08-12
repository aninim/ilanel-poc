<?php
/**
 * Editions range archive.
 *
 * Editions are not catalogue stock. They are limited runs and one-offs —
 * Ripple, Droplet, Atlas, Matariki, Cannon Vase 1st and 2nd Edition — so the
 * page is framed as a collection to view rather than a range to specify from.
 *
 * WordPress resolves taxonomy-{taxonomy}-{term}.php before
 * taxonomy-{taxonomy}.php, so this takes over for /our-range/editions/ only
 * and every other range keeps the standard archive.
 *
 * Visually it borrows the editorial index used by projects and light art
 * rather than the product grid: these pieces are shown, not compared on price.
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

$ilanel_term  = get_queried_object();
$ilanel_total = (int) $GLOBALS['wp_query']->found_posts;
?>

<main id="main" class="rg-main rg-main--archive rg-main--editions">
	<div class="rg-shell">

		<div class="rg-breadcrumbs rg-breadcrumbs--inline">
			<?php ILANEL_Breadcrumbs::render(); ?>
		</div>

		<header class="rg-index__header">
			<span class="rg-section__label"><?php esc_html_e( 'Editions /', 'ilanel-poc' ); ?></span>

			<h1 class="rg-index__title"><?php esc_html_e( 'Limited and one-off works', 'ilanel-poc' ); ?></h1>

			<p class="rg-index__intro">
				<?php
				printf(
					/* translators: %d: number of editions */
					esc_html__( '%d pieces made in small numbers — sculptural experiments, collaborations and short runs from the Melbourne studio. Each is enquired for rather than ordered from stock.', 'ilanel-poc' ),
					absint( $ilanel_total )
				);
				?>
			</p>
		</header>

		<?php if ( have_posts() ) : ?>

			<ul class="rg-index rg-index--editions">
				<?php
				while ( have_posts() ) :
					the_post();

					global $product;

					$ilanel_type = get_post_meta( get_the_ID(), '_ilanel_product_type_label', true );
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
									<h2 class="rg-index__name"><?php the_title(); ?></h2>

									<?php if ( $ilanel_type ) : ?>
										<p class="rg-index__type"><?php echo esc_html( $ilanel_type ); ?></p>
									<?php endif; ?>
								</div>

								<span class="rg-index__more"><?php esc_html_e( 'Enquire /', 'ilanel-poc' ); ?></span>
							</div>
						</a>
					</li>
					<?php
				endwhile;
				?>
			</ul>

		<?php else : ?>

			<p class="rg-range__empty"><?php esc_html_e( 'No editions listed yet.', 'ilanel-poc' ); ?></p>

		<?php endif; ?>

	</div>
</main>

<?php
get_footer( 'shop' );
