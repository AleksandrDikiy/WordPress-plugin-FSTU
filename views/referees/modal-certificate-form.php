<?php
/**
 * Модальне вікно форми довідки за суддівство.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-08
 *
 * @package FSTU\Modules\UserFstu\Referees
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-referees-certificate-form-modal" class="fstu-modal-overlay fstu-hidden" role="dialog" aria-modal="true" aria-labelledby="fstu-referees-certificate-form-modal-title">
	<div class="fstu-modal fstu-modal--compact">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-referees-certificate-form-modal-title"><?php esc_html_e( 'Додавання довідки', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-referees-certificate-form-modal">×</button>
		</div>
		<div class="fstu-modal__body">
			<div id="fstu-referees-certificate-form-message" class="fstu-form-message fstu-hidden"></div>
			<form id="fstu-referees-certificate-form" class="fstu-form-grid">
				<input type="hidden" name="user_id" id="fstu-referees-certificate-user-id" value="0">
				<label for="fstu-referees-certificate-website" class="fstu-hidden-field" aria-hidden="true"><?php esc_html_e( 'Службове поле', 'fstu' ); ?></label>
				<input type="text" name="fstu_website" id="fstu-referees-certificate-website" class="fstu-hidden-field" tabindex="-1" autocomplete="off">
				<div class="fstu-form-group">
					<label class="fstu-label" for="fstu-referees-certificate-referee-name"><?php esc_html_e( 'Суддя', 'fstu' ); ?></label>
					<div class="fstu-view-grid__value" id="fstu-referees-certificate-referee-name">—</div>
				</div>
				<div class="fstu-form-group">
					<label class="fstu-label fstu-label--required" for="fstu-referees-certificate-calendar-id"><?php esc_html_e( 'Захід з календаря', 'fstu' ); ?></label>
					<select name="calendar_id" id="fstu-referees-certificate-calendar-id" class="fstu-select">
						<option value="0"><?php esc_html_e( 'Оберіть захід', 'fstu' ); ?></option>
					</select>
				</div>
				<div class="fstu-form-group">
					<label class="fstu-label fstu-label--required" for="fstu-referees-certificate-url"><?php esc_html_e( 'Посилання на довідку', 'fstu' ); ?></label>
					<input type="url" name="certificate_url" id="fstu-referees-certificate-url" class="fstu-input" placeholder="https://..." maxlength="300">
				</div>
			</form>
		</div>
		<div class="fstu-modal__footer">
			<button type="button" class="fstu-btn" data-close-modal="fstu-referees-certificate-form-modal"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
			<button type="submit" form="fstu-referees-certificate-form" class="fstu-btn fstu-btn--primary" id="fstu-referees-certificate-form-submit"><?php esc_html_e( 'ЗБЕРЕГТИ', 'fstu' ); ?></button>
		</div>
	</div>
</div>

