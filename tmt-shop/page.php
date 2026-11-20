<?php
/**
 * Trang tĩnh (Giới thiệu, Chính sách...) và các trang của WooCommerce (Giỏ hàng, Thanh toán, Tài khoản).
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;
get_header();
$tmt_wc_page = function_exists( 'is_woocommerce' ) && ( is_cart() || is_checkout() || is_account_page() );
// Giỏ hàng / thanh toán kiểu C tự có tiêu đề từng mục; trang cảm ơn dùng tiêu đề riêng.
$tmt_no_h1   = function_exists( 'is_woocommerce' ) && ( is_cart() || is_checkout() );
while ( have_posts() ) :
	the_post();
	if ( $tmt_no_h1 && ! is_wc_endpoint_url() && tmt_promise_items() && WC()->cart && ! WC()->cart->is_empty() ) {
		get_template_part( 'template-parts/checkout/band' );
	}
	?>
	<div class="sf-wrap sf-page">
		<?php tmt_breadcrumb(); ?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( $tmt_wc_page ? 'sf-wc-page' : 'sf-article' ); ?>>
			<?php if ( ! $tmt_no_h1 ) : ?><h1 class="sf-h1"><?php the_title(); ?></h1><?php endif; ?>
			<div class="entry-content"><?php the_content(); ?></div>
		</article>
		<?php if ( ! $tmt_wc_page && ( comments_open() || get_comments_number() ) ) : ?>
			<div class="sf-article"><?php comments_template(); ?></div>
		<?php endif; ?>
	</div>
	<?php
endwhile;
get_footer();
