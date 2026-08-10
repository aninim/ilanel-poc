<?php
/**
 * Single product title — the page's ONE and ONLY h1.
 *
 * Live ilanel.com carries 2–4 h1 tags on 79 pages because section headers
 * ("Related Products", "Featured Projects", "ColoUr Disclaimer") were
 * formatted as Heading 1. Here the product name is the only h1 by
 * construction; every other heading in this theme is h2 or lower, so the
 * defect cannot recur through content entry.
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

<h1 class="product_title entry-title ilanel-product-title">
	<?php the_title(); ?>
	<?php if ( $ilanel_type ) : ?>
		<span class="ilanel-product-title__type"><?php echo esc_html( $ilanel_type ); ?></span>
	<?php endif; ?>
</h1>
