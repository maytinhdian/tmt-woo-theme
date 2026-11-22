/*
 * Nút "Thêm vào giỏ" có xe đẩy đón túi hàng (port storefront-cart-fx.js của app; Tùy biến → Hiệu ứng, inc/cart-fx.php).
 * <body data-cart-fx="cart|fly" data-cart-ajax="…wc-ajax=get_refreshed_fragments"> và biến --atc-ms.
 * Bấm: gửi form (hoặc add-to-cart của thẻ) kèm tmt_atc=1 bằng fetch, WooCommerce thêm giỏ rồi trả fragments; số giỏ không
 * tăng = không thêm được (hết hàng, chưa chọn loại...) thì gửi lại như thường để WooCommerce báo lỗi. "Mua ngay" không đụng tới.
 */
(() => {
	const body = document.body;
	const mode = body.dataset.cartFx;
	const ajax = body.dataset.cartAjax;
	const reduced = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	const duration = () => parseFloat(getComputedStyle(body).getPropertyValue('--atc-ms')) || 2000;
	const exitAt = () => duration() * 0.72;
	const cartLink = () => document.querySelector('[data-sf-cart-link]');
	const count = () => parseInt((document.querySelector('.sf-cart-n') || {}).textContent, 10) || 0;

	/* Nút "Thêm vào giỏ" của WooCommerce ở trang chi tiết: thay ruột bằng xe + túi (chữ giữ nguyên). */
	const tpl = document.getElementById('tmt-atc-tpl');
	document.querySelectorAll('.sf-buy-wrap .single_add_to_cart_button').forEach((b) => {
		if (!tpl || b.hasAttribute('data-sf-atc')) return;
		const label = b.textContent.trim() || 'Thêm vào giỏ';
		b.innerHTML = tpl.innerHTML.replaceAll('__LABEL__', label.replace(/[&<>"]/g, (c) => `&#${c.charCodeAt(0)};`));
		b.classList.add('sf-atc');
		b.setAttribute('data-sf-atc', '');
		b.setAttribute('aria-label', label);
	});

	function measure(btn) {
		btn.style.setProperty('--h', btn.offsetHeight + 'px');
		const sp = btn.querySelector('.sf-atc-face .sf-atc-sp');
		if (sp) btn.style.setProperty('--slot', sp.offsetLeft + 'px');
	}
	const measureAll = () => document.querySelectorAll('[data-sf-atc]').forEach((b) => { if (b.offsetParent) measure(b); });

	function bump(el) {
		if (!el) return;
		el.classList.remove('is-bump');
		void el.offsetWidth;
		el.classList.add('is-bump');
	}

	function apply(data) {
		Object.entries(data.fragments || {}).forEach(([sel, html]) => document.querySelectorAll(sel).forEach((el) => { el.outerHTML = html; }));
		const n = count();
		document.querySelectorAll('[data-sf-cart-count]').forEach((el) => { el.textContent = n; bump(el); });
		const link = cartLink();
		if (link) { link.setAttribute('aria-label', `Giỏ hàng, ${n} món`); bump(link); bump(link.querySelector('.sf-cart-n')); }
		if (window.jQuery) window.jQuery(document.body).trigger('wc_fragments_refreshed');
	}

	function flyBag(btn, done) {
		const link = cartLink();
		const bag = btn.querySelector('.sf-atc-bag');
		if (!link || !bag || !link.getClientRects().length) { done(); return; }
		const from = btn.getBoundingClientRect();
		const to = link.getBoundingClientRect();
		const el = document.createElement('div');
		el.className = 'sf-atc-fly';
		el.innerHTML = bag.innerHTML;
		const x0 = from.right - 26;
		const y0 = from.top + from.height / 2 - 16;
		el.style.left = x0 + 'px';
		el.style.top = y0 + 'px';
		document.body.appendChild(el);
		const dx = to.left + to.width / 2 - 16 - x0;
		const dy = to.top + to.height / 2 - 16 - y0;
		// Vòng cung: đi trên đường thẳng nút → giỏ, lệch vuông góc về phía trên theo sin²(πt).
		const dist = Math.hypot(dx, dy) || 1;
		const lift = Math.max(60, Math.min(170, dist * 0.32));
		let nx = dy / dist;
		let ny = -dx / dist;
		if (ny > 0 || (ny === 0 && nx > 0)) { nx = -nx; ny = -ny; }
		const ease = (t) => (t < 0.5 ? 2 * t * t : 1 - (-2 * t + 2) ** 2 / 2);
		const frames = [];
		for (let i = 0; i <= 36; i++) {
			const t = i / 36;
			const p = ease(t);
			const h = lift * Math.sin(Math.PI * t) ** 2;
			const scale = t < 0.2 ? 0.7 + (t / 0.2) * 0.35 : 1.05 - ((t - 0.2) / 0.8) * 0.5;
			frames.push({
				transform: `translate(${(dx * p + nx * h).toFixed(1)}px, ${(dy * p + ny * h).toFixed(1)}px) rotate(${(Math.sin(Math.PI * t) * -14).toFixed(1)}deg) scale(${scale.toFixed(3)})`,
				opacity: t < 0.1 ? t / 0.1 : 1,
				offset: t,
			});
		}
		const anim = el.animate(frames, { duration: Math.round(duration() * 0.45), easing: 'linear' });
		anim.onfinish = () => { el.remove(); done(); };
	}

	// Trang giỏ + thanh toán (gợi ý mua kèm): thêm xong tải lại để thấy dòng hàng và tổng tiền mới.
	const onCartPage = () => body.classList.contains('woocommerce-checkout') || body.classList.contains('woocommerce-cart');

	/** result: Promise dữ liệu fragments; undefined = đã gửi lại như thường. */
	function play(btn, result) {
		measure(btn);
		btn.classList.remove('is-adding');
		void btn.offsetWidth;
		btn.classList.add('is-adding');
		setTimeout(() => btn.classList.remove('is-adding'), duration());
		setTimeout(() => {
			if (!result) return;
			result.then((data) => {
				if (!data) return;
				const after = () => { apply(data); if (onCartPage()) setTimeout(() => location.reload(), 300); };
				mode === 'fly' ? flyBag(btn, after) : after();
			});
		}, exitAt());
	}

	async function send(fd) {
		const before = count();
		fd.append('tmt_atc', '1');
		const res = await fetch(ajax, { method: 'POST', body: fd, credentials: 'same-origin' });
		const data = await res.json();
		const tmp = document.createElement('div');
		tmp.innerHTML = (data.fragments || {})['.sf-cart-n'] || '';
		const after = parseInt(tmp.textContent, 10) || 0;
		if (!res.ok || after <= before) throw new Error('not-added');
		return data;
	}

	function run(btn, fd, fallback) {
		if (btn.classList.contains('is-adding')) return;
		const result = send(fd).catch(() => { btn.classList.remove('is-adding'); fallback(); return undefined; });
		if (reduced()) { result.then((d) => { if (d) { apply(d); if (onCartPage()) location.reload(); } }); return; }
		play(btn, result);
	}

	const visible = (el) => el && el.matches('[data-sf-atc]') && el.offsetParent !== null;

	// Trang chi tiết: form.cart (nút chính, hoặc nút "Thêm giỏ" ở thanh đáy điện thoại bấm hộ nút chính).
	document.addEventListener('submit', (e) => {
		const form = e.target;
		const sub = e.submitter;
		if (!(form instanceof HTMLFormElement) || !form.matches('form.cart') || form.dataset.atcNative) return;
		if (sub && (sub.classList.contains('sf-buy-now') || sub.hasAttribute('formaction'))) return;
		const btn = [sub, ...form.querySelectorAll('[data-sf-atc]'), ...document.querySelectorAll('[data-sf-buy="add"][data-sf-atc]')].find(visible);
		if (!btn) return;
		e.preventDefault();
		const fd = new FormData(form);
		if (sub && sub.name && !fd.has(sub.name)) fd.append(sub.name, sub.value);
		run(btn, fd, () => {
			form.dataset.atcNative = '1';
			if (sub && sub.form === form) form.requestSubmit(sub); else form.requestSubmit();
		});
	});

	// Thẻ sản phẩm: link ?add-to-cart=ID.
	document.addEventListener('click', (e) => {
		const a = e.target.closest('a[data-sf-atc][data-product_id]');
		if (!a || e.ctrlKey || e.metaKey || e.shiftKey) return;
		e.preventDefault();
		const fd = new FormData();
		fd.append('add-to-cart', a.dataset.product_id);
		fd.append('quantity', a.dataset.quantity || '1');
		run(a, fd, () => { location.href = a.href; });
	});

	const ro = 'ResizeObserver' in window ? new ResizeObserver((entries) => entries.forEach((en) => {
		const btn = en.target.closest('[data-sf-atc]');
		if (btn && btn.offsetParent) measure(btn);
	})) : null;
	document.querySelectorAll('[data-sf-atc]').forEach((b) => {
		if (!ro) return;
		ro.observe(b);
		const t = b.querySelector('.sf-atc-face .sf-atc-txt');
		if (t) ro.observe(t);
	});
	if (!ro) addEventListener('resize', measureAll);
	addEventListener('load', measureAll);
	measureAll();

	// Khung xem trước Tùy biến: chạy thử một lần trên nút đầu tiên đang thấy.
	if (body.hasAttribute('data-cart-fx-demo') && !reduced()) {
		addEventListener('load', () => setTimeout(() => {
			const btn = [...document.querySelectorAll('[data-sf-atc]')].find((b) => {
				const r = b.getBoundingClientRect();
				return b.offsetParent && r.top >= 0 && r.bottom <= innerHeight;
			});
			if (btn) play(btn, null);
			if (btn && mode === 'fly') setTimeout(() => flyBag(btn, () => bump(cartLink())), exitAt());
		}, 400));
	}
})();
