<?php
/**
 * TMT Shop: theme WooCommerce cho tiệm máy tính.
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;

define( 'TMT_SHOP_VERSION', '0.1.0' );
define( 'TMT_SHOP_DIR', get_template_directory() );
define( 'TMT_SHOP_URI', get_template_directory_uri() );

require TMT_SHOP_DIR . '/inc/options.php';
require TMT_SHOP_DIR . '/inc/setup.php';
require TMT_SHOP_DIR . '/inc/template-tags.php';
require TMT_SHOP_DIR . '/inc/menu.php';
require TMT_SHOP_DIR . '/inc/home.php';
require TMT_SHOP_DIR . '/inc/customizer.php';

if ( class_exists( 'WooCommerce' ) ) {
	require TMT_SHOP_DIR . '/inc/woocommerce.php';
	require TMT_SHOP_DIR . '/inc/filters.php';
}
