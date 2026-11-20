<?php
/**
 * Khung Xây dựng cấu hình (giống trang /xay-dung-cau-hinh của app). Dữ liệu ở #pcb-data, vẽ bằng assets/pc-builder.js.
 *
 * @package TMT_Shop_Tools
 */

defined( 'ABSPATH' ) || exit;

$tmt_pcb_data = tmt_pcb_client_data();
$tmt_pcb_note = trim( (string) get_option( 'tmt_pcb_note', 'Miễn phí ráp máy, cài Windows và chạy thử 24 giờ trước khi giao.' ) );
?>
<div class="pcb" id="pcb">
	<?php
	// Báo lỗi khi máy chủ từ chối thêm vào giỏ (trang thường không tự in thông báo của WooCommerce).
	if ( function_exists( 'wc_print_notices' ) && WC()->session ) {
		wc_print_notices();
	}
	?>
	<p class="pcb-lead">Chọn từng linh kiện, giá và tình trạng hàng lấy trực tiếp từ cửa hàng. Web tự báo nếu hai món không lắp chung được.</p>
	<div class="pcb-cols">
		<div class="pcb-box">
			<div class="pcb-head">
				<div class="pcb-presets-row">
					<span class="pcb-presets-lbl">Cấu hình mẫu:</span>
					<div id="pcb-presets" class="pcb-presets"></div>
					<button type="button" class="pcb-chip" data-reset>Làm lại từ đầu</button>
				</div>
				<label class="pcb-switch"><input type="checkbox" id="pcb-only" checked><span aria-hidden="true"></span>Chỉ hiện linh kiện lắp được với cấu hình đang chọn</label>
			</div>
			<div id="pcb-slots"></div>
		</div>
		<aside class="pcb-sum" id="pcb-summary">
			<div class="pcb-box pcb-sum-in">
				<p class="pcb-k">Tổng tiền cấu hình</p>
				<p class="pcb-total" id="pcb-total">0 ₫</p>
				<p class="pcb-count" id="pcb-count"></p>
				<?php if ( '' !== $tmt_pcb_note ) : ?>
					<p class="pcb-note"><i class="material-icons-round" aria-hidden="true">verified</i><?php echo esc_html( $tmt_pcb_note ); ?></p>
				<?php endif; ?>
				<p class="pcb-k2">Công suất điện ước tính</p>
				<div class="pcb-meter"><span id="pcb-meter"></span></div>
				<p class="pcb-power" id="pcb-power"></p>
				<p class="pcb-k2">Kiểm tra tương thích</p>
				<div id="pcb-checks" class="pcb-checks"></div>
				<form method="post" action="<?php echo esc_url( get_permalink() ); ?>" id="pcb-cart-form"><?php // Địa chỉ không có #mã: trình duyệt không mang mã sang trang giỏ. ?>
					<?php wp_nonce_field( 'tmt_pcb_cart' ); ?>
					<input type="hidden" name="tmt_pcb_cart" value="1">
					<input type="hidden" name="code" value="">
					<button type="submit" class="sf-btn pcb-cta" data-action="cart" disabled><i class="material-icons-round" aria-hidden="true">add_shopping_cart</i>Thêm cả bộ vào giỏ hàng</button>
				</form>
				<p class="pcb-blocked" id="pcb-blocked" hidden></p>
				<?php if ( $tmt_pcb_data['urls']['zalo'] ) : ?>
					<a href="<?php echo esc_url( $tmt_pcb_data['urls']['zalo'] ); ?>" class="sf-btn ghost pcb-cta" data-zalo target="_blank" rel="noopener">Gửi tiệm tư vấn qua Zalo</a>
					<p class="pcb-hint">Web tự chép link cấu hình, bạn dán vào khung chat Zalo của tiệm.</p>
				<?php endif; ?>
				<div class="pcb-links">
					<a href="#" data-print><i class="material-icons-round" aria-hidden="true">print</i>In, lưu PDF</a>
					<a href="#" data-share><i class="material-icons-round" aria-hidden="true">link</i><span>Chép link chia sẻ</span></a>
					<?php if ( $tmt_pcb_data['manage'] ) : ?>
						<a href="#" data-save-toggle><i class="material-icons-round" aria-hidden="true">bookmark_add</i>Lưu làm mẫu</a>
					<?php endif; ?>
				</div>
				<?php if ( $tmt_pcb_data['manage'] ) : ?>
					<form id="pcb-save" class="pcb-save" hidden>
						<label for="pcb-save-name">Tên cấu hình mẫu (chỉ quản trị thấy nút này)</label>
						<div><input id="pcb-save-name" type="text" name="name" maxlength="100" required placeholder="Ví dụ: Văn phòng 10 triệu"><button type="submit" class="sf-btn">Lưu</button></div>
						<p id="pcb-save-msg"></p>
					</form>
				<?php endif; ?>
			</div>
		</aside>
	</div>
	<div class="pcb-bar" data-sf-bottom-bar>
		<div><small>Tổng <span id="pcb-bar-n"></span></small><b id="pcb-bar-total">0 ₫</b> <span id="pcb-bar-err" class="pcb-tag err" hidden></span></div>
		<a href="#pcb-summary" class="sf-btn" data-to-summary>Xem tổng, đặt hàng</a>
	</div>
	<script type="application/json" id="pcb-data"><?php echo wp_json_encode( $tmt_pcb_data ); ?></script>
</div>
