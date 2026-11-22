<?php
/**
 * Hàm in giao diện dùng chung.
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;

/** Icon Material Round. */
function tmt_icon( $name, $class = '' ) {
	return '<i class="material-icons-round' . ( $class ? ' ' . esc_attr( $class ) : '' ) . '" aria-hidden="true">' . esc_html( $name ) . '</i>';
}

/**
 * Icon Material cho một nhóm hàng: ô "Icon" ở trang sửa danh mục, trống thì đoán theo slug (cpu, ram, ssd, laptop...).
 *
 * @param WP_Term|null $term Danh mục sản phẩm.
 */
function tmt_category_icon( $term ) {
	if ( $term instanceof WP_Term ) {
		$own = trim( (string) get_term_meta( $term->term_id, 'tmt_icon', true ) );
		if ( preg_match( '/^[a-z0-9_]+$/', $own ) ) {
			return $own;
		}
	}
	$map = array(
		'tan-nhiet' => 'ac_unit', 'vo-case' => 'dns', 'man-hinh' => 'monitor', 'ban-phim' => 'keyboard', 'tai-nghe' => 'headphones',
		'may-bo' => 'desktop_windows', 'phan-mem' => 'apps', 'cham-cong' => 'fingerprint', 'mainboard' => 'developer_board',
		'camera' => 'videocam', 'cpu' => 'memory', 'ram' => 'memory', 'ssd' => 'storage', 'hdd' => 'storage', 'card' => 'videogame_asset',
		'nguon' => 'power', 'laptop' => 'laptop', 'chuot' => 'mouse', 'cap' => 'cable', 'mang' => 'router', 'may-in' => 'print',
		'linh-kien' => 'memory', 'phu-kien' => 'mouse',
	);
	$parts = $term instanceof WP_Term ? explode( '-', $term->slug ) : array();
	foreach ( $map as $needle => $icon ) {
		$n = count( explode( '-', $needle ) );
		for ( $i = 0; $i + $n <= count( $parts ); $i++ ) {
			if ( implode( '-', array_slice( $parts, $i, $n ) ) === $needle ) {
				return $icon;
			}
		}
	}
	return 'inventory_2';
}

/** Tiền dạng 1.290.000đ (không HTML), dùng khi cần chữ thuần. */
function tmt_money_text( $amount ) {
	return number_format( (float) $amount, 0, ',', '.' ) . 'đ';
}

/** Mở khung nội dung chuẩn của theme. */
function tmt_wrap_open( $class = '' ) {
	echo '<div class="sf-wrap' . ( $class ? ' ' . esc_attr( $class ) : '' ) . '">';
}

function tmt_wrap_close() {
	echo '</div>';
}

/** Đường dẫn 🏠 / ... / Trang (dùng breadcrumb của WooCommerce khi có). */
function tmt_breadcrumb() {
	if ( is_front_page() ) {
		return;
	}
	if ( function_exists( 'woocommerce_breadcrumb' ) ) {
		woocommerce_breadcrumb(
			array(
				'delimiter'   => '<span class="sep" aria-hidden="true">/</span>',
				'wrap_before' => '<nav class="sf-crumb" aria-label="Đường dẫn">',
				'wrap_after'  => '</nav>',
				'home'        => 'Trang chủ',
			)
		);
		return;
	}
	echo '<nav class="sf-crumb" aria-label="Đường dẫn"><a href="' . esc_url( home_url( '/' ) ) . '">Trang chủ</a><span class="sep">/</span><span>' . esc_html( wp_get_document_title() ) . '</span></nav>';
}

/** Tiêu đề khối có link "Xem tất cả". */
function tmt_section_head( $title, $more_url = '', $more = 'Xem tất cả' ) {
	echo '<div class="sf-sec-head"><h2 class="sf-h2">' . esc_html( $title ) . '</h2>';
	if ( $more_url ) {
		echo '<a class="sf-more" href="' . esc_url( $more_url ) . '">' . esc_html( $more ) . ' ' . tmt_icon( 'chevron_right' ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</div>';
}

/** Thẻ bài viết (trang chủ, Mẹo vặt). Mang lớp sf-fx-post / sf-fx-media để ăn theo mục Hiệu ứng. */
function tmt_post_card( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	$cats = get_the_category( $post->ID );
	?>
	<article class="sf-post sf-fx-post">
		<a href="<?php echo esc_url( get_permalink( $post ) ); ?>" class="sf-post-img sf-fx-media" tabindex="-1" aria-hidden="true">
			<?php if ( has_post_thumbnail( $post ) ) : ?>
				<?php echo get_the_post_thumbnail( $post, 'tmt-card', array( 'loading' => 'lazy', 'alt' => '' ) ); ?>
			<?php else : ?>
				<?php echo tmt_icon( 'tips_and_updates' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php endif; ?>
		</a>
		<div class="sf-post-b">
			<?php if ( $cats ) : ?>
				<a class="sf-post-cat" href="<?php echo esc_url( get_category_link( $cats[0] ) ); ?>"><?php echo esc_html( $cats[0]->name ); ?></a>
			<?php endif; ?>
			<h3 class="sf-post-t"><a href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a></h3>
			<p class="sf-post-x"><?php echo esc_html( wp_trim_words( get_the_excerpt( $post ), 22 ) ); ?></p>
			<span class="sf-post-d"><?php echo esc_html( get_the_date( 'd/m/Y', $post ) ); ?></span>
		</div>
	</article>
	<?php
}

/**
 * Hiệu ứng chữ cạnh logo (Tùy biến → Hiệu ứng → Chữ cạnh logo; port Theme::brandFx của app).
 *
 * @return array{fx: string, name: bool, by: bool, run: string, k: string, color: string, lines: list<string>, letters: bool, js: bool}
 */
function tmt_brand_fx() {
	$fx     = array_key_exists( (string) tmt_opt( 'effects.brand_fx' ), tmt_brand_fx_choices() ) ? tmt_opt( 'effects.brand_fx' ) : 'none';
	$target = in_array( tmt_opt( 'effects.brand_target' ), array( 'by', 'name', 'both' ), true ) ? tmt_opt( 'effects.brand_target' ) : 'by';
	$k      = array( 'fast' => '.65', 'normal' => '1', 'slow' => '1.5' );
	$on     = 'none' !== $fx;
	$lines  = array();
	foreach ( preg_split( '/\R/u', (string) tmt_opt( 'effects.brand_lines' ) ) as $l ) {
		$l = trim( $l );
		if ( '' !== $l && count( $lines ) < 5 ) {
			$lines[] = mb_substr( $l, 0, 60 );
		}
	}
	return array(
		'fx'      => $fx,
		'name'    => $on && 'rotate' !== $fx && 'by' !== $target,
		'by'      => $on && ( 'rotate' === $fx || 'name' !== $target ),
		'run'     => in_array( tmt_opt( 'effects.brand_run' ), array( 'loop', 'once', 'hover' ), true ) ? tmt_opt( 'effects.brand_run' ) : 'loop',
		'k'       => isset( $k[ tmt_opt( 'effects.brand_speed' ) ] ) ? $k[ tmt_opt( 'effects.brand_speed' ) ] : '1',
		'color'   => 'dark' === tmt_opt( 'effects.brand_color' ) ? 'dark' : 'primary',
		'lines'   => $lines,
		'letters' => in_array( $fx, array( 'reveal', 'flip' ), true ),
		'js'      => in_array( $fx, array( 'type', 'rotate' ), true ),
	);
}

/** Kiểu chữ cạnh logo. */
function tmt_brand_fx_choices() {
	return array(
		'none'    => 'Đứng yên (như cũ)',
		'shimmer' => 'Ánh kim lướt',
		'type'    => 'Gõ chữ',
		'glow'    => 'Phát sáng nhẹ',
		'reveal'  => 'Hiện từng chữ',
		'rotate'  => 'Đổi câu (chữ hiện dần)',
		'flip'    => 'Lật chữ',
	);
}

/** Chữ cạnh logo đã dựng cho hiệu ứng (port partials/brand-text của app). $on: dòng này có hiệu ứng không. */
function tmt_brand_text( $text, $on, $b ) {
	$letters = function ( $t ) {
		$out = '';
		foreach ( preg_split( '/(\X)/u', $t, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY ) as $i => $ch ) {
			$out .= ' ' === $ch ? ' ' : '<span class="ch" style="--i:' . (int) $i . '">' . esc_html( $ch ) . '</span>';
		}
		return $out;
	};
	$hidden = '<span class="visually-hidden">' . esc_html( $text ) . '</span>';
	if ( ! $on ) {
		return esc_html( $text );
	}
	if ( 'type' === $b['fx'] ) {
		return '<span class="sf-bfx sf-bfx-type" data-sf-bfx="type">' . $hidden . '<span class="sf-bfx-stack" aria-hidden="true"><span class="sz">' . esc_html( $text ) . '</span><span class="tx" data-text="' . esc_attr( $text ) . '">' . esc_html( $text ) . '</span></span></span>';
	}
	if ( 'rotate' === $b['fx'] ) {
		$html = '';
		foreach ( array_merge( array( $text ), $b['lines'] ) as $n => $line ) {
			$html .= '<span class="ln' . ( 0 === $n ? ' on' : ' alt' ) . '">' . $letters( $line ) . '</span>';
		}
		return '<span class="sf-bfx sf-bfx-rotate" data-sf-bfx="rotate">' . $hidden . '<span class="sf-bfx-stack" aria-hidden="true">' . $html . '</span></span>';
	}
	if ( $b['letters'] ) {
		return '<span class="sf-bfx sf-bfx-' . esc_attr( $b['fx'] ) . '">' . $hidden . '<span aria-hidden="true">' . $letters( $text ) . '</span></span>';
	}
	return '<span class="sf-bfx sf-bfx-' . esc_attr( $b['fx'] ) . '">' . esc_html( $text ) . '</span>';
}
