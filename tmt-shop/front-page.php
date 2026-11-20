<?php
/**
 * Trang chủ: các section bật ở Tùy biến → Trang chủ, theo thứ tự đã sắp (template-parts/home/<section>.php).
 * Đặt trang tĩnh làm trang chủ cũng dùng file này; nội dung trang tĩnh (nếu có) hiện cuối trang.
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;
get_header();

$tmt_sections = array_intersect( tmt_opt_list( 'home.sections' ), array_keys( tmt_home_sections() ) );
foreach ( $tmt_sections as $tmt_section ) {
	get_template_part( 'template-parts/home/' . $tmt_section );
}

if ( 'page' === get_option( 'show_on_front' ) && have_posts() ) {
	the_post();
	if ( '' !== trim( (string) get_the_content() ) ) {
		echo '<section class="sf-hs"><div class="sf-wrap entry-content">';
		the_content();
		echo '</div></section>';
	}
}

get_footer();
