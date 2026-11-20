<?php
/**
 * Linh kiện chọn được: sản phẩm WooCommerce (và từng loại của sản phẩm có biến thể) thuộc danh mục đã gán cho khe.
 * Thông số đọc từ thuộc tính sản phẩm (pa_socket, pa_ram-type...). Không bao giờ gửi số tồn, chỉ Còn hàng / Tạm hết.
 *
 * @package TMT_Shop_Tools
 */

defined( 'ABSPATH' ) || exit;

/**
 * Danh mục đã gán cho từng khe (chưa tính danh mục con). Chưa lưu thì đoán theo slug danh mục.
 *
 * @return array<string, list<int>>
 */
function tmt_pcb_slot_categories() {
	$saved = get_option( 'tmt_pcb_slot_categories' );
	$out   = array_fill_keys( array_keys( tmt_pcb_slots() ), array() );
	if ( is_array( $saved ) ) {
		foreach ( $out as $slot => $ids ) {
			$out[ $slot ] = array_values( array_map( 'intval', (array) ( isset( $saved[ $slot ] ) ? $saved[ $slot ] : array() ) ) );
		}
		return $out;
	}
	return tmt_pcb_guess_slot_categories();
}

/** @return array<string, list<int>> Đoán danh mục cho từng khe theo slug (cpu, ram, vo-case...). */
function tmt_pcb_guess_slot_categories() {
	$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
	$out   = array_fill_keys( array_keys( tmt_pcb_slots() ), array() );
	if ( is_wp_error( $terms ) ) {
		return $out;
	}
	$taken = array();
	foreach ( tmt_pcb_slots() as $slot => $s ) {
		foreach ( $s['guess'] as $word ) {
			foreach ( $terms as $t ) {
				$slug = $t->slug;
				$hit  = $slug === $word || preg_match( '/(^|-)' . preg_quote( $word, '/' ) . '$/', $slug );
				if ( $hit && empty( $taken[ $t->term_id ] ) ) {
					$out[ $slot ][]       = (int) $t->term_id;
					$taken[ $t->term_id ] = true;
				}
			}
			if ( $out[ $slot ] ) {
				break;
			}
		}
	}
	return $out;
}

/**
 * Id danh mục (gồm danh mục con) => khe. Danh mục con thuộc khe của danh mục cha gần nhất được gán.
 *
 * @return array<int, string>
 */
function tmt_pcb_category_slot_map() {
	static $map = null;
	if ( null !== $map ) {
		return $map;
	}
	$direct = array();
	foreach ( tmt_pcb_slot_categories() as $slot => $ids ) {
		foreach ( $ids as $id ) {
			if ( ! isset( $direct[ $id ] ) ) {
				$direct[ $id ] = $slot;
			}
		}
	}
	$map   = array();
	$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'fields' => 'id=>parent' ) );
	if ( is_wp_error( $terms ) ) {
		return $map;
	}
	foreach ( $terms as $id => $parent ) {
		$seen = array();
		for ( $node = (int) $id; $node && ! isset( $seen[ $node ] ); $node = isset( $terms[ $node ] ) ? (int) $terms[ $node ] : 0 ) {
			$seen[ $node ] = true;
			if ( isset( $direct[ $node ] ) ) {
				$map[ (int) $id ] = $direct[ $node ];
				break;
			}
		}
	}
	return $map;
}

/**
 * Giá trị các thông số của một sản phẩm (key thông số => chuỗi). Biến thể lấy thêm giá trị của loại đang xét.
 *
 * @return array<string, string>
 */
function tmt_pcb_product_specs( WC_Product $product ) {
	$parent = $product->is_type( 'variation' ) ? wc_get_product( $product->get_parent_id() ) : null;
	$source = $parent ? $parent : $product;
	$attrs  = $source->get_attributes();
	$out    = array();
	$aliases = tmt_pcb_spec_aliases();
	foreach ( tmt_pcb_specs() as $key => $def ) {
		$slug = tmt_pcb_attr_slug( $key );
		$val  = '';
		foreach ( array_merge( array( $slug ), isset( $aliases[ $key ] ) ? $aliases[ $key ] : array() ) as $try ) {
			$tax = 'pa_' . $try;
			if ( $parent && '' !== $product->get_attribute( $tax ) ) {
				$val = $product->get_attribute( $tax );
			} elseif ( isset( $attrs[ $tax ] ) ) {
				$val = $source->get_attribute( $tax );
			}
			if ( '' !== $val ) {
				break;
			}
		}
		if ( '' === $val ) {
			// Thuộc tính riêng của sản phẩm (không phải thuộc tính chung) đặt đúng tên thông số.
			foreach ( $attrs as $a ) {
				if ( ! $a->is_taxonomy() && in_array( sanitize_title( $a->get_name() ), array( $slug, sanitize_title( $def[0] ) ), true ) ) {
					$val = implode( ', ', $a->get_options() );
					break;
				}
			}
		}
		$val = trim( wp_strip_all_tags( html_entity_decode( (string) $val ) ) );
		if ( '' !== $val ) {
			$out[ $key ] = $val;
		}
	}
	return $out;
}

/** Dòng mô tả ngắn: các thuộc tính đang hiện trên trang sản phẩm. "Socket AM5 · Loại RAM DDR5". */
function tmt_pcb_summary( WC_Product $product ) {
	$parent = $product->is_type( 'variation' ) ? wc_get_product( $product->get_parent_id() ) : null;
	$source = $parent ? $parent : $product;
	$parts  = array();
	foreach ( $source->get_attributes() as $name => $a ) {
		if ( ! $a->get_visible() ) {
			continue;
		}
		$value = $parent && $a->get_variation() ? $product->get_attribute( $name ) : $source->get_attribute( $name );
		$value = trim( wp_strip_all_tags( (string) $value ) );
		if ( '' === $value ) {
			continue;
		}
		$label = $a->is_taxonomy() ? wc_attribute_label( $name ) : $a->get_name();
		if ( preg_match( '/^(.+?)\s*\(([^)]+)\)$/u', $label, $m ) && is_numeric( $value ) ) {
			$parts[] = $m[1] . ' ' . $value . $m[2]; // "Công suất tiêu thụ (W)" + 65 → "Công suất tiêu thụ 65W".
		} elseif ( in_array( $value, array( 'Có', 'Không' ), true ) ) {
			$parts[] = $label . ': ' . $value;
		} else {
			$parts[] = $label . ' ' . $value;
		}
		if ( count( $parts ) >= 5 ) {
			break;
		}
	}
	return implode( ' · ', $parts );
}

/** Tháng bảo hành (ô "Bảo hành (tháng)" của theme TMT Shop), 0 nếu chưa nhập. */
function tmt_pcb_warranty( WC_Product $product ) {
	$id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
	return (int) get_post_meta( $id, '_tmt_warranty_months', true );
}

/**
 * Mọi linh kiện chọn được, theo id sản phẩm. Bộ nhớ đệm 10 phút, xóa khi sửa sản phẩm hoặc cài đặt.
 *
 * @return array<int, array<string, mixed>>
 */
function tmt_pcb_parts() {
	static $parts = null;
	if ( null !== $parts ) {
		return $parts;
	}
	$cached = wc_tax_enabled() ? false : get_transient( 'tmt_pcb_parts_' . TMT_TOOLS_VERSION );
	if ( is_array( $cached ) ) {
		return $parts = $cached;
	}
	$parts = array();
	$map   = tmt_pcb_category_slot_map();
	if ( ! $map ) {
		return $parts;
	}
	$products = wc_get_products(
		array(
			'status'       => 'publish',
			'limit'        => -1,
			'type'         => array( 'simple', 'variable' ),
			'product_category_id' => array_keys( $map ),
			'orderby'      => 'title',
			'order'        => 'ASC',
		)
	);
	foreach ( $products as $product ) {
		if ( 'hidden' === $product->get_catalog_visibility() ) {
			continue;
		}
		$slot = '';
		foreach ( $product->get_category_ids() as $cid ) {
			if ( isset( $map[ $cid ] ) ) {
				$slot = $map[ $cid ];
				break;
			}
		}
		if ( '' === $slot ) {
			continue;
		}
		$items = $product->is_type( 'variable' ) ? array_filter( array_map( 'wc_get_product', $product->get_children() ) ) : array( $product );
		foreach ( $items as $item ) {
			if ( ! $item->is_type( 'variation' ) && ! $item->is_type( 'simple' ) ) {
				continue;
			}
			if ( $item->is_type( 'variation' ) && 'publish' !== $item->get_status() ) {
				continue;
			}
			// Loại có thuộc tính "Bất kỳ…" cần khách chọn thêm giá trị, trang này không có ô chọn đó: bỏ qua.
			if ( $item->is_type( 'variation' ) && in_array( '', array_map( 'strval', $item->get_variation_attributes() ), true ) ) {
				continue;
			}
			$price = $item->get_price();
			$img   = wp_get_attachment_image_url( $item->get_image_id() ? $item->get_image_id() : $product->get_image_id(), 'woocommerce_thumbnail' );
			$parts[ $item->get_id() ] = array(
				'id'       => $item->get_id(),
				'slot'     => $slot,
				'name'     => wp_strip_all_tags( $item->get_name() ),
				'spec'     => tmt_pcb_summary( $item ),
				// Giá hiển thị theo cách tính thuế của cửa hàng (giống thẻ sản phẩm của theme).
				'price'    => '' === $price ? null : (int) round( (float) wc_get_price_to_display( $item ) ),
				'regular'  => $item->is_on_sale() ? (int) round( (float) wc_get_price_to_display( $item, array( 'price' => $item->get_regular_price() ) ) ) : null,
				'bh'       => tmt_pcb_warranty( $item ),
				'img'      => $img ? $img : null,
				'url'      => $item->get_permalink(),
				'in_stock' => $item->is_in_stock(),
				'buy'      => '' !== $price && $item->is_purchasable() && $item->is_in_stock(),
				// Số còn bán được khi có quản lý tồn và không cho đặt trước; chỉ dùng ở máy chủ, không gửi xuống trình duyệt.
				'avail'    => $item->managing_stock() && ! $item->backorders_allowed() ? max( 0, (int) $item->get_stock_quantity() ) : null,
				'specs'    => tmt_pcb_product_specs( $item ),
			);
		}
	}
	// Giá thấp trước, như app.
	uasort(
		$parts,
		function ( $a, $b ) {
			return array( null === $a['price'] ? PHP_INT_MAX : $a['price'], $a['name'] ) <=> array( null === $b['price'] ? PHP_INT_MAX : $b['price'], $b['name'] );
		}
	);
	// Có thuế thì giá hiển thị tùy khách (địa chỉ, miễn thuế): không lưu chung cho mọi người.
	if ( ! wc_tax_enabled() ) {
		set_transient( 'tmt_pcb_parts_' . TMT_TOOLS_VERSION, $parts, 10 * MINUTE_IN_SECONDS );
	}
	return $parts;
}

/** Sản phẩm, tồn kho, danh mục đổi thì tính lại danh sách linh kiện. */
function tmt_pcb_flush() {
	delete_transient( 'tmt_pcb_parts_' . TMT_TOOLS_VERSION );
}
foreach ( array( 'woocommerce_update_product', 'woocommerce_new_product', 'woocommerce_update_product_variation', 'woocommerce_product_set_stock_status', 'woocommerce_variation_set_stock_status', 'woocommerce_product_set_stock', 'woocommerce_variation_set_stock', 'woocommerce_delete_product', 'woocommerce_trash_product', 'edited_product_cat', 'created_product_cat', 'delete_product_cat', 'woocommerce_attribute_updated', 'edited_term', 'update_option_tmt_pcb_slot_categories' ) as $tmt_pcb_hook ) {
	add_action( $tmt_pcb_hook, 'tmt_pcb_flush' );
}
add_action( 'save_post_product', 'tmt_pcb_flush' );

/**
 * Dựng cấu hình từ [product_id => qty]: mỗi khe một món, món sau cùng khe thắng; bỏ mã không có trong danh sách.
 *
 * @param array<int, int> $items
 * @return array<string, array{part: array<string, mixed>, qty: int}> theo thứ tự khe
 */
function tmt_pcb_build( array $items ) {
	$parts = tmt_pcb_parts();
	$slots = tmt_pcb_slots();
	$tmp   = array();
	foreach ( $items as $id => $qty ) {
		$id = (int) $id;
		if ( isset( $parts[ $id ] ) ) {
			$slot          = $parts[ $id ]['slot'];
			$tmp[ $slot ] = array( 'part' => $parts[ $id ], 'qty' => max( 1, min( $slots[ $slot ]['max'], (int) $qty ) ) );
		}
	}
	$build = array();
	foreach ( array_keys( $slots ) as $slot ) {
		if ( isset( $tmp[ $slot ] ) ) {
			$build[ $slot ] = $tmp[ $slot ];
		}
	}
	return $build;
}

/** Chuỗi gọn để chia sẻ: "12*2,15". */
function tmt_pcb_code( array $build ) {
	return implode(
		',',
		array_map(
			function ( $i ) {
				return $i['part']['id'] . ( $i['qty'] > 1 ? '*' . $i['qty'] : '' );
			},
			array_values( $build )
		)
	);
}

/** @return array<int, int> Đọc "12*2,15" thành [product_id => qty]. */
function tmt_pcb_parse_code( $code ) {
	$out = array();
	foreach ( explode( ',', (string) $code ) as $token ) {
		if ( preg_match( '/^\s*(\d+)(?:\*(\d+))?\s*$/', $token, $m ) ) {
			$out[ (int) $m[1] ] = max( 1, (int) ( isset( $m[2] ) ? $m[2] : 1 ) );
		}
	}
	return array_slice( $out, 0, 20, true );
}
