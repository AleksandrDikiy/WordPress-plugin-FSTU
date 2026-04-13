<?php
/**
 * Модальна форма додавання / редагування спортивного розряду.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Dictionaries\SportsCategories
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-sportscategories-form-modal" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
	<div class="fstu-modal fstu-modal--compact fstu-modal--sportscategories-form" role="dialog" aria-modal="true" aria-labelledby="fstu-sportscategories-form-title">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-sportscategories-form-title"><?php esc_html_e( 'Додавання запису', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close fstu-modal-close" data-close-modal="fstu-sportscategories-form-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal__body">
			<div id="fstu-sportscategories-form-message" class="fstu-form-message fstu-hidden"></div>

			<form id="fstu-sportscategories-form" novalidate>
				<input type="hidden" id="fstu-sportscategories-id" name="sportscategory_id" value="0">
				<div class="fstu-honeypot" aria-hidden="true">
					<label for="fstu-sportscategories-website">Website</label>
					<input type="text" id="fstu-sportscategories-website" name="fstu_website" tabindex="-1" autocomplete="off">
				</div>

				<div class="fstu-form-grid fstu-form-grid--single">
					<div class="fstu-form-group fstu-form-group--wide">
						<label class="fstu-label fstu-label--required" for="fstu-sportscategories-name"><?php esc_html_e( 'Найменування', 'fstu' ); ?></label>
						<input type="text" class="fstu-input" id="fstu-sportscategories-name" name="sportscategory_name" maxlength="50" required>
					</div>

					<div class="fstu-form-group fstu-form-group--wide">
						<label class="fstu-label" for="fstu-sportscategories-order"><?php esc_html_e( 'Сортування', 'fstu' ); ?></label>
						<input type="number" class="fstu-input" id="fstu-sportscategories-order" name="sportscategory_order" min="0" step="1" inputmode="numeric">
						<div class="fstu-hint"><?php esc_html_e( 'Якщо значення не вказане або дорівнює 0, порядок буде визначено автоматично або збережеться поточне службове значення.', 'fstu' ); ?></div>
					</div>
				</div>
			</form>
		</div>
		<div class="fstu-modal__footer">
			<button type="submit" form="fstu-sportscategories-form" class="fstu-btn fstu-btn--primary fstu-btn--save" id="fstu-sportscategories-form-submit"><?php esc_html_e( 'Зберегти', 'fstu' ); ?></button>
			<button type="button" class="fstu-btn fstu-btn--secondary fstu-btn--cancel" data-close-modal="fstu-sportscategories-form-modal"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
		</div>
	</div>
</div>

