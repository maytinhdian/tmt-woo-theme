<?php
/**
 * Ô tra đơn (số đơn + SĐT) và kết quả: tình trạng, các món, tổng tiền, mã QR nếu đơn đang chờ chuyển khoản.
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;

$tmt_result = $args['result'];
$tmt_order  = $tmt_result instanceof WC_Order ? $tmt_result : null;
?>
<div class="sfl">
	<form method="post" class="sfl-form" action="<?php echo esc_url( get_permalink() ); ?>">
		<?php wp_nonce_field( 'tmt_tra_don' ); ?>
		<input type="hidden" name="tmt_tra_don" value="1">
		<label class="sfc-fl"><span>Số đơn hàng</span><span class="sfc-in"><i class="fa-solid fa-hashtag" aria-hidden="true"></i><input name="so_don" value="<?php echo esc_attr( $args['number'] ); ?>" required inputmode="numeric" maxlength="20" placeholder="Ví dụ 1024"></span></label>
		<label class="sfc-fl"><span>Số điện thoại đặt hàng</span><span class="sfc-in"><i class="fa-solid fa-phone" aria-hidden="true"></i><input name="sdt" value="<?php echo esc_attr( $args['phone'] ); ?>" required inputmode="tel" autocomplete="tel" maxlength="20"></span></label>
		<button type="submit" class="sfc-btn y"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>Tra đơn</button>
	</form>

	<?php if ( is_string( $tmt_result ) ) : ?>
		<p class="sfl-err" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><?php echo esc_html( $tmt_result ); ?></p>
	<?php endif; ?>

	<?php if ( $tmt_order ) : ?>
		<?php [ $tmt_label, $tmt_tone ] = tmt_order_status_label( $tmt_order ); ?>
		<section class="sfl-order" aria-label="Đơn hàng">
			<div class="sfl-head">
				<div><small>Đơn hàng</small><b>#<?php echo esc_html( $tmt_order->get_order_number() ); ?></b></div>
				<div><small>Ngày đặt</small><b><?php echo esc_html( wc_format_datetime( $tmt_order->get_date_created(), 'd/m/Y H:i' ) ); ?></b></div>
				<span class="sfl-st t-<?php echo esc_attr( $tmt_tone ); ?>"><?php echo esc_html( $tmt_label ); ?></span>
			</div>
			<ul class="sfl-items">
				<?php foreach ( $tmt_order->get_items() as $tmt_item ) : ?>
					<li><span><?php echo esc_html( $tmt_item->get_name() ); ?> <small>× <?php echo (int) $tmt_item->get_quantity(); ?></small></span><b><?php echo wp_kses_post( $tmt_order->get_formatted_line_subtotal( $tmt_item ) ); ?></b></li>
				<?php endforeach; ?>
			</ul>
			<dl class="sfl-sum">
				<?php foreach ( $tmt_order->get_order_item_totals() as $tmt_k => $tmt_row ) : ?>
					<div class="<?php echo 'order_total' === $tmt_k ? 'is-total' : ''; ?>"><dt><?php echo esc_html( rtrim( wp_strip_all_tags( $tmt_row['label'] ), ':' ) ); ?></dt><dd><?php echo wp_kses_post( $tmt_row['value'] ); ?></dd></div>
				<?php endforeach; ?>
			</dl>
			<?php tmt_order_qr_box( $tmt_order ); ?>
		</section>
	<?php endif; ?>
</div>
