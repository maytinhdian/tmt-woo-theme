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
