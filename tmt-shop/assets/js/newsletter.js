/* Nút "Đăng ký nhận tin" ở chân trang (port từ app storefront-newsletter.js): bấm thì máy bay giấy rời nút, bay lượn
 * 2 vòng rồi về đúng chỗ. Chỉ nạp khi Tùy biến → Hiệu ứng → "máy bay giấy" bật (form có data-sf-nl-plane).
 * Gửi email bằng fetch (plugin trả JSON) nên trang không tải lại; lỗi mạng thì gửi form như thường.
 * Máy bật "giảm chuyển động": không bay, chỉ gửi. */
(function () {
	'use strict';
	const FLY_MS = 4200;
	const LOOPS = 2;
	const reduced = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	const DEG = Math.PI / 180;
	// fa-paper-plane vẽ mũi chếch 45° lên phải: hướng bay lúc cất cánh và lúc về chỗ là "lên phải" (-45° trên màn hình),
	// nên máy bay rời nút và về nút đúng góc đang đặt, không xoay giật.
	const NOSE = -45;

	/**
	 * Đường bay (Anh 2 21/11): cất cánh theo mũi (chếch lên phải), lượn ngang sang phải nhào LOOPS vòng lớn, rồi ngoặt xuống,
	 * vòng xuống dưới nút và bay chếch lên từ dưới về đúng chỗ. Dựng kiểu "rùa" (đi tới + quẹo) rồi đoạn về là đường
	 * cong Bezier kết thúc theo hướng mũi. Trả về điểm {x, y, a} đều theo quãng đường (a = hướng bay, độ).
	 */
	function flightPath() {
		const pts = [];
		let x = 0, y = 0, a = NOSE;
		const step = 2;
		const go = len => { for (let d = 0; d < len; d += step) { x += step * Math.cos(a * DEG); y += step * Math.sin(a * DEG); pts.push({ x, y, a }); } };
		const turn = (deg, r) => {
			const n = Math.max(1, Math.round((Math.abs(deg) * DEG * r) / step));
			for (let i = 0; i < n; i++) { a += deg / n; x += step * Math.cos(a * DEG); y += step * Math.sin(a * DEG); pts.push({ x, y, a }); }
		};
		pts.push({ x, y, a });
		go(55); // cất cánh theo mũi
		turn(45, 70); // lượn ngang sang phải
		for (let i = 0; i < LOOPS; i++) { // nhào LOOPS vòng lớn nối nhau (quẹo trái = vòng lên trên)
			if (i) go(80);
			turn(-360, 58);
		}
		go(30);
		turn(150, 80); // lượn vòng xuống, hướng về phía dưới bên trái nút
		go(70);
		// Về chỗ: Bezier từ điểm hiện tại (giữ hướng đang bay) tới (0,0), đi vào theo hướng mũi từ phía dưới bên trái nút.
		const p0 = { x, y };
		const k = 120;
		const p1 = { x: x + k * Math.cos(a * DEG), y: y + k * Math.sin(a * DEG) };
		const p2 = { x: -k * Math.cos(NOSE * DEG), y: -k * Math.sin(NOSE * DEG) };
		const bez = (t, u, v, w, z) => (1 - t) ** 3 * u + 3 * (1 - t) ** 2 * t * v + 3 * (1 - t) * t * t * w + t ** 3 * z;
		let prev = p0;
		for (let i = 1; i <= 160; i++) {
			const t = i / 160;
			const q = { x: bez(t, p0.x, p1.x, p2.x, 0), y: bez(t, p0.y, p1.y, p2.y, 0) };
			const dir = Math.atan2(q.y - prev.y, q.x - prev.x) / DEG;
			pts.push({ ...q, a: dir });
			prev = q;
		}
		pts[pts.length - 1].a = NOSE;
		// Hướng liền mạch (không nhảy 360°) để xoay mượt.
		for (let i = 1; i < pts.length; i++) {
			while (pts[i].a - pts[i - 1].a > 180) pts[i].a -= 360;
			while (pts[i].a - pts[i - 1].a < -180) pts[i].a += 360;
		}
		// Chia lại đều theo quãng đường.
		const len = [0];
		for (let i = 1; i < pts.length; i++) len.push(len[i - 1] + Math.hypot(pts[i].x - pts[i - 1].x, pts[i].y - pts[i - 1].y));
		return { pts, len, total: len[len.length - 1] };
	}

	function loopFrames(ink, sky) {
		const { pts, len, total } = flightPath();
		// Êm hai đầu kiểu sin: cất cánh và hạ cánh chậm, giữa bay đều.
		const ease = t => (1 - Math.cos(Math.PI * t)) / 2;
		const frames = [];
		const steps = 150;
		let j = 0;
		for (let i = 0; i <= steps; i++) {
			const t = i / steps;
			const d = ease(t) * total;
			while (j < len.length - 2 && len[j + 1] < d) j++;
			const f = len[j + 1] > len[j] ? (d - len[j]) / (len[j + 1] - len[j]) : 0;
			const p = pts[j], q = pts[j + 1];
			const x = p.x + (q.x - p.x) * f;
			const y = p.y + (q.y - p.y) * f;
			const ang = p.a + (q.a - p.a) * f - NOSE; // khung cuối = bội của 360°, trùng góc gốc nên không giật
			const scale = 1 + 0.35 * Math.sin(Math.PI * t);
			// Trên nút vàng: màu chữ nút; rời nút ra nền chân trang xanh đậm: vàng cho nổi.
			const color = t < 0.03 || t > 0.97 ? ink : sky;
			frames.push({ transform: `translate(${x.toFixed(1)}px, ${y.toFixed(1)}px) rotate(${ang.toFixed(1)}deg) scale(${scale.toFixed(3)})`, color, offset: t });
		}
		return frames;
	}

	function fly(icon) {
		if (reduced() || !icon) return Promise.resolve();
		const r = icon.getBoundingClientRect();
		const cs = getComputedStyle(icon);
		const plane = icon.cloneNode(true);
		plane.classList.add('sf-nl-flying');
		Object.assign(plane.style, {
			position: 'fixed', left: r.left + 'px', top: r.top + 'px', width: r.width + 'px', height: r.height + 'px',
			fontSize: cs.fontSize, lineHeight: r.height + 'px', color: cs.color, zIndex: 1100, pointerEvents: 'none', margin: 0,
		});
		document.body.appendChild(plane);
		icon.style.visibility = 'hidden';
		const sky = getComputedStyle(document.body).getPropertyValue('--sf-yellow').trim() || cs.color;
		const anim = plane.animate(loopFrames(cs.color, sky), { duration: FLY_MS, easing: 'linear' });
		return new Promise(resolve => {
			anim.onfinish = anim.oncancel = () => { plane.remove(); icon.style.visibility = ''; resolve(); };
		});
	}

	function show(box, ok, text) {
		if (!box) return;
		const p = document.createElement('p');
		p.className = (ok ? 'sf-nl-ok' : 'sf-nl-err') + ' mb-0';
		p.setAttribute('role', ok ? 'status' : 'alert');
		if (ok) {
			const i = document.createElement('i');
			i.className = 'fa-solid fa-circle-check';
			i.setAttribute('aria-hidden', 'true');
			p.append(i, ' ');
		}
		p.append(text);
		box.replaceChildren(p);
	}

	document.addEventListener('submit', e => {
		const form = e.target;
		if (!(form instanceof HTMLFormElement) || !form.matches('[data-sf-nl-plane]') || form.dataset.native) return;
		e.preventDefault();
		const btn = form.querySelector('button[type="submit"]');
		if (btn && btn.disabled) return;
		if (btn) btn.disabled = true;
		const box = form.parentElement.querySelector('[data-sf-nl-msg]');

		const sent = fetch(form.getAttribute('action'), { // form có ô tên "action" (admin-post) nên form.action là ô đó, không phải địa chỉ
			method: 'POST',
			headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
			body: new FormData(form),
			credentials: 'same-origin',
		}).then(async res => ({ res, data: await res.json().catch(() => ({})) }));

		Promise.all([sent, fly(form.querySelector('.sf-nl-icon'))]).then(([{ res, data }]) => {
			if (res.ok && data.ok) {
				show(box, true, 'Cảm ơn Anh/Chị, tiệm sẽ gửi tin mới qua email.');
				form.reset();
			} else if (res.status === 422) {
				show(box, false, data.message || 'Email chưa đúng.');
			} else {
				throw new Error('server');
			}
		}).catch(() => {
			// Lỗi mạng / máy chủ: gửi form như thường để máy chủ báo kết quả.
			form.dataset.native = '1';
			form.requestSubmit();
		}).finally(() => { if (btn) btn.disabled = false; });
	});
})();
