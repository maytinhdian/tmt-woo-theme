<?php
/**
 * Plugin Name:       TMT Shop Tools
 * Description:       Trang "Xây dựng cấu hình" cho web bán máy tính: khách chọn từng linh kiện, web tự kiểm tra lắp chung được không (socket, RAM, khổ main, nguồn, kích thước vỏ...), báo tổng tiền, thêm cả bộ vào giỏ. Kèm trang Giải pháp, Phần mềm, yêu cầu báo giá / khảo sát / dùng thử, dữ liệu mẫu. Đi kèm theme TMT Shop.
 * Version:           1.3.2
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            TMT Innovative Solutions Co.,ltd
 * License:           GPL-2.0-or-later
 * Text Domain:       tmt-shop-tools
 *
 * @package TMT_Shop_Tools
 */

defined( 'ABSPATH' ) || exit;

define( 'TMT_TOOLS_VERSION', '1.3.2' );
define( 'TMT_TOOLS_DIR', __DIR__ );
define( 'TMT_TOOLS_URL', plugin_dir_url( __FILE__ ) );

require_once TMT_TOOLS_DIR . '/includes/slots.php';

add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-warning"><p><b>TMT Shop Tools</b> cần plugin WooCommerce đang bật.</p></div>';
				}
			);
			return;
		}
		require_once TMT_TOOLS_DIR . '/includes/catalog.php';
		require_once TMT_TOOLS_DIR . '/includes/checker.php';
		require_once TMT_TOOLS_DIR . '/includes/builder.php';
		require_once TMT_TOOLS_DIR . '/includes/requests.php';
		require_once TMT_TOOLS_DIR . '/includes/landing.php';
		require_once TMT_TOOLS_DIR . '/includes/discontinued.php';
		require_once TMT_TOOLS_DIR . '/includes/promotions.php';
		require_once TMT_TOOLS_DIR . '/includes/gifts.php';
		require_once TMT_TOOLS_DIR . '/includes/intro.php';
		if ( is_admin() ) {
			require_once TMT_TOOLS_DIR . '/includes/admin.php';
			require_once TMT_TOOLS_DIR . '/includes/demo.php';
		}
	}
);

/** Kích hoạt: tạo trang "Xây dựng cấu hình" một lần. */
register_activation_hook(
	__FILE__,
	function () {
		$id = (int) get_option( 'tmt_pcb_page_id' );
		if ( $id && get_post( $id ) && 'trash' !== get_post_status( $id ) ) {
			return;
		}
		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Xây dựng cấu hình',
				'post_name'    => 'xay-dung-cau-hinh',
				'post_content' => '<!-- wp:shortcode -->[tmt_len_cau_hinh]<!-- /wp:shortcode -->',
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			update_option( 'tmt_pcb_page_id', $id );
		}
	}
);
