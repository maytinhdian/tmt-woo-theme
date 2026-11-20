<?php
/**
 * Bình luận bài viết / trang.
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;
if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="sf-comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="sf-h2"><?php echo (int) get_comments_number(); ?> bình luận</h2>
		<ol class="sf-comment-list"><?php wp_list_comments( array( 'style' => 'ol', 'short_ping' => true, 'avatar_size' => 40 ) ); ?></ol>
		<?php the_comments_navigation(); ?>
	<?php endif; ?>
	<?php comment_form( array( 'title_reply' => 'Gửi bình luận', 'label_submit' => 'Gửi', 'class_submit' => 'sf-btn blue' ) ); ?>
</section>
