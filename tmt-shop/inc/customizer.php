<?php
/**
 * Bảng tùy biến (Giao diện → Tùy biến): thay bảng /tuy-bien-web của web Laravel. Xem trước trực tiếp,
 * bấm "Đăng" mới áp cho khách. Mọi ô khai trong tmt_customizer_sections(), đọc lại bằng tmt_opt().
 *
 * Kiểu ô: text, textarea, number, checkbox, select, radio, color, image, url, repeater (danh sách, lưu JSON), sortable (bật/tắt + thứ tự).
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;

/** Icon Font Awesome gợi ý cho thanh trên cùng, cam kết. */
function tmt_icon_choices() {
	return array(
		'fa-solid fa-location-dot' => 'Địa chỉ', 'fa-solid fa-phone' => 'Điện thoại', 'fa-solid fa-mobile-screen' => 'Di động',
		'fa-solid fa-envelope' => 'Email', 'fa-solid fa-clock' => 'Giờ mở cửa', 'fa-solid fa-truck-fast' => 'Giao hàng',
		'fa-solid fa-shield-halved' => 'Bảo hành', 'fa-solid fa-circle-check' => 'Chính hãng', 'fa-solid fa-gift' => 'Quà tặng',
		'fa-solid fa-tags' => 'Khuyến mãi', 'fa-solid fa-percent' => 'Giảm giá', 'fa-solid fa-fire' => 'Bán chạy',
		'fa-solid fa-bolt' => 'Flash sale', 'fa-solid fa-star' => 'Nổi bật', 'fa-solid fa-award' => 'Uy tín',
		'fa-solid fa-screwdriver-wrench' => 'Sửa chữa', 'fa-solid fa-laptop' => 'Laptop', 'fa-solid fa-desktop' => 'Máy bộ',
		'fa-solid fa-microchip' => 'Linh kiện', 'fa-solid fa-headset' => 'Tư vấn', 'fa-solid fa-comments' => 'Chat',
		'fa-solid fa-store' => 'Cửa hàng', 'fa-solid fa-credit-card' => 'Trả góp', 'fa-solid fa-qrcode' => 'Chuyển khoản QR',
		'fa-solid fa-receipt' => 'Hóa đơn', 'fa-solid fa-certificate' => 'Chứng nhận', 'fa-solid fa-bullhorn' => 'Thông báo',
		'fa-brands fa-facebook' => 'Facebook', 'fa-brands fa-tiktok' => 'TikTok', 'fa-brands fa-youtube' => 'YouTube',
	);
}

/** Các section trang chủ: key => nhãn. Template: template-parts/home/<key>.php. */
function tmt_home_sections() {
	return array(
		'slider'     => 'Slider chính',
		'promises'   => 'Dải cam kết',
		'categories' => 'Danh mục nổi bật',
		'sale'       => 'Khuyến mãi đếm ngược',
		'products'   => 'Sản phẩm mới',
		'posts'      => 'Bài viết',
	);
}

/**
 * Khai báo mọi mục và ô. Mỗi ô: [khóa, kiểu, nhãn, (tùy chọn) choices/fields/desc/min/max].
 *
 * @return array<string, array{title: string, fields: array}>
 */
function tmt_customizer_sections() {
	$yes_no_pos = array( 'left' => 'Trái', 'center' => 'Giữa', 'right' => 'Phải' );
	return array(
		'tmt_shop'    => array(
			'title'  => 'Thông tin cửa hàng',
			'fields' => array(
				array( 'shop.brand', 'text', 'Tên web (cạnh logo)' ),
				array( 'shop.brand_by', 'text', 'Dòng nhỏ dưới tên ("by ...")' ),
				array( 'shop.company', 'text', 'Tên công ty (chân trang)' ),
				array( 'shop.address', 'text', 'Địa chỉ', array( 'desc' => 'Để trống thì lấy địa chỉ ở WooCommerce → Cài đặt.' ) ),
				array( 'shop.phone', 'text', 'Số điện thoại', array( 'desc' => 'Nhiều số cách nhau dấu phẩy; số đầu dùng cho Zalo, nút gọi.' ) ),
				array( 'shop.email', 'text', 'Email' ),
				array( 'shop.tax_code', 'text', 'Mã số thuế' ),
			),
		),
		'tmt_colors'  => array(
			'title'  => 'Màu và font',
			'fields' => array(
				array( 'colors.primary', 'color', 'Màu chính (nút, link)' ),
				array( 'colors.accent', 'color', 'Màu nhấn (nút vàng, dải cam kết)' ),
				array( 'colors.dark', 'color', 'Màu đậm (chữ tiêu đề, thanh trên cùng, chân trang)' ),
				array( 'font', 'select', 'Font chữ', array( 'choices' => array_combine( tmt_fonts(), tmt_fonts() ) ) ),
			),
		),
		'tmt_layout'  => array(
			'title'  => 'Bố cục trang',
			'fields' => array(
				array( 'layout.width', 'radio', 'Bề rộng nội dung', array( 'choices' => array( 'boxed' => 'Có khung (giữa màn hình)', 'full' => 'Giãn hết chiều ngang' ) ) ),
				array( 'layout.max_width', 'number', 'Bề rộng khung (px)', array( 'min' => 960, 'max' => 1920, 'step' => 10 ) ),
				array( 'layout.gutter', 'number', 'Lề hai bên (px)', array( 'min' => 0, 'max' => 80 ) ),
				array( 'layout.stretch_chrome', 'checkbox', 'Khi giãn hết: thanh trên, đầu trang, chân trang cũng giãn' ),
			),
		),
		'tmt_header'  => array(
			'title'  => 'Logo và đầu trang',
			'fields' => array(
				array( 'header.show_search', 'checkbox', 'Hiện ô tìm kiếm' ),
				array( 'header.search_style', 'radio', 'Mẫu ô tìm kiếm', array( 'choices' => array( 'box' => 'Mẫu 1: Khung xanh, nút kính lúp liền khối', 'pill' => 'Mẫu 2: Bo tròn nền xám, nút tròn vàng', 'split' => 'Mẫu 3: Nổi bóng, nút "Tìm kiếm" xanh' ) ) ),
			),
		),
		'tmt_topbar'  => array(
			'title'  => 'Thanh trên cùng',
			'fields' => array(
				array( 'header.show_topbar', 'checkbox', 'Hiện thanh trên cùng' ),
				array(
					'topbar.items',
					'repeater',
					'Các mục',
					array(
						'desc'   => 'Trong chữ: {sdt}, {email}, {dia_chi} thay bằng thông tin cửa hàng. Ô link nhận https://, /đường-dẫn, số điện thoại, {sdt}, {zalo}, {email}.',
						'max'    => 12,
						'add'    => 'Thêm mục',
						'fields' => array(
							'zone'   => array( 'select', 'Vùng', $yes_no_pos ),
							'icon'   => array( 'icon', 'Icon' ),
							'text'   => array( 'text', 'Chữ' ),
							'link'   => array( 'text', 'Link' ),
							'accent' => array( 'checkbox', 'Tô màu nhấn' ),
						),
					),
				),
				array( 'topbar.mobile', 'radio', 'Trên điện thoại', array( 'choices' => array( 'center' => 'Gọn: chỉ vùng giữa (trống thì phải, rồi trái)', 'scroll' => 'Hiện hết trên một dòng, vuốt ngang', 'hide' => 'Ẩn' ) ) ),
			),
		),
		'tmt_nav'     => array(
			'title'  => 'Menu chính',
			'desc'   => 'Mục menu thêm ở Giao diện → Menu, gán vào vị trí "Menu chính". Chưa gán thì theme tự lấy các danh mục sản phẩm cấp 1.',
			'fields' => array(
				array( 'nav.mega_on', 'checkbox', 'Nút ☰ mega menu đầu menu (mọi danh mục + nhóm con)' ),
				array( 'nav.mega_label', 'text', 'Chữ gợi ý của nút ☰' ),
				array( 'nav.drop_on', 'checkbox', 'Mục là danh mục có nhóm con: dấu ⌄ xổ khung nhóm con' ),
				array( 'nav.drop_show', 'radio', 'Dưới mỗi nhóm con hiện', array( 'choices' => array( 'brand' => 'Hãng đang có hàng', 'category' => 'Nhóm con cấp dưới' ) ) ),
				array( 'nav.align', 'radio', 'Canh menu', array( 'choices' => $yes_no_pos ) ),
				array( 'nav.case', 'select', 'Kiểu chữ', array( 'choices' => array( 'none' => 'Giữ như đã nhập', 'upper' => 'IN HOA', 'lower' => 'thường', 'first' => 'Viết hoa chữ đầu' ) ) ),
				array( 'nav.hover', 'select', 'Hiệu ứng rê chuột', array( 'choices' => tmt_nav_hovers() ) ),
				array( 'nav.active', 'select', 'Đánh dấu trang đang mở', array( 'choices' => tmt_nav_actives() ) ),
				array( 'nav.fx_color', 'radio', 'Màu hiệu ứng', array( 'choices' => tmt_nav_fx_colors() ) ),
				array( 'nav.fx_speed', 'radio', 'Tốc độ hiệu ứng', array( 'choices' => tmt_nav_fx_speeds() ) ),
			),
		),
		'tmt_home'    => array(
			'title'  => 'Trang chủ',
			'desc'   => 'Trang chủ dùng giao diện này khi Cài đặt → Đọc chọn "Trang chủ hiển thị: Bài viết mới nhất" hoặc một trang tĩnh bất kỳ.',
			'fields' => array(
				array( 'home.sections', 'sortable', 'Các section (tick để hiện, kéo mũi tên để đổi thứ tự)', array( 'choices' => tmt_home_sections() ) ),
				array( 'home.slider_style', 'radio', 'Kiểu slider', array( 'choices' => array( 'banners' => 'Slider + 2 banner phụ bên phải', 'menu' => 'Menu danh mục dọc + slider + cột hotline' ) ) ),
				array(
					'home.slides',
					'repeater',
					'Ảnh slider (khoảng 1600×600)',
					array(
						'desc'   => 'Chưa có ảnh thì hiện khung chữ "tiêu đề + mô tả" bên dưới.',
						'max'    => 8,
						'add'    => 'Thêm ảnh',
						'fields' => array(
							'image'        => array( 'image', 'Ảnh máy tính' ),
							'image_mobile' => array( 'image', 'Ảnh điện thoại (tùy chọn)' ),
							'link'         => array( 'text', 'Link khi bấm' ),
							'alt'          => array( 'text', 'Mô tả ảnh' ),
						),
					),
				),
				array( 'home.slide_seconds', 'number', 'Giây mỗi ảnh', array( 'min' => 2, 'max' => 15 ) ),
				array(
					'home.banners',
					'repeater',
					'2 banner phụ cạnh slider',
					array(
						'max'    => 2,
						'add'    => 'Thêm banner',
						'fields' => array(
							'image' => array( 'image', 'Ảnh' ),
							'link'  => array( 'text', 'Link' ),
							'alt'   => array( 'text', 'Mô tả ảnh' ),
						),
					),
				),
				array( 'home.hero_title', 'text', 'Tiêu đề khung chữ (khi chưa có ảnh slider)' ),
				array( 'home.hero_text', 'textarea', 'Mô tả khung chữ' ),
				array( 'home.categories', 'text', 'Danh mục nổi bật (slug, cách nhau dấu phẩy)', array( 'desc' => 'Để trống: các danh mục cấp 1.' ) ),
				array( 'home.sale_title', 'text', 'Tiêu đề khuyến mãi' ),
				array( 'home.sale_ends_at', 'text', 'Hết khuyến mãi lúc (YYYY-MM-DD HH:MM)', array( 'desc' => 'Để trống thì không đếm ngược. Hết giờ thì section tự ẩn.' ) ),
				array( 'home.sale_count', 'number', 'Số sản phẩm khuyến mãi', array( 'min' => 2, 'max' => 20 ) ),
				array( 'home.products_title', 'text', 'Tiêu đề sản phẩm mới' ),
				array( 'home.products_count', 'number', 'Số sản phẩm mới', array( 'min' => 4, 'max' => 24 ) ),
				array( 'home.posts_title', 'text', 'Tiêu đề bài viết' ),
				array( 'home.posts_count', 'number', 'Số bài viết', array( 'min' => 2, 'max' => 9 ) ),
			),
		),
		'tmt_listing' => array(
			'title'  => 'Trang danh sách sản phẩm',
			'fields' => array(
				array( 'listing.filter_style', 'radio', 'Kiểu bộ lọc', array( 'choices' => array( 'bar' => 'Thanh ngang (nút thả xuống)', 'sidebar' => 'Cột trái', 'sidebar-right' => 'Cột phải' ) ) ),
				array( 'listing.card_width', 'number', 'Cỡ nhỏ nhất của thẻ (px)', array( 'min' => 160, 'max' => 360, 'desc' => '230px = 4 thẻ một hàng ở khung 1240px. Điện thoại luôn 2 thẻ.' ) ),
				array( 'listing.per_page', 'number', 'Số sản phẩm mỗi trang', array( 'min' => 4, 'max' => 96 ) ),
			),
		),
		'tmt_product' => array(
			'title'  => 'Trang sản phẩm chi tiết',
			'fields' => array(
				array( 'product.variant_style', 'radio', 'Kiểu chọn loại (sản phẩm có biến thể)', array( 'choices' => array( 'buttons' => 'Nút bấm', 'list' => 'Danh sách kèm giá', 'select' => 'Ô chọn của WooCommerce' ) ) ),
				array( 'product.spec_tags', 'number', 'Số nhãn thông số trên ảnh thẻ', array( 'min' => 0, 'max' => 4 ) ),
				array( 'product.discount_badge', 'checkbox', 'Nhãn "GIẢM x%" trên ảnh' ),
				array( 'product.show_zalo', 'checkbox', 'Nút hỏi qua Zalo' ),
				array( 'product.perks', 'textarea', 'Ưu đãi dưới nút mua (mỗi dòng một ý)' ),
				array( 'product.related_count', 'number', 'Số sản phẩm cùng loại', array( 'min' => 0, 'max' => 12 ) ),
				array( 'product.intro_style', 'radio', 'Giới thiệu nâng cao: kiểu hiển thị', array( 'choices' => array( 'full' => 'Toàn khổ, kiểu trang của hãng', 'compact' => 'Gọn trong khung, mục lục bên trái' ), 'desc' => 'Soạn khối ở trang sửa sản phẩm (cần plugin TMT Shop Tools).' ) ),
				array( 'product.intro_accent', 'radio', 'Giới thiệu nâng cao: màu nhấn', array( 'choices' => array( 'blue' => 'Xanh', 'yellow' => 'Vàng' ) ) ),
				array( 'product.intro_nav', 'checkbox', 'Giới thiệu nâng cao: hiện mục lục' ),
			),
		),
		'tmt_promos'  => array(
			'title'  => 'Banner khuyến mãi (trang sản phẩm)',
			'desc'   => 'Mỗi banner chọn dạng, vị trí, màu, sản phẩm áp dụng và lịch chạy. Hết hạn thì tự ẩn.',
			'fields' => array(
				array(
					'product.promos',
					'repeater',
					'Các banner',
					array(
						'max'    => 20,
						'add'    => 'Thêm banner',
						'fields' => array(
							'on'        => array( 'checkbox', 'Đang bật' ),
							'type'      => array( 'select', 'Dạng', tmt_promo_types() ),
							'position'  => array( 'select', 'Vị trí', tmt_promo_positions() ),
							'style'     => array( 'select', 'Màu', array( 'red' => 'Đỏ sale', 'yellow' => 'Vàng', 'blue' => 'Xanh', 'dark' => 'Tối' ) ),
							'title'     => array( 'text', 'Tiêu đề' ),
							'text'      => array( 'text', 'Dòng phụ' ),
							'lines'     => array( 'textarea', 'Các ý (mỗi dòng một ý; hộp ưu đãi, huy hiệu)' ),
							'code'      => array( 'text', 'Mã giảm giá (dạng Mã giảm giá)' ),
							'image'     => array( 'image', 'Ảnh (dạng Ảnh)' ),
							'link'      => array( 'text', 'Link khi bấm' ),
							'link_text' => array( 'text', 'Chữ nút / link' ),
							'scope'     => array( 'select', 'Áp dụng cho', array( 'all' => 'Mọi sản phẩm', 'categories' => 'Danh mục (slug)', 'products' => 'Sản phẩm (ID hoặc mã SKU)' ) ),
							'targets'   => array( 'text', 'Danh mục / sản phẩm, cách nhau dấu phẩy' ),
							'from'      => array( 'text', 'Bắt đầu (YYYY-MM-DD HH:MM, trống = ngay)' ),
							'to'        => array( 'text', 'Kết thúc (YYYY-MM-DD HH:MM, trống = không hạn)' ),
							'countdown' => array( 'checkbox', 'Đếm ngược tới lúc kết thúc' ),
						),
					),
				),
			),
		),
		'tmt_checkout' => array(
			'title'  => 'Giỏ hàng & thanh toán',
			'desc'   => 'Tài khoản nhận tiền nhập ở WooCommerce → Cài đặt → Thanh toán → Chuyển khoản ngân hàng. Phí giao, nhận tại tiệm, miễn phí giao từ x đồng: WooCommerce → Cài đặt → Giao hàng.',
			'fields' => array(
				array( 'checkout.note', 'textarea', 'Ghi chú dưới nút Đặt hàng' ),
				array( 'payment.bank_bin', 'select', 'Ngân hàng nhận (cho mã QR)', array( 'choices' => array( '' => 'Tự nhận theo tên ngân hàng đã nhập' ) + array_map( function ( $b ) { return $b[0]; }, tmt_vietqr_banks() ) ) ),
				array( 'payment.qr_prefix', 'text', 'Chữ đầu nội dung chuyển khoản (vd. DH → "DH 1024")' ),
			),
		),
		'tmt_footer'  => array(
			'title'  => 'Chân trang',
			'fields' => array(
				array( 'footer.promises_on', 'checkbox', 'Dải cam kết vàng' ),
				array(
					'footer.promise_items',
					'repeater',
					'Ô cam kết (tối đa 4, dùng chung cho section Dải cam kết ở trang chủ)',
					array(
						'max'    => 4,
						'add'    => 'Thêm ô',
						'fields' => array(
							'icon'  => array( 'icon', 'Icon' ),
							'title' => array( 'text', 'Tiêu đề' ),
							'sub'   => array( 'text', 'Dòng phụ' ),
						),
					),
				),
				array( 'footer.about', 'textarea', 'Giới thiệu ngắn dưới logo' ),
				array( 'footer.hours', 'text', 'Giờ mở cửa' ),
				array( 'footer.shipping', 'text', 'Dòng giao hàng (cột Hỗ trợ)' ),
				array( 'footer.map', 'checkbox', 'Bản đồ Google ghim tiệm' ),
				array( 'footer.map_query', 'text', 'Địa chỉ ghim trên bản đồ', array( 'desc' => 'Để trống thì dùng địa chỉ cửa hàng.' ) ),
				array( 'footer.newsletter', 'checkbox', 'Ô nhận tin (thả widget form vào vùng "Chân trang: ô nhận tin")' ),
				array( 'footer.zalo', 'text', 'Zalo (số hoặc link, trống = số tiệm)' ),
				array( 'footer.facebook', 'url', 'Facebook (https://...)' ),
				array( 'footer.tiktok', 'url', 'TikTok (https://...)' ),
				array( 'footer.youtube', 'url', 'YouTube (https://...)' ),
			),
		),
		'tmt_contact' => array(
			'title'  => 'Nút liên hệ nổi',
			'fields' => array(
				array( 'contact.enabled', 'checkbox', 'Hiện cụm nút góc dưới phải' ),
				array( 'contact.hours', 'text', 'Giờ làm việc' ),
				array( 'contact.hotline_on', 'checkbox', 'Nút Hotline' ),
				array( 'contact.hotline', 'text', 'Số hotline (trống = số tiệm)' ),
				array( 'contact.zalo_on', 'checkbox', 'Nút Chat Zalo' ),
				array( 'contact.zalo', 'text', 'Số hoặc link Zalo (trống = số tiệm)' ),
				array( 'contact.messenger_on', 'checkbox', 'Nút Chat Messenger' ),
				array( 'contact.messenger', 'url', 'Link Messenger (https://m.me/...)' ),
			),
		),
		'tmt_banners' => array(
			'title'  => 'Banner dọc hai bên',
			'desc'   => 'Chỉ hiện trên màn hình rộng, khi lề hai bên khung nội dung đủ chỗ. Ảnh dọc khoảng 1:3.',
			'fields' => array(
				array( 'banners.left_on', 'checkbox', 'Bên trái' ),
				array( 'banners.left_image', 'image', 'Ảnh bên trái' ),
				array( 'banners.left_link', 'text', 'Link bên trái' ),
				array( 'banners.right_on', 'checkbox', 'Bên phải' ),
				array( 'banners.right_image', 'image', 'Ảnh bên phải' ),
				array( 'banners.right_link', 'text', 'Link bên phải' ),
			),
		),
		'tmt_effects' => array(
			'title'  => 'Hiệu ứng',
			'fields' => array(
				array( 'effects.product_hover', 'select', 'Rê chuột vào thẻ sản phẩm', array( 'choices' => tmt_card_effects() ) ),
				array( 'effects.post_hover', 'select', 'Rê chuột vào thẻ bài viết', array( 'choices' => array_diff_key( tmt_card_effects(), array( 'swap' => 1 ) ) ) ),
				array( 'effects.intensity', 'radio', 'Cường độ', array( 'choices' => array( 'soft' => 'Nhẹ', 'medium' => 'Vừa', 'strong' => 'Mạnh' ) ) ),
				array( 'effects.cursor', 'select', 'Con trỏ chuột', array( 'choices' => tmt_cursors(), 'desc' => 'Chỉ máy tính có chuột; điện thoại giữ như cũ.' ) ),
			),
		),
	);
}

add_action( 'customize_register', 'tmt_customize_register' );
function tmt_customize_register( WP_Customize_Manager $wp ) {
	require_once TMT_SHOP_DIR . '/inc/customizer-controls.php';

	$wp->add_panel( 'tmt_panel', array( 'title' => 'TMT Shop', 'priority' => 20 ) );
	$defaults = tmt_defaults();
	$priority = 10;

	foreach ( tmt_customizer_sections() as $section_id => $section ) {
		$wp->add_section(
			$section_id,
			array(
				'title'       => $section['title'],
				'panel'       => 'tmt_panel',
				'priority'    => $priority += 5,
				'description' => isset( $section['desc'] ) ? $section['desc'] : '',
			)
		);
		foreach ( $section['fields'] as $f ) {
			list( $key, $type, $label ) = $f;
			$opt     = isset( $f[3] ) ? $f[3] : array();
			$id      = tmt_mod_name( $key );
			$default = isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
			if ( is_array( $default ) ) {
				$default = wp_json_encode( $default );
			}
			$wp->add_setting(
				$id,
				array(
					'default'           => $default,
					'transport'         => in_array( $type, array( 'color' ), true ) ? 'postMessage' : 'refresh',
					'sanitize_callback' => tmt_sanitizer( $type, $opt ),
				)
			);
			$args = array(
				'label'       => $label,
				'section'     => $section_id,
				'description' => isset( $opt['desc'] ) ? $opt['desc'] : '',
			);
			switch ( $type ) {
				case 'color':
					$wp->add_control( new WP_Customize_Color_Control( $wp, $id, $args ) );
					break;
				case 'image':
					$wp->add_control( new WP_Customize_Image_Control( $wp, $id, $args ) );
					break;
				case 'repeater':
					$wp->add_control( new TMT_Repeater_Control( $wp, $id, $args + array( 'fields' => $opt['fields'], 'max' => isset( $opt['max'] ) ? $opt['max'] : 10, 'add_label' => isset( $opt['add'] ) ? $opt['add'] : 'Thêm' ) ) );
					break;
				case 'sortable':
					$wp->add_control( new TMT_Sortable_Control( $wp, $id, $args + array( 'choices' => $opt['choices'] ) ) );
					break;
				default:
					$args['type'] = 'url' === $type ? 'url' : $type;
					if ( isset( $opt['choices'] ) ) {
						$args['choices'] = $opt['choices'];
					}
					if ( 'number' === $type ) {
						$args['input_attrs'] = array_intersect_key( $opt, array_flip( array( 'min', 'max', 'step' ) ) );
					}
					$wp->add_control( $id, $args );
			}
		}
	}

	// Tên web, logo: đưa logo vào mục Thông tin cửa hàng cho dễ tìm.
	if ( $wp->get_control( 'custom_logo' ) ) {
		$wp->get_control( 'custom_logo' )->section  = 'tmt_header';
		$wp->get_control( 'custom_logo' )->priority = 1;
	}
}

/**
 * Hàm làm sạch theo kiểu ô.
 *
 * @param string $type Kiểu.
 * @param array  $opt  Tùy chọn.
 * @return callable
 */
function tmt_sanitizer( $type, array $opt ) {
	switch ( $type ) {
		case 'checkbox':
			return function ( $v ) {
				return (bool) $v;
			};
		case 'number':
			return function ( $v ) use ( $opt ) {
				$v = (int) $v;
				if ( isset( $opt['min'] ) ) {
					$v = max( (int) $opt['min'], $v );
				}
				if ( isset( $opt['max'] ) ) {
					$v = min( (int) $opt['max'], $v );
				}
				return $v;
			};
		case 'color':
			return 'sanitize_hex_color';
		case 'image':
		case 'url':
			return 'esc_url_raw';
		case 'textarea':
			return 'sanitize_textarea_field';
		case 'select':
		case 'radio':
			return function ( $v, $setting ) use ( $opt ) {
				return isset( $opt['choices'][ $v ] ) ? $v : $setting->default;
			};
		case 'sortable':
			return function ( $v ) use ( $opt ) {
				$list = json_decode( (string) $v, true );
				$list = is_array( $list ) ? array_values( array_intersect( $list, array_keys( $opt['choices'] ) ) ) : array();
				return wp_json_encode( array_values( array_unique( $list ) ) );
			};
		case 'repeater':
			return function ( $v ) use ( $opt ) {
				$rows = json_decode( (string) $v, true );
				$out  = array();
				foreach ( is_array( $rows ) ? $rows : array() as $row ) {
					if ( ! is_array( $row ) ) {
						continue;
					}
					$clean = array();
					foreach ( $opt['fields'] as $name => $f ) {
						$val = isset( $row[ $name ] ) ? $row[ $name ] : '';
						switch ( $f[0] ) {
							case 'checkbox':
								$clean[ $name ] = (bool) $val;
								break;
							case 'image':
								$clean[ $name ] = esc_url_raw( (string) $val );
								break;
							case 'textarea':
								$clean[ $name ] = sanitize_textarea_field( (string) $val );
								break;
							case 'icon':
								$clean[ $name ] = (string) tmt_safe_icon( $val );
								break;
							case 'select':
								$clean[ $name ] = isset( $f[2][ $val ] ) ? $val : (string) key( $f[2] );
								break;
							default:
								$clean[ $name ] = sanitize_text_field( (string) $val );
						}
					}
					$out[] = $clean;
				}
				return wp_json_encode( array_slice( $out, 0, isset( $opt['max'] ) ? (int) $opt['max'] : 20 ) );
			};
		default:
			return 'sanitize_text_field';
	}
}

/** Xem trước trực tiếp màu (không tải lại khung). */
add_action(
	'customize_preview_init',
	function () {
		wp_enqueue_script( 'tmt-customize-preview', TMT_SHOP_URI . '/assets/js/customize-preview.js', array( 'customize-preview' ), TMT_SHOP_VERSION, true );
	}
);

add_action(
	'customize_controls_enqueue_scripts',
	function () {
		wp_enqueue_media();
		wp_enqueue_style( 'tmt-fontawesome', TMT_SHOP_URI . '/assets/vendor/fontawesome/css/all.min.css', array(), '7.3.1' );
		wp_enqueue_style( 'tmt-customize-controls', TMT_SHOP_URI . '/assets/css/customize-controls.css', array(), TMT_SHOP_VERSION );
		wp_enqueue_script( 'tmt-customize-controls', TMT_SHOP_URI . '/assets/js/customize-controls.js', array( 'customize-controls', 'jquery' ), TMT_SHOP_VERSION, true );
		wp_localize_script( 'tmt-customize-controls', 'tmtIcons', tmt_icon_choices() );
	}
);
