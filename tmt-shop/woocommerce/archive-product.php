<?php
/**
 * Trang danh sách sản phẩm (cửa hàng, danh mục, hãng, tìm kiếm): đường dẫn, tiêu đề + số sản phẩm, nhóm con,
 * bộ lọc kiểu thanh ngang hoặc cột trái/phải (Tùy biến → Trang danh sách), lưới thẻ, phân trang.
 *
 * @package TMT_Shop
 * @version 8.6.0
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

$tmt_style = tmt_opt( 'listing.filter_style' );
$tmt_side  = 'sidebar-right' === $tmt_style ? 'right' : 'left';
$tmt_style = in_array( $tmt_style, array( 'sidebar', 'sidebar-right' ), true ) ? 'sidebar' : 'bar';
$tmt_ctx   = tmt_filter_groups();
$tmt_total = (int) wc_get_loop_prop( 'total' );
$tmt_term  = is_product_taxonomy() ? get_queried_object() : null;
$tmt_kids  = ( $tmt_term && 'product_cat' === $tmt_term->taxonomy ) ? tmt_cat_children( $tmt_term->term_id ) : array();
?>
<div class="sf-wrap sf-page">
	<?php tmt_breadcrumb(); ?>
	<?php do_action( 'woocommerce_before_main_content' ); ?>
	<div class="sf-list-head">
		<h1 class="sf-list-title"><?php woocommerce_page_title(); ?></h1>
		<span class="sf-list-count"><?php echo esc_html( $tmt_total . ' sản phẩm' ); ?></span>
	</div>
	<?php do_action( 'woocommerce_archive_description' ); ?>
	<?php if ( $tmt_kids ) : ?>
		<div class="sf-subcats">
			<?php foreach ( $tmt_kids as $tmt_c ) : ?>
				<a href="<?php echo esc_url( get_term_link( $tmt_c ) ); ?>"><?php echo tmt_icon( tmt_category_icon( $tmt_c ) ); // phpcs:ignore ?><?php echo esc_html( $tmt_c->name ); ?></a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	<?php wc_print_notices(); ?>

	<?php if ( 'bar' === $tmt_style ) : ?>
		<?php get_template_part( 'template-parts/product/filters-bar', null, $tmt_ctx ); ?>
		<?php tmt_product_loop(); ?>
	<?php else : ?>
		<div class="sf-list-cols<?php echo 'right' === $tmt_side ? ' is-right' : ''; ?>">
			<div class="sf-list-side"><?php get_template_part( 'template-parts/product/filters-sidebar', null, $tmt_ctx + array( 'total' => $tmt_total ) ); ?></div>
			<div class="sf-list-main">
				<div class="sf-sortbar">
					<span class="sf-muted">Sắp xếp</span>
					<?php foreach ( tmt_sorts() as $k => $label ) : ?>
						<a href="<?php echo esc_url( tmt_filter_toggle( 'sap_xep', $k ) ); ?>" rel="nofollow"<?php echo tmt_filter_state()['sap_xep'] === $k ? ' class="on"' : ''; ?>><?php echo esc_html( $label ); ?></a>
					<?php endforeach; ?>
				</div>
				<?php get_template_part( 'template-parts/product/filters-active', null, $tmt_ctx ); ?>
				<?php tmt_product_loop(); ?>
			</div>
		</div>
	<?php endif; ?>
	<?php do_action( 'woocommerce_after_main_content' ); ?>
</div>
<?php
get_footer( 'shop' );
