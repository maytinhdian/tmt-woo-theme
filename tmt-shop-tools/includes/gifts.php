<?php
/**
 * Quà tặng kèm (chép ý từ module Promotions của app, loại "Tặng kèm").
 *
 * Mỗi chương trình là một bài CPT tmt_gift (WooCommerce → Quà tặng kèm), cấu hình trong meta _tmt_gift:
 *   target   = order (mọi đơn) | products (các sản phẩm) | categories (các nhóm hàng, tính cả nhóm con)
 *   products / categories = ID, min_price = giá mỗi món từ, min_qty = mua từ mấy cái, min_order = đơn từ bao nhiêu
 *   gifts    = ID sản phẩm / biến thể làm quà, pick = all (tặng hết) | one (khách chọn 1)
 *   starts / ends = 'Y-m-d H:i' giờ của web, limit = số đơn được nhận quà (trống = không giới hạn)
 * Số quà = số món khớp (target order: 1). Đã dùng: meta _tmt_gift_used (đếm 1 lần mỗi đơn, trả lại khi đơn hủy).
 *
 * Giỏ: trước khi tính tiền, tmt_gift_sync() thêm / bớt dòng quà (cart item tmt_gift = ID chương trình, giá 0).
 * Khách chọn quà khác bằng link ?tmt-qua=<chương trình>-<sản phẩm>. Theme gọi tmt_gift_box() ở trang sản phẩm và
 * tmt_gift_line() ở từng dòng giỏ.
 *
 * @package TMT_Shop_Tools
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	function () {
		$cap = 'manage_woocommerce';
		register_post_type(
			'tmt_gift',
			array(
				'labels'       => array(
					'name'          => 'Quà tặng kèm',
					'singular_name' => 'Chương trình quà tặng',
					'menu_name'     => 'Quà tặng kèm',
					'all_items'     => 'Quà tặng kèm',
					'add_new'       => 'Thêm chương trình',
					'add_new_item'  => 'Thêm chương trình quà tặng',
					'edit_item'     => 'Sửa chương trình quà tặng',
					'search_items'  => 'Tìm chương trình',
					'not_found'     => 'Chưa có chương trình quà tặng nào.',
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

/** Cấu hình một chương trình, đủ khóa. */
function tmt_gift_rules( $id ) {
	$r = get_post_meta( $id, '_tmt_gift', true );
	return wp_parse_args(
		is_array( $r ) ? $r : array(),
		array(
			'target'     => 'products',
			'products'   => array(),
			'categories' => array(),
			'min_price'  => 0,
			'min_qty'    => 1,
			'min_order'  => 0,
			'gifts'      => array(),
			'pick'       => 'all',
			'starts'     => '',
			'ends'       => '',
			'limit'      => '',
		)
	);
}

/** Số đơn còn được nhận quà, null = không giới hạn. */
function tmt_gift_remaining( $id ) {
	$r = tmt_gift_rules( $id );
	if ( '' === (string) $r['limit'] ) {
		return null;
	}
	return max( 0, (int) $r['limit'] - (int) get_post_meta( $id, '_tmt_gift_used', true ) );
}

/** Các chương trình đang chạy: đã đăng, trong thời gian, còn suất. [id => rules + name]. */
function tmt_gift_programs() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$cache = array();
	$now   = current_time( 'Y-m-d H:i' );
	$ids   = get_posts(
		array(
			'post_type'      => 'tmt_gift',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'fields'         => 'ids',
			'orderby'        => 'date',
			'order'          => 'ASC',
		)
	);
	foreach ( $ids as $id ) {
		$r = tmt_gift_rules( $id );
		if ( ( '' !== $r['starts'] && $now < $r['starts'] ) || ( '' !== $r['ends'] && $now > $r['ends'] ) || 0 === tmt_gift_remaining( $id ) || ! $r['gifts'] ) {
			continue;
		}
		$r['name']    = get_the_title( $id );
		$cache[ $id ] = $r;
	}
	return $cache;
}

/** Quà còn tặng được của chương trình (sản phẩm đăng, có hàng). */
function tmt_gift_options( array $r ) {
	$out = array();
	foreach ( array_map( 'absint', (array) $r['gifts'] ) as $gid ) {
		$g = $gid ? wc_get_product( $gid ) : null;
		if ( $g && $g->is_in_stock() && $g->is_purchasable() && 'publish' === get_post_status( $g->get_parent_id() ? $g->get_parent_id() : $g->get_id() ) && ! $g->is_type( array( 'variable', 'grouped', 'external' ) ) ) {
			$out[ $gid ] = $g;
		}
	}
	return $out;
}

/** Sản phẩm (hoặc biến thể) có thuộc phạm vi chương trình không. */
function tmt_gift_matches( array $r, WC_Product $product ) {
	if ( (float) $product->get_price() < (float) $r['min_price'] || '' === $product->get_price() ) {
		return false;
	}
	$pid = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
	if ( 'order' === $r['target'] ) {
		return true;
	}
	if ( 'products' === $r['target'] ) {
		$ids = array_map( 'absint', (array) $r['products'] );
		return in_array( $pid, $ids, true ) || in_array( $product->get_id(), $ids, true );
	}
	$chain = array();
	foreach ( wc_get_product_term_ids( $pid, 'product_cat' ) as $tid ) {
		$chain[] = (int) $tid;
		foreach ( get_ancestors( $tid, 'product_cat', 'taxonomy' ) as $a ) {
			$chain[] = (int) $a;
		}
	}
	return (bool) array_intersect( $chain, array_map( 'absint', (array) $r['categories'] ) );
}

/** Dòng mô tả điều kiện, ví dụ "mua từ 2 cái, đơn từ 5.000.000 ₫". */
function tmt_gift_conditions( array $r ) {
	$c = array();
	if ( (int) $r['min_qty'] > 1 ) {
		$c[] = 'mua từ ' . (int) $r['min_qty'] . ' cái';
	}
	if ( (float) $r['min_order'] > 0 ) {
		$c[] = 'đơn từ ' . wp_strip_all_tags( wc_price( $r['min_order'] ) );
	}
	return implode( ', ', $c );
}

/** Ưu đãi quà của một sản phẩm cho trang chi tiết: [[name, text, options => [WC_Product]]]. */
function tmt_gift_offers( WC_Product $product ) {
	$out = array();
	foreach ( tmt_gift_programs() as $r ) {
		$options = tmt_gift_options( $r );
		$match   = $product->is_type( 'variable' )
			? (bool) array_filter( array_map( 'wc_get_product', $product->get_children() ), fn( $v ) => $v && tmt_gift_matches( $r, $v ) )
			: tmt_gift_matches( $r, $product );
		if ( ! $options || ! $match ) {
			continue;
		}
		$cond  = tmt_gift_conditions( $r );
		$names = implode( ', ', array_map( fn( $g ) => $g->get_name(), $options ) );
		$out[] = array(
			'name'    => $r['name'],
			'text'    => ( 'one' === $r['pick'] && count( $options ) > 1 ? 'Chọn 1 quà: ' : 'Tặng ' ) . $names . ( $cond ? ' (' . $cond . ')' : '' ),
			'options' => $options,
		);
	}
	return $out;
}

/** Hộp quà tặng trên trang sản phẩm (dưới giá). */
function tmt_gift_box( WC_Product $product ) {
	$offers = tmt_gift_offers( $product );
	if ( ! $offers ) {
		return;
	}
	echo '<div class="sf-deals">';
	foreach ( $offers as $o ) {
		printf(
			'<div class="sf-deal is-gift"><i class="fa-solid fa-gift" aria-hidden="true"></i><div><b>%s</b><span>%s</span></div></div>',
			esc_html( $o['name'] ),
			esc_html( $o['text'] )
		);
	}
	echo '</div>';
}

/**
 * Thêm / bớt dòng quà trong giỏ cho khớp chương trình đang chạy. Gọi trước khi tính tiền;
 * dòng quà có giá 0, không tính vào điều kiện của chương trình khác.
 */
function tmt_gift_sync( $cart ) {
	static $busy = false;
	if ( $busy || ! $cart instanceof WC_Cart || ( is_admin() && ! wp_doing_ajax() ) ) {
		return;
	}
	$busy  = true;
	$want  = array(); // "program:gift" => qty
	$picks = WC()->session ? (array) WC()->session->get( 'tmt_gift_pick', array() ) : array();
	$items = array_filter( $cart->get_cart(), fn( $i ) => empty( $i['tmt_gift'] ) );
	$total = 0;
	foreach ( $items as $i ) {
		$total += (float) $i['data']->get_price() * (int) $i['quantity'];
	}
	foreach ( tmt_gift_programs() as $id => $r ) {
		if ( $total <= 0 || $total < (float) $r['min_order'] ) {
			continue;
		}
		$units = 0;
		foreach ( $items as $i ) {
			if ( tmt_gift_matches( $r, $i['data'] ) ) {
				$units += (int) $i['quantity'];
			}
		}
		if ( 'order' === $r['target'] ) {
			$units = $units > 0 ? 1 : 0;
		} elseif ( $units < max( 1, (int) $r['min_qty'] ) ) {
			$units = 0;
		}
		$options = tmt_gift_options( $r );
		if ( ! $units || ! $options ) {
			continue;
		}
		if ( 'one' === $r['pick'] ) {
			$pick    = isset( $picks[ $id ], $options[ (int) $picks[ $id ] ] ) ? (int) $picks[ $id ] : (int) array_key_first( $options );
			$options = array( $pick => $options[ $pick ] );
		}
		foreach ( $options as $gid => $g ) {
			$qty = $units;
			if ( $g->managing_stock() && ! $g->backorders_allowed() ) {
				$qty = min( $qty, max( 0, (int) $g->get_stock_quantity() ) );
			}
			if ( $qty > 0 ) {
				$want[ $id . ':' . $gid ] = $qty;
			}
		}
	}
	// Bỏ / chỉnh dòng quà đang có.
	foreach ( $cart->get_cart() as $key => $i ) {
		if ( empty( $i['tmt_gift'] ) ) {
			continue;
		}
		$k = (int) $i['tmt_gift'] . ':' . (int) ( $i['variation_id'] ? $i['variation_id'] : $i['product_id'] );
		if ( ! isset( $want[ $k ] ) ) {
			$cart->remove_cart_item( $key );
		} else {
			if ( (int) $i['quantity'] !== $want[ $k ] ) {
				$cart->set_quantity( $key, $want[ $k ], false );
			}
			unset( $want[ $k ] );
		}
	}
	// Thêm dòng quà còn thiếu.
	foreach ( $want as $k => $qty ) {
		list( $id, $gid ) = array_map( 'intval', explode( ':', $k ) );
		$g                = wc_get_product( $gid );
		$data             = array( 'tmt_gift' => $id );
		if ( $g->is_type( 'variation' ) ) {
			$cart->add_to_cart( $g->get_parent_id(), $qty, $gid, $g->get_variation_attributes(), $data );
		} else {
			$cart->add_to_cart( $gid, $qty, 0, array(), $data );
		}
	}
	// Quà có giá 0.
	foreach ( $cart->get_cart() as $i ) {
		if ( ! empty( $i['tmt_gift'] ) ) {
			$i['data']->set_price( 0 );
		}
	}
	$busy = false;
}
add_action( 'woocommerce_before_calculate_totals', 'tmt_gift_sync', 5 );

// Quà tặng được thêm khi sản phẩm gốc bị bỏ qua giới hạn "chỉ bán 1" và luôn là dòng riêng.
add_filter(
	'woocommerce_add_cart_item_data',
	function ( $data ) {
		if ( ! empty( $data['tmt_gift'] ) ) {
			$data['tmt_gift'] = absint( $data['tmt_gift'] );
		}
		return $data;
	},
	5
);

// Giữ đánh dấu quà khi nạp giỏ từ phiên; bỏ dòng quà lạ (chương trình đã xóa).
add_filter(
	'woocommerce_get_cart_item_from_session',
	function ( $item, $values ) {
		if ( ! empty( $values['tmt_gift'] ) ) {
			$item['tmt_gift'] = absint( $values['tmt_gift'] );
			$item['data']->set_price( 0 );
		}
		return $item;
	},
	10,
	2
);

// Khách không tự đổi số lượng / bỏ dòng quà (sau lần tính tiền kế tiếp nó tự về đúng).
add_filter(
	'woocommerce_cart_item_remove_link',
	function ( $link, $key ) {
		$item = WC()->cart->get_cart_item( $key );
		return ! empty( $item['tmt_gift'] ) ? '' : $link;
	},
	10,
	2
);

/**
 * Thông tin dòng quà cho theme: null nếu không phải quà; ngược lại [name => tên chương trình, regular => giá gốc
 * hiển thị, choices => [[name, url, on]] khi khách được chọn 1 trong nhiều quà].
 */
function tmt_gift_line( array $item ) {
	if ( empty( $item['tmt_gift'] ) ) {
		return null;
	}
	$id       = (int) $item['tmt_gift'];
	$programs = tmt_gift_programs();
	$r        = isset( $programs[ $id ] ) ? $programs[ $id ] : null;
	$current  = (int) ( $item['variation_id'] ? $item['variation_id'] : $item['product_id'] );
	$choices  = array();
	if ( $r && 'one' === $r['pick'] ) {
		$options = tmt_gift_options( $r );
		if ( count( $options ) > 1 ) {
			foreach ( $options as $gid => $g ) {
				$choices[] = array(
					'name' => $g->get_name(),
					'url'  => add_query_arg( 'tmt-qua', $id . '-' . $gid, wc_get_checkout_url() ),
					'on'   => $gid === $current,
				);
			}
		}
	}
	$p = wc_get_product( $current );
	return array(
		'name'    => $r ? $r['name'] : get_the_title( $id ),
		'regular' => $p ? wc_get_price_to_display( $p, array( 'price' => $p->get_regular_price() ? $p->get_regular_price() : $p->get_price(), 'qty' => (int) $item['quantity'] ) ) : 0,
		'choices' => $choices,
	);
}

// Khách đổi quà (chương trình "chọn 1").
add_action(
	'wp_loaded',
	function () {
		// phpcs:ignore WordPress.Security.NonceVerification -- chỉ đổi lựa chọn quà trong phiên của chính khách.
		$raw = isset( $_GET['tmt-qua'] ) ? sanitize_text_field( wp_unslash( $_GET['tmt-qua'] ) ) : '';
		if ( ! preg_match( '/^(\d+)-(\d+)$/', $raw, $m ) || ! WC()->session || ! WC()->cart ) {
			return;
		}
		$programs = tmt_gift_programs();
		if ( isset( $programs[ (int) $m[1] ] ) && isset( tmt_gift_options( $programs[ (int) $m[1] ] )[ (int) $m[2] ] ) ) {
			$picks                = (array) WC()->session->get( 'tmt_gift_pick', array() );
			$picks[ (int) $m[1] ] = (int) $m[2];
			WC()->session->set( 'tmt_gift_pick', $picks );
			WC()->cart->calculate_totals();
		}
		wp_safe_redirect( remove_query_arg( 'tmt-qua' ) );
		exit;
	},
	30
);

// Ghi vào dòng đơn hàng: "Quà tặng: <tên chương trình>".
add_action(
	'woocommerce_checkout_create_order_line_item',
	function ( $line, $key, $values ) {
		if ( ! empty( $values['tmt_gift'] ) ) {
			$line->add_meta_data( '_tmt_gift', (int) $values['tmt_gift'], true );
			$line->add_meta_data( 'Quà tặng', get_the_title( (int) $values['tmt_gift'] ), true );
		}
	},
	10,
	3
);

/** Đếm suất đã dùng: +1 mỗi chương trình có trong đơn mới, -1 khi đơn bị hủy / thất bại / hoàn tiền. */
function tmt_gift_count_order( $order, $delta ) {
	$order = $order instanceof WC_Order ? $order : wc_get_order( $order );
	if ( ! $order ) {
		return;
	}
	$counted = 'yes' === $order->get_meta( '_tmt_gift_counted' );
	if ( ( $delta > 0 && $counted ) || ( $delta < 0 && ! $counted ) ) {
		return;
	}
	$ids = array();
	foreach ( $order->get_items() as $line ) {
		$pid = (int) $line->get_meta( '_tmt_gift' );
		if ( $pid ) {
			$ids[ $pid ] = true;
		}
	}
	foreach ( array_keys( $ids ) as $pid ) {
		update_post_meta( $pid, '_tmt_gift_used', max( 0, (int) get_post_meta( $pid, '_tmt_gift_used', true ) + $delta ) );
	}
	if ( $ids ) {
		$order->update_meta_data( '_tmt_gift_counted', $delta > 0 ? 'yes' : 'no' );
		$order->save_meta_data();
	}
}
add_action( 'woocommerce_checkout_order_created', fn( $order ) => tmt_gift_count_order( $order, 1 ) );
foreach ( array( 'cancelled', 'failed', 'refunded' ) as $tmt_status ) {
	add_action( 'woocommerce_order_status_' . $tmt_status, fn( $id ) => tmt_gift_count_order( $id, -1 ) );
}
unset( $tmt_status );

/* ---------- Quản trị ---------- */

// Ô chọn sản phẩm / nhóm hàng của WooCommerce (select2) chạy trên màn hình sửa chương trình.
add_filter(
	'woocommerce_screen_ids',
	function ( $ids ) {
		$ids[] = 'tmt_gift';
		$ids[] = 'edit-tmt_gift';
		return $ids;
	}
);

add_action(
	'add_meta_boxes_tmt_gift',
	function () {
		add_meta_box( 'tmt-gift', 'Điều kiện và quà', 'tmt_gift_meta_box', 'tmt_gift', 'normal', 'high' );
	}
);

/** Các option của ô chọn sản phẩm đã lưu. */
function tmt_gift_product_options( array $ids ) {
	foreach ( array_map( 'absint', $ids ) as $id ) {
		$p = $id ? wc_get_product( $id ) : null;
		if ( $p ) {
			printf( '<option value="%d" selected>%s</option>', (int) $id, esc_html( wp_strip_all_tags( $p->get_formatted_name() ) ) );
		}
	}
}

function tmt_gift_meta_box( WP_Post $post ) {
	$r    = tmt_gift_rules( $post->ID );
	$cats = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
	$used = (int) get_post_meta( $post->ID, '_tmt_gift_used', true );
	wp_nonce_field( 'tmt_gift_save', 'tmt_gift_nonce' );
	?>
	<style>
		.tmt-gift-f { display: grid; grid-template-columns: 180px 1fr; gap: 12px 16px; align-items: center; max-width: 900px; }
		.tmt-gift-f label { font-weight: 600; }
		.tmt-gift-f .select2-container { min-width: 100% !important; }
		.tmt-gift-f input[type=number] { width: 160px; }
		.tmt-gift-f .row-products, .tmt-gift-f .row-categories { display: contents; }
		.tmt-gift-f p.description { margin: 2px 0 0; }
		@media (max-width: 782px) { .tmt-gift-f { grid-template-columns: 1fr; } }
	</style>
	<div class="tmt-gift-f">
		<label for="tmt-gift-target">Áp dụng khi mua</label>
		<select id="tmt-gift-target" name="tmt_gift[target]">
			<?php foreach ( array( 'products' => 'Các sản phẩm chọn bên dưới', 'categories' => 'Sản phẩm trong các nhóm hàng', 'order' => 'Mọi đơn hàng' ) as $k => $label ) : ?>
				<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $r['target'], $k ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<div class="row-products" data-for="products">
			<label for="tmt-gift-products">Sản phẩm</label>
			<select id="tmt-gift-products" class="wc-product-search" multiple name="tmt_gift[products][]" data-placeholder="Tìm sản phẩm..." data-action="woocommerce_json_search_products"><?php tmt_gift_product_options( (array) $r['products'] ); ?></select>
		</div>
		<div class="row-categories" data-for="categories">
			<label for="tmt-gift-cats">Nhóm hàng</label>
			<select id="tmt-gift-cats" class="wc-enhanced-select" multiple name="tmt_gift[categories][]" data-placeholder="Chọn nhóm hàng...">
				<?php foreach ( is_array( $cats ) ? $cats : array() as $t ) : ?>
					<option value="<?php echo (int) $t->term_id; ?>" <?php selected( in_array( (int) $t->term_id, array_map( 'absint', (array) $r['categories'] ), true ) ); ?>><?php echo esc_html( $t->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<label for="tmt-gift-min-price">Giá mỗi món từ</label>
		<div><input type="number" min="0" step="1000" id="tmt-gift-min-price" name="tmt_gift[min_price]" value="<?php echo esc_attr( (string) $r['min_price'] ); ?>"> ₫</div>
		<label for="tmt-gift-min-qty">Mua từ</label>
		<div><input type="number" min="1" step="1" id="tmt-gift-min-qty" name="tmt_gift[min_qty]" value="<?php echo esc_attr( (string) $r['min_qty'] ); ?>"> cái <p class="description">Mua 3 cái được 3 phần quà.</p></div>
		<label for="tmt-gift-min-order">Đơn hàng từ</label>
		<div><input type="number" min="0" step="1000" id="tmt-gift-min-order" name="tmt_gift[min_order]" value="<?php echo esc_attr( (string) $r['min_order'] ); ?>"> ₫</div>
		<label for="tmt-gift-gifts">Quà tặng</label>
		<div>
			<select id="tmt-gift-gifts" class="wc-product-search" multiple name="tmt_gift[gifts][]" data-placeholder="Tìm sản phẩm làm quà..." data-action="woocommerce_json_search_products_and_variations"><?php tmt_gift_product_options( (array) $r['gifts'] ); ?></select>
			<p class="description">Quà là sản phẩm có trong kho (trừ tồn khi khách đặt). Hết hàng thì không tặng.</p>
		</div>
		<label>Khi có nhiều quà</label>
		<div>
			<label style="font-weight:400;margin-right:16px"><input type="radio" name="tmt_gift[pick]" value="all" <?php checked( $r['pick'], 'all' ); ?>> Tặng tất cả</label>
			<label style="font-weight:400"><input type="radio" name="tmt_gift[pick]" value="one" <?php checked( $r['pick'], 'one' ); ?>> Khách chọn 1</label>
		</div>
		<label for="tmt-gift-starts">Bắt đầu</label>
		<div><input type="datetime-local" id="tmt-gift-starts" name="tmt_gift[starts]" value="<?php echo esc_attr( str_replace( ' ', 'T', $r['starts'] ) ); ?>"> <span class="description">Trống = ngay khi đăng</span></div>
		<label for="tmt-gift-ends">Kết thúc</label>
		<div><input type="datetime-local" id="tmt-gift-ends" name="tmt_gift[ends]" value="<?php echo esc_attr( str_replace( ' ', 'T', $r['ends'] ) ); ?>"> <span class="description">Trống = đến khi tắt</span></div>
		<label for="tmt-gift-limit">Số suất</label>
		<div><input type="number" min="1" step="1" id="tmt-gift-limit" name="tmt_gift[limit]" value="<?php echo esc_attr( (string) $r['limit'] ); ?>"> đơn <span class="description">Trống = không giới hạn. Đã dùng: <?php echo (int) $used; ?></span></div>
	</div>
	<script>
		(() => {
			const sel = document.getElementById('tmt-gift-target');
			const sync = () => document.querySelectorAll('.tmt-gift-f [data-for]').forEach((el) => { el.style.display = el.dataset.for === sel.value ? 'contents' : 'none'; });
			sel.addEventListener('change', sync);
			sync();
		})();
	</script>
	<?php
}

add_action(
	'save_post_tmt_gift',
	function ( $post_id ) {
		if ( ! isset( $_POST['tmt_gift_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tmt_gift_nonce'] ) ), 'tmt_gift_save' ) || ! current_user_can( 'manage_woocommerce' ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		$in   = isset( $_POST['tmt_gift'] ) && is_array( $_POST['tmt_gift'] ) ? wp_unslash( $_POST['tmt_gift'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$time = function ( $v ) {
			$v = str_replace( 'T', ' ', sanitize_text_field( (string) $v ) );
			return preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $v ) ? $v : '';
		};
		$ids  = fn( $v ) => array_values( array_unique( array_filter( array_map( 'absint', (array) $v ) ) ) );
		$r    = array(
			'target'     => in_array( isset( $in['target'] ) ? $in['target'] : '', array( 'products', 'categories', 'order' ), true ) ? $in['target'] : 'products',
			'products'   => $ids( isset( $in['products'] ) ? $in['products'] : array() ),
			'categories' => $ids( isset( $in['categories'] ) ? $in['categories'] : array() ),
			'min_price'  => max( 0, (int) ( isset( $in['min_price'] ) ? $in['min_price'] : 0 ) ),
			'min_qty'    => max( 1, (int) ( isset( $in['min_qty'] ) ? $in['min_qty'] : 1 ) ),
			'min_order'  => max( 0, (int) ( isset( $in['min_order'] ) ? $in['min_order'] : 0 ) ),
			'gifts'      => $ids( isset( $in['gifts'] ) ? $in['gifts'] : array() ),
			'pick'       => isset( $in['pick'] ) && 'one' === $in['pick'] ? 'one' : 'all',
			'starts'     => $time( isset( $in['starts'] ) ? $in['starts'] : '' ),
			'ends'       => $time( isset( $in['ends'] ) ? $in['ends'] : '' ),
			'limit'      => isset( $in['limit'] ) && '' !== trim( (string) $in['limit'] ) ? max( 1, (int) $in['limit'] ) : '',
		);
		update_post_meta( $post_id, '_tmt_gift', $r );
	}
);

// Cột danh sách: áp dụng, quà, thời gian, suất.
add_filter(
	'manage_tmt_gift_posts_columns',
	function ( $cols ) {
		return array(
			'cb'        => $cols['cb'],
			'title'     => 'Chương trình',
			'tmt_scope' => 'Áp dụng',
			'tmt_gifts' => 'Quà',
			'tmt_time'  => 'Thời gian',
			'tmt_left'  => 'Suất',
		);
	}
);
add_action(
	'manage_tmt_gift_posts_custom_column',
	function ( $col, $id ) {
		$r = tmt_gift_rules( $id );
		if ( 'tmt_scope' === $col ) {
			$n = 'products' === $r['target'] ? count( $r['products'] ) . ' sản phẩm' : ( 'categories' === $r['target'] ? count( $r['categories'] ) . ' nhóm hàng' : 'Mọi đơn' );
			$c = tmt_gift_conditions( $r );
			echo esc_html( $n . ( $c ? ', ' . $c : '' ) );
		} elseif ( 'tmt_gifts' === $col ) {
			$names = array_filter( array_map( fn( $g ) => ( $p = wc_get_product( $g ) ) ? $p->get_name() : '', (array) $r['gifts'] ) );
			echo esc_html( ( 'one' === $r['pick'] && count( $names ) > 1 ? 'Chọn 1: ' : '' ) . implode( ', ', $names ) );
		} elseif ( 'tmt_time' === $col ) {
			echo esc_html( ( $r['starts'] ? $r['starts'] : '…' ) . ' → ' . ( $r['ends'] ? $r['ends'] : '…' ) );
		} elseif ( 'tmt_left' === $col ) {
			$left = tmt_gift_remaining( $id );
			echo esc_html( null === $left ? 'Không giới hạn' : $left . ' / ' . (int) $r['limit'] );
		}
	},
	10,
	2
);
