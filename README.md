# TMT Shop – theme WooCommerce

Theme WordPress cho web bán hàng máy tính, linh kiện, camera: giao diện giống web bán hàng của app quản lý TMT, chủ web chỉnh mọi thứ trong **Giao diện → Tùy biến** (không cần sửa code).

## Cần có

- WordPress 6.5 trở lên, PHP 7.4 trở lên.
- Plugin **WooCommerce** 9.6 trở lên (đã thử với 10.3).

## Cài đặt

1. Nén thư mục `tmt-shop` thành `tmt-shop.zip`.
2. Vào **Giao diện → Giao diện → Thêm mới → Tải giao diện lên**, chọn file zip, bấm **Kích hoạt**.
3. Vào **WooCommerce → Cài đặt**: tiền tệ VND, số chữ số thập phân 0, dấu phân cách hàng nghìn là dấu chấm.
4. Vào **Giao diện → Tùy biến → TMT Shop** nhập tên tiệm, số điện thoại, Zalo, địa chỉ, màu, logo.
5. Nên bật đường dẫn đẹp: **Cài đặt → Đường dẫn tĩnh → Tên bài viết**.

## Thiết lập nhanh

| Việc | Ở đâu |
|---|---|
| Menu chính | Giao diện → Menu, gán vị trí "Menu chính". Chưa gán thì tự lấy danh mục sản phẩm cấp 1. |
| Menu chân trang | Vị trí "Chân trang: Mua hàng" và "Chân trang: Hỗ trợ". |
| Icon danh mục | Sản phẩm → Danh mục → sửa danh mục → ô "Icon" (tên icon Material, ví dụ `memory`). |
| Hãng | Sản phẩm → Thương hiệu (Brands của WooCommerce). |
| Thông số trên thẻ (socket, RAM...) | Sản phẩm → Thuộc tính: tạo thuộc tính chung, gắn vào sản phẩm và tick "Hiển thị trên trang sản phẩm". Thuộc tính chung nào cũng thành bộ lọc. |
| Bảo hành | Sửa sản phẩm → tab Chung → "Bảo hành (tháng)". |
| Sản phẩm chưa có giá | Để trống giá: web hiện "Liên hệ báo giá" và nút "Hỏi giá" qua Zalo. |
| Trang chủ | Tùy biến → TMT Shop → Trang chủ: bật/tắt, đổi thứ tự section, ảnh slider, banner, khuyến mãi đếm ngược. |
| Bộ lọc danh sách | Tùy biến → Trang danh sách: thanh ngang, cột trái hoặc cột phải. |

## Có gì trong bản này (đợt 1)

- Đầu trang: thanh trên cùng 3 vùng, ô tìm 3 kiểu, menu chính với khung xổ theo danh mục + hãng, mega menu, 15 hiệu ứng rê chuột và 9 kiểu đánh dấu trang đang mở.
- Trang chủ theo section: slider (2 kiểu), dải cam kết, danh mục nổi bật, khuyến mãi đếm ngược, sản phẩm mới, bài viết.
- Danh sách sản phẩm: bộ lọc theo hãng, giá, còn hàng, mọi thuộc tính; sắp xếp; trang lọc không cho Google lập chỉ mục.
- Thẻ sản phẩm: nhãn thông số trên ảnh, vài dòng thông số, % giảm, "N loại", nút Thêm vào giỏ (AJAX), Hỏi giá, Zalo.
- Trang chi tiết: bộ ảnh vuốt + phóng to + toàn màn hình, chọn loại bằng nút, Mua ngay, ưu đãi, bảng thông số, sản phẩm cùng loại, thanh mua dính đáy trên điện thoại.
- Chân trang, nút liên hệ nổi (Hotline, Zalo, Messenger), banner dọc hai bên, hiệu ứng thẻ và con trỏ chuột.
- Trang bài viết, trang tĩnh, tìm kiếm, 404.

Các đợt sau: giỏ hàng + thanh toán có mã QR chuyển khoản, tra đơn, banner khuyến mãi trang sản phẩm; plugin **TMT Shop Tools** với trang Lên cấu hình máy tính; Mẹo vặt, Giải pháp, Phần mềm, form yêu cầu báo giá, dữ liệu mẫu.

## Giấy phép

Code theme: GPL v2 trở lên. Thư viện kèm theo: Bootstrap 5.3.3 (MIT), Swiper 11.2.10 (MIT), Font Awesome Free 7.3.1 (icon CC BY 4.0, font SIL OFL 1.1, code MIT). Giấy phép từng thư viện nằm trong `tmt-shop/assets/vendor/`.
