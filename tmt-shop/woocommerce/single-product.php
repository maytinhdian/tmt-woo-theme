<?php
/**
 * Khung trang chi tiết sản phẩm.
 *
 * @package TMT_Shop
 * @version 1.6.4
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );
echo '<div class="sf-wrap sf-page">';
tmt_breadcrumb();
while ( have_posts() ) {
	the_post();
	wc_get_template_part( 'content', 'single-product' );
}
echo '</div>';
get_footer( 'shop' );
