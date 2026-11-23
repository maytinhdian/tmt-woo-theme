/* Trang /sua-may: nút Hẹn tư vấn chọn sẵn ô trong form + khối tính thử (port từ app). */
(function () {
    var box = document.querySelector('[data-rin-calc]');
    // Nút "Hẹn tư vấn": chọn sẵn ô "Tư vấn mua máy" trong form.
    document.querySelectorAll('[data-rin-kind]').forEach(function (a) {
        a.addEventListener('click', function () {
            var r = document.querySelector('#khao-sat input[name=kind][value="' + a.dataset.rinKind + '"]');
            if (r) r.checked = true;
        });
    });
    if (!box) return;
    var d = box.dataset, n = 1;
    var fee = +d.inspect, travel = +d.travel, limit = +d.limit, waive = d.waive === '1';
    var fmt = function (v) { return v.toLocaleString('vi-VN') + '₫'; };
    var val = function (name) { return box.querySelector('input[name=' + name + ']:checked').value; };
    var TITLES = {
        ok: 'Máy không hư: trả máy, chỉ tính phí kiểm tra',
        small: 'Hư dưới hạn mức: thợ thay luôn',
        big: 'Hư trên hạn mức: chờ anh/chị duyệt',
        no: 'Không sửa: trả máy như lúc nhận'
    };
    function render() {
        var where = val('rin-where'), res = val('rin-res');
        var fixed = res === 'small' || res === 'big';
        var quote = res === 'ok' ? 'Bỏ qua vì máy không hư.' : res === 'small' ? 'Dưới hạn mức ' + fmt(limit) + ': gửi để biết, không chờ.' : res === 'big' ? 'Trên hạn mức: anh/chị bấm Đồng ý mới sửa.' : 'Anh/chị bấm Không sửa.';
        var steps = [
            ['Nhắn tình trạng', '', ''],
            [where === 'noi' ? 'Thợ tới nhận máy' : 'Nhận máy tại tiệm', where === 'noi' ? 'Báo phí kiểm tra + phí di chuyển.' : 'Báo phí kiểm tra.', ''],
            ['Kiểm tra', res === 'ok' ? 'Không thấy lỗi phần cứng.' : 'Tìm ra bệnh, chụp ảnh lên phiếu.', ''],
            ['Báo giá', quote, res === 'ok' ? 'skip' : (res === 'small' ? '' : 'wait')],
            ['Sửa', fixed ? 'Thay linh kiện, test lại.' : 'Bỏ qua.', fixed ? '' : 'skip'],
            ['Trả máy', 'Ký nhận, trả tiền mặt hoặc quét QR.', 'done']
        ];
        box.querySelector('[data-title]').textContent = TITLES[res];
        var path = box.querySelector('[data-path]');
        path.innerHTML = '';
        steps.forEach(function (s) {
            var li = document.createElement('li');
            if (s[2]) li.className = s[2];
            var b = document.createElement('b'); b.textContent = s[0]; li.appendChild(b);
            if (s[1]) { var p = document.createElement('span'); p.textContent = s[1]; li.appendChild(p); }
            path.appendChild(li);
        });
        var rows = [], total = 0;
        if (fixed) {
            var cost = res === 'small' ? +d.small : +d.big;
            rows.push(['', 'Tiền sửa (ví dụ' + (n > 1 ? ', 1 máy hư' : '') + ')', fmt(cost)]); total += cost;
            rows.push(['off', 'Phí kiểm tra máy được sửa (' + (waive ? 'miễn' : 'trừ vào tiền sửa') + ')', fmt(fee)]);
            if (n > 1) { rows.push(['', 'Phí kiểm tra ' + (n - 1) + ' máy còn lại', fmt(fee * (n - 1))]); total += fee * (n - 1); }
        } else {
            rows.push(['', 'Phí kiểm tra ' + n + ' máy', fmt(fee * n)]); total += fee * n;
        }
        if (where === 'noi') { rows.push(['', 'Phí di chuyển (1 chuyến, từ)', fmt(travel)]); total += travel; }
        var bill = box.querySelector('[data-bill]');
        bill.innerHTML = '';
        rows.concat([['tot', 'Anh/chị trả', fmt(total)]]).forEach(function (r) {
            var row = document.createElement('div');
            row.className = 'ln' + (r[0] ? ' ' + r[0] : '');
            var a = document.createElement('span'); a.textContent = r[1];
            var b = document.createElement('span'); b.textContent = r[2];
            row.appendChild(a); row.appendChild(b); bill.appendChild(row);
        });
    }
    box.addEventListener('change', render);
    box.querySelectorAll('[data-step]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            n = Math.min(9, Math.max(1, n + +btn.dataset.step));
            box.querySelector('[data-n]').textContent = n;
            render();
        });
    });
    render();
})();
