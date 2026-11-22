/* TMT Shop: menu xổ + mega menu, nút liên hệ nổi, bộ lọc danh sách, slider trang chủ, đếm ngược khuyến mãi. */
(() => {
	'use strict';

	/* Menu chính: bấm (điện thoại) hoặc rê chuột (máy tính) mở khung xổ; khung nằm ngoài <nav> để không bị cắt khi menu cuộn ngang. */
	const header = document.querySelector('.sf-header');
	if (header) {
		const hover = matchMedia('(hover: hover)').matches;
		const nav = header.querySelector('.sf-nav');
		const panel = (id) => document.getElementById(id);
		const btnOf = (id) => header.querySelector(`[data-sf-dd="${id}"]`);
		let openId = null, timer;
		const place = (btn, p) => {
			const box = p.querySelector('.sf-drop');
			if (!box) return;
			if (innerWidth < 768) { box.style.left = ''; return; }
			const h = header.getBoundingClientRect();
			const a = (btn.closest('.sf-nav-dd') || btn).getBoundingClientRect();
			const n = nav.getBoundingClientRect();
			box.style.left = '0px';
			const lo = n.left - h.left, hi = n.right - h.left - box.offsetWidth;
			box.style.left = Math.max(lo, Math.min(a.left - h.left - 16, hi)) + 'px';
		};
		const close = () => {
			if (!openId) return;
			panel(openId).hidden = true;
			const b = btnOf(openId);
			b.setAttribute('aria-expanded', 'false');
			(b.closest('.sf-nav-dd') || b).classList.remove('sf-open');
			nav && nav.classList.remove('sf-nav-open');
			openId = null;
		};
		const open = (btn) => {
			clearTimeout(timer);
			if (openId === btn.dataset.sfDd) return;
			close();
			const p = panel(btn.dataset.sfDd);
			if (!p) return;
			p.hidden = false;
			place(btn, p);
			btn.setAttribute('aria-expanded', 'true');
			(btn.closest('.sf-nav-dd') || btn).classList.add('sf-open');
			nav && nav.classList.add('sf-nav-open');
			openId = btn.dataset.sfDd;
		};
		const later = () => { clearTimeout(timer); timer = setTimeout(close, 200); };
		header.querySelectorAll('[data-sf-dd]').forEach((btn) => {
			btn.addEventListener('click', (e) => {
				e.preventDefault();
				if (!hover && openId === btn.dataset.sfDd) close(); else open(btn);
			});
			if (hover) {
				const zone = btn.closest('.sf-nav-dd') || btn;
				zone.addEventListener('mouseenter', () => open(btn));
				zone.addEventListener('mouseleave', later);
				const box = panel(btn.dataset.sfDd) && panel(btn.dataset.sfDd).querySelector('.sf-dd-box');
				if (box) {
					box.addEventListener('mouseenter', () => clearTimeout(timer));
					box.addEventListener('mouseleave', later);
				}
			}
		});
		document.addEventListener('click', (e) => { if (openId && !e.target.closest('.sf-dd-box, [data-sf-dd]')) close(); });
		document.addEventListener('keydown', (e) => {
			if (e.key === 'Escape' && openId) { const b = btnOf(openId); close(); b.focus(); }
		});
		nav && nav.addEventListener('scroll', close, { passive: true });
	}

	/* Nút liên hệ nổi: bung/thu; tự nâng lên trên thanh dính đáy (data-sf-bottom-bar) để không che nút Mua ngay. */
	const contact = document.querySelector('[data-sf-contact]');
	if (contact) {
		const btn = contact.querySelector('.sf-contact-toggle');
		const items = contact.querySelectorAll('.sf-contact-item');
		const setOpen = (on) => {
			contact.classList.toggle('open', on);
			btn.setAttribute('aria-expanded', on ? 'true' : 'false');
			items.forEach((a) => { a.tabIndex = on ? 0 : -1; });
		};
		btn.addEventListener('click', () => setOpen(!contact.classList.contains('open')));
		document.addEventListener('click', (e) => { if (!contact.contains(e.target)) setOpen(false); });
		document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && contact.classList.contains('open')) { setOpen(false); btn.focus(); } });
		const lift = () => {
			let h = 0;
			document.querySelectorAll('[data-sf-bottom-bar]').forEach((bar) => {
				const r = bar.getBoundingClientRect(), s = getComputedStyle(bar);
				if (s.display !== 'none' && s.visibility !== 'hidden' && r.height > 0 && r.bottom >= innerHeight - 2) h = Math.max(h, innerHeight - r.top);
			});
			contact.style.setProperty('--lift', h ? (h + 4) + 'px' : '0px');
		};
		lift();
		addEventListener('resize', lift);
		addEventListener('scroll', lift, { passive: true });
		if ('ResizeObserver' in window) {
			const ro = new ResizeObserver(lift);
			document.querySelectorAll('[data-sf-bottom-bar]').forEach((bar) => ro.observe(bar));
		}
	}

	/* Bộ lọc: mở một nút thả xuống thì đóng các nút khác, Esc đóng; tấm lọc cột trái trên điện thoại mở/đóng tại chỗ. */
	document.addEventListener('toggle', (e) => {
		const d = e.target;
		if (d.matches && d.matches('.sf-dd') && d.open) document.querySelectorAll('.sf-dd[open]').forEach((o) => { if (o !== d) o.open = false; });
	}, true);
	document.addEventListener('keydown', (e) => {
		if (e.key !== 'Escape') return;
		document.querySelectorAll('.sf-dd[open]').forEach((o) => { o.open = false; });
		const f = document.getElementById('sf-filter');
		f && f.classList.remove('open');
	});
	document.addEventListener('click', (e) => {
		const open = e.target.closest('[data-sf-open]');
		const close = e.target.closest('[data-sf-close]');
		if (open) { e.preventDefault(); const t = document.getElementById(open.dataset.sfOpen); t && t.classList.add('open'); }
		if (close) {
			e.preventDefault();
			const f = document.getElementById('sf-filter');
			f && f.classList.remove('open');
			if (location.hash === '#sf-filter') history.replaceState(null, '', location.pathname + location.search);
		}
	});

	/* Slider trang chủ: cuộn ngang có điểm dừng, chấm + mũi tên, tự chuyển (dừng khi rê chuột / chạm). */
	document.querySelectorAll('[data-slider]').forEach((box) => {
		const track = box.querySelector('.sf-slides'), slides = [...track.children];
		if (slides.length < 2) return;
		const dots = box.querySelector('.sf-sl-dots');
		const go = (i) => { i = (i + slides.length) % slides.length; track.scrollTo({ left: slides[i].offsetLeft, behavior: 'smooth' }); };
		const cur = () => Math.round(track.scrollLeft / track.clientWidth);
		slides.forEach((_, i) => {
			const b = document.createElement('button');
			b.type = 'button';
			b.setAttribute('aria-label', 'Slide ' + (i + 1));
			b.addEventListener('click', () => go(i));
			dots.appendChild(b);
		});
		const mark = () => [...dots.children].forEach((d, i) => d.classList.toggle('on', i === cur()));
		track.addEventListener('scroll', () => requestAnimationFrame(mark), { passive: true });
		mark();
		box.querySelector('.sf-sl-arr.l').addEventListener('click', () => go(cur() - 1));
		box.querySelector('.sf-sl-arr.r').addEventListener('click', () => go(cur() + 1));
		box.querySelectorAll('.sf-sl-arr').forEach((a) => { a.hidden = false; });
		if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;
		const ms = (+box.dataset.seconds || 5) * 1000;
		let paused = false;
		['mouseenter', 'focusin', 'touchstart'].forEach((ev) => box.addEventListener(ev, () => { paused = true; }, { passive: true }));
		['mouseleave', 'focusout'].forEach((ev) => box.addEventListener(ev, () => { paused = false; }));
		setInterval(() => { if (!paused && !document.hidden) go(cur() + 1); }, ms);
	});

	/* Đếm ngược khuyến mãi: hết giờ thì ẩn cả section. */
	document.querySelectorAll('[data-countdown]').forEach((el) => {
		const end = new Date(el.dataset.countdown).getTime(), b = el.querySelectorAll('b');
		let t;
		const tick = () => {
			let s = Math.max(0, Math.floor((end - Date.now()) / 1000));
			const d = Math.floor(s / 86400);
			s %= 86400;
			const v = [Math.floor(s / 3600) + d * 24, Math.floor(s / 60) % 60, s % 60];
			b.forEach((x, i) => { x.textContent = String(v[i]).padStart(2, '0'); });
			if (end <= Date.now()) { el.closest('section').hidden = true; clearInterval(t); }
		};
		t = setInterval(tick, 1000);
		tick();
	});

	/* Phần mềm bản quyền trang chủ: đổi tab tại chỗ, phím mũi tên trái/phải. */
	document.querySelectorAll('[data-sw]').forEach((sec) => {
		const tabs = [...sec.querySelectorAll('[data-sw-tab]')], more = sec.querySelector('[data-sw-more]');
		const pick = (tab) => {
			tabs.forEach((t) => {
				const on = t === tab;
				t.setAttribute('aria-selected', on);
				t.tabIndex = on ? 0 : -1;
				document.getElementById(t.getAttribute('aria-controls')).hidden = !on;
			});
			if (more) more.href = tab.dataset.href;
		};
		tabs.forEach((t, i) => {
			t.addEventListener('click', () => pick(t));
			t.addEventListener('keydown', (e) => {
				const d = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
				if (d) { const n = tabs[(i + d + tabs.length) % tabs.length]; pick(n); n.focus(); }
			});
		});
	});
})();
