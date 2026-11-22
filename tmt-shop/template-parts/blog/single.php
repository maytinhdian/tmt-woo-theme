<?php
/**
 * Bài Mẹo vặt (giống trang bài viết của app): ảnh bìa tràn ngang, mục lục thu gọn được, thanh chia sẻ,
 * video, "Bài này có giúp ích?", người viết, khung gọi tiệm, cột gợi ý sản phẩm, đọc tiếp, thanh dưới trên điện thoại.
 * Gọi trong vòng lặp (the_post() đã chạy).
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;

$tmt_post   = get_post();
$tmt_cat    = tmt_post_category( $tmt_post );
$tmt_video  = tmt_post_video( $tmt_post );
$tmt_url    = get_permalink();
$tmt_share  = rawurlencode( $tmt_url );
$tmt_phone  = tmt_phone();
$tmt_zalo   = tmt_zalo_url( tmt_opt( 'contact.zalo' ) );
$tmt_blogn  = get_option( 'page_for_posts' ) ? get_the_title( get_option( 'page_for_posts' ) ) : 'Mẹo vặt';
$tmt_views  = (int) get_post_meta( $tmt_post->ID, '_tmt_views', true );
$tmt_help   = (int) get_post_meta( $tmt_post->ID, '_tmt_helpful', true );
$tmt_voted  = in_array( $tmt_post->ID, tmt_cookie_ids( 'tmt_voted' ), true );
$tmt_levels = tmt_blog_levels();
$tmt_level  = get_post_meta( $tmt_post->ID, '_tmt_level', true );
$tmt_prods  = tmt_post_products( $tmt_post );
$tmt_rel    = tmt_related_posts( $tmt_post );

// Nội dung chạy trước để lấy mục lục (bộ lọc the_content gắn id cho H2/H3).
ob_start();
the_content();
wp_link_pages();
$tmt_body = ob_get_clean();
$tmt_toc  = array_values( array_filter( tmt_post_toc(), function ( $h ) { return 2 === $h['level']; } ) );
?>
<div class="mv-readbar" data-mv-readbar aria-hidden="true"></div>
<div class="mv" data-mv-post data-url="<?php echo esc_url( $tmt_url ); ?>" data-title="<?php echo esc_attr( get_the_title() ); ?>">
	<header class="mv-hero">
		<?php tmt_blog_cover( $tmt_post, array( 'eager' => true, 'size' => 'full' ) ); ?>
		<div class="shade" aria-hidden="true"></div>
		<div class="sf-wrap">
			<nav class="mv-crumb" aria-label="Đường dẫn">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Trang chủ</a><i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
				<a href="<?php echo esc_url( tmt_blog_url() ); ?>"><?php echo esc_html( $tmt_blogn ); ?></a>
				<?php if ( $tmt_cat ) : ?><i class="fa-solid fa-chevron-right" aria-hidden="true"></i><a href="<?php echo esc_url( get_category_link( $tmt_cat ) ); ?>"><?php echo esc_html( $tmt_cat->name ); ?></a><?php endif; ?>
			</nav>
			<?php if ( $tmt_cat ) : ?><a href="<?php echo esc_url( get_category_link( $tmt_cat ) ); ?>" class="mv-pill"><i class="<?php echo esc_attr( tmt_blog_icon( $tmt_cat ) ); ?>" aria-hidden="true"></i><?php echo esc_html( $tmt_cat->name ); ?></a><?php endif; ?>
			<h1><?php the_title(); ?></h1>
			<?php if ( has_excerpt() ) : ?><p><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
			<div class="mv-meta">
				<span class="d-inline-flex align-items-center"><span class="mv-av" aria-hidden="true"><?php echo esc_html( tmt_blog_initials() ); ?></span><b><?php echo esc_html( tmt_blog_author( $tmt_post ) ); ?></b></span>
				<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'd/m/Y' ) ); ?></time>
				<span><i class="fa-regular fa-clock" aria-hidden="true"></i><?php echo (int) tmt_reading_minutes( $tmt_post ); ?> phút đọc</span>
				<?php if ( $tmt_views > 0 ) : ?><span><i class="fa-regular fa-eye" aria-hidden="true"></i><?php echo esc_html( number_format( $tmt_views, 0, ',', '.' ) ); ?> lượt xem</span><?php endif; ?>
				<?php if ( isset( $tmt_levels[ $tmt_level ] ) ) : ?><span class="mv-lvl <?php echo esc_attr( $tmt_level ); ?>"><?php echo esc_html( $tmt_levels[ $tmt_level ] ); ?></span><?php endif; ?>
			</div>
		</div>
	</header>

	<div class="sf-wrap">
		<div class="mv-art">
			<div class="mv-rail" aria-label="Chia sẻ">
				<a class="mv-shr fb" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_attr( $tmt_share ); ?>" target="_blank" rel="noopener nofollow" aria-label="Chia sẻ lên Facebook"><i class="fa-brands fa-facebook-f" aria-hidden="true"></i></a>
				<button type="button" class="mv-shr ln" data-mv-copy aria-label="Sao chép liên kết bài"><i class="fa-solid fa-link" aria-hidden="true"></i></button>
				<span>Chia sẻ</span>
			</div>

			<article id="post-<?php the_ID(); ?>" <?php post_class( 'min-w-0' ); ?>>
				<?php if ( count( $tmt_toc ) > 1 ) : ?>
					<details class="mv-toc" id="muc-luc" open>
						<summary><span><i class="fa-solid fa-list-ul me-2" aria-hidden="true"></i>Mục lục</span><small><span class="when-open">Thu gọn</span><span class="when-closed">Mở</span></small></summary>
						<ol><?php foreach ( $tmt_toc as $h ) : ?><li><a href="#<?php echo esc_attr( $h['id'] ); ?>" data-mv-toc><?php echo esc_html( $h['text'] ); ?></a></li><?php endforeach; ?></ol>
					</details>
				<?php endif; ?>

				<?php if ( $tmt_video && 'youtube' === $tmt_video['platform'] ) : ?>
					<div class="mv-video"><div class="ratio"><iframe src="<?php echo esc_url( 'https://www.youtube-nocookie.com/embed/' . $tmt_video['id'] ); ?>" title="<?php echo esc_attr( 'Video: ' . get_the_title() ); ?>" loading="lazy" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></div></div>
				<?php elseif ( $tmt_video ) : ?>
					<div class="mv-video"><a class="tt" href="<?php echo esc_url( $tmt_video['url'] ); ?>" target="_blank" rel="noopener nofollow"><i class="fa-brands fa-tiktok" aria-hidden="true"></i><span><b>Xem video 60 giây trên TikTok</b><br><span class="small text-secondary">Mở TikTok của tiệm</span></span><i class="fa-solid fa-arrow-up-right-from-square ms-auto" style="font-size: 1rem" aria-hidden="true"></i></a></div>
				<?php endif; ?>

				<div class="sf-article entry-content"><?php echo $tmt_body; // phpcs:ignore WordPress.Security.EscapeOutput -- nội dung bài đã qua the_content. ?></div>

				<?php the_tags( '<div class="d-flex flex-wrap gap-2 mt-4 mv-tags">', '', '</div>' ); ?>

				<div class="mv-helpful" id="danh-gia">
					<?php if ( $tmt_voted ) : ?>
						<span>Cảm ơn anh chị đã cho tiệm biết!</span>
					<?php else : ?>
						<span>Bài này có giúp ích cho anh chị?</span>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-mv-vote>
							<input type="hidden" name="action" value="tmt_vote">
							<input type="hidden" name="post_id" value="<?php the_ID(); ?>">
							<button name="vote" value="co" class="mv-btn o"><i class="fa-regular fa-thumbs-up" aria-hidden="true"></i>Có<?php echo $tmt_help ? ' (' . (int) $tmt_help . ')' : ''; ?></button>
							<button name="vote" value="chua" class="mv-btn o"><i class="fa-regular fa-thumbs-down" aria-hidden="true"></i>Chưa</button>
						</form>
					<?php endif; ?>
					<span class="share">
						<a class="mv-shr fb" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_attr( $tmt_share ); ?>" target="_blank" rel="noopener nofollow" aria-label="Chia sẻ lên Facebook"><i class="fa-brands fa-facebook-f" aria-hidden="true"></i></a>
						<button type="button" class="mv-shr ln" data-mv-copy aria-label="Sao chép liên kết bài"><i class="fa-solid fa-link" aria-hidden="true"></i></button>
					</span>
				</div>

				<div class="mv-author">
					<span class="mv-av" aria-hidden="true"><?php echo esc_html( tmt_blog_initials() ); ?></span>
					<div>
						<b><?php echo esc_html( tmt_blog_author( $tmt_post ) ); ?></b> <i class="fa-solid fa-circle-check text-primary" aria-hidden="true"></i>
						<?php $tmt_bio = get_the_author_meta( 'description', $tmt_post->post_author ); ?>
						<p><?php echo esc_html( $tmt_bio ? $tmt_bio : 'Đội kỹ thuật ' . tmt_opt( 'shop.brand' ) . '. Bài viết từ những ca máy thật khách mang tới tiệm, làm thử trước khi đăng.' ); ?></p>
					</div>
				</div>

				<?php $tmt_cta = tmt_blog_cta( 'post' ); ?>
				<?php if ( $tmt_cta ) : ?>
					<div class="mv-endcta g-blue">
						<div>
							<h2><?php echo esc_html( $tmt_cta['title'] ); ?></h2>
							<p><?php echo esc_html( $tmt_cta['text'] ); ?></p>
						</div>
						<?php if ( $tmt_cta['buttons'] ) : ?>
							<div class="btns">
								<?php foreach ( $tmt_cta['buttons'] as $b ) : ?>
									<?php echo tmt_blog_cta_button( $b, 'y', 'phone' === $b['type'] && $tmt_cta['phone'] ? $b['label'] . ' ' . $tmt_cta['phone'] : null ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( comments_open() || get_comments_number() ) : ?>
					<div class="mv-comments"><?php comments_template(); ?></div>
				<?php endif; ?>
			</article>

			<aside class="mv-side">
				<?php if ( $tmt_prods ) : ?>
					<section class="mv-box">
						<h2><i class="fa-solid fa-bag-shopping" aria-hidden="true"></i>Tiệm gợi ý</h2>
						<?php foreach ( array_slice( $tmt_prods, 0, 4 ) as $p ) : ?>
							<a href="<?php echo esc_url( $p->get_permalink() ); ?>" class="mv-mini">
								<span class="ph"><?php echo $p->get_image_id() ? wp_get_attachment_image( $p->get_image_id(), 'thumbnail', false, array( 'alt' => '', 'loading' => 'lazy' ) ) : '<i class="fa-solid fa-box" aria-hidden="true"></i>'; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
								<span><b class="mv-clamp2"><?php echo esc_html( $p->get_name() ); ?></b><em><?php echo '' === $p->get_price() ? 'Liên hệ báo giá' : wp_kses_post( wc_price( wc_get_price_to_display( $p ) ) ); ?></em></span>
							</a>
						<?php endforeach; ?>
					</section>
				<?php endif; ?>
				<?php tmt_blog_ask( 'post' ); ?>
			</aside>
		</div>
	</div>

	<?php if ( $tmt_rel || $tmt_prods ) : ?>
		<section class="mv-after">
			<div class="sf-wrap">
				<?php if ( $tmt_rel ) : ?>
					<h2 class="mv-sec-t"><i class="fa-solid fa-book-open" aria-hidden="true"></i>Đọc tiếp</h2>
					<div class="mv-rel">
						<?php foreach ( $tmt_rel as $item ) : $ic = tmt_post_category( $item ); ?>
							<a href="<?php echo esc_url( get_permalink( $item ) ); ?>" class="card2 sf-fx-post">
								<?php tmt_blog_cover( $item, array( 'alt' => '', 'class' => 'sf-fx-media' ) ); ?>
								<div>
									<?php if ( $ic ) : ?><span class="mv-badge"><?php echo esc_html( $ic->name ); ?></span><?php endif; ?>
									<h3 class="mv-clamp2"><?php echo esc_html( get_the_title( $item ) ); ?></h3>
									<p class="small mb-0 mv-clamp2"><?php echo esc_html( wp_trim_words( get_the_excerpt( $item ), 24 ) ); ?></p>
									<?php tmt_blog_meta( $item, false ); ?>
								</div>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<?php if ( $tmt_prods ) : ?>
					<h2 class="mv-sec-t<?php echo $tmt_rel ? ' mt-5' : ''; ?>"><i class="fa-solid fa-bag-shopping" aria-hidden="true"></i>Sản phẩm nhắc tới trong bài</h2>
					<div class="mv-prods"><?php foreach ( $tmt_prods as $p ) { tmt_product_card( $p ); } ?></div>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<nav class="mv-mbar" data-sf-bottom-bar aria-label="Thao tác nhanh">
		<a href="<?php echo count( $tmt_toc ) > 1 ? '#muc-luc' : '#'; ?>" data-mv-open-toc><i class="fa-solid fa-list-ul" aria-hidden="true"></i>Mục lục</a>
		<button type="button" data-mv-share><i class="fa-solid fa-share-nodes" aria-hidden="true"></i>Chia sẻ</button>
		<a href="#danh-gia"><i class="fa-regular fa-thumbs-up" aria-hidden="true"></i>Hữu ích</a>
		<?php if ( $tmt_zalo ) : ?><a href="<?php echo esc_url( $tmt_zalo ); ?>" target="_blank" rel="noopener nofollow" class="mv-btn zalo"><b>Zalo</b>Hỏi tiệm</a><?php endif; ?>
	</nav>
</div>
