<?php
/**
 * Thanh trên cùng 3 vùng (Tùy biến → Thanh trên cùng).
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;
$tmt_top = tmt_topbar();
if ( ! $tmt_top ) {
	return;
}
?>
<div class="sf-top sf-top-m-<?php echo esc_attr( $tmt_top['mobile'] ); ?>">
	<div class="sf-wrap sf-top-in">
		<?php foreach ( $tmt_top['zones'] as $zone => $items ) : ?>
			<div class="sf-top-z sf-top-<?php echo esc_attr( $zone ); ?><?php echo $zone === $tmt_top['mobileZone'] ? ' sf-top-mz' : ''; ?>">
				<?php foreach ( $items as $item ) : ?>
					<?php
					$tag   = $item['href'] ? 'a' : 'span';
					$attrs = $item['href'] ? ' href="' . esc_url( $item['href'] ) . '"' . ( $item['external'] ? ' target="_blank" rel="noopener"' : '' ) . ( '' === $item['html'] ? ' aria-label="' . esc_attr( $item['label'] ) . '"' : '' ) : '';
					?>
					<<?php echo $tag; // phpcs:ignore ?> class="sf-top-i<?php echo $item['accent'] ? ' accent' : ''; ?>"<?php echo $attrs; // phpcs:ignore -- đã escape ở trên ?>>
						<?php if ( $item['icon'] ) : ?><i class="<?php echo esc_attr( $item['icon'] ); ?>" aria-hidden="true"></i><?php endif; ?>
						<?php if ( '' !== $item['html'] ) : ?><span><?php echo wp_kses( $item['html'], array( 'b' => array( 'class' => true ) ) ); ?></span><?php endif; ?>
					</<?php echo $tag; // phpcs:ignore ?>>
				<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>
