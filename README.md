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
| Trang giỏ hàng, thanh toán | Kích hoạt theme là tự chuyển 2 trang này sang dạng của theme. Nếu WooCommerce tạo lại trang kiểu khối, bảng tin hiện nút "Đổi sang giao diện theme". |
| Phí giao, nhận tại tiệm, miễn phí giao | WooCommerce → Cài đặt → Giao hàng: thêm vùng "Việt Nam" với "Phí cố định" (Giao tận nơi), "Nhận tại cửa hàng" và "Miễn phí giao hàng" (đặt số tiền tối thiểu: web tự hiện thanh "Mua thêm x để được giao miễn phí"). |
| Mã QR chuyển khoản | WooCommerce → Cài đặt → Thanh toán → Chuyển khoản ngân hàng: bật và nhập tài khoản (tài khoản đầu tiên được dùng). Ngân hàng nhận ra theo tên; không nhận ra thì chọn ở Tùy biến → Giỏ hàng & thanh toán. Mã QR có sẵn số tiền + nội dung "DH số đơn", hiện ở trang cảm ơn, trang đơn hàng và trang Tra đơn hàng. |
| Tra đơn hàng | Trang "Tra đơn hàng" tự tạo khi kích hoạt theme (mã ngắn `[tmt_tra_don]`). Khách nhập số đơn + số điện thoại, không cần tài khoản. Thêm trang này vào menu chân trang "Hỗ trợ". |
| Banner khuyến mãi trang sản phẩm | Tùy biến → Banner khuyến mãi (trang sản phẩm): 5 dạng (khung quà tặng, dải đếm ngược, nhãn, ảnh, mã giảm giá), 6 vị trí, áp dụng cho tất cả / danh mục / sản phẩm, có ngày bắt đầu, kết thúc. |

## Có gì trong bản này

- Đầu trang: thanh trên cùng 3 vùng, ô tìm 3 kiểu, menu chính với khung xổ theo danh mục + hãng, mega menu, 15 hiệu ứng rê chuột và 9 kiểu đánh dấu trang đang mở.
- Trang chủ theo section: slider (2 kiểu), dải cam kết, danh mục nổi bật, khuyến mãi đếm ngược, sản phẩm mới, bài viết.
- Danh sách sản phẩm: bộ lọc theo hãng, giá, còn hàng, mọi thuộc tính; sắp xếp; trang lọc không cho Google lập chỉ mục.
- Thẻ sản phẩm: nhãn thông số trên ảnh, vài dòng thông số, % giảm, "N loại", nút Thêm vào giỏ (AJAX), Hỏi giá, Zalo.
- Trang chi tiết: bộ ảnh vuốt + phóng to + toàn màn hình, chọn loại bằng nút, Mua ngay, ưu đãi, bảng thông số, sản phẩm cùng loại, thanh mua dính đáy trên điện thoại.
- Chân trang, nút liên hệ nổi (Hotline, Zalo, Messenger), banner dọc hai bên, hiệu ứng thẻ và con trỏ chuột.
- Trang bài viết, trang tĩnh, tìm kiếm, 404.
- Giỏ hàng + thanh toán trên một trang: sửa số lượng, mã giảm giá, mua kèm, chọn giao tận nơi / nhận tại tiệm, thanh tổng xanh đậm, thanh đặt hàng dính đáy trên điện thoại. Chỉ hỏi họ tên, số điện thoại, email (không bắt buộc), địa chỉ.
- Mã QR chuyển khoản (VietQR, tạo ngay trên máy chủ, không gọi dịch vụ ngoài), nút chép số tài khoản / số tiền / nội dung.
- Tra đơn hàng bằng số đơn + số điện thoại.
- Banner khuyến mãi trang chi tiết sản phẩm.

Các đợt sau: Mẹo vặt, Giải pháp, Phần mềm, form yêu cầu báo giá, dữ liệu mẫu.

## Plugin TMT Shop Tools: Xây dựng cấu hình máy tính

Khách tự chọn CPU, mainboard, RAM, SSD, card đồ họa, nguồn, vỏ, tản nhiệt, màn hình... Web tự kiểm tra lắp chung được không (22 luật như app quản lý: socket, loại RAM, số khe RAM / M.2 / SATA, khổ main với vỏ, chiều dài card, chiều cao tản nhiệt, công suất nguồn, đầu cấp nguồn card, BIOS, kênh đôi RAM...), ẩn linh kiện không lắp được, báo tổng tiền, rồi **Thêm cả bộ vào giỏ**, gửi Zalo, chép link, in A5 có mã QR.

Cài đặt:

1. Nén thư mục `tmt-shop-tools` thành `tmt-shop-tools.zip`, vào **Plugin → Cài mới → Tải plugin lên**, bấm **Kích hoạt**. Plugin tự tạo trang **Xây dựng cấu hình** (`/xay-dung-cau-hinh/`) và theme tự hiện nút "Xây dựng cấu hình" ở trang chủ, giỏ trống. Thêm trang này vào menu chính.
2. Vào **WooCommerce → Lên cấu hình**:
   - Mục 1: chọn danh mục cho từng linh kiện (CPU, Mainboard...). Lần đầu plugin tự đoán theo tên danh mục, kiểm tra rồi bấm Lưu.
   - Mục 3: bấm **Tạo thuộc tính còn thiếu**. Plugin tạo các thuộc tính chung (Socket, Loại RAM, Khổ mainboard, Công suất tiêu thụ (W)...) kèm giá trị gợi ý. Đã có thuộc tính tên khác (vd. `pa_loai-ram`) thì plugin dùng luôn.
3. Sửa từng linh kiện → tab **Thuộc tính**, thêm các thông số ở bảng mục 3 (cột "Gắn cho" nói thông số nào cho loại hàng nào). Số ghi số (65 hoặc 65W đều được), Có / Không ghi "Có" hoặc "Không". Thiếu thông số thì web không đoán, chỉ nhắc khách hỏi tiệm.
4. Cấu hình mẫu (vd. "Văn phòng 10 triệu"): đăng nhập quản trị, mở trang Xây dựng cấu hình, chọn linh kiện rồi bấm **Lưu làm mẫu**. Bấm × cạnh tên mẫu để xóa.

Cấu hình có lỗi đỏ (không lắp được) không vào giỏ được, máy chủ kiểm tra lại. Món chưa có giá, tạm hết hoặc không đủ số lượng không vào giỏ (hoặc vào phần còn), web báo rõ để tiệm gọi xác nhận.

## Giấy phép

Code theme: GPL v2 trở lên. Thư viện kèm theo: Bootstrap 5.3.3 (MIT), Swiper 11.2.10 (MIT), Font Awesome Free 7.3.1 (icon CC BY 4.0, font SIL OFL 1.1, code MIT), QR Code Generator for PHP của Kazuhiko Arase (MIT, `tmt-shop/inc/vendor/`). Giấy phép từng thư viện nằm trong `tmt-shop/assets/vendor/`.
