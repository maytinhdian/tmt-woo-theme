/* Xem trước màu ngay trong khung, không tải lại trang. */
(function (api) {
	const map = { tmt_colors_primary: '--sf-blue', tmt_colors_accent: '--sf-yellow', tmt_colors_dark: '--sf-navy' };
	Object.keys(map).forEach((id) => api(id, (setting) => setting.bind((v) => document.documentElement.style.setProperty(map[id], v))));
})(wp.customize);
