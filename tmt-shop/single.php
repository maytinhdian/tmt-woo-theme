<?php
/**
 * Một bài viết. Bài Mẹo vặt: template-parts/blog/single.php; loại nội dung khác: khung bài đơn giản.
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) :
	the_post();
	if ( 'post' === get_post_type() ) {
		get_template_part( 'template-parts/blog/single' );
		continue;
	}
	?>
	<div class="sf-wrap sf-page">
		<?php tmt_breadcrumb(); ?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'sf-article' ); ?>>
			<header class="sf-article-head"><h1 class="sf-h1"><?php the_title(); ?></h1></header>
			<?php if ( has_post_thumbnail() ) : ?><figure class="sf-article-cover"><?php the_post_thumbnail( 'large' ); ?></figure><?php endif; ?>
			<div class="entry-content"><?php the_content(); ?><?php wp_link_pages(); ?></div>
		</article>
	</div>
	<?php
endwhile;
get_footer();
