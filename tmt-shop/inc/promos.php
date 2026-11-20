<?php
/**
 * Banner khuyến mãi trang chi tiết sản phẩm (Tùy biến → Banner khuyến mãi): hộp ưu đãi, dải đếm ngược, huy hiệu, ảnh, mã giảm giá.
 * Mỗi banner có vị trí, màu, phạm vi (mọi sản phẩm / danh mục / sản phẩm) và lịch chạy. In bằng tmt_promos( 'vi-tri' ).
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;

/** Bộ màu: [màu chính, màu phụ (chuyển sắc), nền nhạt, viền, chữ trên màu chính, chữ trên nền nhạt]. */
function tmt_promo_styles() {
	return array(
		'red'    => array( '#D32F2F', '#FF6B3D', '#FFF4F1', '#FFC9BC', '#FFFFFF', '#7A1F12' ),
		'yellow' => array( '#FDDB4C', '#FFE98A', '#FFFBEA', '#F6E39A', '#1A2B4C', '#5B4A0E' ),
		'blue'   => array( '#3271E0', '#5B93F0', '#EEF3FD', '#C6D8F8', '#FFFFFF', '#1A2B4C' ),
		'dark'   => array( '#1A2B4C', '#2E4677', '#F1F3F8', '#CDD4E2', '#FDDB4C', '#1A2B4C' ),
	);
}

/** Thời điểm nhập ở Tùy biến (giờ của trang web) thành timestamp, null khi trống / sai. */
function tmt_promo_time( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return null;
	}
	try {
		return ( new DateTime( $value, wp_timezone() ) )->getTimestamp();
	} catch ( Exception $e ) {
		return null;
	}
}

/** Banner có áp dụng cho sản phẩm này không. */
function tmt_promo_applies( array $p, WC_Product $product ) {
	$targets = array_filter( array_map( 'trim', explode( ',', (string) ( isset( $p['targets'] ) ? $p['targets'] : '' ) ) ) );
	$scope   = isset( $p['scope'] ) ? $p['scope'] : 'all';
	if ( 'all' === $scope ) {
		return true;
	}
	if ( ! $targets ) {
		return false;
	}
	if ( 'categories' === $scope ) {
		$slugs = array();
		foreach ( (array) get_the_terms( $product->get_id(), 'product_cat' ) as $t ) {
			if ( $t instanceof WP_Term ) {
				$slugs[] = $t->slug;
				foreach ( get_ancestors( $t->term_id, 'product_cat' ) as $a ) {
					$slugs[] = get_term( $a, 'product_cat' )->slug;
				}
			}
		}
		return (bool) array_intersect( array_map( 'sanitize_title', $targets ), $slugs );
	}
	return in_array( (string) $product->get_id(), $targets, true ) || ( $product->get_sku() && in_array( $product->get_sku(), $targets, true ) );
}

/** Thoát HTML rồi đổi **chữ** thành chữ đậm. */
function tmt_promo_bold( $line ) {
	return preg_replace( '/\*\*(.+?)\*\*/u', '<b>$1</b>', esc_html( $line ) );
}

/** Icon huy hiệu đoán theo chữ (trả góp, giao hàng, bảo hành...). */
function tmt_promo_chip_icon( $text ) {
	$t = mb_strtolower( $text );
	foreach ( array( 'góp' => 'fa-percent', 'giao' => 'fa-truck', 'ship' => 'fa-truck', 'bảo hành' => 'fa-shield-halved', 'đổi' => 'fa-rotate-left', 'tặng' => 'fa-gift', 'quà' => 'fa-gift', 'cài' => 'fa-screwdriver-wrench', 'giảm' => 'fa-tag', 'chính hãng' => 'fa-certificate', 'hóa đơn' => 'fa-file-invoice', 'vệ sinh' => 'fa-broom' ) as $word => $icon ) {
		if ( false !== strpos( $t, $word ) ) {
			return 'fa-solid ' . $icon;
		}
	}
	return 'fa-solid fa-circle-check';
}

/**
 * Banner đang chạy của sản phẩm đang xem, theo vị trí (tính một lần mỗi trang).
 *
 * @return array<string, array[]>
 */
function tmt_product_promos( WC_Product $product ) {
	static $cache = array();
	if ( isset( $cache[ $product->get_id() ] ) ) {
		return $cache[ $product->get_id() ];
	}
	$now    = time();
	$styles = tmt_promo_styles();
	$out    = array();
	foreach ( tmt_opt_list( 'product.promos' ) as $p ) {
		if ( ! is_array( $p ) || empty( $p['on'] ) ) {
			continue;
		}
		$type = isset( $p['type'] ) && isset( tmt_promo_types()[ $p['type'] ] ) ? $p['type'] : 'box';
		$pos  = isset( $p['position'] ) && isset( tmt_promo_positions()[ $p['position'] ] ) ? $p['position'] : 'below_price';
		$from = tmt_promo_time( isset( $p['from'] ) ? $p['from'] : '' );
		$to   = tmt_promo_time( isset( $p['to'] ) ? $p['to'] : '' );
		if ( ( $from && $from > $now ) || ( $to && $to <= $now ) || ! tmt_promo_applies( $p, $product ) ) {
			continue;
		}
		$lines = array_values( array_filter( array_map( 'trim', explode( "\n", (string) ( isset( $p['lines'] ) ? $p['lines'] : '' ) ) ) ) );
		if ( ( 'image' === $type && empty( $p['image'] ) ) || ( 'coupon' === $type && empty( $p['code'] ) ) || ( in_array( $type, array( 'box', 'badges' ), true ) && ! $lines && empty( $p['title'] ) ) ) {
			continue;
		}
		$c          = isset( $styles[ isset( $p['style'] ) ? $p['style'] : '' ] ) ? $styles[ $p['style'] ] : $styles['red'];
		$link       = isset( $p['link'] ) ? tmt_item_href( $p['link'] ) : null;
		$out[ $pos ][] = array(
			'type'      => $type,
			'title'     => isset( $p['title'] ) ? (string) $p['title'] : '',
			'text'      => isset( $p['text'] ) ? (string) $p['text'] : '',
			'lines'     => $lines,
			'code'      => isset( $p['code'] ) ? strtoupper( (string) $p['code'] ) : '',
			'image'     => isset( $p['image'] ) ? (string) $p['image'] : '',
			'link'      => $link,
			'link_text' => isset( $p['link_text'] ) ? (string) $p['link_text'] : '',
			'ends_at'   => $to && ! empty( $p['countdown'] ) ? gmdate( 'c', $to ) : '',
			'vars'      => "--pa:{$c[0]};--pb:{$c[1]};--pbg:{$c[2]};--pline:{$c[3]};--pink:{$c[4]};--ptext:{$c[5]}",
			'icon'      => 'strip' === $type ? 'fa-solid fa-bolt' : ( 'coupon' === $type ? 'fa-solid fa-ticket' : 'fa-solid fa-gift' ),
		);
	}
	$cache[ $product->get_id() ] = $out;
	return $out;
}

/** In banner của một vị trí trên trang sản phẩm. */
function tmt_promos( $at ) {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$all = tmt_product_promos( $product );
	if ( ! empty( $all[ $at ] ) ) {
		get_template_part( 'template-parts/product/promos', null, array( 'at' => $at, 'items' => $all[ $at ] ) );
	}
}
