<?php
/**
 * Yêu cầu khách gửi từ web: báo giá sản phẩm chưa có giá (YC), đặt lịch khảo sát giải pháp (KS), đăng ký dùng thử phần mềm (DT).
 * Lưu thành mục "Yêu cầu khách" trong trang quản trị (mã YC2611-0001...), gửi email cho tiệm, khách thấy mã vừa gửi.
 *
 * @package TMT_Shop_Tools
 */

defined( 'ABSPATH' ) || exit;

/** Loại yêu cầu: khóa => [tiền tố mã, tên]. */
function tmt_req_types() {
	return array(
		'bao-gia'  => array( 'YC', 'Báo giá sản phẩm' ),
		'khao-sat' => array( 'KS', 'Khảo sát giải pháp' ),
		'dung-thu' => array( 'DT', 'Dùng thử phần mềm' ),
	);
}

/** Tình trạng xử lý: khóa => tên. */
function tmt_req_statuses() {
	return array(
		'moi'          => 'Mới',
		'dang-lien-he' => 'Đang liên hệ',
		'da-bao-gia'   => 'Đã báo giá',
		'da-chot'      => 'Đã chốt',
		'khong-thanh'  => 'Không thành',
	);
}

add_action(
	'init',
	function () {
		$cap = 'manage_woocommerce';
		register_post_type(
			'tmt_request',
			array(
				'labels'       => array(
					'name'          => 'Yêu cầu khách',
					'singular_name' => 'Yêu cầu khách',
					'menu_name'     => 'Yêu cầu khách',
					'all_items'     => 'Tất cả yêu cầu',
					'edit_item'     => 'Xem yêu cầu',
					'search_items'  => 'Tìm yêu cầu',
					'not_found'     => 'Chưa có yêu cầu nào.',
				),
				'public'       => false,
				'show_ui'      => true,
				'menu_icon'    => 'dashicons-feedback',
				'menu_position' => 56,
				'supports'     => array( 'title' ),
				'map_meta_cap' => false,
				'capabilities' => array(
					'edit_post'          => $cap,
					'read_post'          => $cap,
					'delete_post'        => $cap,
					'edit_posts'         => $cap,
					'edit_others_posts'  => $cap,
					'publish_posts'      => $cap,
					'read_private_posts' => $cap,
					'delete_posts'       => $cap,
					'create_posts'       => 'do_not_allow',
				),
			)
		);
	}
);

/** Số điện thoại Việt Nam hợp lệ (0xxxxxxxxx hoặc +84xxxxxxxxx), trả về dạng chỉ số, sai thì chuỗi rỗng. */
function tmt_req_phone( $phone ) {
	$d = preg_replace( '/[^0-9+]/', '', (string) $phone );
	if ( 0 === strpos( $d, '+84' ) ) {
		$d = '0' . substr( $d, 3 );
	}
	return preg_match( '/^0\d{9,10}$/', $d ) ? $d : '';
}

/**
 * Tạo yêu cầu: đánh mã theo tháng, lưu, báo email cho tiệm.
 *
 * @param string $type  Khóa trong tmt_req_types().
 * @param array  $data  name, phone, item (tên món/gói), ref (id sản phẩm/trang), qty, note, extra (nhãn => giá trị).
 * @return array [id, mã]
 */
function tmt_req_create( $type, array $data ) {
	$types  = tmt_req_types();
	$ym     = wp_date( 'ym' );
	$key    = 'tmt_req_seq_' . $types[ $type ][0] . $ym; // Mỗi loại một dãy số theo tháng.
	$seq    = (int) get_option( $key ) + 1;
	update_option( $key, $seq, false );
	$number = $types[ $type ][0] . $ym . '-' . str_pad( (string) $seq, 4, '0', STR_PAD_LEFT );
	$id     = wp_insert_post(
		array(
			'post_type'   => 'tmt_request',
			'post_status' => 'publish',
			'post_title'  => $number . ' · ' . ( '' !== $data['name'] ? $data['name'] : $data['phone'] ),
		)
	);
	$meta = array(
		'_tmt_req_number' => $number,
		'_tmt_req_type'   => $type,
		'_tmt_req_status' => 'moi',
		'_tmt_req_name'   => $data['name'],
		'_tmt_req_phone'  => $data['phone'],
		'_tmt_req_item'   => $data['item'],
		'_tmt_req_ref'    => (int) $data['ref'],
		'_tmt_req_qty'    => (int) $data['qty'],
		'_tmt_req_note'   => $data['note'],
		'_tmt_req_extra'  => $data['extra'],
	);
	foreach ( $meta as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}

	$lines = array(
		'Mã: ' . $number,
		'Loại: ' . $types[ $type ][1],
		'Khách: ' . $data['name'],
		'Điện thoại: ' . $data['phone'],
		'Món / gói: ' . $data['item'] . ( $data['qty'] > 1 ? ' × ' . $data['qty'] : '' ),
	);
	foreach ( $data['extra'] as $label => $value ) {
		$lines[] = $label . ': ' . $value;
	}
	if ( '' !== $data['note'] ) {
		$lines[] = 'Ghi chú: ' . $data['note'];
	}
	$lines[] = '';
	$lines[] = 'Xem trong quản trị: ' . admin_url( 'post.php?post=' . $id . '&action=edit' );
	$to      = trim( (string) get_option( 'tmt_req_email' ) );
	wp_mail( is_email( $to ) ? $to : get_option( 'admin_email' ), '[' . $number . '] ' . $types[ $type ][1] . ': ' . $data['name'] . ' ' . $data['phone'], implode( "\n", $lines ) );

	return array( $id, $number );
}

/**
 * Nhận form (admin-post.php, action tmt_req), khách chưa đăng nhập cũng gửi được.
 * Chặn máy: ô bẫy "website" và tối đa 5 lần / 10 phút mỗi IP. Xong quay lại trang cũ kèm ?da-gui=MÃ.
 */
function tmt_req_handle() {
	// phpcs:disable WordPress.Security.NonceVerification -- form công khai (trang có thể được cache), chống spam bằng ô bẫy + giới hạn lần gửi.
	$post  = wp_unslash( $_POST );
	$type  = isset( $post['type'] ) ? sanitize_key( $post['type'] ) : '';
	$back  = isset( $post['back'] ) ? wp_validate_redirect( esc_url_raw( $post['back'] ), home_url( '/' ) ) : home_url( '/' );
	$back  = remove_query_arg( array( 'da-gui', 'yc-loi' ), $back );
	if ( isset( $post['goi'] ) && '' !== $post['goi'] ) {
		$back = add_query_arg( 'goi', absint( $post['goi'] ), $back ); // Báo lỗi / gửi xong vẫn giữ gói đã chọn.
	}
	$hash  = isset( $post['anchor'] ) ? '#' . sanitize_key( $post['anchor'] ) : '';
	$fail  = function ( $code ) use ( $back, $hash ) {
		wp_safe_redirect( add_query_arg( 'yc-loi', $code, $back ) . $hash );
		exit;
	};
	if ( ! isset( tmt_req_types()[ $type ] ) || ! empty( $post['website'] ) ) {
		$fail( 'gui' );
	}
	$phone = tmt_req_phone( isset( $post['phone'] ) ? $post['phone'] : '' );
	if ( '' === $phone ) {
		$fail( 'sdt' );
	}
	$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$key   = 'tmt_req_' . md5( $ip );
	$tries = (int) get_transient( $key );
	if ( $tries >= 5 ) {
		$fail( 'nhieu' );
	}
	set_transient( $key, $tries + 1, 10 * MINUTE_IN_SECONDS );

	$text = function ( $k, $len = 100 ) use ( $post ) {
		return isset( $post[ $k ] ) ? mb_substr( sanitize_text_field( $post[ $k ] ), 0, $len ) : '';
	};
	$data = array(
		'name'  => $text( 'name' ),
		'phone' => $phone,
		'item'  => '',
		'ref'   => isset( $post['ref'] ) ? absint( $post['ref'] ) : 0,
		'qty'   => isset( $post['qty'] ) ? max( 1, min( 999, absint( $post['qty'] ) ) ) : 1,
		'note'  => isset( $post['note'] ) ? mb_substr( sanitize_textarea_field( $post['note'] ), 0, 1000 ) : '',
		'extra' => array(),
	);

	if ( 'bao-gia' === $type ) {
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $data['ref'] ) : null;
		if ( ! $product || 'publish' !== $product->get_status() ) {
			$fail( 'gui' );
		}
		$data['item'] = $product->get_name() . ( $product->get_sku() ? ' (' . $product->get_sku() . ')' : '' );
	} else {
		$page = get_post( $data['ref'] );
		if ( ! $page || ! in_array( $page->post_type, array( 'tmt_solution', 'tmt_software' ), true ) || 'publish' !== $page->post_status ) {
			$fail( 'gui' );
		}
		$data['item'] = $page->post_title;
		$lp           = tmt_lp_get( $page->ID );
		$pick         = isset( $post['goi'] ) && '' !== $post['goi'] ? absint( $post['goi'] ) : -1;
		if ( $pick >= 0 && isset( $lp['packages'][ $pick ] ) ) {
			$data['item'] .= ' · ' . $lp['packages'][ $pick ]['name'];
		}
		foreach ( array( 'address' => 'Địa chỉ lắp', 'kind' => 'Lắp cho', 'staff' => 'Số nhân viên', 'answer' => ! empty( $lp['form_question'] ) ? $lp['form_question'] : 'Trả lời' ) as $k => $label ) {
			$v = $text( $k, 255 );
			if ( '' !== $v ) {
				$data['extra'][ $label ] = $v;
			}
		}
	}
	// phpcs:enable

	list( , $number ) = tmt_req_create( $type, $data );
	wp_safe_redirect( add_query_arg( 'da-gui', rawurlencode( $number ), $back ) . $hash );
	exit;
}
add_action( 'admin_post_tmt_req', 'tmt_req_handle' );
add_action( 'admin_post_nopriv_tmt_req', 'tmt_req_handle' );

/** Mã vừa gửi (?da-gui=), chỉ nhận đúng dạng mã để khỏi in chữ lạ lên trang. */
function tmt_req_sent() {
	$n = isset( $_GET['da-gui'] ) ? sanitize_text_field( wp_unslash( $_GET['da-gui'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	return preg_match( '/^(YC|KS|DT)\d{4}-\d{4,}$/', $n ) ? $n : '';
}

/** Lỗi khi gửi (?yc-loi=) thành câu cho khách. */
function tmt_req_error() {
	$c   = isset( $_GET['yc-loi'] ) ? sanitize_key( $_GET['yc-loi'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$map = array(
		'sdt'   => 'Số điện thoại chưa đúng, anh chị kiểm tra lại giúp tiệm.',
		'nhieu' => 'Anh chị gửi nhiều lần quá, đợi ít phút rồi gửi lại, hoặc gọi tiệm nhé.',
		'gui'   => 'Chưa gửi được, anh chị thử lại hoặc gọi tiệm nhé.',
	);
	return isset( $map[ $c ] ) ? $map[ $c ] : '';
}

/** Các ô ẩn chung của form yêu cầu. Gửi xong quay về trang của sản phẩm / giải pháp $ref. */
function tmt_req_hidden( $type, $ref, $anchor ) {
	$back = get_permalink( $ref );
	echo '<input type="hidden" name="action" value="tmt_req">';
	echo '<input type="hidden" name="type" value="' . esc_attr( $type ) . '">';
	echo '<input type="hidden" name="ref" value="' . (int) $ref . '">';
	echo '<input type="hidden" name="back" value="' . esc_url( $back ) . '">';
	echo '<input type="hidden" name="anchor" value="' . esc_attr( $anchor ) . '">';
	echo '<label class="tmt-hp" aria-hidden="true">Website<input name="website" tabindex="-1" autocomplete="off"></label>';
}

/* ---------- Nút + hộp "Yêu cầu báo giá" ở trang sản phẩm chưa có giá ---------- */

/** Sản phẩm phải hỏi giá: chưa nhập giá bán (giá để trống). Có giá thì luôn vào giỏ, kể cả tạm hết hàng. */
function tmt_req_needs_quote( $product ) {
	return $product instanceof WC_Product && ! $product->is_type( 'variable' ) && '' === $product->get_price();
}

/** In nút + hộp báo giá (theme gọi ở trang sản phẩm). Hộp tự mở khi trang có ?bao-gia=1, vừa gửi xong hoặc vừa báo lỗi. */
function tmt_req_quote_form( WC_Product $product ) {
	wp_enqueue_style( 'tmt-requests', TMT_TOOLS_URL . 'assets/requests.css', array(), TMT_TOOLS_VERSION );
	wp_enqueue_script( 'tmt-requests', TMT_TOOLS_URL . 'assets/requests.js', array(), TMT_TOOLS_VERSION, true );
	$sent  = tmt_req_sent();
	$error = tmt_req_error();
	$open  = $sent || $error || ! empty( $_GET['bao-gia'] ); // phpcs:ignore WordPress.Security.NonceVerification
	$user  = wp_get_current_user();
	$phone = $user->ID ? get_user_meta( $user->ID, 'billing_phone', true ) : '';
	?>
	<div class="tmt-quote" id="bao-gia">
		<button type="button" class="sf-btn tmt-quote-open" data-quote-open><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i>Yêu cầu báo giá</button>
	</div>
	<dialog class="tmt-quote-dlg" id="tmt-quote-dlg" aria-labelledby="tmt-quote-title"<?php echo $open ? ' data-open' : ''; ?>>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="tmt-quote-form">
			<div class="tmt-quote-head">
				<h2 id="tmt-quote-title"><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i>Yêu cầu báo giá</h2>
				<button type="button" class="tmt-quote-x" data-quote-close aria-label="Đóng"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
			</div>
			<p class="tmt-quote-prod"><?php echo esc_html( $product->get_name() ); ?></p>
			<?php if ( $sent ) : ?>
				<p class="tmt-ok" role="status"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Tiệm đã nhận yêu cầu <b><?php echo esc_html( $sent ); ?></b>. Tiệm gọi lại báo giá trong giờ làm việc.</p>
			<?php else : ?>
				<?php if ( $error ) : ?><p class="tmt-err" role="alert"><?php echo esc_html( $error ); ?></p><?php endif; ?>
				<?php tmt_req_hidden( 'bao-gia', $product->get_id(), 'bao-gia' ); ?>
				<label for="q-name">Họ tên</label>
				<input id="q-name" name="name" required maxlength="100" autocomplete="name" value="<?php echo esc_attr( $user->ID ? $user->display_name : '' ); ?>">
				<div class="tmt-quote-row">
					<div>
						<label for="q-phone">Số điện thoại / Zalo</label>
						<input id="q-phone" name="phone" required inputmode="tel" maxlength="20" autocomplete="tel" value="<?php echo esc_attr( $phone ); ?>">
					</div>
					<div>
						<label for="q-qty">Số lượng</label>
						<input id="q-qty" name="qty" type="number" value="1" min="1" max="999" required inputmode="numeric">
					</div>
				</div>
				<label for="q-note">Ghi chú <span>(không bắt buộc)</span></label>
				<textarea id="q-note" name="note" rows="3" maxlength="1000" placeholder="Ví dụ: cần xuất hóa đơn, giao tận nơi..."></textarea>
				<button type="submit" class="sf-btn blue"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i>Gửi yêu cầu</button>
			<?php endif; ?>
		</form>
	</dialog>
	<?php
}

/* ---------- Trang quản trị ---------- */

add_filter(
	'manage_tmt_request_posts_columns',
	function () {
		return array(
			'cb'       => '<input type="checkbox">',
			'title'    => 'Mã · Khách',
			'tmt_type' => 'Loại',
			'tmt_phone' => 'Điện thoại',
			'tmt_item' => 'Món / gói',
			'tmt_status' => 'Tình trạng',
			'date'     => 'Gửi lúc',
		);
	}
);

add_action(
	'manage_tmt_request_posts_custom_column',
	function ( $col, $id ) {
		$types    = tmt_req_types();
		$statuses = tmt_req_statuses();
		switch ( $col ) {
			case 'tmt_type':
				$t = get_post_meta( $id, '_tmt_req_type', true );
				echo esc_html( isset( $types[ $t ] ) ? $types[ $t ][1] : $t );
				break;
			case 'tmt_phone':
				$p = get_post_meta( $id, '_tmt_req_phone', true );
				echo '<a href="tel:' . esc_attr( $p ) . '">' . esc_html( $p ) . '</a>';
				break;
			case 'tmt_item':
				$q = (int) get_post_meta( $id, '_tmt_req_qty', true );
				echo esc_html( get_post_meta( $id, '_tmt_req_item', true ) . ( $q > 1 ? ' × ' . $q : '' ) );
				break;
			case 'tmt_status':
				$s = get_post_meta( $id, '_tmt_req_status', true );
				echo '<span class="tmt-req-st st-' . esc_attr( $s ) . '">' . esc_html( isset( $statuses[ $s ] ) ? $statuses[ $s ] : $s ) . '</span>';
				break;
		}
	},
	10,
	2
);

/** Lọc theo loại: tab trên đầu danh sách. */
add_filter(
	'views_edit-tmt_request',
	function ( $views ) {
		$cur = isset( $_GET['tmt_type'] ) ? sanitize_key( $_GET['tmt_type'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$out = array( 'all' => '<a href="' . esc_url( admin_url( 'edit.php?post_type=tmt_request' ) ) . '"' . ( '' === $cur ? ' class="current"' : '' ) . '>Tất cả</a>' );
		foreach ( tmt_req_types() as $k => $t ) {
			$out[ $k ] = '<a href="' . esc_url( admin_url( 'edit.php?post_type=tmt_request&tmt_type=' . $k ) ) . '"' . ( $cur === $k ? ' class="current"' : '' ) . '>' . esc_html( $t[1] ) . '</a>';
		}
		if ( isset( $views['trash'] ) ) {
			$out['trash'] = $views['trash'];
		}
		return $out;
	}
);

add_action(
	'pre_get_posts',
	function ( WP_Query $q ) {
		if ( ! is_admin() || ! $q->is_main_query() || 'tmt_request' !== $q->get( 'post_type' ) ) {
			return;
		}
		$t = isset( $_GET['tmt_type'] ) ? sanitize_key( $_GET['tmt_type'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( isset( tmt_req_types()[ $t ] ) ) {
			$q->set( 'meta_query', array( array( 'key' => '_tmt_req_type', 'value' => $t ) ) );
		}
		$s = $q->get( 's' );
		if ( $s ) {
			// Tìm cả theo số điện thoại.
			$q->set( 's', '' );
			$q->set(
				'meta_query',
				array(
					'relation' => 'OR',
					array( 'key' => '_tmt_req_phone', 'value' => preg_replace( '/\D/', '', $s ), 'compare' => 'LIKE' ),
					array( 'key' => '_tmt_req_number', 'value' => $s, 'compare' => 'LIKE' ),
					array( 'key' => '_tmt_req_name', 'value' => $s, 'compare' => 'LIKE' ),
				)
			);
		}
	}
);

/** Số yêu cầu mới cạnh tên menu. */
add_action(
	'admin_menu',
	function () {
		global $menu;
		$n = count(
			get_posts(
				array(
					'post_type'   => 'tmt_request',
					'numberposts' => 99,
					'fields'      => 'ids',
					'meta_key'    => '_tmt_req_status', // phpcs:ignore WordPress.DB.SlowDBQuery
					'meta_value'  => 'moi', // phpcs:ignore WordPress.DB.SlowDBQuery
				)
			)
		);
		if ( ! $n || ! is_array( $menu ) ) {
			return;
		}
		foreach ( $menu as $i => $item ) {
			if ( 'edit.php?post_type=tmt_request' === $item[2] ) {
				$menu[ $i ][0] .= ' <span class="awaiting-mod count-' . (int) $n . '"><span>' . (int) $n . '</span></span>'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
			}
		}
	},
	99
);

add_action(
	'add_meta_boxes_tmt_request',
	function () {
		remove_meta_box( 'submitdiv', 'tmt_request', 'side' );
		add_meta_box( 'tmt-req-info', 'Thông tin khách gửi', 'tmt_req_box_info', 'tmt_request', 'normal', 'high' );
		add_meta_box( 'tmt-req-status', 'Tình trạng', 'tmt_req_box_status', 'tmt_request', 'side', 'high' );
	}
);

function tmt_req_box_info( $post ) {
	$types = tmt_req_types();
	$type  = get_post_meta( $post->ID, '_tmt_req_type', true );
	$ref   = (int) get_post_meta( $post->ID, '_tmt_req_ref', true );
	$rows  = array(
		'Mã'         => get_post_meta( $post->ID, '_tmt_req_number', true ),
		'Loại'       => isset( $types[ $type ] ) ? $types[ $type ][1] : $type,
		'Họ tên'     => get_post_meta( $post->ID, '_tmt_req_name', true ),
		'Điện thoại' => get_post_meta( $post->ID, '_tmt_req_phone', true ),
		'Món / gói'  => get_post_meta( $post->ID, '_tmt_req_item', true ),
		'Số lượng'   => (int) get_post_meta( $post->ID, '_tmt_req_qty', true ),
	);
	foreach ( (array) get_post_meta( $post->ID, '_tmt_req_extra', true ) as $k => $v ) {
		$rows[ $k ] = $v;
	}
	$rows['Ghi chú'] = get_post_meta( $post->ID, '_tmt_req_note', true );
	$rows['Gửi lúc'] = get_the_date( 'H:i d/m/Y', $post );
	echo '<table class="widefat striped"><tbody>';
	foreach ( $rows as $k => $v ) {
		if ( '' === (string) $v ) {
			continue;
		}
		echo '<tr><th style="width:140px">' . esc_html( $k ) . '</th><td style="white-space:pre-line">' . esc_html( (string) $v ) . '</td></tr>';
	}
	echo '</tbody></table>';
	$phone = get_post_meta( $post->ID, '_tmt_req_phone', true );
	echo '<p><a class="button" href="tel:' . esc_attr( $phone ) . '">Gọi khách</a> <a class="button" target="_blank" rel="noopener" href="' . esc_url( 'https://zalo.me/' . preg_replace( '/\D/', '', $phone ) ) . '">Nhắn Zalo</a>';
	if ( $ref && get_post( $ref ) ) {
		echo ' <a class="button" target="_blank" href="' . esc_url( get_permalink( $ref ) ) . '">Xem trang khách gửi</a>';
	}
	echo '</p>';
}

function tmt_req_box_status( $post ) {
	wp_nonce_field( 'tmt_req_status', 'tmt_req_nonce' );
	$cur = get_post_meta( $post->ID, '_tmt_req_status', true );
	echo '<select name="tmt_req_status" class="widefat">';
	foreach ( tmt_req_statuses() as $k => $l ) {
		echo '<option value="' . esc_attr( $k ) . '"' . selected( $cur, $k, false ) . '>' . esc_html( $l ) . '</option>';
	}
	echo '</select><p><label for="tmt-req-memo">Ghi chú của tiệm</label><textarea id="tmt-req-memo" name="tmt_req_memo" class="widefat" rows="4">' . esc_textarea( get_post_meta( $post->ID, '_tmt_req_memo', true ) ) . '</textarea></p>';
	echo '<p><button class="button button-primary">Lưu</button> <a class="submitdelete" style="color:#b32d2e;margin-left:8px" href="' . esc_url( get_delete_post_link( $post->ID ) ) . '">Bỏ vào thùng rác</a></p>';
}

add_action(
	'save_post_tmt_request',
	function ( $id ) {
		if ( ! isset( $_POST['tmt_req_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['tmt_req_nonce'] ), 'tmt_req_status' ) || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$s = isset( $_POST['tmt_req_status'] ) ? sanitize_key( $_POST['tmt_req_status'] ) : '';
		if ( isset( tmt_req_statuses()[ $s ] ) ) {
			update_post_meta( $id, '_tmt_req_status', $s );
		}
		update_post_meta( $id, '_tmt_req_memo', isset( $_POST['tmt_req_memo'] ) ? sanitize_textarea_field( wp_unslash( $_POST['tmt_req_memo'] ) ) : '' );
	}
);

/** Email nhận yêu cầu: trang Yêu cầu khách → Cài đặt. */
add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'edit.php?post_type=tmt_request', 'Cài đặt yêu cầu khách', 'Cài đặt', 'manage_woocommerce', 'tmt-req-settings', 'tmt_req_settings_page' );
	}
);

function tmt_req_settings_page() {
	if ( isset( $_POST['tmt_req_email'] ) && check_admin_referer( 'tmt_req_settings' ) ) {
		update_option( 'tmt_req_email', sanitize_email( wp_unslash( $_POST['tmt_req_email'] ) ) );
		echo '<div class="notice notice-success"><p>Đã lưu.</p></div>';
	}
	?>
	<div class="wrap">
		<h1>Cài đặt yêu cầu khách</h1>
		<form method="post">
			<?php wp_nonce_field( 'tmt_req_settings' ); ?>
			<table class="form-table">
				<tr>
					<th><label for="tmt-req-email">Email nhận yêu cầu</label></th>
					<td><input type="email" id="tmt-req-email" name="tmt_req_email" class="regular-text" value="<?php echo esc_attr( get_option( 'tmt_req_email' ) ); ?>" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
						<p class="description">Mỗi yêu cầu báo giá, khảo sát, dùng thử gửi một email tới đây. Để trống thì gửi về email quản trị.</p></td>
				</tr>
			</table>
			<?php submit_button( 'Lưu' ); ?>
		</form>
	</div>
	<?php
}

add_action(
	'admin_head',
	function () {
		$s = get_current_screen();
		if ( $s && 'tmt_request' === $s->post_type ) {
			echo '<style>.tmt-req-st{display:inline-block;border-radius:4px;padding:2px 8px;font-size:12px;background:#f0f0f1}.tmt-req-st.st-moi{background:#fcf0c3;color:#6b4e00}.tmt-req-st.st-dang-lien-he{background:#e5f0fd;color:#1d4f91}.tmt-req-st.st-da-bao-gia{background:#ece6fb;color:#4b2f9c}.tmt-req-st.st-da-chot{background:#e3f5e7;color:#1d6b33}.tmt-req-st.st-khong-thanh{background:#f6e3e3;color:#8a2424}.post-type-tmt_request #titlediv{display:none}</style>';
		}
	}
);
