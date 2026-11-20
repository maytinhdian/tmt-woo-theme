<?php
/**
 * Thẻ sản phẩm kiểu "thông số nổi bật": nhãn thông số trên ảnh (socket, RAM...), vài dòng thông số, giá,
 * Còn hàng / Tạm hết, nút Thêm vào giỏ (AJAX của WooCommerce) và Zalo.
 * Sản phẩm nhiều loại (biến thể): "N loại", "Từ ...", nút Chọn loại.
 * Mọi lưới sản phẩm gọi tmt_product_card(), không tự viết thẻ khác.
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;

/** @var WC_Product $product */
$product  = $args['product'];
$url      = $product->get_permalink();
$name     = $product->get_name();
$specs    = tmt_product_specs( $product );
$tag_max  = max( 0, min( 4, (int) tmt_opt( 'product.spec_tags' ) ) );
$tags     = array_slice( array_values( array_filter( $specs, function ( $s ) { return $s['select']; } ) ), 0, $tag_max );
$tag_keys = wp_list_pluck( $tags, 'key' );
$lines    = array();
foreach ( $specs as $s ) {
	if ( ! in_array( $s['key'], $tag_keys, true ) && count( $lines ) < 2 ) {
		$lines[] = array( $s['label'], $s['value'] );
	}
}
$brand = tmt_product_brand( $product );
if ( count( $lines ) < 2 && $brand ) {
	array_unshift( $lines, array( 'Hãng', $brand->name ) );
}
$warranty = tmt_warranty_months( $product );
if ( $warranty ) {
	$lines[] = array( 'Bảo hành', $warranty . ' tháng tại tiệm' );
}
$lines     = array_slice( $lines, 0, 3 );
$off       = tmt_opt( 'product.discount_badge' ) ? tmt_discount_percent( $product ) : 0;
$variable  = $product->is_type( 'variable' );
$no_price  = tmt_no_price( $product );
$zalo      = tmt_opt( 'product.show_zalo' ) ? tmt_zalo_url() : null;
$fx        = tmt_effects()['product'];
$images    = $product->get_gallery_image_ids();
$alt_image = 'swap' === $fx && $product->get_image_id() && $images ? wp_get_attachment_image_url( $images[0], 'tmt-card' ) : null;
$cat       = tmt_product_category( $product );
$in_stock  = $product->is_in_stock();
$count     = $variable ? count( $product->get_children() ) : 0;
?>
<div class="sf-pcard">
	<a href="<?php echo esc_url( $url ); ?>" class="sf-pcard-img" tabindex="-1" aria-hidden="true">
		<?php if ( $product->get_image_id() ) : ?>
			<?php echo wp_get_attachment_image( $product->get_image_id(), 'tmt-card', false, array( 'alt' => $name, 'loading' => 'lazy' ) ); ?>
			<?php if ( $alt_image ) : ?><img src="<?php echo esc_url( $alt_image ); ?>" alt="" loading="lazy" class="sf-pcard-alt"><?php endif; ?>
		<?php else : ?>
			<?php echo tmt_icon( tmt_category_icon( $cat ) ); // phpcs:ignore ?>
		<?php endif; ?>
		<?php if ( $off ) : ?><span class="sf-pcard-off">-<?php echo (int) $off; ?>%</span><?php endif; ?>
		<?php echo function_exists( 'tmt_km_badge' ) ? tmt_km_badge( $product ) : ''; // phpcs:ignore -- "Có quà" / "Mua kèm giảm" (plugin). ?>
		<?php if ( $variable && $count > 1 ) : ?><span class="sf-pcard-vcount"><?php echo tmt_icon( 'layers' ); // phpcs:ignore ?><?php echo (int) $count; ?> loại</span><?php endif; ?>
		<?php if ( $tags ) : ?>
			<span class="sf-pcard-tags"><?php foreach ( $tags as $t ) : ?><span><?php echo esc_html( $t['value'] ); ?></span><?php endforeach; ?></span>
		<?php endif; ?>
	</a>
	<div class="sf-pcard-b">
		<a href="<?php echo esc_url( $url ); ?>" class="sf-pcard-name"><?php echo esc_html( $name ); ?></a>
		<?php if ( $lines ) : ?>
			<dl class="sf-pcard-specs">
				<?php foreach ( $lines as $l ) : ?><div><dt><?php echo esc_html( $l[0] ); ?></dt><dd><?php echo esc_html( $l[1] ); ?></dd></div><?php endforeach; ?>
			</dl>
		<?php endif; ?>
		<div class="sf-pcard-pr">
			<span class="sf-pcard-money">
				<?php if ( $no_price ) : ?>
					<span class="sf-price sf-price-ask">Liên hệ báo giá</span>
				<?php elseif ( $variable ) : ?>
					<?php
					$min = (float) $product->get_variation_price( 'min', true );
					$max = (float) $product->get_variation_price( 'max', true );
					?>
					<span class="sf-price"><?php if ( $min !== $max ) : ?><small>Từ</small> <?php endif; ?><?php echo wp_kses_post( wc_price( $min ) ); ?></span>
				<?php else : ?>
					<span class="sf-price"><?php echo wp_kses_post( wc_price( wc_get_price_to_display( $product ) ) ); ?></span>
					<?php if ( $product->is_on_sale() ) : ?><s class="sf-old"><?php echo wp_kses_post( wc_price( wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) ) ) ); ?></s><?php endif; ?>
				<?php endif; ?>
			</span>
			<?php if ( $in_stock ) : ?><span class="sf-stock-tag ok">Còn hàng</span><?php else : ?><span class="sf-stock-tag">Tạm hết</span><?php endif; ?>
		</div>
		<div class="sf-pcard-btns">
			<?php if ( $variable ) : ?>
				<a href="<?php echo esc_url( $url ); ?>" class="sf-btn"><?php echo tmt_icon( 'tune' ); // phpcs:ignore ?>Chọn loại</a>
			<?php elseif ( $no_price && function_exists( 'tmt_req_quote_form' ) ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'bao-gia', 1, $url ) ); ?>" class="sf-btn sf-pcard-quote" rel="nofollow"><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i>Báo giá</a>
			<?php elseif ( $no_price || ! $product->is_purchasable() ) : ?>
				<a href="<?php echo esc_url( $zalo ? $zalo : $url ); ?>" class="sf-btn sf-pcard-quote"<?php echo $zalo ? ' target="_blank" rel="noopener"' : ''; ?>><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i>Hỏi giá</a>
			<?php else : ?>
				<a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" data-quantity="1" data-product_id="<?php echo (int) $product->get_id(); ?>" data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>" class="sf-btn add_to_cart_button<?php echo $product->supports( 'ajax_add_to_cart' ) ? ' ajax_add_to_cart' : ''; ?>" rel="nofollow" aria-label="<?php echo esc_attr( 'Thêm ' . $name . ' vào giỏ' ); ?>"><?php echo tmt_icon( 'add_shopping_cart' ); // phpcs:ignore ?>Thêm vào giỏ</a>
			<?php endif; ?>
			<?php if ( $zalo ) : ?>
				<a href="<?php echo esc_url( $zalo ); ?>" target="_blank" rel="noopener" class="sf-pcard-zalo" aria-label="<?php echo esc_attr( 'Hỏi về ' . $name . ' qua Zalo' ); ?>"><?php echo tmt_icon( 'chat' ); // phpcs:ignore ?></a>
			<?php endif; ?>
		</div>
	</div>
</div>
