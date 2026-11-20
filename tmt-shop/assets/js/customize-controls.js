/* Ô riêng của bảng tùy biến TMT Shop: danh sách lặp (repeater) và section bật/tắt + thứ tự (sortable). */
(function ($, api) {
	'use strict';
	const icons = window.tmtIcons || {};
	const parse = (s, fallback) => { try { const v = JSON.parse(s); return Array.isArray(v) ? v : fallback; } catch (e) { return fallback; } };
	const esc = (s) => String(s == null ? '' : s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

	function repeater(root) {
		const input = root.querySelector('.tmt-rep-value');
		const fields = JSON.parse(root.dataset.fields);
		const max = Number(root.dataset.max) || 10;
		const box = root.querySelector('.tmt-rep-rows');
		const add = root.querySelector('.tmt-rep-add');
		let rows = parse(input.value, []);

		const save = () => { input.value = JSON.stringify(rows); $(input).trigger('change'); add.disabled = rows.length >= max; };
		const fieldHtml = (name, f, val, i) => {
			const [type, label, choices] = f;
			const id = `tmt-${name}-${i}-${Math.random().toString(36).slice(2, 7)}`;
			if (type === 'checkbox') return `<label class="tmt-rep-check"><input type="checkbox" data-k="${name}" ${val ? 'checked' : ''}> ${esc(label)}</label>`;
			if (type === 'select') return `<label for="${id}">${esc(label)}</label><select id="${id}" data-k="${name}">${Object.entries(choices).map(([k, l]) => `<option value="${esc(k)}" ${k === val ? 'selected' : ''}>${esc(l)}</option>`).join('')}</select>`;
			if (type === 'icon') {
				const opts = Object.entries(icons).map(([k, l]) => `<option value="${esc(k)}" ${k === val ? 'selected' : ''}>${esc(l)}</option>`).join('');
				const custom = val && !icons[val] ? `<option value="${esc(val)}" selected>${esc(val)}</option>` : '';
				return `<label for="${id}">${esc(label)}</label><div class="tmt-rep-icon"><i class="${esc(val)}" aria-hidden="true"></i><select id="${id}" data-k="${name}"><option value="">(không icon)</option>${custom}${opts}</select></div>`;
			}
			if (type === 'image') return `<span class="tmt-rep-lbl">${esc(label)}</span><div class="tmt-rep-img">${val ? `<img src="${esc(val)}" alt="">` : ''}<input type="hidden" data-k="${name}" value="${esc(val)}"><button type="button" class="button tmt-rep-pick">${val ? 'Đổi ảnh' : 'Chọn ảnh'}</button>${val ? '<button type="button" class="button-link tmt-rep-clear">Bỏ</button>' : ''}</div>`;
			return `<label for="${id}">${esc(label)}</label><input type="text" id="${id}" data-k="${name}" value="${esc(val)}">`;
		};
		const title = (row, i) => row.text || row.title || row.alt || row.link || `Mục ${i + 1}`;
		const render = () => {
			box.innerHTML = rows.map((row, i) => `
				<div class="tmt-rep-row" data-i="${i}">
					<div class="tmt-rep-head"><b>${esc(title(row, i))}</b>
						<span><button type="button" class="tmt-rep-up" aria-label="Lên" ${i === 0 ? 'disabled' : ''}>▲</button><button type="button" class="tmt-rep-down" aria-label="Xuống" ${i === rows.length - 1 ? 'disabled' : ''}>▼</button><button type="button" class="tmt-rep-del" aria-label="Xóa">✕</button></span></div>
					<div class="tmt-rep-body">${Object.entries(fields).map(([n, f]) => `<div class="tmt-rep-f">${fieldHtml(n, f, row[n], i)}</div>`).join('')}</div>
				</div>`).join('');
			add.disabled = rows.length >= max;
		};
		box.addEventListener('input', (e) => {
			const k = e.target.dataset.k; if (!k) return;
			const i = Number(e.target.closest('.tmt-rep-row').dataset.i);
			rows[i][k] = e.target.type === 'checkbox' ? e.target.checked : e.target.value;
			if (e.target.closest('.tmt-rep-icon')) e.target.previousElementSibling.className = e.target.value;
			e.target.closest('.tmt-rep-row').querySelector('.tmt-rep-head b').textContent = title(rows[i], i);
			save();
		});
		box.addEventListener('click', (e) => {
			const row = e.target.closest('.tmt-rep-row'); if (!row) return;
			const i = Number(row.dataset.i);
			if (e.target.closest('.tmt-rep-head b')) { row.classList.toggle('open'); return; }
			if (e.target.matches('.tmt-rep-del')) { rows.splice(i, 1); render(); save(); }
			if (e.target.matches('.tmt-rep-up') && i > 0) { [rows[i - 1], rows[i]] = [rows[i], rows[i - 1]]; render(); save(); }
			if (e.target.matches('.tmt-rep-down') && i < rows.length - 1) { [rows[i + 1], rows[i]] = [rows[i], rows[i + 1]]; render(); save(); }
			if (e.target.matches('.tmt-rep-clear')) { const k = e.target.parentElement.querySelector('[data-k]').dataset.k; rows[i][k] = ''; render(); save(); box.querySelector(`[data-i="${i}"]`).classList.add('open'); }
			if (e.target.matches('.tmt-rep-pick')) {
				const k = e.target.parentElement.querySelector('[data-k]').dataset.k;
				const frame = wp.media({ title: 'Chọn ảnh', library: { type: 'image' }, multiple: false, button: { text: 'Dùng ảnh này' } });
				frame.on('select', () => { rows[i][k] = frame.state().get('selection').first().toJSON().url; render(); save(); box.querySelector(`[data-i="${i}"]`).classList.add('open'); });
				frame.open();
			}
		});
		add.addEventListener('click', () => {
			if (rows.length >= max) return;
			const row = {};
			Object.entries(fields).forEach(([n, f]) => { row[n] = f[0] === 'checkbox' ? false : (f[0] === 'select' ? Object.keys(f[2])[0] : ''); });
			rows.push(row); render(); save();
			box.lastElementChild.classList.add('open');
		});
		render();
	}

	function sortable(root) {
		const input = root.querySelector('.tmt-sort-value');
		const choices = JSON.parse(root.dataset.choices);
		const list = root.querySelector('.tmt-sort-list');
		let on = parse(input.value, []);
		// Thứ tự: mục đang bật theo thứ tự đã lưu, rồi mục tắt.
		let order = [...on, ...Object.keys(choices).filter((k) => !on.includes(k))];
		const save = () => { input.value = JSON.stringify(order.filter((k) => on.includes(k))); $(input).trigger('change'); };
		const render = () => {
			list.innerHTML = order.map((k, i) => `<li data-k="${k}" class="${on.includes(k) ? '' : 'off'}">
				<label><input type="checkbox" ${on.includes(k) ? 'checked' : ''}> ${esc(choices[k])}</label>
				<span><button type="button" class="up" ${i === 0 ? 'disabled' : ''} aria-label="Lên">▲</button><button type="button" class="down" ${i === order.length - 1 ? 'disabled' : ''} aria-label="Xuống">▼</button></span></li>`).join('');
		};
		list.addEventListener('change', (e) => {
			const k = e.target.closest('li').dataset.k;
			on = e.target.checked ? [...on, k] : on.filter((x) => x !== k);
			on = order.filter((x) => on.includes(x));
			render(); save();
		});
		list.addEventListener('click', (e) => {
			const li = e.target.closest('li'); if (!li || !e.target.matches('button')) return;
			const i = order.indexOf(li.dataset.k), j = e.target.matches('.up') ? i - 1 : i + 1;
			if (j < 0 || j >= order.length) return;
			[order[i], order[j]] = [order[j], order[i]];
			render(); save();
		});
		render();
	}

	api.bind('ready', () => {
		document.querySelectorAll('.tmt-rep').forEach(repeater);
		document.querySelectorAll('.tmt-sort').forEach(sortable);
	});
})(jQuery, wp.customize);
