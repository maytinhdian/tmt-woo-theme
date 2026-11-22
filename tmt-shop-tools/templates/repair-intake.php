<?php
/**
 * Trang /sua-may: quy trình tiếp nhận sửa máy (port từ app, Anh 2 chọn A + khối tính thử C, 22/11/2026). Số tiền chỉ lấy từ tmt_repair_fees().
 *
 * @package TMT_Shop_Tools
 */

defined( 'ABSPATH' ) || exit;

$tmt_f      = tmt_repair_fees();
$tmt_c      = tmt_lp_contact();
$tmt_sent   = tmt_req_sent();
$tmt_error  = tmt_req_error();
$tmt_waive  = tmt_repair_waive_text( $tmt_f );
$tmt_limit  = $tmt_f['limit'];
// Ví dụ trên phiếu mẫu và khối tính thử: một món dưới hạn mức, một món trên hạn mức.
$tmt_small  = (int) max( 50000, round( $tmt_limit * 0.7 / 50000 ) * 50000 );
$tmt_big    = (int) max( $tmt_limit + 50000, round( $tmt_limit * 2.5 / 50000 ) * 50000 );
$tmt_v      = 'tmt_repair_vnd';
$tmt_kinds  = tmt_repair_kinds();
get_header();
?>
<div class="lp rin">
	<section class="lp-hero">
		<div class="sf-wrap">
			<div>
				<nav class="lp-crumb" aria-label="Đường dẫn"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Trang chủ</a> / Sửa máy</nav>
				<h1>Sửa máy rõ ràng:<em>kiểm tra trước, đồng ý mới sửa</em></h1>
				<p class="lead">Laptop, PC, máy in, camera... Anh/chị nhắn tình trạng máy, tiệm kiểm tra và báo đúng bệnh, đúng giá. Không hư thì chỉ trả phí kiểm tra.</p>
				<ul class="lp-ticks">
					<?php foreach ( array( 'Báo phí kiểm tra trước khi nhận máy', 'Có hư mới báo giá thay đồ', 'Anh/chị chọn hạn mức "dưới bao nhiêu cứ thay"', $tmt_waive ) as $t ) : ?>
						<li><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?php echo esc_html( $t ); ?></li>
					<?php endforeach; ?>
				</ul>
				<div class="lp-cta">
					<?php if ( $tmt_c['zalo'] ) : ?><a class="lp-btn y" href="<?php echo esc_url( $tmt_c['zalo'] ); ?>" target="_blank" rel="noopener"><i class="fa-regular fa-comment-dots" aria-hidden="true"></i>Nhắn Zalo sửa máy</a><?php endif; ?>
					<a class="lp-btn <?php echo $tmt_c['zalo'] ? 'w' : 'y'; ?>" href="#khao-sat"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i>Hẹn thợ tới tận nơi</a>
				</div>
			</div>
			<div>
				<div class="rin-ticket" aria-label="Ví dụ một phiếu sửa">
					<div class="hd"><div><b>Phiếu sửa máy</b><small>Laptop · không lên nguồn</small></div><span class="tag">Ví dụ</span></div>
					<div class="ln"><span>Phí kiểm tra (1 máy)</span><span><?php echo esc_html( $tmt_v( $tmt_f['inspect'] ) ); ?></span></div>
					<div class="ln"><span>Hạn mức tự thay</span><span>dưới <?php echo esc_html( $tmt_v( $tmt_limit ) ); ?></span></div>
					<div class="ln"><span>Kết quả kiểm tra</span><span>Hư IC nguồn</span></div>
					<div class="ln"><span>Thay IC nguồn + công</span><span><?php echo esc_html( $tmt_v( $tmt_small ) ); ?></span></div>
					<div class="ln off"><span>Phí kiểm tra (<?php echo $tmt_f['waive'] ? 'miễn vì sửa' : 'trừ vào tiền sửa'; ?>)</span><span><?php echo esc_html( $tmt_v( $tmt_f['inspect'] ) ); ?></span></div>
					<div class="ln tot"><span>Khách trả</span><span><?php echo esc_html( $tmt_v( $tmt_small ) ); ?></span></div>
					<div class="note"><i class="fa-solid fa-bolt" aria-hidden="true"></i>Dưới hạn mức <?php echo esc_html( $tmt_v( $tmt_limit ) ); ?> nên thợ thay luôn, không phải chờ gọi hỏi.</div>
				</div>
			</div>
		</div>
	</section>

	<div class="lp-trust">
		<div class="sf-wrap" style="--n: 4">
			<?php
			foreach ( array(
				array( 'file-invoice-dollar', 'Báo phí trước', 'Biết trước khi giao máy' ),
				array( 'camera', 'Chụp ảnh máy', 'Lúc nhận và lúc trả' ),
				array( 'shield-halved', 'Bảo hành linh kiện', 'Linh kiện thay đều có bảo hành' ),
				array( 'motorcycle', 'Sửa tận nơi', 'Thợ tới nhận hoặc sửa tại chỗ' ),
			) as $t ) :
				?>
				<div><i class="fa-solid fa-<?php echo esc_attr( $t[0] ); ?>" aria-hidden="true"></i><span><b><?php echo esc_html( $t[1] ); ?></b><small><?php echo esc_html( $t[2] ); ?></small></span></div>
			<?php endforeach; ?>
		</div>
	</div>

	<section class="lp-sec alt" id="quy-trinh">
		<div class="sf-wrap">
			<div class="lp-sh"><span class="lp-eye">Quy trình tiếp nhận</span><h2>5 bước từ lúc nhắn tới lúc nhận máy</h2></div>
			<ol class="rin-flow">
				<li><b>Nhắn tình trạng</b><p>Zalo, gọi điện hoặc form bên dưới: máy gì, bị sao, mang tới hay cần thợ tới.</p></li>
				<li><b>Nhận máy</b><p>Ghi phiếu, chụp ảnh máy. Anh/chị chọn hạn mức tự thay.</p><span class="fee">Báo phí kiểm tra + phí di chuyển (nếu tới tận nơi)</span></li>
				<li><b>Kiểm tra</b><p>Thợ tìm đúng bệnh, báo lại cho anh/chị qua Zalo hoặc điện thoại.</p></li>
				<li><b>Báo giá và sửa</b><p>Có hư mới báo giá. Dưới hạn mức thì thay luôn, trên hạn mức chờ anh/chị duyệt.</p></li>
				<li><b>Trả máy</b><p>Ký nhận, trả tiền mặt hoặc quét QR. Linh kiện thay có bảo hành.</p></li>
			</ol>
			<div class="rin-split">Sau khi kiểm tra, có 3 trường hợp</div>
			<div class="rin-cases">
				<div class="g"><h3><i class="fa-solid fa-circle-check" aria-hidden="true"></i>Máy không hư</h3><p>Không báo giá, không thay gì. Trả máy luôn.</p><b>Trả: phí kiểm tra (+ phí di chuyển)</b></div>
				<div class="b"><h3><i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i>Có hư, đồng ý sửa</h3><p>Dưới hạn mức thay luôn, trên hạn mức gửi báo giá chờ anh/chị đồng ý.</p><b>Trả: tiền sửa (+ phí di chuyển). <?php echo $tmt_f['waive'] ? 'Miễn phí kiểm tra' : 'Phí kiểm tra trừ vào tiền sửa'; ?></b></div>
				<div class="y"><h3><i class="fa-solid fa-circle-minus" aria-hidden="true"></i>Có hư, không sửa</h3><p>Anh/chị không đồng ý báo giá: tiệm trả máy như lúc nhận.</p><b>Trả: phí kiểm tra (+ phí di chuyển)</b></div>
			</div>
		</div>
	</section>

	<section class="lp-sec" id="bang-phi">
		<div class="sf-wrap">
			<div class="lp-sh"><span class="lp-eye">Bảng phí</span><h2>Các khoản báo trước khi nhận máy</h2></div>
			<div class="rin-fees">
				<div>
					<div class="ic"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></div>
					<h3>Phí kiểm tra</h3>
					<div class="price"><?php echo esc_html( $tmt_v( $tmt_f['inspect'] ) ); ?></div><small>mỗi máy</small>
					<ul><li>Mang 2 máy thì tính 2 lần phí</li><li><?php echo esc_html( $tmt_waive ); ?></li><li>Không hư cũng chỉ trả phí này</li></ul>
				</div>
				<div>
					<div class="ic"><i class="fa-solid fa-motorcycle" aria-hidden="true"></i></div>
					<h3>Phí di chuyển</h3>
					<div class="price">từ <?php echo esc_html( $tmt_v( $tmt_f['travel_min'] ) ); ?></div><small>mỗi chuyến, chỉ khi thợ tới tận nơi</small>
					<ul><li>Tính 1 lần cho cả chuyến, dù mấy máy</li><?php if ( $tmt_f['per_km'] ) : ?><li>Xa hơn: <?php echo esc_html( $tmt_v( $tmt_f['per_km'] ) ); ?> mỗi km đường đi</li><?php endif; ?><li>Gửi định vị qua Zalo, tiệm báo trước</li></ul>
				</div>
				<div>
					<div class="ic"><i class="fa-solid fa-bolt" aria-hidden="true"></i></div>
					<h3>Hạn mức tự thay</h3>
					<div class="price navy">Anh/chị chọn</div><small>ví dụ "dưới <?php echo esc_html( $tmt_v( $tmt_limit ) ); ?> cứ thay"</small>
					<ul><li>Dưới hạn mức: thay luôn, xong sớm</li><li>Trên hạn mức: chờ anh/chị duyệt</li><li>Không chọn: lần nào cũng hỏi</li></ul>
				</div>
			</div>

			<div class="rin-calc" data-rin-calc data-inspect="<?php echo (int) $tmt_f['inspect']; ?>" data-travel="<?php echo (int) $tmt_f['travel_min']; ?>" data-limit="<?php echo (int) $tmt_limit; ?>" data-small="<?php echo (int) $tmt_small; ?>" data-big="<?php echo (int) $tmt_big; ?>" data-waive="<?php echo $tmt_f['waive'] ? 1 : 0; ?>">
				<div class="opts">
					<h3>Tính thử: máy tôi trả bao nhiêu?</h3>
					<span class="f">Nhận máy</span>
					<div class="rin-seg" role="radiogroup" aria-label="Nhận máy">
						<label><input type="radio" name="rin-where" value="tiem" checked><span><i class="fa-solid fa-store" aria-hidden="true"></i>Mang tới tiệm</span></label>
						<label><input type="radio" name="rin-where" value="noi"><span><i class="fa-solid fa-motorcycle" aria-hidden="true"></i>Thợ tới nơi</span></label>
					</div>
					<span class="f">Số máy</span>
					<div class="rin-qty"><button type="button" data-step="-1" aria-label="Bớt một máy">−</button><output data-n>1</output><button type="button" data-step="1" aria-label="Thêm một máy">+</button><small>phí kiểm tra tính theo máy</small></div>
					<span class="f">Kết quả kiểm tra</span>
					<div class="rin-res" role="radiogroup" aria-label="Kết quả kiểm tra">
						<label><input type="radio" name="rin-res" value="ok"><span><b>Máy không hư</b><small>Chỉ kiểm tra, không thay gì</small></span></label>
						<label><input type="radio" name="rin-res" value="small" checked><span><b>Hư, dưới hạn mức</b><small>Ví dụ thay món <?php echo esc_html( $tmt_v( $tmt_small ) ); ?>, thay luôn</small></span></label>
						<label><input type="radio" name="rin-res" value="big"><span><b>Hư, trên hạn mức, đồng ý sửa</b><small>Ví dụ thay món <?php echo esc_html( $tmt_v( $tmt_big ) ); ?>, chờ duyệt</small></span></label>
						<label><input type="radio" name="rin-res" value="no"><span><b>Hư nhưng không sửa</b><small>Từ chối báo giá, nhận máy về</small></span></label>
					</div>
				</div>
				<div class="out">
					<h3 data-title></h3>
					<ol class="path" data-path></ol>
					<div class="bill" data-bill aria-live="polite"></div>
					<p class="hint">Số tiền sửa chỉ là ví dụ. Phí di chuyển là mức tối thiểu, xa hơn tính theo km.</p>
				</div>
			</div>

			<div class="rin-buy">
				<div class="ic"><i class="fa-solid fa-laptop" aria-hidden="true"></i></div>
				<div><h3>Cần thợ tới tư vấn mua máy?</h3><p>Tiệm tới xem chỗ đặt, nhu cầu dùng và báo cấu hình. Phí di chuyển tính như trên, chốt mua thì trừ vào tiền máy.</p></div>
				<a class="lp-btn y" href="#khao-sat" data-rin-kind="<?php echo esc_attr( $tmt_kinds[2] ); ?>">Hẹn tư vấn</a>
			</div>
		</div>
	</section>

	<section class="lp-sec alt">
		<div class="sf-wrap">
			<div class="lp-sh"><h2>Câu hỏi thường gặp</h2></div>
			<div class="lp-faq">
				<?php foreach ( tmt_repair_faq( $tmt_f ) as $i => $q ) : ?>
					<details<?php echo $i ? '' : ' open'; ?>><summary><?php echo esc_html( $q[0] ); ?></summary><p><?php echo esc_html( $q[1] ); ?></p></details>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="lp-sec lp-final">
		<div class="sf-wrap">
			<div>
				<h2>Máy đang bị gì? Nhắn tiệm xem giúp</h2>
				<p>Tả sơ tình trạng, tiệm gọi lại báo phí và hẹn giờ.</p>
				<div class="contact">
					<?php if ( $tmt_c['phone'] ) : ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $tmt_c['phone'] ) ); ?>"><i class="fa-solid fa-phone" aria-hidden="true"></i><?php echo esc_html( $tmt_c['phone'] ); ?></a><?php endif; ?>
					<?php if ( $tmt_c['address'] ) : ?><span><i class="fa-solid fa-location-dot" aria-hidden="true"></i><?php echo esc_html( $tmt_c['address'] ); ?></span><?php endif; ?>
				</div>
			</div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="lp-form" id="khao-sat">
				<h3>Gửi yêu cầu sửa máy</h3>
				<?php if ( $tmt_sent ) : ?>
					<p class="tmt-ok" role="status"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Tiệm đã nhận yêu cầu <b><?php echo esc_html( $tmt_sent ); ?></b>. Tiệm gọi lại trong giờ làm việc.</p>
				<?php else : ?>
					<div class="sub">Tiệm gọi lại trong giờ làm việc để báo phí và hẹn giờ.</div>
					<?php if ( $tmt_error ) : ?><p class="tmt-err" role="alert"><?php echo esc_html( $tmt_error ); ?></p><?php endif; ?>
					<?php tmt_req_hidden( 'sua-may', 0, 'khao-sat', tmt_repair_url() ); ?>
					<div class="two">
						<div><label class="f" for="rv-name">Họ tên</label><input id="rv-name" name="name" maxlength="100" placeholder="Anh/chị ..." autocomplete="name"></div>
						<div><label class="f" for="rv-phone">Số điện thoại</label><input id="rv-phone" name="phone" required inputmode="tel" maxlength="20" placeholder="09xx xxx xxx" autocomplete="tel"></div>
					</div>
					<span class="f">Cần gì</span>
					<div class="lp-chips">
						<?php foreach ( $tmt_kinds as $i => $k ) : ?>
							<label><input type="radio" name="kind" value="<?php echo esc_attr( $k ); ?>"<?php checked( 0, $i ); ?>><span><?php echo esc_html( $k ); ?></span></label>
						<?php endforeach; ?>
					</div>
					<label class="f" for="rv-address">Địa chỉ (nếu cần thợ tới)</label>
					<input id="rv-address" name="address" maxlength="255" placeholder="Số nhà, phường ..." autocomplete="street-address">
					<label class="f" for="rv-need">Tình trạng máy</label>
					<textarea id="rv-need" name="note" maxlength="1000" rows="2" placeholder="Ví dụ: laptop Dell không lên nguồn"></textarea>
					<button type="submit" class="lp-btn y"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i>Gửi yêu cầu</button>
					<?php if ( $tmt_c['zalo'] ) : ?>
						<div class="alt"><a class="lp-btn o" href="<?php echo esc_url( $tmt_c['zalo'] ); ?>" target="_blank" rel="noopener"><i class="fa-regular fa-comment-dots" aria-hidden="true"></i>Nhắn Zalo</a></div>
					<?php endif; ?>
				<?php endif; ?>
			</form>
		</div>
	</section>
</div>
<?php
get_footer();
