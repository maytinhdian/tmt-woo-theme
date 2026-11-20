<?php
/**
 * Dữ liệu cho các section trang chủ.
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;

/** Ảnh slider đã có ảnh (Tùy biến → Trang chủ). */
function tmt_home_slides() {
	$out = array();
	foreach ( tmt_opt_list( 'home.slides' ) as $s ) {
		$img = isset( $s['image'] ) ? esc_url_raw( $s['image'] ) : '';
		if ( '' === $img ) {
			continue;
		}
		$out[] = array(
			'image'        => $img,
			'image_mobile' => isset( $s['image_mobile'] ) ? esc_url_raw( $s['image_mobile'] ) : '',
			'link'         => tmt_item_href( isset( $s['link'] ) ? $s['link'] : '' ),
			'alt'          => isset( $s['alt'] ) ? (string) $s['alt'] : '',
		);
	}
	return $out;
}

/** 2 banner phụ cạnh slider. */
function tmt_home_banners() {
	$out = array();
	foreach ( array_slice( tmt_opt_list( 'home.banners' ), 0, 2 ) as $b ) {
		$img = isset( $b['image'] ) ? esc_url_raw( $b['image'] ) : '';
		if ( '' !== $img ) {
			$out[] = array( 'image' => $img, 'link' => tmt_item_href( isset( $b['link'] ) ? $b['link'] : '' ), 'alt' => isset( $b['alt'] ) ? (string) $b['alt'] : '' );
		}
	}
	return $out;
}

/**
 * Danh mục nổi bật: slug nhập ở Tùy biến, trống thì các danh mục cấp 1.
 *
 * @return WP_Term[]
 */
function tmt_home_categories( $limit = 12 ) {
	$slugs = array_filter( array_map( 'trim', explode( ',', (string) tmt_opt( 'home.categories' ) ) ) );
	if ( ! $slugs ) {
		return array_slice( tmt_cat_tree()['tops'], 0, $limit );
	}
	$out = array();
	foreach ( $slugs as $slug ) {
		$t = get_term_by( 'slug', sanitize_title( $slug ), 'product_cat' );
		if ( $t ) {
			$out[] = $t;
		}
	}
	return array_slice( $out, 0, $limit );
}

/** Hạn khuyến mãi (timestamp) hoặc null; đã qua hạn thì false (ẩn section). */
function tmt_home_sale_ends() {
	$raw = trim( (string) tmt_opt( 'home.sale_ends_at' ) );
	if ( '' === $raw ) {
		return null;
	}
	try {
		$dt = new DateTime( $raw, wp_timezone() );
	} catch ( Exception $e ) {
		return null;
	}
	return $dt->getTimestamp() > time() ? $dt : false;
}

/** Link mở từ mục slider: liên kết ngoài thì mở thẻ mới. */
function tmt_link_attrs( $href ) {
	$out = ' href="' . esc_url( $href ) . '"';
	if ( 0 === strpos( $href, 'http' ) && 0 !== strpos( $href, home_url() ) ) {
		$out .= ' target="_blank" rel="noopener"';
	}
	return $out;
}

/** Trang cửa hàng (danh sách sản phẩm). */
function tmt_shop_url() {
	return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
}

/** Trang Lên cấu hình (plugin TMT Shop Tools gắn vào qua filter), rỗng khi chưa có. */
function tmt_builder_url() {
	return (string) apply_filters( 'tmt_pc_builder_url', '' );
}
