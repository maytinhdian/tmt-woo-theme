<?php
/**
 * Banner dọc dính hai bên lề (Tùy biến → Banner dọc hai bên). Chỉ hiện khi lề ngoài khung nội dung đủ chỗ,
 * nên không đè nội dung; trang giãn hết không có lề nên không hiện.
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;
$tmt_w = tmt_page_width();
$tmt_b = $tmt_w['full'] ? array() : tmt_side_banners();
if ( ! $tmt_b ) {
	return;
}
$tmt_half = intdiv( $tmt_w['max'], 2 );
foreach ( $tmt_b as $side => $b ) :
	?>
	<aside class="sf-side-banner is-<?php echo esc_attr( $side ); ?>" aria-label="Banner khuyến mãi">
		<?php if ( $b['link'] ) : ?><a href="<?php echo esc_url( $b['link'] ); ?>"><?php endif; ?>
		<img src="<?php echo esc_url( $b['image'] ); ?>" alt="Khuyến mãi" loading="lazy">
		<?php if ( $b['link'] ) : ?></a><?php endif; ?>
	</aside>
<?php endforeach; ?>
<style>
	@media (min-width: <?php echo (int) ( $tmt_w['max'] + 320 ); ?>px) {
		.sf-side-banner { display: block; width: min(200px, calc((100vw - <?php echo (int) $tmt_w['max']; ?>px) / 2 - 32px)); }
		.sf-side-banner.is-left { right: calc(50% + <?php echo (int) $tmt_half; ?>px + 16px); }
		.sf-side-banner.is-right { left: calc(50% + <?php echo (int) $tmt_half; ?>px + 16px); }
	}
</style>
