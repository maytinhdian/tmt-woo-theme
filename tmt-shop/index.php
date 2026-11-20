<?php
/**
 * Danh sách bài viết (trang Bài viết, chuyên mục, thẻ, tác giả, tìm kiếm bài viết) dạng lưới thẻ.
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;
get_header();

if ( is_home() && get_option( 'page_for_posts' ) ) {
	$tmt_title = get_the_title( get_option( 'page_for_posts' ) );
} elseif ( is_search() ) {
	$tmt_title = 'Kết quả tìm "' . get_search_query() . '"';
} elseif ( is_archive() ) {
	$tmt_title = wp_strip_all_tags( get_the_archive_title() );
} else {
	$tmt_title = 'Bài viết';
}
?>
<div class="sf-wrap sf-page">
	<?php tmt_breadcrumb(); ?>
	<header class="sf-page-head">
		<h1 class="sf-h1"><?php echo esc_html( $tmt_title ); ?></h1>
		<?php if ( is_archive() && get_the_archive_description() ) : ?><div class="sf-muted"><?php the_archive_description(); ?></div><?php endif; ?>
	</header>
	<div class="sf-blog-cols<?php echo is_active_sidebar( 'blog' ) ? ' has-side' : ''; ?>">
		<div>
			<?php if ( have_posts() ) : ?>
				<div class="sf-posts">
					<?php while ( have_posts() ) : the_post(); ?>
						<?php tmt_post_card( get_post() ); ?>
					<?php endwhile; ?>
				</div>
				<div class="sf-pager">
					<?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => '‹', 'next_text' => '›' ) ); ?>
				</div>
			<?php else : ?>
				<div class="sf-empty">
					<p>Chưa có bài viết phù hợp.</p>
					<?php get_search_form(); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php if ( is_active_sidebar( 'blog' ) ) : ?>
			<aside class="sf-blog-side"><?php dynamic_sidebar( 'blog' ); ?></aside>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
