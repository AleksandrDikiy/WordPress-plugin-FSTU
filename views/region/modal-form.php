<?php
/**
 * Shared-модалка додавання / редагування / перегляду області.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Dictionaries\Region
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-region-form-modal" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
	<div class="fstu-modal fstu-modal--compact" role="dialog" aria-modal="true" aria-labelledby="fstu-region-form-title">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-region-form-title"><?php esc_html_e( 'Додавання області', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-region-form-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal__body">
			<div id="fstu-region-form-message" class="fstu-form-message fstu-hidden"></div>

			<form id="fstu-region-form" class="fstu-app-form" novalidate>
				<input type="hidden" id="fstu-region-id" name="region_id" value="0">
				<input type="hidden" id="fstu-region-mode" value="create">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( \FSTU\Dictionaries\Region\Region_List::NONCE_ACTION ) ); ?>">
				<div class="fstu-honeypot" aria-hidden="true">
					<label for="fstu-region-website">Website</label>
					<input type="text" id="fstu-region-website" name="fstu_website" tabindex="-1" autocomplete="off">
				</div>

				<div class="fstu-form-group">
					<label class="fstu-label fstu-label--required" for="fstu-region-name"><?php esc_html_e( 'Найменування', 'fstu' ); ?></label>
					<input type="text" class="fstu-input" id="fstu-region-name" name="region_name" maxlength="255" required>
				</div>

				<div class="fstu-form-group">
					<label class="fstu-label fstu-label--required" for="fstu-region-code"><?php esc_html_e( 'Код', 'fstu' ); ?></label>
					<input type="text" class="fstu-input" id="fstu-region-code" name="region_code" maxlength="50" required>
				</div>

				<div class="fstu-form-group">
					<label class="fstu-label" for="fstu-region-name-eng"><?php esc_html_e( 'Англійською', 'fstu' ); ?></label>
					<input type="text" class="fstu-input" id="fstu-region-name-eng" name="region_name_eng" maxlength="255">
				</div>

				<div class="fstu-form-group">
					<label class="fstu-label" for="fstu-region-number"><?php esc_html_e( 'Інформаційне поле', 'fstu' ); ?></label>
					<input type="text" class="fstu-input" id="fstu-region-number" name="region_number" maxlength="50">
					<div class="fstu-hint"><?php esc_html_e( 'Інформаційне поле. Не бере участі в унікальності або перевірках залежностей.', 'fstu' ); ?></div>
				</div>

				<div class="fstu-form-group">
					<label class="fstu-label" for="fstu-region-order"><?php esc_html_e( 'Сортування', 'fstu' ); ?></label>
					<input type="number" class="fstu-input" id="fstu-region-order" name="region_order" min="0" step="1">
					<div class="fstu-hint"><?php esc_html_e( 'Службове поле. Не відображається в основній таблиці.', 'fstu' ); ?></div>
				</div>

				<div class="fstu-form-actions">
					<button type="submit" class="fstu-btn fstu-btn--primary" id="fstu-region-form-submit"><?php esc_html_e( 'Зберегти', 'fstu' ); ?></button>
					<button type="button" class="fstu-btn fstu-btn--secondary" data-close-modal="fstu-region-form-modal"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
				</div>
			</form>
		</div>
	</div>
</div>

