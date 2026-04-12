<?php
/**
 * Модальна форма додавання / редагування виду походу.
 *
 * Version:     1.1.0
 * Date_update: 2026-04-12
 *
 * @package FSTU\Dictionaries\TourType
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-tourtype-form-modal" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
	<div class="fstu-modal fstu-modal--compact fstu-modal--tourtype-form" role="dialog" aria-modal="true" aria-labelledby="fstu-tourtype-form-title">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-tourtype-form-title"><?php esc_html_e( 'Додавання запису', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-tourtype-form-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal__body">
			<div id="fstu-tourtype-form-message" class="fstu-form-message fstu-hidden"></div>

			<form id="fstu-tourtype-form" class="fstu-app-form" novalidate>
				<input type="hidden" id="fstu-tourtype-id" name="tourtype_id" value="0">
				<div class="fstu-honeypot" aria-hidden="true">
					<label for="fstu-tourtype-website">Website</label>
					<input type="text" id="fstu-tourtype-website" name="fstu_website" tabindex="-1" autocomplete="off">
				</div>

				<div class="fstu-form-grid">
					<div class="fstu-form-group fstu-form-group--wide">
						<label class="fstu-label fstu-label--required" for="fstu-tourtype-category"><?php esc_html_e( 'Категорія', 'fstu' ); ?></label>
						<select class="fstu-select fstu-select--full" id="fstu-tourtype-category" name="hour_categories_id" required></select>
					</div>

					<div class="fstu-form-group fstu-form-group--wide">
						<label class="fstu-label fstu-label--required" for="fstu-tourtype-name"><?php esc_html_e( 'Найменування', 'fstu' ); ?></label>
						<input type="text" class="fstu-input" id="fstu-tourtype-name" name="tourtype_name" maxlength="255" required>
					</div>

					<div class="fstu-form-group">
						<label class="fstu-label fstu-label--required" for="fstu-tourtype-code"><?php esc_html_e( 'Код походу', 'fstu' ); ?></label>
						<input type="text" class="fstu-input" id="fstu-tourtype-code" name="tourtype_code" maxlength="20" required>
					</div>

					<div class="fstu-form-group">
						<label class="fstu-label fstu-label--required" for="fstu-tourtype-day"><?php esc_html_e( 'Мінімальна тривалість (дні)', 'fstu' ); ?></label>
						<input type="number" class="fstu-input" id="fstu-tourtype-day" name="tourtype_day" min="0" step="1" inputmode="numeric" required>
					</div>

					<div class="fstu-form-group fstu-form-group--wide fstu-hidden" id="fstu-tourtype-order-group">
						<label class="fstu-label" for="fstu-tourtype-order"><?php esc_html_e( 'Порядок', 'fstu' ); ?></label>
						<input type="number" class="fstu-input" id="fstu-tourtype-order" name="tourtype_order" min="0" step="1" inputmode="numeric">
						<div class="fstu-hint"><?php esc_html_e( 'Поле доступне лише якщо в БД присутня колонка сортування. Значення 0 означає автоматичне визначення порядку.', 'fstu' ); ?></div>
					</div>
				</div>
			</form>
		</div>
		<div class="fstu-modal__footer">
			<button type="submit" form="fstu-tourtype-form" class="fstu-btn fstu-btn--primary" id="fstu-tourtype-form-submit"><?php esc_html_e( 'Зберегти', 'fstu' ); ?></button>
			<button type="button" class="fstu-btn fstu-btn--secondary" data-close-modal="fstu-tourtype-form-modal"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
		</div>
	</div>
</div>

