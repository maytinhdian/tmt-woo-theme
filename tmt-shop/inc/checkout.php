<?php
/**
 * Giỏ hàng + thanh toán kiểu C: một trang (trang Thanh toán của WooCommerce) chia mục 1 Giỏ hàng, 2 Thông tin nhận hàng,
 * 3 Thanh toán, khung tổng xanh đậm bên phải. Trang Giỏ hàng chỉ còn hiện giỏ trống; có hàng thì chuyển sang Thanh toán.
 * Template: woocommerce/checkout/form-checkout.php, review-order.php, cart/cart-empty.php. Kiểu dáng: assets/css/checkout.css.
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;

/*
 * WooCommerce 8+ tạo trang Giỏ hàng / Thanh toán bằng khối (block), giao diện cổ điển không chỉnh được.
 * Khi kích hoạt theme: đổi 2 trang đó sang mã ngắn [woocommerce_cart] / [woocommerce_checkout], giữ bản cũ trong meta.
 */
function tmt_wc_block_pages() {
	$out = array();
	foreach ( array( 'cart' => 'woocommerce/cart', 'checkout' => 'woocommerce/checkout' ) as $page => $block ) {
		$id = wc_get_page_id( $page );
		if ( $id > 0 && has_block( $block, $id ) ) {
			$out[ $page ] = $id;
		}
	}
	return $out;
}

function tmt_use_classic_wc_pages() {
	if ( ! function_exists( 'wc_get_page_id' ) ) {
		return;
	}
	foreach ( tmt_wc_block_pages() as $page => $id ) {
		update_post_meta( $id, '_tmt_block_content', get_post_field( 'post_content', $id ) );
		wp_update_post( array( 'ID' => $id, 'post_content' => '<!-- wp:shortcode -->[woocommerce_' . $page . ']<!-- /wp:shortcode -->' ) );
	}
}
add_action( 'after_switch_theme', 'tmt_use_classic_wc_pages' );

/** Trang vẫn đang dùng khối (vd. WooCommerce cài sau theme): nhắc trong trang quản trị, có nút đổi. */
add_action(
	'admin_notices',
	function () {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! tmt_wc_block_pages() ) {
			return;
		}
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=tmt_classic_pages' ), 'tmt_classic_pages' );
		echo '<div class="notice notice-warning"><p><b>TMT Shop:</b> trang Giỏ hàng / Thanh toán đang dùng khối của WooCommerce nên chưa có giao diện của theme. <a class="button button-primary" href="' . esc_url( $url ) . '">Đổi sang giao diện theme</a></p></div>';
	}
);
add_action(
	'admin_post_tmt_classic_pages',
	function () {
		check_admin_referer( 'tmt_classic_pages' );
		if ( current_user_can( 'manage_woocommerce' ) ) {
			tmt_use_classic_wc_pages();
		}
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
		exit;
	}
);

/** Có hàng trong giỏ thì trang Giỏ hàng chuyển sang trang Thanh toán (một trang kiểu C). */
add_action(
	'template_redirect',
	function () {
		if ( is_cart() && WC()->cart && ! WC()->cart->is_empty() ) {
			wp_safe_redirect( wc_get_checkout_url() );
			exit;
		}
	},
	20
);

// Mã giảm giá nằm trong khung tổng, đăng nhập nằm ở mục 2: bỏ 2 dải mặc định đầu trang thanh toán.
remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );
// Template tự đặt tóm tắt (khung tổng) và phần thanh toán (mục 3).
remove_action( 'woocommerce_checkout_order_review', 'woocommerce_order_review', 10 );
remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
// Giao tới địa chỉ khác: không dùng (một địa chỉ nhận hàng).
add_filter( 'woocommerce_cart_needs_shipping_address', '__return_false' );
add_filter( 'woocommerce_order_needs_shipping_address', '__return_false' ); // Địa chỉ giao nằm ở ô địa chỉ của khách: không in thêm "Địa chỉ giao hàng: N/A".

/** Ô thông tin gọn như web của tiệm: Họ tên, SĐT (Zalo), Email (không bắt buộc), Địa chỉ, Ghi chú. */
add_filter(
	'woocommerce_checkout_fields',
	function ( $fields ) {
		$billing = isset( $fields['billing'] ) ? $fields['billing'] : array();
		$keep    = array();
		$spec    = array(
			'billing_first_name' => array( 'Họ tên', true, 'name', 'person' ),
			'billing_phone'      => array( 'Số điện thoại (Zalo)', true, 'tel', 'call' ),
			'billing_email'      => array( 'Email (nhận thông báo đơn, không bắt buộc)', false, 'email', 'mail' ),
			'billing_address_1'  => array( 'Địa chỉ giao hàng', false, 'street-address', 'location_on' ),
		);
		$prio    = 10;
		foreach ( $spec as $key => $s ) {
			$f                 = isset( $billing[ $key ] ) ? $billing[ $key ] : array( 'type' => 'text' );
			$f['label']        = $s[0];
			$f['required']     = $s[1];
			$f['autocomplete'] = $s[2];
			$f['tmt_icon']     = $s[3];
			$f['priority']     = $prio;
			$f['class']        = array( 'form-row-wide' );
			$f['placeholder']  = 'billing_address_1' === $key ? 'Số nhà, đường, phường, tỉnh/thành' : '';
			$keep[ $key ]      = $f;
			$prio             += 10;
		}
		$fields['billing'] = $keep;
		if ( isset( $fields['order']['order_comments'] ) ) {
			$fields['order']['order_comments']['label']       = 'Ghi chú (không bắt buộc)';
			$fields['order']['order_comments']['placeholder'] = 'Giờ nhận hàng, xuất hóa đơn công ty...';
		}
		return $fields;
	},
	20
);
/** Bỏ bắt buộc của WooCommerce cho ô địa chỉ (theme tự kiểm: chỉ bắt buộc khi giao tận nơi). */
add_filter(
	'woocommerce_billing_fields',
	function ( $fields ) {
		if ( isset( $fields['billing_address_1'] ) ) {
			$fields['billing_address_1']['required'] = false;
		}
		if ( isset( $fields['billing_email'] ) ) {
			$fields['billing_email']['required'] = false;
		}
		return $fields;
	},
	20
);

/**
 * Cách nhận hàng = phương thức giao hàng của WooCommerce cho gói hàng đầu tiên.
 *
 * @return array{rates: WC_Shipping_Rate[], chosen: string}
 */
function tmt_checkout_rates() {
	$packages = WC()->shipping() ? WC()->shipping()->get_packages() : array();
	$rates    = isset( $packages[0]['rates'] ) ? $packages[0]['rates'] : array();
	$chosen   = WC()->session ? (array) WC()->session->get( 'chosen_shipping_methods' ) : array();
	$pick     = isset( $chosen[0] ) && isset( $rates[ $chosen[0] ] ) ? $chosen[0] : (string) key( $rates );
	return array( 'rates' => $rates, 'chosen' => $pick );
}

/** Phương thức "nhận tại tiệm" (không cần địa chỉ). */
function tmt_is_pickup_rate( $rate ) {
	return $rate instanceof WC_Shipping_Rate && in_array( $rate->get_method_id(), array( 'local_pickup', 'pickup_location' ), true );
}

/** Đang chọn nhận tại tiệm. */
function tmt_chosen_pickup() {
	$r = tmt_checkout_rates();
	return isset( $r['rates'][ $r['chosen'] ] ) && tmt_is_pickup_rate( $r['rates'][ $r['chosen'] ] );
}

add_action(
	'woocommerce_after_checkout_validation',
	function ( $data, $errors ) {
		$methods = isset( $data['shipping_method'] ) ? (array) $data['shipping_method'] : array();
		$rates   = tmt_checkout_rates()['rates'];
		$pickup  = isset( $methods[0], $rates[ $methods[0] ] ) && tmt_is_pickup_rate( $rates[ $methods[0] ] );
		if ( WC()->cart->needs_shipping() && ! $pickup && '' === trim( (string) ( isset( $data['billing_address_1'] ) ? $data['billing_address_1'] : '' ) ) ) {
			$errors->add( 'billing_address_1_required', 'Nhập <strong>địa chỉ giao hàng</strong>, hoặc chọn nhận tại tiệm.', array( 'id' => 'billing_address_1' ) );
		}
		$phone = preg_replace( '/\D/', '', (string) ( isset( $data['billing_phone'] ) ? $data['billing_phone'] : '' ) );
		if ( '' !== $phone && ( strlen( $phone ) < 9 || strlen( $phone ) > 12 ) ) {
			$errors->add( 'billing_phone_validation', '<strong>Số điện thoại</strong> chưa đúng.', array( 'id' => 'billing_phone' ) );
		}
	},
	10,
	2
);

/** Tiền khách được giảm nhờ giá khuyến mãi (giá thường − giá bán) của cả giỏ. */
function tmt_cart_savings() {
	$sum = 0;
	foreach ( WC()->cart->get_cart() as $item ) {
		if ( ! empty( $item['tmt_gift'] ) ) {
			continue; // Quà tặng kèm (giá 0) không tính là giảm giá.
		}
		$p       = $item['data'];
		$regular = (float) $p->get_regular_price();
		$price   = (float) $p->get_price();
		if ( $p->is_on_sale() && $regular > $price ) {
			$sum += ( wc_get_price_to_display( $p, array( 'price' => $regular ) ) - wc_get_price_to_display( $p ) ) * $item['quantity'];
		}
	}
	return $sum;
}

/** Mức đơn được giao miễn phí (phương thức "Miễn phí giao hàng" có số tiền tối thiểu), 0 khi không có. */
function tmt_free_shipping_from() {
	$packages = WC()->cart->get_shipping_packages();
	if ( ! $packages || ! class_exists( 'WC_Shipping_Zones' ) ) {
		return 0;
	}
	$zone = WC_Shipping_Zones::get_zone_matching_package( $packages[0] );
	foreach ( $zone->get_shipping_methods( true ) as $m ) {
		if ( 'free_shipping' === $m->id && in_array( $m->requires, array( 'min_amount', 'either' ), true ) && (float) $m->min_amount > 0 ) {
			return (float) $m->min_amount;
		}
	}
	return 0;
}

/** Số tiền đã tính để so với mức miễn phí giao (như WooCommerce: sau giảm giá, gồm thuế nếu hiện giá gồm thuế). */
function tmt_cart_amount_for_free_shipping() {
	$total = WC()->cart->get_displayed_subtotal();
	if ( WC()->cart->display_prices_including_tax() ) {
		$total = $total - WC()->cart->get_discount_tax();
	}
	return $total - WC()->cart->get_discount_total();
}

/** Sản phẩm gợi ý "Mua kèm": bán kèm của các món trong giỏ, thiếu thì lấy hàng đang giảm giá. */
function tmt_cart_suggestions( $limit = 4 ) {
	$in_cart = array();
	foreach ( WC()->cart->get_cart() as $item ) {
		$in_cart[] = (int) $item['product_id'];
	}
	$ids = array_diff( array_map( 'intval', WC()->cart->get_cross_sells() ), $in_cart );
	if ( count( $ids ) < $limit ) {
		$ids = array_merge( $ids, array_diff( array_map( 'intval', wc_get_product_ids_on_sale() ), $in_cart, $ids ) );
	}
	$out = array();
	foreach ( array_slice( array_unique( $ids ), 0, $limit * 2 ) as $id ) {
		$p = wc_get_product( $id );
		if ( $p && $p->is_visible() && $p->is_in_stock() && 0 === $p->get_parent_id() ) {
			$out[] = $p;
		}
		if ( count( $out ) >= $limit ) {
			break;
		}
	}
	return $out;
}

/** Chữ phí giao của dòng "Phí giao hàng" trong khung tổng. */
function tmt_shipping_fee_text() {
	if ( ! WC()->cart->needs_shipping() ) {
		return array( 'Không tính', true );
	}
	$r = tmt_checkout_rates();
	if ( ! isset( $r['rates'][ $r['chosen'] ] ) ) {
		return array( 'Tiệm báo khi gọi', false );
	}
	$rate = $r['rates'][ $r['chosen'] ];
	$cost = (float) $rate->get_cost() + ( WC()->cart->display_prices_including_tax() ? array_sum( $rate->get_taxes() ) : 0 );
	if ( $cost > 0 ) {
		return array( wc_price( $cost ), false );
	}
	return array( tmt_is_pickup_rate( $rate ) ? 'Không tính' : 'Miễn phí', true );
}

/** Tổng tiền + tiết kiệm cho thanh dưới đáy (điện thoại); cập nhật cùng khung tổng khi đổi cách nhận hàng. */
function tmt_checkout_mbar_sum() {
	$save = tmt_cart_savings() + WC()->cart->get_discount_total();
	$html = '<div class="sfc-mbar-sum"><small>Tổng cộng</small><b>' . wc_price( WC()->cart->get_total( 'edit' ) ) . '</b>';
	if ( $save > 0 ) {
		$html .= '<em>Tiết kiệm ' . wc_price( $save ) . '</em>';
	}
	return $html . '</div>';
}
add_filter(
	'woocommerce_update_order_review_fragments',
	function ( $fragments ) {
		$fragments['.sfc-mbar-sum'] = tmt_checkout_mbar_sum();
		return $fragments;
	}
);

/** Ô nhập kiểu của theme (icon trái, viền bo) cho các ô của trang thanh toán. */
add_filter(
	'woocommerce_form_field_args',
	function ( $args, $key ) {
		if ( is_checkout() && ! empty( $args['tmt_icon'] ) ) {
			$args['class'][]       = 'sfc-wcf';
			$args['input_class'][] = 'sfc-wci';
		}
		return $args;
	},
	10,
	2
);
add_filter(
	'woocommerce_form_field',
	function ( $field, $key, $args ) {
		if ( empty( $args['tmt_icon'] ) || ! is_checkout() ) {
			return $field;
		}
		return preg_replace( '/(<span class="woocommerce-input-wrapper">)/', '$1' . tmt_icon( $args['tmt_icon'], 'sfc-wci-ic' ), $field, 1 );
	},
	10,
	3
);

/** CSS + JS của giỏ / thanh toán / cảm ơn. */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! ( is_cart() || is_checkout() || is_account_page() || is_page() && has_shortcode( (string) get_post_field( 'post_content', get_the_ID() ), 'tmt_tra_don' ) ) ) {
			return;
		}
		wp_enqueue_style( 'tmt-checkout', TMT_SHOP_URI . '/assets/css/checkout.css', array( 'tmt-theme' ), TMT_SHOP_VERSION );
		if ( is_checkout() && ! is_wc_endpoint_url() ) {
			wp_enqueue_script( 'tmt-checkout', TMT_SHOP_URI . '/assets/js/checkout.js', array( 'jquery', 'wc-checkout' ), TMT_SHOP_VERSION, true );
		}
	},
	30
);

/** Trang con của Thanh toán/Tài khoản (Đơn đã nhận...): WooCommerce in tên trang con hai lần ở đường dẫn, bỏ mục lặp. */
add_filter(
	'woocommerce_get_breadcrumb',
	function ( $crumbs ) {
		$out = array();
		foreach ( $crumbs as $c ) {
			$last = end( $out );
			if ( ! $last || $last[0] !== $c[0] ) {
				$out[] = $c;
			}
		}
		return $out;
	}
);

// Giỏ trống: khung giỏ trống đã có chữ "Giỏ hàng đang trống", bỏ dải thông báo của WooCommerce.
remove_action( 'woocommerce_cart_is_empty', 'wc_empty_cart_message', 10 );
