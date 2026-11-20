<?php
/**
 * Gắn theme vào WooCommerce: thẻ sản phẩm, trang chi tiết, số món trong giỏ, nút Mua ngay.
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;

// Theme tự dựng khung trang và thanh bên.
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 ); // Theme tự in đường dẫn (tmt_breadcrumb).

/** CSS dàn trang của WooCommerce đè lưới/cột của theme: chỉ giữ CSS chung (form, giỏ, thanh toán). */
add_filter(
	'woocommerce_enqueue_styles',
	function ( $styles ) {
		unset( $styles['woocommerce-layout'] );
		return $styles;
	}
);

/**
 * Thông số của sản phẩm để hiện: thuộc tính đang bật "Hiển thị trên trang sản phẩm", theo thứ tự đã kéo.
 *
 * @return array<int, array{key: string, label: string, value: string, select: bool}>
 */
function tmt_product_specs( WC_Product $product ) {
	$out = array();
	foreach ( $product->get_attributes() as $attr ) {
		if ( ! $attr->get_visible() ) {
			continue;
		}
		if ( $attr->is_taxonomy() ) {
			$names = wc_get_product_terms( $product->get_id(), $attr->get_name(), array( 'fields' => 'names' ) );
			$label = wc_attribute_label( $attr->get_name() );
			$key   = substr( $attr->get_name(), 3 );
		} else {
			$names = $attr->get_options();
			$label = $attr->get_name();
			$key   = sanitize_title( $label );
		}
		$names = array_filter( array_map( 'trim', (array) $names ) );
		if ( $names ) {
			$out[] = array( 'key' => $key, 'label' => $label, 'value' => implode( ', ', $names ), 'select' => $attr->is_taxonomy() && ! $attr->get_variation() );
		}
	}
	return $out;
}

/** Phần trăm giảm so với giá gốc (giá thường), 0 khi không giảm. */
function tmt_discount_percent( WC_Product $product ) {
	if ( ! $product->is_on_sale() ) {
		return 0;
	}
	if ( $product->is_type( 'variable' ) ) {
		$max = 0;
		foreach ( $product->get_variation_prices()['regular_price'] as $vid => $regular ) {
			$sale = $product->get_variation_prices()['sale_price'][ $vid ];
			if ( $regular > 0 && $sale < $regular ) {
				$max = max( $max, (int) round( 100 - $sale * 100 / $regular ) );
			}
		}
		return $max;
	}
	$regular = (float) $product->get_regular_price();
	$sale    = (float) $product->get_sale_price();
	return $regular > 0 && $sale > 0 && $sale < $regular ? (int) round( 100 - $sale * 100 / $regular ) : 0;
}

/** Sản phẩm chưa nhập giá: hiện "Liên hệ báo giá" thay giá 0đ. */
function tmt_no_price( WC_Product $product ) {
	return '' === $product->get_price();
}

add_filter(
	'woocommerce_empty_price_html',
	function () {
		return '<span class="sf-price sf-price-ask">Liên hệ báo giá</span>';
	}
);

/** Thẻ sản phẩm dùng ở mọi lưới (danh sách, trang chủ, cùng loại). Template: template-parts/product/card.php. */
function tmt_product_card( $product = null ) {
	$product = wc_get_product( $product ? $product : get_the_ID() );
	if ( $product ) {
		get_template_part( 'template-parts/product/card', null, array( 'product' => $product ) );
	}
}

/** Huy hiệu số món trong giỏ (WooCommerce tự cập nhật qua fragment khi thêm giỏ bằng AJAX). */
function tmt_cart_count_badge() {
	$n = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	echo '<span class="sf-cart-n' . ( $n ? '' : ' d-none' ) . '">' . (int) $n . '</span>';
}
add_filter(
	'woocommerce_add_to_cart_fragments',
	function ( $fragments ) {
		ob_start();
		tmt_cart_count_badge();
		$fragments['.sf-cart-n'] = ob_get_clean();
		return $fragments;
	}
);

/** Nút "Mua ngay" cạnh "Thêm vào giỏ": thêm rồi sang thẳng trang thanh toán. */
add_action(
	'woocommerce_after_add_to_cart_button',
	function () {
		global $product;
		// formaction mang cờ tmt_buy_now; sản phẩm thường cần thêm add-to-cart (nút Thêm vào giỏ giữ field này nhưng không được bấm).
		$name = $product && ! $product->is_type( 'variable' ) && ! $product->is_type( 'grouped' ) ? ' name="add-to-cart" value="' . (int) $product->get_id() . '"' : '';
		echo '<button type="submit"' . $name . ' formaction="' . esc_url( add_query_arg( 'tmt_buy_now', '1', get_permalink() ) ) . '" class="sf-btn blue sf-buy-now">Mua ngay</button>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
);
add_filter(
	'woocommerce_add_to_cart_redirect',
	function ( $url ) {
		return empty( $_REQUEST['tmt_buy_now'] ) ? $url : wc_get_checkout_url(); // phpcs:ignore WordPress.Security.NonceVerification
	}
);
add_filter(
	'woocommerce_product_single_add_to_cart_text',
	function () {
		return 'Thêm vào giỏ';
	}
);
add_filter(
	'woocommerce_product_add_to_cart_text',
	function ( $text, $product ) {
		if ( $product->is_type( 'variable' ) ) {
			return 'Chọn loại';
		}
		return $product->is_purchasable() && $product->is_in_stock() ? 'Thêm vào giỏ' : 'Xem chi tiết';
	},
	10,
	2
);

/** Số sản phẩm cùng loại. */
add_filter(
	'woocommerce_output_related_products_args',
	function ( $args ) {
		$args['posts_per_page'] = max( 0, min( 12, (int) tmt_opt( 'product.related_count' ) ) );
		$args['columns']        = 4;
		return $args;
	}
);

/** Trang chi tiết: theme tự in khung ảnh, tóm tắt, thông số, mô tả (template woocommerce/content-single-product.php). */
remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );

/** Danh sách: theme tự in tiêu đề, bộ lọc, lưới, phân trang (template woocommerce/archive-product.php). */
add_filter( 'woocommerce_show_page_title', '__return_false' );

/** Ảnh sản phẩm: chính + thư viện. */
function tmt_product_images( WC_Product $product, $size = 'woocommerce_single' ) {
	$ids = array_filter( array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() ) );
	$out = array();
	foreach ( array_unique( $ids ) as $id ) {
		$src = wp_get_attachment_image_url( $id, $size );
		if ( $src ) {
			$out[] = array( 'id' => $id, 'src' => $src, 'full' => wp_get_attachment_image_url( $id, 'full' ), 'thumb' => wp_get_attachment_image_url( $id, 'woocommerce_gallery_thumbnail' ) );
		}
	}
	return $out;
}

/** Hãng của sản phẩm (taxonomy product_brand của WooCommerce). */
function tmt_product_brand( WC_Product $product ) {
	if ( ! taxonomy_exists( 'product_brand' ) ) {
		return null;
	}
	$terms = get_the_terms( $product->get_id(), 'product_brand' );
	return $terms && ! is_wp_error( $terms ) ? $terms[0] : null;
}

/** Danh mục chính (sâu nhất) của sản phẩm, dùng chọn icon khi chưa có ảnh. */
function tmt_product_category( WC_Product $product ) {
	$terms = get_the_terms( $product->get_id(), 'product_cat' );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return null;
	}
	usort(
		$terms,
		function ( $a, $b ) {
			return count( get_ancestors( $b->term_id, 'product_cat' ) ) - count( get_ancestors( $a->term_id, 'product_cat' ) );
		}
	);
	return $terms[0];
}

/** Ô "Bảo hành (tháng)" ở tab Chung của sản phẩm: hiện trên thẻ, trang chi tiết, bảng thông số. */
add_action(
	'woocommerce_product_options_general_product_data',
	function () {
		woocommerce_wp_text_input(
			array(
				'id'                => '_tmt_warranty_months',
				'label'             => 'Bảo hành (tháng)',
				'type'              => 'number',
				'custom_attributes' => array( 'min' => 0, 'step' => 1 ),
				'desc_tip'          => true,
				'description'       => 'Hiện "Bảo hành x tháng tại tiệm" trên thẻ và trang sản phẩm. Để trống thì không hiện.',
			)
		);
	}
);
add_action(
	'woocommerce_admin_process_product_object',
	function ( $product ) {
		if ( isset( $_POST['_tmt_warranty_months'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce đã kiểm nonce khi lưu sản phẩm.
			$m = absint( wp_unslash( $_POST['_tmt_warranty_months'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			$product->update_meta_data( '_tmt_warranty_months', $m ? $m : '' );
		}
	}
);

function tmt_warranty_months( WC_Product $product ) {
	$id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
	return (int) get_post_meta( $id, '_tmt_warranty_months', true );
}

/** Vòng lặp lưới sản phẩm + phân trang, hoặc ô "chưa có sản phẩm". */
function tmt_product_loop() {
	if ( woocommerce_product_loop() ) {
		woocommerce_product_loop_start();
		if ( wc_get_loop_prop( 'total' ) ) {
			while ( have_posts() ) {
				the_post();
				do_action( 'woocommerce_shop_loop' );
				wc_get_template_part( 'content', 'product' );
			}
		}
		woocommerce_product_loop_end();
		echo '<div class="sf-pager">';
		woocommerce_pagination();
		echo '</div>';
		return;
	}
	$phone = tmt_phone();
	echo '<div class="sf-empty"><p>Chưa có sản phẩm phù hợp.</p>';
	if ( $phone ) {
		echo '<p class="sf-muted">Gọi hoặc nhắn Zalo ' . esc_html( $phone ) . ', tiệm tìm hàng giúp.</p>';
	}
	if ( tmt_has_filters() ) {
		echo '<a class="sf-btn" href="' . esc_url( tmt_filter_reset_url() ) . '">Bỏ lọc</a>';
	}
	echo '</div>';
}

/** Danh sách đã tự in thông báo; bỏ ô đếm "Hiển thị x–y" và ô sắp xếp mặc định của WooCommerce. */
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_output_all_notices', 10 );
