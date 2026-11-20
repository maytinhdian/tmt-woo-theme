<?php
/**
 * Cụm nút liên hệ nổi góc dưới phải: bấm nút tròn để bung Hotline, Chat Zalo, Chat Messenger (Tùy biến → Nút liên hệ nổi).
 * Thanh dính đáy màn hình gắn data-sf-bottom-bar thì cụm nút tự nâng lên, không che. JS ở assets/js/theme.js.
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;
$tmt_buttons = tmt_contact_buttons();
if ( ! $tmt_buttons ) {
	return;
}
$tmt_hours = tmt_opt( 'contact.hours' );
$tmt_subs  = array( 'hotline' => 'Gọi ngay cho tiệm', 'zalo' => 'Nhắn tin, gửi ảnh máy lỗi', 'messenger' => 'Nhắn qua trang Facebook' );
$tmt_n     = count( $tmt_buttons );
?>
<div class="sf-contact" data-sf-contact>
	<div class="sf-contact-panel" id="sf-contact-panel" role="group" aria-label="Liên hệ với tiệm">
		<?php if ( $tmt_hours ) : ?>
			<p class="sf-contact-hours"><i class="fa-regular fa-clock" aria-hidden="true"></i> Giờ làm việc: <?php echo esc_html( $tmt_hours ); ?></p>
		<?php endif; ?>
		<?php foreach ( $tmt_buttons as $i => $b ) : ?>
			<a href="<?php echo esc_url( $b['href'] ); ?>" class="sf-contact-item is-<?php echo esc_attr( $b['key'] ); ?>" style="--i: <?php echo (int) ( $tmt_n - $i ); ?>"<?php echo $b['external'] ? ' target="_blank" rel="noopener"' : ''; ?> tabindex="-1">
				<span class="sf-contact-ico" aria-hidden="true">
					<?php if ( 'hotline' === $b['key'] ) : ?><i class="fa-solid fa-phone-volume"></i>
					<?php elseif ( 'zalo' === $b['key'] ) : ?><b>Zalo</b>
					<?php else : ?><i class="fa-brands fa-facebook-messenger"></i><?php endif; ?>
				</span>
				<span class="sf-contact-text"><b><?php echo esc_html( $b['label'] ); ?></b><small><?php echo esc_html( $tmt_subs[ $b['key'] ] ); ?></small></span>
			</a>
		<?php endforeach; ?>
	</div>
	<span class="sf-contact-hint" aria-hidden="true">Cần tư vấn?</span>
	<button type="button" class="sf-contact-toggle" aria-expanded="false" aria-controls="sf-contact-panel" aria-label="Liên hệ với tiệm">
		<i class="fa-solid fa-headset ic-open" aria-hidden="true"></i>
		<i class="fa-solid fa-xmark ic-close" aria-hidden="true"></i>
	</button>
</div>
