<?php
/**
 * Модальна форма додавання / редагування категорії походів.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Dictionaries\HikingCategory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-hikingcategory-form-modal" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
	<div class="fstu-modal fstu-modal--compact fstu-modal--hikingcategory-form" role="dialog" aria-modal="true" aria-labelledby="fstu-hikingcategory-form-title">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-hikingcategory-form-title"><?php esc_html_e( 'Додавання запису', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-hikingcategory-form-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal__body">
			<div id="fstu-hikingcategory-form-message" class="fstu-form-message fstu-hidden"></div>

			<form id="fstu-hikingcategory-form" novalidate>
				<input type="hidden" id="fstu-hikingcategory-id" name="hikingcategory_id" value="0">
				<div class="fstu-honeypot" aria-hidden="true">
					<label for="fstu-hikingcategory-website">Website</label>
					<input type="text" id="fstu-hikingcategory-website" name="fstu_website" tabindex="-1" autocomplete="off">
				</div>

				<div class="fstu-form-grid fstu-form-grid--single">
					<div class="fstu-form-group fstu-form-group--wide">
						<label class="fstu-label fstu-label--required" for="fstu-hikingcategory-name"><?php esc_html_e( 'Найменування', 'fstu' ); ?></label>
						<input type="text" class="fstu-input" id="fstu-hikingcategory-name" name="hikingcategory_name" maxlength="255" required>
					</div>

					<div class="fstu-form-group fstu-form-group--wide">
						<label class="fstu-label" for="fstu-hikingcategory-order"><?php esc_html_e( 'Сортування', 'fstu' ); ?></label>
						<input type="number" class="fstu-input" id="fstu-hikingcategory-order" name="hikingcategory_order" min="0" step="1" inputmode="numeric">
						<div class="fstu-hint"><?php esc_html_e( 'Якщо значення не вказане або дорівнює 0, порядок буде визначено автоматично.', 'fstu' ); ?></div>
					</div>
				</div>
			</form>
		</div>
		<div class="fstu-modal__footer">
			<button type="submit" form="fstu-hikingcategory-form" class="fstu-btn fstu-btn--primary" id="fstu-hikingcategory-form-submit"><?php esc_html_e( 'Зберегти', 'fstu' ); ?></button>
			<button type="button" class="fstu-btn fstu-btn--secondary" data-close-modal="fstu-hikingcategory-form-modal"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
		</div>
	</div>
</div>

