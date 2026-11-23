<?php
/**
 * Phần thân chân trang. Nội dung chỉnh ở Tùy biến → Chân trang và Thông tin cửa hàng;
 * cột Mua hàng / Hỗ trợ lấy từ Giao diện → Menu (vị trí "Chân trang"), chưa gán thì tự điền.
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;

$tmt_phone   = tmt_phone();
$tmt_map     = trim( (string) tmt_opt( 'footer.map_query' ) ) ?: tmt_address();
$tmt_socials = array_filter(
	array(
		'zalo'     => array( tmt_zalo_url( tmt_opt( 'footer.zalo' ) ), 'Zalo', null ),
		'facebook' => array( tmt_opt( 'footer.facebook' ), 'Facebook', 'fa-facebook-f' ),
		'tiktok'   => array( tmt_opt( 'footer.tiktok' ), 'TikTok', 'fa-tiktok' ),
		'youtube'  => array( tmt_opt( 'footer.youtube' ), 'YouTube', 'fa-youtube' ),
	),
	function ( $s ) {
		return $s[0] && preg_match( '#^https://#i', $s[0] );
	}
);
$tmt_promises = tmt_opt( 'footer.promises_on' ) ? tmt_promise_items() : array();
$tmt_menu     = function ( $location, $fallback ) {
	if ( has_nav_menu( $location ) ) {
		wp_nav_menu( array( 'theme_location' => $location, 'container' => false, 'depth' => 1, 'items_wrap' => '<ul>%3$s</ul>' ) );
		return;
	}
	echo '<ul>';
	foreach ( $fallback as $label => $url ) {
		if ( $url ) {
			echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
		}
	}
	echo '</ul>';
};
?>
<footer class="sf-footer">
	<?php if ( $tmt_promises ) : ?>
		<div class="sf-promise">
			<div class="sf-wrap" style="--sf-promise-n: <?php echo count( $tmt_promises ); ?>">
				<?php foreach ( $tmt_promises as $p ) : ?>
					<div><i class="<?php echo esc_attr( $p['icon'] ); ?>" aria-hidden="true"></i><span><b><?php echo esc_html( $p['title'] ); ?></b><?php if ( $p['sub'] ) : ?><small><?php echo esc_html( $p['sub'] ); ?></small><?php endif; ?></span></div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
	<div class="sf-wrap sf-ft-main">
		<div>
			<img src="<?php echo esc_url( tmt_logo_url() ); ?>" alt="<?php echo esc_attr( 'Logo ' . tmt_opt( 'shop.brand' ) ); ?>" height="52" class="sf-ft-logo" loading="lazy">
			<?php if ( tmt_opt( 'footer.about' ) ) : ?><p><?php echo esc_html( tmt_opt( 'footer.about' ) ); ?></p><?php endif; ?>
			<?php if ( tmt_opt( 'shop.company' ) ) : ?><p class="sf-ft-company"><?php echo esc_html( tmt_opt( 'shop.company' ) ); ?></p><?php endif; ?>
			<ul class="sf-ft-contact">
				<?php if ( tmt_address() ) : ?><li><i class="fa-solid fa-location-dot" aria-hidden="true"></i><span><?php echo esc_html( tmt_address() ); ?></span></li><?php endif; ?>
				<?php if ( is_email( tmt_opt( 'shop.email' ) ) ) : ?><li><i class="fa-regular fa-envelope" aria-hidden="true"></i><a href="mailto:<?php echo esc_attr( tmt_opt( 'shop.email' ) ); ?>"><?php echo esc_html( tmt_opt( 'shop.email' ) ); ?></a></li><?php endif; ?>
				<?php if ( $tmt_phone ) : ?><li><i class="fa-solid fa-phone" aria-hidden="true"></i><span><a href="tel:<?php echo esc_attr( tmt_digits( $tmt_phone ) ); ?>"><?php echo esc_html( $tmt_phone ); ?></a> (gọi, Zalo)</span></li><?php endif; ?>
				<?php if ( tmt_opt( 'footer.hours' ) ) : ?><li><i class="fa-regular fa-clock" aria-hidden="true"></i><span><?php echo esc_html( tmt_opt( 'footer.hours' ) ); ?></span></li><?php endif; ?>
				<?php if ( tmt_opt( 'shop.tax_code' ) ) : ?><li><i class="fa-regular fa-file-lines" aria-hidden="true"></i><span>Mã số thuế: <?php echo esc_html( tmt_opt( 'shop.tax_code' ) ); ?></span></li><?php endif; ?>
			</ul>
		</div>
		<div>
			<h3>Mua hàng</h3>
			<?php
			$shop = array();
			foreach ( array_slice( tmt_cat_tree()['tops'], 0, 8 ) as $t ) {
				$shop[ $t->name ] = get_term_link( $t );
			}
			if ( function_exists( 'wc_get_page_permalink' ) ) {
				$shop['Tất cả sản phẩm'] = wc_get_page_permalink( 'shop' );
			}
			$tmt_menu( 'footer_shop', $shop );
			?>
		</div>
		<div>
			<h3>Hỗ trợ</h3>
			<?php
			$support = array();
			if ( function_exists( 'wc_get_page_permalink' ) ) {
				$support['Tài khoản, đơn hàng'] = wc_get_page_permalink( 'myaccount' );
				$support['Giỏ hàng']           = wc_get_cart_url();
			}
			if ( get_option( 'page_for_posts' ) ) {
				$support[ get_the_title( get_option( 'page_for_posts' ) ) ] = get_permalink( get_option( 'page_for_posts' ) );
			}
			if ( get_privacy_policy_url() ) {
				$support['Chính sách bảo mật'] = get_privacy_policy_url();
			}
			$tmt_menu( 'footer_support', $support );
			?>
			<?php if ( tmt_opt( 'footer.shipping' ) ) : ?><p class="sf-ft-ship"><?php echo esc_html( tmt_opt( 'footer.shipping' ) ); ?></p><?php endif; ?>
		</div>
		<div class="sf-ft-nl">
			<?php if ( tmt_opt( 'footer.newsletter' ) && function_exists( 'tmt_nl_action' ) ) : ?>
				<?php
				$tmt_nl  = tmt_nl_result();
				$tmt_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
				?>
				<div id="nhan-tin">
					<h3>Nhận tin khuyến mãi</h3>
					<form method="post" action="<?php echo esc_url( tmt_nl_action() ); ?>" class="sf-nl-form"<?php echo tmt_opt( 'effects.nl_plane' ) ? ' data-sf-nl-plane' : ''; ?>>
						<input type="hidden" name="action" value="tmt_nl">
						<input type="hidden" name="back" value="<?php echo esc_url( set_url_scheme( '//' . wp_parse_url( home_url(), PHP_URL_HOST ) . ( wp_parse_url( home_url(), PHP_URL_PORT ) ? ':' . wp_parse_url( home_url(), PHP_URL_PORT ) : '' ) . $tmt_uri ) ); ?>">
						<label><i class="fa-regular fa-envelope" aria-hidden="true"></i><span class="visually-hidden">Email của bạn</span>
							<input type="email" name="email" placeholder="Email của bạn" required maxlength="191" autocomplete="email"></label>
						<span class="hp" aria-hidden="true"><input name="website" tabindex="-1" autocomplete="off"></span>
						<button type="submit"><i class="fa-solid fa-paper-plane sf-nl-icon" aria-hidden="true"></i> Đăng ký nhận tin</button>
					</form>
					<div data-sf-nl-msg>
						<?php if ( 'ok' === $tmt_nl ) : ?>
							<p class="sf-nl-ok mb-0" role="status"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Cảm ơn Anh/Chị, tiệm sẽ gửi tin mới qua email.</p>
						<?php elseif ( $tmt_nl ) : ?>
							<p class="sf-nl-err mb-0" role="alert"><?php echo 'nhieu' === $tmt_nl ? 'Anh/chị gửi nhiều lần quá, đợi ít phút rồi thử lại nhé.' : 'Email chưa đúng, anh/chị kiểm tra lại giúp tiệm.'; ?></p>
						<?php endif; ?>
					</div>
				</div>
			<?php elseif ( tmt_opt( 'footer.newsletter' ) && is_active_sidebar( 'footer-newsletter' ) ) : ?>
				<h3>Nhận tin khuyến mãi</h3>
				<?php dynamic_sidebar( 'footer-newsletter' ); ?>
			<?php endif; ?>
			<?php if ( $tmt_socials ) : ?>
				<h3>Theo dõi tiệm</h3>
				<div class="sf-social">
					<?php foreach ( $tmt_socials as $key => $s ) : ?>
						<a href="<?php echo esc_url( $s[0] ); ?>" target="_blank" rel="noopener" class="<?php echo esc_attr( $key ); ?>" aria-label="<?php echo esc_attr( $s[1] ); ?>"><?php if ( $s[2] ) : ?><i class="fa-brands <?php echo esc_attr( $s[2] ); ?>" aria-hidden="true"></i><?php else : ?><?php echo esc_html( $s[1] ); ?><?php endif; ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<div class="sf-ft-map">
			<?php if ( tmt_opt( 'footer.map' ) && '' !== $tmt_map ) : ?>
				<h3>Ghé tiệm</h3>
				<div class="sf-map">
					<iframe src="https://www.google.com/maps?q=<?php echo rawurlencode( $tmt_map ); ?>&amp;hl=vi&amp;z=16&amp;output=embed" title="<?php echo esc_attr( 'Bản đồ đường tới ' . tmt_opt( 'shop.brand' ) ); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
				</div>
				<a href="https://www.google.com/maps/dir/?api=1&amp;destination=<?php echo rawurlencode( $tmt_map ); ?>" target="_blank" rel="noopener" class="sf-map-go"><i class="fa-solid fa-diamond-turn-right" aria-hidden="true"></i>Chỉ đường<span>Mở Google Maps <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></span></a>
			<?php endif; ?>
		</div>
	</div>
	<div class="sf-wrap"><div class="sf-copy">© <?php echo esc_html( wp_date( 'Y' ) ); ?> <b><?php echo esc_html( tmt_opt( 'shop.brand_by' ) ?: ( tmt_opt( 'shop.company' ) ?: tmt_opt( 'shop.brand' ) ) ); ?></b>, All rights reserved.</div></div>
</footer>
