<?php
/**
 * Một thẻ trong lưới sản phẩm của WooCommerce (vòng lặp danh sách, sản phẩm cùng loại, shortcode).
 *
 * @package TMT_Shop
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;
if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}
?>
<li <?php wc_product_class( 'sf-grid-item', $product ); ?>><?php tmt_product_card( $product ); ?></li>
