<?php
/**
 * Một bài viết: ảnh bìa, tiêu đề, ngày, nội dung, thẻ, bài liên quan, bình luận.
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) :
	the_post();
	$tmt_cats = get_the_category();
	?>
	<div class="sf-wrap sf-page">
		<?php tmt_breadcrumb(); ?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'sf-article' ); ?>>
			<header class="sf-article-head">
				<?php if ( $tmt_cats ) : ?><a class="sf-post-cat" href="<?php echo esc_url( get_category_link( $tmt_cats[0] ) ); ?>"><?php echo esc_html( $tmt_cats[0]->name ); ?></a><?php endif; ?>
				<h1 class="sf-h1"><?php the_title(); ?></h1>
				<p class="sf-muted"><?php echo tmt_icon( 'event' ); // phpcs:ignore ?> <?php echo esc_html( get_the_date( 'd/m/Y' ) ); ?></p>
			</header>
			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="sf-article-cover"><?php the_post_thumbnail( 'large', array( 'fetchpriority' => 'high' ) ); ?></figure>
			<?php endif; ?>
			<div class="entry-content">
				<?php the_content(); ?>
				<?php wp_link_pages(); ?>
			</div>
			<?php the_tags( '<p class="sf-article-tags">' . tmt_icon( 'sell' ) . ' ', ', ', '</p>' ); ?>
		</article>
		<?php
		$tmt_related = $tmt_cats ? get_posts( array( 'category__in' => wp_list_pluck( $tmt_cats, 'term_id' ), 'post__not_in' => array( get_the_ID() ), 'numberposts' => 3 ) ) : array();
		if ( $tmt_related ) :
			?>
			<section class="sf-hs">
				<div class="sf-sh"><h2>Bài cùng chủ đề</h2></div>
				<div class="sf-posts"><?php foreach ( $tmt_related as $p ) { tmt_post_card( $p ); } ?></div>
			</section>
		<?php endif; ?>
		<?php if ( comments_open() || get_comments_number() ) : ?>
			<div class="sf-article"><?php comments_template(); ?></div>
		<?php endif; ?>
	</div>
	<?php
endwhile;
get_footer();
