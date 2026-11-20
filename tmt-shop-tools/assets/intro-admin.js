/* Khung "Giới thiệu nâng cao" trong trang sửa sản phẩm: thêm / xóa / đổi thứ tự khối và dòng, chọn ảnh từ Thư viện. */
(() => {
	const box = document.getElementById('tmt-intro-ed');
	if (!box) return;
	let seq = Date.now();
	const blocks = box.querySelector('.tmt-in-blocks');
	box.addEventListener('click', (e) => {
		const t = e.target.closest('button');
		if (!t) return;
		const block = t.closest('.tmt-in-block');
		if (t.matches('[data-in-add]')) {
			const tpl = box.querySelector(`template[data-in-tpl="${t.dataset.inAdd}"]`);
			blocks.insertAdjacentHTML('beforeend', tpl.innerHTML.replace(/__b__/g, 'n' + (seq++)));
			const added = blocks.lastElementChild;
			added.scrollIntoView({ block: 'nearest' });
			added.querySelector('.tmt-in-body input:not([type=hidden]), .tmt-in-body textarea')?.focus();
		} else if (t.matches('[data-in-del]') && block) {
			if (window.confirm('Xóa khối này?')) block.remove();
		} else if (t.matches('[data-in-up]') && block?.previousElementSibling) {
			block.parentNode.insertBefore(block, block.previousElementSibling);
		} else if (t.matches('[data-in-down]') && block?.nextElementSibling) {
			block.parentNode.insertBefore(block.nextElementSibling, block);
		} else if (t.matches('[data-in-add-row]')) {
			const list = t.closest('[data-in-list]');
			const html = list.querySelector(':scope > template').innerHTML.replace(/__r__/g, 'n' + (seq++));
			list.querySelector('.tmt-in-rows').insertAdjacentHTML('beforeend', html);
			list.querySelector('.tmt-in-rows').lastElementChild.querySelector('input')?.focus();
		} else if (t.matches('[data-in-del-row]')) {
			t.closest('.tmt-in-row')?.remove();
		} else if (t.matches('[data-in-img]')) {
			const wrap = t.closest('.tmt-in-img');
			const frame = wp.media({ title: 'Chọn ảnh', library: { type: 'image' }, multiple: false });
			frame.on('select', () => {
				const a = frame.state().get('selection').first().toJSON();
				wrap.querySelector('input').value = a.id;
				const img = wrap.querySelector('img');
				img.src = a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url;
				img.hidden = false;
			});
			frame.open();
		} else if (t.matches('[data-in-img-clear]')) {
			const wrap = t.closest('.tmt-in-img');
			wrap.querySelector('input').value = '';
			wrap.querySelector('img').hidden = true;
		}
	});
})();
