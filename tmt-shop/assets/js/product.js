/* Trang chi tiết sản phẩm: bộ ảnh (Swiper), sản phẩm cùng loại, nút +/- số lượng, chọn loại bằng nút, thanh mua dính đáy, mục lục. */
(($) => {
	'use strict';
	const a11y = {
		prevSlideMessage: 'Ảnh trước',
		nextSlideMessage: 'Ảnh sau',
		firstSlideMessage: 'Ảnh đầu tiên',
		lastSlideMessage: 'Ảnh cuối cùng',
		paginationBulletMessage: 'Xem ảnh {{index}}',
		slideLabelMessage: 'Ảnh {{index}} / {{slidesLength}}',
	};
	const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;

	/* Bộ ảnh: tự chuyển, ảnh nhỏ bên dưới, vuốt, phóng to, xem toàn màn hình. Chọn loại có ảnh riêng thì nhảy tới ảnh đó. */
	document.querySelectorAll('[data-gallery]').forEach((root) => {
		const main = root.querySelector('[data-gallery-main]');
		const thumbsEl = root.querySelector('[data-gallery-thumbs]');
		const counter = root.querySelector('[data-gallery-counter]');
		const count = main.querySelectorAll('.swiper-slide').length;
		const thumbs = thumbsEl ? new Swiper(thumbsEl, { slidesPerView: 5, spaceBetween: 8, watchSlidesProgress: true, breakpoints: { 576: { slidesPerView: 6 } } }) : null;
		const swiper = new Swiper(main, {
			loop: count > 1,
			speed: 450,
			grabCursor: count > 1,
			zoom: { maxRatio: 2.5 },
			keyboard: { enabled: true, onlyInViewport: true },
			a11y,
			autoplay: count > 1 && !reduceMotion ? { delay: Number(root.dataset.delay || 4000), pauseOnMouseEnter: true, disableOnInteraction: false } : false,
			navigation: { nextEl: root.querySelector('[data-gallery-next]'), prevEl: root.querySelector('[data-gallery-prev]') },
			pagination: { el: root.querySelector('[data-gallery-dots]'), clickable: true },
			thumbs: thumbs ? { swiper: thumbs } : undefined,
			on: { slideChange(s) { if (counter) counter.textContent = `${s.realIndex + 1} / ${count}`; } },
		});
		const toggleFull = (on) => {
			root.classList.toggle('is-full', on);
			document.documentElement.style.overflow = on ? 'hidden' : '';
			swiper.update();
			if (on) swiper.autoplay && swiper.autoplay.stop(); else if (swiper.params.autoplay && swiper.params.autoplay.enabled) swiper.autoplay.start();
		};
		const full = root.querySelector('[data-gallery-full]');
		full && full.addEventListener('click', () => toggleFull(true));
		root.querySelector('[data-gallery-close]').addEventListener('click', () => toggleFull(false));
		main.addEventListener('dblclick', () => { if (!root.classList.contains('is-full')) toggleFull(true); });
		document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && root.classList.contains('is-full')) toggleFull(false); });

		$('form.variations_form').on('found_variation', (e, v) => {
			if (!v || !v.image_id) return;
			const slide = main.querySelector(`.swiper-slide[data-image-id="${v.image_id}"]:not(.swiper-slide-duplicate)`);
			if (slide) swiper.slideToLoop(Number(slide.dataset.swiperSlideIndex || 0));
		});
	});

	/* Sản phẩm cùng loại: băng chuyền. */
	document.querySelectorAll('[data-carousel]').forEach((el) => {
		const wrap = el.closest('[data-carousel-wrap]') || el.parentElement;
		new Swiper(el, {
			slidesPerView: 2.15,
			spaceBetween: 10,
			a11y,
			navigation: { nextEl: wrap.querySelector('[data-carousel-next]'), prevEl: wrap.querySelector('[data-carousel-prev]') },
			breakpoints: { 768: { slidesPerView: 3, spaceBetween: 14 }, 992: { slidesPerView: 4, spaceBetween: 16 } },
		});
	});

	/* Nút - / + quanh ô số lượng của WooCommerce. */
	const qty = (box) => {
		const input = box.querySelector('input.qty');
		if (!input || input.type === 'hidden' || box.querySelector('.sf-q')) return;
		const step = (d) => {
			const min = parseFloat(input.min) || 1, max = parseFloat(input.max) || Infinity, s = parseFloat(input.step) || 1;
			input.value = Math.min(max, Math.max(min, (parseFloat(input.value) || min) + d * s));
			$(input).trigger('change');
		};
		const mk = (d, label, text) => {
			const b = document.createElement('button');
			b.type = 'button';
			b.className = 'sf-q';
			b.setAttribute('aria-label', label);
			b.textContent = text;
			b.addEventListener('click', () => step(d));
			return b;
		};
		input.before(mk(-1, 'Bớt 1', '−'));
		input.after(mk(1, 'Thêm 1', '+'));
	};
	document.querySelectorAll('.sf-buy-wrap .quantity').forEach(qty);

	/* Chọn loại: thay ô chọn của WooCommerce bằng nút (kiểu buttons) hoặc danh sách (kiểu list); kiểu select giữ nguyên. */
	const wrap = document.querySelector('.sf-buy-wrap');
	const style = wrap ? wrap.dataset.variantStyle : 'select';
	$('form.variations_form').on('click', '.sf-buy-now.disabled', (e) => { e.preventDefault(); $(e.currentTarget).closest('form').find('.single_add_to_cart_button').trigger('click'); });
	$('form.variations_form').each(function () {
		if (style === 'select') return;
		const form = this;
		const build = () => {
			form.querySelectorAll('.variations select').forEach((sel) => {
				let box = sel.parentElement.querySelector('.sf-vopts');
				if (!box) {
					box = document.createElement('div');
					box.className = 'sf-vopts' + (style === 'list' ? ' is-list' : '');
					box.setAttribute('role', 'group');
					sel.after(box);
					sel.setAttribute('data-sf-hidden-select', '');
					box.addEventListener('click', (e) => {
						const b = e.target.closest('button');
						if (!b || b.disabled) return;
						$(sel).val(sel.value === b.dataset.v ? '' : b.dataset.v).trigger('change');
					});
				}
				box.innerHTML = '';
				[...sel.options].forEach((o) => {
					if (!o.value) return;
					const b = document.createElement('button');
					b.type = 'button';
					b.dataset.v = o.value;
					b.textContent = o.textContent;
					b.disabled = o.disabled;
					b.classList.toggle('on', o.value === sel.value);
					b.setAttribute('aria-pressed', o.value === sel.value ? 'true' : 'false');
					box.appendChild(b);
				});
			});
		};
		$(form).on('woocommerce_update_variation_values reset_data', () => setTimeout(build, 0));
		// "Mua ngay" khóa/mở cùng nút "Thêm vào giỏ" (chưa chọn đủ loại thì chưa mua được).
		$(form).on('show_variation hide_variation reset_data', () => setTimeout(() => {
			const add = form.querySelector('.single_add_to_cart_button'), now = form.querySelector('.sf-buy-now');
			if (add && now) now.classList.toggle('disabled', add.classList.contains('disabled'));
		}, 0));
		$(form).on('change', '.variations select', build);
		build();
	});

	/* Thanh mua dính đáy (điện thoại): bấm nút trong form mua của WooCommerce; loại chưa chọn thì cuộn tới chỗ chọn. */
	document.querySelectorAll('[data-sf-buy]').forEach((btn) => {
		btn.addEventListener('click', () => {
			const form = document.querySelector('.sf-buy-wrap form.cart');
			if (!form) return;
			const target = form.querySelector(btn.dataset.sfBuy === 'now' ? '.sf-buy-now' : '.single_add_to_cart_button');
			if (!target || target.classList.contains('disabled')) {
				form.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
				return;
			}
			target.click();
		});
	});

	/* Thanh mục lục: tô mục đang xem. */
	const tabs = [...document.querySelectorAll('.sf-tabs a')];
	if (tabs.length && 'IntersectionObserver' in window) {
		const io = new IntersectionObserver((entries) => {
			entries.forEach((en) => {
				if (en.isIntersecting) tabs.forEach((a) => a.classList.toggle('on', a.hash === '#' + en.target.id));
			});
		}, { rootMargin: '-40% 0px -55% 0px' });
		tabs.forEach((a) => { const t = document.querySelector(a.hash); t && io.observe(t); });
	}
})(jQuery);
