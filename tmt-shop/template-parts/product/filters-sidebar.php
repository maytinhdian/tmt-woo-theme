<?php
/**
 * Bộ lọc kiểu cột (trái hoặc phải): các nhóm gập/mở xếp dọc, ô tick kèm số sản phẩm.
 * Trên điện thoại cột ẩn đi, nút "Bộ lọc" mở thành tấm trượt từ đáy (chạy cả khi tắt JS nhờ :target).
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;
$groups   = $args['groups'];
$s        = tmt_filter_state();
$sorts    = tmt_sorts();
$selected = count( $args['active'] );
?>
<div class="sf-mbar">
	<a href="#sf-filter" data-sf-open="sf-filter"><?php echo tmt_icon( 'tune' ); // phpcs:ignore ?>Bộ lọc<?php if ( $selected ) : ?> <em><?php echo (int) $selected; ?></em><?php endif; ?></a>
	<details class="sf-dd sf-sort">
		<summary><?php echo tmt_icon( 'swap_vert' ); // phpcs:ignore ?><?php echo esc_html( $sorts[ $s['sap_xep'] ] ); ?></summary>
		<div class="sf-pop sf-pop-list" role="group" aria-label="Sắp xếp">
			<div class="sf-pop-h"><b>Sắp xếp</b></div>
			<?php foreach ( $sorts as $k => $label ) : ?>
				<a href="<?php echo esc_url( tmt_filter_toggle( 'sap_xep', $k ) ); ?>" rel="nofollow"<?php echo $s['sap_xep'] === $k ? ' class="on"' : ''; ?>><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</div>
	</details>
</div>
<aside class="sf-side" id="sf-filter" aria-label="Bộ lọc">
	<div class="sf-side-h">
		<b><?php echo tmt_icon( 'tune' ); // phpcs:ignore ?>Bộ lọc</b>
		<?php if ( $selected ) : ?><a href="<?php echo esc_url( tmt_filter_reset_url() ); ?>" rel="nofollow">Xóa hết</a><?php endif; ?>
		<a href="#" class="sf-close" data-sf-close aria-label="Đóng bộ lọc"><?php echo tmt_icon( 'close' ); // phpcs:ignore ?></a>
	</div>
	<div class="sf-side-body">
		<a href="<?php echo esc_url( tmt_filter_toggle( 'con_hang', '1' ) ); ?>" rel="nofollow" class="sf-stock" role="switch" aria-checked="<?php echo $s['con_hang'] ? 'true' : 'false'; ?>">
			<?php echo tmt_icon( 'storefront' ); // phpcs:ignore ?>Chỉ hàng có sẵn<span class="sf-sw<?php echo $s['con_hang'] ? ' on' : ''; ?>" aria-hidden="true"></span>
		</a>
		<?php foreach ( $groups as $i => $g ) : ?>
			<details class="sf-sec"<?php echo ( $g['selected'] || $i < 4 ) ? ' open' : ''; ?>>
				<summary><?php echo esc_html( $g['label'] ); ?><?php if ( $g['selected'] ) : ?><span class="n"><?php echo (int) $g['selected']; ?></span><?php endif; ?><?php echo tmt_icon( 'expand_more', 'ms-auto' ); // phpcs:ignore ?></summary>
				<div class="sf-opts">
					<?php foreach ( $g['options'] as $o ) : ?>
						<a href="<?php echo esc_url( $o['url'] ); ?>" rel="nofollow" class="sf-opt<?php echo $o['on'] ? ' on' : ''; ?>"<?php echo $o['on'] ? ' aria-current="true"' : ''; ?>><span class="<?php echo $g['multi'] ? 'sf-cb' : 'sf-rd'; ?>" aria-hidden="true"></span><?php echo esc_html( $o['label'] ); ?><span class="c"><?php echo (int) $o['count']; ?></span></a>
					<?php endforeach; ?>
				</div>
			</details>
		<?php endforeach; ?>
	</div>
	<div class="sf-side-f">
		<a href="<?php echo esc_url( tmt_filter_reset_url() ); ?>" rel="nofollow" class="sf-btn ghost">Xóa hết</a>
		<a href="#" class="sf-btn flex-grow-1 text-center" data-sf-close>Xem <?php echo (int) $args['total']; ?> sản phẩm</a>
	</div>
</aside>
<a href="#" class="sf-scrim" data-sf-close aria-hidden="true" tabindex="-1"></a>
