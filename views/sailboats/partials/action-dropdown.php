<?php
/**
 * Partial-шаблон dropdown-дій для таблиці реєстру суден.
 *
 * Version:     1.1.0
 * Date_update: 2026-04-07
 *
 * @package FSTU\Modules\UserFstu\Sailboats
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sailboat_id      = isset( $sailboat_id ) ? (int) $sailboat_id : 0;
$dropdown_actions = isset( $dropdown_actions ) && is_array( $dropdown_actions ) ? $dropdown_actions : [];
?>

<div class="fstu-sailboats-dropdown">
	<button type="button" class="fstu-sailboats-dropdown__toggle" aria-expanded="false" data-sailboat-id="<?php echo esc_attr( (string) $sailboat_id ); ?>" title="<?php esc_attr_e( 'Меню дій', 'fstu' ); ?>">▼</button>
	<div class="fstu-sailboats-dropdown__menu">
		<?php foreach ( $dropdown_actions as $action ) : ?>
			<?php
			$label      = isset( $action['label'] ) ? (string) $action['label'] : '';
			$class_name = isset( $action['class'] ) ? (string) $action['class'] : '';
			$is_danger  = ! empty( $action['danger'] );
			?>
			<?php if ( '' !== $label && '' !== $class_name ) : ?>
				<button type="button" class="fstu-sailboats-dropdown__item <?php echo esc_attr( $class_name . ( $is_danger ? ' fstu-sailboats-dropdown__item--danger' : '' ) ); ?>" data-sailboat-id="<?php echo esc_attr( (string) $sailboat_id ); ?>"><?php echo esc_html( $label ); ?></button>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
</div>

