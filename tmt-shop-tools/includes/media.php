<?php
/**
 * Quản lý ảnh (Anh 2 chọn làm cả 4 ý, 22/11/2026), giống module Ảnh của app:
 * 1. Tải lên thì tự thu nhỏ (cạnh dài tối đa 2400px), xoay đúng chiều, đổi sang WebP, bỏ EXIF/GPS.
 * 2. Ảnh tải lên từ trang sửa sản phẩm: tên file + chữ mô tả (alt) theo tên sản phẩm.
 * 3. Trang Media → Ảnh sản phẩm: lọc theo nhóm hàng, mục "Ảnh không dùng" + xóa hàng loạt, cài đặt.
 * 4. Trang sửa sản phẩm: kéo thả nhiều ảnh / dán Ctrl+V vào bộ ảnh (assets/media-admin.js).
 *
 * @package TMT_Shop_Tools
 */

defined( 'ABSPATH' ) || exit;

/** Cài đặt (Media → Ảnh sản phẩm → Cài đặt). */
function tmt_media_opts() {
	$d = array(
		'optimize' => true,
		'max'      => 2400,
		'webp'     => true,
		'quality'  => 82,
		'rename'   => true,
	);
	$o = get_option( 'tmt_media', array() );
	$o = is_array( $o ) ? $o : array();
	$o = array_merge( $d, array_intersect_key( $o, $d ) );
	$o['max']     = max( 800, min( 6000, (int) $o['max'] ) );
	$o['quality'] = max( 50, min( 95, (int) $o['quality'] ) );
	foreach ( array( 'optimize', 'webp', 'rename' ) as $k ) {
		$o[ $k ] = (bool) $o[ $k ];
	}
	return $o;
}

/* ---------- 2. Tên file theo sản phẩm ---------- */

/** Sản phẩm đang được tải ảnh lên (khung chọn ảnh trong trang sửa sản phẩm gửi kèm post_id). */
function tmt_media_upload_product() {
	$id = isset( $_REQUEST['post_id'] ) ? absint( $_REQUEST['post_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification -- WordPress đã kiểm nonce của async-upload.
	if ( ! $id ) {
		return null;
	}
	$p = get_post( $id );
	if ( ! $p || ! in_array( $p->post_type, array( 'product', 'product_variation' ), true ) ) {
		return null;
	}
	$title = 'product_variation' === $p->post_type ? get_the_title( $p ) : $p->post_title;
	$title = trim( (string) $title );
	return ( '' === $title || 'AUTO-DRAFT' === $title ) ? null : array( 'id' => $id, 'title' => $title );
}

add_filter(
	'wp_handle_upload_prefilter',
	function ( $file ) {
		if ( ! tmt_media_opts()['rename'] || empty( $file['name'] ) ) {
			return $file;
		}
		$p = tmt_media_upload_product();
		if ( $p ) {
			$ext  = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
			$slug = sanitize_title( $p['title'] ); // Bỏ dấu tiếng Việt, khoảng trắng → "-".
			if ( '' !== $slug && in_array( $ext, array( 'jpg', 'jpeg', 'png', 'webp', 'gif' ), true ) ) {
				$file['name'] = mb_substr( $slug, 0, 80 ) . '.' . $ext; // Trùng tên thì WordPress tự thêm -1, -2.
			}
		}
		return $file;
	}
);

/** Ảnh mới của sản phẩm: chữ mô tả (alt) + tiêu đề = tên sản phẩm. */
add_action(
	'add_attachment',
	function ( $id ) {
		if ( ! tmt_media_opts()['rename'] || ! wp_attachment_is_image( $id ) ) {
			return;
		}
		$p = tmt_media_upload_product();
		if ( ! $p ) {
			return;
		}
		update_post_meta( $id, '_wp_attachment_image_alt', $p['title'] );
		wp_update_post( array( 'ID' => $id, 'post_title' => $p['title'] ) );
	}
);

/* ---------- 1. Thu nhỏ + WebP + bỏ EXIF ---------- */

/** Thu nhỏ một file ảnh tại chỗ. Trả về [file, url, type] mới, hoặc null nếu không đổi. */
function tmt_media_optimize_file( $file, $url, $type ) {
	$o = tmt_media_opts();
	if ( ! in_array( $type, array( 'image/jpeg', 'image/png', 'image/webp' ), true ) || ! file_exists( $file ) ) {
		return null;
	}
	$editor = wp_get_image_editor( $file );
	if ( is_wp_error( $editor ) ) {
		return null;
	}
	if ( method_exists( $editor, 'maybe_exif_rotate' ) ) {
		$editor->maybe_exif_rotate(); // Ảnh chụp điện thoại: xoay đúng chiều trước khi bỏ EXIF.
	}
	$size = $editor->get_size();
	if ( $size['width'] > $o['max'] || $size['height'] > $o['max'] ) {
		$editor->resize( $o['max'], $o['max'], false );
	}
	$editor->set_quality( $o['quality'] );
	$to_webp = $o['webp'] && 'image/webp' !== $type && wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) );
	$mime    = $to_webp ? 'image/webp' : $type;
	$target  = $to_webp ? preg_replace( '/\.[^.\/]+$/', '', $file ) . '.webp' : $file;
	if ( $to_webp && file_exists( $target ) ) {
		$target = dirname( $file ) . '/' . wp_unique_filename( dirname( $file ), basename( $target ) );
	}
	$saved = $editor->save( $target, $mime ); // Lưu lại = bỏ EXIF/GPS (GD luôn bỏ, Imagick bỏ theo image_strip_meta).
	if ( is_wp_error( $saved ) || empty( $saved['path'] ) ) {
		return null;
	}
	// Ảnh WebP lớn hơn bản gốc (hiếm, ảnh đã nén sẵn): giữ bản gốc.
	if ( $to_webp && filesize( $saved['path'] ) >= filesize( $file ) && $size['width'] <= $o['max'] && $size['height'] <= $o['max'] ) {
		wp_delete_file( $saved['path'] );
		return null;
	}
	if ( $saved['path'] !== $file ) {
		wp_delete_file( $file );
	}
	return array(
		'file' => $saved['path'],
		'url'  => trailingslashit( dirname( $url ) ) . basename( $saved['path'] ),
		'type' => $mime,
	);
}

add_filter(
	'wp_handle_upload',
	function ( $upload, $context ) {
		if ( ! in_array( $context, array( 'upload', 'sideload' ), true ) || ! empty( $upload['error'] ) || ! tmt_media_opts()['optimize'] ) {
			return $upload;
		}
		$new = tmt_media_optimize_file( $upload['file'], $upload['url'], $upload['type'] );
		return $new ? array_merge( $upload, $new ) : $upload;
	},
	10,
	2
);

/** Ảnh đã thu theo cài đặt rồi thì WordPress không cần tạo thêm bản "-scaled". */
add_filter(
	'big_image_size_threshold',
	function ( $threshold ) {
		$o = tmt_media_opts();
		return $o['optimize'] ? max( $o['max'], 2560 ) : $threshold;
	}
);

/* ---------- 3. Trang Media → Ảnh sản phẩm ---------- */

/** Ảnh → các sản phẩm dùng nó (ảnh đại diện + bộ ảnh, cả biến thể). */
function tmt_media_product_map() {
	global $wpdb;
	$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		"SELECT pm.post_id, pm.meta_key, pm.meta_value, p.post_parent, p.post_type FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		WHERE pm.meta_key IN ('_thumbnail_id','_product_image_gallery') AND p.post_type IN ('product','product_variation') AND p.post_status NOT IN ('trash','auto-draft') AND pm.meta_value <> ''"
	);
	$map = array();
	foreach ( $rows as $r ) {
		$pid = 'product_variation' === $r->post_type ? (int) $r->post_parent : (int) $r->post_id;
		foreach ( array_filter( array_map( 'absint', explode( ',', $r->meta_value ) ) ) as $aid ) {
			$map[ $aid ][ $pid ] = $pid;
		}
	}
	return $map;
}

/** Các ảnh có nơi dùng (id chắc chắn): ảnh đại diện mọi bài, bộ ảnh SP, ảnh nhóm hàng, logo, biểu tượng web. */
function tmt_media_used_ids() {
	global $wpdb;
	$ids = array();
	foreach ( $wpdb->get_col( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key IN ('_thumbnail_id','_product_image_gallery') AND meta_value <> ''" ) as $v ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		foreach ( explode( ',', $v ) as $i ) {
			$ids[ (int) $i ] = true;
		}
	}
	foreach ( $wpdb->get_col( "SELECT meta_value FROM {$wpdb->termmeta} WHERE meta_key = 'thumbnail_id'" ) as $v ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$ids[ (int) $v ] = true;
	}
	$ids[ (int) get_option( 'site_icon' ) ]           = true;
	$ids[ (int) get_theme_mod( 'custom_logo' ) ]      = true;
	$ids[ (int) get_option( 'woocommerce_placeholder_image' ) ] = true;
	unset( $ids[0] );
	return $ids;
}

/** Đường dẫn ảnh (dạng 2026/11/ten, bỏ đuôi và "-scaled") có xuất hiện trong bài, meta, cài đặt, Tùy biến không. */
function tmt_media_path_used( $id ) {
	global $wpdb;
	$rel = (string) get_post_meta( $id, '_wp_attached_file', true );
	if ( '' === $rel ) {
		return true; // Không rõ file: coi như đang dùng cho chắc.
	}
	$base = preg_replace( '/(-scaled)?\.[^.\/]+$/', '', $rel );
	$like = '%' . $wpdb->esc_like( $base ) . '%';
	$json = '%' . $wpdb->esc_like( str_replace( '/', '\\/', $base ) ) . '%'; // Tùy biến lưu JSON: "2026\/11\/ten".
	// phpcs:disable WordPress.DB.DirectDatabaseQuery
	if ( $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM {$wpdb->posts} WHERE post_type NOT IN ('revision','attachment') AND post_status <> 'trash' AND (post_content LIKE %s OR post_content LIKE %s) LIMIT 1", $like, '%wp-image-' . (int) $id . '"%' ) ) ) {
		return true;
	}
	if ( $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM {$wpdb->postmeta} WHERE meta_key NOT LIKE %s AND (meta_value LIKE %s OR meta_value LIKE %s) LIMIT 1", $wpdb->esc_like( '_wp_attach' ) . '%', $like, $json ) ) ) {
		return true;
	}
	if ( $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM {$wpdb->options} WHERE option_name NOT LIKE %s AND (option_value LIKE %s OR option_value LIKE %s) LIMIT 1", $wpdb->esc_like( '_transient' ) . '%', $like, $json ) ) ) {
		return true;
	}
	return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM {$wpdb->termmeta} WHERE meta_value LIKE %s LIMIT 1", $like ) );
	// phpcs:enable
}

/** Ảnh không thấy dùng ở đâu (bỏ qua ảnh tải lên chưa tới 1 ngày). Lưu tạm 1 giờ; nút "Quét lại" xóa bộ nhớ tạm. */
function tmt_media_unused( $fresh = false ) {
	$cached = get_transient( 'tmt_media_unused' );
	if ( ! $fresh && is_array( $cached ) ) {
		return $cached;
	}
	$used  = tmt_media_used_ids();
	$all   = get_posts( array( 'post_type' => 'attachment', 'post_mime_type' => 'image', 'post_status' => 'inherit', 'numberposts' => -1, 'fields' => 'ids', 'date_query' => array( array( 'before' => '1 day ago' ) ) ) );
	$found = array();
	foreach ( $all as $id ) {
		if ( ! isset( $used[ $id ] ) && ! tmt_media_path_used( $id ) ) {
			$found[] = (int) $id;
		}
	}
	set_transient( 'tmt_media_unused', $found, HOUR_IN_SECONDS );
	return $found;
}

/** Dung lượng file gốc + các bản cỡ nhỏ của ảnh (byte). */
function tmt_media_bytes( $id ) {
	$file = get_attached_file( $id );
	if ( ! $file || ! file_exists( $file ) ) {
		return 0;
	}
	$n    = filesize( $file );
	$meta = wp_get_attachment_metadata( $id );
	if ( ! empty( $meta['sizes'] ) ) {
		foreach ( $meta['sizes'] as $s ) {
			$f = dirname( $file ) . '/' . $s['file'];
			$n += file_exists( $f ) ? filesize( $f ) : 0;
		}
	}
	if ( ! empty( $meta['original_image'] ) && file_exists( dirname( $file ) . '/' . $meta['original_image'] ) ) {
		$n += filesize( dirname( $file ) . '/' . $meta['original_image'] );
	}
	return $n;
}

add_action(
	'admin_menu',
	function () {
		add_media_page( 'Ảnh sản phẩm', 'Ảnh sản phẩm', 'upload_files', 'tmt-anh', 'tmt_media_page' );
	}
);

/** Xử lý: xóa ảnh không dùng, lưu cài đặt, điền chữ mô tả còn trống, quét lại. */
add_action(
	'admin_post_tmt_media',
	function () {
		check_admin_referer( 'tmt_media' );
		$do   = isset( $_POST['do'] ) ? sanitize_key( $_POST['do'] ) : '';
		$back = admin_url( 'upload.php?page=tmt-anh' );
		if ( 'delete' === $do && current_user_can( 'delete_posts' ) ) {
			$ok     = array_flip( tmt_media_unused() ); // Chỉ xóa ảnh còn nằm trong danh sách không dùng.
			$picked = isset( $_POST['ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['ids'] ) ) : array();
			$n      = 0;
			foreach ( $picked as $id ) {
				if ( isset( $ok[ $id ] ) && wp_delete_attachment( $id, true ) ) {
					++$n;
				}
			}
			delete_transient( 'tmt_media_unused' );
			wp_safe_redirect( add_query_arg( array( 'xem' => 'khong-dung', 'da-xoa' => $n ), $back ) );
			exit;
		}
		if ( 'rescan' === $do ) {
			delete_transient( 'tmt_media_unused' );
			wp_safe_redirect( add_query_arg( 'xem', 'khong-dung', $back ) );
			exit;
		}
		if ( 'settings' === $do && current_user_can( 'manage_options' ) ) {
			$in = isset( $_POST['tmt_media'] ) ? (array) wp_unslash( $_POST['tmt_media'] ) : array();
			update_option(
				'tmt_media',
				array(
					'optimize' => ! empty( $in['optimize'] ),
					'webp'     => ! empty( $in['webp'] ),
					'rename'   => ! empty( $in['rename'] ),
					'max'      => isset( $in['max'] ) ? absint( $in['max'] ) : 2400,
					'quality'  => isset( $in['quality'] ) ? absint( $in['quality'] ) : 82,
				)
			);
			wp_safe_redirect( add_query_arg( array( 'xem' => 'cai-dat', 'da-luu' => 1 ), $back ) );
			exit;
		}
		if ( 'alt' === $do && current_user_can( 'edit_posts' ) ) {
			$n = 0;
			foreach ( tmt_media_product_map() as $aid => $pids ) {
				if ( '' === trim( (string) get_post_meta( $aid, '_wp_attachment_image_alt', true ) ) ) {
					update_post_meta( $aid, '_wp_attachment_image_alt', get_the_title( reset( $pids ) ) );
					++$n;
				}
			}
			wp_safe_redirect( add_query_arg( array( 'xem' => 'cai-dat', 'da-dien' => $n ), $back ) );
			exit;
		}
		wp_safe_redirect( $back );
		exit;
	}
);

function tmt_media_kb( $bytes ) {
	return $bytes >= 1048576 ? number_format( $bytes / 1048576, 1, ',', '.' ) . ' MB' : number_format( max( 1, ceil( $bytes / 1024 ) ), 0, ',', '.' ) . ' KB';
}

/** Một ô ảnh trong lưới. */
function tmt_media_tile( $id, $pids, $check = false ) {
	$thumb = wp_get_attachment_image_url( $id, 'thumbnail' );
	$file  = basename( (string) get_attached_file( $id ) );
	$alt   = (string) get_post_meta( $id, '_wp_attachment_image_alt', true );
	echo '<li class="tmt-mt">';
	if ( $check ) {
		echo '<label class="ck"><input type="checkbox" name="ids[]" value="' . (int) $id . '"></label>';
	}
	echo '<a class="im" href="' . esc_url( get_edit_post_link( $id ) ) . '"><img src="' . esc_url( $thumb ) . '" alt="" loading="lazy"></a>';
	echo '<div class="nm" title="' . esc_attr( $file ) . '">' . esc_html( $file ) . '</div>';
	echo '<div class="mt">' . esc_html( tmt_media_kb( tmt_media_bytes( $id ) ) ) . ( '' === $alt ? ' · <span class="warn">chưa có mô tả</span>' : '' ) . '</div>';
	if ( $pids ) {
		$links = array();
		foreach ( array_slice( $pids, 0, 2 ) as $pid ) {
			$links[] = '<a href="' . esc_url( get_edit_post_link( $pid ) ) . '">' . esc_html( get_the_title( $pid ) ) . '</a>';
		}
		echo '<div class="us">' . implode( ', ', $links ) . ( count( $pids ) > 2 ? ' +' . ( count( $pids ) - 2 ) : '' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</li>';
}

function tmt_media_page() {
	// phpcs:disable WordPress.Security.NonceVerification -- chỉ đọc bộ lọc trên URL.
	$view = isset( $_GET['xem'] ) ? sanitize_key( $_GET['xem'] ) : 'theo-nhom';
	$cat  = isset( $_GET['nhom'] ) ? absint( $_GET['nhom'] ) : 0;
	$q    = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
	$pg   = max( 1, isset( $_GET['trang'] ) ? absint( $_GET['trang'] ) : 1 );
	// phpcs:enable
	$base = admin_url( 'upload.php?page=tmt-anh' );
	$tabs = array( 'theo-nhom' => 'Theo nhóm hàng', 'khong-dung' => 'Ảnh không dùng', 'cai-dat' => 'Cài đặt' );
	$per  = 60;
	wp_enqueue_style( 'tmt-media-admin', TMT_TOOLS_URL . 'assets/media-admin.css', array(), TMT_TOOLS_VERSION );
	echo '<div class="wrap tmt-media"><h1>Ảnh sản phẩm</h1><nav class="nav-tab-wrapper">';
	foreach ( $tabs as $k => $label ) {
		echo '<a class="nav-tab' . ( $k === $view ? ' nav-tab-active' : '' ) . '" href="' . esc_url( add_query_arg( 'xem', $k, $base ) ) . '">' . esc_html( $label ) . '</a>';
	}
	echo '</nav>';

	if ( 'cai-dat' === $view ) {
		$o = tmt_media_opts();
		if ( isset( $_GET['da-luu'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			echo '<div class="notice notice-success"><p>Đã lưu.</p></div>';
		}
		if ( isset( $_GET['da-dien'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			echo '<div class="notice notice-success"><p>Đã điền chữ mô tả cho ' . (int) $_GET['da-dien'] . ' ảnh.</p></div>'; // phpcs:ignore WordPress.Security.NonceVerification
		}
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'tmt_media' );
		echo '<input type="hidden" name="action" value="tmt_media"><input type="hidden" name="do" value="settings"><table class="form-table">';
		echo '<tr><th>Khi tải ảnh lên</th><td><label><input type="checkbox" name="tmt_media[optimize]" value="1"' . checked( $o['optimize'], true, false ) . '> Tự thu nhỏ, xoay đúng chiều, bỏ thông tin GPS / máy chụp</label><br>';
		echo '<label><input type="checkbox" name="tmt_media[webp]" value="1"' . checked( $o['webp'], true, false ) . '> Đổi sang WebP (nhẹ hơn JPG khoảng 30%)' . ( wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ? '' : ' <b>— hosting này chưa hỗ trợ WebP, ảnh giữ JPG/PNG</b>' ) . '</label></td></tr>';
		echo '<tr><th><label for="tmt-mx">Cạnh dài tối đa</label></th><td><input id="tmt-mx" type="number" min="800" max="6000" step="100" name="tmt_media[max]" value="' . (int) $o['max'] . '" class="small-text"> px</td></tr>';
		echo '<tr><th><label for="tmt-q">Chất lượng</label></th><td><input id="tmt-q" type="number" min="50" max="95" name="tmt_media[quality]" value="' . (int) $o['quality'] . '" class="small-text"> (50–95, nên để 82)</td></tr>';
		echo '<tr><th>Ảnh sản phẩm</th><td><label><input type="checkbox" name="tmt_media[rename]" value="1"' . checked( $o['rename'], true, false ) . '> Đặt tên file + chữ mô tả theo tên sản phẩm (ảnh tải lên ở trang sửa sản phẩm)</label></td></tr>';
		echo '</table>';
		submit_button( 'Lưu' );
		echo '</form><hr><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'tmt_media' );
		echo '<input type="hidden" name="action" value="tmt_media"><input type="hidden" name="do" value="alt"><p>Ảnh sản phẩm cũ chưa có chữ mô tả: điền bằng tên sản phẩm (tốt cho Google, không đổi tên file).</p>';
		submit_button( 'Điền chữ mô tả còn trống', 'secondary', 'submit', false );
		echo '</form></div>';
		return;
	}

	$map = tmt_media_product_map();
	if ( 'khong-dung' === $view ) {
		$ids   = tmt_media_unused();
		$total = 0;
		foreach ( $ids as $id ) {
			$total += tmt_media_bytes( $id );
		}
		if ( isset( $_GET['da-xoa'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			echo '<div class="notice notice-success"><p>Đã xóa ' . (int) $_GET['da-xoa'] . ' ảnh.</p></div>'; // phpcs:ignore WordPress.Security.NonceVerification
		}
		echo '<div class="tmt-mhead"><span><b>' . count( $ids ) . ' ảnh</b> không thấy dùng ở sản phẩm, bài viết, trang, nhóm hàng hay Tùy biến (' . esc_html( tmt_media_kb( $total ) ) . '). Ảnh mới tải lên chưa tới 1 ngày không tính.</span>';
		echo ' <form class="inl" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'tmt_media' );
		echo '<input type="hidden" name="action" value="tmt_media"><input type="hidden" name="do" value="rescan"><button class="button">Quét lại</button></form></div>';
		if ( ! $ids ) {
			echo '<p>Không có ảnh thừa.</p></div>';
			return;
		}
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" data-tmt-unused>';
		wp_nonce_field( 'tmt_media' );
		echo '<input type="hidden" name="action" value="tmt_media"><input type="hidden" name="do" value="delete">';
		echo '<p class="tmt-mbar"><label><input type="checkbox" data-all> Chọn tất cả</label> <button class="button button-primary" onclick="return confirm(\'Xóa hẳn các ảnh đã chọn? Không lấy lại được.\')">Xóa ảnh đã chọn</button></p><ul class="tmt-mgrid">';
		foreach ( $ids as $id ) {
			tmt_media_tile( $id, array(), true );
		}
		echo '</ul></form><script>document.querySelector("[data-all]").addEventListener("change",function(e){document.querySelectorAll("[data-tmt-unused] input[name=\'ids[]\']").forEach(function(c){c.checked=e.target.checked;});});</script></div>';
		return;
	}

	// Theo nhóm hàng.
	$ids = array_keys( $map );
	if ( $cat ) {
		$in  = get_posts( array( 'post_type' => 'product', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'tax_query' => array( array( 'taxonomy' => 'product_cat', 'terms' => $cat, 'include_children' => true ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		$in  = array_flip( $in );
		$ids = array_values(
			array_filter(
				$ids,
				function ( $aid ) use ( $map, $in ) {
					return (bool) array_intersect_key( $map[ $aid ], $in );
				}
			)
		);
	}
	if ( '' !== $q ) {
		$needle = mb_strtolower( remove_accents( $q ) );
		$ids    = array_values(
			array_filter(
				$ids,
				function ( $aid ) use ( $map, $needle ) {
					$hay = basename( (string) get_attached_file( $aid ) );
					foreach ( $map[ $aid ] as $pid ) {
						$hay .= ' ' . get_the_title( $pid ) . ' ' . get_post_meta( $pid, '_sku', true );
					}
					return false !== strpos( mb_strtolower( remove_accents( $hay ) ), $needle );
				}
			)
		);
	}
	rsort( $ids );
	$count = count( $ids );
	$pages = max( 1, (int) ceil( $count / $per ) );
	echo '<form class="tmt-mfilter" method="get"><input type="hidden" name="page" value="tmt-anh"><input type="hidden" name="xem" value="theo-nhom">';
	wp_dropdown_categories( array( 'taxonomy' => 'product_cat', 'name' => 'nhom', 'selected' => $cat, 'show_option_all' => 'Mọi nhóm hàng', 'hierarchical' => true, 'hide_empty' => false, 'value_field' => 'term_id' ) );
	echo ' <input type="search" name="q" value="' . esc_attr( $q ) . '" placeholder="Tên sản phẩm, SKU, tên file"> <button class="button">Lọc</button> <span class="cnt">' . (int) $count . ' ảnh</span></form>';
	echo '<ul class="tmt-mgrid">';
	foreach ( array_slice( $ids, ( $pg - 1 ) * $per, $per ) as $id ) {
		tmt_media_tile( $id, array_values( $map[ $id ] ) );
	}
	echo '</ul>';
	if ( $pages > 1 ) {
		echo '<p class="tmt-mpager">';
		for ( $i = 1; $i <= $pages; $i++ ) {
			echo $i === $pg ? '<b>' . (int) $i . '</b> ' : '<a href="' . esc_url( add_query_arg( array( 'xem' => 'theo-nhom', 'nhom' => $cat, 'q' => $q, 'trang' => $i ), $base ) ) . '">' . (int) $i . '</a> ';
		}
		echo '</p>';
	}
	echo '</div>';
}

/* ---------- 4. Trang sửa sản phẩm: kéo thả / dán ảnh vào bộ ảnh ---------- */

add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || 'product' !== get_post_type() ) {
			return;
		}
		wp_enqueue_style( 'tmt-media-admin', TMT_TOOLS_URL . 'assets/media-admin.css', array(), TMT_TOOLS_VERSION );
		wp_enqueue_script( 'tmt-media-admin', TMT_TOOLS_URL . 'assets/media-admin.js', array( 'jquery' ), TMT_TOOLS_VERSION, true );
		wp_localize_script(
			'tmt-media-admin',
			'tmtMedia',
			array(
				'url'   => admin_url( 'async-upload.php' ),
				'nonce' => wp_create_nonce( 'media-form' ),
				'post'  => get_the_ID(),
			)
		);
	}
);
