<?php
/**
 * Dải cam kết dưới menu ở trang thanh toán (cùng nội dung ô cam kết chân trang).
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="sfc-band"><div class="sf-wrap">
	<?php foreach ( tmt_promise_items() as $tmt_p ) : ?>
		<div><i class="<?php echo esc_attr( $tmt_p['icon'] ); ?>" aria-hidden="true"></i><span><b><?php echo esc_html( $tmt_p['title'] ); ?></b><?php if ( $tmt_p['sub'] ) : ?><small><?php echo esc_html( $tmt_p['sub'] ); ?></small><?php endif; ?></span></div>
	<?php endforeach; ?>
</div></div>
