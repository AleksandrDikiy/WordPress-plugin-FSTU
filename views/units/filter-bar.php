<?php
/**
 * Панель фільтрів модуля "Довідник осередків ФСТУ".
 *
 * Version:     1.0.0
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
	<!-- Рядок пошуку -->
	<div class="fstu-filter-row fstu-filter-row--search">
		<div class="fstu-filter-item fstu-filter-item--wide">
			<div class="fstu-search-wrap">
				<span class="fstu-search-icon" aria-hidden="true">🔍</span>
				<input
					type="text"
					id="fstu-units-search"
					class="fstu-search-input"
					placeholder="<?php esc_attr_e( 'Пошук за назвою...', 'fstu' ); ?>"
					autocomplete="off"
				>
				<button
					type="button"
					class="fstu-search-clear fstu-units-hidden"
					id="fstu-units-search-clear"
					title="<?php esc_attr_e( 'Очистити пошук', 'fstu' ); ?>"
				>
					✕
				</button>
			</div>
		</div>
	</div>

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

		<!-- Записів на сторінку -->
		<div class="fstu-filter-item fstu-filter-item--perpage">
			<label class="fstu-units-hidden" for="fstu-units-per-page"><?php esc_html_e( 'Кількість записів на сторінці', 'fstu' ); ?></label>
			<select id="fstu-units-per-page" class="fstu-select" aria-label="<?php esc_attr_e( 'Кількість записів на сторінці', 'fstu' ); ?>">
				<option value="10">10</option>
				<option value="15" selected>15</option>
				<option value="25">25</option>
				<option value="50">50</option>
			</select>
		</div>
	</div>
</div>

