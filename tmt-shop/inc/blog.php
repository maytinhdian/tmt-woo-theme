<?php
/**
 * Mẹo vặt (Bài viết của WordPress) kiểu "dòng tin + video" giống web của app:
 * bài chọn = bài "Ghim lên đầu" (sticky), tab Mới nhất / Đọc nhiều / Video (?xem=), Mẹo 60 giây (bài có video),
 * bài viết có mục lục, chia sẻ, "Bài này có giúp ích?", sản phẩm gợi ý theo mã SKU.
 * Ô thêm cho từng bài (video, mức độ, sản phẩm gợi ý) nằm ở khung "Mẹo vặt" bên phải trang sửa bài.
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;

/** Tab danh sách: khóa ?xem= => [tên, icon Font Awesome]. */
function tmt_blog_tabs() {
	return array(
		''         => array( 'Mới nhất', 'fa-solid fa-clock-rotate-left' ),
		'doc-nhieu' => array( 'Đọc nhiều', 'fa-solid fa-fire' ),
		'video'    => array( 'Video', 'fa-solid fa-circle-play' ),
	);
}

function tmt_blog_tab() {
	$t = isset( $_GET['xem'] ) ? sanitize_key( wp_unslash( $_GET['xem'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	return array_key_exists( $t, tmt_blog_tabs() ) ? $t : '';
}

/** Trang danh sách bài (Trang Bài viết, chuyên mục, thẻ, tìm bài). */
function tmt_is_blog_list() {
	if ( is_search() ) {
		$pt = get_query_var( 'post_type' );
		return 'post' === $pt || ( '' === $pt && ! ( function_exists( 'is_woocommerce' ) && is_woocommerce() ) );
	}
	return is_home() || is_category() || is_tag() || is_author() || is_date();
}

/** Link trang Mẹo vặt (Trang Bài viết ở Cài đặt → Đọc). */
function tmt_blog_url() {
	$id = (int) get_option( 'page_for_posts' );
	return $id ? get_permalink( $id ) : home_url( '/' );
}

add_action(
	'pre_get_posts',
	function ( WP_Query $q ) {
		if ( is_admin() || ! $q->is_main_query() || ! ( $q->is_home() || $q->is_category() || $q->is_tag() || $q->is_search() ) ) {
			return;
		}
		if ( $q->is_search() && 'post' !== $q->get( 'post_type' ) ) {
			return;
		}
		$q->set( 'ignore_sticky_posts', true );
		$q->set( 'posts_per_page', 10 );
		$tab = tmt_blog_tab();
		if ( 'doc-nhieu' === $tab ) {
			$q->set(
				'meta_query',
				array(
					'relation' => 'OR',
					'views'    => array( 'key' => '_tmt_views', 'type' => 'NUMERIC' ),
					array( 'key' => '_tmt_views', 'compare' => 'NOT EXISTS' ),
				)
			);
			$q->set( 'orderby', array( 'views' => 'DESC', 'date' => 'DESC' ) );
		} elseif ( 'video' === $tab ) {
			$q->set( 'meta_query', array( array( 'key' => '_tmt_video_url', 'value' => '', 'compare' => '!=' ) ) );
		}
	}
);

/** Tab Đọc nhiều / Video, tìm bài: không cho Google lập chỉ mục (trùng nội dung). */
add_filter(
	'wp_robots',
	function ( $robots ) {
		if ( tmt_is_blog_list() && ( '' !== tmt_blog_tab() || is_search() ) ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
		}
		return $robots;
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( tmt_is_blog_list() || is_singular( 'post' ) ) {
			wp_enqueue_style( 'tmt-blog', TMT_SHOP_URI . '/assets/css/blog.css', array( 'tmt-theme' ), TMT_SHOP_VERSION );
			wp_enqueue_script( 'tmt-blog', TMT_SHOP_URI . '/assets/js/blog.js', array(), TMT_SHOP_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		}
	},
	20
);

/* ---------- Bề ngoài: icon và nền màu cho bài chưa có ảnh bìa ---------- */

/** Icon Font Awesome theo chuyên mục (dò từ khóa trong slug, chủ tiệm tự đặt chuyên mục). */
function tmt_blog_icon( $term ) {
	$icons = array(
		'windows' => 'fa-brands fa-windows', 'laptop' => 'fa-solid fa-laptop', 'wi-fi' => 'fa-solid fa-wifi', 'wifi' => 'fa-solid fa-wifi',
		'mang' => 'fa-solid fa-network-wired', 'linh-kien' => 'fa-solid fa-microchip', 'rap' => 'fa-solid fa-screwdriver-wrench',
		'bao-mat' => 'fa-solid fa-shield-halved', 'virus' => 'fa-solid fa-shield-virus', 'may-in' => 'fa-solid fa-print',
		'phan-mem' => 'fa-solid fa-window-restore', 'office' => 'fa-solid fa-file-word', 'dien-thoai' => 'fa-solid fa-mobile-screen',
		'game' => 'fa-solid fa-gamepad', 'camera' => 'fa-solid fa-video',
	);
	$slug = $term instanceof WP_Term ? $term->slug : '';
	foreach ( $icons as $key => $icon ) {
		if ( '' !== $slug && false !== strpos( $slug, $key ) ) {
			return $icon;
		}
	}
	return 'fa-regular fa-lightbulb';
}

/** Nền màu cố định theo chuyên mục (cùng chuyên mục cùng màu). */
function tmt_blog_gradient( $term ) {
	$g = array( 'g-blue', 'g-navy', 'g-teal', 'g-violet', 'g-red', 'g-yellow' );
	return $term instanceof WP_Term ? $g[ $term->term_id % count( $g ) ] : 'g-blue';
}

/** Chuyên mục chính của bài (bỏ "Chưa phân loại" khi bài có chuyên mục khác). */
function tmt_post_category( $post ) {
	$cats = get_the_category( $post );
	if ( count( $cats ) > 1 ) {
		$cats = array_values( array_filter( $cats, function ( $c ) { return (int) get_option( 'default_category' ) !== $c->term_id; } ) );
	}
	return $cats ? $cats[0] : null;
}

/**
 * Ảnh bìa: ảnh đại diện, không có thì nền màu + icon chuyên mục.
 *
 * @param array $args class, alt, eager, size.
 */
function tmt_blog_cover( $post, $args = array() ) {
	$post = get_post( $post );
	$args = wp_parse_args( $args, array( 'class' => '', 'alt' => null, 'eager' => false, 'size' => 'tmt-card' ) );
	$cat  = tmt_post_category( $post );
	if ( has_post_thumbnail( $post ) ) {
		$attr = array( 'alt' => null === $args['alt'] ? get_the_title( $post ) : $args['alt'] );
		if ( $args['eager'] ) {
			$attr['fetchpriority'] = 'high';
			$attr['loading']       = 'eager';
		}
		echo '<div class="mv-cv ' . esc_attr( $args['class'] ) . '">' . get_the_post_thumbnail( $post, $args['size'], $attr ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
		return;
	}
	echo '<div class="mv-cv fb ' . esc_attr( tmt_blog_gradient( $cat ) . ' ' . $args['class'] ) . '" aria-hidden="true"><span class="d1"></span><span class="d2"></span><i class="' . esc_attr( tmt_blog_icon( $cat ) ) . '"></i></div>';
}

/** Số phút đọc (220 chữ/phút, ít nhất 1). */
function tmt_reading_minutes( $post ) {
	$words = count( preg_split( '/\s+/u', trim( wp_strip_all_tags( get_post_field( 'post_content', $post ) ) ), -1, PREG_SPLIT_NO_EMPTY ) );
	return max( 1, (int) ceil( $words / 220 ) );
}

/** Mức độ: khóa => tên. */
function tmt_blog_levels() {
	return array( 'easy' => 'Dễ', 'medium' => 'Vừa', 'hard' => 'Khó' );
}

/** Tên người viết: tên hiển thị của tác giả, không có thì "Kỹ thuật {tên tiệm}". */
function tmt_blog_author( $post ) {
	$name = get_the_author_meta( 'display_name', get_post_field( 'post_author', $post ) );
	return $name ? $name : 'Kỹ thuật ' . tmt_opt( 'shop.brand' );
}

/** Chữ viết tắt trong ô tròn cạnh tên người viết (TMT, ABC...). */
function tmt_blog_initials() {
	$words = preg_split( '/\s+/u', trim( (string) tmt_opt( 'shop.brand' ) ), -1, PREG_SPLIT_NO_EMPTY );
	$out   = '';
	foreach ( $words as $w ) {
		if ( preg_match( '/^[\p{Lu}0-9]{2,4}$/u', $w ) ) {
			return $w;
		}
		$out .= mb_substr( $w, 0, 1 );
	}
	return mb_strtoupper( mb_substr( $out, 0, 3 ) );
}

/** Dòng thông tin dưới tiêu đề: người viết, ngày, phút đọc, mức độ. */
function tmt_blog_meta( $post, $with_author = true ) {
	$level  = get_post_meta( $post->ID, '_tmt_level', true );
	$levels = tmt_blog_levels();
	echo '<div class="mv-meta">';
	if ( $with_author ) {
		echo '<span class="hide-m d-inline-flex align-items-center"><span class="mv-av" aria-hidden="true">' . esc_html( tmt_blog_initials() ) . '</span>' . esc_html( tmt_blog_author( $post ) ) . '</span>';
	}
	echo '<time datetime="' . esc_attr( get_the_date( 'c', $post ) ) . '">' . esc_html( get_the_date( 'd/m/Y', $post ) ) . '</time>';
	echo '<span><i class="fa-regular fa-clock" aria-hidden="true"></i>' . (int) tmt_reading_minutes( $post ) . ' phút đọc</span>';
	if ( isset( $levels[ $level ] ) ) {
		echo '<span class="mv-lvl ' . esc_attr( $level ) . '">' . esc_html( $levels[ $level ] ) . '</span>';
	}
	echo '</div>';
}

/* ---------- Video ---------- */

/**
 * Video của bài: ['platform' => youtube|tiktok, 'id' => mã YouTube, 'url' => link, 'thumb' => ảnh nhỏ] hoặc null.
 */
function tmt_post_video( $post ) {
	$url = trim( (string) get_post_meta( get_post( $post )->ID, '_tmt_video_url', true ) );
	if ( '' === $url ) {
		return null;
	}
	if ( preg_match( '~(?:youtube\.com/(?:watch\?v=|shorts/|embed/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m ) ) {
		return array( 'platform' => 'youtube', 'id' => $m[1], 'url' => $url, 'thumb' => 'https://i.ytimg.com/vi/' . $m[1] . '/hqdefault.jpg' );
	}
	if ( false !== stripos( $url, 'tiktok.com' ) ) {
		return array( 'platform' => 'tiktok', 'id' => '', 'url' => $url, 'thumb' => get_the_post_thumbnail_url( $post, 'tmt-card' ) );
	}
	return null;
}

/** Bài có video ngắn cho khung "Mẹo 60 giây". */
function tmt_blog_shorts( $limit = 4 ) {
	return get_posts(
		array(
			'numberposts'         => $limit,
			'ignore_sticky_posts' => true,
			'meta_query'          => array( array( 'key' => '_tmt_video_url', 'value' => '', 'compare' => '!=' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
}

/** Khung "Mẹo 60 giây". */
function tmt_blog_shorts_box( $shorts ) {
	if ( ! $shorts ) {
		return;
	}
	?>
	<section class="mv-box" aria-labelledby="mv-shorts-t">
		<h2 id="mv-shorts-t"><i class="fa-solid fa-circle-play" aria-hidden="true"></i>Mẹo 60 giây</h2>
		<div class="mv-shorts">
			<?php foreach ( $shorts as $s ) : $v = tmt_post_video( $s ); $cat = tmt_post_category( $s ); ?>
				<a href="<?php echo esc_url( get_permalink( $s ) ); ?>">
					<?php if ( $v && $v['thumb'] ) : ?>
						<div class="mv-cv"><img src="<?php echo esc_url( $v['thumb'] ); ?>" alt="" loading="lazy"><span class="mv-play"><i class="fa-solid fa-play" aria-hidden="true"></i></span></div>
					<?php else : ?>
						<div class="mv-cv fb <?php echo esc_attr( tmt_blog_gradient( $cat ) ); ?>"><span class="mv-play"><i class="fa-solid fa-play" aria-hidden="true"></i></span></div>
					<?php endif; ?>
					<?php if ( $v ) : ?><span class="src"><i class="fa-brands fa-<?php echo esc_attr( $v['platform'] ); ?>" aria-hidden="true"></i><?php echo 'tiktok' === $v['platform'] ? 'TikTok' : 'YouTube'; ?></span><?php endif; ?>
					<b><span class="mv-clamp2"><?php echo esc_html( get_the_title( $s ) ); ?></span></b>
				</a>
			<?php endforeach; ?>
		</div>
		<?php
		$channels = array_filter( array( 'TikTok' => tmt_opt( 'footer.tiktok' ), 'YouTube' => tmt_opt( 'footer.youtube' ) ) );
		if ( $channels ) :
			$links = array();
			foreach ( $channels as $name => $link ) {
				$links[] = '<a href="' . esc_url( $link ) . '" target="_blank" rel="noopener">' . esc_html( $name ) . '</a>';
			}
			?>
			<p class="small mb-0 mt-2">Xem thêm trên <?php echo implode( ' và ', $links ); // phpcs:ignore WordPress.Security.EscapeOutput ?> của tiệm.</p>
		<?php endif; ?>
	</section>
	<?php
}

/** Khung vàng "Không muốn tự làm?": nhắn Zalo hoặc gọi tiệm. */
function tmt_blog_ask( $title = 'Không muốn tự làm?' ) {
	$phone = tmt_phone();
	$zalo  = tmt_zalo_url( tmt_opt( 'contact.zalo' ) );
	?>
	<div class="mv-ask">
		<i class="fa-solid fa-screwdriver-wrench big" aria-hidden="true"></i>
		<h2><?php echo esc_html( $title ); ?></h2>
		<p>Mang máy ghé tiệm, kiểm tra miễn phí và báo giá trước khi làm. Có câu hỏi thì nhắn tiệm, kỹ thuật viên trả lời.</p>
		<div class="btns">
			<?php if ( $zalo ) : ?><a href="<?php echo esc_url( $zalo ); ?>" target="_blank" rel="noopener nofollow" class="mv-btn zalo"><b>Zalo</b>Nhắn tiệm</a><?php endif; ?>
			<?php if ( $phone ) : ?><a href="tel:<?php echo esc_attr( tmt_digits( $phone ) ); ?>" class="mv-btn o"><i class="fa-solid fa-phone" aria-hidden="true"></i>Gọi tiệm</a><?php endif; ?>
		</div>
	</div>
	<?php
}

/* ---------- Bài viết: mục lục, lượt xem, đánh giá, sản phẩm gợi ý ---------- */

/** Gắn id cho H2/H3 trong bài để mục lục nhảy tới; trả mục lục [id, chữ, cấp] qua tmt_post_toc(). */
add_filter(
	'the_content',
	function ( $html ) {
		if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
			return $html;
		}
		$used = array();
		$toc  = array();
		$html = preg_replace_callback(
			'~<h([23])([^>]*)>(.*?)</h\1>~is',
			function ( $m ) use ( &$used, &$toc ) {
				$text = trim( wp_strip_all_tags( $m[3] ) );
				if ( preg_match( '/\sid=["\']([^"\']+)["\']/', $m[2], $idm ) ) {
					$id = $idm[1];
					$attrs = $m[2];
				} else {
					$base = sanitize_title( remove_accents( $text ) );
					$base = '' !== $base ? $base : 'muc';
					$id   = $base;
					for ( $i = 2; isset( $used[ $id ] ); $i++ ) {
						$id = $base . '-' . $i;
					}
					$attrs = $m[2] . ' id="' . esc_attr( $id ) . '"';
				}
				$used[ $id ] = true;
				$toc[]       = array( 'id' => $id, 'text' => $text, 'level' => (int) $m[1] );
				return '<h' . $m[1] . $attrs . '>' . $m[3] . '</h' . $m[1] . '>';
			},
			$html
		);
		$GLOBALS['tmt_post_toc'] = $toc;
		return $html;
	},
	20
);

/** Mục lục của bài đang xem (gọi sau the_content). */
function tmt_post_toc() {
	return isset( $GLOBALS['tmt_post_toc'] ) ? $GLOBALS['tmt_post_toc'] : array();
}

/** Danh sách id lưu trong cookie (bài đã xem / đã đánh giá), tối đa 60 id. */
function tmt_cookie_ids( $name ) {
	$raw = isset( $_COOKIE[ $name ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $name ] ) ) : '';
	return array_values( array_filter( array_map( 'absint', explode( '.', $raw ) ) ) );
}

function tmt_cookie_add( $name, $id ) {
	$ids   = tmt_cookie_ids( $name );
	$ids[] = (int) $id;
	$ids   = array_slice( array_unique( $ids ), -60 );
	setcookie( $name, implode( '.', $ids ), time() + 180 * DAY_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
	$_COOKIE[ $name ] = implode( '.', $ids );
}

/** Đếm lượt xem: mỗi trình duyệt một lần mỗi bài, bỏ qua người sửa bài. Không đổi ngày sửa bài. */
add_action(
	'template_redirect',
	function () {
		if ( ! is_singular( 'post' ) || is_preview() || current_user_can( 'edit_posts' ) ) {
			return;
		}
		$id = get_queried_object_id();
		if ( in_array( $id, tmt_cookie_ids( 'tmt_seen' ), true ) ) {
			return;
		}
		update_post_meta( $id, '_tmt_views', (int) get_post_meta( $id, '_tmt_views', true ) + 1 );
		tmt_cookie_add( 'tmt_seen', $id );
	}
);

/** "Bài này có giúp ích?": mỗi trình duyệt một lần mỗi bài. Gửi bằng fetch (trả JSON) hoặc form thường (quay lại bài). */
function tmt_blog_vote() {
	$id   = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
	$vote = isset( $_POST['vote'] ) && 'chua' === $_POST['vote'] ? 'chua' : 'co'; // phpcs:ignore WordPress.Security.NonceVerification
	$ok   = $id && 'post' === get_post_type( $id ) && 'publish' === get_post_status( $id );
	if ( $ok && ! in_array( $id, tmt_cookie_ids( 'tmt_voted' ), true ) ) {
		$key = 'co' === $vote ? '_tmt_helpful' : '_tmt_not_helpful';
		update_post_meta( $id, $key, (int) get_post_meta( $id, $key, true ) + 1 );
		tmt_cookie_add( 'tmt_voted', $id );
	}
	if ( isset( $_SERVER['HTTP_ACCEPT'] ) && false !== strpos( sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) ), 'application/json' ) ) {
		wp_send_json( array( 'ok' => $ok ), $ok ? 200 : 404 );
	}
	wp_safe_redirect( $ok ? get_permalink( $id ) . '#danh-gia' : home_url( '/' ) );
	exit;
}
add_action( 'admin_post_tmt_vote', 'tmt_blog_vote' );
add_action( 'admin_post_nopriv_tmt_vote', 'tmt_blog_vote' );

/** Sản phẩm gợi ý của bài: ô "Sản phẩm gợi ý" (mã SKU cách nhau dấu phẩy), chỉ sản phẩm đang hiện. */
function tmt_post_products( $post ) {
	if ( ! function_exists( 'wc_get_product_id_by_sku' ) ) {
		return array();
	}
	$skus = array_filter( array_map( 'trim', preg_split( '/[,\n;]+/', (string) get_post_meta( get_post( $post )->ID, '_tmt_products', true ) ) ) );
	$out  = array();
	foreach ( array_slice( $skus, 0, 8 ) as $sku ) {
		$p = wc_get_product( wc_get_product_id_by_sku( $sku ) );
		if ( $p && $p->is_type( 'variation' ) ) {
			$p = wc_get_product( $p->get_parent_id() );
		}
		if ( $p && 'publish' === $p->get_status() && 'hidden' !== $p->get_catalog_visibility() && ! isset( $out[ $p->get_id() ] ) ) {
			$out[ $p->get_id() ] = $p;
		}
	}
	return array_values( $out );
}

/** Bài cùng chuyên mục (đọc tiếp). */
function tmt_related_posts( $post, $limit = 3 ) {
	$cat = tmt_post_category( $post );
	return get_posts(
		array(
			'numberposts'         => $limit,
			'post__not_in'        => array( $post->ID ),
			'ignore_sticky_posts' => true,
			'cat'                 => $cat ? $cat->term_id : 0,
		)
	);
}

/* ---------- Khung "Mẹo vặt" ở trang sửa bài ---------- */

add_action(
	'add_meta_boxes_post',
	function () {
		add_meta_box( 'tmt-blog', 'Mẹo vặt', 'tmt_blog_metabox', 'post', 'side' );
	}
);

function tmt_blog_metabox( $post ) {
	wp_nonce_field( 'tmt_blog_meta', 'tmt_blog_nonce' );
	$level = get_post_meta( $post->ID, '_tmt_level', true );
	?>
	<p><label for="tmt-video"><b>Video ngắn</b> (link YouTube hoặc TikTok)</label>
		<input type="url" id="tmt-video" name="tmt_video_url" class="widefat" value="<?php echo esc_attr( get_post_meta( $post->ID, '_tmt_video_url', true ) ); ?>" placeholder="https://youtu.be/...">
		<span class="description">Bài có video hiện ở khung "Mẹo 60 giây" và tab Video.</span></p>
	<p><label for="tmt-level"><b>Mức độ</b></label>
		<select id="tmt-level" name="tmt_level" class="widefat">
			<option value="">Không ghi</option>
			<?php foreach ( tmt_blog_levels() as $k => $l ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $level, $k ); ?>><?php echo esc_html( $l ); ?></option><?php endforeach; ?>
		</select></p>
	<p><label for="tmt-products"><b>Sản phẩm gợi ý</b> (mã SKU, cách nhau dấu phẩy)</label>
		<textarea id="tmt-products" name="tmt_products" class="widefat" rows="2" placeholder="CPU-IN-I5-12400F, SSD-SS-990"><?php echo esc_textarea( get_post_meta( $post->ID, '_tmt_products', true ) ); ?></textarea></p>
	<p class="description">Bài chọn ở đầu trang Mẹo vặt: tick "Ghim lên đầu blog" (khung Bài viết). Lượt xem: <?php echo (int) get_post_meta( $post->ID, '_tmt_views', true ); ?> · Hữu ích: <?php echo (int) get_post_meta( $post->ID, '_tmt_helpful', true ); ?> / Chưa: <?php echo (int) get_post_meta( $post->ID, '_tmt_not_helpful', true ); ?></p>
	<?php
}

add_action(
	'save_post_post',
	function ( $id ) {
		if ( ! isset( $_POST['tmt_blog_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['tmt_blog_nonce'] ), 'tmt_blog_meta' ) || ! current_user_can( 'edit_post', $id ) ) {
			return;
		}
		$video = isset( $_POST['tmt_video_url'] ) ? esc_url_raw( wp_unslash( $_POST['tmt_video_url'] ) ) : '';
		$level = isset( $_POST['tmt_level'] ) ? sanitize_key( $_POST['tmt_level'] ) : '';
		$prods = isset( $_POST['tmt_products'] ) ? sanitize_textarea_field( wp_unslash( $_POST['tmt_products'] ) ) : '';
		foreach ( array( '_tmt_video_url' => $video, '_tmt_level' => isset( tmt_blog_levels()[ $level ] ) ? $level : '', '_tmt_products' => $prods ) as $k => $v ) {
			if ( '' === $v ) {
				delete_post_meta( $id, $k );
			} else {
				update_post_meta( $id, $k, $v );
			}
		}
	}
);
