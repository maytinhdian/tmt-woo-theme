<?php
/**
 * Thanh toán kiểu C (một trang): mục 1 Giỏ hàng (đổi số lượng, bỏ món), mua kèm, mục 2 Thông tin nhận hàng (ô của WooCommerce
 * + cách nhận hàng = phương thức giao hàng), mục 3 Thanh toán (cổng thanh toán của WooCommerce); khung tổng xanh đậm bên phải
 * (thanh bước, miễn phí giao, mã giảm giá, tổng tiền, nút Đặt hàng). Điện thoại: tổng + Đặt hàng dính đáy.
 *
 * Nút số lượng / mã giảm giá thuộc 2 form riêng nằm ngoài form thanh toán (thuộc tính form=), xử lý bởi WooCommerce (update_cart).
 *
 * @package TMT_Shop
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo '<div class="sf-empty"><p>Cần đăng nhập để đặt hàng.</p><a class="sf-btn" href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '">Đăng nhập</a></div>';
	return;
}

$tmt_cart     = WC()->cart;
$tmt_count    = $tmt_cart->get_cart_contents_count();
$tmt_rates    = tmt_checkout_rates();
$tmt_pickup   = tmt_chosen_pickup();
$tmt_free     = tmt_free_shipping_from();
$tmt_suggest  = tmt_cart_suggestions( 4 );
$tmt_fields   = $checkout->get_checkout_fields();
$tmt_phone    = tmt_phone();
$tmt_zalo     = tmt_zalo_url();
$tmt_nonce    = wp_create_nonce( 'woocommerce-cart' );
$tmt_max_qty  = 99;
$tmt_ship_ico = array( 'local_pickup' => 'storefront', 'pickup_location' => 'storefront', 'free_shipping' => 'local_shipping', 'flat_rate' => 'local_shipping' );
?>
<form id="tmt-qty-form" method="post" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" hidden>
	<input type="hidden" name="woocommerce-cart-nonce" value="<?php echo esc_attr( $tmt_nonce ); ?>">
	<input type="hidden" name="update_cart" value="1">
	<input type="hidden" name="proceed" value="1">
</form>
<form id="tmt-coupon-form" method="post" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" hidden>
	<input type="hidden" name="woocommerce-cart-nonce" value="<?php echo esc_attr( $tmt_nonce ); ?>">
	<input type="hidden" name="apply_coupon" value="1">
	<input type="hidden" name="proceed" value="1">
</form>

<?php do_action( 'woocommerce_before_checkout_form', $checkout ); ?>

<form name="checkout" method="post" class="checkout woocommerce-checkout sfc" id="sf-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="Đặt hàng">
	<div class="sfc-cols">
		<div class="sfc-main">
			<?php /* 1. Giỏ hàng */ ?>
			<section class="sfc-sec" id="sfc-s1" data-step="1">
				<div class="sfc-sh"><span class="sfc-no">1</span><h1>Giỏ hàng <span><?php echo (int) $tmt_count; ?> sản phẩm</span></h1>
					<a href="<?php echo esc_url( tmt_shop_url() ); ?>" class="sfc-lnk"><i class="fa-solid fa-plus" aria-hidden="true"></i>Thêm sản phẩm</a></div>
				<?php foreach ( $tmt_cart->get_cart() as $tmt_key => $tmt_item ) : ?>
					<?php
					$p = apply_filters( 'woocommerce_cart_item_product', $tmt_item['data'], $tmt_item, $tmt_key );
					if ( ! $p || ! $p->exists() || $tmt_item['quantity'] <= 0 || ! apply_filters( 'woocommerce_cart_item_visible', true, $tmt_item, $tmt_key ) ) {
						continue;
					}
					$qty      = (int) $tmt_item['quantity'];
					$url      = $p->is_visible() ? $p->get_permalink( $tmt_item ) : '';
					$regular  = (float) $p->get_regular_price();
					$on_sale  = $p->is_on_sale() && $regular > (float) $p->get_price();
					$off      = $on_sale ? (int) round( 100 - (float) $p->get_price() * 100 / $regular ) : 0;
					$parent   = $p->get_parent_id() ? wc_get_product( $p->get_parent_id() ) : $p;
					$warranty = tmt_warranty_months( $p );
					$meta     = wc_get_formatted_cart_item_data( $tmt_item, true );
					$max      = $p->get_max_purchase_quantity() > 0 ? min( $tmt_max_qty, $p->get_max_purchase_quantity() ) : $tmt_max_qty;
					$gift     = function_exists( 'tmt_gift_line' ) ? tmt_gift_line( $tmt_item ) : null; // Dòng quà tặng kèm (plugin).
					if ( $gift ) {
						$on_sale = false;
						$off     = 0;
					}
					?>
					<div class="sfc-it <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $tmt_item, $tmt_key ) ); ?>">
						<<?php echo $url ? 'a href="' . esc_url( $url ) . '"' : 'span'; // phpcs:ignore ?> class="sfc-th" tabindex="-1" aria-hidden="true">
							<?php if ( $p->get_image_id() ) : ?>
								<?php echo wp_get_attachment_image( $p->get_image_id(), 'woocommerce_gallery_thumbnail', false, array( 'alt' => '', 'loading' => 'lazy' ) ); ?>
							<?php else : ?>
								<?php echo tmt_icon( tmt_category_icon( tmt_product_category( $parent ) ) ); // phpcs:ignore ?>
							<?php endif; ?>
							<?php if ( $off ) : ?><span>-<?php echo (int) $off; ?>%</span><?php endif; ?>
						</<?php echo $url ? 'a' : 'span'; ?>>
						<div class="sfc-inf">
							<?php if ( $url ) : ?><a href="<?php echo esc_url( $url ); ?>" class="sfc-nm"><?php echo esc_html( $p->get_name() ); ?></a><?php else : ?><span class="sfc-nm"><?php echo esc_html( $p->get_name() ); ?></span><?php endif; ?>
							<div class="sfc-meta">
								<?php if ( $p->is_in_stock() && ! $p->is_on_backorder( $qty ) ) : ?>
									<span class="sfc-stk ok"><i class="fa-solid fa-circle-check" aria-hidden="true"></i>Còn hàng</span>
								<?php else : ?>
									<span class="sfc-stk no"><i class="fa-regular fa-clock" aria-hidden="true"></i>Tạm hết, tiệm gọi báo ngày có hàng</span>
								<?php endif; ?>
								<?php if ( $warranty ) : ?><span class="sfc-bh"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i>BH <?php echo (int) $warranty; ?> tháng</span><?php endif; ?>
								<?php if ( $gift ) : ?>
									<span class="sfc-gift"><i class="fa-solid fa-gift" aria-hidden="true"></i>Quà tặng · <?php echo esc_html( $gift['name'] ); ?></span>
								<?php elseif ( $meta && ! $p->is_type( 'variation' ) ) : ?><span class="sfc-var"><?php echo esc_html( $meta ); ?></span><?php endif; ?>
							</div>
							<?php if ( $gift && $gift['choices'] ) : ?>
								<div class="sfc-gift-pick"><span>Đổi quà:</span><?php foreach ( $gift['choices'] as $c ) : ?><a href="<?php echo esc_url( $c['url'] ); ?>"<?php echo $c['on'] ? ' class="on" aria-current="true"' : ''; ?>><?php echo esc_html( $c['name'] ); ?></a><?php endforeach; ?></div>
							<?php endif; ?>
						</div>
						<?php if ( $gift ) : ?>
							<span class="sfc-qty is-fixed">x<?php echo (int) $qty; ?></span>
						<?php elseif ( $p->is_sold_individually() ) : ?>
							<span class="sfc-qty is-fixed">x1</span>
						<?php else : ?>
							<span class="sfc-qty">
								<button form="tmt-qty-form" name="cart[<?php echo esc_attr( $tmt_key ); ?>][qty]" value="<?php echo (int) ( $qty - 1 ); ?>" aria-label="<?php echo esc_attr( 'Bớt một ' . $p->get_name() ); ?>"><i class="fa-solid fa-minus" aria-hidden="true"></i></button>
								<span aria-label="Số lượng"><?php echo (int) $qty; ?></span>
								<button form="tmt-qty-form" name="cart[<?php echo esc_attr( $tmt_key ); ?>][qty]" value="<?php echo (int) ( $qty + 1 ); ?>" aria-label="<?php echo esc_attr( 'Thêm một ' . $p->get_name() ); ?>"<?php disabled( $qty >= $max ); ?>><i class="fa-solid fa-plus" aria-hidden="true"></i></button>
							</span>
						<?php endif; ?>
						<div class="sfc-pc">
							<b><?php echo wp_kses_post( $gift ? 'Tặng' : $tmt_cart->get_product_subtotal( $p, $qty ) ); ?></b>
							<?php if ( $gift && $gift['regular'] > 0 ) : ?><s><?php echo wp_kses_post( wc_price( $gift['regular'] ) ); ?></s><?php endif; ?>
							<?php if ( $on_sale ) : ?><s><?php echo wp_kses_post( wc_price( wc_get_price_to_display( $p, array( 'price' => $regular, 'qty' => $qty ) ) ) ); ?></s><?php endif; ?>
						</div>
						<?php if ( $gift ) : ?>
							<span class="sfc-rm" aria-hidden="true"></span>
						<?php else : ?>
						<a href="<?php echo esc_url( wc_get_cart_remove_url( $tmt_key ) ); ?>" class="sfc-rm" aria-label="<?php echo esc_attr( 'Bỏ ' . $p->get_name() ); ?>" title="Bỏ khỏi giỏ"><i class="fa-solid fa-xmark" aria-hidden="true"></i></a>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</section>

			<?php if ( $tmt_suggest ) : ?>
				<section class="sfc-sec sfc-acc">
					<div class="sfc-acc-h"><h2><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> Mua kèm giá tốt</h2><a href="<?php echo esc_url( tmt_shop_url() ); ?>">Xem thêm <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a></div>
					<div class="sfc-acc-g"><?php foreach ( $tmt_suggest as $tmt_p ) : ?><div><?php tmt_product_card( $tmt_p ); ?></div><?php endforeach; ?></div>
				</section>
			<?php endif; ?>

			<?php /* 2. Thông tin nhận hàng */ ?>
			<section class="sfc-sec" id="sfc-s2" data-step="2">
				<div class="sfc-sh"><span class="sfc-no">2</span><h2>Thông tin nhận hàng</h2></div>
				<?php if ( is_user_logged_in() ) : ?>
					<p class="sfc-member is-in"><i class="fa-solid fa-user-check" aria-hidden="true"></i> Đang đặt bằng tài khoản <b><?php echo esc_html( wp_get_current_user()->display_name ); ?></b>. Đơn sẽ hiện trong Tài khoản của bạn.</p>
				<?php elseif ( 'yes' === get_option( 'woocommerce_enable_checkout_login_reminder' ) ) : ?>
					<div class="sfc-member">
						<div class="sfc-member-ic"><i class="fa-solid fa-user-check" aria-hidden="true"></i></div>
						<div><b>Đã có tài khoản?</b><span>Đăng nhập để tự điền thông tin và xem lại đơn trong Tài khoản.</span></div>
						<div class="sfc-member-btns"><a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="sfc-btn o sm"><i class="fa-regular fa-user" aria-hidden="true"></i>Đăng nhập</a></div>
					</div>
				<?php endif; ?>

				<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>
				<div id="customer_details" class="sfc-fields woocommerce-billing-fields">
					<?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>
					<div class="woocommerce-billing-fields__field-wrapper">
						<?php
						foreach ( $tmt_fields['billing'] as $tmt_k => $tmt_f ) {
							if ( 'billing_address_1' === $tmt_k ) {
								continue; // In sau phần Cách nhận hàng.
							}
							woocommerce_form_field( $tmt_k, $tmt_f, $checkout->get_value( $tmt_k ) );
						}
						?>
					</div>

					<?php if ( $tmt_cart->needs_shipping() ) : ?>
						<p class="sfc-fh">Cách nhận hàng</p>
						<?php if ( $tmt_rates['rates'] ) : ?>
							<div class="sfc-opts" id="sfc-ship">
								<?php foreach ( $tmt_rates['rates'] as $tmt_id => $tmt_rate ) : ?>
									<?php
									$tmt_mid  = $tmt_rate->get_method_id();
									$tmt_cost = (float) $tmt_rate->get_cost();
									$tmt_sub  = tmt_is_pickup_rate( $tmt_rate ) ? tmt_address() : ( $tmt_cost > 0 ? wp_strip_all_tags( wc_price( $tmt_cost ) ) : 'Miễn phí giao' );
									?>
									<label class="sfc-opt">
										<input type="radio" name="shipping_method[0]" data-index="0" value="<?php echo esc_attr( $tmt_id ); ?>" class="shipping_method" data-pickup="<?php echo tmt_is_pickup_rate( $tmt_rate ) ? '1' : '0'; ?>"<?php checked( $tmt_id, $tmt_rates['chosen'] ); ?>>
										<?php echo tmt_icon( isset( $tmt_ship_ico[ $tmt_mid ] ) ? $tmt_ship_ico[ $tmt_mid ] : 'local_shipping' ); // phpcs:ignore ?>
										<span><b><?php echo esc_html( $tmt_rate->get_label() ); ?></b><?php if ( $tmt_sub ) : ?><small><?php echo esc_html( $tmt_sub ); ?></small><?php endif; ?></span><em aria-hidden="true"></em>
									</label>
								<?php endforeach; ?>
							</div>
						<?php else : ?>
							<p class="sfc-noship"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Tiệm sẽ gọi báo cách giao và phí giao.</p>
						<?php endif; ?>
					<?php endif; ?>

					<?php if ( isset( $tmt_fields['billing']['billing_address_1'] ) ) : ?>
						<div id="sfc-address"<?php echo $tmt_pickup || ! $tmt_cart->needs_shipping() ? ' hidden' : ''; ?>>
							<?php woocommerce_form_field( 'billing_address_1', $tmt_fields['billing']['billing_address_1'], $checkout->get_value( 'billing_address_1' ) ); ?>
						</div>
					<?php endif; ?>

					<?php foreach ( isset( $tmt_fields['order'] ) ? $tmt_fields['order'] : array() as $tmt_k => $tmt_f ) : ?>
						<?php woocommerce_form_field( $tmt_k, $tmt_f, $checkout->get_value( $tmt_k ) ); ?>
					<?php endforeach; ?>
					<?php do_action( 'woocommerce_after_checkout_billing_form', $checkout ); ?>

					<?php if ( ! is_user_logged_in() && $checkout->is_registration_enabled() ) : ?>
						<div class="woocommerce-account-fields">
							<?php if ( ! $checkout->is_registration_required() ) : ?>
								<p class="form-row form-row-wide create-account">
									<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
										<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="createaccount" <?php checked( ( true === $checkout->get_value( 'createaccount' ) || ( true === apply_filters( 'woocommerce_create_account_default_checked', false ) ) ), true ); ?> type="checkbox" name="createaccount" value="1" /> <span>Tạo tài khoản để xem lại đơn</span>
									</label>
								</p>
							<?php endif; ?>
							<?php if ( $checkout->get_checkout_fields( 'account' ) ) : ?>
								<div class="create-account">
									<?php foreach ( $checkout->get_checkout_fields( 'account' ) as $tmt_k => $tmt_f ) : ?>
										<?php woocommerce_form_field( $tmt_k, $tmt_f, $checkout->get_value( $tmt_k ) ); ?>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
				<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
			</section>

			<?php /* 3. Thanh toán */ ?>
			<section class="sfc-sec" id="sfc-s3" data-step="3">
				<div class="sfc-sh"><span class="sfc-no">3</span><h2>Thanh toán</h2></div>
				<?php woocommerce_checkout_payment(); ?>
			</section>
		</div>

		<?php /* Khung tổng */ ?>
		<aside class="sfc-side" aria-label="Tóm tắt đơn hàng">
			<div class="sfc-navy" id="order_review">
				<ol class="sfc-steps is-v" aria-label="Các bước đặt hàng">
					<li class="on" data-step-dot="1"><a href="#sfc-s1"><span>1</span><b>Giỏ hàng</b></a></li>
					<li data-step-dot="2"><a href="#sfc-s2"><span>2</span><b>Thông tin nhận hàng</b></a></li>
					<li data-step-dot="3"><a href="#sfc-s3"><span>3</span><b>Thanh toán</b></a></li>
				</ol>

				<?php if ( $tmt_free > 0 && $tmt_cart->needs_shipping() ) : ?>
					<?php
					$tmt_have = tmt_cart_amount_for_free_shipping();
					$tmt_ok   = $tmt_have >= $tmt_free;
					$tmt_pct  = (int) min( 100, round( $tmt_have * 100 / $tmt_free ) );
					?>
					<div class="sfc-ship<?php echo $tmt_ok ? ' ok' : ''; ?>">
						<div class="sfc-ship-t"><i class="fa-solid fa-truck-fast" aria-hidden="true"></i>
							<?php if ( $tmt_ok ) : ?><span>Đơn của bạn được <b>giao miễn phí</b></span>
							<?php else : ?><span>Mua thêm <b><?php echo wp_kses_post( wc_price( $tmt_free - $tmt_have ) ); ?></b> để được <b>giao miễn phí</b></span><?php endif; ?>
						</div>
						<div class="sfc-ship-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo (int) $tmt_pct; ?>" aria-label="Tiến độ miễn phí giao hàng">
							<i style="width: <?php echo (int) $tmt_pct; ?>%"></i><em style="left: <?php echo (int) $tmt_pct; ?>%"><i class="fa-solid <?php echo $tmt_ok ? 'fa-circle-check' : 'fa-truck'; ?>" aria-hidden="true"></i></em>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( wc_coupons_enabled() ) : ?>
					<div class="sfc-vch">
						<?php foreach ( $tmt_cart->get_applied_coupons() as $tmt_code ) : ?>
							<div class="sfc-vok"><i class="fa-solid fa-tag" aria-hidden="true"></i><b><?php echo esc_html( strtoupper( $tmt_code ) ); ?></b><span>Đã áp dụng</span><a href="<?php echo esc_url( add_query_arg( array( 'remove_coupon' => rawurlencode( $tmt_code ), 'woocommerce-cart-nonce' => $tmt_nonce ), wc_get_checkout_url() ) ); ?>" aria-label="<?php echo esc_attr( 'Bỏ mã ' . $tmt_code ); ?>">Bỏ</a></div>
						<?php endforeach; ?>
						<div class="sfc-vin">
							<label for="sfc-coupon" class="visually-hidden">Mã giảm giá</label>
							<span><i class="fa-solid fa-ticket" aria-hidden="true"></i><input form="tmt-coupon-form" id="sfc-coupon" name="coupon_code" placeholder="Nhập mã giảm giá" maxlength="30" autocomplete="off" autocapitalize="characters"></span>
							<button form="tmt-coupon-form" type="submit">Áp dụng</button>
						</div>
					</div>
				<?php endif; ?>

				<?php woocommerce_order_review(); ?>
				<?php do_action( 'woocommerce_checkout_order_review' ); ?>

				<button type="submit" form="sf-checkout" class="sfc-btn y big" data-sfc-place>Đặt hàng <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
				<?php if ( tmt_opt( 'checkout.note' ) ) : ?><p class="sfc-note"><?php echo esc_html( tmt_opt( 'checkout.note' ) ); ?></p><?php endif; ?>
				<?php if ( $tmt_phone ) : ?>
					<div class="sfc-help"><?php if ( $tmt_zalo ) : ?><a href="<?php echo esc_url( $tmt_zalo ); ?>" target="_blank" rel="noopener" class="sfc-zl">Zalo</a><?php endif; ?>
						<span>Cần tư vấn trước khi đặt? <?php if ( $tmt_zalo ) : ?><a href="<?php echo esc_url( $tmt_zalo ); ?>" target="_blank" rel="noopener">Nhắn tiệm</a> hoặc gọi <?php else : ?>Gọi <?php endif; ?><a href="tel:<?php echo esc_attr( tmt_digits( $tmt_phone ) ); ?>"><?php echo esc_html( $tmt_phone ); ?></a></span></div>
				<?php endif; ?>
			</div>
		</aside>
	</div>
	<div class="sfc-mbar" data-sf-bottom-bar>
		<?php echo tmt_checkout_mbar_sum(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<button type="submit" form="sf-checkout" class="sfc-btn y" data-sfc-place>Đặt hàng <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
	</div>
</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
