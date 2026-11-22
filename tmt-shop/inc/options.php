<?php
/**
 * Giá trị tùy biến của theme (Giao diện → Tùy biến). Mọi template đọc qua tmt_opt('khóa'), không gọi get_theme_mod trực tiếp.
 * Khóa dạng "nhóm.tên" giống bảng tùy biến của web Laravel; lưu thành theme_mod "tmt_nhóm_tên".
 * Ô danh sách (thanh trên cùng, cam kết, slider...) lưu chuỗi JSON, đọc bằng tmt_opt_list().
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;

/**
 * Giá trị mặc định. Thêm ô mới: khai ở đây rồi thêm control ở inc/customizer.php.
 */
function tmt_defaults() {
	static $d = null;
	if ( null !== $d ) {
		return $d;
	}
	$d = array(
		// Thông tin cửa hàng (dùng ở đầu trang, chân trang, nút liên hệ).
		'shop.brand'        => get_bloginfo( 'name' ),
		'shop.brand_by'     => '',
		'shop.company'      => '',
		'shop.address'      => '',
		'shop.phone'        => '',
		'shop.email'        => get_option( 'admin_email' ),
		'shop.tax_code'     => '',

		'colors.primary'    => '#3271E0',
		'colors.accent'     => '#FDDB4C',
		'colors.dark'       => '#1E3A78',
		'font'              => 'Roboto',

		'layout.width'      => 'boxed',
		'layout.max_width'  => 1240,
		'layout.gutter'     => 20,
		'layout.stretch_chrome' => true,

		'header.show_search'  => true,
		'header.search_style' => 'box',
		'header.show_topbar'  => true,
		'topbar.items'        => array(
			array( 'zone' => 'left', 'icon' => 'fa-solid fa-location-dot', 'text' => '{dia_chi}', 'link' => '', 'accent' => false ),
			array( 'zone' => 'right', 'icon' => '', 'text' => 'Gọi hoặc Zalo: {sdt}', 'link' => '{sdt}', 'accent' => false ),
		),
		'topbar.mobile'       => 'center',

		'nav.case'          => 'none',
		'nav.align'         => 'left',
		'nav.mega_on'       => true,
		'nav.mega_label'    => 'Danh mục',
		'nav.drop_on'       => true,
		'nav.drop_show'     => 'brand',
		'nav.hover'         => 'line',
		'nav.active'        => 'line',
		'nav.fx_color'      => 'primary',
		'nav.fx_speed'      => 'normal',

		'footer.about'      => '',
		'footer.hours'      => '8:00 đến 20:00, cả Chủ nhật',
		'footer.shipping'   => '',
		'footer.promises_on' => true,
		'footer.promise_items' => array(
			array( 'icon' => 'fa-solid fa-shield-halved', 'title' => 'Bảo hành tại tiệm', 'sub' => 'Tra bảo hành online bằng serial' ),
			array( 'icon' => 'fa-solid fa-truck-fast', 'title' => 'Giao trong ngày', 'sub' => 'Nội thành, đơn đặt trước 16:00' ),
			array( 'icon' => 'fa-solid fa-screwdriver-wrench', 'title' => 'Ráp máy miễn phí', 'sub' => 'Cài Windows, test kỹ trước khi giao' ),
			array( 'icon' => 'fa-solid fa-certificate', 'title' => 'Hàng chính hãng', 'sub' => 'Có serial, xuất hóa đơn công ty' ),
		),
		'footer.newsletter' => false,
		'footer.map'        => true,
		'footer.map_query'  => '',
		'footer.zalo'       => '',
		'footer.facebook'   => '',
		'footer.youtube'    => '',
		'footer.tiktok'     => '',

		'home.sections'     => array( 'slider', 'promises', 'categories', 'sale', 'software', 'repair', 'products', 'posts' ),
		'home.slider_style' => 'banners',
		'home.slides'       => array(),
		'home.slide_seconds' => 5,
		'home.banners'      => array(),
		'home.hero_title'   => 'Linh kiện, laptop và sửa máy tính',
		'home.hero_text'    => 'Hàng chính hãng có serial, bảo hành ngay tại tiệm. Mua online giao trong ngày.',
		'home.categories'   => '',
		'home.sale_title'   => 'Giá sốc cuối tuần',
		'home.sale_ends_at' => '',
		'home.sale_count'   => 10,
		'home.software_title' => 'Phần mềm bản quyền',
		'home.software_cats'  => '',
		'home.software_count' => 4,
		'home.software_banner_title' => 'Key bản quyền chính hãng, nhận ngay qua email',
		'home.software_banner_text'  => 'Dùng cho máy nhà, máy văn phòng, có hóa đơn đỏ.',
		'home.software_points' => "Key chính hãng, kích hoạt online\nGửi key qua email, Zalo trong 15 phút\nTiệm cài giúp qua UltraViewer hoặc tại tiệm\nNhắc gia hạn trước 30 ngày\nXuất hóa đơn VAT cho công ty",
		'home.software_steps'  => "Chọn phần mềm | Mua online hoặc nhắn Zalo\nThanh toán | Chuyển khoản, quét QR\nNhận key | Qua email, Zalo trong 15 phút\nCài đặt | Tiệm hỗ trợ từ xa miễn phí",
		'home.repair_title' => 'Kiểm tra trước, đồng ý mới sửa',
		'home.repair_text'  => '',
		'home.products_title' => 'Sản phẩm mới về',
		'home.products_count' => 8,
		'home.posts_title'  => 'Mẹo vặt máy tính',
		'home.posts_count'  => 3,

		'listing.filter_style' => 'bar',
		'listing.card_width'   => 230,
		'listing.per_page'     => 24,

		'product.show_zalo'    => true,
		'product.perks'        => "Tra bảo hành online bằng serial\nLắp đặt miễn phí khi mua tại tiệm\nGiao trong ngày nội thành",
		'product.related_count' => 4,
		'product.discount_badge' => true,
		'product.variant_style' => 'buttons',
		'product.spec_tags'    => 3,
		'product.promos'       => array(),
		'product.intro_style'  => 'full',
		'product.intro_nav'    => true,
		'product.intro_accent' => 'blue',
		'checkout.note'        => 'Tiệm gọi xác nhận đơn trong giờ làm việc trước khi giao.',
		'payment.bank_bin'     => '',
		'payment.qr_prefix'    => 'DH',

		'contact.enabled'      => true,
		'contact.hours'        => '8:00 đến 20:00, cả Chủ nhật',
		'contact.hotline_on'   => true,
		'contact.hotline'      => '',
		'contact.zalo_on'      => true,
		'contact.zalo'         => '',
		'contact.messenger_on' => true,
		'contact.messenger'    => '',

		'banners.left_on'      => false,
		'banners.left_image'   => '',
		'banners.left_link'    => '',
		'banners.right_on'     => false,
		'banners.right_image'  => '',
		'banners.right_link'   => '',

		'blog.cta_on'          => true,
		'blog.cta_style'       => 'yellow',
		'blog.cta_title'       => '',
		'blog.cta_text'        => '',
		'blog.cta_sub'         => '',
		'blog.cta_points'      => '',
		'blog.cta_btn1'        => 'zalo',
		'blog.cta_btn1_label'  => '',
		'blog.cta_btn2'        => 'phone',
		'blog.cta_btn2_label'  => '',
		'blog.cta_link'        => '',
		'blog.cta_on_list'     => true,
		'blog.cta_on_post'     => true,
		'blog.cta_open'        => true,

		'effects.product_hover' => 'classic',
		'effects.post_hover'    => 'classic',
		'effects.intensity'     => 'medium',
		'effects.cursor'        => 'none',
		'effects.brand_fx'      => 'none',
		'effects.brand_target'  => 'by',
		'effects.brand_run'     => 'loop',
		'effects.brand_speed'   => 'normal',
		'effects.brand_color'   => 'primary',
		'effects.brand_lines'   => '',
		'effects.cart_fx'       => 'none',
		'effects.cart_look'     => 'theme',
		'effects.cart_where'    => 'both',
		'effects.cart_speed'    => 'normal',
		'effects.cart_count'    => false,
	);
	return $d;
}

/** Tên theme_mod của một khóa. */
function tmt_mod_name( $key ) {
	return 'tmt_' . str_replace( '.', '_', $key );
}

/**
 * Đọc một ô tùy biến (đã đè giá trị mặc định).
 *
 * @param string $key Khóa dạng "nhóm.tên".
 * @return mixed
 */
function tmt_opt( $key ) {
	$defaults = tmt_defaults();
	$default  = isset( $defaults[ $key ] ) ? $defaults[ $key ] : null;
	if ( is_array( $default ) ) {
		return tmt_opt_list( $key );
	}
	$value = get_theme_mod( tmt_mod_name( $key ), $default );
	if ( is_bool( $default ) ) {
		return (bool) $value;
	}
	if ( is_int( $default ) ) {
		return (int) $value;
	}
	return $value;
}

/**
 * Đọc ô danh sách (lưu JSON). Chưa từng lưu thì trả mặc định; đã lưu rỗng thì trả mảng rỗng.
 *
 * @param string $key Khóa.
 * @return array
 */
function tmt_opt_list( $key ) {
	$defaults = tmt_defaults();
	$raw      = get_theme_mod( tmt_mod_name( $key ), null );
	if ( null === $raw ) {
		return isset( $defaults[ $key ] ) ? (array) $defaults[ $key ] : array();
	}
	if ( is_array( $raw ) ) {
		return $raw;
	}
	$list = json_decode( (string) $raw, true );
	// Repeater lưu mảng các dòng (mảng con), sortable lưu mảng chuỗi (khóa section): giữ cả hai, bỏ giá trị lạ.
	return is_array( $list ) ? array_values( array_filter( $list, function ( $v ) { return is_array( $v ) || is_string( $v ); } ) ) : array();
}

/** Ô nhiều dòng thành danh sách dòng không rỗng. */
function tmt_opt_lines( $key ) {
	return array_values( array_filter( array_map( 'trim', explode( "\n", (string) tmt_opt( $key ) ) ) ) );
}

/** Số điện thoại đầu tiên của tiệm (ô có thể ghi nhiều số cách nhau dấu phẩy). */
function tmt_phone() {
	$phones = array_filter( array_map( 'trim', explode( ',', (string) tmt_opt( 'shop.phone' ) ) ) );
	return $phones ? reset( $phones ) : '';
}

/** Chỉ giữ chữ số và dấu +. */
function tmt_digits( $s ) {
	return preg_replace( '/[^0-9+]/', '', (string) $s );
}

/** Link Zalo từ số điện thoại hoặc link https; null khi không dựng được. */
function tmt_zalo_url( $value = '' ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		$value = tmt_phone();
	}
	if ( preg_match( '#^https://#i', $value ) ) {
		return $value;
	}
	$d = preg_replace( '/\D/', '', $value );
	return strlen( $d ) >= 8 ? 'https://zalo.me/' . $d : null;
}

/** Địa chỉ tiệm: ô Thông tin cửa hàng, trống thì lấy địa chỉ cửa hàng của WooCommerce. */
function tmt_address() {
	$a = trim( (string) tmt_opt( 'shop.address' ) );
	if ( '' === $a ) {
		$a = trim( implode( ', ', array_filter( array( get_option( 'woocommerce_store_address' ), get_option( 'woocommerce_store_city' ) ) ) ) );
	}
	return $a;
}

/** Icon Font Awesome an toàn (chỉ dạng "fa-solid fa-xxx"), null khi không hợp lệ. */
function tmt_safe_icon( $icon ) {
	$icon = trim( (string) $icon );
	return preg_match( '/^fa-(solid|regular|brands) fa-[a-z0-9-]+$/', $icon ) ? $icon : null;
}

/** Màu hex hợp lệ hoặc giá trị mặc định. */
function tmt_color( $key ) {
	$c        = (string) tmt_opt( $key );
	$defaults = tmt_defaults();
	return preg_match( '/^#[0-9a-fA-F]{6}$/', $c ) ? $c : $defaults[ $key ];
}

/** Font chọn được (Google Fonts có chữ Việt). */
function tmt_fonts() {
	return array( 'Roboto', 'Be Vietnam Pro', 'Inter', 'Nunito', 'Montserrat', 'Open Sans' );
}

/**
 * Bề rộng trang: khung (boxed) hay giãn hết, bề rộng tối đa, lề.
 *
 * @return array{full: bool, max: int, gutter: int, chrome: bool}
 */
function tmt_page_width() {
	$full = 'full' === tmt_opt( 'layout.width' );
	return array(
		'full'   => $full,
		'max'    => max( 960, min( 1920, (int) tmt_opt( 'layout.max_width' ) ) ),
		'gutter' => max( 0, min( 80, (int) tmt_opt( 'layout.gutter' ) ) ),
		'chrome' => $full && tmt_opt( 'layout.stretch_chrome' ),
	);
}

/** Thay {sdt}, {email}, {dia_chi} trong chữ (đã escape), tô màu nhấn. */
function tmt_fill_tokens_html( $text ) {
	$map = array(
		'{sdt}'     => tmt_phone(),
		'{email}'   => (string) tmt_opt( 'shop.email' ),
		'{dia_chi}' => tmt_address(),
	);
	$html = esc_html( $text );
	foreach ( $map as $token => $value ) {
		$html = str_replace( $token, '' === $value ? '' : '<b class="sf-top-hl">' . esc_html( $value ) . '</b>', $html );
	}
	return $html;
}

/** Link của mục thanh trên cùng: https://, /đường-dẫn, số điện thoại, mailto:, {sdt}, {zalo}, {email}. */
function tmt_item_href( $link ) {
	$link = trim( (string) $link );
	if ( '' === $link ) {
		return null;
	}
	if ( '{sdt}' === $link ) {
		$d = tmt_digits( tmt_phone() );
		return strlen( $d ) >= 8 ? 'tel:' . $d : null;
	}
	if ( '{zalo}' === $link ) {
		return tmt_zalo_url();
	}
	if ( '{email}' === $link ) {
		$e = (string) tmt_opt( 'shop.email' );
		return is_email( $e ) ? 'mailto:' . $e : null;
	}
	if ( preg_match( '#^(https?://|/|mailto:|tel:)#i', $link ) ) {
		return 0 === strpos( $link, '/' ) ? home_url( $link ) : $link;
	}
	if ( strlen( tmt_digits( $link ) ) >= 8 && preg_match( '/^[0-9+ .]+$/', $link ) ) {
		return 'tel:' . tmt_digits( $link );
	}
	return null;
}

/**
 * Thanh trên cùng đã dựng cho template, null khi tắt.
 *
 * @return array|null
 */
function tmt_topbar() {
	if ( ! tmt_opt( 'header.show_topbar' ) ) {
		return null;
	}
	$zones = array( 'left' => array(), 'center' => array(), 'right' => array() );
	foreach ( tmt_opt_list( 'topbar.items' ) as $item ) {
		$zone = isset( $item['zone'] ) ? $item['zone'] : 'left';
		$icon = tmt_safe_icon( isset( $item['icon'] ) ? $item['icon'] : '' );
		$text = trim( (string) ( isset( $item['text'] ) ? $item['text'] : '' ) );
		if ( ! isset( $zones[ $zone ] ) || ( ! $icon && '' === $text ) ) {
			continue;
		}
		$html = '' === $text ? '' : tmt_fill_tokens_html( $text );
		if ( '' !== $text && '' === trim( wp_strip_all_tags( $html ) ) ) {
			continue; // Chỉ có {dia_chi}/{sdt} mà chưa nhập thông tin: bỏ, không để icon trơ.
		}
		$href            = tmt_item_href( isset( $item['link'] ) ? $item['link'] : '' );
		$zones[ $zone ][] = array(
			'icon'     => $icon,
			'html'     => $html,
			'label'    => wp_strip_all_tags( $text ),
			'href'     => $href,
			'external' => $href && 0 === strpos( $href, 'http' ) && false === strpos( $href, home_url() ),
			'accent'   => ! empty( $item['accent'] ),
		);
	}
	if ( ! array_filter( $zones ) ) {
		return null;
	}
	$mobile = in_array( tmt_opt( 'topbar.mobile' ), array( 'center', 'scroll', 'hide' ), true ) ? tmt_opt( 'topbar.mobile' ) : 'center';
	$mz     = null;
	foreach ( array( 'center', 'right', 'left' ) as $z ) {
		if ( $zones[ $z ] ) {
			$mz = $z;
			break;
		}
	}
	return array( 'zones' => $zones, 'mobile' => $mobile, 'mobileZone' => $mz );
}

/**
 * Ô cam kết (tối đa 4), dùng ở chân trang và section trang chủ.
 *
 * @return array
 */
function tmt_promise_items() {
	$out = array();
	foreach ( tmt_opt_list( 'footer.promise_items' ) as $item ) {
		$title = trim( (string) ( isset( $item['title'] ) ? $item['title'] : '' ) );
		if ( '' === $title ) {
			continue;
		}
		$out[] = array(
			'icon'  => tmt_safe_icon( isset( $item['icon'] ) ? $item['icon'] : '' ) ?: 'fa-solid fa-circle-check',
			'title' => $title,
			'sub'   => trim( (string) ( isset( $item['sub'] ) ? $item['sub'] : '' ) ),
		);
	}
	return array_slice( $out, 0, 4 );
}

/**
 * Nút liên hệ nổi đang bật.
 *
 * @return array
 */
function tmt_contact_buttons() {
	if ( ! tmt_opt( 'contact.enabled' ) ) {
		return array();
	}
	$buttons = array();
	$hotline = trim( (string) ( tmt_opt( 'contact.hotline' ) ?: tmt_phone() ) );
	$digits  = tmt_digits( $hotline );
	if ( tmt_opt( 'contact.hotline_on' ) && strlen( $digits ) >= 8 ) {
		$buttons[] = array( 'key' => 'hotline', 'label' => 'Hotline: ' . $hotline, 'href' => 'tel:' . $digits, 'external' => false );
	}
	$zalo = tmt_zalo_url( tmt_opt( 'contact.zalo' ) );
	if ( tmt_opt( 'contact.zalo_on' ) && $zalo ) {
		$buttons[] = array( 'key' => 'zalo', 'label' => 'Chat Zalo', 'href' => $zalo, 'external' => true );
	}
	$messenger = trim( (string) tmt_opt( 'contact.messenger' ) );
	if ( tmt_opt( 'contact.messenger_on' ) && preg_match( '#^https://#i', $messenger ) ) {
		$buttons[] = array( 'key' => 'messenger', 'label' => 'Chat Messenger', 'href' => $messenger, 'external' => true );
	}
	return $buttons;
}

/** Kiểu hiệu ứng thẻ được phép. */
function tmt_card_effects() {
	return array(
		'classic' => 'Viền xanh + bóng nhẹ',
		'lift'    => 'Nổi lên, đổ bóng',
		'zoom'    => 'Phóng to ảnh',
		'glow'    => 'Viền phát sáng',
		'shine'   => 'Vệt sáng lướt qua ảnh',
		'tilt'    => 'Nghiêng 3D theo chuột',
		'overlay' => 'Phủ màu, chữ "Xem chi tiết"',
		'swap'    => 'Đổi sang ảnh thứ hai (chỉ thẻ sản phẩm)',
		'none'    => 'Không hiệu ứng',
	);
}

/** Kiểu con trỏ chuột (chỉ máy có chuột; tự tắt trên điện thoại và khi máy bật giảm chuyển động). */
function tmt_cursors() {
	return array(
		'none'  => 'Con trỏ thường',
		'ring'  => 'Chấm + vòng đuổi theo, phóng to khi rê vào nút',
		'glow'  => 'Quầng sáng mềm theo chuột',
		'trail' => 'Vệt chấm nối đuôi',
		'spark' => 'Bung hạt màu khi bấm',
	);
}

/**
 * Hiệu ứng đang bật: lớp gắn lên body, hệ số cường độ, kiểu con trỏ, có cần nạp fx.js không.
 *
 * @return array{product: string, post: string, k: string, cursor: string, bodyClass: string, js: bool}
 */
function tmt_effects() {
	$all     = array_keys( tmt_card_effects() );
	$product = in_array( tmt_opt( 'effects.product_hover' ), $all, true ) ? tmt_opt( 'effects.product_hover' ) : 'classic';
	$post    = in_array( tmt_opt( 'effects.post_hover' ), array_diff( $all, array( 'swap' ) ), true ) ? tmt_opt( 'effects.post_hover' ) : 'classic';
	$k       = array( 'soft' => '.6', 'medium' => '1', 'strong' => '1.6' );
	$i       = isset( $k[ tmt_opt( 'effects.intensity' ) ] ) ? tmt_opt( 'effects.intensity' ) : 'medium';
	$cursor  = array_key_exists( (string) tmt_opt( 'effects.cursor' ), tmt_cursors() ) ? tmt_opt( 'effects.cursor' ) : 'none';
	return array(
		'product'   => $product,
		'post'      => $post,
		'k'         => $k[ $i ],
		'cursor'    => $cursor,
		'bodyClass' => "sf-fx-p-{$product} sf-fx-a-{$post}",
		'js'        => 'none' !== $cursor || 'tilt' === $product || 'tilt' === $post,
	);
}

/** Banner dọc hai bên đang bật: side => [image, link]. */
function tmt_side_banners() {
	$out = array();
	foreach ( array( 'left', 'right' ) as $side ) {
		$img = (string) tmt_opt( "banners.{$side}_image" );
		if ( tmt_opt( "banners.{$side}_on" ) && '' !== $img ) {
			$out[ $side ] = array(
				'image' => $img,
				'link'  => tmt_item_href( tmt_opt( "banners.{$side}_link" ) ),
			);
		}
	}
	return $out;
}

/** BIN (mã ngân hàng NAPAS) => [tên hiển thị, các tên thường gọi để nhận ra]. */
function tmt_vietqr_banks() {
	return array(
		'970436' => array( 'Vietcombank', array( 'vietcombank', 'vcb', 'ngoai thuong' ) ),
		'970415' => array( 'VietinBank', array( 'vietinbank', 'cong thuong', 'ctg' ) ),
		'970418' => array( 'BIDV', array( 'bidv', 'dau tu va phat trien' ) ),
		'970405' => array( 'Agribank', array( 'agribank', 'nong nghiep' ) ),
		'970407' => array( 'Techcombank', array( 'techcombank', 'tcb', 'ky thuong' ) ),
		'970422' => array( 'MB Bank', array( 'mbbank', 'mb bank', 'quan doi', 'mb' ) ),
		'970416' => array( 'ACB', array( 'acb', 'a chau' ) ),
		'970432' => array( 'VPBank', array( 'vpbank', 'viet nam thinh vuong' ) ),
		'970423' => array( 'TPBank', array( 'tpbank', 'tien phong' ) ),
		'970403' => array( 'Sacombank', array( 'sacombank', 'sai gon thuong tin' ) ),
		'970441' => array( 'VIB', array( 'vib', 'quoc te' ) ),
		'970443' => array( 'SHB', array( 'shb', 'sai gon ha noi' ) ),
		'970437' => array( 'HDBank', array( 'hdbank', 'phat trien tp' ) ),
		'970448' => array( 'OCB', array( 'ocb', 'phuong dong' ) ),
		'970426' => array( 'MSB', array( 'msb', 'maritime', 'hang hai' ) ),
		'970440' => array( 'SeABank', array( 'seabank', 'dong nam a' ) ),
		'970431' => array( 'Eximbank', array( 'eximbank', 'xuat nhap khau' ) ),
		'970449' => array( 'LPBank', array( 'lpbank', 'lienvietpostbank', 'buu dien lien viet' ) ),
		'970454' => array( 'BVBank', array( 'bvbank', 'viet capital', 'ban viet' ) ),
		'970428' => array( 'Nam A Bank', array( 'nam a', 'namabank' ) ),
		'970412' => array( 'PVcomBank', array( 'pvcombank', 'dai chung' ) ),
		'546034' => array( 'CAKE by VPBank', array( 'cake' ) ),
		'963388' => array( 'Timo', array( 'timo' ) ),
	);
}

/** Dạng banner khuyến mãi trang sản phẩm. */
function tmt_promo_types() {
	return array( 'box' => 'Hộp ưu đãi', 'strip' => 'Dải (có thể đếm ngược)', 'badges' => 'Huy hiệu', 'image' => 'Ảnh', 'coupon' => 'Mã giảm giá' );
}

/** Vị trí banner trên trang sản phẩm. */
function tmt_promo_positions() {
	return array( 'top' => 'Đầu trang', 'gallery' => 'Dưới ảnh', 'above_price' => 'Trên giá', 'below_price' => 'Dưới giá', 'below_buy' => 'Dưới nút mua', 'below_desc' => 'Dưới mô tả' );
}
