<?php
/**
 * Bài viết mới (Mẹo vặt): lấy từ Bài viết của WordPress.
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;
$posts = get_posts( array( 'numberposts' => max( 2, min( 9, (int) tmt_opt( 'home.posts_count' ) ) ), 'post_status' => 'publish', 'ignore_sticky_posts' => true ) );
if ( ! $posts ) {
	return;
}
$blog = get_option( 'page_for_posts' ) ? get_permalink( get_option( 'page_for_posts' ) ) : '';
?>
<section class="sf-hs">
	<div class="sf-wrap">
		<div class="sf-sh">
			<h2><?php echo esc_html( tmt_opt( 'home.posts_title' ) ); ?></h2>
			<?php if ( $blog ) : ?><a href="<?php echo esc_url( $blog ); ?>" class="more">Xem tất cả bài viết<i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a><?php endif; ?>
		</div>
		<div class="sf-posts">
			<?php foreach ( $posts as $p ) : ?><?php tmt_post_card( $p ); ?><?php endforeach; ?>
		</div>
	</div>
</section>
