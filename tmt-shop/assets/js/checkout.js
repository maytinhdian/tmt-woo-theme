/* Thanh toán kiểu C: ẩn/hiện ô địa chỉ theo cách nhận hàng, thanh bước sáng theo mục đang xem, thêm hàng "Mua kèm" thì tải lại. */
(($) => {
	'use strict';
	const addr = document.getElementById('sfc-address');
	const sync = () => {
		const r = document.querySelector('#sfc-ship input[name^="shipping_method"]:checked');
		if (addr && r) addr.hidden = r.dataset.pickup === '1';
	};
	$(document.body).on('change', '#sfc-ship input[name^="shipping_method"]', sync);
	sync();

	// Thêm từ "Mua kèm" (thêm giỏ bằng AJAX): tải lại để mục 1 và tổng tiền có món mới.
	$(document.body).on('added_to_cart', () => location.reload());

	// Có lỗi khi đặt: WooCommerce đưa thông báo lên đầu form; cuộn tới đó (mặc định đã cuộn, chỉ cần mở lại mục chứa ô sai).
	$(document.body).on('checkout_error', () => {
		const bad = document.querySelector('.woocommerce-invalid input, .woocommerce-invalid select');
		if (bad && bad.closest('[hidden]')) bad.closest('[hidden]').hidden = false;
	});

	const dots = document.querySelectorAll('[data-step-dot]');
	if (!('IntersectionObserver' in window) || !dots.length) return;
	const seen = new Set();
	const io = new IntersectionObserver((entries) => {
		entries.forEach((e) => (e.isIntersecting ? seen.add(+e.target.dataset.step) : seen.delete(+e.target.dataset.step)));
		const on = seen.size ? Math.max(...seen) : 1;
		dots.forEach((d) => { const n = +d.dataset.stepDot; d.classList.toggle('on', n === on); d.classList.toggle('done', n < on); });
	}, { rootMargin: '-45% 0px -45% 0px' });
	document.querySelectorAll('.sfc-sec[data-step]').forEach((s) => io.observe(s));
})(jQuery);
