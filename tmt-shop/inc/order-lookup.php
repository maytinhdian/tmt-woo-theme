<?php
/**
 * Tra đơn hàng không cần đăng nhập: mã ngắn [tmt_tra_don] (trang "Tra đơn hàng" tự tạo khi kích hoạt theme).
 * Khách nhập số đơn + số điện thoại đặt hàng; đúng cả hai mới hiện đơn (tình trạng, món, tổng tiền, mã QR nếu đang chờ chuyển khoản).
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;

/** Tên tình trạng đơn dễ hiểu cho khách. */
function tmt_order_status_label( WC_Order $order ) {
	$map = array(
		'pending'    => array( 'Chờ thanh toán', 'warning' ),
		'on-hold'    => array( 'Chờ tiệm xác nhận', 'warning' ),
		'processing' => array( 'Đang chuẩn bị hàng', 'info' ),
		'completed'  => array( 'Đã giao', 'success' ),
		'cancelled'  => array( 'Đã hủy', 'danger' ),
		'refunded'   => array( 'Đã hoàn tiền', 'neutral' ),
		'failed'     => array( 'Thanh toán chưa thành công', 'danger' ),
	);
	$s = $order->get_status();
	return isset( $map[ $s ] ) ? $map[ $s ] : array( wc_get_order_status_name( $s ), 'neutral' );
}

/** Tạo trang "Tra đơn hàng" một lần (khi kích hoạt theme), lưu id để chân trang / thanh trên cùng trỏ tới. */
function tmt_lookup_page_id() {
	$id = (int) get_option( 'tmt_lookup_page_id' );
	return $id && 'publish' === get_post_status( $id ) ? $id : 0;
}
add_action(
	'after_switch_theme',
	function () {
		if ( tmt_lookup_page_id() ) {
			return;
		}
		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Tra đơn hàng',
				'post_name'    => 'tra-don-hang',
				'post_content' => '<!-- wp:shortcode -->[tmt_tra_don]<!-- /wp:shortcode -->',
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			update_option( 'tmt_lookup_page_id', $id );
		}
	}
);

/**
 * Tìm đơn theo số đơn + SĐT. Trả về đơn, hoặc chuỗi lỗi.
 *
 * @return WC_Order|string
 */
function tmt_lookup_order( $number, $phone ) {
	$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$key   = 'tmt_lookup_' . md5( $ip );
	$tries = (int) get_transient( $key );
	if ( $tries >= 10 ) {
		return 'Tra quá nhiều lần. Đợi 15 phút rồi thử lại, hoặc gọi tiệm.';
	}
	set_transient( $key, $tries + 1, 15 * MINUTE_IN_SECONDS );

	$digits = preg_replace( '/\D/', '', (string) $phone );
	$id     = absint( apply_filters( 'woocommerce_shortcode_order_tracking_order_id', ltrim( preg_replace( '/[^0-9]/', '', (string) $number ), '0' ) ) );
	$order  = $id ? wc_get_order( $id ) : false;
	$own    = $order instanceof WC_Order && ! $order instanceof WC_Order_Refund ? preg_replace( '/\D/', '', $order->get_billing_phone() ) : '';
	// So 9 số cuối: khách gõ 0393... hay +84393... đều khớp.
	if ( strlen( $digits ) < 9 || '' === $own || substr( $own, -9 ) !== substr( $digits, -9 ) ) {
		return 'Không tìm thấy đơn với số đơn và số điện thoại này. Kiểm tra lại số đơn trong email hoặc tin nhắn xác nhận.';
	}
	return $order;
}

add_shortcode(
	'tmt_tra_don',
	function () {
		$number = isset( $_POST['so_don'] ) ? sanitize_text_field( wp_unslash( $_POST['so_don'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$phone  = isset( $_POST['sdt'] ) ? sanitize_text_field( wp_unslash( $_POST['sdt'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$result = null;
		if ( isset( $_POST['tmt_tra_don'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$result = isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'tmt_tra_don' ) ? tmt_lookup_order( $number, $phone ) : 'Phiên làm việc đã hết hạn, bấm Tra lại.';
		}
		ob_start();
		get_template_part( 'template-parts/order/lookup', null, array( 'number' => $number, 'phone' => $phone, 'result' => $result ) );
		return ob_get_clean();
	}
);
