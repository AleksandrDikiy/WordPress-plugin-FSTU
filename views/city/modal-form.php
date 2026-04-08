<?php
/**
 * Shared-модалка додавання / редагування / перегляду міста.
 *
 * Version:     1.1.0
 * Date_update: 2026-04-07
 *
 * @package FSTU\Dictionaries\City
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="fstu-city-form-modal" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
	<div class="fstu-modal fstu-modal--compact" role="dialog" aria-modal="true" aria-labelledby="fstu-city-form-title">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-city-form-title"><?php esc_html_e( 'Додавання міста', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-city-form-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal__body">
			<div id="fstu-city-form-message" class="fstu-form-message fstu-hidden"></div>
			<form id="fstu-city-form" class="fstu-app-form" novalidate>
				<input type="hidden" id="fstu-city-id" name="city_id" value="0">
				<input type="hidden" id="fstu-city-mode" value="create">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( \FSTU\Dictionaries\City\City_List::NONCE_ACTION ) ); ?>">
				<div class="fstu-honeypot" aria-hidden="true"><input type="text" id="fstu-city-website" name="fstu_website" tabindex="-1" autocomplete="off"></div>

				<div class="fstu-form-group">
					<label class="fstu-label fstu-label--required" for="fstu-city-region-id"><?php esc_html_e( 'Область', 'fstu' ); ?></label>
					<select class="fstu-input" id="fstu-city-region-id" name="region_id" required>
						<option value=""><?php esc_html_e( 'Виберіть область', 'fstu' ); ?></option>
					</select>
				</div>
				<div class="fstu-form-group">
					<label class="fstu-label fstu-label--required" for="fstu-city-name"><?php esc_html_e( 'Найменування', 'fstu' ); ?></label>
					<input type="text" class="fstu-input" id="fstu-city-name" name="city_name" maxlength="255" required>
				</div>
				<div class="fstu-form-group">
					<label class="fstu-label" for="fstu-city-name-eng"><?php esc_html_e( 'Англійською', 'fstu' ); ?></label>
					<input type="text" class="fstu-input" id="fstu-city-name-eng" name="city_name_eng" maxlength="255">
				</div>
				<div class="fstu-form-group">
					<label class="fstu-label" for="fstu-city-order"><?php esc_html_e( 'Сортування', 'fstu' ); ?></label>
					<input type="number" class="fstu-input" id="fstu-city-order" name="city_order" min="0" step="1">
				</div>
				<div class="fstu-form-actions">
					<button type="submit" class="fstu-btn fstu-btn--primary" id="fstu-city-form-submit"><?php esc_html_e( 'Зберегти', 'fstu' ); ?></button>
					<button type="button" class="fstu-btn fstu-btn--secondary" data-close-modal="fstu-city-form-modal"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
				</div>
			</form>
		</div>
	</div>
</div>

