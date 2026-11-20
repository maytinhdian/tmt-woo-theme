<?php
/**
 * Bản in cấu hình đưa khách, khổ A5 (giống mẫu in của app). Mã QR mở lại đúng cấu hình trên web.
 * Biến: $build (tmt_pcb_build).
 *
 * @package TMT_Shop_Tools
 */

defined( 'ABSPATH' ) || exit;

$tmt_money = function ( $vnd ) {
	return number_format( (int) $vnd, 0, ',', '.' );
};
$tmt_code  = tmt_pcb_code( $build );
$tmt_link  = tmt_pcb_url() . ( $tmt_code ? '#' . $tmt_code : '' );
$tmt_total = 0;
foreach ( $build as $tmt_i ) {
	$tmt_total += (int) $tmt_i['part']['price'] * $tmt_i['qty'];
}
$tmt_slots   = tmt_pcb_slots();
$tmt_brand   = function_exists( 'tmt_opt' ) ? (string) tmt_opt( 'shop.brand' ) : get_bloginfo( 'name' );
$tmt_address = function_exists( 'tmt_opt' ) ? (string) tmt_opt( 'shop.address' ) : '';
$tmt_phone   = function_exists( 'tmt_opt' ) ? (string) tmt_opt( 'shop.phone' ) : '';
$tmt_logo    = function_exists( 'tmt_logo_url' ) ? tmt_logo_url() : '';
$tmt_note    = trim( (string) get_option( 'tmt_pcb_note', 'Miễn phí ráp máy, cài Windows và chạy thử 24 giờ trước khi giao.' ) );
?><!DOCTYPE html>
<html lang="vi">
<head>
	<meta charset="utf-8">
	<meta name="robots" content="noindex">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Cấu hình máy tính | <?php echo esc_html( $tmt_brand ); ?></title>
	<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&amp;display=swap&amp;subset=vietnamese"><?php // phpcs:ignore WordPress.WP.EnqueuedResources ?>
	<style>
		@page { size: A5 portrait; margin: 8mm; }
		:root { --blue: #3271E0; --navy: #1E3A78; --red: #C62828; }
		* { box-sizing: border-box; }
		body { margin: 0; font: 9.5pt/1.35 'Roboto', Arial, sans-serif; color: var(--navy); background: #e9ecf2; }
		.sheet { width: 148mm; min-height: 210mm; margin: 12px auto; padding: 8mm; background: #fff; }
		header { display: grid; grid-template-columns: 22mm 1fr; gap: 4mm; align-items: center; border-bottom: 1.5px solid var(--blue); padding-bottom: 3mm; }
		header.nologo { grid-template-columns: 1fr; }
		header img { width: 22mm; }
		header h1 { margin: 0; font-size: 10pt; text-transform: uppercase; color: var(--red); }
		header p { margin: .6mm 0 0; font-size: 8.5pt; }
		h2 { text-align: center; margin: 4mm 0 1mm; font: 900 14pt 'Roboto', sans-serif; color: var(--blue); }
		.date { text-align: center; font-size: 8.5pt; margin-bottom: 3mm; }
		table { width: 100%; border-collapse: collapse; }
		th, td { border: 1px solid #b8c4dc; padding: 1.2mm 1.5mm; vertical-align: top; }
		th { background: #eef3fd; font-size: 8pt; text-transform: uppercase; }
		td.n { text-align: right; white-space: nowrap; }
		td small { display: block; color: #55648a; font-size: 7.5pt; }
		tfoot td { font-weight: 700; }
		tfoot .grand td { font-size: 11pt; color: var(--red); }
		.foot { display: grid; grid-template-columns: 1fr 26mm; gap: 4mm; margin-top: 4mm; font-size: 8.5pt; align-items: end; }
		.foot ul { margin: 0; padding-left: 4mm; }
		.qr { text-align: center; font-size: 7pt; }
		.qr svg { width: 26mm; height: 26mm; }
		.empty { text-align: center; padding: 10mm 0; color: #55648a; }
		.toolbar { text-align: center; margin: 12px; }
		.toolbar button { font: 600 10pt 'Roboto', sans-serif; background: var(--blue); color: #fff; border: 0; border-radius: 6px; padding: 8px 18px; cursor: pointer; }
		@media print { body { background: #fff; } .sheet { margin: 0; padding: 0; width: auto; min-height: 0; } .toolbar { display: none; } }
		@media (max-width: 600px) { .sheet { width: auto; min-height: 0; margin: 0; } }
	</style>
</head>
<body>
	<div class="toolbar"><button type="button" onclick="window.print()">In hoặc lưu PDF</button></div>
	<div class="sheet">
		<header class="<?php echo $tmt_logo ? '' : 'nologo'; ?>">
			<?php if ( $tmt_logo ) : ?><img src="<?php echo esc_url( $tmt_logo ); ?>" alt=""><?php endif; ?>
			<div>
				<h1><?php echo esc_html( $tmt_brand ); ?></h1>
				<?php if ( $tmt_address ) : ?><p><?php echo esc_html( $tmt_address ); ?></p><?php endif; ?>
				<p><?php echo $tmt_phone ? 'ĐT/Zalo: ' . esc_html( $tmt_phone ) . ' · ' : ''; ?><?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?></p>
			</div>
		</header>
		<h2>CẤU HÌNH MÁY TÍNH</h2>
		<div class="date">Ngày <?php echo esc_html( wp_date( 'd/m/Y' ) ); ?></div>

		<?php if ( ! $build ) : ?>
			<p class="empty">Chưa chọn linh kiện nào.</p>
		<?php else : ?>
			<table>
				<thead><tr><th style="width: 7mm">#</th><th>Linh kiện</th><th style="width: 10mm">BH</th><th style="width: 8mm">SL</th><th style="width: 20mm">Đơn giá</th><th style="width: 22mm">Thành tiền</th></tr></thead>
				<tbody>
					<?php $tmt_n = 0; foreach ( $build as $tmt_slot => $tmt_i ) : $tmt_p = $tmt_i['part']; ++$tmt_n; ?>
						<tr>
							<td class="n"><?php echo (int) $tmt_n; ?></td>
							<td><b><?php echo esc_html( $tmt_slots[ $tmt_slot ]['label'] ); ?>:</b> <?php echo esc_html( $tmt_p['name'] ); ?><?php if ( $tmt_p['spec'] ) : ?><small><?php echo esc_html( $tmt_p['spec'] ); ?></small><?php endif; ?></td>
							<td class="n"><?php echo $tmt_p['bh'] ? (int) $tmt_p['bh'] . 'th' : ''; ?></td>
							<td class="n"><?php echo (int) $tmt_i['qty']; ?></td>
							<td class="n"><?php echo null === $tmt_p['price'] ? 'Liên hệ' : esc_html( $tmt_money( $tmt_p['price'] ) ); ?></td>
							<td class="n"><?php echo null === $tmt_p['price'] ? '' : esc_html( $tmt_money( $tmt_p['price'] * $tmt_i['qty'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
				<tfoot>
					<?php if ( '' !== $tmt_note ) : ?>
						<tr><td colspan="5">Ráp máy, cài Windows, chạy thử</td><td class="n">Miễn phí</td></tr>
					<?php endif; ?>
					<tr class="grand"><td colspan="5">TỔNG CỘNG</td><td class="n"><?php echo esc_html( $tmt_money( $tmt_total ) ); ?> ₫</td></tr>
				</tfoot>
			</table>
		<?php endif; ?>

		<div class="foot">
			<ul>
				<li>Giá tham khảo ngày in, có thể đổi theo giá nhập và tồn kho.</li>
				<li>Bảo hành tại tiệm theo thời hạn từng linh kiện (BH, tháng).</li>
				<li>Quét mã QR để xem lại và chỉnh cấu hình này trên web.</li>
			</ul>
			<?php if ( function_exists( 'tmt_qr_svg' ) ) : ?>
				<div class="qr"><?php echo tmt_qr_svg( $tmt_link ); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG do thư viện QR của theme dựng. ?><div><?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?></div></div>
			<?php endif; ?>
		</div>
	</div>
</body>
</html>
