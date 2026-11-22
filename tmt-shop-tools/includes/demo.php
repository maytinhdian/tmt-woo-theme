<?php
/**
 * Dữ liệu mẫu: một nút nạp sẵn danh mục, ~25 sản phẩm có thông số (lắp được cấu hình), 2 cấu hình mẫu,
 * 6 bài Mẹo vặt, 2 trang Giải pháp, 1 trang Phần mềm, trang chủ + trang Mẹo vặt, menu chính.
 * Mọi thứ nạp vào đều đánh dấu _tmt_demo để nút "Xóa dữ liệu mẫu" gỡ sạch (đồ chủ tiệm tự tạo không bị đụng).
 * Trang: WooCommerce → Dữ liệu mẫu.
 *
 * @package TMT_Shop_Tools
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'woocommerce', 'Dữ liệu mẫu', 'Dữ liệu mẫu', 'manage_woocommerce', 'tmt-demo', 'tmt_demo_page' );
	},
	60
);

function tmt_demo_page() {
	$done = get_option( 'tmt_demo_done' );
	$msg  = isset( $_GET['tmt_msg'] ) ? sanitize_key( $_GET['tmt_msg'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	?>
	<div class="wrap">
		<h1>Dữ liệu mẫu</h1>
		<?php if ( 'nap' === $msg ) : ?><div class="notice notice-success"><p>Đã nạp dữ liệu mẫu. <a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank">Xem web</a></p></div><?php endif; ?>
		<?php if ( 'xoa' === $msg ) : ?><div class="notice notice-success"><p>Đã xóa dữ liệu mẫu.</p></div><?php endif; ?>
		<p>Nạp sẵn để xem web chạy thế nào trước khi nhập hàng thật:</p>
		<ul style="list-style:disc;padding-left:20px">
			<li>Danh mục linh kiện, laptop, màn hình, camera, máy chấm công, thiết bị mạng.</li>
			<li>Khoảng 25 sản phẩm có giá, tồn kho, bảo hành và thông số (lắp thử được ở trang Xây dựng cấu hình), 1 sản phẩm chưa có giá để thử "Yêu cầu báo giá", 1 sản phẩm ngừng kinh doanh, laptop có "Giới thiệu nâng cao" và quà tặng kèm.</li>
			<li>6 phần mềm bản quyền (Windows, Office, Diệt virus) kèm 3 khe phần mềm ở Lên cấu hình, 2 cấu hình mẫu (Văn phòng, Chơi game), 6 bài Mẹo vặt, trang Giải pháp Camera + Mạng WiFi, trang Phần mềm chấm công.</li>
			<li>Nếu web chưa có: trang chủ tĩnh + trang Mẹo vặt, menu chính, tiền tệ VNĐ.</li>
		</ul>
		<p>Đồ anh chị tự tạo không bị đụng tới. Bấm "Xóa dữ liệu mẫu" là gỡ đúng những gì đã nạp.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:8px">
			<?php wp_nonce_field( 'tmt_demo' ); ?>
			<input type="hidden" name="action" value="tmt_demo_import">
			<?php submit_button( $done ? 'Nạp lại dữ liệu mẫu' : 'Nạp dữ liệu mẫu', 'primary', 'submit', false ); ?>
		</form>
		<?php if ( $done ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block" onsubmit="return confirm('Xóa toàn bộ dữ liệu mẫu đã nạp?')">
				<?php wp_nonce_field( 'tmt_demo' ); ?>
				<input type="hidden" name="action" value="tmt_demo_remove">
				<?php submit_button( 'Xóa dữ liệu mẫu', 'delete', 'submit', false ); ?>
			</form>
			<p class="description">Đã nạp lúc <?php echo esc_html( wp_date( 'H:i d/m/Y', (int) $done ) ); ?>.</p>
		<?php endif; ?>
	</div>
	<?php
}

add_action(
	'admin_post_tmt_demo_import',
	function () {
		check_admin_referer( 'tmt_demo' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'Không có quyền.' );
		}
		tmt_demo_import();
		wp_safe_redirect( admin_url( 'admin.php?page=tmt-demo&tmt_msg=nap' ) );
		exit;
	}
);

add_action(
	'admin_post_tmt_demo_remove',
	function () {
		check_admin_referer( 'tmt_demo' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'Không có quyền.' );
		}
		tmt_demo_remove();
		wp_safe_redirect( admin_url( 'admin.php?page=tmt-demo&tmt_msg=xoa' ) );
		exit;
	}
);

/* ---------- Hàm tạo, đánh dấu để xóa được ---------- */

/** Bài / trang / sản phẩm mẫu đã có (theo slug), để nạp lại không bị trùng. */
function tmt_demo_find( $type, $slug ) {
	$p = get_posts( array( 'post_type' => $type, 'name' => $slug, 'post_status' => 'any', 'numberposts' => 1 ) );
	return $p ? $p[0]->ID : 0;
}

function tmt_demo_post( $type, $title, $args = array() ) {
	$slug = isset( $args['post_name'] ) ? $args['post_name'] : sanitize_title( remove_accents( $title ) );
	$id   = tmt_demo_find( $type, $slug );
	if ( $id && ! get_post_meta( $id, '_tmt_demo', true ) ) {
		return $id; // Chủ tiệm đã có trang này: giữ nguyên.
	}
	$data = array_merge( array( 'post_type' => $type, 'post_title' => $title, 'post_name' => $slug, 'post_status' => 'publish' ), $args );
	if ( $id ) {
		$data['ID'] = $id;
	}
	$id = wp_insert_post( wp_slash( $data ) );
	update_post_meta( $id, '_tmt_demo', 1 );
	return $id;
}

function tmt_demo_term( $tax, $name, $slug, $parent = 0 ) {
	$t = term_exists( $slug, $tax );
	if ( ! $t ) {
		$t = wp_insert_term( $name, $tax, array( 'slug' => $slug, 'parent' => $parent ) );
		if ( is_wp_error( $t ) ) {
			return 0;
		}
		update_term_meta( $t['term_id'], '_tmt_demo', 1 );
	}
	return (int) $t['term_id'];
}

/** Slug thuộc tính WooCommerce đang dùng cho một thông số (kể cả tên khác như loai-ram). */
function tmt_demo_attr_slug( $key ) {
	$slug = tmt_pcb_attr_slug( $key );
	if ( wc_attribute_taxonomy_id_by_name( $slug ) ) {
		return $slug;
	}
	$aliases = tmt_pcb_spec_aliases();
	foreach ( isset( $aliases[ $key ] ) ? $aliases[ $key ] : array() as $a ) {
		if ( wc_attribute_taxonomy_id_by_name( $a ) ) {
			return $a;
		}
	}
	return $slug;
}

/** Thuộc tính chung (tạo nếu chưa có). */
function tmt_demo_attribute( $slug, $label ) {
	if ( ! wc_attribute_taxonomy_id_by_name( $slug ) ) {
		wc_create_attribute( array( 'name' => $label, 'slug' => $slug, 'type' => 'select' ) );
		delete_transient( 'wc_attribute_taxonomies' );
	}
	if ( ! taxonomy_exists( 'pa_' . $slug ) ) {
		register_taxonomy( 'pa_' . $slug, 'product', array( 'hierarchical' => false, 'show_ui' => false, 'rewrite' => false ) );
	}
}

/**
 * Sản phẩm đơn giản mẫu.
 *
 * @param array $p name, sku, cat (id), price, sale, stock, bh, specs (khóa thông số => giá trị), attrs (slug => giá trị), brand, desc.
 */
function tmt_demo_product( array $p ) {
	$id      = wc_get_product_id_by_sku( $p['sku'] );
	$product = $id ? wc_get_product( $id ) : new WC_Product_Simple();
	if ( $id && ! get_post_meta( $id, '_tmt_demo', true ) ) {
		return $id;
	}
	$product->set_name( $p['name'] );
	$product->set_status( 'publish' );
	$product->set_sku( $p['sku'] );
	$product->set_category_ids( array( $p['cat'] ) );
	$product->set_regular_price( isset( $p['price'] ) ? (string) $p['price'] : '' );
	$product->set_sale_price( ! empty( $p['sale'] ) ? (string) $p['sale'] : '' );
	$product->set_manage_stock( true );
	$product->set_stock_quantity( isset( $p['stock'] ) ? $p['stock'] : 8 );
	$product->set_backorders( 'notify' );
	$product->set_short_description( isset( $p['short'] ) ? $p['short'] : '' );
	$product->set_description( isset( $p['desc'] ) ? $p['desc'] : '<p>' . esc_html( $p['name'] ) . ' chính hãng, mới 100%, có serial, bảo hành tại tiệm.</p><h2>Điểm nổi bật</h2><ul><li>Hàng chính hãng, xuất hóa đơn được</li><li>Giao trong ngày ở nội thành</li><li>Đổi mới trong 7 ngày nếu lỗi do nhà sản xuất</li></ul>' );
	$attrs = array();
	$all   = array();
	foreach ( isset( $p['specs'] ) ? $p['specs'] : array() as $key => $v ) {
		$all[ tmt_demo_attr_slug( $key ) ] = $v;
	}
	foreach ( isset( $p['attrs'] ) ? $p['attrs'] : array() as $slug => $v ) {
		$all[ $slug ] = $v;
	}
	foreach ( $all as $slug => $values ) {
		$tax = 'pa_' . $slug;
		$ids = array();
		foreach ( (array) $values as $v ) {
			$t = term_exists( (string) $v, $tax );
			if ( ! $t ) {
				$t = wp_insert_term( (string) $v, $tax );
			}
			if ( ! is_wp_error( $t ) ) {
				$ids[] = (int) $t['term_id'];
			}
		}
		$a = new WC_Product_Attribute();
		$a->set_id( wc_attribute_taxonomy_id_by_name( $slug ) );
		$a->set_name( $tax );
		$a->set_options( $ids );
		$a->set_visible( true );
		$attrs[ $tax ] = $a;
	}
	$product->set_attributes( $attrs );
	$product->update_meta_data( '_tmt_warranty_months', isset( $p['bh'] ) ? $p['bh'] : 36 );
	$product->update_meta_data( '_tmt_demo', 1 );
	$id = $product->save();
	if ( ! empty( $p['brand'] ) && taxonomy_exists( 'product_brand' ) ) {
		wp_set_object_terms( $id, array( tmt_demo_term( 'product_brand', $p['brand'], sanitize_title( $p['brand'] ) ) ), 'product_brand' );
	}
	return $id;
}

/* ---------- Nạp ---------- */

function tmt_demo_import() {
	if ( function_exists( 'set_time_limit' ) ) {
		set_time_limit( 300 ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions
	}
	// Tiền VNĐ, không số lẻ.
	if ( ! get_option( 'tmt_demo_done' ) ) {
		update_option( 'woocommerce_currency', 'VND' );
		update_option( 'woocommerce_price_num_decimals', 0 );
		update_option( 'woocommerce_price_thousand_sep', '.' );
		update_option( 'woocommerce_price_decimal_sep', ',' );
		update_option( 'woocommerce_currency_pos', 'right' );
	}

	// Danh mục.
	$c       = array();
	$lk      = tmt_demo_term( 'product_cat', 'Linh kiện', 'linh-kien' );
	$cats    = array(
		'cpu' => 'CPU', 'mainboard' => 'Mainboard', 'ram' => 'RAM', 'ssd' => 'SSD', 'card-do-hoa' => 'Card đồ họa',
		'nguon' => 'Nguồn', 'vo-case' => 'Vỏ case', 'tan-nhiet' => 'Tản nhiệt',
	);
	foreach ( $cats as $slug => $name ) {
		$c[ $slug ] = tmt_demo_term( 'product_cat', $name, $slug, $lk );
	}
	foreach ( array( 'man-hinh' => 'Màn hình', 'ban-phim' => 'Bàn phím', 'chuot' => 'Chuột', 'laptop' => 'Laptop', 'camera' => 'Camera', 'may-cham-cong' => 'Máy chấm công', 'thiet-bi-mang' => 'Thiết bị mạng' ) as $slug => $name ) {
		$c[ $slug ] = tmt_demo_term( 'product_cat', $name, $slug );
	}

	$pm = tmt_demo_term( 'product_cat', 'Phần mềm bản quyền', 'phan-mem-ban-quyen' );
	foreach ( array( 'windows' => 'Windows', 'office' => 'Office', 'diet-virus' => 'Diệt virus' ) as $slug => $name ) {
		$c[ $slug ] = tmt_demo_term( 'product_cat', $name, $slug, $pm );
	}

	// Thuộc tính thông số.
	tmt_pcb_create_attributes();
	foreach ( array( 'dung-luong' => 'Dung lượng', 'kich-thuoc-man-hinh' => 'Kích thước màn hình', 'do-phan-giai' => 'Độ phân giải', 'so-cong' => 'Số cổng' ) as $slug => $label ) {
		tmt_demo_attribute( $slug, $label );
	}

	$items = array(
		array( 'name' => 'CPU Intel Core i3-12100F', 'sku' => 'CPU-IN-I3-12100F', 'cat' => $c['cpu'], 'price' => 1990000, 'brand' => 'Intel', 'specs' => array( 'socket' => 'LGA1700', 'tdp' => '58', 'igpu' => 'Không', 'cooler_included' => 'Có', 'cpu_generation' => 'Intel Core 12' ) ),
		array( 'name' => 'CPU Intel Core i5-12400F', 'sku' => 'CPU-IN-I5-12400F', 'cat' => $c['cpu'], 'price' => 3290000, 'sale' => 2990000, 'brand' => 'Intel', 'specs' => array( 'socket' => 'LGA1700', 'tdp' => '65', 'igpu' => 'Không', 'cooler_included' => 'Có', 'cpu_generation' => 'Intel Core 12' ) ),
		array( 'name' => 'CPU Intel Core i5-12400', 'sku' => 'CPU-IN-I5-12400', 'cat' => $c['cpu'], 'price' => 3890000, 'brand' => 'Intel', 'specs' => array( 'socket' => 'LGA1700', 'tdp' => '65', 'igpu' => 'Có', 'cooler_included' => 'Có', 'cpu_generation' => 'Intel Core 12' ) ),
		array( 'name' => 'CPU AMD Ryzen 5 7600', 'sku' => 'CPU-AMD-R5-7600', 'cat' => $c['cpu'], 'price' => 4990000, 'sale' => 4590000, 'brand' => 'AMD', 'specs' => array( 'socket' => 'AM5', 'tdp' => '65', 'igpu' => 'Có', 'cooler_included' => 'Có', 'cpu_generation' => 'AMD Ryzen 7000' ) ),
		array( 'name' => 'Mainboard MSI PRO H610M-E DDR4', 'sku' => 'MB-MSI-H610M-E', 'cat' => $c['mainboard'], 'price' => 1790000, 'brand' => 'MSI', 'specs' => array( 'socket' => 'LGA1700', 'ram_type' => 'DDR4', 'ram_slots' => '2', 'm2_slots' => '1', 'form_factor' => 'mATX', 'chipset' => 'H610', 'sata_ports' => '4', 'max_ram_speed' => '3200' ) ),
		array( 'name' => 'Mainboard ASUS PRIME B760M-A D4', 'sku' => 'MB-AS-B760M-A', 'cat' => $c['mainboard'], 'price' => 3090000, 'sale' => 2890000, 'brand' => 'ASUS', 'specs' => array( 'socket' => 'LGA1700', 'ram_type' => 'DDR4', 'ram_slots' => '4', 'm2_slots' => '2', 'form_factor' => 'mATX', 'chipset' => 'B760', 'sata_ports' => '4', 'max_ram_speed' => '3200' ) ),
		array( 'name' => 'Mainboard Gigabyte B650M DS3H', 'sku' => 'MB-GB-B650M-DS3H', 'cat' => $c['mainboard'], 'price' => 3290000, 'brand' => 'Gigabyte', 'specs' => array( 'socket' => 'AM5', 'ram_type' => 'DDR5', 'ram_slots' => '4', 'm2_slots' => '2', 'form_factor' => 'mATX', 'chipset' => 'B650', 'sata_ports' => '4', 'max_ram_speed' => '6400' ) ),
		array( 'name' => 'RAM Kingston 8GB DDR4 3200', 'sku' => 'RAM-KS-8G-D4-3200', 'cat' => $c['ram'], 'price' => 590000, 'brand' => 'Kingston', 'specs' => array( 'ram_type' => 'DDR4', 'ram_sticks' => '1', 'ram_speed' => '3200' ), 'attrs' => array( 'dung-luong' => '8GB' ) ),
		array( 'name' => 'RAM Kingston Fury 16GB DDR4 3200', 'sku' => 'RAM-KS-16G-D4-3200', 'cat' => $c['ram'], 'price' => 990000, 'brand' => 'Kingston', 'specs' => array( 'ram_type' => 'DDR4', 'ram_sticks' => '1', 'ram_speed' => '3200' ), 'attrs' => array( 'dung-luong' => '16GB' ) ),
		array( 'name' => 'RAM Kingston Fury 16GB DDR5 5600', 'sku' => 'RAM-KS-16G-D5-5600', 'cat' => $c['ram'], 'price' => 1390000, 'sale' => 1190000, 'brand' => 'Kingston', 'specs' => array( 'ram_type' => 'DDR5', 'ram_sticks' => '1', 'ram_speed' => '5600' ), 'attrs' => array( 'dung-luong' => '16GB' ) ),
		array( 'name' => 'SSD Kingston NV3 500GB NVMe', 'sku' => 'SSD-KS-NV3-500', 'cat' => $c['ssd'], 'price' => 890000, 'brand' => 'Kingston', 'specs' => array( 'storage_interface' => 'NVMe' ), 'attrs' => array( 'dung-luong' => '500GB' ) ),
		array( 'name' => 'SSD Samsung 990 Pro 1TB NVMe', 'sku' => 'SSD-SS-990PRO-1T', 'cat' => $c['ssd'], 'price' => 2890000, 'sale' => 2490000, 'bh' => 60, 'brand' => 'Samsung', 'specs' => array( 'storage_interface' => 'NVMe' ), 'attrs' => array( 'dung-luong' => '1TB' ) ),
		array( 'name' => 'Card MSI GeForce RTX 4060 Ventus 2X 8GB', 'sku' => 'VGA-MSI-4060-V2X', 'cat' => $c['card-do-hoa'], 'price' => 8490000, 'sale' => 7990000, 'brand' => 'MSI', 'specs' => array( 'tdp' => '115', 'gpu_length' => '199', 'psu_recommended' => '550', 'gpu_power_connector' => '8 pin' ) ),
		array( 'name' => 'Card ASUS GeForce GTX 1650 4GB', 'sku' => 'VGA-AS-1650-4G', 'cat' => $c['card-do-hoa'], 'price' => 3490000, 'brand' => 'ASUS', 'specs' => array( 'tdp' => '75', 'gpu_length' => '173', 'psu_recommended' => '300', 'gpu_power_connector' => 'Không' ) ),
		array( 'name' => 'Nguồn Xigmatek X-Power 450W', 'sku' => 'PSU-XG-450', 'cat' => $c['nguon'], 'price' => 590000, 'bh' => 24, 'brand' => 'Xigmatek', 'specs' => array( 'psu_watt' => '450', 'psu_pcie_8pin' => '1', 'psu_12vhpwr' => 'Không' ) ),
		array( 'name' => 'Nguồn Deepcool PK650D 650W 80 Plus Bronze', 'sku' => 'PSU-DC-PK650D', 'cat' => $c['nguon'], 'price' => 1390000, 'brand' => 'Deepcool', 'specs' => array( 'psu_watt' => '650', 'psu_pcie_8pin' => '2', 'psu_12vhpwr' => 'Không' ) ),
		array( 'name' => 'Vỏ Xigmatek NYX 3F mATX', 'sku' => 'CASE-XG-NYX3F', 'cat' => $c['vo-case'], 'price' => 690000, 'bh' => 12, 'brand' => 'Xigmatek', 'specs' => array( 'form_factor' => 'mATX', 'max_gpu_length' => '280', 'max_cooler_height' => '155' ) ),
		array( 'name' => 'Vỏ Corsair 4000D Airflow ATX', 'sku' => 'CASE-CS-4000D', 'cat' => $c['vo-case'], 'price' => 2290000, 'bh' => 24, 'brand' => 'Corsair', 'specs' => array( 'form_factor' => 'ATX', 'max_gpu_length' => '360', 'max_cooler_height' => '170' ) ),
		array( 'name' => 'Tản nhiệt ID-Cooling SE-214-XT', 'sku' => 'TN-ID-SE214XT', 'cat' => $c['tan-nhiet'], 'price' => 390000, 'bh' => 12, 'brand' => 'ID-Cooling', 'specs' => array( 'cooler_sockets' => array( 'LGA1700', 'AM4', 'AM5' ), 'tdp' => '180', 'cooler_height' => '150' ) ),
		array( 'name' => 'Màn hình Dell P2422H 24 inch IPS', 'sku' => 'MH-DL-P2422H', 'cat' => $c['man-hinh'], 'price' => 4290000, 'brand' => 'Dell', 'attrs' => array( 'kich-thuoc-man-hinh' => '24 inch', 'do-phan-giai' => 'Full HD' ) ),
		array( 'name' => 'Màn hình LG 27MP400 27 inch IPS', 'sku' => 'MH-LG-27MP400', 'cat' => $c['man-hinh'], 'price' => 3390000, 'sale' => 3090000, 'brand' => 'LG', 'attrs' => array( 'kich-thuoc-man-hinh' => '27 inch', 'do-phan-giai' => 'Full HD' ) ),
		array( 'name' => 'Bàn phím Logitech K120', 'sku' => 'BP-LG-K120', 'cat' => $c['ban-phim'], 'price' => 150000, 'bh' => 12, 'brand' => 'Logitech' ),
		array( 'name' => 'Chuột Logitech B100', 'sku' => 'CH-LG-B100', 'cat' => $c['chuot'], 'price' => 90000, 'bh' => 12, 'brand' => 'Logitech' ),
		array( 'name' => 'Laptop Dell Inspiron 15 3530 i5', 'sku' => 'LT-DL-15-3530-I5', 'cat' => $c['laptop'], 'price' => 15990000, 'sale' => 14990000, 'bh' => 12, 'brand' => 'Dell', 'attrs' => array( 'kich-thuoc-man-hinh' => '15.6 inch', 'do-phan-giai' => 'Full HD' ) ),
		array( 'name' => 'Camera WiFi Imou Ranger 2 1080P', 'sku' => 'CAM-IM-RANGER2', 'cat' => $c['camera'], 'price' => 590000, 'bh' => 24, 'brand' => 'Imou', 'attrs' => array( 'do-phan-giai' => '2MP' ) ),
		array( 'name' => 'Đầu ghi Hikvision 8 kênh DS-7208HGHI', 'sku' => 'DG-HIK-7208', 'cat' => $c['camera'], 'price' => '', 'bh' => 24, 'brand' => 'Hikvision', 'short' => 'Giá thay đổi theo số camera và ổ cứng đi kèm, tiệm báo giá theo nhu cầu.' ),
		array( 'name' => 'Máy chấm công khuôn mặt ZKTeco MB360', 'sku' => 'CC-ZK-MB360', 'cat' => $c['may-cham-cong'], 'price' => 3490000, 'bh' => 12, 'brand' => 'ZKTeco' ),
		array( 'name' => 'Router WiFi TP-Link Archer AX23', 'sku' => 'NET-TP-AX23', 'cat' => $c['thiet-bi-mang'], 'price' => 990000, 'bh' => 24, 'brand' => 'TP-Link' ),
		array( 'name' => 'Switch TP-Link 8 cổng Gigabit TL-SG1008D', 'sku' => 'NET-TP-SG1008D', 'cat' => $c['thiet-bi-mang'], 'price' => 390000, 'sale' => 350000, 'bh' => 24, 'brand' => 'TP-Link', 'attrs' => array( 'so-cong' => '8 cổng' ) ),
	);
	$key  = '<p>Key bản quyền chính hãng Microsoft / hãng phần mềm, kích hoạt online, dùng mãi trên máy đã kích hoạt (gói năm thì gia hạn theo năm).</p><h2>Nhận key thế nào?</h2><ul><li>Thanh toán xong, tiệm gửi key qua email hoặc Zalo trong 15 phút</li><li>Tiệm cài giúp miễn phí qua UltraViewer hoặc tại tiệm</li><li>Xuất hóa đơn VAT cho công ty</li></ul>';
	$soft = array(
		array( 'name' => 'Windows 11 Home bản quyền (key điện tử)', 'sku' => 'PM-MS-WIN11-HOME', 'cat' => $c['windows'], 'price' => 2990000, 'brand' => 'Microsoft' ),
		array( 'name' => 'Windows 11 Pro bản quyền (key điện tử)', 'sku' => 'PM-MS-WIN11-PRO', 'cat' => $c['windows'], 'price' => 3890000, 'sale' => 3490000, 'brand' => 'Microsoft' ),
		array( 'name' => 'Microsoft 365 Family 1 năm (6 người)', 'sku' => 'PM-MS-365-FAMILY', 'cat' => $c['office'], 'price' => 1990000, 'sale' => 1590000, 'brand' => 'Microsoft' ),
		array( 'name' => 'Office Home 2024 dùng vĩnh viễn', 'sku' => 'PM-MS-OFFICE-2024', 'cat' => $c['office'], 'price' => 3490000, 'brand' => 'Microsoft' ),
		array( 'name' => 'Kaspersky Standard 1 máy 1 năm', 'sku' => 'PM-KAS-STD-1PC', 'cat' => $c['diet-virus'], 'price' => 290000, 'brand' => 'Kaspersky' ),
		array( 'name' => 'ESET NOD32 Antivirus 1 máy 1 năm', 'sku' => 'PM-ESET-NOD32-1PC', 'cat' => $c['diet-virus'], 'price' => 250000, 'sale' => 220000, 'brand' => 'ESET' ),
	);
	foreach ( $soft as $it ) {
		$items[] = $it + array( 'bh' => 0, 'stock' => 50, 'desc' => $key, 'short' => 'Gửi key qua email, Zalo trong 15 phút. Tiệm cài giúp miễn phí.' );
	}
	$ids = array();
	foreach ( $items as $it ) {
		$ids[ $it['sku'] ] = tmt_demo_product( $it );
	}
	// Khe Windows / Office / Diệt virus ở Lên cấu hình (nhớ khe nào do dữ liệu mẫu thêm để xóa đúng).
	update_option( 'tmt_demo_slots', array_values( array_unique( array_merge( (array) get_option( 'tmt_demo_slots', array() ), tmt_pcb_add_software_slots() ) ) ), false );
	tmt_pcb_flush();

	// Cấu hình mẫu ở trang Xây dựng cấu hình.
	$presets = array_values( array_filter( tmt_pcb_presets(), function ( $p ) { return empty( $p['demo'] ); } ) );
	$max     = $presets ? max( array_column( $presets, 'id' ) ) : 0;
	$builds  = array(
		'Văn phòng 8 triệu'  => array( 'CPU-IN-I5-12400' => 1, 'MB-MSI-H610M-E' => 1, 'RAM-KS-8G-D4-3200' => 2, 'SSD-KS-NV3-500' => 1, 'PSU-XG-450' => 1, 'CASE-XG-NYX3F' => 1, 'BP-LG-K120' => 1, 'CH-LG-B100' => 1 ),
		'Chơi game RTX 4060' => array( 'CPU-IN-I5-12400F' => 1, 'MB-AS-B760M-A' => 1, 'RAM-KS-16G-D4-3200' => 2, 'SSD-SS-990PRO-1T' => 1, 'VGA-MSI-4060-V2X' => 1, 'PSU-DC-PK650D' => 1, 'CASE-CS-4000D' => 1, 'TN-ID-SE214XT' => 1 ),
	);
	foreach ( $builds as $name => $parts ) {
		$list = array();
		foreach ( $parts as $sku => $qty ) {
			$list[ $ids[ $sku ] ] = $qty;
		}
		$presets[] = array( 'id' => ++$max, 'name' => $name, 'items' => $list, 'demo' => 1 );
	}
	update_option( 'tmt_pcb_presets', $presets, false );

	tmt_demo_extras( $ids );
	tmt_demo_posts( $ids );
	tmt_demo_landings( $c );
	tmt_demo_site();
	update_option( 'tmt_demo_done', time() );
}

/** Sản phẩm ngừng kinh doanh, giới thiệu nâng cao cho laptop, chương trình quà tặng kèm. */
function tmt_demo_extras( $ids ) {
	$old = wc_get_product( $ids['VGA-AS-1650-4G'] );
	if ( $old && 'yes' !== $old->get_meta( '_tmt_eol' ) ) {
		$old->update_meta_data( '_tmt_eol_vis', $old->get_catalog_visibility() );
		$old->update_meta_data( '_tmt_eol', 'yes' );
		$old->update_meta_data( '_tmt_replacement', $ids['VGA-MSI-4060-V2X'] );
		$old->set_catalog_visibility( 'hidden' );
		$old->save();
	}
	if ( function_exists( 'tmt_intro_clean' ) ) {
		$blocks = tmt_intro_clean(
			array(
				array( 'type' => 'hero', 'eyebrow' => 'Dell Inspiron 15', 'title' => 'Làm việc cả ngày, gọn trong một chiếc balo', 'text' => 'Chip Intel Core i5 thế hệ 13, màn 120Hz, pin dùng cả buổi.' ),
				array( 'type' => 'stats', 'items' => array( array( 'value' => '120Hz', 'title' => 'Màn hình mượt' ), array( 'value' => '1.65kg', 'title' => 'Nhẹ' ), array( 'value' => '16GB', 'title' => 'RAM' ), array( 'value' => '512GB', 'title' => 'SSD NVMe' ) ) ),
				array( 'type' => 'media', 'eyebrow' => 'Hiệu năng', 'title' => 'Mở nhiều ứng dụng vẫn mượt', 'body' => "Core i5 10 nhân cho văn phòng, học online, chỉnh ảnh nhẹ.\n\n<ul><li>Turbo đến 4.6GHz</li><li>Card Intel Iris Xe</li></ul>" ),
				array( 'type' => 'features', 'title' => 'Tiện ích', 'items' => array( array( 'icon' => 'fa-solid fa-wifi', 'title' => 'WiFi 6', 'text' => 'Kết nối nhanh, ổn định' ), array( 'icon' => 'fa-solid fa-fingerprint', 'title' => 'Vân tay', 'text' => 'Mở máy một chạm' ), array( 'icon' => 'fa-solid fa-camera', 'title' => 'Webcam HD', 'text' => 'Họp online rõ nét' ) ) ),
				array( 'type' => 'compare', 'title' => 'So với đời trước', 'columns' => array( '', 'Inspiron 3520', '' ), 'rows' => array( array( 'label' => 'Màn 120Hz', 'values' => array( '+', '-', '' ) ), array( 'label' => 'WiFi 6', 'values' => array( '+', '+', '' ) ) ) ),
				array( 'type' => 'box', 'items' => array( array( 'icon' => 'fa-solid fa-laptop', 'title' => 'Máy' ), array( 'icon' => 'fa-solid fa-plug', 'title' => 'Sạc 65W' ) ) ),
			)
		);
		update_post_meta( $ids['LT-DL-15-3530-I5'], '_tmt_intro', $blocks );
	}
	$gift = tmt_demo_post( 'tmt_gift', 'Mua laptop tặng chuột hoặc bàn phím', array( 'post_name' => 'demo-qua-laptop' ) );
	update_post_meta( $gift, '_tmt_gift', array( 'target' => 'products', 'products' => array( $ids['LT-DL-15-3530-I5'] ), 'categories' => array(), 'min_price' => 0, 'min_qty' => 1, 'min_order' => 0, 'gifts' => array( $ids['CH-LG-B100'], $ids['BP-LG-K120'] ), 'pick' => 'one', 'starts' => '', 'ends' => '', 'limit' => '' ) );
	if ( post_type_exists( 'tmt_promo' ) ) {
		$flash = tmt_demo_post( 'tmt_promo', 'Giá sốc SSD 7 ngày', array( 'post_name' => 'demo-gia-soc-ssd' ) );
		update_post_meta( $flash, '_tmt_promo', array( 'type' => 'flash', 'rows' => array( array( 'product' => $ids['SSD-SS-990PRO-1T'], 'category' => 0, 'price' => 1990000, 'percent' => 0 ) ), 'groups' => array(), 'items' => array(), 'amount' => 0, 'starts' => '', 'ends' => wp_date( 'Y-m-d H:i', time() + 7 * DAY_IN_SECONDS ), 'limit' => 10 ) );
		$combo = tmt_demo_post( 'tmt_promo', 'Combo CPU + Main giảm 200.000', array( 'post_name' => 'demo-combo-cpu-main' ) );
		update_post_meta( $combo, '_tmt_promo', array( 'type' => 'combo', 'rows' => array(), 'groups' => array(), 'items' => array( array( 'product' => $ids['CPU-IN-I5-12400F'], 'qty' => 1 ), array( 'product' => $ids['MB-AS-B760M-A'], 'qty' => 1 ) ), 'amount' => 200000, 'starts' => '', 'ends' => '', 'limit' => '' ) );
		update_option( 'tmt_promo_ver', time() );
		delete_transient( 'wc_products_onsale' );
	}
}

/** 6 bài Mẹo vặt ở 3 chuyên mục, 2 bài ghim, 2 bài có video, bài có sản phẩm gợi ý. */
function tmt_demo_posts( $ids ) {
	$cw = tmt_demo_term( 'category', 'Windows', 'windows' );
	$cl = tmt_demo_term( 'category', 'Linh kiện', 'linh-kien' );
	$cm = tmt_demo_term( 'category', 'Mạng, WiFi', 'mang-wifi' );
	$h  = function ( $sections ) {
		$out = '';
		foreach ( $sections as $title => $text ) {
			$out .= '<!-- wp:heading --><h2 class="wp-block-heading">' . $title . '</h2><!-- /wp:heading --><!-- wp:paragraph --><p>' . $text . '</p><!-- /wp:paragraph -->';
		}
		return $out;
	};
	$posts = array(
		array( 'Máy tính chậm: 5 việc nên làm trước khi mang đi sửa', $cw, 'Tắt bớt ứng dụng chạy cùng Windows, dọn ổ C, kiểm tra ổ cứng... làm theo thứ tự, phần lớn máy chạy nhanh lại ngay.', array( 'Tắt ứng dụng chạy cùng Windows' => 'Mở Task Manager (Ctrl + Shift + Esc), tab Startup, tắt các ứng dụng không cần.', 'Dọn ổ C' => 'Gõ Disk Cleanup, chọn ổ C, tick Temporary files và Recycle Bin.', 'Kiểm tra ổ cứng' => 'Ổ HDD cũ là nguyên nhân phổ biến nhất. Thay SSD giúp máy khởi động dưới 15 giây.', 'Quét virus' => 'Dùng Windows Security, chọn Full scan.', 'Cập nhật Windows và driver' => 'Vào Settings, Windows Update, cài hết bản cập nhật.' ), array( 'sticky' => true, 'level' => 'easy', 'products' => 'SSD-KS-NV3-500, SSD-SS-990PRO-1T' ) ),
		array( 'Chọn SSD NVMe hay SATA cho máy cũ?', $cl, 'Main có khe M.2 NVMe thì chọn NVMe, nhanh gấp 3 đến 5 lần SATA mà giá gần bằng.', array( 'Xem main có khe M.2 không' => 'Tra tên mainboard hoặc mở nắp máy tìm khe M.2 nằm ngang.', 'NVMe nhanh hơn bao nhiêu?' => 'SATA khoảng 550 MB/s, NVMe đời mới 3.000 đến 7.000 MB/s.', 'Máy không có khe M.2' => 'Chọn SSD SATA 2.5 inch, vẫn nhanh hơn HDD rất nhiều.' ), array( 'video' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'level' => 'medium', 'products' => 'SSD-KS-NV3-500, SSD-SS-990PRO-1T' ) ),
		array( 'Ráp máy văn phòng 8 triệu: chọn linh kiện thế nào?', $cl, 'CPU có đồ họa tích hợp, 16GB RAM, SSD NVMe là đủ cho Word, Excel, phần mềm kế toán và họp online.', array( 'CPU' => 'Core i5-12400 có sẵn đồ họa, không cần mua card rời.', 'RAM' => 'Lắp 2 thanh 8GB để chạy kênh đôi, nhanh hơn 1 thanh 16GB.', 'Ổ cứng' => 'SSD NVMe 500GB cho Windows và phần mềm.', 'Nguồn và vỏ' => 'Nguồn 450W chính hãng là đủ, vỏ mATX gọn bàn.' ), array( 'sticky' => true, 'level' => 'medium', 'products' => 'CPU-IN-I5-12400, MB-MSI-H610M-E, RAM-KS-8G-D4-3200' ) ),
		array( 'WiFi yếu ở phòng xa: lắp thêm gì?', $cm, 'Nhà nhiều tầng nên kéo dây mạng lên tầng trên rồi gắn thêm router hoặc bộ phát, ổn định hơn kích sóng.', array( 'Vì sao WiFi yếu' => 'Tường bê tông và sàn chặn sóng, mỗi tầng giảm khoảng một nửa.', 'Kéo dây mạng' => 'Cách ổn định nhất: dây mạng tới mỗi tầng, mỗi tầng một bộ phát.', 'Mesh WiFi' => 'Không kéo dây được thì dùng bộ Mesh 2 đến 3 cục.' ), array( 'video' => 'https://www.tiktok.com/@tiem/video/1', 'level' => 'easy', 'products' => 'NET-TP-AX23, NET-TP-SG1008D' ) ),
		array( 'Cài lại Windows 11 không mất dữ liệu', $cw, 'Dùng tính năng Reset this PC, chọn Keep my files. Vẫn nên sao lưu trước cho chắc.', array( 'Sao lưu' => 'Chép Desktop, Documents, Downloads ra ổ ngoài hoặc OneDrive.', 'Reset this PC' => 'Settings, System, Recovery, Reset this PC, Keep my files.', 'Sau khi cài' => 'Cài lại phần mềm, driver máy in, đăng nhập lại Office.' ), array( 'level' => 'medium' ) ),
		array( 'Camera WiFi hay camera có dây cho cửa hàng?', $cm, 'Cửa hàng mở cả ngày nên chọn camera có dây + đầu ghi: hình ổn định, không rớt mạng, xem lại cả tháng.', array( 'Camera WiFi' => 'Lắp nhanh, hợp nhà ở 1 đến 2 mắt, lưu thẻ nhớ.', 'Camera có dây + đầu ghi' => 'Ổn định, nhiều mắt, lưu ổ cứng 15 đến 30 ngày.', 'Xem trên điện thoại' => 'Cả hai loại đều xem được qua app, chia sẻ cho nhiều người.' ), array( 'level' => 'easy', 'products' => 'CAM-IM-RANGER2, DG-HIK-7208' ) ),
	);
	$sticky = array_map( 'intval', (array) get_option( 'sticky_posts', array() ) );
	$day    = 0;
	foreach ( $posts as $p ) {
		$id = tmt_demo_post(
			'post',
			$p[0],
			array(
				'post_excerpt'  => $p[2],
				'post_content'  => '<!-- wp:paragraph --><p>' . $p[2] . '</p><!-- /wp:paragraph -->' . $h( $p[3] ),
				'post_category' => array( $p[1] ),
				'post_date'     => wp_date( 'Y-m-d H:i:s', time() - ( $day++ ) * 2 * DAY_IN_SECONDS ),
			)
		);
		foreach ( array( 'video' => '_tmt_video_url', 'level' => '_tmt_level', 'products' => '_tmt_products' ) as $k => $meta ) {
			if ( ! empty( $p[4][ $k ] ) ) {
				update_post_meta( $id, $meta, $p[4][ $k ] );
			}
		}
		update_post_meta( $id, '_tmt_views', wp_rand( 40, 900 ) );
		if ( ! empty( $p[4]['sticky'] ) ) {
			$sticky[] = $id;
		}
	}
	update_option( 'sticky_posts', array_values( array_unique( $sticky ) ) );
}

/** Trang Giải pháp Camera, Mạng WiFi và Phần mềm chấm công. */
function tmt_demo_landings( $c ) {
	$camera = tmt_demo_post( 'tmt_solution', 'Lắp camera', array( 'post_name' => 'lap-camera', 'menu_order' => 1, 'post_excerpt' => 'Camera cho nhà, cửa hàng, văn phòng, xưởng. Khảo sát miễn phí, xem trên điện thoại.' ) );
	$lp     = tmt_lp_defaults( 'tmt_solution' );
	update_post_meta(
		$camera,
		'_tmt_lp',
		array_merge(
			$lp,
			array(
				'headline'  => 'Lắp camera an ninh',
				'highlight' => 'xem rõ cả ban đêm',
				'intro'     => 'Tiệm khảo sát tận nơi, chọn góc đặt camera, đi dây gọn, cài app xem trên điện thoại cho cả nhà.',
				'ticks'     => array( 'Hình 2MP đến 4K, có màu ban đêm', 'Báo động khi có người lạ', 'Xem lại 15 đến 30 ngày', 'Bảo hành tận nơi 24 tháng' ),
				'icon'      => 'video',
				'product_cat' => $c['camera'],
				'needs'     => array(
					array( 'icon' => 'house', 'title' => 'Nhà ở', 'text' => 'Cổng, sân, phòng khách. Xem con nhỏ, người già khi đi làm.', 'suggest' => '2 đến 4 camera WiFi' ),
					array( 'icon' => 'store', 'title' => 'Cửa hàng', 'text' => 'Quầy thu ngân, kho, cửa ra vào. Lưu lại khi có tranh chấp.', 'suggest' => '4 camera có dây + đầu ghi' ),
					array( 'icon' => 'building', 'title' => 'Văn phòng', 'text' => 'Lối đi, phòng làm việc, kết hợp máy chấm công.', 'suggest' => '8 camera + đầu ghi' ),
					array( 'icon' => 'industry', 'title' => 'Nhà xưởng', 'text' => 'Bãi xe, kho hàng, khu sản xuất, góc rộng xa.', 'suggest' => '8 đến 16 camera 4MP' ),
				),
				'packages'  => array(
					array( 'group' => 'Camera có dây', 'name' => 'Gói 2 camera', 'audience' => 'Nhà phố, tiệm nhỏ', 'price' => 3990000, 'period' => '', 'items' => array( '2 camera 2MP có màu ban đêm', 'Đầu ghi 4 kênh + ổ 500GB', 'Dây, nguồn, công lắp' ), 'excluded' => array(), 'icon' => 'video', 'featured' => false ),
					array( 'group' => 'Camera có dây', 'name' => 'Gói 4 camera', 'audience' => 'Cửa hàng, văn phòng', 'price' => 6490000, 'period' => '', 'items' => array( '4 camera 2MP có màu ban đêm', 'Đầu ghi 4 kênh + ổ 1TB', 'Dây, nguồn, công lắp', 'Cài app xem trên điện thoại' ), 'excluded' => array(), 'icon' => 'video', 'featured' => true ),
					array( 'group' => 'Camera có dây', 'name' => 'Gói 8 camera', 'audience' => 'Xưởng, kho', 'price' => '', 'period' => '', 'items' => array( '8 camera 4MP', 'Đầu ghi 8 kênh + ổ 2TB', 'Khảo sát đi dây theo mặt bằng' ), 'excluded' => array(), 'icon' => 'industry', 'featured' => false ),
					array( 'group' => 'Camera WiFi', 'name' => 'Gói 1 camera WiFi', 'audience' => 'Phòng khách, cửa ra vào', 'price' => 890000, 'period' => '', 'items' => array( 'Camera WiFi xoay 360°', 'Thẻ nhớ 64GB', 'Cài app, chia sẻ cho người nhà' ), 'excluded' => array(), 'icon' => 'wifi', 'featured' => false ),
					array( 'group' => 'Camera WiFi', 'name' => 'Gói 3 camera WiFi', 'audience' => 'Nhà 2 tầng', 'price' => 2490000, 'period' => '', 'items' => array( '3 camera WiFi', '3 thẻ nhớ 64GB', 'Cài app, đàm thoại 2 chiều' ), 'excluded' => array(), 'icon' => 'wifi', 'featured' => true ),
				),
				'showcases' => array(
					array( 'label' => 'Xem trên điện thoại', 'title' => 'Ở đâu cũng xem được nhà mình', 'text' => 'Tiệm cài app, tạo tài khoản và chia sẻ cho người nhà. Có người lạ thì điện thoại báo ngay.', 'points' => array( 'Xem trực tiếp và xem lại', 'Báo động khi có chuyển động', 'Nói chuyện qua camera' ), 'image' => 0 ),
					array( 'label' => 'Thi công', 'title' => 'Đi dây gọn, dán nhãn từng camera', 'text' => 'Dây đi âm tường hoặc nẹp, đầu ghi đặt chỗ kín, có chống sét cho camera ngoài trời.', 'points' => array( 'Nẹp thẩm mỹ cùng màu tường', 'Dán nhãn dây hai đầu', 'Hướng dẫn tự xem lại, tải video' ), 'image' => 0 ),
				),
				'faq'       => array(
					array( 'q' => 'Camera lưu được bao lâu?', 'a' => 'Tùy ổ cứng và số camera. 4 camera 2MP với ổ 1TB lưu khoảng 20 ngày, ghi đè tự động.' ),
					array( 'q' => 'Mất mạng camera có ghi không?', 'a' => 'Camera có dây vẫn ghi vào đầu ghi bình thường, chỉ không xem từ xa được khi mất mạng.' ),
					array( 'q' => 'Bảo hành thế nào?', 'a' => 'Thiết bị bảo hành 24 tháng, tiệm tới tận nơi kiểm tra trong khu vực lắp đặt.' ),
				),
			)
		)
	);

	$net = tmt_demo_post( 'tmt_solution', 'Mạng, WiFi văn phòng', array( 'post_name' => 'mang-wifi', 'menu_order' => 2, 'post_excerpt' => 'Kéo dây mạng, WiFi phủ khắp nhà và văn phòng, tủ rack gọn gàng.' ) );
	update_post_meta(
		$net,
		'_tmt_lp',
		array_merge(
			tmt_lp_defaults( 'tmt_solution' ),
			array(
				'headline'  => 'Mạng, WiFi ổn định',
				'highlight' => 'phủ sóng mọi phòng',
				'intro'     => 'Thiết kế mạng theo mặt bằng, kéo dây tới từng phòng, WiFi chuyển vùng không rớt khi đi lại.',
				'ticks'     => array( 'WiFi 6 chuyển vùng', 'Dây mạng Cat6 âm tường', 'Tủ rack, dán nhãn đầu dây', 'Tách mạng khách và nội bộ' ),
				'icon'      => 'network-wired',
				'product_cat' => $c['thiet-bi-mang'],
				'needs'     => array(
					array( 'icon' => 'house', 'title' => 'Nhà nhiều tầng', 'text' => 'Mỗi tầng một bộ phát, đi lại không rớt.', 'suggest' => 'Mesh WiFi 3 cục' ),
					array( 'icon' => 'building', 'title' => 'Văn phòng', 'text' => 'Dây mạng tới từng bàn, WiFi trần.', 'suggest' => 'Switch + WiFi gắn trần' ),
					array( 'icon' => 'store', 'title' => 'Quán cà phê', 'text' => 'WiFi khách riêng, giới hạn tốc độ.', 'suggest' => 'Router cân bằng tải' ),
				),
				'faq'       => array( array( 'q' => 'Có cần đục tường không?', 'a' => 'Không bắt buộc, có thể đi nẹp nổi cùng màu tường.' ) ),
			)
		)
	);

	$soft = tmt_demo_post( 'tmt_software', 'Phần mềm chấm công tính lương', array( 'post_name' => 'cham-cong-tinh-luong', 'menu_order' => 1, 'post_excerpt' => 'Lấy giờ từ máy chấm công, tự tính công, đi trễ, tăng ca và bảng lương.' ) );
	update_post_meta(
		$soft,
		'_tmt_lp',
		array_merge(
			tmt_lp_defaults( 'tmt_software' ),
			array(
				'eyebrow'   => 'Chấm công tính lương',
				'headline'  => 'Chấm công, tính lương',
				'highlight' => 'xong trong 5 phút',
				'intro'     => 'Phần mềm tự lấy giờ vào ra từ máy chấm công, tính công, đi trễ, tăng ca, nghỉ phép và xuất bảng lương Excel.',
				'ticks'     => array( 'Lấy dữ liệu tự động', 'Ca làm linh hoạt', 'Phiếu lương gửi Zalo', 'Dùng nhiều chi nhánh' ),
				'icon'      => 'fingerprint',
				'product_cat' => $c['may-cham-cong'],
				'form_question' => 'Đang dùng máy chấm công chưa?',
				'form_options'  => array( 'Đã có máy', 'Chưa có, cần mua' ),
				'steps'     => array(
					array( 'icon' => 'fingerprint', 'title' => 'Nhân viên chấm', 'text' => 'Khuôn mặt, vân tay hoặc thẻ' ),
					array( 'icon' => 'rotate', 'title' => 'Tự đồng bộ', 'text' => 'Giờ vào ra về máy tính' ),
					array( 'icon' => 'calendar-days', 'title' => 'Tính công', 'text' => 'Theo ca, trễ, tăng ca' ),
					array( 'icon' => 'file-excel', 'title' => 'Ra bảng lương', 'text' => 'Xuất Excel, gửi phiếu' ),
				),
				'features'  => array(
					array( 'icon' => 'clock', 'title' => 'Ca làm linh hoạt', 'text' => 'Ca hành chính, ca xoay, ca đêm, nghỉ giữa ca.' ),
					array( 'icon' => 'user-clock', 'title' => 'Đi trễ, về sớm, tăng ca', 'text' => 'Tự tính phút trễ và giờ tăng ca theo quy định.' ),
					array( 'icon' => 'calendar-xmark', 'title' => 'Nghỉ phép', 'text' => 'Phép năm, nghỉ không lương, nghỉ lễ.' ),
					array( 'icon' => 'money-bill-wave', 'title' => 'Tính lương', 'text' => 'Lương ngày, lương tháng, phụ cấp, khấu trừ.' ),
					array( 'icon' => 'file-invoice-dollar', 'title' => 'Phiếu lương', 'text' => 'In hoặc gửi phiếu lương cho từng người.' ),
					array( 'icon' => 'building', 'title' => 'Nhiều chi nhánh', 'text' => 'Gộp dữ liệu nhiều máy chấm công.' ),
				),
				'packages'  => array(
					array( 'group' => '', 'name' => 'Cơ bản', 'audience' => 'Dưới 30 nhân viên', 'price' => 1500000, 'period' => 'năm', 'items' => array( '1 máy chấm công', 'Chấm công, tính công', 'Xuất Excel' ), 'excluded' => array( 'Tính lương' ), 'icon' => '', 'featured' => false ),
					array( 'group' => '', 'name' => 'Đầy đủ', 'audience' => 'Dưới 100 nhân viên', 'price' => 3000000, 'period' => 'năm', 'items' => array( '3 máy chấm công', 'Tính công + tính lương', 'Phiếu lương gửi Zalo' ), 'excluded' => array(), 'icon' => '', 'featured' => true ),
					array( 'group' => '', 'name' => 'Doanh nghiệp', 'audience' => 'Trên 100 nhân viên', 'price' => '', 'period' => '', 'items' => array( 'Không giới hạn máy', 'Nhiều chi nhánh', 'Hỗ trợ ưu tiên' ), 'excluded' => array(), 'icon' => '', 'featured' => false ),
				),
				'faq'       => array( array( 'q' => 'Dùng thử bao lâu?', 'a' => 'Dùng thử 15 ngày đủ tính năng, tiệm cài và hướng dẫn miễn phí.' ) ),
			)
		)
	);
}

/** Trang chủ tĩnh + trang Mẹo vặt + menu chính, chỉ khi web chưa có. */
function tmt_demo_site() {
	if ( 'page' !== get_option( 'show_on_front' ) ) {
		$home = tmt_demo_post( 'page', 'Trang chủ', array( 'post_name' => 'trang-chu' ) );
		$blog = tmt_demo_post( 'page', 'Mẹo vặt', array( 'post_name' => 'meo-vat', 'post_excerpt' => 'Mẹo sửa máy, chọn linh kiện, cài phần mềm, từ những ca máy thật khách mang tới tiệm.' ) );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home );
		update_option( 'page_for_posts', $blog );
		update_option( 'tmt_demo_front', 1 );
	}
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	if ( empty( $locations['primary'] ) ) {
		$menu = wp_get_nav_menu_object( 'Menu chính (mẫu)' );
		$menu = $menu ? $menu->term_id : wp_create_nav_menu( 'Menu chính (mẫu)' );
		if ( ! is_wp_error( $menu ) ) {
			update_term_meta( $menu, '_tmt_demo', 1 );
			if ( ! wp_get_nav_menu_items( $menu ) ) {
				foreach ( array( 'laptop', 'linh-kien', 'man-hinh', 'camera' ) as $slug ) {
					$t = get_term_by( 'slug', $slug, 'product_cat' );
					if ( $t ) {
						wp_update_nav_menu_item( $menu, 0, array( 'menu-item-object' => 'product_cat', 'menu-item-object-id' => $t->term_id, 'menu-item-type' => 'taxonomy', 'menu-item-status' => 'publish' ) );
					}
				}
				$links = array(
					'Xây dựng cấu hình' => function_exists( 'tmt_pcb_url' ) ? tmt_pcb_url() : '',
					'Giải pháp'         => get_post_type_archive_link( 'tmt_solution' ),
					'Phần mềm'          => get_post_type_archive_link( 'tmt_software' ),
					'Mẹo vặt'           => get_option( 'page_for_posts' ) ? get_permalink( get_option( 'page_for_posts' ) ) : '',
				);
				foreach ( $links as $title => $url ) {
					if ( $url ) {
						wp_update_nav_menu_item( $menu, 0, array( 'menu-item-title' => $title, 'menu-item-url' => $url, 'menu-item-type' => 'custom', 'menu-item-status' => 'publish' ) );
					}
				}
			}
			$locations['primary'] = $menu;
			set_theme_mod( 'nav_menu_locations', $locations );
		}
	}
	flush_rewrite_rules( false );
}

/* ---------- Xóa ---------- */

function tmt_demo_remove() {
	$posts = get_posts(
		array(
			'post_type'   => array( 'product', 'post', 'page', 'tmt_solution', 'tmt_software', 'tmt_gift', 'tmt_promo' ),
			'post_status' => 'any',
			'numberposts' => -1,
			'fields'      => 'ids',
			'meta_key'    => '_tmt_demo', // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	foreach ( $posts as $id ) {
		if ( 'product' === get_post_type( $id ) ) {
			$p = wc_get_product( $id );
			if ( $p ) {
				foreach ( $p->get_children() as $child ) {
					wp_delete_post( $child, true );
				}
				$p->delete( true );
			}
			continue;
		}
		wp_delete_post( $id, true );
	}
	if ( get_option( 'tmt_demo_front' ) ) {
		if ( ! get_post( (int) get_option( 'page_on_front' ) ) ) {
			update_option( 'show_on_front', 'posts' );
			update_option( 'page_on_front', 0 );
		}
		if ( ! get_post( (int) get_option( 'page_for_posts' ) ) ) {
			update_option( 'page_for_posts', 0 );
		}
		delete_option( 'tmt_demo_front' );
	}
	$terms = get_terms( array( 'taxonomy' => array( 'product_cat', 'category', 'product_brand', 'nav_menu' ), 'hide_empty' => false, 'meta_key' => '_tmt_demo' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	foreach ( is_wp_error( $terms ) ? array() : $terms as $t ) {
		if ( 'nav_menu' === $t->taxonomy ) {
			wp_delete_nav_menu( $t->term_id );
		} elseif ( 0 === (int) $t->count ) {
			wp_delete_term( $t->term_id, $t->taxonomy );
		}
	}
	update_option( 'tmt_pcb_presets', array_values( array_filter( tmt_pcb_presets(), function ( $p ) { return empty( $p['demo'] ); } ) ), false );
	$demo_slots = (array) get_option( 'tmt_demo_slots', array() );
	if ( $demo_slots ) {
		update_option( 'tmt_pcb_extra_slots', array_values( array_filter( (array) get_option( 'tmt_pcb_extra_slots', array() ), function ( $x ) use ( $demo_slots ) { return ! in_array( isset( $x['key'] ) ? $x['key'] : '', $demo_slots, true ); } ) ), false );
		delete_option( 'tmt_demo_slots' );
	}
	$sticky = array_values( array_filter( (array) get_option( 'sticky_posts', array() ), 'get_post' ) );
	update_option( 'sticky_posts', $sticky );
	tmt_pcb_flush();
	delete_option( 'tmt_demo_done' );
}
