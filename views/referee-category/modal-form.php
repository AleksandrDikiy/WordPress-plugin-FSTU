<?php
/**
 * Модальна форма додавання / редагування суддівської категорії.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Dictionaries\RefereeCategory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-referee-category-form-modal" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
	<div class="fstu-modal fstu-modal--compact fstu-modal--referee-category-form" role="dialog" aria-modal="true" aria-labelledby="fstu-referee-category-form-title">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-referee-category-form-title"><?php esc_html_e( 'Додавання запису', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-referee-category-form-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal__body">
			<div id="fstu-referee-category-form-message" class="fstu-form-message fstu-hidden"></div>

			<form id="fstu-referee-category-form" novalidate>
				<input type="hidden" id="fstu-referee-category-id" name="referee_category_id" value="0">
				<div class="fstu-honeypot" aria-hidden="true">
					<label for="fstu-referee-category-website">Website</label>
					<input type="text" id="fstu-referee-category-website" name="fstu_website" tabindex="-1" autocomplete="off">
				</div>

				<div class="fstu-form-grid fstu-form-grid--single">
					<div class="fstu-form-group fstu-form-group--wide">
						<label class="fstu-label fstu-label--required" for="fstu-referee-category-name"><?php esc_html_e( 'Найменування', 'fstu' ); ?></label>
						<input type="text" class="fstu-input" id="fstu-referee-category-name" name="referee_category_name" maxlength="255" required>
					</div>

					<div class="fstu-form-group fstu-form-group--wide">
						<label class="fstu-label" for="fstu-referee-category-order"><?php esc_html_e( 'Сортування', 'fstu' ); ?></label>
						<input type="number" class="fstu-input" id="fstu-referee-category-order" name="referee_category_order" min="0" step="1" inputmode="numeric">
						<div class="fstu-hint"><?php esc_html_e( 'Якщо значення не вказане або дорівнює 0, порядок буде визначено автоматично.', 'fstu' ); ?></div>
					</div>
				</div>
			</form>
		</div>
		<div class="fstu-modal__footer">
			<button type="submit" form="fstu-referee-category-form" class="fstu-btn fstu-btn--primary" id="fstu-referee-category-form-submit"><?php esc_html_e( 'Зберегти', 'fstu' ); ?></button>
			<button type="button" class="fstu-btn fstu-btn--secondary" data-close-modal="fstu-referee-category-form-modal"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
		</div>
	</div>
</div>

