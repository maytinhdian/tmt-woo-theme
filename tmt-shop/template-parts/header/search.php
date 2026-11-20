<?php
/**
 * Ô tìm sản phẩm đầu trang, 3 mẫu cùng cao 46px (Tùy biến → Logo và đầu trang).
 * Tắt thì ô vẫn giữ chỗ (ẩn) để logo, tài khoản, giỏ hàng không xê dịch.
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;
$tmt_style = in_array( tmt_opt( 'header.search_style' ), array( 'box', 'pill', 'split' ), true ) ? tmt_opt( 'header.search_style' ) : 'box';
$tmt_on    = tmt_opt( 'header.show_search' );
?>
<form class="sf-search sf-search-<?php echo esc_attr( $tmt_style ); ?><?php echo $tmt_on ? '' : ' sf-search-off'; ?>" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>"<?php echo $tmt_on ? '' : ' aria-hidden="true" inert'; ?>>
	<span class="sf-search-lead" aria-hidden="true"><?php echo tmt_icon( 'search' ); // phpcs:ignore ?></span>
	<input type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Tìm SSD, RAM, laptop, camera..." aria-label="Tìm sản phẩm" maxlength="100">
	<?php if ( class_exists( 'WooCommerce' ) ) : ?><input type="hidden" name="post_type" value="product"><?php endif; ?>
	<button type="submit" aria-label="Tìm">
		<?php if ( 'split' === $tmt_style ) : ?>
			<?php echo tmt_icon( 'arrow_forward' ); // phpcs:ignore ?><span>Tìm kiếm</span>
		<?php else : ?>
			<?php echo tmt_icon( 'pill' === $tmt_style ? 'arrow_forward' : 'search' ); // phpcs:ignore ?>
		<?php endif; ?>
	</button>
</form>
