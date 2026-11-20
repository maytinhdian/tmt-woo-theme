<?php
/**
 * Mã VietQR (chuẩn EMVCo của NAPAS) cho đơn chuyển khoản: có sẵn số tài khoản, số tiền, nội dung = mã đơn.
 * Tạo tại chỗ trên máy chủ (thư viện inc/vendor/qrcode.php), không gọi dịch vụ ngoài.
 * Tài khoản nhận lấy từ WooCommerce → Cài đặt → Thanh toán → Chuyển khoản ngân hàng (tài khoản đầu tiên);
 * ngân hàng nhận ra theo tên, hoặc chọn ở Tùy biến → Giỏ hàng & thanh toán.
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;

require_once TMT_SHOP_DIR . '/inc/vendor/qrcode.php';

/** Đoán BIN theo tên ngân hàng gõ tay ("Vietcombank - CN Dĩ An"), rỗng nếu không nhận ra. */
function tmt_vietqr_guess_bin( $text ) {
	$name = ' ' . strtolower( remove_accents( (string) $text ) ) . ' ';
	foreach ( tmt_vietqr_banks() as $bin => $bank ) {
		foreach ( $bank[1] as $alias ) {
			if ( preg_match( '/(?<![a-z])' . preg_quote( $alias, '/' ) . '(?![a-z])/', $name ) ) {
				return (string) $bin;
			}
		}
	}
	return '';
}

/**
 * Tài khoản nhận tiền dùng cho mã QR, null khi chưa nhập tài khoản hoặc không nhận ra ngân hàng.
 *
 * @return array{bin: string, bank: string, number: string, name: string}|null
 */
function tmt_vietqr_account() {
	$accounts = (array) get_option( 'woocommerce_bacs_accounts', array() );
	foreach ( $accounts as $a ) {
		$number = preg_replace( '/\s+/', '', (string) ( isset( $a['account_number'] ) ? $a['account_number'] : '' ) );
		if ( '' === $number ) {
			continue;
		}
		$banks = tmt_vietqr_banks();
		$bin   = (string) tmt_opt( 'payment.bank_bin' );
		$bin   = isset( $banks[ $bin ] ) ? $bin : tmt_vietqr_guess_bin( isset( $a['bank_name'] ) ? $a['bank_name'] : '' );
		if ( '' === $bin ) {
			return null;
		}
		return array(
			'bin'    => $bin,
			'bank'   => $banks[ $bin ][0],
			'number' => $number,
			'name'   => (string) ( isset( $a['account_name'] ) ? $a['account_name'] : '' ),
		);
	}
	return null;
}

/** Nội dung chuyển khoản: chữ không dấu, số, khoảng trắng; tối đa 25 ký tự để mọi ngân hàng nhận. */
function tmt_vietqr_clean( $content ) {
	$content = strtoupper( remove_accents( (string) $content ) );
	$content = trim( preg_replace( '/\s+/', ' ', preg_replace( '/[^A-Z0-9 ]/', '', $content ) ) );
	return substr( $content, 0, 25 );
}

function tmt_vietqr_tag( $id, $value ) {
	return $id . str_pad( (string) strlen( $value ), 2, '0', STR_PAD_LEFT ) . $value;
}

/** CRC-16/CCITT-FALSE (đa thức 0x1021, khởi đầu 0xFFFF), in hoa 4 ký tự hex. */
function tmt_vietqr_crc16( $data ) {
	$crc = 0xFFFF;
	for ( $i = 0, $n = strlen( $data ); $i < $n; $i++ ) {
		$crc ^= ord( $data[ $i ] ) << 8;
		for ( $b = 0; $b < 8; $b++ ) {
			$crc = ( $crc & 0x8000 ) ? ( ( $crc << 1 ) ^ 0x1021 ) : ( $crc << 1 );
			$crc &= 0xFFFF;
		}
	}
	return strtoupper( str_pad( dechex( $crc ), 4, '0', STR_PAD_LEFT ) );
}

/** Chuỗi EMVCo theo chuẩn VietQR (chuyển nhanh 24/7 tới tài khoản). */
function tmt_vietqr_payload( $bin, $number, $amount = 0, $content = '' ) {
	$beneficiary = tmt_vietqr_tag( '00', $bin ) . tmt_vietqr_tag( '01', preg_replace( '/\s+/', '', $number ) );
	$merchant    = tmt_vietqr_tag( '00', 'A000000727' ) . tmt_vietqr_tag( '01', $beneficiary ) . tmt_vietqr_tag( '02', 'QRIBFTTA' );
	$amount      = (int) round( $amount );
	$data        = tmt_vietqr_tag( '00', '01' ) . tmt_vietqr_tag( '01', $amount > 0 ? '12' : '11' ) . tmt_vietqr_tag( '38', $merchant )
		. tmt_vietqr_tag( '53', '704' ) . ( $amount > 0 ? tmt_vietqr_tag( '54', (string) $amount ) : '' ) . tmt_vietqr_tag( '58', 'VN' );
	$content     = tmt_vietqr_clean( $content );
	if ( '' !== $content ) {
		$data .= tmt_vietqr_tag( '62', tmt_vietqr_tag( '08', $content ) );
	}
	$data .= '6304';
	return $data . tmt_vietqr_crc16( $data );
}

/** Ảnh QR dạng SVG (co giãn theo khung chứa). */
function tmt_qr_svg( $text ) {
	$qr = TMT_QRCode::getMinimumQRCode( $text, TMT_QR_ERROR_CORRECT_LEVEL_M );
	$n  = $qr->getModuleCount();
	$q  = 2; // Lề trắng (ô).
	$d  = '';
	for ( $r = 0; $r < $n; $r++ ) {
		for ( $c = 0; $c < $n; $c++ ) {
			if ( $qr->isDark( $r, $c ) ) {
				$d .= 'M' . ( $c + $q ) . ' ' . ( $r + $q ) . 'h1v1h-1z';
			}
		}
	}
	$size = $n + 2 * $q;
	return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $size . ' ' . $size . '" width="100%" height="100%" shape-rendering="crispEdges" role="img" aria-label="Mã QR chuyển khoản"><rect width="100%" height="100%" fill="#fff"/><path d="' . $d . '" fill="#1e3a78"/></svg>';
}

/** Nội dung chuyển khoản của một đơn ("DH 1234" theo tiền tố ở Tùy biến). */
function tmt_order_transfer_content( WC_Order $order ) {
	return tmt_vietqr_clean( trim( (string) tmt_opt( 'payment.qr_prefix' ) ) . ' ' . $order->get_order_number() );
}

/** Đơn đang chờ chuyển khoản (Chuyển khoản ngân hàng, chưa thanh toán, còn tiền phải trả). */
function tmt_order_waits_transfer( WC_Order $order ) {
	return 'bacs' === $order->get_payment_method() && $order->has_status( array( 'on-hold', 'pending' ) ) && (float) $order->get_total() > 0;
}

/** Đơn này có hiện được mã QR không (có tài khoản nhận, đang chờ chuyển khoản, tiền VND). */
function tmt_order_has_qr( WC_Order $order ) {
	return tmt_vietqr_account() && tmt_order_waits_transfer( $order ) && 'VND' === $order->get_currency();
}

/** Khối mã QR + thông tin chuyển khoản của đơn (trang cảm ơn, xem đơn, tra đơn). */
function tmt_order_qr_box( WC_Order $order ) {
	if ( ! tmt_order_has_qr( $order ) ) {
		return;
	}
	$acc = tmt_vietqr_account();
	$amount  = (int) round( (float) $order->get_total() );
	$content = tmt_order_transfer_content( $order );
	$rows    = array(
		array( 'Ngân hàng', $acc['bank'], '' ),
		array( 'Số tài khoản', $acc['number'], $acc['number'] ),
		array( 'Chủ tài khoản', $acc['name'], '' ),
		array( 'Số tiền', wp_strip_all_tags( wc_price( $amount ) ), (string) $amount ),
		array( 'Nội dung', $content, $content ),
	);
	?>
	<section class="sfq" aria-label="Chuyển khoản bằng mã QR">
		<div class="sfq-code"><?php echo tmt_qr_svg( tmt_vietqr_payload( $acc['bin'], $acc['number'], $amount, $content ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
		<div class="sfq-info">
			<h2><i class="fa-solid fa-qrcode" aria-hidden="true"></i>Quét mã để chuyển khoản</h2>
			<p>Mở app ngân hàng, chọn quét QR: số tiền và nội dung đã điền sẵn. Tiệm xác nhận khi nhận được tiền.</p>
			<dl>
				<?php foreach ( $rows as $r ) : ?>
					<?php if ( '' !== $r[1] ) : ?>
						<div><dt><?php echo esc_html( $r[0] ); ?></dt><dd><b><?php echo esc_html( $r[1] ); ?></b><?php if ( $r[2] ) : ?><button type="button" class="sfq-copy" data-copy="<?php echo esc_attr( $r[2] ); ?>">Chép</button><?php endif; ?></dd></div>
					<?php endif; ?>
				<?php endforeach; ?>
			</dl>
		</div>
	</section>
	<script>
		document.querySelectorAll('.sfq-copy').forEach((b) => b.addEventListener('click', () => {
			navigator.clipboard && navigator.clipboard.writeText(b.dataset.copy).then(() => { b.textContent = 'Đã chép'; setTimeout(() => { b.textContent = 'Chép'; }, 1500); });
		}));
	</script>
	<?php
}

/** Trang cảm ơn: mã QR thay cho bảng tài khoản của WooCommerce (khối QR đã có đủ thông tin). */
add_action(
	'woocommerce_thankyou_bacs',
	function ( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order || ! tmt_order_has_qr( $order ) ) {
			return;
		}
		tmt_order_qr_box( $order );
		$gateways = WC()->payment_gateways()->payment_gateways();
		if ( isset( $gateways['bacs'] ) ) {
			remove_action( 'woocommerce_thankyou_bacs', array( $gateways['bacs'], 'thankyou_page' ) );
		}
	},
	5
);
/** Tài khoản → Xem đơn hàng. */
add_action(
	'woocommerce_view_order',
	function ( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $order ) {
			tmt_order_qr_box( $order );
		}
	},
	5
);

/** Email đơn chờ chuyển khoản: nhiều hộp thư chặn ảnh SVG nên gửi link mở trang đơn có mã QR. */
add_action(
	'woocommerce_email_before_order_table',
	function ( $order, $sent_to_admin, $plain_text ) {
		if ( $sent_to_admin || ! $order instanceof WC_Order || ! tmt_order_waits_transfer( $order ) || ! tmt_vietqr_account() ) {
			return;
		}
		$url  = $order->get_checkout_order_received_url();
		$text = 'Chuyển khoản nhanh: mở trang đơn hàng để quét mã QR (số tiền và nội dung "' . tmt_order_transfer_content( $order ) . '" đã điền sẵn).';
		if ( $plain_text ) {
			echo esc_html( $text ) . "\n" . esc_url_raw( $url ) . "\n\n";
			return;
		}
		echo '<p>' . esc_html( $text ) . ' <a href="' . esc_url( $url ) . '">Mở mã QR</a></p>';
	},
	5,
	3
);

/** Nội dung chuyển khoản mà WooCommerce in ở phần thông tin tài khoản: dùng cùng mã với QR. */
add_filter(
	'woocommerce_bacs_account_fields',
	function ( $fields, $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $order ) {
			$fields['tmt_content'] = array( 'label' => 'Nội dung chuyển khoản', 'value' => tmt_order_transfer_content( $order ) );
		}
		return $fields;
	},
	10,
	2
);
