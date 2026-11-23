<?php
/**
 * WooCommerce → Lên cấu hình: gán danh mục cho từng khe, khe phụ kiện tự thêm, tạo sẵn thuộc tính thông số, ghi chú, số Zalo.
 *
 * @package TMT_Shop_Tools
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'woocommerce', 'Lên cấu hình máy tính', 'Lên cấu hình', 'manage_woocommerce', 'tmt-len-cau-hinh', 'tmt_pcb_admin_page' );
	},
	60
);

add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		if ( false !== strpos( (string) $hook, 'tmt-len-cau-hinh' ) ) {
			wp_enqueue_script( 'wc-enhanced-select' );
			wp_enqueue_style( 'woocommerce_admin_styles' );
		}
	}
);

/** Link "Cài đặt" ngay ở danh sách plugin. */
add_filter(
	'plugin_action_links_tmt-shop-tools/tmt-shop-tools.php',
	function ( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=tmt-len-cau-hinh' ) ) . '">Cài đặt</a>' );
		return $links;
	}
);

/**
 * Tạo thuộc tính chung còn thiếu (pa_socket, pa_ram-type...) kèm giá trị gợi ý.
 *
 * @return int số thuộc tính mới tạo
 */
function tmt_pcb_create_attributes() {
	$made    = 0;
	$aliases = tmt_pcb_spec_aliases();
	foreach ( tmt_pcb_specs() as $key => $def ) {
		$slug = tmt_pcb_attr_slug( $key );
		foreach ( isset( $aliases[ $key ] ) ? $aliases[ $key ] : array() as $alias ) {
			if ( ! wc_attribute_taxonomy_id_by_name( $slug ) && wc_attribute_taxonomy_id_by_name( $alias ) ) {
				$slug = $alias; // Đã có thuộc tính tên khác cho thông số này.
			}
		}
		if ( ! wc_attribute_taxonomy_id_by_name( $slug ) ) {
			$id = wc_create_attribute( array( 'name' => $def[0], 'slug' => $slug, 'type' => 'select', 'has_archives' => false ) );
			if ( is_wp_error( $id ) ) {
				continue;
			}
			++$made;
		}
		$tax = 'pa_' . $slug;
		if ( ! taxonomy_exists( $tax ) ) {
			register_taxonomy( $tax, array( 'product' ), array( 'hierarchical' => false, 'show_ui' => false, 'query_var' => false, 'rewrite' => false ) );
		}
		foreach ( $def[2] as $value ) {
			if ( ! term_exists( $value, $tax ) ) {
				wp_insert_term( $value, $tax );
			}
		}
	}
	delete_transient( 'wc_attribute_taxonomies' );
	tmt_pcb_flush();
	return $made;
}

add_action(
	'admin_post_tmt_pcb_save',
	function () {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'Không có quyền.' );
		}
		check_admin_referer( 'tmt_pcb_save' );
		$msg = 'saved';
		if ( isset( $_POST['make_attributes'] ) ) {
			$msg = 'attrs-' . tmt_pcb_create_attributes();
		} else {
			$cats = array();
			foreach ( array_keys( tmt_pcb_slots() ) as $slot ) {
				$cats[ $slot ] = isset( $_POST['slot'][ $slot ] ) ? array_values( array_filter( array_map( 'absint', (array) wp_unslash( $_POST['slot'][ $slot ] ) ) ) ) : array();
			}
			update_option( 'tmt_pcb_slot_categories', $cats, false );
			$extra = array();
			foreach ( isset( $_POST['extra'] ) ? (array) wp_unslash( $_POST['extra'] ) : array() as $x ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$label = sanitize_text_field( isset( $x['label'] ) ? $x['label'] : '' );
				if ( '' === $label ) {
					continue; // Xóa tên = bỏ khe.
				}
				$key     = isset( $x['key'] ) && preg_match( '/^x[a-z0-9]{1,12}$/', (string) $x['key'] ) ? (string) $x['key'] : 'x' . substr( md5( uniqid( $label, true ) ), 0, 6 );
				$extra[] = array( 'key' => $key, 'label' => $label, 'icon' => sanitize_key( isset( $x['icon'] ) ? $x['icon'] : '' ), 'max' => absint( isset( $x['max'] ) ? $x['max'] : 1 ) );
			}
			update_option( 'tmt_pcb_extra_slots', $extra, false );
			if ( isset( $_POST['soft_slots'] ) ) {
				$msg = 'soft-' . count( tmt_pcb_add_software_slots() );
			}
			update_option( 'tmt_pcb_note', sanitize_text_field( wp_unslash( isset( $_POST['note'] ) ? $_POST['note'] : '' ) ), false );
			update_option( 'tmt_pcb_zalo', preg_replace( '/[^\d]/', '', (string) wp_unslash( isset( $_POST['zalo'] ) ? $_POST['zalo'] : '' ) ), false );
			tmt_pcb_flush();
		}
		wp_safe_redirect( admin_url( 'admin.php?page=tmt-len-cau-hinh&tmt_msg=' . rawurlencode( $msg ) ) );
		exit;
	}
);

/** Số sản phẩm đang có thuộc tính này. */
function tmt_pcb_attr_usage( $tax ) {
	if ( ! taxonomy_exists( $tax ) ) {
		return 0;
	}
	$terms = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => true, 'fields' => 'ids' ) );
	if ( is_wp_error( $terms ) || ! $terms ) {
		return 0;
	}
	$ids = get_objects_in_term( $terms, $tax );
	return is_wp_error( $ids ) ? 0 : count( array_unique( $ids ) );
}

function tmt_pcb_admin_page() {
	$slots  = tmt_pcb_slots();
	$chosen = tmt_pcb_slot_categories();
	$saved  = is_array( get_option( 'tmt_pcb_slot_categories' ) );
	$terms  = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'orderby' => 'name' ) );
	$terms  = is_wp_error( $terms ) ? array() : $terms;
	$names  = array();
	// Tên đầy đủ "Linh kiện › CPU".
	foreach ( $terms as $t ) {
		$label = $t->name;
		for ( $p = $t->parent, $guard = 0; $p && $guard < 6; ++$guard ) {
			$parent = get_term( $p, 'product_cat' );
			if ( ! $parent || is_wp_error( $parent ) ) {
				break;
			}
			$label = $parent->name . ' › ' . $label;
			$p     = $parent->parent;
		}
		$names[ $t->term_id ] = $label;
	}
	asort( $names );
	$parts   = tmt_pcb_parts();
	$per     = array_count_values( array_column( $parts, 'slot' ) );
	$msg     = isset( $_GET['tmt_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['tmt_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$page    = tmt_pcb_url();
	$theme_zalo = function_exists( 'tmt_opt' ) ? (string) tmt_opt( 'contact.zalo' ) : '';
	?>
	<div class="wrap">
		<h1>Lên cấu hình máy tính</h1>
		<?php if ( 'saved' === $msg ) : ?>
			<div class="notice notice-success is-dismissible"><p>Đã lưu.</p></div>
		<?php elseif ( 0 === strpos( $msg, 'attrs-' ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>Đã tạo <?php echo (int) substr( $msg, 6 ); ?> thuộc tính mới. Giờ vào sửa từng linh kiện → tab Thuộc tính để chọn giá trị.</p></div>
		<?php elseif ( 0 === strpos( $msg, 'soft-' ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo (int) substr( $msg, 5 ) ? 'Đã thêm ' . (int) substr( $msg, 5 ) . ' khe phần mềm. Chọn danh mục cho chúng ở mục 1.' : 'Các khe phần mềm đã có sẵn.'; ?></p></div>
		<?php endif; ?>
		<p>
			<?php if ( $page ) : ?>
				Trang cho khách: <a href="<?php echo esc_url( $page ); ?>" target="_blank"><?php echo esc_html( $page ); ?></a>. Thêm trang này vào menu chính (Giao diện → Menu).
				Lưu cấu hình mẫu ngay trên trang đó khi đang đăng nhập quản trị (nút "Lưu làm mẫu").
			<?php else : ?>
				Chưa có trang Xây dựng cấu hình: tạo một trang mới, nội dung là mã ngắn <code>[tmt_len_cau_hinh]</code>, rồi tắt và bật lại plugin.
			<?php endif; ?>
		</p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="tmt_pcb_save">
			<?php wp_nonce_field( 'tmt_pcb_save' ); ?>

			<h2>1. Danh mục của từng linh kiện</h2>
			<p>Sản phẩm thuộc danh mục (và danh mục con) đã chọn sẽ hiện trong khe đó. <?php echo $saved ? '' : '<b>Chưa lưu lần nào: đang tự đoán theo tên danh mục, kiểm tra rồi bấm Lưu.</b>'; ?></p>
			<table class="form-table" role="presentation">
				<?php foreach ( $slots as $slot => $s ) : ?>
					<tr>
						<th scope="row"><label for="tmt-slot-<?php echo esc_attr( $slot ); ?>"><?php echo esc_html( $s['label'] ); ?><?php echo $s['required'] ? ' <span style="color:#d63638">*</span>' : ''; ?></label></th>
						<td>
							<select id="tmt-slot-<?php echo esc_attr( $slot ); ?>" name="slot[<?php echo esc_attr( $slot ); ?>][]" multiple class="wc-enhanced-select" style="min-width: 360px" data-placeholder="Chọn danh mục">
								<?php foreach ( $names as $id => $label ) : ?>
									<option value="<?php echo (int) $id; ?>" <?php selected( in_array( (int) $id, $chosen[ $slot ], true ) ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
							<span class="description" style="margin-left:.5rem"><?php echo (int) ( isset( $per[ $slot ] ) ? $per[ $slot ] : 0 ); ?> sản phẩm</span>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>

			<h2>2. Phụ kiện thêm</h2>
			<p>Thêm khe cho phụ kiện tiệm bán kèm (tai nghe, loa, webcam, card WiFi, Windows...). Khe phụ kiện không bắt buộc, không có luật lắp ráp, hiện cuối danh sách dưới tiêu đề "Phụ kiện".
				Bấm Lưu xong, khe mới hiện ở mục 1 để chọn danh mục (danh mục trùng tên khe được tự nhận). Xóa tên để bỏ khe.</p>
			<table class="widefat striped" style="max-width: 760px">
				<thead><tr><th>Tên khe</th><th>Biểu tượng</th><th style="width:110px">Tối đa mỗi món</th></tr></thead>
				<tbody>
					<?php
					$tmt_rows = tmt_pcb_extra_slots();
					for ( $i = 0; $i < 3; $i++ ) {
						$tmt_rows[] = array( 'key' => '', 'label' => '', 'icon' => 'extension', 'max' => 1 );
					}
					foreach ( $tmt_rows as $i => $x ) :
						?>
						<tr>
							<td><input type="hidden" name="extra[<?php echo (int) $i; ?>][key]" value="<?php echo esc_attr( $x['key'] ); ?>">
								<input type="text" class="regular-text" name="extra[<?php echo (int) $i; ?>][label]" value="<?php echo esc_attr( $x['label'] ); ?>" placeholder="<?php echo $x['key'] ? '' : 'vd. Tai nghe'; ?>" aria-label="Tên khe"></td>
							<td><select name="extra[<?php echo (int) $i; ?>][icon]" aria-label="Biểu tượng">
								<?php foreach ( tmt_pcb_extra_icons() as $icon => $name ) : ?>
									<option value="<?php echo esc_attr( $icon ); ?>" <?php selected( $x['icon'], $icon ); ?>><?php echo esc_html( $name ); ?></option>
								<?php endforeach; ?>
							</select></td>
							<td><input type="number" min="1" max="10" name="extra[<?php echo (int) $i; ?>][max]" value="<?php echo (int) $x['max']; ?>" style="width:80px" aria-label="Tối đa"></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p><button type="submit" class="button" name="soft_slots" value="1">Thêm sẵn khe Windows, Office, Diệt virus</button>
				<span class="description">Khe nào đã có thì bỏ qua. Danh mục tên Windows / Office / Diệt virus được tự nhận.</span></p>

			<h2>3. Hiển thị</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="tmt-pcb-note">Ghi chú dưới tổng tiền</label></th>
					<td><input id="tmt-pcb-note" type="text" class="large-text" name="note" value="<?php echo esc_attr( (string) get_option( 'tmt_pcb_note', 'Miễn phí ráp máy, cài Windows và chạy thử 24 giờ trước khi giao.' ) ); ?>">
						<p class="description">Để trống thì không hiện (và bản in không ghi dòng "Ráp máy miễn phí").</p></td>
				</tr>
				<tr>
					<th scope="row"><label for="tmt-pcb-zalo">Số Zalo nhận tư vấn</label></th>
					<td><input id="tmt-pcb-zalo" type="text" name="zalo" value="<?php echo esc_attr( (string) get_option( 'tmt_pcb_zalo' ) ); ?>" placeholder="<?php echo esc_attr( $theme_zalo ? $theme_zalo : '0393080822' ); ?>">
						<p class="description">Để trống thì dùng số Zalo trong Tùy biến của theme.</p></td>
				</tr>
			</table>
			<?php submit_button( 'Lưu' ); ?>

			<h2>4. Thông số dùng để kiểm tra lắp ráp</h2>
			<p>Web đọc thông số từ <b>thuộc tính chung</b> của WooCommerce (Sản phẩm → Thuộc tính). Thiếu thông số thì web không đoán, chỉ nhắc khách hỏi tiệm.
				Số đọc theo số đầu tiên ("65W" hiểu là 65). Có / Không ghi "Có" hoặc "Không". "Socket hỗ trợ" của tản nhiệt chọn nhiều giá trị.</p>
			<table class="widefat striped" style="max-width: 900px">
				<thead><tr><th>Thông số</th><th>Thuộc tính</th><th>Gắn cho</th><th>Đang có</th></tr></thead>
				<tbody>
					<?php
					$for = array(
						'socket' => 'CPU, mainboard', 'cooler_sockets' => 'Tản nhiệt', 'chipset' => 'Mainboard', 'cpu_generation' => 'CPU',
						'ram_type' => 'RAM, mainboard', 'ram_slots' => 'Mainboard', 'ram_sticks' => 'RAM (bộ 2 thanh ghi 2)', 'ram_speed' => 'RAM',
						'max_ram_speed' => 'Mainboard', 'm2_slots' => 'Mainboard', 'sata_ports' => 'Mainboard', 'storage_interface' => 'SSD',
						'form_factor' => 'Mainboard, vỏ case (khổ lớn nhất chứa được)', 'tdp' => 'CPU, card đồ họa, tản nhiệt (mức chịu được)',
						'igpu' => 'CPU', 'cooler_included' => 'CPU', 'gpu_length' => 'Card đồ họa', 'gpu_power_connector' => 'Card đồ họa',
						'psu_recommended' => 'Card đồ họa', 'psu_watt' => 'Nguồn', 'psu_pcie_8pin' => 'Nguồn', 'psu_12vhpwr' => 'Nguồn',
						'max_gpu_length' => 'Vỏ case', 'max_cooler_height' => 'Vỏ case', 'cooler_height' => 'Tản nhiệt',
					);
					$missing = 0;
					$aliases = tmt_pcb_spec_aliases();
					foreach ( tmt_pcb_specs() as $key => $def ) :
						$slug   = tmt_pcb_attr_slug( $key );
						$exists = (bool) wc_attribute_taxonomy_id_by_name( $slug );
						if ( ! $exists ) {
							// Shop đã có thuộc tính tên khác cho thông số này (vd. pa_loai-ram): dùng luôn, không tạo thêm.
							foreach ( isset( $aliases[ $key ] ) ? $aliases[ $key ] : array() as $alias ) {
								if ( wc_attribute_taxonomy_id_by_name( $alias ) ) {
									$slug   = $alias;
									$exists = true;
									break;
								}
							}
						}
						$missing += $exists ? 0 : 1;
						?>
						<tr>
							<td><?php echo esc_html( $def[0] ); ?></td>
							<td><code>pa_<?php echo esc_html( $slug ); ?></code></td>
							<td><?php echo esc_html( isset( $for[ $key ] ) ? $for[ $key ] : '' ); ?></td>
							<td><?php echo $exists ? esc_html( tmt_pcb_attr_usage( 'pa_' . $slug ) . ' sản phẩm' ) : '<span style="color:#d63638">Chưa tạo</span>'; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p>
				<button type="submit" name="make_attributes" value="1" class="button button-secondary" <?php disabled( 0 === $missing ); ?>>
					<?php echo $missing ? esc_html( 'Tạo ' . $missing . ' thuộc tính còn thiếu (kèm giá trị gợi ý)' ) : 'Đã có đủ thuộc tính'; ?>
				</button>
			</p>
		</form>
	</div>
	<?php
}
