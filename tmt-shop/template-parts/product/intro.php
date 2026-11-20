<?php
/**
 * Giới thiệu nâng cao (chép từ app: blocks/product/intro + intro-block). Khối soạn ở trang sửa sản phẩm
 * (plugin TMT Shop Tools, tmt_intro_blocks()). Kiểu ở Tùy biến → Trang sản phẩm: `full` toàn khổ kiểu trang của hãng,
 * `compact` gọn trong khung + mục lục trái. Styles assets/css/intro.css, mục lục tô theo khối đang xem ở product.js.
 *
 * Biến: $args['blocks'] (đã present), $args['product'], $args['eol'] (ngừng kinh doanh: không nút "Mua ngay").
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;

$blocks  = $args['blocks'];
$product = $args['product'];
$style   = 'compact' === tmt_opt( 'product.intro_style' ) ? 'compact' : 'full';
$accent  = 'yellow' === tmt_opt( 'product.intro_accent' ) ? 'yellow' : 'blue';
$nav     = array_values( array_filter( $blocks, fn( $b ) => $b['label'] ) );
$shownav = tmt_opt( 'product.intro_nav' ) && count( $nav ) > 1;
$types   = tmt_intro_types();

/** Một khối, cùng HTML cho hai kiểu (CSS khác theo .sf-gt-full / .sf-gt-compact). */
$tmt_gt_block = function ( array $b ) use ( $product ) {
	$e = fn( $k ) => isset( $b[ $k ] ) ? (string) $b[ $k ] : '';
	switch ( $b['type'] ) {
		case 'hero':
			?>
			<section class="gt gt-hero" id="<?php echo esc_attr( $b['anchor'] ); ?>">
				<div class="sf-gt-in">
					<?php if ( '' !== $e( 'eyebrow' ) ) : ?><p class="gt-eyebrow"><?php echo esc_html( $e( 'eyebrow' ) ); ?></p><?php endif; ?>
					<?php if ( '' !== $e( 'title' ) ) : ?><h2 class="gt-hero-title"><?php echo esc_html( $e( 'title' ) ); ?></h2><?php endif; ?>
					<?php if ( '' !== $e( 'text' ) ) : ?><p class="gt-lead"><?php echo esc_html( $e( 'text' ) ); ?></p><?php endif; ?>
					<?php if ( ! empty( $b['image_url'] ) ) : ?>
						<div class="gt-hero-img"><img src="<?php echo esc_url( $b['image_url'] ); ?>" alt="<?php echo esc_attr( '' !== $e( 'image_alt' ) ? $e( 'image_alt' ) : $e( 'title' ) ); ?>" loading="lazy" decoding="async"></div>
					<?php endif; ?>
				</div>
			</section>
			<?php
			break;
		case 'stats':
			?>
			<section class="gt gt-stats" id="<?php echo esc_attr( $b['anchor'] ); ?>">
				<div class="sf-gt-in">
					<?php if ( '' !== $e( 'title' ) ) : ?><h3 class="gt-h"><?php echo esc_html( $e( 'title' ) ); ?></h3><?php endif; ?>
					<div class="gt-stats-grid" style="--n: <?php echo (int) min( 4, max( 1, count( $b['items'] ) ) ); ?>">
						<?php foreach ( $b['items'] as $s ) : ?>
							<div class="gt-stat"><strong><?php echo esc_html( $s['value'] ); ?></strong><span><?php echo esc_html( $s['title'] ); ?></span></div>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
			<?php
			break;
		case 'media':
			$img = ! empty( $b['image_url'] );
			?>
			<section class="gt gt-media gt-bg-<?php echo esc_attr( $e( 'bg' ) ? $e( 'bg' ) : 'white' ); ?><?php echo ! empty( $b['reverse'] ) ? ' rev' : ''; ?><?php echo $img ? '' : ' no-img'; ?>" id="<?php echo esc_attr( $b['anchor'] ); ?>">
				<div class="sf-gt-in gt-row">
					<?php if ( $img ) : ?>
						<figure class="gt-img sf-fx-media"><img src="<?php echo esc_url( $b['image_url'] ); ?>" alt="<?php echo esc_attr( '' !== $e( 'image_alt' ) ? $e( 'image_alt' ) : $e( 'title' ) ); ?>" loading="lazy" decoding="async"></figure>
					<?php endif; ?>
					<div class="gt-copy">
						<?php if ( '' !== $e( 'eyebrow' ) ) : ?><p class="gt-eyebrow"><?php echo esc_html( $e( 'eyebrow' ) ); ?></p><?php endif; ?>
						<?php if ( '' !== $e( 'title' ) ) : ?><h3 class="gt-title"><?php echo esc_html( $e( 'title' ) ); ?></h3><?php endif; ?>
						<?php if ( ! empty( $b['html'] ) ) : ?><div class="gt-prose"><?php echo wp_kses_post( $b['html'] ); ?></div><?php endif; ?>
					</div>
				</div>
			</section>
			<?php
			break;
		case 'features':
			?>
			<section class="gt gt-features" id="<?php echo esc_attr( $b['anchor'] ); ?>">
				<div class="sf-gt-in">
					<?php if ( '' !== $e( 'title' ) || '' !== $e( 'text' ) ) : ?>
						<div class="gt-head">
							<?php if ( '' !== $e( 'title' ) ) : ?><h3 class="gt-h"><?php echo esc_html( $e( 'title' ) ); ?></h3><?php endif; ?>
							<?php if ( '' !== $e( 'text' ) ) : ?><p><?php echo esc_html( $e( 'text' ) ); ?></p><?php endif; ?>
						</div>
					<?php endif; ?>
					<div class="gt-feat-grid">
						<?php foreach ( $b['items'] as $f ) : ?>
							<div class="gt-feat sf-fx-post">
								<?php if ( '' !== $f['icon'] ) : ?><span class="gt-ic"><i class="<?php echo esc_attr( $f['icon'] ); ?>" aria-hidden="true"></i></span><?php endif; ?>
								<b><?php echo esc_html( $f['title'] ); ?></b>
								<?php if ( '' !== $f['text'] ) : ?><p><?php echo esc_html( $f['text'] ); ?></p><?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
			<?php
			break;
		case 'video':
			?>
			<section class="gt gt-video" id="<?php echo esc_attr( $b['anchor'] ); ?>">
				<div class="sf-gt-in">
					<?php if ( '' !== $e( 'title' ) || '' !== $e( 'text' ) ) : ?>
						<div class="gt-head">
							<?php if ( '' !== $e( 'title' ) ) : ?><h3 class="gt-h"><?php echo esc_html( $e( 'title' ) ); ?></h3><?php endif; ?>
							<?php if ( '' !== $e( 'text' ) ) : ?><p><?php echo esc_html( $e( 'text' ) ); ?></p><?php endif; ?>
						</div>
					<?php endif; ?>
					<div class="gt-frame">
						<iframe src="https://www.youtube-nocookie.com/embed/<?php echo esc_attr( $b['youtube'] ); ?>" title="<?php echo esc_attr( '' !== $e( 'title' ) ? $e( 'title' ) : 'Video ' . $product->get_name() ); ?>" loading="lazy" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
					</div>
				</div>
			</section>
			<?php
			break;
		case 'compare':
			?>
			<section class="gt gt-compare" id="<?php echo esc_attr( $b['anchor'] ); ?>">
				<div class="sf-gt-in">
					<div class="gt-head">
						<h3 class="gt-h"><?php echo esc_html( '' !== $e( 'title' ) ? $e( 'title' ) : 'So sánh' ); ?></h3>
						<?php if ( '' !== $e( 'text' ) ) : ?><p><?php echo esc_html( $e( 'text' ) ); ?></p><?php endif; ?>
					</div>
					<div class="gt-table">
						<table>
							<thead><tr><th scope="col"><span class="visually-hidden">Tiêu chí</span></th><?php foreach ( $b['columns'] as $c => $name ) : ?><th scope="col"<?php echo 0 === $c ? ' class="me"' : ''; ?>><?php echo esc_html( $name ); ?></th><?php endforeach; ?></tr></thead>
							<tbody>
								<?php foreach ( $b['rows'] as $row ) : ?>
									<tr>
										<th scope="row"><?php echo esc_html( $row['label'] ); ?></th>
										<?php foreach ( $b['columns'] as $c => $name ) : ?>
											<?php
											$cell  = tmt_intro_compare_cell( isset( $row['values'][ $c ] ) ? $row['values'][ $c ] : '' );
											$class = trim( ( 0 === $c ? 'me ' : '' ) . $cell['class'] );
											?>
											<td<?php echo $class ? ' class="' . esc_attr( $class ) . '"' : ''; ?>><?php if ( $cell['icon'] ) : ?><i class="<?php echo esc_attr( $cell['icon'] ); ?>" aria-hidden="true"></i><span class="visually-hidden"><?php echo esc_html( $cell['text'] ); ?></span><?php else : ?><?php echo esc_html( $cell['text'] ); ?><?php endif; ?></td>
										<?php endforeach; ?>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
			</section>
			<?php
			break;
		case 'box':
			?>
			<section class="gt gt-box" id="<?php echo esc_attr( $b['anchor'] ); ?>">
				<div class="sf-gt-in">
					<div class="gt-head"><h3 class="gt-h"><?php echo esc_html( '' !== $e( 'title' ) ? $e( 'title' ) : 'Trong hộp có gì' ); ?></h3></div>
					<ul class="gt-chips">
						<?php foreach ( $b['items'] as $i ) : ?>
							<li><?php if ( '' !== $i['icon'] ) : ?><i class="<?php echo esc_attr( $i['icon'] ); ?>" aria-hidden="true"></i><?php endif; ?><?php echo esc_html( $i['title'] ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</section>
			<?php
			break;
		case 'text':
			?>
			<section class="gt gt-text" id="<?php echo esc_attr( $b['anchor'] ); ?>">
				<div class="sf-gt-in gt-narrow">
					<?php if ( '' !== $e( 'title' ) ) : ?><h3 class="gt-title"><?php echo esc_html( $e( 'title' ) ); ?></h3><?php endif; ?>
					<?php if ( ! empty( $b['html'] ) ) : ?><div class="gt-prose"><?php echo wp_kses_post( $b['html'] ); ?></div><?php endif; ?>
				</div>
			</section>
			<?php
			break;
	}
};
?>
<section id="gioi-thieu" class="sf-gt sf-gt-<?php echo esc_attr( $style ); ?> sf-gt-acc-<?php echo esc_attr( $accent ); ?>" aria-label="Giới thiệu sản phẩm" data-gt>
	<?php if ( 'full' === $style ) : ?>
		<div class="sf-gt-bleed">
			<?php if ( $shownav ) : ?>
				<nav class="sf-gt-nav" aria-label="Mục lục giới thiệu">
					<div class="sf-gt-in">
						<b class="sf-gt-nav-name"><?php echo esc_html( wp_html_excerpt( $product->get_name(), 34, '…' ) ); ?></b>
						<div class="sf-gt-nav-links">
							<?php foreach ( $nav as $b ) : ?><a href="#<?php echo esc_attr( $b['anchor'] ); ?>" data-gt-link><?php echo esc_html( $b['label'] ); ?></a><?php endforeach; ?>
						</div>
						<?php if ( empty( $args['eol'] ) && $product->is_purchasable() && '' !== $product->get_price() ) : ?>
							<a href="#product-<?php echo (int) $product->get_id(); ?>" class="sf-gt-buy">Mua ngay · <?php echo wp_kses_post( wc_price( wc_get_price_to_display( $product ) ) ); ?></a>
						<?php endif; ?>
					</div>
				</nav>
			<?php endif; ?>
			<?php foreach ( $blocks as $b ) : ?>
				<?php $tmt_gt_block( $b ); ?>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<div class="sf-panel sf-gt-box">
			<h2 class="sf-panel-h">Giới thiệu chi tiết</h2>
			<div class="sf-gt-cols<?php echo $shownav ? '' : ' no-toc'; ?>">
				<?php if ( $shownav ) : ?>
					<nav class="sf-gt-toc" aria-label="Mục lục giới thiệu">
						<?php foreach ( $nav as $b ) : ?>
							<a href="#<?php echo esc_attr( $b['anchor'] ); ?>" data-gt-link><i class="fa-solid <?php echo esc_attr( $types[ $b['type'] ][1] ); ?>" aria-hidden="true"></i><?php echo esc_html( $b['label'] ); ?></a>
						<?php endforeach; ?>
					</nav>
				<?php endif; ?>
				<div class="sf-gt-body">
					<?php foreach ( $blocks as $b ) : ?>
						<?php $tmt_gt_block( $b ); ?>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	<?php endif; ?>
</section>
