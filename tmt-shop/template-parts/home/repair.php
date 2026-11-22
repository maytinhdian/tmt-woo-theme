<?php
/**
 * Quy trình sửa máy (kiểu B của app): 4 bước + 3 ô phí, dẫn sang trang /sua-may. Phí lấy từ plugin (tmt_repair_fees()).
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;
if ( ! function_exists( 'tmt_repair_fees' ) || ! tmt_repair_on() ) {
	return;
}
$rf   = tmt_repair_fees();
$v    = 'tmt_repair_vnd';
$zalo = tmt_zalo_url( tmt_opt( 'contact.zalo' ) );
$text = tmt_opt( 'home.repair_text' );
?>
<section class="sf-hs">
	<div class="sf-wrap">
		<div class="rin-home">
			<div class="top">
				<div>
					<span class="lp-eye">Sửa máy ở tiệm</span>
					<h2><?php echo esc_html( tmt_opt( 'home.repair_title' ) ? tmt_opt( 'home.repair_title' ) : 'Kiểm tra trước, đồng ý mới sửa' ); ?></h2>
					<?php if ( $text ) : ?><p><?php echo esc_html( $text ); ?></p><?php endif; ?>
				</div>
				<div class="btns">
					<a class="lp-btn o" href="<?php echo esc_url( tmt_repair_url() ); ?>">Xem quy trình chi tiết</a>
					<?php if ( $zalo ) : ?><a class="lp-btn y" href="<?php echo esc_url( $zalo ); ?>" target="_blank" rel="noopener"><i class="fa-regular fa-comment-dots" aria-hidden="true"></i>Nhắn sửa máy</a><?php endif; ?>
				</div>
			</div>
			<ol class="rin-hsteps">
				<li><i class="fa-regular fa-comment-dots" aria-hidden="true"></i><b>1. Nhắn tình trạng</b><small>Zalo, gọi, form</small></li>
				<li><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i><b>2. Nhận máy</b><small>Báo phí, chọn hạn mức</small></li>
				<li><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><b>3. Kiểm tra, báo giá</b><small>Có hư mới báo giá</small></li>
				<li><i class="fa-solid fa-circle-check" aria-hidden="true"></i><b>4. Sửa, trả máy</b><small>Bảo hành linh kiện</small></li>
			</ol>
			<div class="rin-hfees">
				<div><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span><b>Phí kiểm tra <?php echo esc_html( $v( $rf['inspect'] ) ); ?> / máy</b><small><?php echo $rf['waive'] ? 'miễn khi đồng ý sửa' : 'trừ vào tiền sửa khi đồng ý sửa'; ?></small></span></div>
				<div><i class="fa-solid fa-motorcycle" aria-hidden="true"></i><span><b>Di chuyển từ <?php echo esc_html( $v( $rf['travel_min'] ) ); ?> / chuyến</b><small>theo km, chỉ khi thợ tới tận nơi</small></span></div>
				<div><i class="fa-solid fa-bolt" aria-hidden="true"></i><span><b>Hạn mức tự thay</b><small>"dưới <?php echo esc_html( $v( $rf['limit'] ) ); ?> cứ thay", anh/chị chọn</small></span></div>
			</div>
		</div>
	</div>
</section>
