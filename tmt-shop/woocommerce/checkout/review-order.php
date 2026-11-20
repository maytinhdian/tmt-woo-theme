<?php
/**
 * Các dòng tiền trong khung tổng xanh đậm (WooCommerce tự thay khối này khi đổi cách nhận hàng, mã giảm giá).
 * Tạm tính theo giá gốc, giảm giá sản phẩm, mã giảm giá, phụ phí, phí giao, thuế (nếu hiện riêng), tổng cộng + tiết kiệm.
 *
 * @package TMT_Shop
 * @version 5.2.0
 */

defined( 'ABSPATH' ) || exit;

$tmt_cart    = WC()->cart;
$tmt_savings = tmt_cart_savings();
$tmt_fee     = tmt_shipping_fee_text();
$tmt_saved   = $tmt_savings + $tmt_cart->get_discount_total();
foreach ( $tmt_cart->get_fees() as $tmt_f ) {
	$tmt_saved += $tmt_f->total < 0 ? -1 * $tmt_f->total : 0; // Phí âm = giảm combo (plugin).
}
?>
<div class="woocommerce-checkout-review-order-table sfc-sum">
	<?php do_action( 'woocommerce_review_order_before_cart_contents' ); ?>
	<?php do_action( 'woocommerce_review_order_after_cart_contents' ); ?>
	<div class="sfc-sr"><span>Tạm tính (<?php echo (int) $tmt_cart->get_cart_contents_count(); ?> sản phẩm)</span><b><?php echo wp_kses_post( wc_price( $tmt_cart->get_displayed_subtotal() + $tmt_savings ) ); ?></b></div>
	<?php if ( $tmt_savings > 0 ) : ?>
		<div class="sfc-sr is-save"><span><i class="fa-solid fa-piggy-bank" aria-hidden="true"></i>Giảm giá sản phẩm</span><b>−<?php echo wp_kses_post( wc_price( $tmt_savings ) ); ?></b></div>
	<?php endif; ?>
	<?php foreach ( $tmt_cart->get_coupons() as $tmt_code => $tmt_coupon ) : ?>
		<div class="sfc-sr is-save"><span><i class="fa-solid fa-ticket" aria-hidden="true"></i>Mã <?php echo esc_html( strtoupper( $tmt_code ) ); ?></span><b>−<?php echo wp_kses_post( wc_price( $tmt_cart->get_coupon_discount_amount( $tmt_code, $tmt_cart->display_cart_ex_tax ) ) ); ?></b></div>
	<?php endforeach; ?>
	<?php foreach ( $tmt_cart->get_fees() as $tmt_f ) : ?>
		<div class="sfc-sr<?php echo $tmt_f->total < 0 ? ' is-save' : ''; ?>"><span><?php if ( $tmt_f->total < 0 ) : ?><i class="fa-solid fa-layer-group" aria-hidden="true"></i><?php endif; ?><?php echo esc_html( $tmt_f->name ); ?></span><b><?php wc_cart_totals_fee_html( $tmt_f ); ?></b></div>
	<?php endforeach; ?>
	<?php do_action( 'woocommerce_review_order_before_shipping' ); ?>
	<?php if ( $tmt_cart->needs_shipping() ) : ?>
		<div class="sfc-sr"><span>Phí giao hàng</span><b<?php echo $tmt_fee[1] ? ' class="is-free"' : ''; ?>><?php echo wp_kses_post( $tmt_fee[0] ); ?></b></div>
	<?php endif; ?>
	<?php do_action( 'woocommerce_review_order_after_shipping' ); ?>
	<?php if ( wc_tax_enabled() && ! $tmt_cart->display_prices_including_tax() ) : ?>
		<?php foreach ( $tmt_cart->get_tax_totals() as $tmt_tax ) : ?>
			<div class="sfc-sr"><span><?php echo esc_html( $tmt_tax->label ); ?></span><b><?php echo wp_kses_post( $tmt_tax->formatted_amount ); ?></b></div>
		<?php endforeach; ?>
	<?php endif; ?>
	<?php do_action( 'woocommerce_review_order_before_order_total' ); ?>
	<div class="sfc-tot"><span>Tổng cộng</span><div><b><?php echo wp_kses_post( wc_price( $tmt_cart->get_total( 'edit' ) ) ); ?></b><?php if ( $tmt_saved > 0 ) : ?><em>Tiết kiệm <?php echo wp_kses_post( wc_price( $tmt_saved ) ); ?></em><?php endif; ?></div></div>
	<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>
</div>
