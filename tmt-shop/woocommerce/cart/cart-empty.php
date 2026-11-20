<?php
/**
 * Giỏ trống kiểu C: hình giỏ, nút xem sản phẩm / lên cấu hình / Zalo, danh mục, hàng đang giảm giá.
 *
 * @package TMT_Shop
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;

$tmt_zalo    = tmt_zalo_url();
$tmt_builder = tmt_builder_url();
$tmt_cats    = array_slice( tmt_cat_tree()['tops'], 0, 6 );
$tmt_sale    = wc_get_products( array( 'include' => wc_get_product_ids_on_sale() ?: array( 0 ), 'status' => 'publish', 'visibility' => 'catalog', 'limit' => 5, 'type' => array( 'simple', 'variable' ) ) );
do_action( 'woocommerce_cart_is_empty' );
?>
<div class="sfc">
	<ol class="sfc-steps is-mute" aria-label="Các bước đặt hàng">
		<li class="on"><span>1</span><b>Giỏ hàng</b></li><li><span>2</span><b>Thông tin nhận hàng</b></li><li><span>3</span><b>Thanh toán</b></li>
	</ol>
	<div class="sfc-empty cart-empty">
		<div class="sfc-empty-art" aria-hidden="true">
			<div class="sfc-empty-cart"><i class="fa-solid fa-cart-shopping"></i></div>
			<span class="z1"><i class="fa-solid fa-laptop"></i></span><span class="z2"><i class="fa-solid fa-memory"></i></span><span class="z3"><i class="fa-solid fa-computer-mouse"></i></span>
		</div>
		<h1>Giỏ hàng đang trống</h1>
		<p>Chưa có sản phẩm nào. Xem hàng đang giảm giá<?php echo $tmt_builder ? ', hoặc tự lên cấu hình máy theo ngân sách' : ''; ?>.</p>
		<div class="sfc-empty-btns">
			<a href="<?php echo esc_url( tmt_shop_url() ); ?>" class="sfc-btn y"><i class="fa-solid fa-bag-shopping" aria-hidden="true"></i>Xem sản phẩm</a>
			<?php if ( $tmt_builder ) : ?><a href="<?php echo esc_url( $tmt_builder ); ?>" class="sfc-btn o"><i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i>Xây dựng cấu hình</a><?php endif; ?>
			<?php if ( $tmt_zalo ) : ?><a href="<?php echo esc_url( $tmt_zalo ); ?>" target="_blank" rel="noopener" class="sfc-btn zalo"><span class="sfc-zl">Zalo</span>Nhờ tiệm tư vấn</a><?php endif; ?>
		</div>
		<?php if ( $tmt_cats ) : ?>
			<div class="sfc-empty-cats">
				<?php foreach ( $tmt_cats as $tmt_c ) : ?><a href="<?php echo esc_url( get_term_link( $tmt_c ) ); ?>"><?php echo tmt_icon( tmt_category_icon( $tmt_c ) ); // phpcs:ignore ?><?php echo esc_html( $tmt_c->name ); ?></a><?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php if ( $tmt_sale ) : ?>
		<section class="sfc-acc mt-4">
			<div class="sfc-acc-h"><h2><i class="fa-solid fa-tags" aria-hidden="true"></i> Đang giảm giá</h2><a href="<?php echo esc_url( tmt_shop_url() ); ?>">Xem thêm <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a></div>
			<div class="sfc-acc-g is-5"><?php foreach ( $tmt_sale as $tmt_p ) : ?><div><?php tmt_product_card( $tmt_p ); ?></div><?php endforeach; ?></div>
		</section>
	<?php endif; ?>
</div>
