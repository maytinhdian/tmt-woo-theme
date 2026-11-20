<?php
/**
 * Bộ lọc kiểu thanh ngang: hàng hãng chọn nhanh, mỗi nhóm lọc là một nút thả xuống (<details>, chạy cả khi tắt JS).
 * Trên điện thoại thanh nút cuộn ngang, ô chọn trượt lên từ đáy màn hình.
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;
$groups = $args['groups'];
$s      = tmt_filter_state();
$sorts  = tmt_sorts();
$brand  = null;
foreach ( $groups as $g ) {
	if ( 'hang' === $g['key'] ) {
		$brand = $g;
	}
}
?>
<?php if ( $brand && count( $brand['options'] ) > 1 ) : ?>
	<div class="sf-brands" aria-label="Chọn nhanh hãng">
		<?php foreach ( array_slice( $brand['options'], 0, 8 ) as $o ) : ?>
			<a href="<?php echo esc_url( $o['url'] ); ?>" rel="nofollow"<?php echo $o['on'] ? ' class="on" aria-current="true"' : ''; ?>><?php echo esc_html( $o['label'] ); ?></a>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
<div class="sf-fbar">
	<a href="<?php echo esc_url( tmt_filter_toggle( 'con_hang', '1' ) ); ?>" rel="nofollow" class="sf-pill<?php echo $s['con_hang'] ? ' on' : ''; ?>" role="switch" aria-checked="<?php echo $s['con_hang'] ? 'true' : 'false'; ?>">
		<?php echo tmt_icon( 'storefront' ); // phpcs:ignore ?>Còn hàng<span class="sf-sw" aria-hidden="true"></span>
	</a>
	<?php foreach ( $groups as $g ) : ?>
		<?php
		$on_opts = array_values( array_filter( $g['options'], function ( $o ) { return $o['on']; } ) );
		if ( 0 === $g['selected'] ) {
			$summary = $g['label'];
		} elseif ( 1 === count( $on_opts ) ) {
			$summary = $g['label'] . ': ' . $on_opts[0]['label'];
		} else {
			$summary = $g['label'] . ' · ' . $g['selected'];
		}
		?>
		<details class="sf-dd">
			<summary class="sf-pill<?php echo $g['selected'] ? ' on' : ''; ?>"><?php echo esc_html( $summary ); ?><?php echo tmt_icon( 'expand_more' ); // phpcs:ignore ?></summary>
			<div class="sf-pop" role="group" aria-label="<?php echo esc_attr( $g['label'] ); ?>">
				<div class="sf-pop-h"><b><?php echo esc_html( $g['label'] ); ?></b><span class="sf-muted"><?php echo $g['multi'] ? 'Chọn được nhiều' : 'Chọn một'; ?></span></div>
				<div class="sf-tiles">
					<?php foreach ( $g['options'] as $o ) : ?>
						<a href="<?php echo esc_url( $o['url'] ); ?>" rel="nofollow" class="sf-tile<?php echo $o['on'] ? ' on' : ''; ?>"<?php echo $o['on'] ? ' aria-current="true"' : ''; ?>><b><?php echo esc_html( $o['label'] ); ?></b><span class="c"><?php echo (int) $o['count']; ?></span></a>
					<?php endforeach; ?>
				</div>
				<?php if ( $g['selected'] ) : ?>
					<div class="sf-pop-f"><a href="<?php echo esc_url( $g['clear'] ); ?>" rel="nofollow">Bỏ chọn <?php echo esc_html( mb_strtolower( $g['label'] ) ); ?></a></div>
				<?php endif; ?>
			</div>
		</details>
	<?php endforeach; ?>
	<details class="sf-dd sf-sort">
		<summary class="sf-pill"><?php echo tmt_icon( 'swap_vert' ); // phpcs:ignore ?><?php echo esc_html( $sorts[ $s['sap_xep'] ] ); ?><?php echo tmt_icon( 'expand_more' ); // phpcs:ignore ?></summary>
		<div class="sf-pop sf-pop-list" role="group" aria-label="Sắp xếp">
			<div class="sf-pop-h"><b>Sắp xếp</b></div>
			<?php foreach ( $sorts as $k => $label ) : ?>
				<a href="<?php echo esc_url( tmt_filter_toggle( 'sap_xep', $k ) ); ?>" rel="nofollow"<?php echo $s['sap_xep'] === $k ? ' class="on"' : ''; ?>><?php echo esc_html( $label ); ?><?php echo $s['sap_xep'] === $k ? tmt_icon( 'check' ) : ''; // phpcs:ignore ?></a>
			<?php endforeach; ?>
		</div>
	</details>
</div>
<?php get_template_part( 'template-parts/product/filters-active', null, $args ); ?>
