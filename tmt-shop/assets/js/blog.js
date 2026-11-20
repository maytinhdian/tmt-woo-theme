/* Mẹo vặt: bài chọn chạy vòng, thanh tiến độ đọc, mục lục đang đọc, chia sẻ, "Bài này có giúp ích?" (chép từ trang Mẹo vặt của app). */
(() => {
	// Bài chọn ở đầu danh sách: chuyển bằng chấm tròn, tự chạy mỗi 7 giây (dừng khi rê chuột vào).
	const box = document.querySelector('[data-mv-slider]');
	if (box) {
		const slides = [...box.querySelectorAll('.mv-slide')];
		let cur = 0, hold = false;
		const go = (i) => {
			cur = (i + slides.length) % slides.length;
			slides.forEach((s, k) => { s.hidden = k !== cur; });
		};
		box.addEventListener('click', (e) => { const b = e.target.closest('[data-mv-go]'); if (b) go(+b.dataset.mvGo); });
		box.addEventListener('mouseenter', () => { hold = true; });
		box.addEventListener('mouseleave', () => { hold = false; });
		if (slides.length > 1 && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
			setInterval(() => { if (!hold && !document.hidden) go(cur + 1); }, 7000);
		}
	}

	const post = document.querySelector('[data-mv-post]');
	if (!post) return;
	document.body.classList.add('has-mv-mbar');

	// Thanh tiến độ đọc + tô sáng mục đang đọc trong mục lục.
	const bar = document.querySelector('[data-mv-readbar]');
	const art = post.querySelector('.mv-art article');
	const links = [...post.querySelectorAll('[data-mv-toc]')];
	const heads = links.map((a) => document.getElementById(decodeURIComponent(a.hash.slice(1)))).filter(Boolean);
	const onScroll = () => {
		const r = art.getBoundingClientRect();
		const done = Math.min(1, Math.max(0, -r.top / Math.max(1, r.height - innerHeight)));
		if (bar) bar.style.width = (done * 100) + '%';
		let on = -1;
		heads.forEach((h, i) => { if (h.getBoundingClientRect().top < 160) on = i; });
		links.forEach((a, i) => a.classList.toggle('on', i === on));
	};
	addEventListener('scroll', onScroll, { passive: true });
	onScroll();
	post.querySelector('[data-mv-open-toc]')?.addEventListener('click', () => { const t = document.getElementById('muc-luc'); if (t) t.open = true; });

	// Chia sẻ: điện thoại mở bảng chia sẻ của máy (Zalo, Messenger...), máy tính sao chép liên kết.
	const url = post.dataset.url, title = post.dataset.title;
	const copy = async (btn) => {
		try {
			await navigator.clipboard.writeText(url);
			btn.classList.add('on');
			btn.setAttribute('aria-label', 'Đã sao chép liên kết');
		} catch (e) {
			window.prompt('Sao chép liên kết:', url);
		}
	};
	post.querySelectorAll('[data-mv-copy]').forEach((b) => b.addEventListener('click', () => copy(b)));
	post.querySelector('[data-mv-share]')?.addEventListener('click', (e) => {
		if (navigator.share) navigator.share({ title, url }).catch(() => {}); else copy(e.currentTarget);
	});

	// "Bài này có giúp ích?": gửi bằng fetch, không tải lại trang.
	const vote = post.querySelector('[data-mv-vote]');
	vote?.addEventListener('submit', async (e) => {
		e.preventDefault();
		const body = new FormData(vote);
		body.append('vote', e.submitter?.value || 'co');
		const res = await fetch(vote.action, { method: 'POST', body, credentials: 'same-origin', headers: { Accept: 'application/json' } });
		if (res.ok) {
			vote.closest('.mv-helpful').querySelector('span').textContent = 'Cảm ơn anh chị đã cho tiệm biết!';
			vote.remove();
		}
	});
})();
