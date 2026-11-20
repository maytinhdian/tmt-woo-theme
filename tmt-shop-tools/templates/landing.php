<?php
/**
 * Trang Giải pháp / Phần mềm kiểu landing (giống web của app, Anh 2 duyệt 20/11/2026). Phần nào chưa có nội dung thì tự ẩn.
 *
 * @package TMT_Shop_Tools
 */

defined( 'ABSPATH' ) || exit;

get_header();
the_post();
$tmt_id       = get_the_ID();
$tmt_pt       = get_post_type();
$tmt_solution = 'tmt_solution' === $tmt_pt;
$tmt_lp       = tmt_lp_get( $tmt_id );
$tmt_c        = tmt_lp_contact();
$tmt_types    = tmt_lp_types();
$tmt_button   = $tmt_lp['button'] ? $tmt_lp['button'] : ( $tmt_solution ? 'Đặt lịch khảo sát' : 'Dùng thử miễn phí' );
$tmt_groups   = tmt_lp_groups( $tmt_lp['packages'] );
$tmt_sent     = tmt_req_sent();
$tmt_error    = tmt_req_error();
$tmt_pick     = isset( $_GET['goi'] ) ? absint( $_GET['goi'] ) : -1; // phpcs:ignore WordPress.Security.NonceVerification
$tmt_picked   = $tmt_pick >= 0 && isset( $tmt_lp['packages'][ $tmt_pick ] ) ? $tmt_lp['packages'][ $tmt_pick ] : null;
$tmt_products = array();
if ( $tmt_lp['product_cat'] && function_exists( 'wc_get_products' ) ) {
	$tmt_term = get_term( (int) $tmt_lp['product_cat'], 'product_cat' );
	if ( $tmt_term && ! is_wp_error( $tmt_term ) ) {
		$tmt_products = wc_get_products( array( 'status' => 'publish', 'visibility' => 'catalog', 'category' => array( $tmt_term->slug ), 'limit' => 4, 'orderby' => 'menu_order', 'order' => 'ASC' ) );
	}
}
$tmt_others = get_posts( array( 'post_type' => $tmt_pt, 'numberposts' => 6, 'post__not_in' => array( $tmt_id ), 'orderby' => 'menu_order', 'order' => 'ASC' ) );
$tmt_steps  = $tmt_lp['steps'];
?>
<div class="lp">
	<section class="lp-hero">
		<div class="sf-wrap">
			<div>
				<nav class="lp-crumb" aria-label="Đường dẫn"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Trang chủ</a> / <a href="<?php echo esc_url( get_post_type_archive_link( $tmt_pt ) ); ?>"><?php echo esc_html( $tmt_types[ $tmt_pt ][1] ); ?></a> / <?php the_title(); ?></nav>
				<?php if ( $tmt_lp['eyebrow'] ) : ?><span class="lp-eye"><?php echo esc_html( $tmt_lp['eyebrow'] ); ?></span><?php endif; ?>
				<h1><?php echo esc_html( $tmt_lp['headline'] ? $tmt_lp['headline'] : get_the_title() ); ?><?php if ( $tmt_lp['highlight'] ) : ?><em><?php echo esc_html( $tmt_lp['highlight'] ); ?></em><?php endif; ?></h1>
				<?php if ( $tmt_lp['intro'] ) : ?><p class="lead"><?php echo esc_html( $tmt_lp['intro'] ); ?></p><?php endif; ?>
				<?php if ( $tmt_lp['ticks'] ) : ?>
					<ul class="lp-ticks"><?php foreach ( $tmt_lp['ticks'] as $t ) : ?><li><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?php echo esc_html( $t ); ?></li><?php endforeach; ?></ul>
				<?php endif; ?>
				<div class="lp-cta">
					<a class="lp-btn y" href="#dang-ky"><i class="fa-solid fa-<?php echo $tmt_solution ? 'calendar-check' : 'circle-play'; ?>" aria-hidden="true"></i><?php echo esc_html( $tmt_button ); ?></a>
					<?php if ( $tmt_lp['packages'] ) : ?>
						<a class="lp-btn w" href="#goi"><i class="fa-solid fa-box-open" aria-hidden="true"></i><?php echo $tmt_solution ? 'Xem gói trọn bộ' : 'Xem bảng giá'; ?></a>
					<?php elseif ( $tmt_c['zalo'] ) : ?>
						<a class="lp-btn w" href="<?php echo esc_url( $tmt_c['zalo'] ); ?>" target="_blank" rel="noopener"><i class="fa-regular fa-comment-dots" aria-hidden="true"></i>Tư vấn qua Zalo</a>
					<?php endif; ?>
				</div>
			</div>
			<div><?php tmt_lp_shot( get_post_thumbnail_id(), $tmt_lp['icon'], get_the_title(), true ); ?></div>
		</div>
	</section>

	<?php if ( $tmt_lp['trust'] ) : ?>
		<div class="lp-trust">
			<div class="sf-wrap" style="--n: <?php echo (int) min( 4, count( $tmt_lp['trust'] ) ); ?>">
				<?php foreach ( $tmt_lp['trust'] as $t ) : ?>
					<div><i class="fa-solid fa-<?php echo esc_attr( $t['icon'] ? $t['icon'] : 'check' ); ?>" aria-hidden="true"></i><span><b><?php echo esc_html( $t['title'] ); ?></b><small><?php echo esc_html( $t['text'] ); ?></small></span></div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $tmt_lp['needs'] ) ) : ?>
		<section class="lp-sec alt">
			<div class="sf-wrap">
				<div class="lp-sh"><span class="lp-eye">Chọn theo nhu cầu</span><h2>Lắp cho đâu?</h2></div>
				<div class="lp-cards">
					<?php foreach ( $tmt_lp['needs'] as $n ) : ?>
						<div class="lp-card">
							<div class="ic"><i class="fa-solid fa-<?php echo esc_attr( $n['icon'] ? $n['icon'] : $tmt_lp['icon'] ); ?>" aria-hidden="true"></i></div>
							<h3><?php echo esc_html( $n['title'] ); ?></h3>
							<?php if ( $n['text'] ) : ?><p><?php echo esc_html( $n['text'] ); ?></p><?php endif; ?>
							<?php if ( $n['suggest'] ) : ?><div class="sug"><i class="fa-solid fa-lightbulb" aria-hidden="true"></i>Gợi ý: <b><?php echo esc_html( $n['suggest'] ); ?></b></div><?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( ! $tmt_solution && $tmt_steps ) : ?>
		<section class="lp-sec alt">
			<div class="sf-wrap">
				<div class="lp-sh"><span class="lp-eye">Cách hoạt động</span><h2>Từ đầu tới cuối chỉ <?php echo (int) count( $tmt_steps ); ?> bước</h2></div>
				<?php tmt_lp_steps( $tmt_steps ); ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $tmt_lp['features'] ) ) : ?>
		<section class="lp-sec">
			<div class="sf-wrap">
				<div class="lp-sh"><span class="lp-eye">Tính năng</span><h2>Mọi thứ cần có, không thừa</h2></div>
				<div class="lp-feats">
					<?php foreach ( $tmt_lp['features'] as $f ) : ?>
						<div class="lp-feat"><div class="ic"><i class="fa-solid fa-<?php echo esc_attr( $f['icon'] ? $f['icon'] : 'check' ); ?>" aria-hidden="true"></i></div><div><h3><?php echo esc_html( $f['title'] ); ?></h3><?php if ( $f['text'] ) : ?><p><?php echo esc_html( $f['text'] ); ?></p><?php endif; ?></div></div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $tmt_lp['packages'] ) : ?>
		<section class="lp-sec<?php echo $tmt_solution ? '' : ' alt'; ?>" id="goi">
			<div class="sf-wrap">
				<div class="lp-sh"><span class="lp-eye"><?php echo $tmt_solution ? 'Gói trọn bộ' : 'Bảng giá'; ?></span><h2><?php echo $tmt_solution ? 'Giá đã gồm thiết bị và công lắp' : 'Chọn gói phù hợp'; ?></h2></div>
				<?php if ( count( $tmt_groups ) > 1 ) : ?>
					<div class="lp-seg" role="tablist" data-lp-tabs>
						<?php $k = 0; foreach ( array_keys( $tmt_groups ) as $g ) : ?>
							<button type="button" role="tab" id="lp-tab-<?php echo (int) $k; ?>" aria-controls="lp-pane-<?php echo (int) $k; ?>" aria-selected="<?php echo $k ? 'false' : 'true'; ?>"><?php echo esc_html( $g ); ?></button>
						<?php ++$k; endforeach; ?>
					</div>
				<?php endif; ?>
				<?php $k = 0; foreach ( $tmt_groups as $packages ) : ?>
					<div id="lp-pane-<?php echo (int) $k; ?>"<?php echo count( $tmt_groups ) > 1 ? ' role="tabpanel" aria-labelledby="lp-tab-' . (int) $k . '"' : ''; ?><?php echo $k ? ' hidden' : ''; ?>>
						<div class="lp-plans" style="--n: <?php echo (int) min( 3, count( $packages ) ); ?>">
							<?php foreach ( $packages as $i => $p ) : ?>
								<div class="lp-plan<?php echo $p['featured'] ? ' hot' : ''; ?>">
									<?php if ( $p['featured'] ) : ?><span class="rib"><?php echo $tmt_solution ? 'Bán chạy' : 'Nhiều người chọn'; ?></span><?php endif; ?>
									<?php if ( $p['icon'] ) : ?><div class="ic"><i class="fa-solid fa-<?php echo esc_attr( $p['icon'] ); ?>" aria-hidden="true"></i></div><?php endif; ?>
									<h3><?php echo esc_html( $p['name'] ); ?></h3>
									<?php if ( $p['audience'] ) : ?><div class="who"><?php echo esc_html( $p['audience'] ); ?></div><?php endif; ?>
									<div class="pr"><?php echo esc_html( tmt_lp_price( $p ) ); ?><?php if ( '' !== $p['price'] && $p['period'] ) : ?><small> / <?php echo esc_html( $p['period'] ); ?></small><?php endif; ?></div>
									<?php if ( $tmt_solution && '' !== $p['price'] ) : ?><small class="note">Đã gồm thiết bị và công lắp</small><?php endif; ?>
									<ul>
										<?php foreach ( (array) $p['items'] as $x ) : ?><li><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?php echo esc_html( $x ); ?></li><?php endforeach; ?>
										<?php foreach ( (array) $p['excluded'] as $x ) : ?><li class="no"><i class="fa-solid fa-circle-minus" aria-hidden="true"></i><?php echo esc_html( $x ); ?></li><?php endforeach; ?>
									</ul>
									<a class="lp-btn <?php echo $p['featured'] ? 'b' : 'o'; ?>" href="<?php echo esc_url( add_query_arg( 'goi', $i, get_permalink() ) ); ?>#dang-ky"><?php echo esc_html( '' === $p['price'] && ! $tmt_solution ? 'Nhận báo giá' : $tmt_button ); ?></a>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php ++$k; endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $tmt_solution && $tmt_steps ) : ?>
		<section class="lp-sec<?php echo $tmt_lp['packages'] || empty( $tmt_lp['needs'] ) ? ' alt' : ''; ?>">
			<div class="sf-wrap">
				<div class="lp-sh"><span class="lp-eye">Quy trình làm việc</span><h2>Từ lúc gọi tới lúc bàn giao</h2></div>
				<?php tmt_lp_steps( $tmt_steps ); ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $tmt_lp['showcases'] ) : ?>
		<section class="lp-sec">
			<div class="sf-wrap">
				<?php foreach ( $tmt_lp['showcases'] as $i => $s ) : ?>
					<div class="lp-show<?php echo $i % 2 ? ' rev' : ''; ?>">
						<div>
							<?php if ( $s['label'] ) : ?><span class="lp-eye"><?php echo esc_html( $s['label'] ); ?></span><?php endif; ?>
							<h3><?php echo esc_html( $s['title'] ); ?></h3>
							<?php if ( $s['text'] ) : ?><p><?php echo esc_html( $s['text'] ); ?></p><?php endif; ?>
							<?php if ( $s['points'] ) : ?><ul><?php foreach ( (array) $s['points'] as $pt ) : ?><li><i class="fa-solid fa-check" aria-hidden="true"></i><?php echo esc_html( $pt ); ?></li><?php endforeach; ?></ul><?php endif; ?>
						</div>
						<div><?php tmt_lp_shot( (int) $s['image'], $tmt_lp['icon'], $s['title'] ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $tmt_products && function_exists( 'tmt_product_card' ) ) : ?>
		<section class="lp-sec alt">
			<div class="sf-wrap">
				<div class="lp-sh"><span class="lp-eye"><?php echo $tmt_solution ? 'Tiệm đang bán' : 'Mua trọn bộ'; ?></span><h2><?php echo esc_html( $tmt_lp['devices_title'] ? $tmt_lp['devices_title'] : 'Thiết bị đang bán' ); ?></h2></div>
				<div class="lp-row4"><?php foreach ( $tmt_products as $p ) : ?><div><?php tmt_product_card( $p ); ?></div><?php endforeach; ?></div>
				<p class="text-center mt-3 mb-0"><a href="<?php echo esc_url( get_term_link( $tmt_term ) ); ?>" class="fw-bold">Xem tất cả <i class="fa-solid fa-chevron-right" style="font-size:.7rem" aria-hidden="true"></i></a></p>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $tmt_lp['faq'] ) : ?>
		<section class="lp-sec">
			<div class="sf-wrap">
				<div class="lp-sh"><h2>Câu hỏi thường gặp</h2></div>
				<div class="lp-faq">
					<?php foreach ( $tmt_lp['faq'] as $i => $f ) : ?>
						<details<?php echo $i ? '' : ' open'; ?>><summary><?php echo esc_html( $f['q'] ); ?></summary><p><?php echo esc_html( $f['a'] ); ?></p></details>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<section class="lp-sec lp-final">
		<div class="sf-wrap">
			<div>
				<h2><?php echo esc_html( $tmt_lp['cta_title'] ? $tmt_lp['cta_title'] : $tmt_button ); ?></h2>
				<?php if ( $tmt_lp['cta_text'] ) : ?><p><?php echo esc_html( $tmt_lp['cta_text'] ); ?></p><?php endif; ?>
				<div class="contact">
					<?php if ( $tmt_c['phone'] ) : ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $tmt_c['phone'] ) ); ?>"><i class="fa-solid fa-phone" aria-hidden="true"></i><?php echo esc_html( $tmt_c['phone'] ); ?></a><?php endif; ?>
					<?php if ( $tmt_c['email'] ) : ?><a href="mailto:<?php echo esc_attr( $tmt_c['email'] ); ?>"><i class="fa-solid fa-envelope" aria-hidden="true"></i><?php echo esc_html( $tmt_c['email'] ); ?></a><?php endif; ?>
					<?php if ( $tmt_c['address'] ) : ?><span><i class="fa-solid fa-location-dot" aria-hidden="true"></i><?php echo esc_html( $tmt_c['address'] ); ?></span><?php endif; ?>
				</div>
				<?php if ( $tmt_others ) : ?>
					<p class="others"><?php echo esc_html( $tmt_types[ $tmt_pt ][1] ); ?> khác:<?php foreach ( $tmt_others as $o ) : ?> <a href="<?php echo esc_url( get_permalink( $o ) ); ?>"><?php echo esc_html( get_the_title( $o ) ); ?></a><?php endforeach; ?></p>
				<?php endif; ?>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="lp-form" id="dang-ky">
				<h3><?php echo $tmt_solution ? 'Đặt lịch khảo sát miễn phí' : esc_html( 'Đăng ký ' . mb_strtolower( $tmt_button ) ); ?></h3>
				<?php if ( $tmt_sent ) : ?>
					<p class="tmt-ok" role="status"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Tiệm đã nhận yêu cầu <b><?php echo esc_html( $tmt_sent ); ?></b>. Kỹ thuật gọi lại trong giờ làm việc.</p>
				<?php else : ?>
					<div class="sub"><?php echo $tmt_solution ? 'Kỹ thuật gọi lại trong giờ làm việc để hẹn giờ tới xem.' : 'Tiệm gọi lại trong giờ làm việc để cài đặt, hướng dẫn.'; ?></div>
					<?php if ( $tmt_error ) : ?><p class="tmt-err" role="alert"><?php echo esc_html( $tmt_error ); ?></p><?php endif; ?>
					<?php tmt_req_hidden( $tmt_solution ? 'khao-sat' : 'dung-thu', $tmt_id, 'dang-ky' ); ?>
					<?php if ( $tmt_picked ) : ?><input type="hidden" name="goi" value="<?php echo (int) $tmt_pick; ?>"><div class="picked">Gói đã chọn: <b><?php echo esc_html( $tmt_picked['name'] ); ?></b></div><?php endif; ?>
					<div class="two">
						<div><label class="f" for="lp-name">Họ tên</label><input id="lp-name" name="name" maxlength="100" placeholder="Anh/chị ..." autocomplete="name"></div>
						<div><label class="f" for="lp-phone">Số điện thoại</label><input id="lp-phone" name="phone" required inputmode="tel" maxlength="20" placeholder="09xx xxx xxx" autocomplete="tel"></div>
					</div>
					<?php if ( $tmt_solution ) : ?>
						<label class="f" for="lp-address">Địa chỉ lắp</label>
						<input id="lp-address" name="address" maxlength="255" placeholder="Số nhà, phường ..." autocomplete="street-address">
						<?php $tmt_kinds = array_filter( wp_list_pluck( $tmt_lp['needs'], 'title' ) ); ?>
						<?php if ( $tmt_kinds ) : ?>
							<span class="f">Lắp cho</span>
							<div class="lp-chips"><?php foreach ( $tmt_kinds as $kind ) : ?><label><input type="radio" name="kind" value="<?php echo esc_attr( $kind ); ?>"><span><?php echo esc_html( $kind ); ?></span></label><?php endforeach; ?></div>
						<?php endif; ?>
						<label class="f" for="lp-need">Nhu cầu</label>
						<textarea id="lp-need" name="note" maxlength="1000" rows="2" placeholder="Ví dụ: mấy tầng, mấy máy, cần làm gì"></textarea>
					<?php else : ?>
						<label class="f" for="lp-staff">Số nhân viên</label>
						<select id="lp-staff" name="staff"><?php foreach ( array( 'Dưới 20', '20 – 50', '50 – 100', 'Trên 100' ) as $s ) : ?><option><?php echo esc_html( $s ); ?></option><?php endforeach; ?></select>
						<?php if ( $tmt_lp['form_question'] && $tmt_lp['form_options'] ) : ?>
							<span class="f"><?php echo esc_html( $tmt_lp['form_question'] ); ?></span>
							<div class="lp-chips" role="radiogroup" aria-label="<?php echo esc_attr( $tmt_lp['form_question'] ); ?>">
								<?php foreach ( $tmt_lp['form_options'] as $i => $opt ) : ?><label><input type="radio" name="answer" value="<?php echo esc_attr( $opt ); ?>"<?php checked( 0, $i ); ?>><span><?php echo esc_html( $opt ); ?></span></label><?php endforeach; ?>
							</div>
						<?php endif; ?>
					<?php endif; ?>
					<button type="submit" class="lp-btn y"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i><?php echo $tmt_solution ? 'Gửi yêu cầu' : 'Gửi đăng ký'; ?></button>
					<?php if ( $tmt_c['phone'] ) : ?>
						<div class="alt">
							<a class="lp-btn o" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $tmt_c['phone'] ) ); ?>"><i class="fa-solid fa-phone" aria-hidden="true"></i>Gọi ngay</a>
							<?php if ( $tmt_c['zalo'] ) : ?><a class="lp-btn o" href="<?php echo esc_url( $tmt_c['zalo'] ); ?>" target="_blank" rel="noopener"><i class="fa-regular fa-comment-dots" aria-hidden="true"></i>Zalo</a><?php endif; ?>
						</div>
					<?php endif; ?>
				<?php endif; ?>
			</form>
		</div>
	</section>
</div>
<?php if ( count( $tmt_groups ) > 1 ) : ?>
	<script>
		document.querySelectorAll('[data-lp-tabs]').forEach(function (bar) {
			bar.addEventListener('click', function (e) {
				var btn = e.target.closest('[role=tab]');
				if (!btn) return;
				bar.querySelectorAll('[role=tab]').forEach(function (t) {
					var on = t === btn;
					t.setAttribute('aria-selected', on ? 'true' : 'false');
					document.getElementById(t.getAttribute('aria-controls')).hidden = !on;
				});
			});
		});
	</script>
<?php endif; ?>
<?php
get_footer();
