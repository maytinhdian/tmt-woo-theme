<?php
/**
 * Menu chính: mục lấy từ Giao diện → Menu (vị trí "Menu chính"), chưa gán menu thì tự lấy nhóm hàng cấp 1.
 * Mega menu "☰": mỗi nhóm hàng cấp 1 một cột kèm toàn bộ nhóm con.
 * Mục là một danh mục sản phẩm có nhóm con: dấu ⌄ xổ khung nhiều cột, dưới mỗi nhóm con là hãng / nhóm con nữa (Tùy biến → Menu chính).
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cây danh mục sản phẩm (nạp một lần).
 *
 * @return array{tops: WP_Term[], children: array<int, WP_Term[]>}
 */
function tmt_cat_tree() {
	static $tree = null;
	if ( null !== $tree || ! taxonomy_exists( 'product_cat' ) ) {
		return $tree ? $tree : array( 'tops' => array(), 'children' => array() );
	}
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false, // WooCommerce tự xếp theo thứ tự kéo thả ở trang Danh mục.
		)
	);
	$tree      = array( 'tops' => array(), 'children' => array() );
	$uncat     = (int) get_option( 'default_product_cat' );
	if ( is_wp_error( $terms ) ) {
		return $tree;
	}
	foreach ( $terms as $t ) {
		if ( (int) $t->term_id === $uncat ) {
			continue;
		}
		if ( 0 === (int) $t->parent ) {
			$tree['tops'][] = $t;
		} else {
			$tree['children'][ (int) $t->parent ][] = $t;
		}
	}
	return $tree;
}

/** Nhóm con trực tiếp của một danh mục. */
function tmt_cat_children( $term_id ) {
	$tree = tmt_cat_tree();
	return isset( $tree['children'][ (int) $term_id ] ) ? $tree['children'][ (int) $term_id ] : array();
}

/**
 * Hãng đang có hàng trong một danh mục (taxonomy product_brand của WooCommerce).
 *
 * @return WP_Term[]
 */
function tmt_brands_in_cat( WP_Term $cat, $limit = 8 ) {
	if ( ! taxonomy_exists( 'product_brand' ) ) {
		return array();
	}
	$key    = 'tmt_brands_' . $cat->term_id;
	$cached = wp_cache_get( $key, 'tmt' );
	if ( false !== $cached ) {
		return $cached;
	}
	$ids = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => 300,
			'no_found_rows'  => true,
			'tax_query'      => array( array( 'taxonomy' => 'product_cat', 'terms' => $cat->term_id ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	$brands = $ids ? wp_get_object_terms( $ids, 'product_brand', array( 'orderby' => 'count', 'order' => 'DESC' ) ) : array();
	$brands = is_wp_error( $brands ) ? array() : array_slice( $brands, 0, $limit );
	wp_cache_set( $key, $brands, 'tmt', HOUR_IN_SECONDS );
	return $brands;
}

/**
 * Mục menu chính đã dựng cho template.
 *
 * @return array<int, array{label: string, href: string, active: bool, external: bool, drop: array|null}>
 */
function tmt_nav_items() {
	$out       = array();
	$locations = get_nav_menu_locations();
	$current   = tmt_current_url();
	$cur_term  = ( is_tax( 'product_cat' ) || is_tax( 'product_brand' ) ) ? get_queried_object() : null;

	if ( ! empty( $locations['primary'] ) && ( $items = wp_get_nav_menu_items( $locations['primary'] ) ) ) {
		$kids = array();
		foreach ( $items as $it ) {
			if ( $it->menu_item_parent ) {
				$kids[ (int) $it->menu_item_parent ][] = $it;
			}
		}
		foreach ( $items as $it ) {
			if ( $it->menu_item_parent ) {
				continue;
			}
			$term   = ( 'taxonomy' === $it->type && 'product_cat' === $it->object ) ? get_term( (int) $it->object_id, 'product_cat' ) : null;
			$term   = $term instanceof WP_Term ? $term : null;
			$active = untrailingslashit( $it->url ) === untrailingslashit( $current )
				|| ( $term && $cur_term instanceof WP_Term && ( $cur_term->term_id === $term->term_id || term_is_ancestor_of( $term, $cur_term, 'product_cat' ) ) );
			$out[]  = array(
				'label'    => $it->title,
				'href'     => $it->url,
				'active'   => $active,
				'external' => '_blank' === $it->target,
				'drop'     => tmt_nav_drop( $term, isset( $kids[ $it->ID ] ) ? $kids[ $it->ID ] : array() ),
			);
		}
		return $out;
	}

	// Chưa gán menu: nhóm hàng cấp 1 + trang Mẹo vặt (nếu có trang bài viết).
	foreach ( array_slice( tmt_cat_tree()['tops'], 0, 8 ) as $t ) {
		$out[] = array(
			'label'    => $t->name,
			'href'     => get_term_link( $t ),
			'active'   => $cur_term instanceof WP_Term && ( $cur_term->term_id === $t->term_id || term_is_ancestor_of( $t, $cur_term, 'product_cat' ) ),
			'external' => false,
			'drop'     => tmt_nav_drop( $t, array() ),
		);
	}
	$posts_page = (int) get_option( 'page_for_posts' );
	if ( $posts_page ) {
		$out[] = array( 'label' => get_the_title( $posts_page ), 'href' => get_permalink( $posts_page ), 'active' => is_home(), 'external' => false, 'drop' => null );
	}
	return $out;
}

/**
 * Khung xổ của một mục: nhóm con của danh mục (kèm hãng/nhóm con), hoặc mục con tự thêm trong Giao diện → Menu.
 *
 * @param WP_Term|null $term Danh mục của mục menu.
 * @param array        $kids Mục con trong menu.
 */
function tmt_nav_drop( $term, array $kids ) {
	if ( ! tmt_opt( 'nav.drop_on' ) ) {
		return null;
	}
	$items = array();
	if ( $kids ) {
		foreach ( $kids as $k ) {
			$items[] = array( 'name' => $k->title, 'href' => $k->url, 'icon' => 'chevron_right', 'links' => array() );
		}
		return array( 'items' => $items, 'all' => null );
	}
	if ( ! $term ) {
		return null;
	}
	$children = tmt_cat_children( $term->term_id );
	if ( ! $children ) {
		return null;
	}
	$show = tmt_opt( 'nav.drop_show' );
	foreach ( $children as $c ) {
		$links = array();
		if ( 'category' === $show ) {
			foreach ( array_slice( tmt_cat_children( $c->term_id ), 0, 8 ) as $g ) {
				$links[] = array( 'name' => $g->name, 'href' => get_term_link( $g ) );
			}
		} else {
			foreach ( tmt_brands_in_cat( $c, 6 ) as $b ) {
				$links[] = array( 'name' => $b->name, 'href' => add_query_arg( 'hang', $b->slug, get_term_link( $c ) ) );
			}
		}
		$items[] = array( 'name' => $c->name, 'href' => get_term_link( $c ), 'icon' => tmt_category_icon( $c ), 'links' => $links );
	}
	return array( 'items' => $items, 'all' => get_term_link( $term ) );
}

/**
 * Mega menu "☰": mỗi nhóm hàng cấp 1 một cột.
 *
 * @return array|null
 */
function tmt_mega_menu() {
	if ( ! tmt_opt( 'nav.mega_on' ) ) {
		return null;
	}
	$cols = array();
	foreach ( tmt_cat_tree()['tops'] as $t ) {
		$children = array();
		foreach ( tmt_cat_children( $t->term_id ) as $c ) {
			$children[] = array( 'name' => $c->name, 'href' => get_term_link( $c ) );
		}
		$cols[] = array( 'name' => $t->name, 'href' => get_term_link( $t ), 'icon' => tmt_category_icon( $t ), 'children' => $children );
	}
	if ( ! $cols ) {
		return null;
	}
	$label = trim( (string) tmt_opt( 'nav.mega_label' ) );
	return array( 'label' => '' === $label ? 'Danh mục' : $label, 'columns' => $cols );
}

/** URL trang đang xem (không có query). */
function tmt_current_url() {
	global $wp;
	return home_url( isset( $wp->request ) ? $wp->request : '' );
}

/** Lớp hiệu ứng menu (Tùy biến → Menu chính), cùng bộ kiểu với web bán hàng của app. */
function tmt_nav_fx() {
	$pick   = function ( $key, $allowed, $fallback ) {
		$v = (string) tmt_opt( $key );
		return array_key_exists( $v, $allowed ) ? $v : $fallback;
	};
	$h      = $pick( 'nav.hover', tmt_nav_hovers(), 'line' );
	$a      = $pick( 'nav.active', tmt_nav_actives(), 'line' );
	$align  = $pick( 'nav.align', array( 'left' => 1, 'center' => 1, 'right' => 1 ), 'left' );
	$case   = $pick( 'nav.case', array( 'none' => 1, 'upper' => 1, 'lower' => 1, 'first' => 1 ), 'none' );
	$color  = $pick( 'nav.fx_color', tmt_nav_fx_colors(), 'primary' );
	$speed  = $pick( 'nav.fx_speed', tmt_nav_fx_speeds(), 'normal' );
	$class  = "sf-nav-{$align} sf-nav-case-{$case} sf-nav-h-{$h} sf-nav-a-{$a} sf-nav-c-{$color} sf-nav-d-{$speed}";
	$class .= in_array( $a, array( 'gpill', 'tab', 'neon' ), true ) ? ' sf-nav-boxed' : '';
	return array( 'class' => $class, 'letters' => in_array( $h, array( 'lwave', 'hop', 'paint' ), true ), 'case' => $case );
}

function tmt_nav_classes() {
	return tmt_nav_fx()['class'];
}

/** Chữ một mục menu, dựng sẵn cho hiệu ứng (data-t cho chữ lật/tách đôi, từng chữ cái khi hiệu ứng chạy theo chữ). */
function tmt_nav_label( $label ) {
	static $fx = null;
	$fx   = $fx ? $fx : tmt_nav_fx();
	$text = $label;
	if ( 'first' === $fx['case'] ) {
		$l    = mb_strtolower( $label );
		$text = mb_strtoupper( mb_substr( $l, 0, 1 ) ) . mb_substr( $l, 1 );
	}
	if ( $fx['letters'] ) {
		$inner = '<span class="visually-hidden">' . esc_html( $text ) . '</span><span aria-hidden="true">';
		foreach ( preg_split( '//u', $text, -1, PREG_SPLIT_NO_EMPTY ) as $i => $ch ) {
			$inner .= '<span class="l" style="--i:' . (int) $i . '">' . ( ' ' === $ch ? '&nbsp;' : esc_html( $ch ) ) . '</span>';
		}
		$inner .= '</span>';
	} else {
		$inner = esc_html( $text );
	}
	return '<span class="sf-nl"><span class="sf-nr" data-t="' . esc_attr( $text ) . '"><b>' . $inner . '</b></span></span>';
}

function tmt_nav_hovers() {
	return array(
		'line'     => 'Gạch chân trượt từ trái',
		'roll'     => 'Chữ lật',
		'shine'    => 'Chữ ánh kim',
		'jelly'    => 'Nảy',
		'glow'     => 'Phát sáng neon',
		'lwave'    => 'Sóng chữ',
		'hop'      => 'Nhảy từng chữ',
		'paint'    => 'Tô màu từng chữ',
		'flip'     => 'Lật 3D',
		'pop'      => 'Phóng nổi',
		'3d'       => 'Chữ nổi 3D',
		'glitch'   => 'Nhiễu (chữ giật, tách màu)',
		'flowtext' => 'Chữ chạy màu',
		'ring'     => 'Lắc chuông',
		'split'    => 'Tách đôi',
	);
}

function tmt_nav_actives() {
	return array(
		'line'   => 'Gạch chân',
		'gpill'  => 'Viên gradient',
		'flow'   => 'Gạch chạy màu',
		'marker' => 'Bút dạ quang',
		'pulse'  => 'Chấm nhấp nháy',
		'tab'    => 'Tab nổi',
		'neon'   => 'Viền neon',
		'caret'  => 'Mũi tên chỉ',
		'wavy'   => 'Gạch sóng',
	);
}

function tmt_nav_fx_colors() {
	return array( 'primary' => 'Màu chính', 'accent' => 'Màu nhấn', 'dark' => 'Màu đậm' );
}

function tmt_nav_fx_speeds() {
	return array( 'fast' => 'Nhanh', 'normal' => 'Vừa', 'slow' => 'Chậm' );
}

/** Ô "Icon" (tên icon Material) khi thêm/sửa danh mục sản phẩm. */
add_action(
	'product_cat_add_form_fields',
	function () {
		echo '<div class="form-field"><label for="tmt_icon">Icon trên menu</label><input type="text" name="tmt_icon" id="tmt_icon" placeholder="memory"><p>Tên icon Material (fonts.google.com/icons), ví dụ memory, laptop, videocam. Để trống thì theme tự đoán.</p></div>';
	}
);
add_action(
	'product_cat_edit_form_fields',
	function ( $term ) {
		$v = get_term_meta( $term->term_id, 'tmt_icon', true );
		echo '<tr class="form-field"><th><label for="tmt_icon">Icon trên menu</label></th><td><input type="text" name="tmt_icon" id="tmt_icon" value="' . esc_attr( $v ) . '" placeholder="' . esc_attr( tmt_category_icon( null ) ) . '"><p class="description">Tên icon Material, ví dụ memory, laptop, videocam. Để trống thì theme tự đoán theo tên.</p></td></tr>';
	}
);
foreach ( array( 'created_product_cat', 'edited_product_cat' ) as $tmt_hook ) {
	add_action(
		$tmt_hook,
		function ( $term_id ) {
			if ( ! isset( $_POST['tmt_icon'] ) || ! current_user_can( 'manage_product_terms' ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- WordPress đã kiểm nonce của form danh mục.
				return;
			}
			$icon = sanitize_key( wp_unslash( $_POST['tmt_icon'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			update_term_meta( $term_id, 'tmt_icon', $icon );
		}
	);
}
