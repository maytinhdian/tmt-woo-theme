<?php
/**
 * Trang "Sửa máy" /sua-may: quy trình tiếp nhận sửa máy + bảng phí + khối tính thử (port từ app, Anh 2 duyệt 22/11/2026).
 * Mọi khoản phí chỉ đọc qua tmt_repair_fees() (Tùy biến → Sửa máy), trang này và khối trang chủ luôn cùng số.
 *
 * @package TMT_Shop_Tools
 */

defined( 'ABSPATH' ) || exit;

/** Mặc định giống app (config repairs.fees). */
function tmt_repair_fee_defaults() {
	return array(
		'on'         => true,
		'inspect'    => 100000,
		'travel_min' => 150000,
		'per_km'     => 10000,
		'limit'      => 500000,
		'waive'      => true,
	);
}

/** Phí sửa máy đang dùng: on, inspect, travel_min, per_km, limit, waive. */
function tmt_repair_fees() {
	$d = tmt_repair_fee_defaults();
	$o = get_option( 'tmt_repair', array() );
	$o = is_array( $o ) ? $o : array();
	$f = array();
	foreach ( $d as $k => $v ) {
		$val     = array_key_exists( $k, $o ) ? $o[ $k ] : $v;
		$f[ $k ] = is_bool( $v ) ? (bool) $val : max( 0, (int) $val );
	}
	return $f;
}

/** Trang /sua-may đang bật không. */
function tmt_repair_on() {
	return tmt_repair_fees()['on'];
}

function tmt_repair_url() {
	return home_url( '/sua-may/' );
}

/** Các lựa chọn "Cần gì" trong form (cũng là danh sách hợp lệ khi lưu). */
function tmt_repair_kinds() {
	return array( 'Sửa máy · mang tới tiệm', 'Sửa máy · thợ tới tận nơi', 'Tư vấn mua máy tận nơi' );
}

function tmt_repair_vnd( $n ) {
	return number_format( (int) $n, 0, ',', '.' ) . '₫';
}

/** Câu miễn / trừ phí kiểm tra. */
function tmt_repair_waive_text( $f = null ) {
	$f = $f ? $f : tmt_repair_fees();
	return $f['waive'] ? 'Đồng ý sửa thì miễn phí kiểm tra' : 'Đồng ý sửa thì phí kiểm tra trừ vào tiền sửa';
}

/* ---------- Tùy biến: Sửa máy ---------- */

add_action(
	'customize_register',
	function ( $wp ) {
		$wp->add_section(
			'tmt_repair',
			array(
				'title'       => 'Sửa máy: quy trình và phí',
				'priority'    => 165,
				'description' => 'Trang ' . esc_html( tmt_repair_url() ) . ' và khối "Quy trình sửa máy" ở trang chủ đọc các số này.',
			)
		);
		$d      = tmt_repair_fee_defaults();
		$fields = array(
			'on'         => array( 'checkbox', 'Bật trang Sửa máy' ),
			'inspect'    => array( 'number', 'Phí kiểm tra mỗi máy (₫)' ),
			'waive'      => array( 'checkbox', 'Đồng ý sửa thì miễn phí kiểm tra (bỏ chọn = trừ vào tiền sửa)' ),
			'travel_min' => array( 'number', 'Phí di chuyển tối thiểu mỗi chuyến (₫)' ),
			'per_km'     => array( 'number', 'Xa hơn: mỗi km (₫, 0 = không tính theo km)' ),
			'limit'      => array( 'number', 'Hạn mức "dưới bao nhiêu cứ thay" gợi ý (₫)' ),
		);
		foreach ( $fields as $k => $f ) {
			$id = 'tmt_repair[' . $k . ']';
			$wp->add_setting(
				$id,
				array(
					'type'              => 'option',
					'default'           => $d[ $k ],
					'sanitize_callback' => 'checkbox' === $f[0] ? 'wp_validate_boolean' : 'absint',
				)
			);
			$wp->add_control(
				$id,
				array(
					'section'     => 'tmt_repair',
					'type'        => $f[0],
					'label'       => $f[1],
					'input_attrs' => 'number' === $f[0] ? array( 'min' => 0, 'step' => 1000 ) : array(),
				)
			);
		}
	}
);

/* ---------- Đường dẫn /sua-may ---------- */

add_action(
	'init',
	function () {
		add_rewrite_rule( '^sua-may/?$', 'index.php?tmt_sua_may=1', 'top' );
		if ( get_option( 'tmt_repair_rewrite' ) !== TMT_TOOLS_VERSION ) {
			flush_rewrite_rules( false );
			update_option( 'tmt_repair_rewrite', TMT_TOOLS_VERSION );
		}
	}
);

add_filter(
	'query_vars',
	function ( $vars ) {
		$vars[] = 'tmt_sua_may';
		return $vars;
	}
);

/** Trang đang là /sua-may (và đang bật). */
function tmt_is_repair_page() {
	return (bool) get_query_var( 'tmt_sua_may' ) && tmt_repair_on();
}

add_action(
	'template_redirect',
	function () {
		if ( get_query_var( 'tmt_sua_may' ) && ! tmt_repair_on() ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
		} elseif ( tmt_is_repair_page() ) {
			global $wp_query;
			$wp_query->is_404  = false;
			$wp_query->is_home = false;
			status_header( 200 );
		}
	}
);

add_filter(
	'template_include',
	function ( $template ) {
		return tmt_is_repair_page() ? TMT_TOOLS_DIR . '/templates/repair-intake.php' : $template;
	},
	20
);

add_filter(
	'document_title_parts',
	function ( $parts ) {
		if ( tmt_is_repair_page() ) {
			$parts['title'] = 'Sửa máy: kiểm tra trước, đồng ý mới sửa';
		}
		return $parts;
	}
);

/** CSS trang Sửa máy (dùng chung khung lp-* của trang Giải pháp). Khối trang chủ gọi lại hàm này. */
function tmt_repair_enqueue() {
	wp_enqueue_style( 'tmt-landing', TMT_TOOLS_URL . 'assets/landing.css', array(), TMT_TOOLS_VERSION );
	wp_enqueue_style( 'tmt-repair', TMT_TOOLS_URL . 'assets/repair.css', array( 'tmt-landing' ), TMT_TOOLS_VERSION );
}

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( tmt_is_repair_page() ) {
			tmt_repair_enqueue();
			wp_enqueue_style( 'tmt-requests', TMT_TOOLS_URL . 'assets/requests.css', array(), TMT_TOOLS_VERSION );
			wp_enqueue_script( 'tmt-repair', TMT_TOOLS_URL . 'assets/repair.js', array(), TMT_TOOLS_VERSION, true );
		} elseif ( is_front_page() && tmt_repair_on() && function_exists( 'tmt_opt_list' ) && in_array( 'repair', tmt_opt_list( 'home.sections' ), true ) ) {
			tmt_repair_enqueue(); // Khối "Quy trình sửa máy" của theme.
		}
	},
	20
);

add_filter(
	'body_class',
	function ( $classes ) {
		if ( tmt_is_repair_page() ) {
			$classes[] = 'tmt-landing';
		}
		return $classes;
	}
);

/** Mô tả + FAQ (JSON-LD) trong <head>. */
add_action(
	'wp_head',
	function () {
		if ( ! tmt_is_repair_page() ) {
			return;
		}
		$f = tmt_repair_fees();
		echo '<meta name="description" content="' . esc_attr( 'Quy trình tiếp nhận sửa laptop, PC tại tiệm hoặc tận nơi: phí kiểm tra ' . tmt_repair_vnd( $f['inspect'] ) . ' mỗi máy, có hư mới báo giá, ' . mb_strtolower( tmt_repair_waive_text( $f ) ) . '.' ) . '">' . "\n";
		$ld = array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => array_map(
				function ( $q ) {
					return array( '@type' => 'Question', 'name' => $q[0], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $q[1] ) );
				},
				tmt_repair_faq( $f )
			),
		);
		echo '<script type="application/ld+json">' . wp_json_encode( $ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ) . "</script>\n";
	},
	5
);

/** Câu hỏi thường gặp (số tiền lấy từ phí đang dùng). */
function tmt_repair_faq( $f ) {
	return array(
		array( 'Máy không hư mà vẫn trả phí kiểm tra?', 'Có. Thợ đã tháo, đo, thử để chắc máy không hư. Phí được báo trước khi nhận máy, anh/chị đồng ý rồi tiệm mới làm.' ),
		array( 'Mang nhiều máy cùng lúc thì phí tính sao?', 'Phí kiểm tra tính theo từng máy. Phí di chuyển chỉ tính 1 lần cho mỗi chuyến thợ tới.' ),
		array( 'Không muốn thợ tự thay, muốn được hỏi trước?', 'Lúc nhận máy để hạn mức trống. Lần nào cần thay, tiệm gửi báo giá qua Zalo để anh/chị đồng ý.' ),
		array( 'Phí di chuyển tính thế nào?', 'Mỗi chuyến ít nhất ' . tmt_repair_vnd( $f['travel_min'] ) . ( $f['per_km'] ? ', xa hơn thì ' . tmt_repair_vnd( $f['per_km'] ) . ' mỗi km đường đi' : '' ) . '. Anh/chị gửi định vị qua Zalo, tiệm báo số tiền trước khi đi.' ),
		array( 'Xem tình trạng sửa ở đâu?', 'Tiệm báo qua Zalo hoặc gọi điện mỗi khi có kết quả kiểm tra, báo giá và lúc máy sửa xong.' ),
	);
}
