<?php
/**
 * Ô tìm dùng trong nội dung (404, danh sách trống, widget Tìm kiếm): tìm sản phẩm khi có WooCommerce.
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;
?>
<form class="sf-search sf-search-box sf-search-inline" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<span class="sf-search-lead" aria-hidden="true"><?php echo tmt_icon( 'search' ); // phpcs:ignore ?></span>
	<input type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Tìm sản phẩm..." aria-label="Tìm" maxlength="100">
	<?php if ( class_exists( 'WooCommerce' ) ) : ?><input type="hidden" name="post_type" value="product"><?php endif; ?>
	<button type="submit" aria-label="Tìm"><?php echo tmt_icon( 'search' ); // phpcs:ignore ?></button>
</form>
