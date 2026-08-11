<?php
/**
 * Single product title — the page's ONE and ONLY h1.
 *
 * Ross Gardam's product page leads with the piece name as a large light
 * serif, with an uppercase tracked type qualifier beneath ("Liminal" /
 * "LINEAR PENDANT"). We mirror that pairing exactly.
 *
 * Live ilanel.com carries 2–4 h1 tags on 79 pages because section headers
 * were formatted as Heading 1. Here the product name is the only h1 by
 * construction; every other heading in this theme is h2 or lower.
 *
 * Override of woocommerce/templates/single-product/title.php
 *
 * @package ILANEL_POC
 */

defined( 'ABSPATH' ) || exit;

global $product;

$ilanel_type = $product instanceof WC_Product
	? get_post_meta( $product->get_id(), '_ilanel_product_type_label', true )
	: '';
?>

<div class="rg-article__head">
	<h1 class="rg-product__name"><?php the_title(); ?></h1>

	<?php if ( $ilanel_type ) : ?>
		<p class="rg-product__type"><?php echo esc_html( $ilanel_type ); ?></p>
	<?php endif; ?>
</div>
