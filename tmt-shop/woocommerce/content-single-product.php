<?php
/**
 * Trang chi tiết sản phẩm: khung ảnh tự chuyển (Swiper) + tóm tắt (hãng, mã, tên, thông số chính, chọn loại, giá,
 * tồn, nút mua, Zalo, ưu đãi), thanh mục lục, thông số kỹ thuật, mô tả, sản phẩm cùng loại, thanh mua dính đáy trên điện thoại.
 * Nút mua dùng form của WooCommerce (hook woocommerce_single_product_summary) nên plugin thêm vào vẫn hiện.
 *
 * @package TMT_Shop
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

do_action( 'woocommerce_before_single_product' );
if ( post_password_required() ) {
	echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput
	return;
}

$tmt_images   = tmt_product_images( $product );
$tmt_specs    = tmt_product_specs( $product );
$tmt_brand    = tmt_product_brand( $product );
$tmt_cat      = tmt_product_category( $product );
$tmt_off      = tmt_opt( 'product.discount_badge' ) ? tmt_discount_percent( $product ) : 0;
$tmt_zalo     = tmt_opt( 'product.show_zalo' ) ? tmt_zalo_url() : null;
$tmt_warranty = tmt_warranty_months( $product );
$tmt_desc     = trim( (string) $product->get_description() );
$tmt_related  = wc_get_related_products( $product->get_id(), max( 0, (int) tmt_opt( 'product.related_count' ) ) );
$tmt_tabs     = array_filter(
	array(
		'thong-so'  => $tmt_specs ? 'Thông số' : null,
		'mo-ta'     => '' !== $tmt_desc ? 'Mô tả' : null,
		'danh-gia'  => comments_open() && wc_review_ratings_enabled() ? 'Đánh giá' : null,
		'cung-loai' => $tmt_related ? 'Cùng loại' : null,
	)
);
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'sf-product', $product ); ?>>
	<?php tmt_promos( 'top' ); ?>
	<section class="sf-pd">
		<div class="sf-pd-col">
			<div class="sf-gal" data-gallery data-delay="4000">
				<div class="sf-gal-main swiper" data-gallery-main>
					<?php if ( $tmt_off ) : ?><span class="sf-gal-off">GIẢM <?php echo (int) $tmt_off; ?>%</span><?php endif; ?>
					<div class="swiper-wrapper">
						<?php if ( $tmt_images ) : ?>
							<?php foreach ( $tmt_images as $i => $img ) : ?>
								<div class="swiper-slide" data-image-id="<?php echo (int) $img['id']; ?>">
									<div class="swiper-zoom-container"><img src="<?php echo esc_url( $img['src'] ); ?>" data-full="<?php echo esc_url( $img['full'] ); ?>" alt="<?php echo esc_attr( $product->get_name() . ( $i ? ' (ảnh ' . ( $i + 1 ) . ')' : '' ) ); ?>"<?php echo $i ? ' loading="lazy"' : ''; ?>></div>
								</div>
							<?php endforeach; ?>
						<?php else : ?>
							<div class="swiper-slide sf-gal-empty"><?php echo tmt_icon( tmt_category_icon( $tmt_cat ) ); // phpcs:ignore ?></div>
						<?php endif; ?>
					</div>
					<?php if ( count( $tmt_images ) > 1 ) : ?>
						<button type="button" class="sf-gal-nav prev" data-gallery-prev aria-label="Ảnh trước"><?php echo tmt_icon( 'chevron_left' ); // phpcs:ignore ?></button>
						<button type="button" class="sf-gal-nav next" data-gallery-next aria-label="Ảnh sau"><?php echo tmt_icon( 'chevron_right' ); // phpcs:ignore ?></button>
						<div class="sf-gal-dots" data-gallery-dots></div>
						<span class="sf-gal-count" data-gallery-counter>1 / <?php echo count( $tmt_images ); ?></span>
					<?php endif; ?>
					<?php if ( $tmt_images ) : ?>
						<button type="button" class="sf-gal-full" data-gallery-full aria-label="Xem ảnh toàn màn hình"><?php echo tmt_icon( 'fullscreen' ); // phpcs:ignore ?></button>
					<?php endif; ?>
					<button type="button" class="sf-gal-close" data-gallery-close aria-label="Đóng"><?php echo tmt_icon( 'close' ); // phpcs:ignore ?></button>
				</div>
				<?php if ( count( $tmt_images ) > 1 ) : ?>
					<div class="sf-gal-thumbs swiper" data-gallery-thumbs>
						<div class="swiper-wrapper">
							<?php foreach ( $tmt_images as $img ) : ?>
								<div class="swiper-slide"><img src="<?php echo esc_url( $img['thumb'] ); ?>" alt="" loading="lazy"></div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
			<?php tmt_promos( 'gallery' ); ?>
		</div>
		<div class="sf-pd-col">
			<div class="sf-sum summary entry-summary">
				<div class="sf-sum-brand">
					<?php if ( $tmt_brand ) : ?><a class="tag" href="<?php echo esc_url( get_term_link( $tmt_brand ) ); ?>"><?php echo esc_html( $tmt_brand->name ); ?></a><?php endif; ?>
					<span><?php if ( $product->get_sku() ) : ?>Mã SP: <?php echo esc_html( $product->get_sku() ); ?><?php endif; ?><?php if ( $tmt_cat ) : ?><?php echo $product->get_sku() ? ' · ' : ''; ?><?php echo esc_html( $tmt_cat->name ); ?><?php endif; ?></span>
				</div>
				<h1 class="sf-sum-name product_title"><?php the_title(); ?></h1>
				<?php if ( $tmt_specs ) : ?>
					<div class="sf-keyspecs">
						<?php foreach ( array_slice( $tmt_specs, 0, 5 ) as $s ) : ?><span><b><?php echo esc_html( $s['label'] ); ?></b> <?php echo esc_html( $s['value'] ); ?></span><?php endforeach; ?>
					</div>
				<?php endif; ?>
				<?php tmt_promos( 'above_price' ); ?>
				<div class="sf-pricebox">
					<div class="sf-pricebox-row price"><?php echo wp_kses_post( $product->get_price_html() ); ?><?php if ( $tmt_off ) : ?><span class="sf-off-pill">-<?php echo (int) $tmt_off; ?>%</span><?php endif; ?></div>
					<?php if ( $product->is_in_stock() ) : ?>
						<span class="sf-stock-line ok"><?php echo tmt_icon( 'check_circle' ); // phpcs:ignore ?><b>Còn hàng</b> tại tiệm</span>
					<?php else : ?>
						<span class="sf-stock-line"><?php echo tmt_icon( 'schedule' ); // phpcs:ignore ?><b>Tạm hết</b>, gọi tiệm để hỏi ngày có hàng</span>
					<?php endif; ?>
				</div>
				<?php tmt_promos( 'below_price' ); ?>
				<div class="sf-buy-wrap" data-variant-style="<?php echo esc_attr( tmt_opt( 'product.variant_style' ) ); ?>">
					<?php do_action( 'woocommerce_single_product_summary' ); ?>
				</div>
				<?php if ( $tmt_zalo ) : ?>
					<a href="<?php echo esc_url( $tmt_zalo ); ?>" target="_blank" rel="noopener" class="sf-zalo"><?php echo tmt_icon( 'chat' ); // phpcs:ignore ?>Hỏi tiệm qua Zalo <?php echo esc_html( tmt_phone() ); ?></a>
				<?php endif; ?>
				<?php tmt_promos( 'below_buy' ); ?>
				<?php
				$tmt_perks = array();
				if ( $tmt_warranty ) {
					$tmt_perks[] = array( 'verified_user', 'Bảo hành ' . $tmt_warranty . ' tháng tại tiệm' );
				}
				$tmt_icons = array( 'local_shipping', 'build', 'payments', 'qr_code_2', 'support_agent' );
				foreach ( tmt_opt_lines( 'product.perks' ) as $i => $perk ) {
					$tmt_perks[] = array( $tmt_icons[ $i % count( $tmt_icons ) ], $perk );
				}
				?>
				<?php if ( $tmt_perks ) : ?>
					<ul class="sf-perks">
						<?php foreach ( $tmt_perks as $p ) : ?><li><?php echo tmt_icon( $p[0] ); // phpcs:ignore ?><?php echo esc_html( $p[1] ); ?></li><?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<?php if ( count( $tmt_tabs ) > 1 ) : ?>
		<nav class="sf-tabs" aria-label="Các phần của trang">
			<?php foreach ( $tmt_tabs as $id => $label ) : ?><a href="#<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></a><?php endforeach; ?>
		</nav>
	<?php endif; ?>

	<?php if ( $tmt_specs || $tmt_brand || $tmt_warranty ) : ?>
		<section class="sf-panel" id="thong-so">
			<h2 class="sf-panel-h">Thông số kỹ thuật</h2>
			<table class="sf-spec">
				<tbody>
					<?php foreach ( $tmt_specs as $s ) : ?><tr><th scope="row"><?php echo esc_html( $s['label'] ); ?></th><td><?php echo esc_html( $s['value'] ); ?></td></tr><?php endforeach; ?>
					<?php if ( $tmt_brand ) : ?><tr><th scope="row">Hãng</th><td><?php echo esc_html( $tmt_brand->name ); ?></td></tr><?php endif; ?>
					<?php if ( $tmt_warranty ) : ?><tr><th scope="row">Bảo hành</th><td><?php echo (int) $tmt_warranty; ?> tháng tại tiệm</td></tr><?php endif; ?>
				</tbody>
			</table>
		</section>
	<?php endif; ?>

	<?php if ( '' !== $tmt_desc ) : ?>
		<section class="sf-panel" id="mo-ta">
			<h2 class="sf-panel-h">Mô tả sản phẩm</h2>
			<div class="sf-desc entry-content"><?php the_content(); ?></div>
		</section>
	<?php endif; ?>
	<?php tmt_promos( 'below_desc' ); ?>

	<?php if ( isset( $tmt_tabs['danh-gia'] ) ) : ?>
		<section class="sf-panel" id="danh-gia"><?php comments_template(); ?></section>
	<?php endif; ?>

	<?php do_action( 'woocommerce_after_single_product_summary' ); ?>

	<?php if ( $tmt_related ) : ?>
		<section class="sf-related" id="cung-loai" data-carousel-wrap>
			<div class="sf-sec-head">
				<h2 class="sf-h2">Sản phẩm cùng loại</h2>
				<?php if ( count( $tmt_related ) > 2 ) : ?>
					<div class="d-flex gap-2">
						<button type="button" class="sf-round" data-carousel-prev aria-label="Sản phẩm trước"><?php echo tmt_icon( 'chevron_left' ); // phpcs:ignore ?></button>
						<button type="button" class="sf-round" data-carousel-next aria-label="Sản phẩm sau"><?php echo tmt_icon( 'chevron_right' ); // phpcs:ignore ?></button>
					</div>
				<?php endif; ?>
			</div>
			<div class="swiper" data-carousel>
				<div class="swiper-wrapper">
					<?php foreach ( $tmt_related as $rid ) : ?><div class="swiper-slide"><?php tmt_product_card( $rid ); ?></div><?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<div class="sf-mbuy" data-sf-bottom-bar>
		<?php if ( $tmt_zalo ) : ?>
			<a href="<?php echo esc_url( $tmt_zalo ); ?>" target="_blank" rel="noopener" class="z" aria-label="Hỏi qua Zalo"><?php echo tmt_icon( 'chat' ); // phpcs:ignore ?>Zalo</a>
		<?php endif; ?>
		<?php if ( $product->is_purchasable() && $product->is_in_stock() ) : ?>
			<button type="button" class="sf-btn" data-sf-buy="add"><?php echo tmt_icon( 'add_shopping_cart' ); // phpcs:ignore ?>Thêm giỏ</button>
			<button type="button" class="sf-btn blue" data-sf-buy="now">Mua ngay</button>
		<?php elseif ( $tmt_zalo ) : ?>
			<a href="<?php echo esc_url( $tmt_zalo ); ?>" target="_blank" rel="noopener" class="sf-btn blue sf-mbuy-wide">Hỏi giá qua Zalo</a>
		<?php endif; ?>
	</div>
</div>
<?php
do_action( 'woocommerce_after_single_product' );
