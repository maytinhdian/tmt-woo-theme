<?php
/**
 * Dải cam kết: dùng chung các ô cam kết của chân trang (Tùy biến → Chân trang).
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;
$promises = tmt_promise_items();
if ( ! $promises ) {
	return;
}
?>
<section class="sf-hs tight">
	<div class="sf-wrap">
		<div class="sf-prom" style="--sf-prom-n: <?php echo count( $promises ); ?>">
			<?php foreach ( $promises as $p ) : ?>
				<div><i class="<?php echo esc_attr( $p['icon'] ); ?>" aria-hidden="true"></i><span><b><?php echo esc_html( $p['title'] ); ?></b><?php if ( $p['sub'] ) : ?><small><?php echo esc_html( $p['sub'] ); ?></small><?php endif; ?></span></div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
