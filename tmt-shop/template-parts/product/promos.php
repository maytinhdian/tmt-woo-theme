<?php
/**
 * Banner khuyến mãi của một vị trí trên trang sản phẩm (dữ liệu từ tmt_product_promos()).
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="sf-promos at-<?php echo esc_attr( $args['at'] ); ?>">
	<?php foreach ( $args['items'] as $p ) : ?>
		<?php
		$tag  = $p['link'] ? 'a' : 'div';
		$href = $p['link'] ? tmt_link_attrs( $p['link'] ) : '';
		$cls  = 'sf-promo t-' . $p['type'] . ' r-normal';
		$cd   = $p['ends_at'] ? ' data-promo-end="' . esc_attr( $p['ends_at'] ) . '"' : '';
		?>
		<?php if ( 'box' === $p['type'] ) : ?>
			<div class="<?php echo esc_attr( $cls ); ?>" style="<?php echo esc_attr( $p['vars'] ); ?>"<?php echo $cd; // phpcs:ignore ?>>
				<div class="sf-promo-h"><i class="<?php echo esc_attr( $p['icon'] ); ?>" aria-hidden="true"></i><span><?php echo esc_html( $p['title'] ? $p['title'] : 'Quà tặng và ưu đãi' ); ?></span>
					<?php if ( $cd ) : ?><span class="sf-promo-cd" data-promo-cd aria-label="Thời gian còn lại"></span><?php endif; ?></div>
				<?php if ( $p['lines'] ) : ?>
					<ol><?php foreach ( $p['lines'] as $line ) : ?><li><span><?php echo tmt_promo_bold( $line ); // phpcs:ignore ?></span></li><?php endforeach; ?></ol>
				<?php endif; ?>
				<?php if ( $p['link'] ) : ?><a<?php echo $href; // phpcs:ignore ?> class="sf-promo-more"><?php echo esc_html( $p['link_text'] ? $p['link_text'] : 'Xem điều kiện áp dụng' ); ?></a><?php endif; ?>
			</div>
		<?php elseif ( 'strip' === $p['type'] ) : ?>
			<<?php echo $tag . $href; // phpcs:ignore ?> class="<?php echo esc_attr( $cls ); ?>" style="<?php echo esc_attr( $p['vars'] ); ?>"<?php echo $cd; // phpcs:ignore ?>>
				<i class="<?php echo esc_attr( $p['icon'] ); ?> ico" aria-hidden="true"></i>
				<span class="sf-promo-txt"><b><?php echo esc_html( $p['title'] ); ?></b><?php if ( $p['text'] ) : ?><small><?php echo esc_html( $p['text'] ); ?></small><?php endif; ?></span>
				<?php if ( $cd ) : ?>
					<span class="sf-promo-cd" data-promo-cd aria-label="Thời gian còn lại"><span>Kết thúc sau</span></span>
				<?php elseif ( $p['link'] ) : ?>
					<span class="sf-promo-go"><?php echo esc_html( $p['link_text'] ? $p['link_text'] : 'Xem ngay' ); ?> <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></span>
				<?php endif; ?>
			</<?php echo $tag; // phpcs:ignore ?>>
		<?php elseif ( 'badges' === $p['type'] ) : ?>
			<<?php echo $tag . $href; // phpcs:ignore ?> class="<?php echo esc_attr( $cls ); ?>" style="<?php echo esc_attr( $p['vars'] ); ?>">
				<?php foreach ( $p['lines'] ? $p['lines'] : array( $p['title'] ) as $line ) : ?><span><i class="<?php echo esc_attr( tmt_promo_chip_icon( $line ) ); ?>" aria-hidden="true"></i><span><?php echo tmt_promo_bold( $line ); // phpcs:ignore ?></span></span><?php endforeach; ?>
			</<?php echo $tag; // phpcs:ignore ?>>
		<?php elseif ( 'image' === $p['type'] ) : ?>
			<<?php echo $tag . $href; // phpcs:ignore ?> class="<?php echo esc_attr( $cls ); ?>" style="<?php echo esc_attr( $p['vars'] ); ?>">
				<img src="<?php echo esc_url( $p['image'] ); ?>" alt="<?php echo esc_attr( $p['title'] ); ?>" loading="lazy">
			</<?php echo $tag; // phpcs:ignore ?>>
		<?php elseif ( 'coupon' === $p['type'] ) : ?>
			<div class="<?php echo esc_attr( $cls ); ?>" style="<?php echo esc_attr( $p['vars'] ); ?>">
				<i class="<?php echo esc_attr( $p['icon'] ); ?> ico" aria-hidden="true"></i>
				<span class="sf-promo-txt"><span class="code"><?php echo esc_html( $p['code'] ); ?></span><?php if ( $p['title'] || $p['text'] ) : ?><small><?php echo esc_html( trim( $p['title'] . ( $p['title'] && $p['text'] ? ' · ' : '' ) . $p['text'] ) ); ?></small><?php endif; ?></span>
				<button type="button" class="sf-promo-copy" data-promo-copy="<?php echo esc_attr( $p['code'] ); ?>">Chép mã</button>
			</div>
		<?php endif; ?>
	<?php endforeach; ?>
</div>
