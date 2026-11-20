<?php
/**
 * Danh sách trang Giải pháp (/giai-phap/) hoặc Phần mềm (/phan-mem/): thẻ có icon, tóm tắt, giá từ.
 *
 * @package TMT_Shop_Tools
 */

defined( 'ABSPATH' ) || exit;

get_header();
$tmt_pt    = get_query_var( 'post_type' );
$tmt_pt    = is_array( $tmt_pt ) ? reset( $tmt_pt ) : $tmt_pt;
$tmt_types = tmt_lp_types();
$tmt_name  = isset( $tmt_types[ $tmt_pt ] ) ? $tmt_types[ $tmt_pt ][1] : post_type_archive_title( '', false );
$tmt_lead  = 'tmt_solution' === $tmt_pt ? 'Khảo sát miễn phí, báo giá rõ từng thiết bị, thi công gọn và bảo hành tận nơi.' : 'Phần mềm tiệm cài đặt, hướng dẫn và hỗ trợ, bán kèm thiết bị trọn bộ.';
?>
<div class="lp">
	<section class="lp-hero lp-hero-sm">
		<div class="sf-wrap">
			<div>
				<nav class="lp-crumb" aria-label="Đường dẫn"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Trang chủ</a> / <?php echo esc_html( $tmt_name ); ?></nav>
				<h1><?php echo esc_html( $tmt_name ); ?></h1>
				<p class="lead"><?php echo esc_html( $tmt_lead ); ?></p>
			</div>
		</div>
	</section>
	<section class="lp-sec alt">
		<div class="sf-wrap">
			<?php if ( have_posts() ) : ?>
				<div class="lp-list">
					<?php while ( have_posts() ) : the_post(); $lp = tmt_lp_get( get_the_ID() ); $prices = array_filter( wp_list_pluck( $lp['packages'], 'price' ), 'is_numeric' ); ?>
						<a href="<?php the_permalink(); ?>" class="sf-fx-post">
							<?php if ( has_post_thumbnail() ) : ?>
								<span class="img sf-fx-media"><?php the_post_thumbnail( 'tmt-card', array( 'alt' => '', 'loading' => 'lazy' ) ); ?></span>
							<?php else : ?>
								<span class="ic"><i class="fa-solid fa-<?php echo esc_attr( $lp['icon'] ? $lp['icon'] : 'box-open' ); ?>" aria-hidden="true"></i></span>
							<?php endif; ?>
							<b><?php the_title(); ?></b>
							<span class="tx"><?php echo esc_html( has_excerpt() ? get_the_excerpt() : $lp['intro'] ); ?></span>
							<?php if ( $prices ) : ?><span class="from">Gói từ <b><?php echo esc_html( number_format( (float) min( $prices ), 0, ',', '.' ) ); ?>₫</b></span><?php endif; ?>
							<span class="go">Xem chi tiết <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></span>
						</a>
					<?php endwhile; ?>
				</div>
			<?php else : ?>
				<p class="text-center">Chưa có trang nào.</p>
			<?php endif; ?>
		</div>
	</section>
</div>
<?php
get_footer();
