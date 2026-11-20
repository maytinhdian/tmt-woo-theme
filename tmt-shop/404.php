<?php
/**
 * Không tìm thấy trang.
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<div class="sf-wrap sf-page">
	<div class="sf-empty sf-404">
		<?php echo tmt_icon( 'travel_explore' ); // phpcs:ignore ?>
		<h1 class="sf-h1">Không tìm thấy trang</h1>
		<p class="sf-muted">Trang có thể đã đổi địa chỉ hoặc sản phẩm đã ngừng bán. Thử tìm sản phẩm khác:</p>
		<?php get_search_form(); ?>
		<p><a class="sf-btn" href="<?php echo esc_url( home_url( '/' ) ); ?>">Về trang chủ</a></p>
	</div>
</div>
<?php
get_footer();
