<?php
/**
 * Khe linh kiện và thông số dùng cho luật tương thích (giống module PcBuilder của app quản lý).
 *
 * @package TMT_Shop_Tools
 */

defined( 'ABSPATH' ) || exit;

/**
 * Khe linh kiện theo thứ tự hiển thị.
 * guess = từ khóa nhận ra danh mục WooCommerce khi chủ tiệm chưa gán (so với slug danh mục).
 *
 * @return array<string, array{label: string, icon: string, required: bool, max: int, guess: list<string>}>
 */
function tmt_pcb_slots() {
	return array(
		'cpu'      => array( 'label' => 'CPU', 'icon' => 'memory', 'required' => true, 'max' => 1, 'guess' => array( 'cpu', 'bo-xu-ly', 'vi-xu-ly' ) ),
		'main'     => array( 'label' => 'Mainboard', 'icon' => 'developer_board', 'required' => true, 'max' => 1, 'guess' => array( 'mainboard', 'bo-mach-chu', 'main' ) ),
		'ram'      => array( 'label' => 'RAM', 'icon' => 'sd_card', 'required' => true, 'max' => 4, 'guess' => array( 'ram' ) ),
		'ssd'      => array( 'label' => 'Ổ SSD', 'icon' => 'storage', 'required' => true, 'max' => 4, 'guess' => array( 'ssd', 'o-ssd' ) ),
		'hdd'      => array( 'label' => 'Ổ HDD', 'icon' => 'album', 'required' => false, 'max' => 4, 'guess' => array( 'hdd', 'o-hdd', 'o-cung' ) ),
		'vga'      => array( 'label' => 'Card đồ họa', 'icon' => 'videogame_asset', 'required' => false, 'max' => 1, 'guess' => array( 'vga', 'card-do-hoa', 'card-man-hinh' ) ),
		'psu'      => array( 'label' => 'Nguồn', 'icon' => 'power', 'required' => true, 'max' => 1, 'guess' => array( 'nguon', 'psu', 'nguon-may-tinh' ) ),
		'case'     => array( 'label' => 'Vỏ case', 'icon' => 'computer', 'required' => true, 'max' => 1, 'guess' => array( 'vo-case', 'case', 'vo-may-tinh' ) ),
		'cooler'   => array( 'label' => 'Tản nhiệt CPU', 'icon' => 'air', 'required' => false, 'max' => 1, 'guess' => array( 'tan-nhiet', 'tan-nhiet-cpu' ) ),
		'monitor'  => array( 'label' => 'Màn hình', 'icon' => 'desktop_windows', 'required' => false, 'max' => 3, 'guess' => array( 'man-hinh' ) ),
		'keyboard' => array( 'label' => 'Bàn phím', 'icon' => 'keyboard', 'required' => false, 'max' => 1, 'guess' => array( 'ban-phim' ) ),
		'mouse'    => array( 'label' => 'Chuột', 'icon' => 'mouse', 'required' => false, 'max' => 1, 'guess' => array( 'chuot' ) ),
	);
}

/**
 * Thông số luật tương thích đọc: key => [tên, kiểu, giá trị gợi ý]. Mỗi thông số là một thuộc tính WooCommerce
 * có slug = key đổi "_" thành "-" (vd. ram_type → pa_ram-type). Nút "Tạo thuộc tính" ở trang cài đặt tạo sẵn.
 * Kiểu: select (chọn), number (số, đọc số đầu tiên: "65W" → 65), bool (Có / Không), text (nhiều giá trị).
 *
 * @return array<string, array{0: string, 1: string, 2: list<string>}>
 */
function tmt_pcb_specs() {
	return array(
		'socket'              => array( 'Socket', 'select', array( 'LGA1700', 'LGA1851', 'LGA1200', 'AM4', 'AM5' ) ),
		'cooler_sockets'      => array( 'Socket hỗ trợ', 'text', array( 'LGA1700', 'LGA1851', 'LGA1200', 'AM4', 'AM5' ) ),
		'chipset'             => array( 'Chipset', 'text', array() ),
		'cpu_generation'      => array( 'Đời CPU', 'select', array( 'Intel Core 11', 'Intel Core 12', 'Intel Core 13', 'Intel Core 14', 'Intel Core Ultra 2', 'AMD Ryzen 5000', 'AMD Ryzen 7000', 'AMD Ryzen 8000', 'AMD Ryzen 9000' ) ),
		'ram_type'            => array( 'Loại RAM', 'select', array( 'DDR3', 'DDR4', 'DDR5' ) ),
		'ram_slots'           => array( 'Số khe RAM', 'number', array( '2', '4' ) ),
		'ram_sticks'          => array( 'Số thanh trong bộ', 'number', array( '1', '2' ) ),
		'ram_speed'           => array( 'Bus RAM', 'number', array() ),
		'max_ram_speed'       => array( 'Bus RAM tối đa', 'number', array() ),
		'm2_slots'            => array( 'Số khe M.2', 'number', array( '1', '2', '3', '4' ) ),
		'sata_ports'          => array( 'Số cổng SATA', 'number', array( '2', '4', '6' ) ),
		'storage_interface'   => array( 'Chuẩn kết nối ổ', 'select', array( 'NVMe', 'SATA' ) ),
		'form_factor'         => array( 'Khổ mainboard', 'select', array( 'E-ATX', 'ATX', 'mATX', 'ITX' ) ),
		'tdp'                 => array( 'Công suất tiêu thụ (W)', 'number', array() ),
		'igpu'                => array( 'Có nhân đồ họa', 'bool', array( 'Có', 'Không' ) ),
		'cooler_included'     => array( 'Kèm quạt tản nhiệt', 'bool', array( 'Có', 'Không' ) ),
		'gpu_length'          => array( 'Chiều dài card (mm)', 'number', array() ),
		'gpu_power_connector' => array( 'Đầu cấp nguồn card', 'select', array( 'Không cần', '6 pin', '8 pin', '6+8 pin', '2x8 pin', '3x8 pin', '12VHPWR' ) ),
		'psu_recommended'     => array( 'Nguồn khuyên dùng (W)', 'number', array() ),
		'psu_watt'            => array( 'Công suất nguồn (W)', 'number', array() ),
		'psu_pcie_8pin'       => array( 'Số đầu PCIe 8 pin', 'number', array( '1', '2', '3', '4' ) ),
		'psu_12vhpwr'         => array( 'Có đầu 12VHPWR', 'bool', array( 'Có', 'Không' ) ),
		'max_gpu_length'      => array( 'Card đồ họa dài tối đa (mm)', 'number', array() ),
		'max_cooler_height'   => array( 'Tản nhiệt cao tối đa (mm)', 'number', array() ),
		'cooler_height'       => array( 'Chiều cao tản nhiệt (mm)', 'number', array() ),
	);
}

/** Slug thuộc tính WooCommerce của một thông số (không có tiền tố pa_). */
function tmt_pcb_attr_slug( $key ) {
	return str_replace( '_', '-', $key );
}

/**
 * Tên thuộc tính khác hay gặp cho cùng thông số (shop đã tạo trước khi cài plugin), đọc khi không có slug chuẩn.
 *
 * @return array<string, list<string>>
 */
function tmt_pcb_spec_aliases() {
	return (array) apply_filters(
		'tmt_pcb_spec_aliases',
		array(
			'ram_type'          => array( 'loai-ram', 'chuan-ram' ),
			'form_factor'       => array( 'kho-mainboard', 'kich-thuoc-mainboard', 'form-factor' ),
			'storage_interface' => array( 'chuan-ket-noi', 'giao-tiep' ),
			'psu_watt'          => array( 'cong-suat-nguon' ),
			'tdp'               => array( 'cong-suat-tieu-thu' ),
			'ram_speed'         => array( 'bus-ram' ),
		)
	);
}
