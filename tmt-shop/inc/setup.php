<?php
/**
 * Cài đặt theme: hỗ trợ WordPress/WooCommerce, vị trí menu, nạp CSS/JS, biến màu.
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', 'tmt_setup' );
function tmt_setup() {
	load_theme_textdomain( 'tmt-shop', TMT_SHOP_DIR . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'custom-logo', array( 'height' => 88, 'width' => 320, 'flex-height' => true, 'flex-width' => true ) );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );

	// WooCommerce: theme tự làm khung ảnh (Swiper), nên không bật zoom/lightbox/slider của WooCommerce.
	add_theme_support(
		'woocommerce',
		array(
			'thumbnail_image_width' => 480,
			'single_image_width'    => 900,
			'product_grid'          => array( 'default_columns' => 4, 'default_rows' => 6 ),
		)
	);

	register_nav_menus(
		array(
			'primary'        => 'Menu chính (dưới logo)',
			'footer_shop'    => 'Chân trang: cột Mua hàng',
			'footer_support' => 'Chân trang: cột Hỗ trợ',
		)
	);

	add_image_size( 'tmt-card', 480, 360, false );
	add_image_size( 'tmt-slide', 1600, 600, true );
}

/** Bề rộng nội dung cho ảnh/nhúng trong bài. */
add_action(
	'after_setup_theme',
	function () {
		$GLOBALS['content_width'] = 860;
	},
	0
);

add_action( 'widgets_init', 'tmt_widgets_init' );
function tmt_widgets_init() {
	register_sidebar(
		array(
			'name'          => 'Cột bên bài viết',
			'id'            => 'blog',
			'before_widget' => '<section id="%1$s" class="sf-widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="sf-widget-title">',
			'after_title'   => '</h3>',
		)
	);
}

add_action( 'wp_enqueue_scripts', 'tmt_enqueue' );
function tmt_enqueue() {
	$v    = TMT_SHOP_VERSION;
	$font = in_array( tmt_opt( 'font' ), tmt_fonts(), true ) ? tmt_opt( 'font' ) : 'Roboto';

	$families = array( 'Roboto:wght@300;400;500;700;900' );
	if ( 'Roboto' !== $font ) {
		$families[] = str_replace( ' ', '+', $font ) . ':wght@400;500;600;700;800';
	}
	wp_enqueue_style( 'tmt-fonts', 'https://fonts.googleapis.com/css2?family=' . implode( '&family=', $families ) . '&display=swap&subset=vietnamese', array(), null );
	wp_enqueue_style( 'tmt-icons', 'https://fonts.googleapis.com/icon?family=Material+Icons+Round', array(), null );
	wp_enqueue_style( 'tmt-bootstrap', TMT_SHOP_URI . '/assets/vendor/bootstrap/bootstrap.min.css', array(), '5.3.3' );
	wp_enqueue_style( 'tmt-fontawesome', TMT_SHOP_URI . '/assets/vendor/fontawesome/css/all.min.css', array(), '7.3.1' );
	wp_enqueue_style( 'tmt-theme', TMT_SHOP_URI . '/assets/css/theme.css', array( 'tmt-bootstrap' ), $v );
	wp_add_inline_style( 'tmt-theme', tmt_inline_css( $font ) );

	wp_enqueue_script( 'tmt-theme', TMT_SHOP_URI . '/assets/js/theme.js', array(), $v, array( 'in_footer' => true, 'strategy' => 'defer' ) );

	if ( function_exists( 'is_product' ) && is_product() ) {
		wp_enqueue_style( 'tmt-swiper', TMT_SHOP_URI . '/assets/vendor/swiper/swiper-bundle.min.css', array(), '11.2.10' );
		wp_enqueue_script( 'tmt-swiper', TMT_SHOP_URI . '/assets/vendor/swiper/swiper-bundle.min.js', array(), '11.2.10', true );
		wp_enqueue_script( 'tmt-product', TMT_SHOP_URI . '/assets/js/product.js', array( 'tmt-swiper', 'jquery' ), $v, true );
	}
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}

/** Biến màu, font, bề rộng trang (đổi ở Tùy biến). */
function tmt_inline_css( $font ) {
	$w   = tmt_page_width();
	$css = sprintf(
		':root{--sf-blue:%1$s;--sf-yellow:%2$s;--sf-navy:%3$s;--sf-font:"%4$s";--sf-max:%5$dpx;--sf-gutter:%6$dpx;--sf-card-min:%7$dpx}',
		tmt_color( 'colors.primary' ),
		tmt_color( 'colors.accent' ),
		tmt_color( 'colors.dark' ),
		esc_attr( $font ),
		$w['max'],
		$w['gutter'],
		max( 160, min( 360, (int) tmt_opt( 'listing.card_width' ) ) )
	);
	$css .= sprintf( '@media (max-width:575px){:root{--sf-gutter:%dpx}}', min( $w['gutter'], 20 ) );
	return $css;
}

add_filter( 'body_class', 'tmt_body_class' );
function tmt_body_class( $classes ) {
	$w         = tmt_page_width();
	$classes[] = 'sf';
	$classes[] = tmt_effects()['bodyClass'];
	if ( $w['full'] ) {
		$classes[] = 'sf-full';
	}
	if ( $w['chrome'] ) {
		$classes[] = 'sf-full-chrome';
	}
	return $classes;
}

/** Ảnh logo: logo tải lên ở Tùy biến → Nhận diện, chưa có thì logo mẫu của theme. */
function tmt_logo_url() {
	$id = get_theme_mod( 'custom_logo' );
	if ( $id ) {
		$src = wp_get_attachment_image_url( $id, 'full' );
		if ( $src ) {
			return $src;
		}
	}
	return TMT_SHOP_URI . '/assets/img/logo.svg';
}

/** Không chèn thẻ <p> ảo vào trích dẫn tự sinh. */
add_filter(
	'excerpt_length',
	function () {
		return 28;
	}
);
add_filter(
	'excerpt_more',
	function () {
		return '…';
	}
);

/** Vùng widget ô nhận tin ở chân trang (dán form của plugin nhận email, ví dụ MailPoet). */
add_action(
	'widgets_init',
	function () {
		register_sidebar(
			array(
				'name'          => 'Chân trang: ô nhận tin',
				'id'            => 'footer-newsletter',
				'description'   => 'Bật "Ô nhận tin" ở Tùy biến → Chân trang, rồi thả widget form nhận email vào đây.',
				'before_widget' => '<div id="%1$s" class="sf-nl %2$s">',
				'after_widget'  => '</div>',
				'before_title'  => '<span class="visually-hidden">',
				'after_title'   => '</span>',
			)
		);
	}
);
