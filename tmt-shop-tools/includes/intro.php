<?php
/**
 * "Giới thiệu nâng cao" của sản phẩm (chép từ app: Storefront/Support/IntroBlocks): các khối kiểu trang của hãng
 * xếp dưới khung mua. Soạn trong khung "Giới thiệu nâng cao" ở trang sửa sản phẩm, lưu meta _tmt_intro (mảng khối
 * ['type' => 'media', 'title' => ..., ...]). Theme hiển thị bằng template-parts/product/intro.php từ tmt_intro_blocks().
 *
 * Thêm loại khối: khai báo ở tmt_intro_types() + tmt_intro_fields(), nhánh trong tmt_intro_clean() / tmt_intro_blocks(),
 * ô soạn trong tmt_intro_block_form() và phần hiển thị trong theme.
 *
 * @package TMT_Shop_Tools
 */

defined( 'ABSPATH' ) || exit;

const TMT_INTRO_MAX_BLOCKS = 30;
const TMT_INTRO_MAX_ITEMS  = 12;
const TMT_INTRO_MAX_ROWS   = 20;
const TMT_INTRO_COLS       = 3;

/** Loại khối => [tên, icon Font Awesome, mô tả ngắn]. */
function tmt_intro_types() {
	return array(
		'hero'     => array( 'Ảnh bìa', 'fa-image', 'Ảnh lớn + tiêu đề, nền tối' ),
		'stats'    => array( 'Số nổi bật', 'fa-hashtag', '2 đến 6 con số lớn' ),
		'media'    => array( 'Ảnh + chữ', 'fa-table-columns', 'Hai cột, ảnh trái hoặc phải' ),
		'features' => array( 'Lưới tính năng', 'fa-border-all', 'Icon + tiêu đề + mô tả' ),
		'video'    => array( 'Video', 'fa-circle-play', 'YouTube' ),
		'compare'  => array( 'So sánh', 'fa-scale-balanced', 'Bảng 2-3 cột, cột đầu tô màu' ),
		'box'      => array( 'Trong hộp có gì', 'fa-box-open', 'Icon + chữ ngắn' ),
		'text'     => array( 'Đoạn văn tự do', 'fa-pen-nib', 'Chữ dài, có thể dùng HTML đơn giản' ),
	);
}

/** Ô của từng loại khối (ngoài type và nav). */
function tmt_intro_fields() {
	return array(
		'hero'     => array( 'eyebrow', 'title', 'text', 'image', 'image_alt' ),
		'stats'    => array( 'title', 'items' ),
		'media'    => array( 'eyebrow', 'title', 'body', 'image', 'image_alt', 'side', 'bg' ),
		'features' => array( 'title', 'text', 'items' ),
		'video'    => array( 'title', 'text', 'url' ),
		'compare'  => array( 'title', 'text', 'columns', 'rows' ),
		'box'      => array( 'title', 'items' ),
		'text'     => array( 'title', 'body' ),
	);
}

/** Ô của một dòng trong danh sách items theo loại khối. */
function tmt_intro_item_keys( $type ) {
	$keys = array(
		'stats'    => array( 'value', 'title' ),
		'features' => array( 'icon', 'title', 'text' ),
		'box'      => array( 'icon', 'title' ),
	);
	return isset( $keys[ $type ] ) ? $keys[ $type ] : array();
}

function tmt_intro_sides() {
	return array( 'auto' => 'Tự xen kẽ', 'left' => 'Ảnh bên trái', 'right' => 'Ảnh bên phải' );
}

function tmt_intro_backgrounds() {
	return array( 'white' => 'Trắng', 'soft' => 'Xám nhạt', 'dark' => 'Tối' );
}

/** ID video YouTube từ link (watch?v=, youtu.be/, shorts/, embed/), sai thì ''. */
function tmt_intro_youtube_id( $url ) {
	return preg_match( '~(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|shorts/|embed/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~', (string) $url, $m ) ? $m[1] : '';
}

/**
 * Làm sạch khối gửi từ form: chỉ giữ ô của loại khối, cắt khoảng trắng, lọc HTML, bỏ dòng / khối trống.
 *
 * @return list<array<string, mixed>>
 */
function tmt_intro_clean( $blocks ) {
	$str    = fn( $v, $max ) => mb_substr( trim( sanitize_text_field( (string) $v ) ), 0, $max );
	$fields = tmt_intro_fields();
	$out    = array();
	foreach ( is_array( $blocks ) ? array_values( $blocks ) : array() as $b ) {
		$type = is_array( $b ) && isset( $b['type'] ) ? (string) $b['type'] : '';
		if ( ! isset( $fields[ $type ] ) ) {
			continue;
		}
		$block = array( 'type' => $type );
		$nav   = $str( isset( $b['nav'] ) ? $b['nav'] : '', 30 );
		if ( '' !== $nav ) {
			$block['nav'] = $nav;
		}
		foreach ( $fields[ $type ] as $f ) {
			$v = isset( $b[ $f ] ) ? $b[ $f ] : '';
			switch ( $f ) {
				case 'body':
					$v = trim( wp_kses_post( (string) $v ) );
					break;
				case 'text':
					$v = mb_substr( trim( sanitize_textarea_field( (string) $v ) ), 0, 600 );
					break;
				case 'image':
					$v = absint( $v );
					$v = $v && wp_attachment_is_image( $v ) ? $v : 0;
					break;
				case 'url':
					$v = esc_url_raw( trim( (string) $v ) );
					break;
				case 'side':
					$v = array_key_exists( (string) $v, tmt_intro_sides() ) ? $v : 'auto';
					break;
				case 'bg':
					$v = array_key_exists( (string) $v, tmt_intro_backgrounds() ) ? $v : 'white';
					break;
				case 'items':
					$rows = array();
					foreach ( is_array( $v ) ? $v : array() as $item ) {
						$row = array();
						foreach ( tmt_intro_item_keys( $type ) as $k ) {
							$x         = isset( $item[ $k ] ) ? $item[ $k ] : '';
							$row[ $k ] = 'icon' === $k
								? ( preg_match( '/^fa-(solid|regular|brands) fa-[a-z0-9-]{1,40}$/', trim( (string) $x ) ) ? trim( (string) $x ) : '' )
								: $str( $x, 'value' === $k ? 24 : ( 'text' === $k ? 200 : 80 ) );
						}
						if ( array_filter( $row, fn( $x, $k ) => 'icon' !== $k && '' !== $x, ARRAY_FILTER_USE_BOTH ) ) {
							$rows[] = $row;
						}
					}
					$v = array_slice( $rows, 0, TMT_INTRO_MAX_ITEMS );
					break;
				case 'columns':
					$cols = array();
					for ( $c = 0; $c < TMT_INTRO_COLS; $c++ ) {
						$cols[] = $str( is_array( $v ) && isset( $v[ $c ] ) ? $v[ $c ] : '', 60 );
					}
					$v = $cols;
					break;
				case 'rows':
					$rows = array();
					foreach ( is_array( $v ) ? $v : array() as $r ) {
						$vals = array();
						for ( $c = 0; $c < TMT_INTRO_COLS; $c++ ) {
							$vals[] = $str( isset( $r['values'][ $c ] ) ? $r['values'][ $c ] : '', 80 );
						}
						$label = $str( isset( $r['label'] ) ? $r['label'] : '', 60 );
						if ( '' !== $label || '' !== implode( '', $vals ) ) {
							$rows[] = array( 'label' => $label, 'values' => $vals );
						}
					}
					$v = array_slice( $rows, 0, TMT_INTRO_MAX_ROWS );
					break;
				default:
					$v = $str( $v, 'title' === $f ? 160 : 60 );
			}
			$block[ $f ] = $v;
		}
		// Bỏ khối không có nội dung.
		$content = $block;
		unset( $content['type'], $content['nav'], $content['side'], $content['bg'] );
		if ( isset( $content['columns'] ) ) {
			$content['columns'] = implode( '', $content['columns'] );
		}
		if ( ! array_filter( $content, fn( $x ) => '' !== $x && array() !== $x && 0 !== $x ) ) {
			continue;
		}
		$out[] = $block;
		if ( count( $out ) >= TMT_INTRO_MAX_BLOCKS ) {
			break;
		}
	}
	return $out;
}

/** Khối đã lưu của sản phẩm (biến thể dùng của sản phẩm cha). */
function tmt_intro_get( $product_id ) {
	$product = wc_get_product( $product_id );
	$id      = $product && $product->get_parent_id() ? $product->get_parent_id() : (int) $product_id;
	$v       = get_post_meta( $id, '_tmt_intro', true );
	return is_array( $v ) ? $v : array();
}

/** Tên hiện trên mục lục, null = không có mục. */
function tmt_intro_nav_label( array $b ) {
	if ( ! empty( $b['nav'] ) ) {
		return $b['nav'];
	}
	switch ( $b['type'] ) {
		case 'hero':
			return 'Tổng quan';
		case 'media':
			return '' !== $b['eyebrow'] ? $b['eyebrow'] : ( '' !== $b['title'] ? $b['title'] : null );
		case 'features':
			return '' !== $b['title'] ? $b['title'] : 'Tiện ích';
		case 'video':
			return 'Video';
		case 'compare':
			return 'So sánh';
		case 'text':
			return '' !== $b['title'] ? $b['title'] : null;
	}
	return null;
}

/**
 * Dữ liệu hiển thị cho theme: thêm label, anchor, image_url, html, reverse, youtube, columns.
 *
 * @return list<array<string, mixed>>
 */
function tmt_intro_blocks( WC_Product $product ) {
	$fields = tmt_intro_fields();
	$out    = array();
	$media  = 0;
	$used   = array();
	foreach ( array_values( tmt_intro_get( $product->get_id() ) ) as $i => $b ) {
		if ( ! isset( $b['type'], $fields[ $b['type'] ] ) ) {
			continue;
		}
		$b       = array_merge( array_fill_keys( $fields[ $b['type'] ], '' ), $b );
		$b['label'] = tmt_intro_nav_label( $b );
		$slug    = $b['label'] ? sanitize_title( $b['label'] ) : '';
		$anchor  = 'gt-' . ( '' !== $slug ? $slug : ( $i + 1 ) );
		$used[ $anchor ] = isset( $used[ $anchor ] ) ? $used[ $anchor ] + 1 : 1;
		$b['anchor']     = $used[ $anchor ] > 1 ? $anchor . '-' . $used[ $anchor ] : $anchor;
		if ( ! empty( $b['image'] ) ) {
			$b['image_url'] = wp_get_attachment_image_url( (int) $b['image'], 'hero' === $b['type'] ? 'full' : 'large' );
		}
		if ( '' !== (string) ( isset( $b['body'] ) ? $b['body'] : '' ) ) {
			$b['html'] = wpautop( wp_kses_post( $b['body'] ) );
		}
		if ( 'media' === $b['type'] ) {
			$b['reverse'] = 'right' === $b['side'] || ( 'auto' === $b['side'] && 1 === $media % 2 );
			++$media;
		}
		if ( 'video' === $b['type'] ) {
			$b['youtube'] = tmt_intro_youtube_id( $b['url'] );
			if ( '' === $b['youtube'] ) {
				continue;
			}
		}
		foreach ( array( 'items', 'rows' ) as $list ) {
			if ( isset( $b[ $list ] ) && ! is_array( $b[ $list ] ) ) {
				$b[ $list ] = array();
			}
		}
		if ( 'compare' === $b['type'] ) {
			$cols    = array_pad( is_array( $b['columns'] ) ? $b['columns'] : array(), TMT_INTRO_COLS, '' );
			$cols[0] = '' !== $cols[0] ? $cols[0] : $product->get_name();
			$count   = 1;
			foreach ( $cols as $c => $name ) {
				$has = (bool) array_filter( $b['rows'], fn( $r ) => '' !== ( isset( $r['values'][ $c ] ) ? $r['values'][ $c ] : '' ) );
				if ( '' !== $name || $has ) {
					$count = $c + 1;
				}
			}
			$b['columns'] = array_slice( $cols, 0, max( 2, $count ) );
		}
		$out[] = $b;
	}
	return $out;
}

/** Ô so sánh: "+" thành dấu tích, "-" thành dấu gạch chéo; còn lại là chữ. */
function tmt_intro_compare_cell( $value ) {
	if ( '+' === $value ) {
		return array( 'icon' => 'fa-solid fa-check', 'class' => 'yes', 'text' => 'Có' );
	}
	if ( '-' === $value ) {
		return array( 'icon' => 'fa-solid fa-xmark', 'class' => 'no', 'text' => 'Không' );
	}
	return array( 'icon' => null, 'class' => '', 'text' => $value );
}

/* ---------- Khung soạn trong trang sửa sản phẩm ---------- */

add_action(
	'add_meta_boxes_product',
	function () {
		add_meta_box( 'tmt-intro', 'Giới thiệu nâng cao', 'tmt_intro_meta_box', 'product', 'normal', 'default' );
	}
);

add_action(
	'admin_enqueue_scripts',
	function () {
		$screen = get_current_screen();
		if ( ! $screen || 'product' !== $screen->id ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'tmt-intro-admin', TMT_TOOLS_URL . 'assets/intro-admin.css', array(), TMT_TOOLS_VERSION );
		wp_enqueue_script( 'tmt-intro-admin', TMT_TOOLS_URL . 'assets/intro-admin.js', array(), TMT_TOOLS_VERSION, true );
	}
);

/** Một dòng trong danh sách items / rows của khối. $bi, $ri là khóa (hoặc chỗ thay __b__ / __r__). */
function tmt_intro_item_form( $type, $bi, $ri, array $item ) {
	$name = "tmt_intro[{$bi}]";
	echo '<div class="tmt-in-row">';
	if ( 'compare' === $type ) {
		printf( '<input type="text" name="%s[rows][%s][label]" value="%s" placeholder="Tiêu chí" class="w-label">', esc_attr( $name ), esc_attr( $ri ), esc_attr( isset( $item['label'] ) ? $item['label'] : '' ) );
		for ( $c = 0; $c < TMT_INTRO_COLS; $c++ ) {
			printf( '<input type="text" name="%s[rows][%s][values][%d]" value="%s" placeholder="Cột %d (+ / - / chữ)">', esc_attr( $name ), esc_attr( $ri ), (int) $c, esc_attr( isset( $item['values'][ $c ] ) ? $item['values'][ $c ] : '' ), (int) $c + 1 );
		}
	} else {
		$ph = array( 'icon' => 'fa-solid fa-bolt', 'value' => 'Số (vd 120Hz)', 'title' => 'stats' === $type ? 'Ghi chú (vd Tần số quét)' : 'Tiêu đề', 'text' => 'Mô tả ngắn' );
		foreach ( tmt_intro_item_keys( $type ) as $k ) {
			printf( '<input type="text" name="%s[items][%s][%s]" value="%s" placeholder="%s" class="w-%s">', esc_attr( $name ), esc_attr( $ri ), esc_attr( $k ), esc_attr( isset( $item[ $k ] ) ? $item[ $k ] : '' ), esc_attr( $ph[ $k ] ), esc_attr( $k ) );
		}
	}
	echo '<button type="button" class="button-link tmt-in-x" data-in-del-row aria-label="Xóa dòng">×</button></div>';
}

/** Khung soạn một khối. $bi = khóa khối (hoặc __b__ trong mẫu). */
function tmt_intro_block_form( $type, $bi, array $b ) {
	$types = tmt_intro_types();
	$name  = "tmt_intro[{$bi}]";
	$val   = fn( $k ) => isset( $b[ $k ] ) ? $b[ $k ] : '';
	$text  = function ( $k, $label, $ph = '' ) use ( $name, $val ) {
		printf( '<label><span>%s</span><input type="text" name="%s[%s]" value="%s" placeholder="%s"></label>', esc_html( $label ), esc_attr( $name ), esc_attr( $k ), esc_attr( $val( $k ) ), esc_attr( $ph ) );
	};
	$area = function ( $k, $label, $rows = 3, $ph = '' ) use ( $name, $val ) {
		printf( '<label class="full"><span>%s</span><textarea name="%s[%s]" rows="%d" placeholder="%s">%s</textarea></label>', esc_html( $label ), esc_attr( $name ), esc_attr( $k ), (int) $rows, esc_attr( $ph ), esc_textarea( $val( $k ) ) );
	};
	$select = function ( $k, $label, array $choices ) use ( $name, $val ) {
		printf( '<label><span>%s</span><select name="%s[%s]">', esc_html( $label ), esc_attr( $name ), esc_attr( $k ) );
		foreach ( $choices as $v => $l ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $v ), selected( $val( $k ), $v, false ), esc_html( $l ) );
		}
		echo '</select></label>';
	};
	?>
	<div class="tmt-in-block" data-type="<?php echo esc_attr( $type ); ?>">
		<input type="hidden" name="<?php echo esc_attr( $name ); ?>[type]" value="<?php echo esc_attr( $type ); ?>">
		<div class="tmt-in-head">
			<b><?php echo esc_html( $types[ $type ][0] ); ?></b>
			<input type="text" name="<?php echo esc_attr( $name ); ?>[nav]" value="<?php echo esc_attr( $val( 'nav' ) ); ?>" placeholder="Tên trên mục lục (tùy chọn)" class="tmt-in-nav">
			<span class="tmt-in-tools">
				<button type="button" class="button" data-in-up aria-label="Lên">↑</button>
				<button type="button" class="button" data-in-down aria-label="Xuống">↓</button>
				<button type="button" class="button tmt-in-del" data-in-del aria-label="Xóa khối">Xóa</button>
			</span>
		</div>
		<div class="tmt-in-body">
			<?php
			$fields = tmt_intro_fields()[ $type ];
			if ( in_array( 'eyebrow', $fields, true ) ) {
				$text( 'eyebrow', 'Chữ nhỏ trên tiêu đề', 'vd HIỆU NĂNG' );
			}
			if ( in_array( 'title', $fields, true ) ) {
				$text( 'title', 'Tiêu đề' );
			}
			if ( in_array( 'text', $fields, true ) ) {
				$area( 'text', 'hero' === $type ? 'Câu dẫn' : 'Mô tả ngắn', 2 );
			}
			if ( in_array( 'url', $fields, true ) ) {
				$text( 'url', 'Link YouTube', 'https://www.youtube.com/watch?v=...' );
			}
			if ( in_array( 'body', $fields, true ) ) {
				$area( 'body', 'Nội dung', 6, 'Mỗi đoạn cách một dòng. Có thể dùng HTML: <b>, <ul><li>, <a>.' );
			}
			if ( in_array( 'image', $fields, true ) ) {
				$img = $val( 'image' ) ? wp_get_attachment_image_url( (int) $val( 'image' ), 'thumbnail' ) : '';
				?>
				<div class="tmt-in-img">
					<span>Ảnh</span>
					<input type="hidden" name="<?php echo esc_attr( $name ); ?>[image]" value="<?php echo (int) $val( 'image' ); ?>">
					<img src="<?php echo esc_url( (string) $img ); ?>" alt=""<?php echo $img ? '' : ' hidden'; ?>>
					<button type="button" class="button" data-in-img>Chọn ảnh</button>
					<button type="button" class="button-link" data-in-img-clear>Bỏ ảnh</button>
				</div>
				<?php
				$text( 'image_alt', 'Mô tả ảnh (cho Google)' );
			}
			if ( in_array( 'side', $fields, true ) ) {
				$select( 'side', 'Vị trí ảnh', tmt_intro_sides() );
				$select( 'bg', 'Nền', tmt_intro_backgrounds() );
			}
			if ( 'compare' === $type ) {
				echo '<div class="tmt-in-cols full"><span>Tên cột (cột 1 trống = tên sản phẩm này)</span>';
				for ( $c = 0; $c < TMT_INTRO_COLS; $c++ ) {
					printf( '<input type="text" name="%s[columns][%d]" value="%s" placeholder="Cột %d">', esc_attr( $name ), (int) $c, esc_attr( isset( $b['columns'][ $c ] ) ? $b['columns'][ $c ] : '' ), (int) $c + 1 );
				}
				echo '</div>';
			}
			if ( in_array( 'items', $fields, true ) || 'compare' === $type ) {
				$list = 'compare' === $type ? ( isset( $b['rows'] ) ? (array) $b['rows'] : array() ) : ( isset( $b['items'] ) ? (array) $b['items'] : array() );
				$hint = array(
					'stats'    => 'Mỗi dòng: con số + ghi chú.',
					'features' => 'Icon dạng "fa-solid fa-bolt" (xem tên ở fontawesome.com, bản miễn phí).',
					'box'      => 'Mỗi dòng một món trong hộp, icon tùy chọn.',
					'compare'  => 'Gõ + để hiện dấu tích, - để hiện dấu gạch chéo.',
				);
				?>
				<div class="tmt-in-list full" data-in-list>
					<span><?php echo 'compare' === $type ? 'Các dòng so sánh' : 'Các dòng'; ?> <em><?php echo esc_html( $hint[ $type ] ); ?></em></span>
					<div class="tmt-in-rows">
						<?php foreach ( array_values( $list ) as $ri => $item ) : ?>
							<?php tmt_intro_item_form( $type, $bi, 'r' . $ri, (array) $item ); ?>
						<?php endforeach; ?>
					</div>
					<template><?php tmt_intro_item_form( $type, $bi, '__r__', array() ); ?></template>
					<button type="button" class="button" data-in-add-row>+ Thêm dòng</button>
				</div>
				<?php
			}
			?>
		</div>
	</div>
	<?php
}

function tmt_intro_meta_box( WP_Post $post ) {
	$blocks = tmt_intro_get( $post->ID );
	?>
	<div id="tmt-intro-ed">
		<p class="description">Các khối giới thiệu kiểu trang của hãng, hiện dưới khung mua. Kiểu hiển thị chọn ở Giao diện → Tùy biến → Trang sản phẩm. Để trống thì trang không có phần này.</p>
		<div class="tmt-in-blocks">
			<?php foreach ( $blocks as $i => $b ) : ?>
				<?php
				if ( isset( $b['type'], tmt_intro_fields()[ $b['type'] ] ) ) {
					tmt_intro_block_form( $b['type'], 'b' . $i, $b );
				}
				?>
			<?php endforeach; ?>
		</div>
		<input type="hidden" name="tmt_intro_sent" value="1">
		<div class="tmt-in-add">
			<span>Thêm khối:</span>
			<?php foreach ( tmt_intro_types() as $type => $t ) : ?>
				<button type="button" class="button" data-in-add="<?php echo esc_attr( $type ); ?>" title="<?php echo esc_attr( $t[2] ); ?>"><?php echo esc_html( $t[0] ); ?></button>
				<template data-in-tpl="<?php echo esc_attr( $type ); ?>"><?php tmt_intro_block_form( $type, '__b__', array() ); ?></template>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}

add_action(
	'woocommerce_process_product_meta',
	function ( $post_id ) {
		// phpcs:disable WordPress.Security.NonceVerification -- WooCommerce đã kiểm nonce khi lưu sản phẩm.
		if ( empty( $_POST['tmt_intro_sent'] ) ) {
			return;
		}
		$raw = isset( $_POST['tmt_intro'] ) && is_array( $_POST['tmt_intro'] ) ? wp_unslash( $_POST['tmt_intro'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		// phpcs:enable
		$blocks = tmt_intro_clean( $raw );
		if ( $blocks ) {
			update_post_meta( $post_id, '_tmt_intro', $blocks );
		} else {
			delete_post_meta( $post_id, '_tmt_intro' );
		}
	}
);
