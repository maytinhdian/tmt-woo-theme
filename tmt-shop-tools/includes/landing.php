<?php
/**
 * Trang Giải pháp (/giai-phap/...) và Phần mềm (/phan-mem/...) kiểu landing, giống web của app:
 * đầu trang xanh + ảnh, dải cam kết, "Lắp cho đâu?" / tính năng, gói giá chia nhóm, các bước, giới thiệu kèm ảnh,
 * thiết bị đang bán (theo danh mục), hỏi đáp, form "Đặt lịch khảo sát" / "Đăng ký dùng thử" (vào Yêu cầu khách).
 * Chủ tiệm sửa mọi chữ trong khung "Nội dung trang" ở trang sửa, phần nào để trống thì tự ẩn.
 *
 * @package TMT_Shop_Tools
 */

defined( 'ABSPATH' ) || exit;

/** Hai loại trang: post type => [đường dẫn, tên, tên một trang, icon menu]. */
function tmt_lp_types() {
	return array(
		'tmt_solution' => array( 'giai-phap', 'Giải pháp', 'giải pháp', 'dashicons-networking' ),
		'tmt_software' => array( 'phan-mem', 'Phần mềm', 'phần mềm', 'dashicons-desktop' ),
	);
}

add_action(
	'init',
	function () {
		foreach ( tmt_lp_types() as $pt => $t ) {
			register_post_type(
				$pt,
				array(
					'labels'        => array(
						'name'          => $t[1],
						'singular_name' => $t[1],
						'add_new'       => 'Thêm trang ' . $t[2],
						'add_new_item'  => 'Thêm trang ' . $t[2],
						'edit_item'     => 'Sửa trang ' . $t[2],
						'all_items'     => 'Tất cả trang ' . $t[2],
						'view_item'     => 'Xem trang',
						'not_found'     => 'Chưa có trang nào.',
						'featured_image' => 'Ảnh đầu trang',
						'set_featured_image' => 'Chọn ảnh đầu trang',
					),
					'public'        => true,
					'has_archive'   => $t[0],
					'rewrite'       => array( 'slug' => $t[0], 'with_front' => false ),
					'menu_icon'     => $t[3],
					'menu_position' => 57,
					'supports'      => array( 'title', 'excerpt', 'thumbnail', 'page-attributes' ),
					'show_in_rest'  => false,
				)
			);
		}
		// Đường dẫn mới: cập nhật quy tắc một lần sau khi cài / nâng cấp plugin.
		if ( get_option( 'tmt_lp_rewrite' ) !== TMT_TOOLS_VERSION ) {
			flush_rewrite_rules( false );
			update_option( 'tmt_lp_rewrite', TMT_TOOLS_VERSION );
		}
	}
);

/** Danh sách sắp theo "Thứ tự" (ô Thuộc tính trang), rồi tên. */
add_action(
	'pre_get_posts',
	function ( WP_Query $q ) {
		if ( ! is_admin() && $q->is_main_query() && $q->is_post_type_archive( array_keys( tmt_lp_types() ) ) ) {
			$q->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
			$q->set( 'posts_per_page', 30 );
		}
	}
);

/**
 * Các ô của trang. Ô thường: khóa => [tên, kiểu text|textarea|lines|icon|category]; danh sách lặp: khóa => [tên, cột, gợi ý].
 * Cột: khóa => [tên, kiểu text|textarea|lines|icon|image|number|check].
 */
function tmt_lp_schema( $pt ) {
	$solution = 'tmt_solution' === $pt;
	$fields   = array(
		'eyebrow'   => array( 'Chữ nhỏ trên tiêu đề', 'text' ),
		'headline'  => array( 'Tiêu đề lớn', 'text' ),
		'highlight' => array( 'Dòng tô vàng dưới tiêu đề', 'text' ),
		'intro'     => array( 'Đoạn giới thiệu', 'textarea' ),
		'ticks'     => array( 'Điểm nổi bật (mỗi dòng một ý)', 'lines' ),
		'icon'      => array( 'Icon (khi chưa có ảnh)', 'icon' ),
		'button'    => array( 'Chữ nút chính', 'text' ),
		'product_cat' => array( 'Thiết bị đang bán: lấy từ danh mục', 'category' ),
		'devices_title' => array( 'Tiêu đề phần thiết bị', 'text' ),
		'cta_title' => array( 'Tiêu đề cuối trang', 'text' ),
		'cta_text'  => array( 'Lời mời cuối trang', 'textarea' ),
	);
	if ( ! $solution ) {
		$fields['form_question'] = array( 'Câu hỏi trong form (tùy chọn)', 'text' );
		$fields['form_options']  = array( 'Các lựa chọn của câu hỏi (mỗi dòng một)', 'lines' );
	}
	$icon3 = array( 'icon' => array( 'Icon', 'icon' ), 'title' => array( 'Tiêu đề', 'text' ), 'text' => array( 'Mô tả', 'text' ) );
	$lists = array( 'trust' => array( 'Dải cam kết dưới đầu trang', $icon3, 'Nên 4 ô.' ) );
	if ( $solution ) {
		$lists['needs'] = array( 'Lắp cho đâu?', $icon3 + array( 'suggest' => array( 'Gợi ý', 'text' ) ), 'Tên các ô thành lựa chọn "Lắp cho" trong form khảo sát.' );
	} else {
		$lists['features'] = array( 'Tính năng', $icon3, '' );
	}
	$lists['steps']     = array( $solution ? 'Quy trình làm việc' : 'Cách hoạt động', $icon3, '' );
	$lists['showcases'] = array(
		'Giới thiệu kèm ảnh',
		array(
			'label'  => array( 'Chữ nhỏ', 'text' ),
			'title'  => array( 'Tiêu đề', 'text' ),
			'text'   => array( 'Mô tả', 'textarea' ),
			'points' => array( 'Ý chính (mỗi dòng một)', 'lines' ),
			'image'  => array( 'Ảnh', 'image' ),
		),
		'Ảnh và chữ xen kẽ trái phải.',
	);
	$lists['packages']  = array(
		$solution ? 'Gói trọn bộ' : 'Bảng giá',
		array(
			'group'    => array( 'Nhóm (tab)', 'text' ),
			'name'     => array( 'Tên gói', 'text' ),
			'audience' => array( 'Dành cho', 'text' ),
			'price'    => array( 'Giá (để trống = Liên hệ)', 'number' ),
			'period'   => array( 'Mỗi (tháng, năm...)', 'text' ),
			'items'    => array( 'Gồm (mỗi dòng một)', 'lines' ),
			'excluded' => array( 'Không gồm (mỗi dòng một)', 'lines' ),
			'icon'     => array( 'Icon', 'icon' ),
			'featured' => array( 'Nổi bật', 'check' ),
		),
		$solution ? 'Gói có "Nhóm" khác nhau thì chia thành tab (ví dụ Camera có dây / Camera WiFi).' : '',
	);
	$lists['faq'] = array( 'Câu hỏi thường gặp', array( 'q' => array( 'Câu hỏi', 'text' ), 'a' => array( 'Trả lời', 'textarea' ) ), '' );
	return array( 'fields' => $fields, 'lists' => $lists );
}

/** Nội dung mặc định khi tạo trang mới (chủ tiệm sửa lại cho đúng tiệm mình). */
function tmt_lp_defaults( $pt ) {
	if ( 'tmt_solution' === $pt ) {
		return array(
			'eyebrow' => '',
			'button'  => 'Đặt lịch khảo sát',
			'icon'    => 'network-wired',
			'cta_title' => 'Chưa biết cần những gì? Tiệm tới xem giúp',
			'cta_text'  => 'Để lại số điện thoại, kỹ thuật hẹn giờ tới khảo sát và báo giá rõ từng thiết bị, công, vật tư. Không mất phí.',
			'devices_title' => 'Thiết bị đang bán',
			'trust'   => array(
				array( 'icon' => 'ruler-combined', 'title' => 'Khảo sát miễn phí', 'text' => 'Tận nơi, báo giá trước' ),
				array( 'icon' => 'screwdriver-wrench', 'title' => 'Thi công gọn, thẩm mỹ', 'text' => 'Đi dây âm tường hoặc nẹp' ),
				array( 'icon' => 'shield-halved', 'title' => 'Bảo hành tận nơi', 'text' => 'Thiết bị chính hãng, có hóa đơn' ),
				array( 'icon' => 'headset', 'title' => 'Hỗ trợ qua Zalo', 'text' => 'Cài lại app, đổi mật khẩu' ),
			),
			'steps'   => array(
				array( 'icon' => 'phone', 'title' => 'Liên hệ', 'text' => 'Gọi, Zalo hoặc gửi yêu cầu' ),
				array( 'icon' => 'ruler-combined', 'title' => 'Khảo sát', 'text' => 'Miễn phí, tận nơi' ),
				array( 'icon' => 'file-invoice-dollar', 'title' => 'Báo giá', 'text' => 'Rõ từng thiết bị, công, vật tư' ),
				array( 'icon' => 'screwdriver-wrench', 'title' => 'Thi công', 'text' => 'Gọn, đúng hẹn, dán nhãn' ),
				array( 'icon' => 'handshake', 'title' => 'Bàn giao', 'text' => 'Hướng dẫn dùng, bảo trì' ),
			),
		);
	}
	return array(
		'button'  => 'Dùng thử miễn phí',
		'icon'    => 'desktop',
		'cta_title' => 'Dùng thử miễn phí',
		'cta_text'  => 'Để lại số điện thoại, tiệm gọi lại cài đặt và hướng dẫn sử dụng.',
		'devices_title' => 'Thiết bị bán kèm',
		'trust'   => array(
			array( 'icon' => 'screwdriver-wrench', 'title' => 'Cài đặt tận nơi', 'text' => 'Kỹ thuật tới tận nơi' ),
			array( 'icon' => 'chalkboard-user', 'title' => 'Hướng dẫn sử dụng', 'text' => 'Cho kế toán, quản lý' ),
			array( 'icon' => 'headset', 'title' => 'Hỗ trợ từ xa', 'text' => 'Qua Zalo, UltraViewer' ),
			array( 'icon' => 'box-open', 'title' => 'Bán kèm thiết bị', 'text' => 'Trọn bộ, một nơi lo' ),
		),
	);
}

/** Nội dung trang (đã đủ khóa). Trang chưa từng lưu thì lấy mặc định. */
function tmt_lp_get( $post_id ) {
	$pt     = get_post_type( $post_id );
	$schema = tmt_lp_schema( $pt );
	$saved  = get_post_meta( $post_id, '_tmt_lp', true );
	$data   = is_array( $saved ) ? $saved : tmt_lp_defaults( $pt );
	$out    = array();
	foreach ( $schema['fields'] as $k => $f ) {
		$v         = isset( $data[ $k ] ) ? $data[ $k ] : '';
		$out[ $k ] = 'lines' === $f[1] ? array_values( array_filter( (array) $v, 'strlen' ) ) : $v;
	}
	foreach ( $schema['lists'] as $k => $l ) {
		$out[ $k ] = isset( $data[ $k ] ) && is_array( $data[ $k ] ) ? array_values( $data[ $k ] ) : array();
	}
	return $out;
}

/** Biến chữ nhiều dòng thành mảng dòng không rỗng. */
function tmt_lp_lines( $v ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $v ) ), 'strlen' ) );
}

/** Làm sạch một giá trị theo kiểu ô. */
function tmt_lp_clean( $type, $v ) {
	switch ( $type ) {
		case 'textarea':
			return sanitize_textarea_field( (string) $v );
		case 'lines':
			return array_map( 'sanitize_text_field', tmt_lp_lines( $v ) );
		case 'icon':
			return preg_replace( '/[^a-z0-9-]/', '', strtolower( (string) $v ) );
		case 'image':
		case 'category':
			return absint( $v );
		case 'number':
			$d = preg_replace( '/\D/', '', (string) $v );
			return '' === $d ? '' : (int) $d;
		case 'check':
			return ! empty( $v );
		default:
			return sanitize_text_field( (string) $v );
	}
}

/* ---------- Khung "Nội dung trang" ở trang sửa ---------- */

add_action(
	'add_meta_boxes',
	function ( $pt ) {
		if ( isset( tmt_lp_types()[ $pt ] ) ) {
			add_meta_box( 'tmt-lp', 'Nội dung trang', 'tmt_lp_metabox', $pt, 'normal', 'high' );
			add_meta_box( 'postexcerpt', 'Tóm tắt (hiện ở danh sách và kết quả Google)', 'post_excerpt_meta_box', $pt, 'normal', 'high' );
		}
	}
);

add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		$s = get_current_screen();
		if ( ! $s || ! isset( tmt_lp_types()[ $s->post_type ] ) || ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'tmt-lp-admin', TMT_TOOLS_URL . 'assets/landing-admin.css', array(), TMT_TOOLS_VERSION );
		wp_enqueue_script( 'tmt-lp-admin', TMT_TOOLS_URL . 'assets/landing-admin.js', array(), TMT_TOOLS_VERSION, true );
	}
);

/** In một ô nhập theo kiểu. */
function tmt_lp_input( $name, $type, $value, $label ) {
	$id = 'f' . substr( md5( $name ), 0, 10 );
	if ( 'check' === $type ) {
		echo '<label class="tmt-lp-check"><input type="checkbox" name="' . esc_attr( $name ) . '" value="1"' . checked( ! empty( $value ), true, false ) . '> ' . esc_html( $label ) . '</label>';
		return;
	}
	echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>';
	switch ( $type ) {
		case 'textarea':
			echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="3">' . esc_textarea( (string) $value ) . '</textarea>';
			break;
		case 'lines':
			echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="3">' . esc_textarea( implode( "\n", (array) $value ) ) . '</textarea>';
			break;
		case 'category':
			wp_dropdown_categories(
				array(
					'taxonomy'         => 'product_cat',
					'name'             => $name,
					'id'               => $id,
					'selected'         => (int) $value,
					'show_option_none' => 'Không hiện',
					'option_none_value' => 0,
					'hide_empty'       => false,
					'hierarchical'     => true,
				)
			);
			break;
		case 'image':
			$src = $value ? wp_get_attachment_image_url( (int) $value, 'thumbnail' ) : '';
			echo '<span class="tmt-lp-img"><input type="hidden" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '"><img src="' . esc_url( $src ) . '" alt=""' . ( $src ? '' : ' hidden' ) . '><button type="button" class="button" data-lp-img>Chọn ảnh</button><button type="button" class="button-link" data-lp-img-clear>Bỏ ảnh</button></span>';
			break;
		case 'icon':
			echo '<span class="tmt-lp-icon"><input id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '" placeholder="video, wifi, fingerprint..." list="tmt-lp-icons"></span>';
			break;
		case 'number':
			echo '<input id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( '' === $value ? '' : number_format( (float) $value, 0, ',', '.' ) ) . '" inputmode="numeric">';
			break;
		default:
			echo '<input id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '">';
	}
}

function tmt_lp_metabox( $post ) {
	wp_nonce_field( 'tmt_lp_save', 'tmt_lp_nonce' );
	$schema = tmt_lp_schema( $post->post_type );
	$data   = tmt_lp_get( $post->ID );
	echo '<p class="description">Phần nào để trống thì trang tự ẩn phần đó. Ảnh đầu trang: khung "Ảnh đầu trang" bên phải. Icon: tên icon Font Awesome (xem <a href="https://fontawesome.com/search?ic=free&s=solid" target="_blank" rel="noopener">fontawesome.com</a>), ví dụ video, wifi, fingerprint.</p>';
	echo '<datalist id="tmt-lp-icons">';
	foreach ( array( 'network-wired', 'wifi', 'server', 'hard-drive', 'video', 'fingerprint', 'id-card', 'door-closed', 'shield-halved', 'house', 'store', 'building', 'industry', 'warehouse', 'mobile-screen', 'bell', 'moon', 'clock-rotate-left', 'users', 'school', 'box-open', 'screwdriver-wrench', 'phone', 'ruler-combined', 'file-invoice-dollar', 'handshake', 'headset', 'chalkboard-user', 'face-smile', 'rotate', 'calendar-days', 'clock', 'user-clock', 'money-bill-wave', 'file-excel', 'chart-column', 'cloud', 'desktop', 'print', 'gears', 'bolt' ) as $i ) {
		echo '<option value="' . esc_attr( $i ) . '">';
	}
	echo '</datalist><div class="tmt-lp-grid">';
	foreach ( $schema['fields'] as $k => $f ) {
		echo '<div class="tmt-lp-f' . ( in_array( $f[1], array( 'textarea', 'lines' ), true ) ? ' wide' : '' ) . '">';
		tmt_lp_input( 'tmt_lp[' . $k . ']', $f[1], $data[ $k ], $f[0] );
		echo '</div>';
	}
	echo '</div>';
	foreach ( $schema['lists'] as $k => $l ) {
		echo '<div class="tmt-lp-list" data-lp-list="' . esc_attr( $k ) . '"><h3>' . esc_html( $l[0] ) . '</h3>';
		if ( $l[2] ) {
			echo '<p class="description">' . esc_html( $l[2] ) . '</p>';
		}
		echo '<div class="tmt-lp-rows">';
		foreach ( $data[ $k ] as $i => $row ) {
			tmt_lp_row( $k, $i, $l[1], $row );
		}
		echo '</div><template>';
		tmt_lp_row( $k, '__i__', $l[1], array() );
		echo '</template><button type="button" class="button" data-lp-add>+ Thêm dòng</button></div>';
	}
}

/** Một dòng của danh sách lặp. */
function tmt_lp_row( $list, $i, $cols, $row ) {
	echo '<div class="tmt-lp-row"><div class="tmt-lp-cols">';
	foreach ( $cols as $c => $f ) {
		$v = isset( $row[ $c ] ) ? $row[ $c ] : ( 'lines' === $f[1] ? array() : '' );
		echo '<div class="tmt-lp-f t-' . esc_attr( $f[1] ) . '">';
		tmt_lp_input( 'tmt_lp[' . $list . '][' . $i . '][' . $c . ']', $f[1], $v, $f[0] );
		echo '</div>';
	}
	echo '</div><div class="tmt-lp-acts"><button type="button" class="button-link" data-lp-up title="Lên">↑</button><button type="button" class="button-link" data-lp-down title="Xuống">↓</button><button type="button" class="button-link tmt-lp-del" data-lp-del title="Xóa dòng">✕</button></div></div>';
}

add_action(
	'save_post',
	function ( $id, $post ) {
		if ( ! isset( tmt_lp_types()[ $post->post_type ] ) || ! isset( $_POST['tmt_lp_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['tmt_lp_nonce'] ), 'tmt_lp_save' ) || ! current_user_can( 'edit_post', $id ) ) {
			return;
		}
		$in     = isset( $_POST['tmt_lp'] ) && is_array( $_POST['tmt_lp'] ) ? wp_unslash( $_POST['tmt_lp'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- làm sạch từng ô bên dưới.
		$schema = tmt_lp_schema( $post->post_type );
		$out    = array();
		foreach ( $schema['fields'] as $k => $f ) {
			$out[ $k ] = tmt_lp_clean( $f[1], isset( $in[ $k ] ) ? $in[ $k ] : '' );
		}
		foreach ( $schema['lists'] as $k => $l ) {
			$out[ $k ] = array();
			foreach ( isset( $in[ $k ] ) && is_array( $in[ $k ] ) ? $in[ $k ] : array() as $row ) {
				$clean = array();
				$any   = false;
				foreach ( $l[1] as $c => $f ) {
					$clean[ $c ] = tmt_lp_clean( $f[1], isset( $row[ $c ] ) ? $row[ $c ] : '' );
					if ( 'check' !== $f[1] && 'icon' !== $f[1] && ! empty( $clean[ $c ] ) ) {
						$any = true;
					}
				}
				if ( $any ) {
					$out[ $k ][] = $clean;
				}
			}
		}
		update_post_meta( $id, '_tmt_lp', $out );
	},
	10,
	2
);

/* ---------- Ngoài web ---------- */

add_filter(
	'template_include',
	function ( $template ) {
		$types = array_keys( tmt_lp_types() );
		if ( is_singular( $types ) ) {
			return TMT_TOOLS_DIR . '/templates/landing.php';
		}
		if ( is_post_type_archive( $types ) ) {
			return TMT_TOOLS_DIR . '/templates/landing-archive.php';
		}
		return $template;
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		$types = array_keys( tmt_lp_types() );
		if ( is_singular( $types ) || is_post_type_archive( $types ) ) {
			wp_enqueue_style( 'tmt-landing', TMT_TOOLS_URL . 'assets/landing.css', array(), TMT_TOOLS_VERSION );
			wp_enqueue_style( 'tmt-requests', TMT_TOOLS_URL . 'assets/requests.css', array(), TMT_TOOLS_VERSION );
		}
	},
	20
);

/** Trang landing tràn ngang (theme TMT Shop): bỏ khung giới hạn bề rộng của trang. */
add_filter(
	'body_class',
	function ( $classes ) {
		if ( is_singular( array_keys( tmt_lp_types() ) ) ) {
			$classes[] = 'tmt-landing';
		}
		return $classes;
	}
);

/** Số điện thoại / email / địa chỉ / Zalo của tiệm (lấy từ Tùy biến của theme TMT Shop khi có). */
function tmt_lp_contact() {
	$opt   = function ( $k ) {
		return function_exists( 'tmt_opt' ) ? trim( (string) tmt_opt( $k ) ) : '';
	};
	$phone = function_exists( 'tmt_phone' ) ? tmt_phone() : $opt( 'shop.phone' );
	return array(
		'phone'   => $phone,
		'email'   => $opt( 'shop.email' ) ? $opt( 'shop.email' ) : get_option( 'admin_email' ),
		'address' => function_exists( 'tmt_address' ) ? tmt_address() : '',
		'zalo'    => function_exists( 'tmt_zalo_url' ) ? tmt_zalo_url( $opt( 'contact.zalo' ) ) : '',
	);
}

/** Gói chia theo nhóm (tab): [tên nhóm => [chỉ số => gói]]. Không gói nào có nhóm thì một nhóm rỗng. */
function tmt_lp_groups( array $packages ) {
	$groups = array();
	$named  = false;
	foreach ( $packages as $i => $p ) {
		$g = trim( (string) $p['group'] );
		$named = $named || '' !== $g;
		$groups[ '' !== $g ? $g : 'Gói khác' ][ $i ] = $p;
	}
	return $named ? $groups : array( '' => $packages );
}

/** Ảnh hoặc khung icon. */
function tmt_lp_shot( $image_id, $icon, $alt, $eager = false ) {
	if ( $image_id && wp_attachment_is_image( $image_id ) ) {
		echo '<div class="lp-shot">' . wp_get_attachment_image( $image_id, 'large', false, array( 'alt' => $alt, 'loading' => $eager ? 'eager' : 'lazy' ) ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
		return;
	}
	echo '<div class="lp-shot ph" role="img" aria-label="' . esc_attr( $alt ) . '"><i class="fa-solid fa-' . esc_attr( $icon ? $icon : 'box-open' ) . '" aria-hidden="true"></i></div>';
}

/** Giá gói: 1.290.000₫ hoặc "Liên hệ". */
function tmt_lp_price( $p ) {
	return '' === $p['price'] || null === $p['price'] ? 'Liên hệ' : number_format( (float) $p['price'], 0, ',', '.' ) . '₫';
}

/** Các bước có số thứ tự và đường nối. */
function tmt_lp_steps( array $steps ) {
	echo '<div class="lp-steps" style="--n: ' . (int) count( $steps ) . '">';
	foreach ( $steps as $s ) {
		echo '<div class="lp-step"><div class="ic"><i class="fa-solid fa-' . esc_attr( $s['icon'] ? $s['icon'] : 'circle' ) . '" aria-hidden="true"></i></div><b>' . esc_html( $s['title'] ) . '</b><small>' . esc_html( $s['text'] ) . '</small></div>';
	}
	echo '</div>';
}
