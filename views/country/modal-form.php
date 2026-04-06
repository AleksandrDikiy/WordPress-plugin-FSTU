<?php
/**
 * Shared-модалка додавання / редагування / перегляду країни.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Dictionaries\Country
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-country-form-modal" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
	<div class="fstu-modal fstu-modal--compact" role="dialog" aria-modal="true" aria-labelledby="fstu-country-form-title">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-country-form-title"><?php esc_html_e( 'Додавання країни', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-country-form-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal__body">
			<div id="fstu-country-form-message" class="fstu-form-message fstu-hidden"></div>

			<form id="fstu-country-form" class="fstu-app-form" novalidate>
				<input type="hidden" id="fstu-country-id" name="country_id" value="0">
				<input type="hidden" id="fstu-country-mode" value="create">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( \FSTU\Dictionaries\Country\Country_List::NONCE_ACTION ) ); ?>">
				<div class="fstu-honeypot" aria-hidden="true">
					<label for="fstu-country-website">Website</label>
					<input type="text" id="fstu-country-website" name="fstu_website" tabindex="-1" autocomplete="off">
				</div>

				<div class="fstu-form-group">
					<label class="fstu-label fstu-label--required" for="fstu-country-name"><?php esc_html_e( 'Найменування', 'fstu' ); ?></label>
					<input type="text" class="fstu-input" id="fstu-country-name" name="country_name" maxlength="255" required>
				</div>

				<div class="fstu-form-group">
					<label class="fstu-label" for="fstu-country-name-eng"><?php esc_html_e( 'Англійською', 'fstu' ); ?></label>
					<input type="text" class="fstu-input" id="fstu-country-name-eng" name="country_name_eng" maxlength="255">
				</div>

				<div class="fstu-form-group">
					<label class="fstu-label" for="fstu-country-order"><?php esc_html_e( 'Сортування', 'fstu' ); ?></label>
					<input type="number" class="fstu-input" id="fstu-country-order" name="country_order" min="0" step="1">
					<div class="fstu-hint"><?php esc_html_e( 'Службове поле. Не відображається в основній таблиці.', 'fstu' ); ?></div>
				</div>

				<div class="fstu-form-actions">
					<button type="submit" class="fstu-btn fstu-btn--primary" id="fstu-country-form-submit"><?php esc_html_e( 'Зберегти', 'fstu' ); ?></button>
					<button type="button" class="fstu-btn fstu-btn--secondary" data-close-modal="fstu-country-form-modal"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
				</div>
			</form>
		</div>
	</div>
</div>

