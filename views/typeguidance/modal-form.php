<?php
/**
 * Модальна форма додавання / редагування керівного органу.
 * Використовується також для режиму перегляду без окремого modal-view.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Dictionaries\TypeGuidance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-typeguidance-form-modal" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
	<div class="fstu-modal fstu-modal--compact" role="dialog" aria-modal="true" aria-labelledby="fstu-typeguidance-form-title">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-typeguidance-form-title"><?php esc_html_e( 'Додавання запису', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-typeguidance-form-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal__body">
			<div id="fstu-typeguidance-form-message" class="fstu-form-message fstu-hidden"></div>

			<form id="fstu-typeguidance-form" class="fstu-app-form" novalidate>
				<input type="hidden" id="fstu-typeguidance-id" name="typeguidance_id" value="0">
				<input type="hidden" id="fstu-typeguidance-mode" value="create">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( \FSTU\Dictionaries\TypeGuidance\TypeGuidance_List::NONCE_ACTION ) ); ?>">
				<div class="fstu-honeypot" aria-hidden="true">
					<label for="fstu-typeguidance-website">Website</label>
					<input type="text" id="fstu-typeguidance-website" name="fstu_website" tabindex="-1" autocomplete="off">
				</div>

				<div class="fstu-form-group">
					<label class="fstu-label fstu-label--required" for="fstu-typeguidance-name"><?php esc_html_e( 'Найменування', 'fstu' ); ?></label>
					<input type="text" class="fstu-input" id="fstu-typeguidance-name" name="typeguidance_name" maxlength="255" required>
				</div>

				<div class="fstu-form-group">
					<label class="fstu-label" for="fstu-typeguidance-number"><?php esc_html_e( '№ статті/сторінки', 'fstu' ); ?></label>
					<input type="text" class="fstu-input" id="fstu-typeguidance-number" name="typeguidance_number" maxlength="50">
				</div>

				<div class="fstu-form-group fstu-typeguidance-order-group fstu-hidden">
					<label class="fstu-label" for="fstu-typeguidance-order"><?php esc_html_e( 'Сортування', 'fstu' ); ?></label>
					<input type="number" class="fstu-input" id="fstu-typeguidance-order" name="typeguidance_order" min="0" step="1">
					<div class="fstu-hint"><?php esc_html_e( 'Службове поле. Не відображається в основній таблиці.', 'fstu' ); ?></div>
				</div>

				<div class="fstu-form-actions">
					<button type="submit" class="fstu-btn fstu-btn--primary" id="fstu-typeguidance-form-submit"><?php esc_html_e( 'Зберегти', 'fstu' ); ?></button>
					<button type="button" class="fstu-btn fstu-btn--secondary" data-close-modal="fstu-typeguidance-form-modal"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
				</div>
			</form>
		</div>
	</div>
</div>

