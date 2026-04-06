<?php
/**
 * Панель фільтрів модуля "Довідник осередків ФСТУ".
 *
 * Version:     1.1.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Dictionaries\Units
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$regions    = isset( $regions ) && is_array( $regions ) ? $regions : [];
$unit_types = isset( $unit_types ) && is_array( $unit_types ) ? $unit_types : [];
?>

<div class="fstu-filter-bar">
	<!-- Рядок фільтрів -->
	<div class="fstu-filter-row">
		<!-- Регіон -->
		<div class="fstu-filter-item fstu-filter-item--medium">
			<label class="fstu-units-hidden" for="fstu-units-filter-region"><?php esc_html_e( 'Фільтр за регіоном', 'fstu' ); ?></label>
			<select id="fstu-units-filter-region" class="fstu-select" aria-label="<?php esc_attr_e( 'Фільтр за регіоном', 'fstu' ); ?>">
				<option value="0"><?php esc_html_e( 'Усі регіони', 'fstu' ); ?></option>
				<?php foreach ( $regions as $region ) : ?>
					<option value="<?php echo absint( $region['Region_ID'] ?? 0 ); ?>">
						<?php echo esc_html( $region['Region_Name'] ?? '' ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<!-- Тип осередка -->
		<div class="fstu-filter-item fstu-filter-item--medium">
			<label class="fstu-units-hidden" for="fstu-units-filter-type"><?php esc_html_e( 'Фільтр за типом осередка', 'fstu' ); ?></label>
			<select id="fstu-units-filter-type" class="fstu-select" aria-label="<?php esc_attr_e( 'Фільтр за типом осередка', 'fstu' ); ?>">
				<option value="0"><?php esc_html_e( 'Усі типи', 'fstu' ); ?></option>
				<?php foreach ( $unit_types as $type ) : ?>
					<option value="<?php echo absint( $type['UnitType_ID'] ?? 0 ); ?>">
						<?php echo esc_html( $type['UnitType_Name'] ?? '' ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
</div>

