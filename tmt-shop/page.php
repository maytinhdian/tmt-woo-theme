<?php
/**
 * Trang tĩnh (Giới thiệu, Chính sách...) và các trang của WooCommerce (Giỏ hàng, Thanh toán, Tài khoản).
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;
get_header();
$tmt_wc_page = function_exists( 'is_woocommerce' ) && ( is_cart() || is_checkout() || is_account_page() );
while ( have_posts() ) :
	the_post();
	?>
	<div class="sf-wrap sf-page">
		<?php tmt_breadcrumb(); ?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( $tmt_wc_page ? 'sf-wc-page' : 'sf-article' ); ?>>
			<h1 class="sf-h1"><?php the_title(); ?></h1>
			<div class="entry-content"><?php the_content(); ?></div>
		</article>
		<?php if ( ! $tmt_wc_page && ( comments_open() || get_comments_number() ) ) : ?>
			<div class="sf-article"><?php comments_template(); ?></div>
		<?php endif; ?>
	</div>
	<?php
endwhile;
get_footer();
