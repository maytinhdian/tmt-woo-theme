<?php
/**
 * Section slider. Kiểu "banners": slider + 2 banner phụ bên phải. Kiểu "menu": menu danh mục dọc, slider + banner nhỏ, cột hotline.
 * Chưa có ảnh slide thì hiện khung chữ từ Tiêu đề + Mô tả (Tùy biến → Trang chủ).
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;
$style   = 'menu' === tmt_opt( 'home.slider_style' ) ? 'menu' : 'banners';
$slides  = tmt_home_slides();
$banners = tmt_home_banners();
$phone   = tmt_phone();
$zalo    = tmt_zalo_url();
$builder = tmt_builder_url();
$cats    = 'menu' === $style ? tmt_home_categories( 11 ) : array();
$n       = count( $slides );
?>
<section class="sf-home-top">
	<?php if ( $slides ) : ?><h1 class="visually-hidden"><?php echo esc_html( tmt_opt( 'home.hero_title' ) ); ?></h1><?php endif; ?>
	<div class="sf-wrap">
		<div class="sf-top-grid <?php echo esc_attr( $style ); ?>">
			<?php if ( 'menu' === $style ) : ?>
				<nav class="sf-vmenu" aria-label="Danh mục sản phẩm">
					<?php foreach ( $cats as $c ) : ?>
						<a href="<?php echo esc_url( get_term_link( $c ) ); ?>"><?php echo tmt_icon( tmt_category_icon( $c ) ); // phpcs:ignore ?><?php echo esc_html( $c->name ); ?><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
					<?php endforeach; ?>
					<?php if ( $builder ) : ?>
						<a href="<?php echo esc_url( $builder ); ?>"><i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i>Xây dựng cấu hình<i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
					<?php endif; ?>
				</nav>
				<nav class="sf-cats sf-mcats" aria-label="Danh mục sản phẩm">
					<?php foreach ( $cats as $c ) : ?>
						<a href="<?php echo esc_url( get_term_link( $c ) ); ?>"><span><?php echo tmt_icon( tmt_category_icon( $c ) ); // phpcs:ignore ?></span><?php echo esc_html( $c->name ); ?></a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>

			<div <?php echo 'menu' === $style ? 'class="sf-mid"' : 'style="display: contents"'; ?>>
				<div class="sf-slider" data-slider data-seconds="<?php echo (int) tmt_opt( 'home.slide_seconds' ); ?>" aria-roledescription="carousel" aria-label="Khuyến mãi nổi bật">
					<div class="sf-slides">
						<?php if ( $slides ) : ?>
							<?php foreach ( $slides as $i => $s ) : ?>
								<?php $tag = $s['link'] ? 'a' : 'div'; ?>
								<<?php echo $tag; // phpcs:ignore ?> class="sf-slide"<?php echo $s['link'] ? tmt_link_attrs( $s['link'] ) : ''; // phpcs:ignore ?> aria-roledescription="slide" aria-label="<?php echo (int) ( $i + 1 ) . ' / ' . (int) $n; ?>">
									<picture>
										<?php if ( $s['image_mobile'] ) : ?><source media="(max-width: 767px)" srcset="<?php echo esc_url( $s['image_mobile'] ); ?>"><?php endif; ?>
										<img src="<?php echo esc_url( $s['image'] ); ?>" alt="<?php echo esc_attr( $s['alt'] ); ?>"<?php echo $i ? ' loading="lazy"' : ' fetchpriority="high"'; ?>>
									</picture>
								</<?php echo $tag; // phpcs:ignore ?>>
							<?php endforeach; ?>
						<?php else : ?>
							<div class="sf-slide sf-slide-def">
								<h1><?php echo esc_html( tmt_opt( 'home.hero_title' ) ); ?></h1>
								<p><?php echo esc_html( tmt_opt( 'home.hero_text' ) ); ?></p>
								<div class="d-flex flex-wrap gap-2">
									<a href="<?php echo esc_url( tmt_shop_url() ); ?>" class="sf-btn">Xem sản phẩm</a>
									<?php if ( $builder ) : ?><a href="<?php echo esc_url( $builder ); ?>" class="sf-btn ghost" style="color:#fff;border-color:#fff">Tự xây dựng cấu hình</a><?php endif; ?>
								</div>
								<i class="fa-solid fa-computer big" aria-hidden="true"></i>
							</div>
						<?php endif; ?>
					</div>
					<button type="button" class="sf-sl-arr l" aria-label="Slide trước" hidden><i class="fa-solid fa-chevron-left"></i></button>
					<button type="button" class="sf-sl-arr r" aria-label="Slide sau" hidden><i class="fa-solid fa-chevron-right"></i></button>
					<div class="sf-sl-dots"></div>
				</div>

				<div class="sf-hside">
					<?php if ( $banners ) : ?>
						<?php foreach ( $banners as $b ) : ?>
							<a<?php echo $b['link'] ? tmt_link_attrs( $b['link'] ) : ' role="presentation"'; // phpcs:ignore ?>><img src="<?php echo esc_url( $b['image'] ); ?>" alt="<?php echo esc_attr( $b['alt'] ); ?>" loading="lazy"></a>
						<?php endforeach; ?>
					<?php else : ?>
						<?php if ( $builder ) : ?>
							<a href="<?php echo esc_url( $builder ); ?>" class="sf-htile y"><b>Tự xây dựng cấu hình máy tính</b><small>Web kiểm tra linh kiện lắp vừa, báo giá ngay.</small><span class="go">Bắt đầu chọn</span><i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i></a>
						<?php else : ?>
							<a href="<?php echo esc_url( tmt_shop_url() ); ?>" class="sf-htile y"><b>Linh kiện chính hãng, có serial</b><small>Bảo hành ngay tại tiệm.</small><span class="go">Xem sản phẩm</span><i class="fa-solid fa-microchip" aria-hidden="true"></i></a>
						<?php endif; ?>
						<?php if ( $zalo ) : ?>
							<a href="<?php echo esc_url( $zalo ); ?>" target="_blank" rel="noopener" class="sf-htile n"><b>Sửa máy, nâng cấp tại tiệm</b><small>Nhắn Zalo gửi ảnh máy lỗi, tiệm báo giá trước khi sửa.</small><span class="go">Nhắn Zalo</span><i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i></a>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( 'menu' === $style ) : ?>
				<div class="sf-rcol">
					<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
						<a class="sf-box d-block" href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>">
							<h3><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>Tra cứu đơn hàng</h3>
							<small class="mt-0">Xem đơn đã đặt và tình trạng giao hàng.</small>
						</a>
					<?php endif; ?>
					<?php if ( $phone ) : ?>
						<div class="sf-box y">
							<h3><i class="fa-solid fa-phone" aria-hidden="true"></i>Gọi hoặc Zalo</h3>
							<a class="hl" href="tel:<?php echo esc_attr( tmt_digits( $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
							<?php if ( tmt_opt( 'footer.hours' ) ) : ?><small><?php echo esc_html( tmt_opt( 'footer.hours' ) ); ?></small><?php endif; ?>
						</div>
					<?php endif; ?>
					<?php if ( $zalo ) : ?>
						<a class="sf-box d-block" href="<?php echo esc_url( $zalo ); ?>" target="_blank" rel="noopener">
							<h3><i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i>Đặt lịch sửa máy</h3>
							<small class="mt-0">Nhắn Zalo: kiểm tra miễn phí, báo giá trước khi sửa.</small>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
