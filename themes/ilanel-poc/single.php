<?php
/**
 * Single journal (news) post.
 *
 * Native `post` type — see class-ilanel-journal.php for why, and for the
 * /news/ permalink rewrite this page depends on.
 *
 * Structurally a lighter version of single-project.php's pattern (title
 * above a contained hero, breadcrumb, alternating story rows built from
 * paragraph + gallery image pairs) — a journal entry has no credits,
 * products or "lighting used" section, just editorial copy and photography.
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$ilanel_post_id = get_the_ID();

	$ilanel_gallery = get_post_meta( $ilanel_post_id, '_ilanel_project_gallery', true );
	$ilanel_gallery = is_array( $ilanel_gallery ) ? $ilanel_gallery : array();

	$ilanel_hero = get_the_post_thumbnail_url( $ilanel_post_id, 'large' );

	$ilanel_content = apply_filters( 'the_content', get_the_content() );
	$ilanel_paras   = array_values( array_filter( preg_split( '#(?=<p)#', $ilanel_content ) ) );
	?>

	<main id="main" class="rg-main rg-main--project rg-main--journal">

		<div class="rg-breadcrumbs">
			<div class="rg-shell">
				<nav class="ilanel-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'ilanel-poc' ); ?>">
					<ol>
						<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'ilanel-poc' ); ?></a></li>
						<li><a href="<?php echo esc_url( home_url( '/news/' ) ); ?>"><?php esc_html_e( 'Journal', 'ilanel-poc' ); ?></a></li>
						<li><span aria-current="page"><?php the_title(); ?></span></li>
					</ol>
				</nav>
			</div>
		</div>

		<header class="rg-project__head">
			<div class="rg-shell">
				<h1 class="rg-product__name"><?php the_title(); ?></h1>
				<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>" class="rg-journal__single-date"><?php echo esc_html( get_the_date() ); ?></time>
			</div>
		</header>

		<?php if ( $ilanel_hero ) : ?>
			<link rel="preload" as="image" fetchpriority="high" href="<?php echo esc_url( $ilanel_hero ); ?>">

			<div class="rg-shell">
				<figure class="rg-project__hero-figure">
					<img src="<?php echo esc_url( $ilanel_hero ); ?>"
						alt="<?php echo esc_attr( get_the_title() ); ?>"
						fetchpriority="high" />
				</figure>
			</div>
		<?php endif; ?>

		<?php
		/*
		 * Story: alternating rows, same device single-project.php uses for
		 * project installations — paragraph paired with a gallery image,
		 * flipping text-left/text-right so the page doesn't read as a
		 * uniform stack.
		 */
		?>
		<div class="rg-project__story">
			<?php
			$ilanel_row = 0;

			foreach ( $ilanel_paras as $ilanel_i => $ilanel_para ) :
				if ( ! trim( wp_strip_all_tags( $ilanel_para ) ) ) {
					continue;
				}

				$ilanel_img  = isset( $ilanel_gallery[ $ilanel_i ] ) ? $ilanel_gallery[ $ilanel_i ] : '';
				$ilanel_flip = ( 1 === $ilanel_row % 2 );
				$ilanel_row++;
				?>
				<div class="rg-project__row<?php echo $ilanel_flip ? ' rg-project__row--reversed' : ''; ?>">
					<div class="rg-shell">
						<div class="rg-project__row-inner">
							<div class="rg-project__row-copy">
								<?php echo wp_kses_post( $ilanel_para ); ?>
							</div>

							<?php if ( $ilanel_img ) : ?>
								<div class="rg-project__row-media">
									<img src="<?php echo esc_url( $ilanel_img ); ?>"
										alt="<?php echo esc_attr( get_the_title() ); ?>"
										loading="lazy" />
								</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<?php
			endforeach;

			// Any gallery images beyond what the copy consumed still deserve a place.
			$ilanel_remaining = array_slice( $ilanel_gallery, count( $ilanel_paras ) );
			?>

			<?php foreach ( $ilanel_remaining as $ilanel_index => $ilanel_src ) : ?>
				<?php $ilanel_flip = ( 1 === $ilanel_row % 2 ); ?>
				<div class="rg-project__row rg-project__row--image-only<?php echo $ilanel_flip ? ' rg-project__row--reversed' : ''; ?>">
					<div class="rg-shell">
						<div class="rg-project__row-inner">
							<div class="rg-project__row-media">
								<img src="<?php echo esc_url( $ilanel_src ); ?>"
									alt="<?php
									/* translators: %s: post title */
									printf( esc_attr__( '%s — photograph', 'ilanel-poc' ), esc_attr( get_the_title() ) );
									?>"
									loading="lazy" />
							</div>
						</div>
					</div>
				</div>
				<?php
				$ilanel_row++;
			endforeach;
			?>
		</div>

		<?php
		// Discover more — recent journal entries, most recent first.
		$ilanel_discover = get_posts(
			array(
				'post_type'      => 'post',
				'posts_per_page' => 3,
				'post__not_in'   => array( $ilanel_post_id ),
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		?>

		<?php if ( $ilanel_discover ) : ?>
			<section class="rg-discover rg-discover--projects">
				<div class="rg-shell">
					<h2 class="rg-discover__title"><?php esc_html_e( 'More from the Journal', 'ilanel-poc' ); ?></h2>

					<ul class="rg-discover__grid">
						<?php foreach ( $ilanel_discover as $ilanel_related ) : ?>
							<li>
								<a href="<?php echo esc_url( get_permalink( $ilanel_related->ID ) ); ?>">
									<div class="rg-discover__media">
										<?php
										$ilanel_related_img = get_the_post_thumbnail_url( $ilanel_related->ID, 'medium_large' );

										if ( $ilanel_related_img ) {
											echo '<img src="' . esc_url( $ilanel_related_img ) . '" alt="' . esc_attr( get_the_title( $ilanel_related->ID ) ) . '" loading="lazy" />';
										}
										?>
									</div>
									<span class="rg-index__more rg-index__more--static">
										<?php echo esc_html( get_the_title( $ilanel_related->ID ) ); ?>
									</span>
								</a>
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
