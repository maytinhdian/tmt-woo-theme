<?php
/**
 * Sản phẩm ngừng kinh doanh (chép ý từ app: "Đang bán" tắt nhưng "Hiện trên web" bật).
 *
 * Trang sản phẩm vẫn mở để khách tra thông số, nhưng: không mua được, không hiện ở danh sách / tìm kiếm / cùng loại /
 * Lên cấu hình (đặt "Hiển thị danh mục" = Ẩn, nhớ giá trị cũ để bỏ đánh dấu là trả lại), khung giá + nút mua thay bằng
 * hộp "Ngừng kinh doanh" kèm sản phẩm thay thế nếu sản phẩm đó đang bán. Schema Offer = Discontinued.
 *
 * Meta: _tmt_eol = yes, _tmt_replacement = ID sản phẩm thay thế, _tmt_eol_vis = hiển thị danh mục trước khi ngừng.
 * Theme gọi tmt_eol() và tmt_eol_box().
 *
 * @package TMT_Shop_Tools
 */

defined( 'ABSPATH' ) || exit;

/** Sản phẩm (hoặc sản phẩm cha của biến thể) đã ngừng kinh doanh? */
function tmt_eol( $product ) {
	$product = $product instanceof WC_Product ? $product : wc_get_product( $product );
	if ( ! $product ) {
		return false;
	}
	$id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
	return 'yes' === get_post_meta( $id, '_tmt_eol', true );
}

/** Sản phẩm thay thế đang bán (đã đăng, không ngừng, mua được), không có thì null. */
function tmt_eol_replacement( WC_Product $product ) {
	$rid = (int) $product->get_meta( '_tmt_replacement' );
	if ( ! $rid || $rid === $product->get_id() ) {
		return null;
	}
	$r = wc_get_product( $rid );
	if ( ! $r || 'publish' !== get_post_status( $r->get_parent_id() ? $r->get_parent_id() : $r->get_id() ) || tmt_eol( $r ) ) {
		return null;
	}
	return $r;
}

/** Hộp "Ngừng kinh doanh" thay cho khung giá + nút mua. */
function tmt_eol_box( WC_Product $product ) {
	$r = tmt_eol_replacement( $product );
	?>
	<div class="sf-eol">
		<span class="sf-eol-tag"><i class="fa-solid fa-ban" aria-hidden="true"></i>Ngừng kinh doanh</span>
		<p>Tiệm không còn bán mẫu này. Thông số vẫn giữ để tra cứu.</p>
		<?php if ( $r ) : ?>
			<?php $img = $r->get_image_id() ? wp_get_attachment_image_url( $r->get_image_id(), 'woocommerce_thumbnail' ) : ''; ?>
			<a href="<?php echo esc_url( $r->get_permalink() ); ?>" class="sf-eol-alt" data-replacement>
				<?php if ( $img ) : ?>
					<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $r->get_name() ); ?>" loading="lazy">
				<?php else : ?>
					<span class="ph"><i class="fa-solid fa-box" aria-hidden="true"></i></span>
				<?php endif; ?>
				<span>
					<small>Mẫu thay thế</small>
					<span class="nm"><?php echo esc_html( $r->get_name() ); ?></span>
					<span class="pr"><?php echo '' === $r->get_price() ? 'Liên hệ báo giá' : wp_kses_post( wc_price( wc_get_price_to_display( $r ) ) ); ?></span>
				</span>
				<span class="go">Xem<i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
			</a>
		<?php endif; ?>
	</div>
	<?php
}

// Không mua được (cả biến thể của sản phẩm ngừng).
add_filter(
	'woocommerce_is_purchasable',
	function ( $ok, $product ) {
		return $ok && ! tmt_eol( $product );
	},
	20,
	2
);
add_filter(
	'woocommerce_variation_is_purchasable',
	function ( $ok, $variation ) {
		return $ok && ! tmt_eol( $variation );
	},
	20,
	2
);

// Schema: Offer.availability = Discontinued.
add_filter(
	'woocommerce_structured_data_product_offer',
	function ( $offer, $product ) {
		if ( tmt_eol( $product ) ) {
			$offer['availability'] = 'https://schema.org/Discontinued';
		}
		return $offer;
	},
	10,
	2
);

// Danh sách sản phẩm trong quản trị: nhãn "Ngừng kinh doanh" sau tên.
add_filter(
	'display_post_states',
	function ( $states, $post ) {
		if ( 'product' === $post->post_type && 'yes' === get_post_meta( $post->ID, '_tmt_eol', true ) ) {
			$states['tmt_eol'] = 'Ngừng kinh doanh';
		}
		return $states;
	},
	10,
	2
);

// Ô trong tab "Kho hàng" của sản phẩm.
add_action(
	'woocommerce_product_options_inventory_product_data',
	function () {
		global $product_object;
		$rid = $product_object ? (int) $product_object->get_meta( '_tmt_replacement' ) : 0;
		$r   = $rid ? wc_get_product( $rid ) : null;
		echo '<div class="options_group">';
		woocommerce_wp_checkbox(
			array(
				'id'          => '_tmt_eol',
				'label'       => 'Ngừng kinh doanh',
				'description' => 'Giữ trang thông số cho khách tra, bỏ khỏi danh sách và không cho mua.',
				'value'       => $product_object && 'yes' === $product_object->get_meta( '_tmt_eol' ) ? 'yes' : 'no',
			)
		);
		?>
		<p class="form-field">
			<label for="_tmt_replacement">Sản phẩm thay thế</label>
			<select class="wc-product-search" style="width:50%" id="_tmt_replacement" name="_tmt_replacement" data-placeholder="Tìm sản phẩm..." data-action="woocommerce_json_search_products" data-exclude="<?php echo (int) ( $product_object ? $product_object->get_id() : 0 ); ?>" data-allow_clear="true">
				<?php if ( $r ) : ?><option value="<?php echo (int) $rid; ?>" selected><?php echo esc_html( wp_strip_all_tags( $r->get_formatted_name() ) ); ?></option><?php endif; ?>
			</select>
			<?php echo wc_help_tip( 'Hiện trên trang sản phẩm ngừng kinh doanh, khi sản phẩm thay thế đang bán.' ); // phpcs:ignore ?>
		</p>
		<?php
		echo '</div>';
	}
);

add_action(
	'woocommerce_admin_process_product_object',
	function ( WC_Product $product ) {
		// phpcs:disable WordPress.Security.NonceVerification -- WooCommerce đã kiểm nonce khi lưu sản phẩm.
		$eol = ! empty( $_POST['_tmt_eol'] );
		$rid = isset( $_POST['_tmt_replacement'] ) ? absint( $_POST['_tmt_replacement'] ) : 0;
		// phpcs:enable
		$was = 'yes' === $product->get_meta( '_tmt_eol' );
		if ( $eol && ! $was ) {
			$product->update_meta_data( '_tmt_eol_vis', $product->get_catalog_visibility() );
			$product->update_meta_data( '_tmt_eol', 'yes' );
		} elseif ( ! $eol && $was ) {
			$prev = (string) $product->get_meta( '_tmt_eol_vis' );
			$product->delete_meta_data( '_tmt_eol' );
			$product->delete_meta_data( '_tmt_eol_vis' );
			$product->set_catalog_visibility( in_array( $prev, array( 'visible', 'catalog', 'search', 'hidden' ), true ) ? $prev : 'visible' );
		}
		if ( $eol ) {
			// Đặt sau lần đọc ở trên: ô "Hiển thị danh mục" trong form có thể vừa gửi giá trị khác.
			$product->set_catalog_visibility( 'hidden' );
		}
		if ( $rid && $rid !== $product->get_id() ) {
			$product->update_meta_data( '_tmt_replacement', $rid );
		} else {
			$product->delete_meta_data( '_tmt_replacement' );
		}
	},
	20
);
