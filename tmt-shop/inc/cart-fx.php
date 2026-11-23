<?php
/**
 * Nút "Thêm vào giỏ" có hiệu ứng xe đẩy đón túi hàng (port từ app: partials/atc-button, storefront-cart-fx.js).
 * Tùy biến → Hiệu ứng: kiểu, màu nút, áp ở đâu, tốc độ, dòng "Trong giỏ: N món".
 * Bật thì thêm vào giỏ không tải lại trang: assets/js/cart-fx.js gửi form tới wc-ajax=get_refreshed_fragments kèm
 * add-to-cart (WooCommerce thêm giỏ ở wp_loaded rồi trả fragments), cập nhật số giỏ đầu trang.
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;

/** Kiểu hiệu ứng. */
function tmt_cart_fx_choices() {
	return array(
		'none' => 'Không có (như cũ)',
		'cart' => 'Xe đẩy đón túi hàng',
		'fly'  => 'Xe đẩy đón túi + túi bay lên giỏ đầu trang',
	);
}

/**
 * Cài đặt hiệu ứng nút giỏ đã kiểm.
 *
 * @return array{fx: string, on: bool, look: string, product: bool, card: bool, ms: int, count: bool}
 */
function tmt_cart_fx() {
	$fx    = array_key_exists( (string) tmt_opt( 'effects.cart_fx' ), tmt_cart_fx_choices() ) ? tmt_opt( 'effects.cart_fx' ) : 'none';
	$where = in_array( tmt_opt( 'effects.cart_where' ), array( 'both', 'product', 'card' ), true ) ? tmt_opt( 'effects.cart_where' ) : 'both';
	$ms    = array( 'fast' => 1400, 'normal' => 2000, 'slow' => 2600 );
	$speed = isset( $ms[ tmt_opt( 'effects.cart_speed' ) ] ) ? tmt_opt( 'effects.cart_speed' ) : 'normal';
	$on    = 'none' !== $fx;
	return array(
		'fx'      => $fx,
		'on'      => $on,
		'look'    => 'dark' === tmt_opt( 'effects.cart_look' ) ? 'dark' : 'theme',
		'product' => $on && 'card' !== $where,
		'card'    => $on && 'product' !== $where,
		'ms'      => $ms[ $speed ],
		'count'   => $on && 'card' !== $where && (bool) tmt_opt( 'effects.cart_count' ),
	);
}

/** Ruột nút (chữ + xe + túi). $label: chữ trên nút; nút nhỏ ở thẻ rút thành "Vào giỏ" khi hẹp. */
function tmt_atc_inner( $label = 'Thêm vào giỏ' ) {
	$bag  = '<svg viewBox="0 0 24 26"><path d="M8 9V6.5a4 4 0 0 1 8 0V9" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/><rect x="3" y="9" width="18" height="16" rx="3" fill="currentColor"/></svg>';
	$cart = '<svg viewBox="0 0 52 44" fill="none" stroke="currentColor" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"><path class="sf-atc-fill" d="M12 11h35l-5 17H14z" fill="currentColor" stroke="none"/><path d="M2 4h7l5 24h28l5-17H12"/><circle cx="18" cy="37" r="3.6" fill="currentColor"/><circle cx="38" cy="37" r="3.6" fill="currentColor"/></svg>';
	$txt  = '<span class="sf-atc-sp"></span><span class="sf-atc-txt"><span class="sf-atc-lg">' . esc_html( $label ) . '</span><span class="sf-atc-sh"%s>Vào giỏ</span></span>';
	return '<span class="sf-atc-ghost" aria-hidden="true">' . sprintf( $txt, '' ) . '</span>'
		. '<span class="sf-atc-face">' . sprintf( $txt, ' aria-hidden="true"' ) . '</span>'
		. '<span class="sf-atc-sky" aria-hidden="true"><span class="sf-atc-bag">' . $bag . '</span></span>'
		. '<span class="sf-atc-stage" aria-hidden="true"><span class="sf-atc-bag">' . $bag . '</span><span class="sf-atc-cart">' . $cart . '</span></span>';
}

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( tmt_cart_fx()['on'] ) {
			wp_enqueue_script( 'tmt-cart-fx', TMT_SHOP_URI . '/assets/js/cart-fx.js', array(), TMT_SHOP_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		}
	},
	20
);

/** Thuộc tính thêm vào <body> (header.php). */
function tmt_cart_fx_body_attrs() {
	$c = tmt_cart_fx();
	if ( ! $c['on'] ) {
		return '';
	}
	return sprintf(
		' data-cart-fx="%s" data-cart-look="%s" data-cart-ajax="%s"%s',
		esc_attr( $c['fx'] ),
		esc_attr( $c['look'] ),
		esc_url( WC_AJAX::get_endpoint( 'get_refreshed_fragments' ) ),
		is_customize_preview() ? ' data-cart-fx-demo' : ''
	);
}

/** Mẫu ruột nút cho nút "Thêm vào giỏ" của WooCommerce ở trang chi tiết (cart-fx.js thay vào, giữ chữ của nút). */
add_action(
	'wp_footer',
	function () {
		if ( function_exists( 'is_product' ) && is_product() && tmt_cart_fx()['product'] ) {
			echo '<template id="tmt-atc-tpl">' . tmt_atc_inner( '__LABEL__' ) . '</template>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
	}
);

/** Dòng "Trong giỏ: N món" dưới nút ở trang chi tiết. */
add_action(
	'woocommerce_after_add_to_cart_form',
	function () {
		if ( tmt_cart_fx()['count'] && WC()->cart ) {
			echo '<p class="sf-atc-count" aria-live="polite">Trong giỏ: <b data-sf-cart-count>' . (int) WC()->cart->get_cart_contents_count() . '</b> món</p>';
		}
	}
);

/* Yêu cầu thêm giỏ của nút hiệu ứng (có tmt_atc): không ghi thông báo "đã thêm vào giỏ", không chuyển sang trang giỏ. */
function tmt_is_atc_request() {
	return ! empty( $_POST['tmt_atc'] ) && ! empty( $_GET['wc-ajax'] ); // phpcs:ignore WordPress.Security.NonceVerification
}
add_filter(
	'wc_add_to_cart_message_html',
	function ( $html ) {
		return tmt_is_atc_request() ? '' : $html;
	}
);
add_filter(
	'pre_option_woocommerce_cart_redirect_after_add',
	function ( $v ) {
		return tmt_is_atc_request() ? 'no' : $v;
	}
);
