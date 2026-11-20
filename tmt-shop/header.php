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
<body <?php body_class(); ?> style="--fx-k: <?php echo esc_attr( $tmt_fx['k'] ); ?>">
<?php wp_body_open(); ?>
<a class="visually-hidden-focusable sf-skip" href="#main">Bỏ qua, tới nội dung</a>
<?php get_template_part( 'template-parts/header/topbar' ); ?>
<header class="sf-header" id="sf-header">
	<div class="sf-wrap sf-head-row">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="sf-brand" rel="home">
			<img src="<?php echo esc_url( tmt_logo_url() ); ?>" alt="<?php echo esc_attr( 'Logo ' . tmt_opt( 'shop.brand' ) ); ?>" height="44">
			<span class="sf-brand-t d-none d-sm-block"><b><?php echo esc_html( tmt_opt( 'shop.brand' ) ); ?></b><?php if ( tmt_opt( 'shop.brand_by' ) ) : ?><br><small>by <?php echo esc_html( tmt_opt( 'shop.brand_by' ) ); ?></small><?php endif; ?></span>
		</a>
		<?php get_template_part( 'template-parts/header/search' ); ?>
		<div class="sf-head-actions">
			<?php if ( class_exists( 'WooCommerce' ) ) : ?>
				<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="sf-head-link" aria-label="Tài khoản">
					<?php echo tmt_icon( 'account_circle' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span class="d-none d-lg-inline"><?php echo is_user_logged_in() ? esc_html( wp_get_current_user()->display_name ) : 'Đăng nhập'; ?></span>
				</a>
				<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="sf-head-link sf-cart-link" aria-label="Giỏ hàng">
					<?php echo tmt_icon( 'shopping_cart' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php tmt_cart_count_badge(); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
	<?php get_template_part( 'template-parts/header/nav' ); ?>
</header>
<main id="main" class="sf-main">
