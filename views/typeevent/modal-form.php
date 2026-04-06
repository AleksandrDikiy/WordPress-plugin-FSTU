<?php
/**
 * Модальне вікно форми додавання/редагування виду змагань.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Dictionaries\TypeEvent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-typeevent-modal-form" class="fstu-modal-overlay fstu-typeevent-hidden" aria-hidden="true">
	<div class="fstu-modal fstu-modal--narrow" role="dialog" aria-modal="true" aria-labelledby="fstu-typeevent-form-title">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-typeevent-form-title"><?php esc_html_e( 'Додавання виду змагань', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal-close-btn" id="fstu-typeevent-modal-form-close" title="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>" aria-label="<?php esc_attr_e( 'Закрити форму виду змагань', 'fstu' ); ?>">✕</button>
		</div>
		<div class="fstu-modal__body">
			<form id="fstu-typeevent-form" class="fstu-app-form">
				<?php wp_nonce_field( \FSTU\Dictionaries\TypeEvent\TypeEvent_List::NONCE_ACTION, 'nonce' ); ?>
				<input type="hidden" id="fstu-typeevent-edit-id" name="typeevent_id" value="">

				<label class="fstu-typeevent-hidden" for="fstu-typeevent-honeypot"><?php esc_html_e( 'Не заповнюйте це поле', 'fstu' ); ?></label>
				<input type="text" id="fstu-typeevent-honeypot" name="fstu_website" class="fstu-honeypot" tabindex="-1" autocomplete="off" aria-hidden="true">

				<div class="fstu-form-row fstu-form-row--2col">
					<div class="fstu-form-group">
						<label class="fstu-label fstu-label--required" for="fstu-typeevent-name"><?php esc_html_e( 'Найменування', 'fstu' ); ?></label>
						<input type="text" id="fstu-typeevent-name" name="typeevent_name" class="fstu-input" placeholder="<?php esc_attr_e( 'Наприклад: Чемпіонат України', 'fstu' ); ?>" required>
					</div>
					<div class="fstu-form-group fstu-form-group--code">
						<label class="fstu-label" for="fstu-typeevent-code"><?php esc_html_e( 'Сортування', 'fstu' ); ?></label>
						<input type="number" id="fstu-typeevent-code" name="typeevent_code" class="fstu-input" min="0" step="1" placeholder="0">
					</div>
				</div>

				<div id="fstu-typeevent-form-message" class="fstu-form-message fstu-typeevent-hidden" aria-live="polite"></div>

				<div class="fstu-form-actions">
					<button type="submit" class="fstu-btn fstu-btn--primary" id="fstu-typeevent-form-submit">
						<span class="fstu-btn__icon">💾</span>
						<span class="fstu-btn-text"><?php esc_html_e( 'Зберегти', 'fstu' ); ?></span>
					</button>
					<button type="button" class="fstu-btn fstu-btn--text" id="fstu-typeevent-form-cancel"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
				</div>
			</form>
		</div>
	</div>
</div>

