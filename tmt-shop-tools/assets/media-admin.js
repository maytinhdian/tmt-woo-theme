/* Trang sửa sản phẩm: kéo thả nhiều ảnh, dán Ctrl+V hoặc bấm chọn để thêm thẳng vào "Bộ ảnh sản phẩm" của WooCommerce
 * (tải lên qua async-upload.php giống khung chọn ảnh, máy chủ tự thu nhỏ + đặt tên theo sản phẩm). Chưa có ảnh đại diện
 * thì ảnh đầu tiên làm ảnh đại diện. Kéo ảnh trong bộ ảnh để đổi thứ tự (sẵn của WooCommerce). */
(function ($) {
	'use strict';
	var box = document.getElementById('woocommerce-product-images');
	if (!box || !window.tmtMedia) return;
	var list = box.querySelector('ul.product_images');
	var field = document.getElementById('product_image_gallery');
	var inside = box.querySelector('.inside');
	if (!list || !field || !inside) return;

	var drop = document.createElement('div');
	drop.className = 'tmt-drop';
	drop.tabIndex = 0;
	drop.innerHTML = '<b>Kéo thả ảnh vào đây</b>hoặc bấm để chọn, hoặc Ctrl+V dán ảnh<span class="st" aria-live="polite"></span>';
	var pick = document.createElement('input');
	pick.type = 'file'; pick.accept = 'image/*'; pick.multiple = true; pick.hidden = true;
	inside.appendChild(drop);
	inside.appendChild(pick);
	var st = drop.querySelector('.st');

	function say(t, err) { st.textContent = t; st.className = 'st' + (err ? ' err' : ''); }

	function hasThumb() {
		var v = document.getElementById('_thumbnail_id');
		return v && +v.value > 0;
	}

	function addToGallery(a) {
		if (!hasThumb() && window.wp && wp.media && wp.media.featuredImage) {
			wp.media.featuredImage.set(a.id);
			return;
		}
		var ids = field.value ? field.value.split(',') : [];
		ids.push(String(a.id));
		field.value = ids.join(',');
		var src = (a.sizes && (a.sizes.thumbnail || a.sizes.medium || a.sizes.full) || {}).url || a.url;
		var li = document.createElement('li');
		li.className = 'image';
		li.setAttribute('data-attachment_id', a.id);
		li.innerHTML = '<img src="" alt=""><ul class="actions"><li><a href="#" class="delete tips" data-tip="Xóa ảnh">Xóa</a></li></ul>';
		li.querySelector('img').src = src;
		list.appendChild(li);
	}

	function upload(file) {
		var fd = new FormData();
		fd.append('async-upload', file, file.name || ('anh-dan-' + Date.now() + '.png'));
		fd.append('name', file.name || 'anh-dan.png');
		fd.append('action', 'upload-attachment');
		fd.append('_wpnonce', tmtMedia.nonce);
		fd.append('post_id', tmtMedia.post);
		return fetch(tmtMedia.url, { method: 'POST', body: fd, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (j) {
				if (!j || !j.success) throw new Error(j && j.data && j.data.message || 'Không tải được ảnh');
				addToGallery(j.data);
			});
	}

	function send(files) {
		files = Array.prototype.filter.call(files || [], function (f) { return /^image\//.test(f.type); });
		if (!files.length) return;
		drop.classList.add('is-busy');
		var done = 0, failed = 0;
		var chain = Promise.resolve();
		files.forEach(function (f) {
			chain = chain.then(function () {
				say('Đang tải ' + (done + failed + 1) + ' / ' + files.length + '...');
				return upload(f).then(function () { done++; }, function (e) { failed++; say(e.message, true); });
			});
		});
		chain.then(function () {
			drop.classList.remove('is-busy');
			if (!failed) say('Đã thêm ' + done + ' ảnh. Bấm Cập nhật để lưu thứ tự.');
			else say('Đã thêm ' + done + ', lỗi ' + failed + ' ảnh.', true);
		});
	}

	drop.addEventListener('click', function () { pick.click(); });
	drop.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); pick.click(); } });
	pick.addEventListener('change', function () { send(pick.files); pick.value = ''; });
	['dragenter', 'dragover'].forEach(function (t) {
		drop.addEventListener(t, function (e) { e.preventDefault(); drop.classList.add('is-over'); });
	});
	['dragleave', 'drop'].forEach(function (t) {
		drop.addEventListener(t, function (e) { e.preventDefault(); drop.classList.remove('is-over'); });
	});
	drop.addEventListener('drop', function (e) { send(e.dataTransfer.files); });
	// Dán ảnh (chụp màn hình, chép ảnh từ web) ở bất kỳ đâu trong trang, trừ lúc đang gõ trong ô chữ / trình soạn thảo.
	document.addEventListener('paste', function (e) {
		var t = e.target;
		if (t && (t.closest('input, textarea, [contenteditable="true"], .mce-content-body') )) return;
		var files = [];
		Array.prototype.forEach.call((e.clipboardData && e.clipboardData.items) || [], function (it) {
			if (it.kind === 'file' && /^image\//.test(it.type)) files.push(it.getAsFile());
		});
		if (files.length) { e.preventDefault(); send(files); }
	});
})(jQuery);
