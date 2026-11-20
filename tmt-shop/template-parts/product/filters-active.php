<?php
/**
 * Chip các lọc đang chọn, bấm để bỏ từng cái.
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;
if ( empty( $args['active'] ) ) {
	return;
}
?>
<div class="sf-active">
	<span class="sf-muted">Đang lọc:</span>
	<?php foreach ( $args['active'] as $a ) : ?>
		<a href="<?php echo esc_url( $a[1] ); ?>" rel="nofollow" class="sf-chip-x" aria-label="<?php echo esc_attr( 'Bỏ lọc ' . $a[0] ); ?>"><?php echo esc_html( $a[0] ); ?><?php echo tmt_icon( 'close' ); // phpcs:ignore ?></a>
	<?php endforeach; ?>
	<a href="<?php echo esc_url( tmt_filter_reset_url() ); ?>" rel="nofollow" class="text-nowrap">Xóa tất cả</a>
</div>
