/* Hộp "Yêu cầu báo giá": mở bằng nút (cả nút ở thanh dưới điện thoại), tự mở khi trang có data-open. */
(() => {
	const dlg = document.getElementById('tmt-quote-dlg');
	if (!dlg) return;
	const open = () => {
		if (dlg.open) return;
		dlg.showModal();
		const name = dlg.querySelector('input[name=name]');
		if (name && !name.value) name.focus();
	};
	document.querySelectorAll('[data-quote-open]').forEach((b) => b.addEventListener('click', open));
	dlg.querySelector('[data-quote-close]')?.addEventListener('click', () => dlg.close());
	dlg.addEventListener('click', (e) => { if (e.target === dlg) dlg.close(); });
	if (dlg.hasAttribute('data-open')) open();
})();
