<?php
/**
 * Phần mềm bản quyền (kiểu A của app): khung cam kết bên trái, thẻ sản phẩm bên phải, mỗi danh mục một tab
 * (đổi tab tại chỗ, theme.js), dải các bước mua key bên dưới.
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;
$tabs = tmt_home_software_tabs();
if ( ! $tabs ) {
	return;
}
$points = array_values( array_filter( array_map( 'trim', preg_split( '/\R/u', (string) tmt_opt( 'home.software_points' ) ) ) ) );
$steps  = array();
foreach ( preg_split( '/\R/u', (string) tmt_opt( 'home.software_steps' ) ) as $line ) {
	$p = array_map( 'trim', explode( '|', $line, 2 ) );
	if ( '' !== $p[0] && count( $steps ) < 4 ) {
		$steps[] = $p;
	}
}
$icons = array( 'fa-certificate', 'fa-envelope-open-text', 'fa-headset', 'fa-bell', 'fa-file-invoice', 'fa-circle-check' );
$zalo  = tmt_zalo_url( tmt_opt( 'contact.zalo' ) );
$uid   = 'sfsw-' . wp_rand( 1000, 9999 );
$many  = count( $tabs ) > 1;
$title = tmt_opt( 'home.software_title' );
$text  = tmt_opt( 'home.software_banner_text' );
?>
<section class="sf-hs sf-sw" data-sw>
	<div class="sf-wrap">
		<div class="sf-sh">
			<h2><i class="fa-solid fa-key" aria-hidden="true"></i><?php echo esc_html( $title ? $title : 'Phần mềm bản quyền' ); ?></h2>
			<?php if ( $many ) : ?>
				<div class="sf-sw-tabs" role="tablist" aria-label="Loại phần mềm">
					<?php foreach ( $tabs as $i => $tab ) : ?>
						<button type="button" role="tab" id="<?php echo esc_attr( $uid . '-t' . $i ); ?>" aria-controls="<?php echo esc_attr( $uid . '-p' . $i ); ?>" aria-selected="<?php echo $i ? 'false' : 'true'; ?>"<?php echo $i ? ' tabindex="-1"' : ''; ?>
							data-sw-tab data-href="<?php echo esc_url( get_term_link( $tab['term'] ) ); ?>"><?php echo esc_html( $tab['term']->name ); ?></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<a href="<?php echo esc_url( get_term_link( $tabs[0]['term'] ) ); ?>" class="more" data-sw-more>Xem tất cả<i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
		</div>
		<div class="sf-sw-grid">
			<div class="sf-sw-ban">
				<h3><?php echo esc_html( tmt_opt( 'home.software_banner_title' ) ); ?></h3>
				<?php if ( $text ) : ?><p><?php echo esc_html( $text ); ?></p><?php endif; ?>
				<?php if ( $points ) : ?>
					<ul>
						<?php foreach ( $points as $i => $point ) : ?>
							<li><i class="fa-solid <?php echo esc_attr( isset( $icons[ $i ] ) ? $icons[ $i ] : 'fa-circle-check' ); ?>" aria-hidden="true"></i><?php echo esc_html( $point ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<?php if ( $zalo ) : ?>
					<a href="<?php echo esc_url( $zalo ); ?>" target="_blank" rel="noopener" class="sf-sw-btn"><i class="fa-solid fa-comments" aria-hidden="true"></i>Nhờ tiệm tư vấn</a>
				<?php endif; ?>
				<i class="fa-brands fa-windows sf-sw-big" aria-hidden="true"></i>
			</div>
			<div class="sf-sw-panels">
				<?php foreach ( $tabs as $i => $tab ) : ?>
					<div class="sf-sw-row" id="<?php echo esc_attr( $uid . '-p' . $i ); ?>"<?php echo $many ? ' role="tabpanel" aria-labelledby="' . esc_attr( $uid . '-t' . $i ) . '"' : ''; ?><?php echo $i ? ' hidden' : ''; ?>>
						<?php foreach ( $tab['products'] as $p ) : ?><div><?php tmt_product_card( $p ); ?></div><?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php if ( $steps ) : ?>
			<ol class="sf-sw-steps">
				<?php foreach ( $steps as $i => $step ) : ?>
					<li><span class="n" aria-hidden="true"><?php echo (int) $i + 1; ?></span><span><b><?php echo esc_html( $step[0] ); ?></b><?php echo esc_html( isset( $step[1] ) ? $step[1] : '' ); ?></span></li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>
	</div>
</section>
