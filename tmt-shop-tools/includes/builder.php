<?php
/**
 * Trang Xây dựng cấu hình: mã ngắn [tmt_len_cau_hinh], API kiểm tra, thêm cả bộ vào giỏ, cấu hình mẫu, bản in A5.
 * Mọi luật chạy ở máy chủ (checker.php); trình duyệt hỏi lại mỗi lần đổi linh kiện, giống app quản lý.
 *
 * @package TMT_Shop_Tools
 */

defined( 'ABSPATH' ) || exit;

/** Trang có mã ngắn Xây dựng cấu hình (tạo khi bật plugin), 0 khi chưa có. */
function tmt_pcb_page_id() {
	$id = (int) get_option( 'tmt_pcb_page_id' );
	return $id && 'publish' === get_post_status( $id ) ? $id : 0;
}

function tmt_pcb_url() {
	$id = tmt_pcb_page_id();
	return $id ? get_permalink( $id ) : '';
}

/** Theme TMT Shop hiện nút "Xây dựng cấu hình" (trang chủ, giỏ trống) khi có link này. */
add_filter(
	'tmt_pc_builder_url',
	function ( $url ) {
		$own = tmt_pcb_url();
		return $own ? $own : $url;
	}
);

/** Trang Xây dựng cấu hình dùng bề rộng đầy đủ của theme. */
add_filter(
	'tmt_page_wide',
	function ( $wide ) {
		return $wide || ( is_page() && has_shortcode( (string) get_post_field( 'post_content', get_queried_object_id() ), 'tmt_len_cau_hinh' ) );
	}
);

/** Số Zalo của tiệm: ô ở trang cài đặt, không có thì lấy số trong Tùy biến của theme TMT Shop. */
function tmt_pcb_zalo_url() {
	$own = preg_replace( '/[^\d]/', '', (string) get_option( 'tmt_pcb_zalo' ) );
	if ( strlen( $own ) >= 8 ) {
		return 'https://zalo.me/' . $own;
	}
	if ( function_exists( 'tmt_zalo_url' ) && function_exists( 'tmt_opt' ) ) {
		$url = tmt_zalo_url( (string) tmt_opt( 'contact.zalo' ) );
		return $url ? $url : '';
	}
	return '';
}

/**
 * Cấu hình mẫu: [{id, name, items: {product_id: qty}}]. Chủ tiệm lưu ngay trên trang (nút chỉ hiện khi đăng nhập quản trị).
 *
 * @return list<array{id: int, name: string, items: array<int, int>}>
 */
function tmt_pcb_presets() {
	$list = get_option( 'tmt_pcb_presets', array() );
	return is_array( $list ) ? array_values( $list ) : array();
}

/** Mẫu gửi xuống trình duyệt: chỉ giữ món còn trong danh sách chọn được; mẫu rỗng thì bỏ. */
function tmt_pcb_presets_for_client() {
	$parts = tmt_pcb_parts();
	$out   = array();
	foreach ( tmt_pcb_presets() as $p ) {
		$items = array();
		foreach ( (array) $p['items'] as $id => $qty ) {
			if ( isset( $parts[ (int) $id ] ) ) {
				$items[ (int) $id ] = (int) $qty;
			}
		}
		if ( $items ) {
			$out[] = array( 'id' => (int) $p['id'], 'name' => (string) $p['name'], 'items' => (object) $items );
		}
	}
	return $out;
}

function tmt_pcb_can_manage() {
	return current_user_can( 'manage_woocommerce' );
}

/** API cho trang: kiểm tra cấu hình (ai cũng gọi được, chỉ đọc), lưu / xóa mẫu (quản trị). */
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'tmt/v1',
			'/cau-hinh/kiem-tra',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => function ( WP_REST_Request $req ) {
					$items = array();
					foreach ( array_slice( (array) $req->get_param( 'items' ), 0, 20 ) as $row ) {
						if ( is_array( $row ) && isset( $row['id'] ) ) {
							$items[ absint( $row['id'] ) ] = max( 1, absint( isset( $row['qty'] ) ? $row['qty'] : 1 ) );
						}
					}
					return rest_ensure_response( tmt_pcb_evaluate( $items ) );
				},
			)
		);
		register_rest_route(
			'tmt/v1',
			'/cau-hinh/mau',
			array(
				array(
					'methods'             => 'POST',
					'permission_callback' => 'tmt_pcb_can_manage',
					'callback'            => function ( WP_REST_Request $req ) {
						$name  = sanitize_text_field( (string) $req->get_param( 'name' ) );
						$build = tmt_pcb_build( tmt_pcb_parse_code( (string) $req->get_param( 'code' ) ) );
						if ( '' === $name || mb_strlen( $name ) > 100 ) {
							return new WP_Error( 'tmt_pcb_name', 'Nhập tên mẫu (tối đa 100 ký tự).', array( 'status' => 422 ) );
						}
						if ( ! $build ) {
							return new WP_Error( 'tmt_pcb_empty', 'Chọn linh kiện trước khi lưu mẫu.', array( 'status' => 422 ) );
						}
						$items = array();
						foreach ( $build as $i ) {
							$items[ $i['part']['id'] ] = $i['qty'];
						}
						$list = tmt_pcb_presets();
						$hit  = false;
						foreach ( $list as &$p ) {
							if ( 0 === strcasecmp( $p['name'], $name ) ) {
								$p['items'] = $items;
								$hit        = true;
							}
						}
						unset( $p );
						if ( ! $hit ) {
							$max    = $list ? max( array_column( $list, 'id' ) ) : 0;
							$list[] = array( 'id' => $max + 1, 'name' => $name, 'items' => $items );
						}
						update_option( 'tmt_pcb_presets', $list, false );
						return rest_ensure_response( array( 'presets' => tmt_pcb_presets_for_client(), 'message' => ( $hit ? 'Đã cập nhật' : 'Đã lưu' ) . ' mẫu "' . $name . '".' ) );
					},
				),
			)
		);
		register_rest_route(
			'tmt/v1',
			'/cau-hinh/mau/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'permission_callback' => 'tmt_pcb_can_manage',
				'callback'            => function ( WP_REST_Request $req ) {
					$id   = (int) $req['id'];
					$list = array_values(
						array_filter(
							tmt_pcb_presets(),
							function ( $p ) use ( $id ) {
								return (int) $p['id'] !== $id;
							}
						)
					);
					update_option( 'tmt_pcb_presets', $list, false );
					return rest_ensure_response( array( 'presets' => tmt_pcb_presets_for_client(), 'message' => 'Đã xóa mẫu.' ) );
				},
			)
		);
	}
);

/**
 * Thêm cả bộ vào giỏ (form POST từ trang, có nonce). Kiểm lại luật ở máy chủ: cấu hình có lỗi đỏ không vào giỏ.
 * Món chưa có giá hoặc tạm hết không vào giỏ, báo rõ cho khách.
 */
add_action(
	'wp_loaded',
	function () {
		if ( empty( $_POST['tmt_pcb_cart'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$back = wp_get_referer() ? wp_get_referer() : tmt_pcb_url();
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'tmt_pcb_cart' ) ) {
			wc_add_notice( 'Phiên làm việc đã hết hạn, bấm Thêm cả bộ vào giỏ lại.', 'error' );
			wp_safe_redirect( $back );
			exit;
		}
		$items = tmt_pcb_parse_code( sanitize_text_field( wp_unslash( isset( $_POST['code'] ) ? $_POST['code'] : '' ) ) );
		$result = tmt_pcb_evaluate( $items );
		if ( $result['errors'] || ! $result['count'] ) {
			wc_add_notice( 'Cấu hình chưa đặt được: ' . implode( '; ', $result['blocks'] ) . '. Sửa các dòng đỏ hoặc nhắn tiệm tư vấn.', 'error' );
			wp_safe_redirect( $back );
			exit;
		}
		$added   = 0;
		$skipped = 0;
		$short   = array();
		foreach ( tmt_pcb_build( $items ) as $i ) {
			$product = wc_get_product( $i['part']['id'] );
			if ( ! $product || ! $i['part']['buy'] ) {
				++$skipped;
				continue;
			}
			// Không thêm quá số còn bán được (WooCommerce sẽ báo lỗi tiếng Anh): thêm phần còn, báo phần thiếu.
			$qty   = $i['qty'];
			$avail = isset( $i['part']['avail'] ) ? $i['part']['avail'] : null;
			if ( null !== $avail ) {
				$in_cart = 0;
				foreach ( WC()->cart->get_cart() as $line ) {
					if ( (int) ( $line['variation_id'] ? $line['variation_id'] : $line['product_id'] ) === $product->get_id() ) {
						$in_cart += (int) $line['quantity'];
					}
				}
				$qty = min( $qty, max( 0, $avail - $in_cart ) );
				if ( $qty < $i['qty'] ) {
					$short[] = $i['part']['name'] . ' (thiếu ' . ( $i['qty'] - $qty ) . ')';
				}
				if ( $qty < 1 ) {
					continue;
				}
			}
			$ok = $product->is_type( 'variation' )
				? WC()->cart->add_to_cart( $product->get_parent_id(), $qty, $product->get_id(), $product->get_variation_attributes() )
				: WC()->cart->add_to_cart( $product->get_id(), $qty );
			$ok ? ++$added : ++$skipped;
		}
		if ( $added ) {
			$note  = $skipped ? " {$skipped} món chưa có giá hoặc tạm hết chưa vào giỏ, tiệm báo giá khi gọi xác nhận đơn." : '';
			$note .= $short ? ' Không đủ hàng: ' . implode( ', ', $short ) . ', tiệm đặt thêm và báo khi gọi xác nhận đơn.' : '';
			wc_add_notice( "Đã thêm {$added} linh kiện của cấu hình vào giỏ. Tiệm ráp sẵn và cài Windows miễn phí.{$note}", 'success' );
			wp_safe_redirect( wc_get_cart_url() );
		} else {
			wc_add_notice( 'Các linh kiện đã chọn chưa bán online được (chưa có giá hoặc tạm hết). Nhắn Zalo để tiệm báo giá nhé.', 'error' );
			wp_safe_redirect( $back );
		}
		exit;
	},
	20
);

/** Bản in A5 (?in-cau-hinh=12*2,15 trên trang Xây dựng cấu hình). */
add_action(
	'template_redirect',
	function () {
		if ( ! isset( $_GET['in-cau-hinh'] ) || ! tmt_pcb_page_id() || ! is_page( tmt_pcb_page_id() ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$code  = sanitize_text_field( wp_unslash( $_GET['in-cau-hinh'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$build = tmt_pcb_build( tmt_pcb_parse_code( $code ) );
		nocache_headers();
		header( 'X-Robots-Tag: noindex' );
		include TMT_TOOLS_DIR . '/templates/print.php';
		exit;
	}
);

/** Dữ liệu nhúng vào trang: khe, linh kiện (không có số tồn), mẫu, đường dẫn. */
function tmt_pcb_client_data() {
	$parts = array();
	foreach ( tmt_pcb_parts() as $p ) {
		unset( $p['specs'], $p['avail'] );
		$parts[] = $p;
	}
	$slots = array();
	foreach ( tmt_pcb_slots() as $k => $s ) {
		unset( $s['guess'] );
		$slots[ $k ] = $s;
	}
	return array(
		'slots'   => $slots,
		'parts'   => $parts,
		'presets' => tmt_pcb_presets_for_client(),
		'manage'  => tmt_pcb_can_manage(),
		'urls'    => array(
			'evaluate' => rest_url( 'tmt/v1/cau-hinh/kiem-tra' ),
			'presets'  => rest_url( 'tmt/v1/cau-hinh/mau' ),
			'page'     => tmt_pcb_url() ? tmt_pcb_url() : get_permalink(),
			'zalo'     => tmt_pcb_zalo_url(),
		),
		'nonce'   => wp_create_nonce( 'wp_rest' ),
	);
}

add_shortcode(
	'tmt_len_cau_hinh',
	function () {
		wp_enqueue_style( 'tmt-pcb', TMT_TOOLS_URL . 'assets/pc-builder.css', array(), TMT_TOOLS_VERSION );
		wp_enqueue_script( 'tmt-pcb', TMT_TOOLS_URL . 'assets/pc-builder.js', array(), TMT_TOOLS_VERSION, true );
		if ( ! wp_style_is( 'tmt-icons', 'enqueued' ) ) {
			wp_enqueue_style( 'tmt-icons', 'https://fonts.googleapis.com/icon?family=Material+Icons+Round', array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceVersion
		}
		ob_start();
		include TMT_TOOLS_DIR . '/templates/builder.php';
		return ob_get_clean();
	}
);
