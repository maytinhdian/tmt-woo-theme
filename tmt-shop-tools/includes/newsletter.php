<?php
/**
 * Ô "Nhận tin khuyến mãi" có sẵn ở chân trang (không cần plugin gửi mail riêng): lưu email khách đăng ký,
 * xem + tải CSV ở WooCommerce → Email nhận tin. Port từ NewsletterController của app.
 *
 * @package TMT_Shop_Tools
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	function () {
		register_post_type(
			'tmt_subscriber',
			array(
				'labels'       => array(
					'name'          => 'Email nhận tin',
					'singular_name' => 'Email nhận tin',
					'search_items'  => 'Tìm email',
					'not_found'     => 'Chưa có ai đăng ký nhận tin.',
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => 'woocommerce',
				'supports'     => array( 'title' ),
				'capabilities' => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap' => true,
				'capability_type' => 'post',
			)
		);
	}
);

/** Gửi tới đâu (theme dùng làm action của form). */
function tmt_nl_action() {
	return admin_url( 'admin-post.php' );
}

/** Kết quả vừa gửi khi trình duyệt gửi form thường (?nhan-tin=ok|loi). '' = chưa gửi. */
function tmt_nl_result() {
	$r = isset( $_GET['nhan-tin'] ) ? sanitize_key( $_GET['nhan-tin'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	return in_array( $r, array( 'ok', 'loi', 'nhieu' ), true ) ? $r : '';
}

function tmt_nl_handle() {
	// phpcs:disable WordPress.Security.NonceVerification -- form công khai ở chân trang (trang có thể được cache), chống spam bằng ô bẫy + giới hạn lần gửi.
	$post  = wp_unslash( $_POST );
	$ajax  = isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && 'xmlhttprequest' === strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ) );
	$back  = isset( $post['back'] ) ? wp_validate_redirect( esc_url_raw( $post['back'] ), home_url( '/' ) ) : home_url( '/' );
	$back  = remove_query_arg( 'nhan-tin', $back );
	$email = isset( $post['email'] ) ? sanitize_email( $post['email'] ) : '';
	$done  = function ( $code, $msg = '' ) use ( $ajax, $back ) {
		if ( $ajax ) {
			wp_send_json( 'ok' === $code ? array( 'ok' => true ) : array( 'ok' => false, 'message' => $msg ), 'ok' === $code ? 200 : 422 );
		}
		wp_safe_redirect( add_query_arg( 'nhan-tin', $code, $back ) . '#nhan-tin' );
		exit;
	};
	if ( ! empty( $post['website'] ) ) {
		$done( 'ok' ); // Máy spam: trả như thường, không lưu.
	}
	if ( ! is_email( $email ) || strlen( $email ) > 191 ) {
		$done( 'loi', 'Email chưa đúng, anh/chị kiểm tra lại giúp tiệm.' );
	}
	$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$key   = 'tmt_nl_' . md5( $ip );
	$tries = (int) get_transient( $key );
	if ( $tries >= 5 ) {
		$done( 'nhieu', 'Anh/chị gửi nhiều lần quá, đợi ít phút rồi thử lại nhé.' );
	}
	set_transient( $key, $tries + 1, 10 * MINUTE_IN_SECONDS );
	// phpcs:enable

	$email = strtolower( $email );
	$found = get_posts( array( 'post_type' => 'tmt_subscriber', 'post_status' => 'any', 'title' => $email, 'numberposts' => 1, 'fields' => 'ids' ) );
	if ( ! $found ) {
		wp_insert_post( array( 'post_type' => 'tmt_subscriber', 'post_status' => 'publish', 'post_title' => $email ) );
	}
	$done( 'ok' );
}
add_action( 'admin_post_tmt_nl', 'tmt_nl_handle' );
add_action( 'admin_post_nopriv_tmt_nl', 'tmt_nl_handle' );

/* ---------- Quản trị: cột ngày + nút tải CSV ---------- */

add_filter(
	'manage_tmt_subscriber_posts_columns',
	function () {
		return array( 'cb' => '<input type="checkbox">', 'title' => 'Email', 'date' => 'Đăng ký lúc' );
	}
);

add_filter(
	'post_row_actions',
	function ( $actions, $post ) {
		return 'tmt_subscriber' === $post->post_type ? array_intersect_key( $actions, array( 'trash' => 1, 'delete' => 1, 'untrash' => 1 ) ) : $actions;
	},
	10,
	2
);

add_filter(
	'views_edit-tmt_subscriber',
	function ( $views ) {
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=tmt_nl_csv' ), 'tmt_nl_csv' );
		echo '<p><a class="button button-primary" href="' . esc_url( $url ) . '">Tải danh sách (CSV, mở bằng Excel)</a></p>';
		return $views;
	}
);

add_action(
	'admin_post_tmt_nl_csv',
	function () {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'Không có quyền.' );
		}
		check_admin_referer( 'tmt_nl_csv' );
		$ids = get_posts( array( 'post_type' => 'tmt_subscriber', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'date', 'order' => 'ASC' ) );
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="email-nhan-tin-' . wp_date( 'Y-m-d' ) . '.csv"' );
		$out = fopen( 'php://output', 'w' );
		fwrite( $out, "\xEF\xBB\xBF" ); // BOM để Excel đọc đúng tiếng Việt.
		fputcsv( $out, array( 'Email', 'Đăng ký lúc' ) );
		foreach ( $ids as $p ) {
			fputcsv( $out, array( $p->post_title, get_the_date( 'd/m/Y H:i', $p ) ) );
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}
);
