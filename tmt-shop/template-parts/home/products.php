<?php
/**
 * Sản phẩm mới về: lưới thẻ sản phẩm (mới nhất trước).
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;
if ( ! function_exists( 'wc_get_products' ) ) {
	return;
}
$products = wc_get_products(
	array(
		'status'     => 'publish',
		'visibility' => 'catalog',
		'limit'      => max( 4, min( 24, (int) tmt_opt( 'home.products_count' ) ) ),
		'orderby'    => 'date',
		'order'      => 'DESC',
	)
);
if ( ! $products ) {
	return;
}
?>
<section class="sf-hs alt">
	<div class="sf-wrap">
		<div class="sf-sh">
			<h2><?php echo esc_html( tmt_opt( 'home.products_title' ) ); ?></h2>
			<a href="<?php echo esc_url( tmt_shop_url() ); ?>" class="more">Xem tất cả<i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
		</div>
		<ul class="products sf-grid">
			<?php foreach ( $products as $p ) : ?><li class="product"><?php tmt_product_card( $p ); ?></li><?php endforeach; ?>
		</ul>
	</div>
</section>
