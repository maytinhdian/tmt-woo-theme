<?php
/**
 * Menu chính + nút mega menu + khung xổ (khung nằm ngoài <nav> vì nav cuộn ngang trên điện thoại sẽ cắt mất khung).
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;
$tmt_nav  = tmt_nav_items();
$tmt_mega = tmt_mega_menu();
if ( ! $tmt_nav && ! $tmt_mega ) {
	return;
}
?>
<nav class="sf-wrap sf-nav <?php echo esc_attr( tmt_nav_classes() ); ?>" aria-label="Menu chính">
	<?php if ( $tmt_mega ) : ?>
		<button type="button" class="sf-mega-btn" data-sf-dd="sf-dd-mega" aria-expanded="false" aria-controls="sf-dd-mega" aria-label="<?php echo esc_attr( $tmt_mega['label'] ); ?>" title="<?php echo esc_attr( $tmt_mega['label'] ); ?>"><?php echo tmt_icon( 'menu' ); // phpcs:ignore ?></button>
	<?php endif; ?>
	<?php foreach ( $tmt_nav as $i => $item ) : ?>
		<?php $cls = $item['active'] ? ' class="active" aria-current="page"' : ''; ?>
		<?php if ( $item['drop'] ) : ?>
			<span class="sf-nav-dd"><a href="<?php echo esc_url( $item['href'] ); ?>"<?php echo $cls; // phpcs:ignore ?>><?php echo tmt_nav_label( $item['label'] ); // phpcs:ignore ?></a><button type="button" class="sf-chev" data-sf-dd="sf-dd-<?php echo (int) $i; ?>" aria-expanded="false" aria-controls="sf-dd-<?php echo (int) $i; ?>" aria-label="<?php echo esc_attr( 'Nhóm con của ' . $item['label'] ); ?>"><?php echo tmt_icon( 'expand_more' ); // phpcs:ignore ?></button></span>
		<?php else : ?>
			<a href="<?php echo esc_url( $item['href'] ); ?>"<?php echo $cls; // phpcs:ignore ?><?php echo $item['external'] ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo tmt_nav_label( $item['label'] ); // phpcs:ignore ?></a>
		<?php endif; ?>
	<?php endforeach; ?>
</nav>
<?php if ( $tmt_mega ) : ?>
	<div class="sf-navdd" id="sf-dd-mega" hidden>
		<div class="sf-wrap">
			<div class="sf-dd-box sf-mega">
				<div class="sf-mega-cols" style="--c: <?php echo (int) min( count( $tmt_mega['columns'] ), 5 ); ?>">
					<?php foreach ( $tmt_mega['columns'] as $col ) : ?>
						<div class="sf-mega-col">
							<a href="<?php echo esc_url( $col['href'] ); ?>" class="sf-mega-h"><?php echo tmt_icon( $col['icon'] ); // phpcs:ignore ?><?php echo esc_html( $col['name'] ); ?></a>
							<?php if ( $col['children'] ) : ?>
								<ul>
									<?php foreach ( $col['children'] as $child ) : ?>
										<li><a href="<?php echo esc_url( $child['href'] ); ?>"><?php echo esc_html( $child['name'] ); ?></a></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</div>
<?php endif; ?>
<?php foreach ( $tmt_nav as $i => $item ) : ?>
	<?php if ( ! $item['drop'] ) { continue; } ?>
	<div class="sf-navdd" id="sf-dd-<?php echo (int) $i; ?>" hidden>
		<div class="sf-dd-box sf-drop">
			<div class="sf-drop-grid" style="--n: <?php echo (int) min( count( $item['drop']['items'] ), 5 ); ?>">
				<?php foreach ( $item['drop']['items'] as $child ) : ?>
					<div class="sf-drop-col">
						<a href="<?php echo esc_url( $child['href'] ); ?>" class="sf-drop-h"><?php echo tmt_icon( $child['icon'] ); // phpcs:ignore ?><span><?php echo esc_html( $child['name'] ); ?></span></a>
						<?php if ( $child['links'] ) : ?>
							<ul>
								<?php foreach ( $child['links'] as $link ) : ?>
									<li><a href="<?php echo esc_url( $link['href'] ); ?>"><?php echo esc_html( $link['name'] ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
			<?php if ( $item['drop']['all'] ) : ?>
				<a href="<?php echo esc_url( $item['drop']['all'] ); ?>" class="sf-drop-all">Xem tất cả <?php echo esc_html( $item['label'] ); ?></a>
			<?php endif; ?>
		</div>
	</div>
<?php endforeach; ?>
