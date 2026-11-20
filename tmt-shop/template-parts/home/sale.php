<?php
/**
 * Khuyến mãi đếm ngược: sản phẩm đang giảm giá (giá khuyến mãi của WooCommerce). Hết giờ thì ẩn (lúc tải trang và khi đang mở).
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;
if ( ! function_exists( 'wc_get_product_ids_on_sale' ) ) {
	return;
}
$ends = tmt_home_sale_ends();
if ( false === $ends ) {
	return;
}
$ids = wc_get_product_ids_on_sale();
if ( ! $ids ) {
	return;
}
$products = wc_get_products(
	array(
		'include'    => $ids,
		'status'     => 'publish',
		'visibility' => 'catalog',
		'limit'      => max( 2, min( 20, (int) tmt_opt( 'home.sale_count' ) ) ),
		'orderby'    => 'date',
		'order'      => 'DESC',
	)
);
if ( ! $products ) {
	return;
}
$title = tmt_opt( 'home.sale_title' );
$left  = $ends ? max( 0, $ends->getTimestamp() - time() ) : 0;
?>
<section class="sf-hs">
	<div class="sf-wrap">
		<div class="sf-flash">
			<div class="sf-sh">
				<h2><i class="fa-solid fa-bolt" aria-hidden="true"></i><?php echo esc_html( $title ? $title : 'Khuyến mãi' ); ?></h2>
				<?php if ( $ends ) : ?>
					<div class="sf-cd" data-countdown="<?php echo esc_attr( $ends->format( DATE_ATOM ) ); ?>" role="timer" aria-label="Thời gian còn lại">Kết thúc sau
						<b><?php echo esc_html( str_pad( (string) intdiv( $left, 3600 ), 2, '0', STR_PAD_LEFT ) ); ?></b>:<b>--</b>:<b>--</b>
					</div>
				<?php endif; ?>
				<a href="<?php echo esc_url( tmt_shop_url() ); ?>" class="more">Xem tất cả<i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
			</div>
			<div class="sf-row">
				<?php foreach ( $products as $p ) : ?><div><?php tmt_product_card( $p ); ?></div><?php endforeach; ?>
			</div>
		</div>
	</div>
</section>
