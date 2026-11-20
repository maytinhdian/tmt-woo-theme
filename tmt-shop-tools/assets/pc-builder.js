/* Xây dựng cấu hình: chép từ khung lên cấu hình của app (bản web cho khách). Luật chạy ở máy chủ, trang hỏi lại mỗi lần đổi. */
(function () {
	const el = document.getElementById('pcb-data');
	if (!el) return;
	const D = JSON.parse(el.textContent);
	const byId = Object.fromEntries(D.parts.map(p => [p.id, p]));
	const S = { sel: {}, open: null, only: true, preset: null, res: null, seq: 0, checked: false };
	const vnd = n => Number(n || 0).toLocaleString('vi-VN') + ' ₫';
	const price = p => p.price === null ? 'Liên hệ' : vnd(p.price);
	const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
	const $ = id => document.getElementById(id);

	const pick = slot => S.sel[slot] ? byId[S.sel[slot].id] : null;
	const qty = slot => S.sel[slot] ? S.sel[slot].qty : 0;
	const reasons = p => (S.res && S.res.reasons[p.id]) || [];
	const code = () => Object.keys(D.slots).filter(k => S.sel[k]).map(k => S.sel[k].id + (S.sel[k].qty > 1 ? '*' + S.sel[k].qty : '')).join(',');
	const meta = p => esc(p.spec) + (p.bh ? (p.spec ? ' · ' : '') + 'BH ' + p.bh + ' tháng' : '');

	function stockTag(p) {
		if (p.price === null) return '<span class="pcb-tag info">Liên hệ báo giá</span>';
		return p.in_stock ? '<span class="pcb-tag ok">Còn hàng</span>' : '<span class="pcb-tag warn">Tạm hết, đặt 2-3 ngày</span>';
	}

	function thumb(p, icon) {
		return p && p.img ? '<img src="' + esc(p.img) + '" alt="" loading="lazy">' : '<i class="material-icons-round" aria-hidden="true">' + icon + '</i>';
	}

	function optRow(p, slot) {
		const r = reasons(p), cur = S.sel[slot] && S.sel[slot].id === p.id;
		return '<div class="pcb-opt' + (r.length ? ' no' : '') + (cur ? ' cur' : '') + '">'
			+ '<div class="pcb-ico sm">' + thumb(p, D.slots[slot].icon) + '</div>'
			+ '<div class="pcb-main"><a class="pcb-name" href="' + esc(p.url) + '" target="_blank" rel="noopener">' + esc(p.name) + '</a><div class="pcb-spec">' + meta(p) + '</div>'
			+ stockTag(p) + r.map(x => '<span class="pcb-tag err">' + esc(x) + '</span>').join('') + '</div>'
			+ '<div class="pcb-right"><span class="pcb-price">' + price(p) + (p.regular ? '<del>' + vnd(p.regular) + '</del>' : '') + '</span>'
			+ (cur ? '<span class="pcb-tag ok">Đang chọn</span>' : '<button type="button" class="pcb-btn' + (r.length ? '' : ' solid') + '" data-choose="' + p.id + '">Chọn</button>') + '</div></div>';
	}

	function renderSlots() {
		let html = '';
		for (const [slot, s] of Object.entries(D.slots)) {
			const p = pick(slot), q = qty(slot), open = S.open === slot, bad = p && reasons(p).length;
			html += '<div class="pcb-slot' + (p ? '' : ' empty') + (bad ? ' bad' : '') + (open ? ' open' : '') + '">'
				+ '<div class="pcb-ico">' + thumb(p, s.icon) + '</div><div class="pcb-main">'
				+ '<div class="pcb-label">' + esc(s.label) + (s.required ? ' <span class="req">*</span>' : '') + '</div>';
			if (p) {
				html += '<div class="pcb-name">' + esc(p.name) + '</div><div class="pcb-spec">' + meta(p) + '</div>' + stockTag(p)
					+ reasons(p).map(x => '<span class="pcb-tag err">' + esc(x) + '</span>').join('');
			} else {
				html += '<div class="pcb-spec">' + (s.required ? 'Chưa chọn' : 'Không bắt buộc') + '</div>';
			}
			html += '</div><div class="pcb-right">';
			if (p) {
				if (s.max > 1) html += '<span class="pcb-qty"><button type="button" data-q="' + slot + '" data-d="-1" aria-label="Bớt">−</button><span>' + q + '</span><button type="button" data-q="' + slot + '" data-d="1" aria-label="Thêm">+</button></span>';
				html += '<span class="pcb-price">' + (p.price === null ? 'Liên hệ' : vnd(p.price * q)) + '</span>'
					+ '<button type="button" class="pcb-btn" data-open="' + slot + '" aria-expanded="' + open + '">' + (open ? 'Đóng' : 'Đổi') + '</button>'
					+ '<button type="button" class="pcb-x" data-del="' + slot + '" aria-label="Bỏ ' + esc(s.label) + '"><i class="material-icons-round" aria-hidden="true">close</i></button>';
			} else {
				html += '<button type="button" class="pcb-btn' + (s.required ? ' solid' : '') + '" data-open="' + slot + '" aria-expanded="' + open + '">' + (open ? 'Đóng' : 'Chọn ' + esc(s.label)) + '</button>';
			}
			html += '</div></div>';
			if (open) {
				const all = D.parts.filter(x => x.slot === slot);
				const shown = S.only ? all.filter(x => !reasons(x).length || (S.sel[slot] && S.sel[slot].id === x.id)) : all;
				html += '<div class="pcb-picker">' + (all.length ? shown.map(x => optRow(x, slot)).join('') : '<p class="pcb-none">Chưa có sản phẩm nào cho linh kiện này.</p>')
					+ (all.length > shown.length ? '<p class="pcb-hidden">Đã ẩn ' + (all.length - shown.length) + ' linh kiện không lắp được. Tắt công tắc phía trên để xem hết.</p>' : '')
					+ '</div>';
			}
		}
		$('pcb-slots').innerHTML = html;
	}

	function renderSummary() {
		const R = S.res;
		if (!R) return;
		$('pcb-total').textContent = vnd(R.total);
		$('pcb-count').textContent = R.count ? R.count + ' linh kiện' : 'Chưa chọn linh kiện nào';
		const { estimate: est, recommended: rec, psu } = R.power;
		const pct = psu ? Math.min(100, Math.round(est * 100 / psu)) : 0;
		$('pcb-meter').style.width = pct + '%';
		$('pcb-meter').style.background = !psu ? '#e9edf3' : (est > psu ? '#d32f2f' : (psu < rec ? '#f5a623' : '#2e7d32'));
		$('pcb-power').textContent = 'Khoảng ' + est + 'W khi chạy nặng' + (psu ? ', nguồn ' + psu + 'W' : '') + '. Nên dùng nguồn từ ' + rec + 'W.';
		const icon = { ok: ['check_circle', 'ok'], warn: ['error', 'warn'], err: ['cancel', 'err'], info: ['info', 'info'] };
		$('pcb-checks').innerHTML = R.checks.length
			? R.checks.map(([lv, t]) => '<div class="pcb-check ' + icon[lv][1] + '"><i class="material-icons-round" aria-hidden="true">' + icon[lv][0] + '</i><span>' + esc(t) + '</span></div>').join('')
			: '<p class="pcb-none">Chọn CPU và mainboard để bắt đầu.</p>';
		$('pcb-bar-total').textContent = vnd(R.total);
		$('pcb-bar-n').textContent = '(' + $('pcb-count').textContent.toLowerCase() + ')';
		$('pcb-bar-err').textContent = R.errors + ' lỗi lắp ráp';
		$('pcb-bar-err').hidden = !R.errors;
		applyBlocks();
	}

	// Lỗi đỏ chặn thêm vào giỏ (máy chủ kiểm lại khi nhận form).
	function applyBlocks() {
		const blocked = S.res && S.res.blocks && S.res.blocks.length;
		const btn = document.querySelector('[data-action="cart"]');
		if (btn) {
			btn.disabled = !S.checked || !!blocked;
			btn.title = blocked ? 'Chưa đặt hàng được: ' + S.res.blocks.join('; ') : '';
		}
		const box = $('pcb-blocked');
		box.hidden = !blocked || !S.res.count;
		box.textContent = 'Cấu hình đang có lỗi lắp ráp, sửa các dòng đỏ trước khi đặt hàng (hoặc gửi tiệm tư vấn).';
		const form = $('pcb-cart-form');
		if (form) form.elements.code.value = code();
	}

	function render() {
		renderSlots();
		document.querySelectorAll('#pcb-presets [data-preset]').forEach(b => b.classList.toggle('on', b.dataset.preset === String(S.preset)));
		const c = code();
		history.replaceState(null, '', c ? '#' + c : location.pathname + location.search);
	}

	// Hỏi máy chủ kiểm tra lại mỗi khi đổi linh kiện; bỏ kết quả cũ về muộn.
	async function evaluate() {
		const seq = ++S.seq;
		S.checked = false;
		const btn = document.querySelector('[data-action="cart"]');
		if (btn) btn.disabled = true;
		$('pcb-summary').classList.add('busy');
		const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
		if (D.manage) headers['X-WP-Nonce'] = D.nonce;
		try {
			const res = await fetch(D.urls.evaluate, { method: 'POST', headers, credentials: 'same-origin', body: JSON.stringify({ items: Object.values(S.sel).map(s => ({ id: s.id, qty: s.qty })) }) });
			if (!res.ok) throw new Error(res.status);
			const data = await res.json();
			if (seq !== S.seq) return;
			S.res = data;
			S.checked = true;
			renderSlots();
			renderSummary();
		} catch (e) {
			if (seq === S.seq) $('pcb-checks').innerHTML = '<div class="pcb-check err"><i class="material-icons-round" aria-hidden="true">wifi_off</i><span>Không kiểm tra được, thử tải lại trang.</span></div>';
		} finally {
			if (seq === S.seq) $('pcb-summary').classList.remove('busy');
		}
	}

	function update() { render(); evaluate(); }

	function renderPresets() {
		$('pcb-presets').innerHTML = D.presets.length
			? D.presets.map(p => '<span class="pcb-chip-w"><button type="button" class="pcb-chip" data-preset="' + p.id + '">' + esc(p.name) + '</button>'
				+ (D.manage ? '<button type="button" class="pcb-chip-x" data-preset-del="' + p.id + '" aria-label="Xóa mẫu ' + esc(p.name) + '">×</button>' : '') + '</span>').join('')
			: '<span class="pcb-none">Chưa có</span>';
	}
	function load(id) {
		const preset = D.presets.find(p => String(p.id) === String(id));
		S.sel = {}; S.preset = preset ? preset.id : null; S.open = null;
		if (preset) Object.entries(preset.items).forEach(([pid, q]) => { const p = byId[pid]; if (p) S.sel[p.slot] = { id: p.id, qty: Math.min(D.slots[p.slot].max, +q || 1) }; });
	}
	function fromHash() {
		const h = decodeURIComponent(location.hash.slice(1));
		if (!/^\d/.test(h)) return false;
		h.split(',').forEach(t => { const [id, q] = t.split('*'); const p = byId[id]; if (p) S.sel[p.slot] = { id: p.id, qty: Math.min(D.slots[p.slot].max, +q || 1) }; });
		return Object.keys(S.sel).length > 0;
	}
	const shareUrl = () => D.urls.page + (code() ? '#' + code() : '');
	const printUrl = () => D.urls.page + (D.urls.page.includes('?') ? '&' : '?') + 'in-cau-hinh=' + encodeURIComponent(code());
	function copy(text, el, label) {
		const done = () => { if (el) { const old = el.textContent; el.textContent = label; setTimeout(() => { el.textContent = old; }, 1800); } };
		if (navigator.clipboard) navigator.clipboard.writeText(text).then(done, done); else done();
	}
	async function presetsApi(url, method, body) {
		const res = await fetch(url, { method, credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-WP-Nonce': D.nonce }, body: body ? JSON.stringify(body) : undefined });
		const data = await res.json().catch(() => ({}));
		if (!res.ok) throw new Error(data.message || 'Không lưu được, thử lại.');
		D.presets = data.presets; renderPresets();
		return data;
	}

	renderPresets();
	if (!fromHash() && D.presets.length) load(D.presets[0].id);

	$('pcb').addEventListener('click', e => {
		const b = e.target.closest('button,a[data-share],a[data-print],a[data-zalo],a[data-save-toggle],a[data-to-summary]');
		if (!b || b.disabled || b.type === 'submit') return;
		if ('zalo' in b.dataset) { copy(shareUrl()); return; }
		if (b.matches('a')) e.preventDefault();
		if (b.dataset.preset) load(b.dataset.preset);
		else if (b.dataset.presetDel) {
			if (!window.confirm('Xóa cấu hình mẫu này?')) return;
			presetsApi(D.urls.presets + '/' + b.dataset.presetDel, 'DELETE').catch(err => window.alert(err.message));
			return;
		}
		else if ('reset' in b.dataset) { S.sel = {}; S.preset = null; S.open = 'cpu'; }
		else if (b.dataset.open) { S.open = S.open === b.dataset.open ? null : b.dataset.open; render(); return; }
		else if (b.dataset.choose) { const p = byId[b.dataset.choose]; S.sel[p.slot] = { id: p.id, qty: S.sel[p.slot] ? S.sel[p.slot].qty : 1 }; S.open = null; S.preset = null; }
		else if (b.dataset.del) { delete S.sel[b.dataset.del]; S.preset = null; }
		else if (b.dataset.q) { const s = S.sel[b.dataset.q]; s.qty = Math.max(1, Math.min(D.slots[b.dataset.q].max, s.qty + +b.dataset.d)); S.preset = null; }
		else if ('share' in b.dataset) { copy(shareUrl(), b.querySelector('span'), 'Đã chép link'); return; }
		else if ('print' in b.dataset) { window.open(printUrl(), '_blank'); return; }
		else if ('toSummary' in b.dataset) { $('pcb-summary').scrollIntoView({ behavior: 'smooth' }); return; }
		else if ('saveToggle' in b.dataset) { const f = $('pcb-save'); f.hidden = !f.hidden; if (!f.hidden) f.elements.name.focus(); return; }
		else return;
		update();
	});
	$('pcb-only').addEventListener('change', e => { S.only = e.target.checked; render(); });
	const save = $('pcb-save');
	if (save) save.addEventListener('submit', async e => {
		e.preventDefault();
		const msg = $('pcb-save-msg');
		try {
			const data = await presetsApi(D.urls.presets, 'POST', { name: save.elements.name.value, code: code() });
			const saved = D.presets.find(p => p.name.toLowerCase() === save.elements.name.value.trim().toLowerCase());
			S.preset = saved ? saved.id : null; render();
			msg.className = 'ok'; msg.textContent = data.message;
		} catch (err) {
			msg.className = 'err'; msg.textContent = err.message;
		}
	});
	$('pcb-cart-form').addEventListener('submit', e => {
		if (!S.checked || (S.res && S.res.blocks.length)) { e.preventDefault(); return; }
		e.target.elements.code.value = code();
	});
	update();
})();
