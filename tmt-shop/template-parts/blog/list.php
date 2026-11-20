<?php
/**
 * Danh sách Mẹo vặt kiểu "dòng tin + video" (giống trang /meo-vat của app):
 * bài chọn (bài ghim), cột chủ đề, tab Mới nhất / Đọc nhiều / Video, dòng tin, cột phải Mẹo 60 giây + "Không muốn tự làm?".
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;

global $wp_query;
$tmt_tab       = tmt_blog_tab();
$tmt_blog_id   = (int) get_option( 'page_for_posts' );
$tmt_blog_name = $tmt_blog_id ? get_the_title( $tmt_blog_id ) : 'Mẹo vặt';
$tmt_base      = remove_query_arg( array( 'xem', 'paged' ), get_pagenum_link( 1, false ) );
$tmt_active    = is_category() ? get_queried_object() : null;
$tmt_search    = is_search() ? get_search_query() : '';
$tmt_total     = (int) wp_count_posts()->publish;

if ( is_category() || is_tag() ) {
	$tmt_heading = ( is_tag() ? '#' : '' ) . single_term_title( '', false );
	$tmt_intro   = wp_strip_all_tags( term_description() );
} elseif ( is_search() ) {
	$tmt_heading = 'Tìm bài mẹo vặt';
	$tmt_intro   = '';
} elseif ( is_author() || is_date() ) {
	$tmt_heading = wp_strip_all_tags( get_the_archive_title() );
	$tmt_intro   = '';
} else {
	$tmt_heading = $tmt_blog_name;
	$tmt_intro   = $tmt_blog_id ? get_the_excerpt( $tmt_blog_id ) : '';
	if ( '' === $tmt_intro ) {
		$tmt_intro = 'Mẹo sửa máy, chọn linh kiện, cài phần mềm, từ những ca máy thật khách mang tới tiệm.';
	}
}

$tmt_featured = array();
if ( is_home() && ! is_paged() && '' === $tmt_tab ) {
	$tmt_sticky = array_slice( get_option( 'sticky_posts', array() ), 0, 5 );
	if ( $tmt_sticky ) {
		$tmt_featured = get_posts( array( 'post__in' => $tmt_sticky, 'numberposts' => 5, 'ignore_sticky_posts' => true ) );
	}
}
$tmt_cats   = get_categories( array( 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 12 ) );
$tmt_shorts = tmt_blog_shorts();
?>
<div class="mv sf-wrap">
	<nav class="mv-crumb" aria-label="Đường dẫn">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Trang chủ</a><i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
		<?php if ( is_home() ) : ?>
			<span><?php echo esc_html( $tmt_blog_name ); ?></span>
		<?php else : ?>
			<a href="<?php echo esc_url( tmt_blog_url() ); ?>"><?php echo esc_html( $tmt_blog_name ); ?></a><i class="fa-solid fa-chevron-right" aria-hidden="true"></i><span><?php echo esc_html( $tmt_heading ); ?></span>
		<?php endif; ?>
	</nav>
	<div class="mv-head">
		<div>
			<h1 class="mv-h1"><?php echo esc_html( $tmt_heading ); ?></h1>
			<?php if ( '' !== $tmt_search ) : ?>
				<p class="mv-sub">Kết quả tìm "<b><?php echo esc_html( $tmt_search ); ?></b>": <?php echo (int) $wp_query->found_posts; ?> bài · <a href="<?php echo esc_url( tmt_blog_url() ); ?>">Bỏ tìm</a></p>
			<?php elseif ( $tmt_intro ) : ?>
				<p class="mv-sub"><?php echo esc_html( $tmt_intro ); ?></p>
			<?php endif; ?>
		</div>
		<form class="mv-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
			<input type="search" name="s" value="<?php echo esc_attr( $tmt_search ); ?>" placeholder="<?php echo esc_attr( 'Tìm trong ' . $tmt_total . ' bài mẹo...' ); ?>" aria-label="Tìm bài mẹo vặt" maxlength="100">
			<input type="hidden" name="post_type" value="post">
			<button type="submit">Tìm</button>
		</form>
	</div>

	<?php if ( $tmt_featured ) : ?>
		<section class="mv-top" aria-label="Bài chọn" data-mv-slider>
			<?php foreach ( $tmt_featured as $i => $f ) : $fc = tmt_post_category( $f ); ?>
				<div class="mv-slide"<?php echo $i ? ' hidden' : ''; ?>>
					<?php tmt_blog_cover( $f, array( 'eager' => 0 === $i, 'size' => 'large', 'alt' => '' ) ); ?>
					<div class="tx">
						<span class="mv-pill align-self-start"><i class="fa-solid fa-star" aria-hidden="true"></i>Bài chọn<?php echo $fc ? ' · ' . esc_html( $fc->name ) : ''; ?></span>
						<h2><a href="<?php echo esc_url( get_permalink( $f ) ); ?>"><?php echo esc_html( get_the_title( $f ) ); ?></a></h2>
						<?php if ( has_excerpt( $f ) ) : ?><p><?php echo esc_html( get_the_excerpt( $f ) ); ?></p><?php endif; ?>
						<?php tmt_blog_meta( $f ); ?>
						<?php if ( count( $tmt_featured ) > 1 ) : ?>
							<div class="mv-dots"><?php foreach ( $tmt_featured as $k => $unused ) : ?><button type="button"<?php echo $k === $i ? ' class="on"' : ''; ?> data-mv-go="<?php echo (int) $k; ?>" aria-label="<?php echo esc_attr( 'Bài chọn ' . ( $k + 1 ) ); ?>"></button><?php endforeach; ?></div>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</section>
	<?php endif; ?>

	<div class="mv-main">
		<nav class="mv-vnav" aria-label="Chủ đề">
			<b>Chủ đề</b>
			<a href="<?php echo esc_url( tmt_blog_url() ); ?>"<?php echo is_home() && '' === $tmt_tab ? ' class="on"' : ''; ?>><i class="fa-solid fa-border-all" aria-hidden="true"></i>Tất cả<em><?php echo (int) $tmt_total; ?></em></a>
			<?php foreach ( $tmt_cats as $c ) : ?>
				<a href="<?php echo esc_url( get_category_link( $c ) ); ?>"<?php echo $tmt_active && $tmt_active->term_id === $c->term_id ? ' class="on"' : ''; ?>><i class="<?php echo esc_attr( tmt_blog_icon( $c ) ); ?>" aria-hidden="true"></i><?php echo esc_html( $c->name ); ?><em><?php echo (int) $c->count; ?></em></a>
			<?php endforeach; ?>
			<?php if ( $tmt_shorts ) : ?>
				<b class="mt-3">Dạng bài</b>
				<a href="<?php echo esc_url( add_query_arg( 'xem', 'video', tmt_blog_url() ) ); ?>"<?php echo is_home() && 'video' === $tmt_tab ? ' class="on"' : ''; ?>><i class="fa-solid fa-circle-play" aria-hidden="true"></i>Video ngắn</a>
			<?php endif; ?>
		</nav>

		<div class="min-w-0">
			<?php if ( $tmt_cats ) : ?>
				<nav class="mv-cats d-md-none mb-2" aria-label="Chủ đề">
					<a href="<?php echo esc_url( tmt_blog_url() ); ?>"<?php echo is_home() ? ' class="on"' : ''; ?>><i class="fa-solid fa-border-all" aria-hidden="true"></i>Tất cả</a>
					<?php foreach ( $tmt_cats as $c ) : ?>
						<a href="<?php echo esc_url( get_category_link( $c ) ); ?>"<?php echo $tmt_active && $tmt_active->term_id === $c->term_id ? ' class="on"' : ''; ?>><i class="<?php echo esc_attr( tmt_blog_icon( $c ) ); ?>" aria-hidden="true"></i><?php echo esc_html( $c->name ); ?></a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>
			<nav class="mv-tabs" aria-label="Sắp xếp">
				<?php foreach ( tmt_blog_tabs() as $key => $t ) : ?>
					<a href="<?php echo esc_url( '' === $key ? $tmt_base : add_query_arg( 'xem', $key, $tmt_base ) ); ?>"<?php echo $tmt_tab === $key ? ' class="on" aria-current="page"' : ''; ?>><i class="<?php echo esc_attr( $t[1] ); ?>" aria-hidden="true"></i><?php echo esc_html( $t[0] ); ?></a>
				<?php endforeach; ?>
			</nav>

			<div class="mv-feed">
				<?php if ( have_posts() ) : ?>
					<?php while ( have_posts() ) : the_post(); $p = get_post(); $pc = tmt_post_category( $p ); ?>
						<article class="it sf-fx-post">
							<a href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php tmt_blog_cover( $p, array( 'alt' => '', 'class' => 'sf-fx-media' ) ); ?></a>
							<div>
								<?php if ( $pc ) : ?><a href="<?php echo esc_url( get_category_link( $pc ) ); ?>" class="mv-badge"><?php echo esc_html( $pc->name ); ?></a><?php endif; ?>
								<h2 class="mv-clamp2"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
								<p class="mv-clamp2"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 30 ) ); ?></p>
								<?php tmt_blog_meta( $p ); ?>
							</div>
						</article>
					<?php endwhile; ?>
				<?php else : ?>
					<p class="mv-empty"><?php echo 'video' === $tmt_tab ? 'Chưa có bài nào kèm video.' : 'Chưa có bài viết nào ở đây.'; ?> Anh chị quay lại sau nhé.</p>
				<?php endif; ?>
			</div>

			<?php if ( $wp_query->max_num_pages > 1 ) : ?>
				<nav class="d-flex justify-content-center align-items-center gap-3 mt-4" aria-label="Phân trang">
					<?php $tmt_paged = max( 1, (int) get_query_var( 'paged' ) ); ?>
					<?php if ( $tmt_paged > 1 ) : ?><a href="<?php echo esc_url( get_previous_posts_page_link() ); ?>" class="mv-btn o"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i>Trang trước</a><?php endif; ?>
					<span class="small">Trang <?php echo (int) $tmt_paged; ?> / <?php echo (int) $wp_query->max_num_pages; ?></span>
					<?php if ( $tmt_paged < $wp_query->max_num_pages ) : ?><a href="<?php echo esc_url( get_next_posts_page_link() ); ?>" class="mv-btn o">Xem thêm bài<i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a><?php endif; ?>
				</nav>
			<?php endif; ?>
		</div>

		<aside class="mv-side">
			<?php tmt_blog_shorts_box( $tmt_shorts ); ?>
			<?php tmt_blog_ask(); ?>
			<?php if ( is_active_sidebar( 'blog' ) ) : ?><?php dynamic_sidebar( 'blog' ); ?><?php endif; ?>
		</aside>
	</div>
</div>
