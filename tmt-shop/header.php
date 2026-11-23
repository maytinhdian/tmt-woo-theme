<?php
/**
 * Đầu trang: thanh trên cùng 3 vùng, logo + tìm kiếm + tài khoản + giỏ, menu chính + mega menu.
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;
$tmt_fx = tmt_effects();
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<?php $tmt_cfx = function_exists( 'tmt_cart_fx' ) ? tmt_cart_fx() : array( 'on' => false ); ?>
<body <?php body_class(); ?> style="--fx-k: <?php echo esc_attr( $tmt_fx['k'] ); ?><?php echo $tmt_cfx['on'] ? '; --atc-ms: ' . (int) $tmt_cfx['ms'] . 'ms' : ''; ?>"<?php echo 'none' !== $tmt_fx['cursor'] ? ' data-fx-cursor="' . esc_attr( $tmt_fx['cursor'] ) . '"' : ''; ?><?php echo $tmt_cfx['on'] ? tmt_cart_fx_body_attrs() : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
<?php wp_body_open(); ?>
<a class="visually-hidden-focusable sf-skip" href="#main">Bỏ qua, tới nội dung</a>
<?php get_template_part( 'template-parts/header/topbar' ); ?>
<header class="sf-header" id="sf-header">
	<div class="sf-wrap sf-head-row">
		<?php $tmt_bfx = tmt_brand_fx(); ?>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="sf-brand sf-bfx-run-<?php echo esc_attr( $tmt_bfx['run'] ); ?> sf-bfx-c-<?php echo esc_attr( $tmt_bfx['color'] ); ?>" rel="home"<?php echo 'none' !== $tmt_bfx['fx'] ? ' style="--bfx-k: ' . esc_attr( $tmt_bfx['k'] ) . '" data-bfx-run="' . esc_attr( $tmt_bfx['run'] ) . '"' : ''; ?>>
			<img src="<?php echo esc_url( tmt_logo_url() ); ?>" alt="<?php echo esc_attr( 'Logo ' . tmt_opt( 'shop.brand' ) ); ?>" height="44">
			<span class="sf-brand-t d-none d-sm-block"><b><?php echo tmt_brand_text( tmt_opt( 'shop.brand' ), $tmt_bfx['name'], $tmt_bfx ); // phpcs:ignore WordPress.Security.EscapeOutput ?></b><?php if ( tmt_opt( 'shop.brand_by' ) ) : ?><br><small><?php echo tmt_brand_text( 'by ' . tmt_opt( 'shop.brand_by' ), $tmt_bfx['by'], $tmt_bfx ); // phpcs:ignore WordPress.Security.EscapeOutput ?></small><?php endif; ?></span>
		</a>
		<?php get_template_part( 'template-parts/header/search' ); ?>
		<div class="sf-head-actions">
			<?php if ( class_exists( 'WooCommerce' ) ) : ?>
				<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="sf-head-link" aria-label="Tài khoản">
					<?php echo tmt_icon( 'account_circle' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span class="d-none d-lg-inline"><?php echo is_user_logged_in() ? esc_html( wp_get_current_user()->display_name ) : 'Đăng nhập'; ?></span>
				</a>
				<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="sf-head-link sf-cart-link" aria-label="Giỏ hàng" data-sf-cart-link>
					<?php echo tmt_icon( 'shopping_cart' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php tmt_cart_count_badge(); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
	<?php get_template_part( 'template-parts/header/nav' ); ?>
</header>
<main id="main" class="sf-main">
