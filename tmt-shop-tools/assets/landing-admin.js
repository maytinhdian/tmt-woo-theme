/* Khung "Nội dung trang": thêm / xóa / đổi thứ tự dòng, chọn ảnh từ Thư viện. */
(() => {
	const box = document.getElementById('tmt-lp');
	if (!box) return;
	let seq = Date.now();
	box.addEventListener('click', (e) => {
		const t = e.target;
		const row = t.closest('.tmt-lp-row');
		if (t.matches('[data-lp-add]')) {
			const list = t.closest('[data-lp-list]');
			const html = list.querySelector('template').innerHTML.replace(/__i__/g, 'n' + (seq++));
			list.querySelector('.tmt-lp-rows').insertAdjacentHTML('beforeend', html);
			list.querySelector('.tmt-lp-rows').lastElementChild.querySelector('input:not([type=hidden]), textarea')?.focus();
		} else if (t.matches('[data-lp-del]') && row) {
			row.remove();
		} else if (t.matches('[data-lp-up]') && row && row.previousElementSibling) {
			row.parentNode.insertBefore(row, row.previousElementSibling);
		} else if (t.matches('[data-lp-down]') && row && row.nextElementSibling) {
			row.parentNode.insertBefore(row.nextElementSibling, row);
		} else if (t.matches('[data-lp-img]')) {
			const wrap = t.closest('.tmt-lp-img');
			const frame = wp.media({ title: 'Chọn ảnh', library: { type: 'image' }, multiple: false });
			frame.on('select', () => {
				const a = frame.state().get('selection').first().toJSON();
				wrap.querySelector('input').value = a.id;
				const img = wrap.querySelector('img');
				img.src = (a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url);
				img.hidden = false;
			});
			frame.open();
		} else if (t.matches('[data-lp-img-clear]')) {
			const wrap = t.closest('.tmt-lp-img');
			wrap.querySelector('input').value = '';
			wrap.querySelector('img').hidden = true;
		}
	});
})();
