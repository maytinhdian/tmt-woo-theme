<?php
/**
 * Luật tương thích linh kiện, chép từ CompatibilityChecker + BuildEvaluator của app quản lý (22 luật đã duyệt).
 * Thiếu thông số thì không đoán: báo dòng "info" để khách hỏi tiệm.
 * Mỗi dòng kiểm tra: level (ok/warn/err/info), message, slots (khe liên quan), conflict (hai món không lắp chung được).
 *
 * @package TMT_Shop_Tools
 */

defined( 'ABSPATH' ) || exit;

/** Khổ mainboard từ nhỏ tới lớn. Vỏ ghi khổ lớn nhất nó chứa được. */
const TMT_PCB_FORM_FACTORS = array( 'ITX' => 1, 'MATX' => 2, 'ATX' => 3, 'E-ATX' => 4 );

/** Đời CPU => chipset main thường phải cập nhật BIOS mới nhận. */
const TMT_PCB_BIOS_UPDATE = array(
	'Intel Core 13'  => array( 'H610', 'B660', 'H670', 'Z690' ),
	'Intel Core 14'  => array( 'H610', 'B660', 'H670', 'Z690' ),
	'Intel Core 11'  => array( 'B460', 'H470', 'Z490' ),
	'AMD Ryzen 5000' => array( 'A320', 'B350', 'X370', 'B450', 'X470', 'A520', 'B550', 'X570' ),
	'AMD Ryzen 8000' => array( 'A620', 'B650', 'X670' ),
	'AMD Ryzen 9000' => array( 'A620', 'B650', 'X670' ),
);

/** Đầu cấp nguồn card => số đầu PCIe 8 pin cần. */
const TMT_PCB_PCIE_8PIN = array( 'Không cần' => 0, '6 pin' => 1, '8 pin' => 1, '6+8 pin' => 2, '2x8 pin' => 2, '3x8 pin' => 3 );

/** Điện năng phần còn lại của máy (main, quạt, USB) và mỗi ổ, W. */
const TMT_PCB_BASE_WATT  = 60;
const TMT_PCB_DRIVE_WATT = 5;

function tmt_pcb_check_row( $level, $message, $slots = array(), $conflict = false, $kind = 'compat' ) {
	return array( 'level' => $level, 'message' => $message, 'slots' => $slots, 'conflict' => $conflict, 'kind' => $kind );
}

/** Giá trị chữ của một thông số, null khi chưa nhập. */
function tmt_pcb_text( $part, $key ) {
	$v = isset( $part['specs'][ $key ] ) ? trim( (string) $part['specs'][ $key ] ) : '';
	return '' === $v ? null : $v;
}

/** Số đầu tiên trong thông số ("65 W" → 65), null khi chưa nhập hoặc không có số. */
function tmt_pcb_number( $part, $key ) {
	$v = tmt_pcb_text( $part, $key );
	if ( null === $v || ! preg_match( '/\d+(?:[.,]\d+)?/', $v, $m ) ) {
		return null;
	}
	return (int) round( (float) str_replace( ',', '.', $m[0] ) );
}

/** Thông số Có / Không; null khi chưa nhập. */
function tmt_pcb_flag( $part, $key ) {
	$v = tmt_pcb_text( $part, $key );
	if ( null === $v ) {
		return null;
	}
	return in_array( strtolower( remove_accents( $v ) ), array( '1', 'co', 'yes', 'true', 'x' ), true );
}

function tmt_pcb_part( $build, $slot ) {
	return isset( $build[ $slot ] ) ? $build[ $slot ]['part'] : null;
}

function tmt_pcb_qty( $build, $slot ) {
	return isset( $build[ $slot ] ) ? $build[ $slot ]['qty'] : 0;
}

/** Khổ main viết kiểu nào cũng nhận: "Micro-ATX", "M-ATX", "mATX", "Mini-ITX". */
function tmt_pcb_form_factor( $text ) {
	$t = strtoupper( str_replace( array( ' ', '_' ), '', (string) $text ) );
	$t = str_replace( array( 'MICRO-ATX', 'MICROATX', 'M-ATX', 'UATX' ), 'MATX', $t );
	$t = str_replace( array( 'MINI-ITX', 'MINIITX' ), 'ITX', $t );
	$t = str_replace( 'EATX', 'E-ATX', $t );
	return $t;
}

/** "LGA1700, AM4 / AM5" => ['LGA1700', 'AM4', 'AM5'] */
function tmt_pcb_socket_list( $text ) {
	return array_values( array_filter( array_map( 'trim', array_map( 'strtoupper', preg_split( '/[,;\/|\s]+/', (string) $text ) ) ) ) );
}

/**
 * So một thông số của hai món. Thiếu thông số thì trả dòng info thay vì đoán.
 */
function tmt_pcb_compare( $a, $b, $key_a, $key_b, $fits, $ok, $bad ) {
	$slots = array( $a['slot'], $b['slot'] );
	$va    = tmt_pcb_text( $a, $key_a );
	$vb    = tmt_pcb_text( $b, $key_b );
	if ( null === $va || null === $vb ) {
		$specs = tmt_pcb_specs();
		list( $who, $key ) = null === $va ? array( $a['name'], $key_a ) : array( $b['name'], $key_b );
		$label = isset( $specs[ $key ] ) ? mb_strtolower( preg_replace( '/\s*\(.*\)$/', '', $specs[ $key ][0] ) ) : $key;
		return tmt_pcb_check_row( 'info', "Chưa có thông tin {$label} của {$who}, hỏi tiệm để chắc chắn", $slots );
	}
	return $fits( $va, $vb )
		? tmt_pcb_check_row( 'ok', $ok( $va, $vb ), $slots )
		: tmt_pcb_check_row( 'err', $bad( $va, $vb ), $slots, true );
}

/**
 * Công suất ước tính khi chạy nặng và công suất nguồn nên dùng (dư 40%, làm tròn 50W, không dưới mức hãng card khuyên).
 *
 * @return array{estimate: int, recommended: int}
 */
function tmt_pcb_power( array $build ) {
	$cpu = tmt_pcb_part( $build, 'cpu' );
	$vga = tmt_pcb_part( $build, 'vga' );
	$est = TMT_PCB_BASE_WATT
		+ ( $cpu ? (int) tmt_pcb_number( $cpu, 'tdp' ) : 0 )
		+ ( $vga ? (int) tmt_pcb_number( $vga, 'tdp' ) : 0 )
		+ TMT_PCB_DRIVE_WATT * ( tmt_pcb_qty( $build, 'ssd' ) + tmt_pcb_qty( $build, 'hdd' ) );
	$rec = max( (int) ceil( $est * 1.4 / 50 ) * 50, $vga ? (int) tmt_pcb_number( $vga, 'psu_recommended' ) : 0 );
	return array( 'estimate' => $est, 'recommended' => $rec );
}

/** @return list<array<string, mixed>> các dòng kiểm tra của cấu hình */
function tmt_pcb_check( array $build ) {
	$out    = array();
	$slots  = tmt_pcb_slots();
	$cpu    = tmt_pcb_part( $build, 'cpu' );
	$main   = tmt_pcb_part( $build, 'main' );
	$ram    = tmt_pcb_part( $build, 'ram' );
	$ssd    = tmt_pcb_part( $build, 'ssd' );
	$vga    = tmt_pcb_part( $build, 'vga' );
	$psu    = tmt_pcb_part( $build, 'psu' );
	$case   = tmt_pcb_part( $build, 'case' );
	$cooler = tmt_pcb_part( $build, 'cooler' );

	$missing = array();
	foreach ( $slots as $key => $s ) {
		if ( $s['required'] && ! isset( $build[ $key ] ) ) {
			$missing[] = $s['label'];
		}
	}
	if ( $missing ) {
		$out[] = tmt_pcb_check_row( 'warn', 'Còn thiếu: ' . implode( ', ', $missing ) . '. Máy cần đủ các món này mới chạy được', array(), false, 'missing' );
	}

	if ( $cpu && $main ) {
		$out[] = tmt_pcb_compare(
			$cpu, $main, 'socket', 'socket',
			function ( $a, $b ) { return 0 === strcasecmp( $a, $b ); },
			function ( $a ) { return "CPU và mainboard cùng socket {$a}"; },
			function ( $a, $b ) { return "CPU socket {$a} không lắp được mainboard socket {$b}"; }
		);
	}

	if ( $ram && $main ) {
		$out[] = tmt_pcb_compare(
			$ram, $main, 'ram_type', 'ram_type',
			function ( $a, $b ) { return 0 === strcasecmp( $a, $b ); },
			function ( $a ) { return "RAM {$a} đúng loại mainboard"; },
			function ( $a, $b ) { return "Mainboard nhận {$b}, RAM đang chọn là {$a}"; }
		);
		$ram_slots = tmt_pcb_number( $main, 'ram_slots' );
		$sticks    = ( tmt_pcb_number( $ram, 'ram_sticks' ) ? tmt_pcb_number( $ram, 'ram_sticks' ) : 1 ) * tmt_pcb_qty( $build, 'ram' );
		if ( null !== $ram_slots && $sticks > $ram_slots ) {
			$out[] = tmt_pcb_check_row( 'err', "Cần {$sticks} khe RAM, mainboard chỉ có {$ram_slots} khe", array( 'ram', 'main' ), true );
		}
	}

	if ( $ssd && $main && 0 === strcasecmp( (string) tmt_pcb_text( $ssd, 'storage_interface' ), 'NVMe' ) ) {
		$m2    = tmt_pcb_number( $main, 'm2_slots' );
		$count = tmt_pcb_qty( $build, 'ssd' );
		if ( null !== $m2 && $count > $m2 ) {
			$out[] = tmt_pcb_check_row( 'err', "{$count} ổ NVMe nhưng mainboard chỉ có {$m2} khe M.2", array( 'ssd', 'main' ), true );
		}
	}

	if ( $case && $main ) {
		$out[] = tmt_pcb_compare(
			$main, $case, 'form_factor', 'form_factor',
			function ( $a, $b ) {
				$a = tmt_pcb_form_factor( $a );
				$b = tmt_pcb_form_factor( $b );
				return isset( TMT_PCB_FORM_FACTORS[ $a ], TMT_PCB_FORM_FACTORS[ $b ] ) && TMT_PCB_FORM_FACTORS[ $a ] <= TMT_PCB_FORM_FACTORS[ $b ];
			},
			function ( $a, $b ) { return "Vỏ {$b} lắp vừa mainboard {$a}"; },
			function ( $a, $b ) { return "Vỏ chỉ chứa main tới {$b}, không lắp được mainboard {$a}"; }
		);
	}

	if ( $case && $vga ) {
		$out[] = tmt_pcb_compare(
			$vga, $case, 'gpu_length', 'max_gpu_length',
			function ( $a, $b ) { return (int) $a <= (int) $b; },
			function ( $a, $b ) { return 'Card đồ họa ' . (int) $a . 'mm vừa vỏ (tối đa ' . (int) $b . 'mm)'; },
			function ( $a, $b ) { return 'Card đồ họa dài ' . (int) $a . 'mm, vỏ chỉ chứa ' . (int) $b . 'mm'; }
		);
	}

	if ( $cooler && $cpu ) {
		$out[] = tmt_pcb_compare(
			$cpu, $cooler, 'socket', 'cooler_sockets',
			function ( $a, $b ) { return in_array( strtoupper( $a ), tmt_pcb_socket_list( $b ), true ); },
			function ( $a ) { return "Tản nhiệt hỗ trợ socket {$a}"; },
			function ( $a, $b ) { return "Tản nhiệt không hỗ trợ socket {$a} (chỉ {$b})"; }
		);
		$cpu_tdp    = tmt_pcb_number( $cpu, 'tdp' );
		$cooler_tdp = tmt_pcb_number( $cooler, 'tdp' );
		if ( $cpu_tdp && $cooler_tdp && $cooler_tdp < $cpu_tdp ) {
			$out[] = tmt_pcb_check_row( 'warn', "Tản nhiệt chịu {$cooler_tdp}W, CPU tỏa tới {$cpu_tdp}W: máy dễ nóng khi chạy nặng", array( 'cooler', 'cpu' ) );
		}
	}

	if ( $cooler && $case ) {
		$height = tmt_pcb_number( $cooler, 'cooler_height' );
		$max    = tmt_pcb_number( $case, 'max_cooler_height' );
		if ( $height && $max ) {
			$out[] = $height <= $max
				? tmt_pcb_check_row( 'ok', "Tản nhiệt cao {$height}mm vừa vỏ (tối đa {$max}mm)", array( 'cooler', 'case' ) )
				: tmt_pcb_check_row( 'err', "Tản nhiệt cao {$height}mm, vỏ chỉ chứa {$max}mm", array( 'cooler', 'case' ), true );
		}
	}

	if ( $cpu && ! $vga && false === tmt_pcb_flag( $cpu, 'igpu' ) ) {
		$out[] = tmt_pcb_check_row( 'err', "{$cpu['name']} không có nhân đồ họa, cần thêm card đồ họa mới lên hình", array( 'cpu', 'vga' ) );
	}

	if ( $cpu && ! $cooler && false === tmt_pcb_flag( $cpu, 'cooler_included' ) ) {
		$out[] = tmt_pcb_check_row( 'warn', "{$cpu['name']} không kèm quạt, nên chọn tản nhiệt", array( 'cpu', 'cooler' ) );
	}

	// BIOS: main đời cũ thường phải cập nhật BIOS mới nhận CPU đời mới.
	$generation = $cpu ? tmt_pcb_text( $cpu, 'cpu_generation' ) : null;
	$chipset    = $main ? tmt_pcb_text( $main, 'chipset' ) : null;
	if ( $generation && $chipset && isset( TMT_PCB_BIOS_UPDATE[ $generation ] ) ) {
		foreach ( TMT_PCB_BIOS_UPDATE[ $generation ] as $old ) {
			if ( false !== stripos( $chipset, $old ) ) {
				$out[] = tmt_pcb_check_row( 'warn', "{$cpu['name']} trên main chipset {$chipset}: có thể phải cập nhật BIOS trước khi lắp, tiệm kiểm tra giúp khi ráp", array( 'cpu', 'main' ) );
				break;
			}
		}
	}

	// Số ổ SATA (SSD SATA + HDD) so với số cổng SATA của main.
	$ports = $main ? tmt_pcb_number( $main, 'sata_ports' ) : null;
	if ( null !== $ports ) {
		$sata_ssd = $ssd && 0 === strcasecmp( (string) tmt_pcb_text( $ssd, 'storage_interface' ), 'SATA' ) ? tmt_pcb_qty( $build, 'ssd' ) : 0;
		$drives   = $sata_ssd + tmt_pcb_qty( $build, 'hdd' );
		if ( $drives > $ports ) {
			$out[] = tmt_pcb_check_row( 'warn', "{$drives} ổ SATA nhưng mainboard chỉ có {$ports} cổng SATA", array( 'ssd', 'hdd', 'main' ) );
		}
	}

	// Bus RAM cao hơn mức main hỗ trợ thì RAM tự chạy chậm lại.
	if ( $ram && $main ) {
		$speed = tmt_pcb_number( $ram, 'ram_speed' );
		$max   = tmt_pcb_number( $main, 'max_ram_speed' );
		if ( $speed && $max && $speed > $max ) {
			$out[] = tmt_pcb_check_row( 'warn', "RAM bus {$speed}MHz sẽ chạy ở {$max}MHz (mức tối đa của mainboard)", array( 'ram', 'main' ) );
		}
	}

	// Đầu cấp nguồn cho card đồ họa.
	$connector = $vga ? tmt_pcb_text( $vga, 'gpu_power_connector' ) : null;
	if ( $connector && $psu ) {
		$have8  = tmt_pcb_number( $psu, 'psu_pcie_8pin' );
		$has12v = tmt_pcb_flag( $psu, 'psu_12vhpwr' );
		if ( '12VHPWR' === strtoupper( $connector ) ) {
			if ( false === $has12v && null !== $have8 ) {
				$out[] = $have8 >= 3
					? tmt_pcb_check_row( 'info', "Card dùng đầu 12VHPWR, nguồn không có: dùng cáp chuyển kèm card ({$have8} đầu 8 pin)", array( 'vga', 'psu' ) )
					: tmt_pcb_check_row( 'warn', "Card cần đầu 12VHPWR, nguồn không có và chỉ có {$have8} đầu 8 pin (cáp chuyển cần ít nhất 3)", array( 'vga', 'psu' ) );
			}
		} else {
			$need = isset( TMT_PCB_PCIE_8PIN[ $connector ] ) ? TMT_PCB_PCIE_8PIN[ $connector ] : 0;
			if ( $need > 0 && null !== $have8 && $have8 < $need ) {
				$out[] = tmt_pcb_check_row( 'warn', "Card cần {$connector} ({$need} đầu), nguồn chỉ có {$have8} đầu PCIe 8 pin", array( 'vga', 'psu' ) );
			}
		}
	}

	// 1 thanh RAM thì chạy kênh đơn, chậm hơn 2 thanh.
	if ( $ram && 1 === ( tmt_pcb_number( $ram, 'ram_sticks' ) ? tmt_pcb_number( $ram, 'ram_sticks' ) : 1 ) * tmt_pcb_qty( $build, 'ram' ) ) {
		$out[] = tmt_pcb_check_row( 'info', 'Đang dùng 1 thanh RAM: nên dùng 2 thanh để chạy kênh đôi, máy nhanh hơn', array( 'ram' ) );
	}

	if ( $psu ) {
		$power = tmt_pcb_power( $build );
		$est   = $power['estimate'];
		$rec   = $power['recommended'];
		$watt  = tmt_pcb_number( $psu, 'psu_watt' );
		$unknown = array();
		foreach ( array( $cpu, $vga ) as $p ) {
			if ( $p && null === tmt_pcb_number( $p, 'tdp' ) ) {
				$unknown[] = $p['name'];
			}
		}
		if ( null === $watt ) {
			$out[] = tmt_pcb_check_row( 'info', "Chưa có công suất của {$psu['name']}, hỏi tiệm để chắc chắn", array( 'psu' ) );
		} elseif ( $watt < $est ) {
			$out[] = tmt_pcb_check_row( 'err', "Nguồn {$watt}W không đủ, cấu hình dùng khoảng {$est}W", array( 'psu', 'cpu', 'vga' ), true );
		} elseif ( $unknown ) {
			$out[] = tmt_pcb_check_row( 'info', 'Chưa có công suất tiêu thụ của ' . implode( ', ', $unknown ) . ', hỏi tiệm để chắc chắn', array( 'psu', 'cpu', 'vga' ) );
		} elseif ( $watt < $rec ) {
			$out[] = tmt_pcb_check_row( 'warn', "Nguồn {$watt}W chạy được nhưng nên dùng từ {$rec}W", array( 'psu', 'cpu', 'vga' ) );
		} else {
			$out[] = tmt_pcb_check_row( 'ok', "Nguồn {$watt}W đủ dư cho cấu hình", array( 'psu' ) );
		}
	}

	return $out;
}

/**
 * Lý do một linh kiện không lắp được với các món đang chọn ở khe khác (để gạch trong danh sách chọn).
 *
 * @return list<string>
 */
function tmt_pcb_reasons( array $candidate, array $build ) {
	$slot  = $candidate['slot'];
	$trial = $build;
	if ( ! isset( $build[ $slot ] ) || $build[ $slot ]['part']['id'] !== $candidate['id'] ) {
		$trial[ $slot ] = array( 'part' => $candidate, 'qty' => isset( $build[ $slot ] ) ? $build[ $slot ]['qty'] : 1 );
	}
	$out = array();
	foreach ( tmt_pcb_check( $trial ) as $c ) {
		if ( $c['conflict'] && in_array( $slot, $c['slots'], true ) ) {
			$out[] = $c['message'];
		}
	}
	return $out;
}

/**
 * Kết quả gửi xuống trình duyệt: dòng kiểm tra, lý do gạch từng linh kiện, công suất, tổng tiền, lý do chặn đặt hàng.
 *
 * @param array<int, int> $items product_id => qty
 * @return array<string, mixed>
 */
function tmt_pcb_evaluate( array $items ) {
	$build  = tmt_pcb_build( $items );
	$checks = tmt_pcb_check( $build );
	foreach ( $build as $i ) {
		$avail = isset( $i['part']['avail'] ) ? $i['part']['avail'] : null;
		if ( ! $i['part']['in_stock'] ) {
			$checks[] = tmt_pcb_check_row( 'warn', $i['part']['name'] . ' tạm hết, tiệm đặt hàng 2-3 ngày', array( $i['part']['slot'] ), false, 'stock' );
		} elseif ( null !== $avail && $avail < $i['qty'] ) {
			$checks[] = tmt_pcb_check_row( 'warn', $i['part']['name'] . ' không đủ ' . $i['qty'] . ' cái, phần thiếu tiệm đặt hàng 2-3 ngày', array( $i['part']['slot'] ), false, 'stock' );
		} elseif ( null === $i['part']['price'] ) {
			$checks[] = tmt_pcb_check_row( 'info', $i['part']['name'] . ' chưa có giá trên web, tiệm báo giá khi gọi xác nhận', array( $i['part']['slot'] ), false, 'stock' );
		}
	}

	$reasons = array();
	foreach ( tmt_pcb_parts() as $part ) {
		$r = tmt_pcb_reasons( $part, $build );
		if ( $r ) {
			$reasons[ $part['id'] ] = $r;
		}
	}

	// Lỗi đỏ (không lắp được) chặn thêm vào giỏ; thiếu món, hết hàng vẫn đặt được (tiệm gọi xác nhận).
	$blocks = array();
	if ( ! $build ) {
		$blocks[] = 'Chưa chọn linh kiện nào';
	}
	$errors = 0;
	foreach ( $checks as $c ) {
		if ( 'err' === $c['level'] ) {
			++$errors;
			$blocks[] = $c['message'];
		}
	}

	$total = 0;
	$count = 0;
	foreach ( $build as $i ) {
		$total += (int) $i['part']['price'] * $i['qty'];
		$count += $i['qty'];
	}
	$psu = tmt_pcb_part( $build, 'psu' );

	return array(
		'items'   => array_values(
			array_map(
				function ( $i ) {
					return array( 'slot' => $i['part']['slot'], 'id' => $i['part']['id'], 'qty' => $i['qty'] );
				},
				$build
			)
		),
		'code'    => tmt_pcb_code( $build ),
		'checks'  => array_map(
			function ( $c ) {
				return array( $c['level'], $c['message'] );
			},
			$checks
		),
		'errors'  => $errors,
		'blocks'  => $blocks,
		'reasons' => (object) $reasons,
		'power'   => tmt_pcb_power( $build ) + array( 'psu' => $psu ? tmt_pcb_number( $psu, 'psu_watt' ) : null ),
		'count'   => $count,
		'total'   => $total,
	);
}
