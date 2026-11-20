<?php
/**
 * Hai loại ô riêng của bảng tùy biến: danh sách lặp (repeater) và danh sách bật/tắt + đổi thứ tự (sortable).
 * Giá trị là chuỗi JSON trong một ô ẩn; giao diện dựng bằng assets/js/customize-controls.js.
 *
 * @package TMT_Shop
 */

defined( 'ABSPATH' ) || exit;

/** Danh sách lặp: mỗi dòng nhiều ô (chữ, chọn, icon, ảnh, tick). */
class TMT_Repeater_Control extends WP_Customize_Control {
	public $type      = 'tmt-repeater';
	public $fields    = array();
	public $max       = 10;
	public $add_label = 'Thêm';

	public function render_content() {
		?>
		<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
		<?php if ( $this->description ) : ?><span class="description customize-control-description"><?php echo esc_html( $this->description ); ?></span><?php endif; ?>
		<div class="tmt-rep" data-fields="<?php echo esc_attr( wp_json_encode( $this->fields ) ); ?>" data-max="<?php echo (int) $this->max; ?>">
			<input type="hidden" class="tmt-rep-value" <?php $this->link(); ?> value="<?php echo esc_attr( $this->value() ); ?>">
			<div class="tmt-rep-rows"></div>
			<button type="button" class="button tmt-rep-add">+ <?php echo esc_html( $this->add_label ); ?></button>
		</div>
		<?php
	}
}

/** Bật/tắt từng mục và đổi thứ tự (section trang chủ). */
class TMT_Sortable_Control extends WP_Customize_Control {
	public $type = 'tmt-sortable';

	public function render_content() {
		?>
		<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
		<div class="tmt-sort" data-choices="<?php echo esc_attr( wp_json_encode( $this->choices ) ); ?>">
			<input type="hidden" class="tmt-sort-value" <?php $this->link(); ?> value="<?php echo esc_attr( $this->value() ); ?>">
			<ul class="tmt-sort-list"></ul>
		</div>
		<?php
	}
}
