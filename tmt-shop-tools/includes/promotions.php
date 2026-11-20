<?php
/**
 * Khuyến mãi giá (chép ý từ module Promotions của app, ngoài "Tặng kèm" đã ở gifts.php).
 *
 * Mỗi chương trình là một bài CPT tmt_promo (WooCommerce → Khuyến mãi), cấu hình trong meta _tmt_promo:
 *   type   = flash (giá KM có hạn) | group (giá riêng nhóm khách) | combo (mua đủ bộ thì giảm)
 *   rows   = [{product, category, price, percent}] cho flash / group: giá cố định cho một sản phẩm, hoặc % cho sản phẩm /
 *            nhóm hàng (tính trên giá gốc, làm tròn nghìn). Dòng có sản phẩm thắng dòng nhóm hàng.
 *   groups = nhóm khách được hưởng (group): loyal | vip | dealer, đọc từ user meta tmt_group (Người dùng → Sửa).
 *   items  = [{product, qty}] + amount = tiền giảm mỗi bộ (combo).
 *   starts / ends = 'Y-m-d H:i' giờ của web, limit = số suất (flash / group: số cái bán giá KM; combo: số bộ).
 * Đã dùng: meta _tmt_promo_used, cộng khi có đơn, trả lại khi đơn hủy / thất bại / hoàn tiền.
 *
 * Luật như app: giá món = thấp nhất giữa giá đang bán, giá KM có hạn và giá nhóm khách (không cộng hai cái);
 * combo trừ thêm (phí âm trong giỏ); quà tặng (gifts.php) và mã giảm giá của WooCommerce tính sau.
 * Giá áp qua filter giá của WooCommerce nên danh sách, thẻ, trang sản phẩm, giỏ đều thấy cùng một giá.
 * Theme gọi tmt_km_box() ở trang sản phẩm và tmt_km_badge() trên thẻ.
 *
 * @package TMT_Shop_Tools
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	function () {
		$cap = 'manage_woocommerce';
		register_post_type(
			'tmt_promo',
			array(
				'labels'       => array(
					'name'          => 'Khuyến mãi',
					'singular_name' => 'Chương trình khuyến mãi',
					'menu_name'     => 'Khuyến mãi',
					'all_items'     => 'Khuyến mãi',
					'add_new'       => 'Thêm chương trình',
					'add_new_item'  => 'Thêm chương trình khuyến mãi',
					'edit_item'     => 'Sửa chương trình khuyến mãi',
					'search_items'  => 'Tìm chương trình',
					'not_found'     => 'Chưa có chương trình khuyến mãi nào.',
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => 'woocommerce',
				'supports'     => array( 'title' ),
				'map_meta_cap' => false,
				'capabilities' => array(
					'edit_post'          => $cap,
					'read_post'          => $cap,
					'delete_post'        => $cap,
					'edit_posts'         => $cap,
					'edit_others_posts'  => $cap,
					'publish_posts'      => $cap,
					'read_private_posts' => $cap,
					'delete_posts'       => $cap,
					'create_posts'       => $cap,
				),
			)
		);
	}
);

/** Loại chương trình => [tên, mô tả]. */
function tmt_km_types() {
	return array(
		'flash' => array( 'Giá KM có hạn', 'Giảm giá món trong thời gian, giới hạn số suất, có đếm ngược' ),
		'group' => array( 'Giá nhóm khách', 'Đại lý, thợ, thân thiết: giá riêng theo sản phẩm / nhóm hàng' ),
		'combo' => array( 'Combo', 'Mua đủ bộ món thì giảm' ),
	);
}

/** Nhóm khách (giống app: Customers/Enums/CustomerGroup, trừ "Khách thường"). */
function tmt_customer_groups() {
	return array(
		'loyal'  => 'Thân thiết',
		'vip'    => 'VIP',
		'dealer' => 'Đại lý, thợ',
	);
}

/** Nhóm của khách đang xem ('' = khách thường / chưa đăng nhập). */
function tmt_current_group() {
	$uid = get_current_user_id();
	$g   = $uid ? (string) get_user_meta( $uid, 'tmt_group', true ) : '';
	return isset( tmt_customer_groups()[ $g ] ) ? $g : '';
}

function tmt_km_rules( $id ) {
	$r = get_post_meta( $id, '_tmt_promo', true );
	return wp_parse_args(
		is_array( $r ) ? $r : array(),
		array(
			'type'   => 'flash',
			'rows'   => array(),
			'groups' => array(),
			'items'  => array(),
			'amount' => 0,
			'starts' => '',
			'ends'   => '',
			'limit'  => '',
		)
	);
}

/** Suất còn lại, null = không giới hạn. */
function tmt_km_remaining( $id ) {
	$r = tmt_km_rules( $id );
	if ( '' === (string) $r['limit'] ) {
		return null;
	}
	return max( 0, (int) $r['limit'] - (int) get_post_meta( $id, '_tmt_promo_used', true ) );
}

/** Chương trình đang chạy: đã đăng, trong thời gian, còn suất. [id => rules + name]. */
function tmt_km_programs() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$cache = array();
	if ( ! did_action( 'init' ) ) {
		return array(); // Chưa đăng ký CPT: không giữ cache rỗng.
	}
	$now = current_time( 'Y-m-d H:i' );
	$ids = get_posts(
		array(
			'post_type'        => 'tmt_promo',
			'post_status'      => 'publish',
			'posts_per_page'   => 100,
			'fields'           => 'ids',
			'orderby'          => 'ID',
			'order'            => 'ASC',
			'suppress_filters' => true,
		)
	);
	foreach ( $ids as $id ) {
		$r = tmt_km_rules( $id );
		if ( ( '' !== $r['starts'] && $now < $r['starts'] ) || ( '' !== $r['ends'] && $now > $r['ends'] ) || 0 === tmt_km_remaining( $id ) ) {
			continue;
		}
		$r['id']      = (int) $id;
		$r['name']    = get_the_title( $id );
		$cache[ $id ] = $r;
	}
	return $cache;
}

/** Nhóm hàng của sản phẩm kể cả nhóm cha. */
function tmt_km_cat_chain( $pid ) {
	static $memo = array();
	if ( ! isset( $memo[ $pid ] ) ) {
		$chain = array();
		foreach ( wc_get_product_term_ids( $pid, 'product_cat' ) as $tid ) {
			$chain[] = (int) $tid;
			foreach ( get_ancestors( $tid, 'product_cat', 'taxonomy' ) as $a ) {
				$chain[] = (int) $a;
			}
		}
		$memo[ $pid ] = $chain;
	}
	return $memo[ $pid ];
}

/** Giá của chương trình flash / group cho sản phẩm (hoặc biến thể), null = không áp. Tính trên giá gốc. */
function tmt_km_row_price( array $r, WC_Product $product ) {
	$regular = (float) $product->get_regular_price( 'edit' );
	if ( $regular <= 0 ) {
		return null;
	}
	$id     = $product->get_id();
	$parent = $product->get_parent_id();
	$row    = null;
	foreach ( (array) $r['rows'] as $x ) {
		if ( ! empty( $x['product'] ) && in_array( (int) $x['product'], array( $id, $parent ), true ) ) {
			$row = $x;
			break;
		}
	}
	if ( ! $row ) {
		$chain = tmt_km_cat_chain( $parent ? $parent : $id );
		foreach ( (array) $r['rows'] as $x ) {
			if ( empty( $x['product'] ) && ! empty( $x['category'] ) && in_array( (int) $x['category'], $chain, true ) ) {
				$row = $x;
				break;
			}
		}
	}
	if ( ! $row ) {
		return null;
	}
	// Giá cố định chỉ cho dòng có sản phẩm cụ thể (sản phẩm cha có biến thể: áp cho mọi biến thể như %).
	if ( '' !== (string) $row['price'] && ! empty( $row['product'] ) && ( (int) $row['product'] === $id || ! $parent ) ) {
		return max( 0, (int) $row['price'] );
	}
	$percent = (float) $row['percent'];
	return $percent > 0 ? (int) ( round( $regular * ( 100 - min( 100, $percent ) ) / 100 / 1000 ) * 1000 ) : null;
}

/** Sản phẩm (đối tượng giỏ) không được hưởng giá KM (vượt số suất). */
function tmt_km_blocked( WC_Product $product, $block = null ) {
	static $blocked = array();
	$key = spl_object_id( $product );
	if ( null !== $block ) {
		if ( $block ) {
			$blocked[ $key ] = true;
		} else {
			unset( $blocked[ $key ] );
		}
	}
	return isset( $blocked[ $key ] );
}

/**
 * Giá KM tốt nhất của sản phẩm cho khách đang xem: ['price' => int, 'id' => chương trình, 'name', 'type', 'ends',
 * 'remaining'] hoặc null. $base = giá đang bán (để chỉ áp khi rẻ hơn).
 */
function tmt_km_best( WC_Product $product, $base = null ) {
	static $busy = false;
	if ( $busy || tmt_km_blocked( $product ) ) {
		return null;
	}
	$busy  = true;
	$base  = null === $base ? (float) $product->get_price( 'edit' ) : (float) $base;
	$group = tmt_current_group();
	$best  = null;
	if ( $base > 0 ) {
		foreach ( tmt_km_programs() as $r ) {
			if ( 'combo' === $r['type'] || ( 'group' === $r['type'] && ( '' === $group || ! in_array( $group, (array) $r['groups'], true ) ) ) ) {
				continue;
			}
			$price = tmt_km_row_price( $r, $product );
			if ( null === $price || $price >= ( $best ? $best['price'] : $base ) ) {
				continue;
			}
			$best = array(
				'price'     => $price,
				'id'        => $r['id'],
				'name'      => $r['name'],
				'type'      => $r['type'],
				'ends'      => $r['ends'],
				'remaining' => tmt_km_remaining( $r['id'] ),
			);
		}
	}
	$busy = false;
	return $best;
}

// Áp giá: giá bán và giá sale đều thành giá KM khi rẻ hơn (WooCommerce tự gạch giá gốc, tính % giảm).
foreach ( array( 'woocommerce_product_get_price', 'woocommerce_product_variation_get_price' ) as $tmt_hook ) {
	add_filter(
		$tmt_hook,
		function ( $value, $product ) {
			if ( '' === $value || null === $value || is_admin() && ! wp_doing_ajax() ) {
				return $value;
			}
			$best = tmt_km_best( $product, $value );
			return $best ? (string) $best['price'] : $value;
		},
		20,
		2
	);
}
foreach ( array( 'woocommerce_product_get_sale_price', 'woocommerce_product_variation_get_sale_price' ) as $tmt_hook ) {
	add_filter(
		$tmt_hook,
		function ( $value, $product ) {
			if ( is_admin() && ! wp_doing_ajax() ) {
				return $value;
			}
			$base = '' !== (string) $value ? $value : $product->get_regular_price( 'edit' );
			$best = '' !== (string) $base ? tmt_km_best( $product, $base ) : null;
			return $best ? (string) $best['price'] : $value;
		},
		20,
		2
	);
}
unset( $tmt_hook );

// Sản phẩm có biến thể: khoảng giá trên thẻ / trang.
foreach ( array( 'woocommerce_variation_prices_price', 'woocommerce_variation_prices_sale_price' ) as $tmt_hook ) {
	add_filter(
		$tmt_hook,
		function ( $value, $variation ) {
			$base = '' !== (string) $value ? $value : $variation->get_regular_price( 'edit' );
			$best = '' !== (string) $base ? tmt_km_best( $variation, $base ) : null;
			return $best ? (string) $best['price'] : $value;
		},
		20,
		2
	);
}
unset( $tmt_hook );
add_filter(
	'woocommerce_get_variation_prices_hash',
	function ( $hash ) {
		$hash[] = 'tmt-promo-' . tmt_current_group() . '-' . md5( wp_json_encode( array_keys( tmt_km_programs() ) ) . get_option( 'tmt_promo_ver', '' ) );
		return $hash;
	}
);

// Mục "Đang giảm giá" của theme (wc_get_product_ids_on_sale) có cả sản phẩm giá KM có hạn.
add_filter(
	'woocommerce_product_is_on_sale',
	function ( $on, $product ) {
		return $on || ( ! is_admin() && tmt_km_best( $product ) );
	},
	20,
	2
);
add_filter(
	'transient_wc_products_onsale',
	function ( $ids ) {
		if ( ! is_array( $ids ) || is_admin() ) {
			return $ids;
		}
		foreach ( tmt_km_programs() as $r ) {
			if ( 'flash' !== $r['type'] ) {
				continue;
			}
			foreach ( (array) $r['rows'] as $row ) {
				if ( ! empty( $row['product'] ) ) {
					$ids[] = (int) $row['product'];
				} elseif ( ! empty( $row['category'] ) ) {
					$ids = array_merge( $ids, wc_get_products( array( 'status' => 'publish', 'limit' => 60, 'return' => 'ids', 'product_category_id' => array( (int) $row['category'] ) ) ) );
				}
			}
		}
		return array_values( array_unique( array_map( 'intval', $ids ) ) );
	}
);

/* ---------- Giỏ hàng ---------- */

/**
 * Trước khi tính tiền: đánh dấu dòng hưởng giá KM (cart item tmt_promo) và bỏ giá KM ở dòng vượt số suất còn lại.
 * Chạy trước gifts.php (ưu tiên 5) để điều kiện quà tính trên giá đã giảm.
 */
add_action(
	'woocommerce_before_calculate_totals',
	function ( $cart ) {
		if ( ! $cart instanceof WC_Cart ) {
			return;
		}
		$left = array();
		foreach ( $cart->cart_contents as $key => $item ) {
			if ( ! empty( $item['tmt_gift'] ) ) {
				continue;
			}
			tmt_km_blocked( $item['data'], false );
			$best = tmt_km_best( $item['data'] );
			unset( $cart->cart_contents[ $key ]['tmt_promo'] );
			if ( ! $best ) {
				continue;
			}
			if ( null !== $best['remaining'] ) {
				$left[ $best['id'] ] = isset( $left[ $best['id'] ] ) ? $left[ $best['id'] ] : $best['remaining'];
				if ( $left[ $best['id'] ] < (int) $item['quantity'] ) {
					tmt_km_blocked( $item['data'], true );
					if ( did_action( 'wp' ) && ! wp_doing_ajax() && ( is_checkout() || is_cart() ) && ! did_action( 'woocommerce_checkout_order_processed' ) && ! wc_has_notice( $best['name'] . ': chỉ còn ' . $left[ $best['id'] ] . ' suất giá khuyến mãi.', 'notice' ) ) {
						wc_add_notice( $best['name'] . ': chỉ còn ' . $left[ $best['id'] ] . ' suất giá khuyến mãi.', 'notice' );
					}
					continue;
				}
				$left[ $best['id'] ] -= (int) $item['quantity'];
			}
			$cart->cart_contents[ $key ]['tmt_promo'] = $best['id'];
		}
	},
	4
);

/** Combo đủ bộ trong giỏ: [[id, name, sets, amount (tổng tiền giảm)]]. */
function tmt_km_cart_combos( WC_Cart $cart ) {
	$qty = array();
	foreach ( $cart->get_cart() as $item ) {
		if ( ! empty( $item['tmt_gift'] ) ) {
			continue;
		}
		foreach ( array_unique( array( (int) $item['product_id'], (int) $item['variation_id'] ) ) as $pid ) {
			if ( $pid ) {
				$qty[ $pid ] = ( isset( $qty[ $pid ] ) ? $qty[ $pid ] : 0 ) + (int) $item['quantity'];
			}
		}
	}
	$out = array();
	foreach ( tmt_km_programs() as $r ) {
		$items = array_values( array_filter( (array) $r['items'], fn( $i ) => ! empty( $i['product'] ) ) );
		if ( 'combo' !== $r['type'] || count( $items ) < 2 || (int) $r['amount'] <= 0 ) {
			continue;
		}
		$sets = PHP_INT_MAX;
		foreach ( $items as $i ) {
			$sets = min( $sets, intdiv( isset( $qty[ (int) $i['product'] ] ) ? $qty[ (int) $i['product'] ] : 0, max( 1, (int) $i['qty'] ) ) );
		}
		$left = tmt_km_remaining( $r['id'] );
		if ( null !== $left ) {
			$sets = min( $sets, $left );
		}
		if ( $sets <= 0 ) {
			continue;
		}
		// Món đã vào combo này không tính cho combo sau.
		foreach ( $items as $i ) {
			$qty[ (int) $i['product'] ] -= max( 1, (int) $i['qty'] ) * $sets;
		}
		$out[] = array( 'id' => $r['id'], 'name' => $r['name'], 'sets' => $sets, 'amount' => (int) $r['amount'] * $sets );
	}
	return $out;
}

add_action(
	'woocommerce_cart_calculate_fees',
	function ( $cart ) {
		foreach ( tmt_km_cart_combos( $cart ) as $c ) {
			$cart->fees_api()->add_fee(
				array(
					'id'      => 'tmt-combo-' . $c['id'],
					'name'    => 'Combo: ' . $c['name'] . ( $c['sets'] > 1 ? ' (x' . $c['sets'] . ')' : '' ),
					'amount'  => -1 * $c['amount'],
					'taxable' => false,
				)
			);
		}
	}
);

// Ghi chương trình vào dòng đơn / phí combo để đếm suất.
add_action(
	'woocommerce_checkout_create_order_line_item',
	function ( $line, $key, $values ) {
		if ( ! empty( $values['tmt_promo'] ) ) {
			$line->add_meta_data( '_tmt_promo', (int) $values['tmt_promo'], true );
			$line->add_meta_data( 'Khuyến mãi', get_the_title( (int) $values['tmt_promo'] ), true );
		}
	},
	10,
	3
);
add_action(
	'woocommerce_checkout_create_order_fee_item',
	function ( $item, $fee_key, $fee ) {
		if ( isset( $fee->id ) && 0 === strpos( (string) $fee->id, 'tmt-combo-' ) ) {
			$pid = (int) substr( $fee->id, 10 );
			foreach ( tmt_km_cart_combos( WC()->cart ) as $c ) {
				if ( $c['id'] === $pid ) {
					$item->add_meta_data( '_tmt_promo', $pid, true );
					$item->add_meta_data( '_tmt_promo_sets', $c['sets'], true );
				}
			}
		}
	},
	10,
	3
);

/** Đếm suất: + khi có đơn, - khi đơn hủy / thất bại / hoàn tiền (mỗi đơn một lần). */
function tmt_km_count_order( $order, $delta ) {
	$order = $order instanceof WC_Order ? $order : wc_get_order( $order );
	if ( ! $order ) {
		return;
	}
	$counted = 'yes' === $order->get_meta( '_tmt_promo_counted' );
	if ( ( $delta > 0 && $counted ) || ( $delta < 0 && ! $counted ) ) {
		return;
	}
	$use = array();
	foreach ( $order->get_items( array( 'line_item', 'fee' ) ) as $item ) {
		$pid = (int) $item->get_meta( '_tmt_promo' );
		if ( $pid ) {
			$n           = 'fee' === $item->get_type() ? (int) $item->get_meta( '_tmt_promo_sets' ) : (int) $item->get_quantity();
			$use[ $pid ] = ( isset( $use[ $pid ] ) ? $use[ $pid ] : 0 ) + max( 1, $n );
		}
	}
	foreach ( $use as $pid => $n ) {
		update_post_meta( $pid, '_tmt_promo_used', max( 0, (int) get_post_meta( $pid, '_tmt_promo_used', true ) + $delta * $n ) );
	}
	if ( $use ) {
		$order->update_meta_data( '_tmt_promo_counted', $delta > 0 ? 'yes' : 'no' );
		$order->save_meta_data();
		update_option( 'tmt_promo_ver', (string) time() );
	}
}
add_action( 'woocommerce_checkout_order_created', fn( $order ) => tmt_km_count_order( $order, 1 ) );
foreach ( array( 'cancelled', 'failed', 'refunded' ) as $tmt_status ) {
	add_action( 'woocommerce_order_status_' . $tmt_status, fn( $id ) => tmt_km_count_order( $id, -1 ) );
}
unset( $tmt_status );

/* ---------- Hiển thị (theme gọi) ---------- */

/** Combo có sản phẩm này: [[name, amount, items => [[name, qty, url|null]]]]. */
function tmt_km_combos_for( WC_Product $product ) {
	$out = array();
	$ids = array( $product->get_id() );
	if ( $product->is_type( 'variable' ) ) {
		$ids = array_merge( $ids, $product->get_children() );
	}
	foreach ( tmt_km_programs() as $r ) {
		if ( 'combo' !== $r['type'] || (int) $r['amount'] <= 0 ) {
			continue;
		}
		$pids = array_map( fn( $i ) => (int) $i['product'], (array) $r['items'] );
		if ( ! array_intersect( $ids, $pids ) ) {
			continue;
		}
		$items = array();
		foreach ( (array) $r['items'] as $i ) {
			$p = wc_get_product( (int) $i['product'] );
			if ( $p ) {
				$items[] = array(
					'name' => $p->get_name(),
					'qty'  => max( 1, (int) $i['qty'] ),
					'url'  => in_array( $p->get_id(), $ids, true ) ? null : $p->get_permalink(),
				);
			}
		}
		$out[] = array( 'name' => $r['name'], 'amount' => (int) $r['amount'], 'items' => $items );
	}
	return $out;
}

/** Hộp ưu đãi trên trang sản phẩm: giá sốc + đếm ngược + suất, giá nhóm khách, combo. */
function tmt_km_box( WC_Product $product ) {
	$best = tmt_km_best( $product );
	if ( ! $best && $product->is_type( 'variable' ) ) {
		foreach ( $product->get_children() as $vid ) {
			$v = wc_get_product( $vid );
			if ( $v && ( $best = tmt_km_best( $v ) ) ) {
				break;
			}
		}
	}
	$combos = tmt_km_combos_for( $product );
	if ( ! $best && ! $combos ) {
		return;
	}
	echo '<div class="sf-deals">';
	if ( $best && 'flash' === $best['type'] && ( '' !== $best['ends'] || null !== $best['remaining'] ) ) {
		$end = '' !== $best['ends'] ? get_gmt_from_date( $best['ends'] . ':00', 'c' ) : '';
		echo '<div class="sf-deal is-flash"><i class="fa-solid fa-bolt" aria-hidden="true"></i><div><b>' . esc_html( $best['name'] ) . '</b><span>';
		if ( $end ) {
			echo 'Kết thúc sau <time data-sf-countdown="' . esc_attr( $end ) . '" datetime="' . esc_attr( $end ) . '">' . esc_html( wp_date( 'H:i d/m', strtotime( $end ) ) ) . '</time>';
		}
		if ( null !== $best['remaining'] ) {
			echo ( $end ? ' · ' : '' ) . 'còn ' . (int) $best['remaining'] . ' suất';
		}
		echo '</span></div></div>';
	} elseif ( $best && 'group' === $best['type'] ) {
		echo '<div class="sf-deal is-group"><i class="fa-solid fa-user-tag" aria-hidden="true"></i><div><b>' . esc_html( $best['name'] ) . '</b><span>Giá riêng cho nhóm ' . esc_html( tmt_customer_groups()[ tmt_current_group() ] ) . '</span></div></div>';
	}
	foreach ( $combos as $c ) {
		$parts = array();
		foreach ( $c['items'] as $it ) {
			$label   = ( $it['qty'] > 1 ? $it['qty'] . ' × ' : '' ) . $it['name'];
			$parts[] = $it['url'] ? '<a href="' . esc_url( $it['url'] ) . '">' . esc_html( $label ) . '</a>' : esc_html( $label );
		}
		echo '<div class="sf-deal is-combo"><i class="fa-solid fa-layer-group" aria-hidden="true"></i><div><b>Mua kèm giảm ' . wp_kses_post( wc_price( $c['amount'] ) ) . '</b><span>' . implode( ' + ', $parts ) . '</span></div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- từng phần đã escape.
	}
	echo '</div>';
}

/** Nhãn nhỏ trên thẻ sản phẩm: "Có quà" / "Mua kèm giảm", '' khi không có. */
function tmt_km_badge( WC_Product $product ) {
	if ( function_exists( 'tmt_gift_offers' ) && tmt_gift_offers( $product ) ) {
		return '<span class="sf-pcard-deal"><i class="fa-solid fa-gift" aria-hidden="true"></i>Có quà</span>';
	}
	if ( tmt_km_combos_for( $product ) ) {
		return '<span class="sf-pcard-deal"><i class="fa-solid fa-layer-group" aria-hidden="true"></i>Mua kèm giảm</span>';
	}
	return '';
}

/* ---------- Quản trị ---------- */

add_filter(
	'woocommerce_screen_ids',
	function ( $ids ) {
		$ids[] = 'tmt_promo';
		$ids[] = 'edit-tmt_promo';
		return $ids;
	}
);

add_action(
	'add_meta_boxes_tmt_promo',
	function () {
		add_meta_box( 'tmt-promo', 'Cách giảm', 'tmt_km_meta_box', 'tmt_promo', 'normal', 'high' );
	}
);

/** Một dòng giá (flash / group). */
function tmt_km_row_form( $i, array $row, array $cats ) {
	$p = ! empty( $row['product'] ) ? wc_get_product( (int) $row['product'] ) : null;
	?>
	<div class="tmt-pr-row">
		<select class="wc-product-search" name="tmt_promo[rows][<?php echo esc_attr( $i ); ?>][product]" data-placeholder="Sản phẩm..." data-action="woocommerce_json_search_products_and_variations" data-allow_clear="true">
			<?php if ( $p ) : ?><option value="<?php echo (int) $p->get_id(); ?>" selected><?php echo esc_html( wp_strip_all_tags( $p->get_formatted_name() ) ); ?></option><?php endif; ?>
		</select>
		<span>hoặc</span>
		<select name="tmt_promo[rows][<?php echo esc_attr( $i ); ?>][category]">
			<option value="">Nhóm hàng...</option>
			<?php foreach ( $cats as $t ) : ?><option value="<?php echo (int) $t->term_id; ?>" <?php selected( isset( $row['category'] ) ? (int) $row['category'] : 0, (int) $t->term_id ); ?>><?php echo esc_html( $t->name ); ?></option><?php endforeach; ?>
		</select>
		<input type="number" min="0" step="1000" name="tmt_promo[rows][<?php echo esc_attr( $i ); ?>][price]" value="<?php echo esc_attr( isset( $row['price'] ) ? (string) $row['price'] : '' ); ?>" placeholder="Giá KM ₫">
		<input type="number" min="0" max="100" step="0.5" name="tmt_promo[rows][<?php echo esc_attr( $i ); ?>][percent]" value="<?php echo esc_attr( isset( $row['percent'] ) && $row['percent'] ? (string) $row['percent'] : '' ); ?>" placeholder="hoặc %">
		<button type="button" class="button-link tmt-pr-x" data-pr-del aria-label="Xóa dòng">×</button>
	</div>
	<?php
}

/** Một món của combo. */
function tmt_km_item_form( $i, array $item ) {
	$p = ! empty( $item['product'] ) ? wc_get_product( (int) $item['product'] ) : null;
	?>
	<div class="tmt-pr-row">
		<select class="wc-product-search" name="tmt_promo[items][<?php echo esc_attr( $i ); ?>][product]" data-placeholder="Sản phẩm trong bộ..." data-action="woocommerce_json_search_products_and_variations">
			<?php if ( $p ) : ?><option value="<?php echo (int) $p->get_id(); ?>" selected><?php echo esc_html( wp_strip_all_tags( $p->get_formatted_name() ) ); ?></option><?php endif; ?>
		</select>
		<span>số lượng</span>
		<input type="number" min="1" step="1" name="tmt_promo[items][<?php echo esc_attr( $i ); ?>][qty]" value="<?php echo esc_attr( (string) ( isset( $item['qty'] ) ? max( 1, (int) $item['qty'] ) : 1 ) ); ?>">
		<button type="button" class="button-link tmt-pr-x" data-pr-del aria-label="Xóa dòng">×</button>
	</div>
	<?php
}

function tmt_km_meta_box( WP_Post $post ) {
	$r    = tmt_km_rules( $post->ID );
	$cats = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
	$cats = is_array( $cats ) ? $cats : array();
	$used = (int) get_post_meta( $post->ID, '_tmt_promo_used', true );
	wp_nonce_field( 'tmt_promo_save', 'tmt_promo_nonce' );
	?>
	<style>
		.tmt-pr { display: grid; grid-template-columns: 170px 1fr; gap: 12px 16px; align-items: start; max-width: 980px; }
		.tmt-pr > label { font-weight: 600; padding-top: 6px; }
		.tmt-pr .tmt-pr-types { display: flex; flex-wrap: wrap; gap: 8px; }
		.tmt-pr .tmt-pr-types label { border: 1px solid #c3c4c7; border-radius: 6px; padding: 8px 12px; cursor: pointer; max-width: 260px; }
		.tmt-pr .tmt-pr-types label:has(input:checked) { border-color: #2271b1; box-shadow: 0 0 0 1px #2271b1; }
		.tmt-pr .tmt-pr-types small { display: block; color: #646970; }
		.tmt-pr-rows { display: grid; gap: 6px; margin-bottom: 6px; }
		.tmt-pr-row { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
		.tmt-pr-row .select2-container { min-width: 260px !important; }
		.tmt-pr-row input[type=number] { width: 120px; }
		.tmt-pr-x { font-size: 20px; color: #b32d2e; text-decoration: none; }
		.tmt-pr [data-for] { display: contents; }
		.tmt-pr p.description { margin: 4px 0 0; }
		@media (max-width: 782px) { .tmt-pr { grid-template-columns: 1fr; } }
	</style>
	<div class="tmt-pr" id="tmt-promo-ed">
		<label>Loại</label>
		<div class="tmt-pr-types">
			<?php foreach ( tmt_km_types() as $k => $t ) : ?>
				<label><input type="radio" name="tmt_promo[type]" value="<?php echo esc_attr( $k ); ?>" <?php checked( $r['type'], $k ); ?>> <b><?php echo esc_html( $t[0] ); ?></b><small><?php echo esc_html( $t[1] ); ?></small></label>
			<?php endforeach; ?>
		</div>

		<div data-for="flash group">
			<label>Giá khuyến mãi</label>
			<div data-pr-list>
				<div class="tmt-pr-rows">
					<?php foreach ( array_values( (array) $r['rows'] ) as $i => $row ) : ?><?php tmt_km_row_form( 'r' . $i, (array) $row, $cats ); ?><?php endforeach; ?>
				</div>
				<template><?php tmt_km_row_form( '__i__', array(), $cats ); ?></template>
				<button type="button" class="button" data-pr-add>+ Thêm dòng</button>
				<p class="description">Mỗi dòng: một sản phẩm (giá cố định hoặc %) hoặc cả nhóm hàng (chỉ %). % tính trên giá gốc, làm tròn nghìn. Dòng sản phẩm thắng dòng nhóm hàng.</p>
			</div>
		</div>

		<div data-for="group">
			<label>Nhóm khách</label>
			<div>
				<?php foreach ( tmt_customer_groups() as $k => $label ) : ?>
					<label style="margin-right:16px"><input type="checkbox" name="tmt_promo[groups][]" value="<?php echo esc_attr( $k ); ?>" <?php checked( in_array( $k, (array) $r['groups'], true ) ); ?>> <?php echo esc_html( $label ); ?></label>
				<?php endforeach; ?>
				<p class="description">Đặt nhóm cho khách ở Người dùng → Sửa → "Nhóm khách". Khách phải đăng nhập mới thấy giá nhóm.</p>
			</div>
		</div>

		<div data-for="combo">
			<label>Các món trong bộ</label>
			<div data-pr-list>
				<div class="tmt-pr-rows">
					<?php foreach ( array_values( (array) $r['items'] ) as $i => $item ) : ?><?php tmt_km_item_form( 'r' . $i, (array) $item ); ?><?php endforeach; ?>
				</div>
				<template><?php tmt_km_item_form( '__i__', array() ); ?></template>
				<button type="button" class="button" data-pr-add>+ Thêm món</button>
			</div>
			<label for="tmt-promo-amount">Giảm mỗi bộ</label>
			<div><input type="number" min="0" step="1000" id="tmt-promo-amount" name="tmt_promo[amount]" value="<?php echo esc_attr( (string) $r['amount'] ); ?>"> ₫ <p class="description">Trừ vào giỏ khi khách mua đủ các món, mua 2 bộ giảm 2 lần.</p></div>
		</div>

		<label for="tmt-promo-starts">Bắt đầu</label>
		<div><input type="datetime-local" id="tmt-promo-starts" name="tmt_promo[starts]" value="<?php echo esc_attr( str_replace( ' ', 'T', $r['starts'] ) ); ?>"> <span class="description">Trống = ngay khi đăng</span></div>
		<label for="tmt-promo-ends">Kết thúc</label>
		<div><input type="datetime-local" id="tmt-promo-ends" name="tmt_promo[ends]" value="<?php echo esc_attr( str_replace( ' ', 'T', $r['ends'] ) ); ?>"> <span class="description">Giá KM có hạn hiện đếm ngược tới giờ này</span></div>
		<label for="tmt-promo-limit">Số suất</label>
		<div><input type="number" min="1" step="1" id="tmt-promo-limit" name="tmt_promo[limit]" value="<?php echo esc_attr( (string) $r['limit'] ); ?>"> <span class="description">Số cái bán giá KM (combo: số bộ). Trống = không giới hạn. Đã dùng: <?php echo (int) $used; ?></span></div>
	</div>
	<script>
		jQuery(($) => {
			const box = document.getElementById('tmt-promo-ed');
			const sync = () => {
				const type = box.querySelector('input[name="tmt_promo[type]"]:checked')?.value || 'flash';
				box.querySelectorAll('[data-for]').forEach((el) => { el.style.display = el.dataset.for.split(' ').includes(type) ? 'contents' : 'none'; });
			};
			box.addEventListener('change', (e) => { if (e.target.name === 'tmt_promo[type]') sync(); });
			box.addEventListener('click', (e) => {
				const t = e.target.closest('button'); if (!t) return;
				if (t.matches('[data-pr-add]')) {
					const list = t.closest('[data-pr-list]');
					list.querySelector('.tmt-pr-rows').insertAdjacentHTML('beforeend', list.querySelector('template').innerHTML.replace(/__i__/g, 'n' + Date.now()));
					$(document.body).trigger('wc-enhanced-select-init');
				} else if (t.matches('[data-pr-del]')) {
					t.closest('.tmt-pr-row').remove();
				}
			});
			sync();
		});
	</script>
	<?php
}

add_action(
	'save_post_tmt_promo',
	function ( $post_id ) {
		if ( ! isset( $_POST['tmt_promo_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tmt_promo_nonce'] ) ), 'tmt_promo_save' ) || ! current_user_can( 'manage_woocommerce' ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		$in   = isset( $_POST['tmt_promo'] ) && is_array( $_POST['tmt_promo'] ) ? wp_unslash( $_POST['tmt_promo'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$time = function ( $v ) {
			$v = str_replace( 'T', ' ', sanitize_text_field( (string) $v ) );
			return preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $v ) ? $v : '';
		};
		$type = isset( $in['type'] ) && array_key_exists( $in['type'], tmt_km_types() ) ? $in['type'] : 'flash';
		$rows = array();
		foreach ( isset( $in['rows'] ) && is_array( $in['rows'] ) ? $in['rows'] : array() as $row ) {
			$x = array(
				'product'  => absint( isset( $row['product'] ) ? $row['product'] : 0 ),
				'category' => absint( isset( $row['category'] ) ? $row['category'] : 0 ),
				'price'    => isset( $row['price'] ) && '' !== trim( (string) $row['price'] ) ? max( 0, (int) $row['price'] ) : '',
				'percent'  => isset( $row['percent'] ) ? max( 0, min( 100, (float) $row['percent'] ) ) : 0,
			);
			if ( $x['product'] ) {
				$x['category'] = 0;
			} else {
				$x['price'] = ''; // Nhóm hàng chỉ giảm %.
			}
			if ( ( $x['product'] || $x['category'] ) && ( '' !== $x['price'] || $x['percent'] > 0 ) ) {
				$rows[] = $x;
			}
		}
		$items = array();
		foreach ( isset( $in['items'] ) && is_array( $in['items'] ) ? $in['items'] : array() as $item ) {
			$pid = absint( isset( $item['product'] ) ? $item['product'] : 0 );
			if ( $pid ) {
				$items[] = array( 'product' => $pid, 'qty' => max( 1, (int) ( isset( $item['qty'] ) ? $item['qty'] : 1 ) ) );
			}
		}
		$groups = array_values( array_intersect( array_keys( tmt_customer_groups() ), array_map( 'strval', isset( $in['groups'] ) ? (array) $in['groups'] : array() ) ) );
		update_post_meta(
			$post_id,
			'_tmt_promo',
			array(
				'type'   => $type,
				'rows'   => 'combo' === $type ? array() : $rows,
				'groups' => 'group' === $type ? $groups : array(),
				'items'  => 'combo' === $type ? $items : array(),
				'amount' => 'combo' === $type ? max( 0, (int) ( isset( $in['amount'] ) ? $in['amount'] : 0 ) ) : 0,
				'starts' => $time( isset( $in['starts'] ) ? $in['starts'] : '' ),
				'ends'   => $time( isset( $in['ends'] ) ? $in['ends'] : '' ),
				'limit'  => isset( $in['limit'] ) && '' !== trim( (string) $in['limit'] ) ? max( 1, (int) $in['limit'] ) : '',
			)
		);
		update_option( 'tmt_promo_ver', (string) time() );
		delete_transient( 'wc_products_onsale' );
	}
);
add_action( 'trashed_post', fn( $id ) => 'tmt_promo' === get_post_type( $id ) && update_option( 'tmt_promo_ver', (string) time() ) );

// Cột danh sách.
add_filter(
	'manage_tmt_promo_posts_columns',
	function ( $cols ) {
		return array(
			'cb'        => $cols['cb'],
			'title'     => 'Chương trình',
			'tmt_type'  => 'Loại',
			'tmt_scope' => 'Áp dụng',
			'tmt_time'  => 'Thời gian',
			'tmt_left'  => 'Suất',
		);
	}
);
add_action(
	'manage_tmt_promo_posts_custom_column',
	function ( $col, $id ) {
		$r = tmt_km_rules( $id );
		if ( 'tmt_type' === $col ) {
			echo esc_html( tmt_km_types()[ $r['type'] ][0] );
			if ( 'group' === $r['type'] && $r['groups'] ) {
				echo '<br><small>' . esc_html( implode( ', ', array_map( fn( $g ) => tmt_customer_groups()[ $g ], $r['groups'] ) ) ) . '</small>';
			}
		} elseif ( 'tmt_scope' === $col ) {
			echo esc_html( 'combo' === $r['type'] ? count( $r['items'] ) . ' món, giảm ' . wp_strip_all_tags( wc_price( $r['amount'] ) ) . ' / bộ' : count( $r['rows'] ) . ' dòng giá' );
		} elseif ( 'tmt_time' === $col ) {
			echo esc_html( ( $r['starts'] ? $r['starts'] : '…' ) . ' → ' . ( $r['ends'] ? $r['ends'] : '…' ) );
		} elseif ( 'tmt_left' === $col ) {
			$left = tmt_km_remaining( $id );
			echo esc_html( null === $left ? 'Không giới hạn' : $left . ' / ' . (int) $r['limit'] );
		}
	},
	10,
	2
);

// Nhóm khách trên trang sửa người dùng.
function tmt_km_user_field( $user ) {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	$g = (string) get_user_meta( $user->ID, 'tmt_group', true );
	?>
	<h2>Nhóm khách</h2>
	<table class="form-table"><tr><th><label for="tmt-group">Nhóm</label></th><td>
		<select id="tmt-group" name="tmt_group">
			<option value="">Khách thường</option>
			<?php foreach ( tmt_customer_groups() as $k => $label ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $g, $k ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
		</select>
		<p class="description">Khách đăng nhập sẽ thấy giá của chương trình "Giá nhóm khách" dành cho nhóm này.</p>
	</td></tr></table>
	<?php
}
add_action( 'show_user_profile', 'tmt_km_user_field' );
add_action( 'edit_user_profile', 'tmt_km_user_field' );
function tmt_km_user_save( $user_id ) {
	// phpcs:ignore WordPress.Security.NonceVerification -- WordPress đã kiểm nonce form hồ sơ.
	if ( ! current_user_can( 'manage_woocommerce' ) || ! isset( $_POST['tmt_group'] ) ) {
		return;
	}
	$g = sanitize_key( wp_unslash( $_POST['tmt_group'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	if ( isset( tmt_customer_groups()[ $g ] ) ) {
		update_user_meta( $user_id, 'tmt_group', $g );
	} else {
		delete_user_meta( $user_id, 'tmt_group' );
	}
}
add_action( 'personal_options_update', 'tmt_km_user_save' );
add_action( 'edit_user_profile_update', 'tmt_km_user_save' );

// Cột "Nhóm khách" ở danh sách người dùng.
add_filter( 'manage_users_columns', fn( $c ) => $c + array( 'tmt_group' => 'Nhóm khách' ) );
add_filter(
	'manage_users_custom_column',
	function ( $out, $col, $uid ) {
		if ( 'tmt_group' !== $col ) {
			return $out;
		}
		$g = (string) get_user_meta( $uid, 'tmt_group', true );
		return isset( tmt_customer_groups()[ $g ] ) ? esc_html( tmt_customer_groups()[ $g ] ) : '';
	},
	10,
	3
);
