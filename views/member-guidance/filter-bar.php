<?php
/**
 * Шаблон filter-bar модуля Member Guidance.
 * Містить лише фільтр по типу керівного органу без дублювання пошуку.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Dictionaries\MemberGuidance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$typeguidance_options    = isset( $typeguidance_options ) && is_array( $typeguidance_options ) ? $typeguidance_options : [];
$initial_typeguidance_id = isset( $initial_typeguidance_id ) ? (int) $initial_typeguidance_id : 1;
?>

<div class="fstu-member-guidance-filter-bar">
	<div class="fstu-member-guidance-filter-bar__group">
		<label class="fstu-label" for="fstu-member-guidance-typeguidance-filter"><?php esc_html_e( 'Тип керівного органу', 'fstu' ); ?></label>
		<select id="fstu-member-guidance-typeguidance-filter" class="fstu-select" aria-label="<?php esc_attr_e( 'Фільтр за типом керівного органу', 'fstu' ); ?>">
			<?php if ( empty( $typeguidance_options ) ) : ?>
				<option value="0"><?php esc_html_e( 'Немає доступних типів', 'fstu' ); ?></option>
			<?php else : ?>
				<?php foreach ( $typeguidance_options as $option ) : ?>
					<?php
					$option_id   = (int) ( $option['id'] ?? 0 );
					$option_name = (string) ( $option['name'] ?? '' );
					?>
					<option value="<?php echo esc_attr( (string) $option_id ); ?>" <?php selected( $initial_typeguidance_id, $option_id ); ?>><?php echo esc_html( $option_name ); ?></option>
				<?php endforeach; ?>
			<?php endif; ?>
		</select>
	</div>
</div>

