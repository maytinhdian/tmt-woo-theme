<?php
/**
 * Bộ lọc trang danh sách sản phẩm (cửa hàng, danh mục, hãng, tìm kiếm).
 *
 * Tham số trên đường dẫn (giống web Laravel): hang[]=slug-hãng, gia=duoi-1-trieu|1-3-trieu|3-10-trieu|tren-10-trieu,
 * con-hang=1, ts[socket]=am5 (thuộc tính toàn cục pa_socket của WooCommerce), sap-xep=moi|gia-tang|gia-giam|ten.
 *
 * Cách làm: lấy mọi sản phẩm của trang đang xem (danh mục/hãng/tìm kiếm) một lần, đọc hãng, thuộc tính, giá, tồn
 * từ bảng tra cứu của WooCommerce, lọc trong PHP rồi đưa danh sách id vào truy vấn chính. Số đếm mỗi lựa chọn
 * tính theo các lọc khác đang chọn. Hợp với cửa hàng vài nghìn sản phẩm; cửa hàng lớn hơn nên dùng plugin lọc có chỉ mục.
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;

/** Khoảng giá: key => [nhãn, từ, đến]. */
function tmt_price_ranges() {
	return array(
		'duoi-1-trieu'  => array( 'Dưới 1 triệu', 1, 999999 ),
		'1-3-trieu'     => array( '1 đến 3 triệu', 1000000, 3000000 ),
		'3-10-trieu'    => array( '3 đến 10 triệu', 3000001, 10000000 ),
		'tren-10-trieu' => array( 'Trên 10 triệu', 10000001, null ),
	);
}

function tmt_sorts() {
	return array(
		'moi'      => 'Mới nhất',
		'gia-tang' => 'Giá thấp đến cao',
		'gia-giam' => 'Giá cao đến thấp',
		'ten'      => 'Tên A đến Z',
	);
}

/** Trang hiện tại có phải trang danh sách sản phẩm không. */
function tmt_is_listing() {
	return function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() || ( is_search() && 'product' === get_query_var( 'post_type' ) ) );
}

/**
 * Lọc đang chọn, đã làm sạch.
 *
 * @return array{hang: string[], gia: ?string, con_hang: bool, ts: array<string, string>, sap_xep: string}
 */
function tmt_filter_state() {
	static $state = null;
	if ( null !== $state ) {
		return $state;
	}
	// phpcs:disable WordPress.Security.NonceVerification -- bộ lọc chỉ đọc GET.
	$hang = isset( $_GET['hang'] ) ? (array) wp_unslash( $_GET['hang'] ) : array();
	$ts   = isset( $_GET['ts'] ) && is_array( $_GET['ts'] ) ? wp_unslash( $_GET['ts'] ) : array();
	$gia  = isset( $_GET['gia'] ) ? sanitize_key( wp_unslash( $_GET['gia'] ) ) : '';
	$sort = isset( $_GET['sap-xep'] ) ? sanitize_key( wp_unslash( $_GET['sap-xep'] ) ) : 'moi';
	// phpcs:enable
	$clean_ts = array();
	foreach ( $ts as $k => $v ) {
		if ( is_string( $v ) && taxonomy_exists( 'pa_' . sanitize_title( $k ) ) ) {
			$clean_ts[ sanitize_title( $k ) ] = sanitize_title( $v );
		}
	}
	$state = array(
		'hang'     => array_values( array_unique( array_filter( array_map( 'sanitize_title', array_filter( $hang, 'is_string' ) ) ) ) ),
		'gia'      => isset( tmt_price_ranges()[ $gia ] ) ? $gia : null,
		'con_hang' => ! empty( $_GET['con-hang'] ), // phpcs:ignore WordPress.Security.NonceVerification
		'ts'       => $clean_ts,
		'sap_xep'  => isset( tmt_sorts()[ $sort ] ) ? $sort : 'moi',
	);
	return $state;
}

/** Có lọc nào đang chọn không (trang đã lọc để noindex). */
function tmt_has_filters() {
	$s = tmt_filter_state();
	return $s['hang'] || $s['gia'] || $s['con_hang'] || $s['ts'];
}

/**
 * Dữ liệu lọc của mọi sản phẩm thuộc trang đang xem: id => [brands[], attrs[tax][], price, in_stock].
 *
 * @return array
 */
function tmt_listing_rows() {
	static $rows = null;
	if ( null !== $rows ) {
		return $rows;
	}
	global $wpdb;
	$args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'fields'         => 'ids',
		'posts_per_page' => 3000,
		'no_found_rows'  => true,
		'tax_query'      => array(), // phpcs:ignore WordPress.DB.SlowDBQuery
	);
	// Ẩn sản phẩm đặt "Ẩn khỏi cửa hàng".
	$args['tax_query'][] = array( 'taxonomy' => 'product_visibility', 'field' => 'name', 'terms' => array( 'exclude-from-catalog' ), 'operator' => 'NOT IN' );
	if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
		$args['tax_query'][] = array( 'taxonomy' => 'product_visibility', 'field' => 'name', 'terms' => array( 'outofstock' ), 'operator' => 'NOT IN' );
	}
	if ( is_product_taxonomy() ) {
		$q                   = get_queried_object();
		$args['tax_query'][] = array( 'taxonomy' => $q->taxonomy, 'terms' => $q->term_id );
	}
	if ( is_search() ) {
		$args['s'] = get_search_query( false );
	}
	$ids  = get_posts( $args );
	$rows = array();
	if ( ! $ids ) {
		return $rows;
	}
	foreach ( $ids as $id ) {
		$rows[ $id ] = array( 'brands' => array(), 'attrs' => array(), 'price' => 0, 'in_stock' => false );
	}
	$in = implode( ',', array_map( 'intval', $ids ) );
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $in toàn số nguyên.
	$lookup = $wpdb->get_results( "SELECT product_id, min_price, max_price, stock_status FROM {$wpdb->wc_product_meta_lookup} WHERE product_id IN ($in)" );
	foreach ( $lookup as $l ) {
		$rows[ $l->product_id ]['price']    = (float) $l->min_price;
		$rows[ $l->product_id ]['in_stock'] = 'outofstock' !== $l->stock_status;
	}
	$taxes = array_merge( taxonomy_exists( 'product_brand' ) ? array( 'product_brand' ) : array(), wc_get_attribute_taxonomy_names() );
	if ( $taxes ) {
		$terms = wp_get_object_terms( $ids, $taxes, array( 'fields' => 'all_with_object_id', 'orderby' => 'name' ) );
		foreach ( is_wp_error( $terms ) ? array() : $terms as $t ) {
			if ( 'product_brand' === $t->taxonomy ) {
				$rows[ $t->object_id ]['brands'][] = $t->slug;
			} else {
				$rows[ $t->object_id ]['attrs'][ substr( $t->taxonomy, 3 ) ][] = $t->slug;
			}
		}
	}
	return $rows;
}

/**
 * Sản phẩm có qua các lọc không (bỏ qua một nhóm khi đếm số cho chính nhóm đó).
 *
 * @param array  $row  Dữ liệu một sản phẩm.
 * @param array  $s    Lọc đang chọn.
 * @param string $skip Nhóm bỏ qua: hang, gia, con_hang, ts.<key>.
 */
function tmt_row_passes( array $row, array $s, $skip = '' ) {
	if ( 'con_hang' !== $skip && $s['con_hang'] && ! $row['in_stock'] ) {
		return false;
	}
	if ( 'hang' !== $skip && $s['hang'] && ! array_intersect( $s['hang'], $row['brands'] ) ) {
		return false;
	}
	if ( 'gia' !== $skip && $s['gia'] ) {
		list( , $min, $max ) = tmt_price_ranges()[ $s['gia'] ];
		if ( $row['price'] < $min || ( null !== $max && $row['price'] > $max ) ) {
			return false;
		}
	}
	foreach ( $s['ts'] as $k => $v ) {
		if ( 'ts.' . $k !== $skip && ! in_array( $v, isset( $row['attrs'][ $k ] ) ? $row['attrs'][ $k ] : array(), true ) ) {
			return false;
		}
	}
	return true;
}

/** Id sản phẩm qua mọi lọc. */
function tmt_filtered_ids() {
	$s   = tmt_filter_state();
	$ids = array();
	foreach ( tmt_listing_rows() as $id => $row ) {
		if ( tmt_row_passes( $row, $s ) ) {
			$ids[] = $id;
		}
	}
	return $ids;
}

/** Đưa kết quả lọc + sắp xếp vào truy vấn chính của trang danh sách. */
add_action(
	'woocommerce_product_query',
	function ( WP_Query $q ) {
		if ( is_admin() || ! $q->is_main_query() ) {
			return;
		}
		$q->set( 'posts_per_page', max( 4, min( 96, (int) tmt_opt( 'listing.per_page' ) ) ) );
		if ( tmt_has_filters() ) {
			$ids = tmt_filtered_ids();
			$q->set( 'post__in', $ids ? $ids : array( 0 ) );
		}
	}
);

/** Sắp xếp theo ?sap-xep= (thay ô sắp xếp mặc định của WooCommerce). */
add_filter(
	'woocommerce_get_catalog_ordering_args',
	function ( $args ) {
		if ( ! isset( $_GET['sap-xep'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return $args;
		}
		switch ( tmt_filter_state()['sap_xep'] ) {
			case 'gia-tang':
				return array( 'orderby' => 'price', 'order' => 'ASC', 'meta_key' => '' );
			case 'gia-giam':
				return array( 'orderby' => 'price', 'order' => 'DESC', 'meta_key' => '' );
			case 'ten':
				return array( 'orderby' => 'title', 'order' => 'ASC', 'meta_key' => '' );
			default:
				return array( 'orderby' => 'date ID', 'order' => 'DESC', 'meta_key' => '' );
		}
	}
);
/** WooCommerce sắp theo giá bằng orderby=price chỉ khi biết là đang sắp theo giá: báo nó biết. */
add_filter(
	'woocommerce_default_catalog_orderby',
	function ( $default ) {
		$map = array( 'gia-tang' => 'price', 'gia-giam' => 'price-desc', 'ten' => 'title', 'moi' => 'date' );
		return isset( $_GET['sap-xep'] ) ? $map[ tmt_filter_state()['sap_xep'] ] : $default; // phpcs:ignore WordPress.Security.NonceVerification
	}
);

/** Trang đã lọc không cho Google lập chỉ mục (tránh hàng nghìn trang trùng). */
add_filter(
	'wp_robots',
	function ( $robots ) {
		if ( tmt_is_listing() && ( tmt_has_filters() || isset( $_GET['sap-xep'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$robots['noindex'] = true;
			$robots['follow']  = true;
		}
		return $robots;
	}
);

/**
 * Đường dẫn với tham số lọc mới, giữ các lọc khác, bỏ số trang.
 *
 * @param array $s Lọc mới.
 */
function tmt_filter_url( array $s ) {
	$params = array();
	if ( $s['hang'] ) {
		$params['hang'] = array_values( $s['hang'] );
	}
	if ( $s['gia'] ) {
		$params['gia'] = $s['gia'];
	}
	if ( $s['con_hang'] ) {
		$params['con-hang'] = 1;
	}
	if ( $s['ts'] ) {
		$params['ts'] = $s['ts'];
	}
	if ( 'moi' !== $s['sap_xep'] ) {
		$params['sap-xep'] = $s['sap_xep'];
	}
	if ( is_search() ) {
		$params['s']         = get_search_query( false );
		$params['post_type'] = 'product';
	}
	$base = is_search() ? home_url( '/' ) : tmt_listing_base_url();
	return $params ? $base . '?' . http_build_query( $params ) : $base;
}

/** Đường dẫn trang 1 của trang danh sách đang xem. */
function tmt_listing_base_url() {
	if ( is_product_taxonomy() ) {
		return get_term_link( get_queried_object() );
	}
	return wc_get_page_permalink( 'shop' );
}

/** Đường dẫn bật/tắt một lựa chọn. */
function tmt_filter_toggle( $key, $value ) {
	$s = tmt_filter_state();
	if ( 'hang' === $key ) {
		$s['hang'] = in_array( $value, $s['hang'], true ) ? array_values( array_diff( $s['hang'], array( $value ) ) ) : array_merge( $s['hang'], array( $value ) );
	} elseif ( 0 === strpos( $key, 'ts.' ) ) {
		$k = substr( $key, 3 );
		if ( isset( $s['ts'][ $k ] ) && $s['ts'][ $k ] === $value ) {
			unset( $s['ts'][ $k ] );
		} else {
			$s['ts'][ $k ] = $value;
		}
	} elseif ( 'con_hang' === $key ) {
		$s['con_hang'] = ! $s['con_hang'];
	} elseif ( 'gia' === $key ) {
		$s['gia'] = $s['gia'] === $value ? null : $value;
	} elseif ( 'sap_xep' === $key ) {
		$s['sap_xep'] = $value;
	}
	return tmt_filter_url( $s );
}

/** Đường dẫn bỏ hẳn một nhóm lọc. */
function tmt_filter_clear( $key ) {
	$s = tmt_filter_state();
	if ( 0 === strpos( $key, 'ts.' ) ) {
		unset( $s['ts'][ substr( $key, 3 ) ] );
	} elseif ( 'hang' === $key ) {
		$s['hang'] = array();
	} else {
		$s[ $key ] = 'con_hang' === $key ? false : null;
	}
	return tmt_filter_url( $s );
}

/** Đường dẫn bỏ mọi lọc. */
function tmt_filter_reset_url() {
	$s = tmt_filter_state();
	return tmt_filter_url( array( 'hang' => array(), 'gia' => null, 'con_hang' => false, 'ts' => array(), 'sap_xep' => $s['sap_xep'] ) );
}

/**
 * Các nhóm lọc cho template (dùng chung kiểu thanh ngang và cột trái/phải).
 *
 * @return array{groups: array, active: array}
 */
function tmt_filter_groups() {
	$s      = tmt_filter_state();
	$rows   = tmt_listing_rows();
	$groups = array();
	$active = array();
	if ( $s['con_hang'] ) {
		$active[] = array( 'Còn hàng', tmt_filter_toggle( 'con_hang', '1' ) );
	}

	// Giá.
	$opts = array();
	foreach ( tmt_price_ranges() as $k => $r ) {
		$n = 0;
		foreach ( $rows as $row ) {
			if ( $row['price'] >= $r[1] && ( null === $r[2] || $row['price'] <= $r[2] ) && tmt_row_passes( $row, $s, 'gia' ) ) {
				++$n;
			}
		}
		if ( $n || $s['gia'] === $k ) {
			$opts[] = array( 'label' => $r[0], 'count' => $n, 'url' => tmt_filter_toggle( 'gia', $k ), 'on' => $s['gia'] === $k );
		}
		if ( $s['gia'] === $k ) {
			$active[] = array( $r[0], tmt_filter_toggle( 'gia', $k ) );
		}
	}
	if ( $opts ) {
		$groups[] = array( 'key' => 'gia', 'label' => 'Giá', 'selected' => $s['gia'] ? 1 : 0, 'clear' => tmt_filter_clear( 'gia' ), 'multi' => false, 'options' => $opts );
	}

	// Hãng.
	$counts = array();
	foreach ( $rows as $row ) {
		if ( tmt_row_passes( $row, $s, 'hang' ) ) {
			foreach ( array_unique( $row['brands'] ) as $b ) {
				$counts[ $b ] = isset( $counts[ $b ] ) ? $counts[ $b ] + 1 : 1;
			}
		}
	}
	$all_brands = array();
	foreach ( $rows as $row ) {
		$all_brands = array_merge( $all_brands, $row['brands'] );
	}
	$all_brands = array_unique( $all_brands );
	if ( $all_brands && taxonomy_exists( 'product_brand' ) ) {
		$opts = array();
		foreach ( get_terms( array( 'taxonomy' => 'product_brand', 'slug' => $all_brands, 'hide_empty' => false ) ) as $b ) {
			$on     = in_array( $b->slug, $s['hang'], true );
			$opts[] = array( 'label' => $b->name, 'count' => isset( $counts[ $b->slug ] ) ? $counts[ $b->slug ] : 0, 'url' => tmt_filter_toggle( 'hang', $b->slug ), 'on' => $on );
			if ( $on ) {
				$active[] = array( $b->name, tmt_filter_toggle( 'hang', $b->slug ) );
			}
		}
		usort(
			$opts,
			function ( $a, $b ) {
				return $b['count'] - $a['count'];
			}
		);
		$groups[] = array( 'key' => 'hang', 'label' => 'Hãng', 'selected' => count( $s['hang'] ), 'clear' => tmt_filter_clear( 'hang' ), 'multi' => true, 'options' => $opts );
	}

	// Thuộc tính (thông số dạng chọn): nhóm có ít nhất 2 giá trị trong trang đang xem, hoặc đang được chọn.
	foreach ( wc_get_attribute_taxonomies() as $attr ) {
		$k      = $attr->attribute_name;
		$values = array();
		foreach ( $rows as $row ) {
			if ( isset( $row['attrs'][ $k ] ) ) {
				$values = array_merge( $values, $row['attrs'][ $k ] );
			}
		}
		$values = array_unique( $values );
		if ( count( $values ) < 2 && ! isset( $s['ts'][ $k ] ) ) {
			continue;
		}
		$counts = array();
		foreach ( $rows as $row ) {
			if ( isset( $row['attrs'][ $k ] ) && tmt_row_passes( $row, $s, 'ts.' . $k ) ) {
				foreach ( array_unique( $row['attrs'][ $k ] ) as $v ) {
					$counts[ $v ] = isset( $counts[ $v ] ) ? $counts[ $v ] + 1 : 1;
				}
			}
		}
		$cur   = isset( $s['ts'][ $k ] ) ? $s['ts'][ $k ] : null;
		$opts  = array();
		$terms = get_terms( array( 'taxonomy' => 'pa_' . $k, 'slug' => $values, 'hide_empty' => false, 'orderby' => 'menu_order' ) );
		foreach ( is_wp_error( $terms ) ? array() : $terms as $t ) {
			$opts[] = array( 'label' => $t->name, 'count' => isset( $counts[ $t->slug ] ) ? $counts[ $t->slug ] : 0, 'url' => tmt_filter_toggle( 'ts.' . $k, $t->slug ), 'on' => $cur === $t->slug );
			if ( $cur === $t->slug ) {
				$active[] = array( $attr->attribute_label . ': ' . $t->name, tmt_filter_toggle( 'ts.' . $k, $t->slug ) );
			}
		}
		$groups[] = array( 'key' => 'ts.' . $k, 'label' => $attr->attribute_label, 'selected' => $cur ? 1 : 0, 'clear' => tmt_filter_clear( 'ts.' . $k ), 'multi' => false, 'options' => $opts );
	}

	return array( 'groups' => $groups, 'active' => $active );
}
