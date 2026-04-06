<?php
/**
 * Модальна форма додавання / редагування комісії або колегії.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Dictionaries\Commission
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-commission-form-modal" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
	<div class="fstu-modal fstu-modal--compact" role="dialog" aria-modal="true" aria-labelledby="fstu-commission-form-title">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-commission-form-title"><?php esc_html_e( 'Додавання запису', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-commission-form-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal__body">
			<div id="fstu-commission-form-message" class="fstu-form-message fstu-hidden"></div>

			<form id="fstu-commission-form" class="fstu-app-form" novalidate>
				<input type="hidden" id="fstu-commission-id" name="commission_id" value="0">
				<div class="fstu-honeypot" aria-hidden="true">
					<label for="fstu-commission-website">Website</label>
					<input type="text" id="fstu-commission-website" name="fstu_website" tabindex="-1" autocomplete="off">
				</div>

				<div class="fstu-form-group">
					<label class="fstu-label fstu-label--required" for="fstu-commission-name"><?php esc_html_e( 'Найменування', 'fstu' ); ?></label>
					<input type="text" class="fstu-input" id="fstu-commission-name" name="commission_name" maxlength="255" required>
				</div>

				<div class="fstu-form-group">
					<label class="fstu-label" for="fstu-commission-emailgooglegroup"><?php esc_html_e( 'Google Group', 'fstu' ); ?></label>
					<input type="email" class="fstu-input" id="fstu-commission-emailgooglegroup" name="commission_emailgooglegroup" maxlength="255">
					<div class="fstu-hint"><?php esc_html_e( 'Поле не є обов’язковим.', 'fstu' ); ?></div>
				</div>

				<div class="fstu-form-group">
					<label class="fstu-label" for="fstu-commission-number"><?php esc_html_e( '№ статті/сторінки', 'fstu' ); ?></label>
					<input type="text" class="fstu-input" id="fstu-commission-number" name="commission_number" maxlength="50">
				</div>

				<div class="fstu-form-actions">
					<button type="submit" class="fstu-btn fstu-btn--primary" id="fstu-commission-form-submit"><?php esc_html_e( 'Зберегти', 'fstu' ); ?></button>
					<button type="button" class="fstu-btn fstu-btn--secondary" data-close-modal="fstu-commission-form-modal"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
				</div>
			</form>
		</div>
	</div>
</div>

