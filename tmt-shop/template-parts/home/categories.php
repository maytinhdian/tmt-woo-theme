<?php
/**
 * Danh mục nổi bật: icon tròn (slug chọn ở Tùy biến → Trang chủ, trống thì danh mục cấp 1).
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;
$cats = tmt_home_categories();
if ( ! $cats ) {
	return;
}
?>
<section class="sf-hs">
	<div class="sf-wrap">
		<div class="sf-sh"><h2>Danh mục nổi bật</h2></div>
		<nav class="sf-cats" aria-label="Danh mục nổi bật">
			<?php foreach ( $cats as $c ) : ?>
				<a href="<?php echo esc_url( get_term_link( $c ) ); ?>"><span><?php echo tmt_icon( tmt_category_icon( $c ) ); // phpcs:ignore ?></span><?php echo esc_html( $c->name ); ?></a>
			<?php endforeach; ?>
		</nav>
	</div>
</section>
