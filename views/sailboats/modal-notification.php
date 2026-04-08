<?php
/**
 * Модальне вікно надсилання повідомлення щодо внесків.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-07
 *
 * @package FSTU\Modules\Registry\Sailboats
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-sailboats-notification-modal" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
	<div class="fstu-modal fstu-modal--compact" role="dialog" aria-modal="true" aria-labelledby="fstu-sailboats-notification-title">
		<div class="fstu-modal__header">
			<h3 id="fstu-sailboats-notification-title" class="fstu-modal__title"><?php esc_html_e( 'Повідомлення щодо внесків', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-sailboats-notification-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal__body">
			<div id="fstu-sailboats-notification-message" class="fstu-form-message fstu-hidden"></div>
			<form id="fstu-sailboats-notification-form" class="fstu-form-grid" novalidate>
				<input type="hidden" id="fstu-sailboats-notification-item-id" name="item_id" value="0">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( \FSTU\Modules\Registry\Sailboats\Sailboats_List::NONCE_ACTION ) ); ?>">
				<div class="fstu-honeypot" aria-hidden="true">
					<label for="fstu-sailboats-notification-website">Website</label>
					<input type="text" id="fstu-sailboats-notification-website" name="fstu_website" class="fstu-hidden-field" tabindex="-1" autocomplete="off">
				</div>
				<div class="fstu-form-group">
					<label class="fstu-label fstu-label--required" for="fstu-sailboats-notification-type"><?php esc_html_e( 'Тип повідомлення', 'fstu' ); ?></label>
					<select id="fstu-sailboats-notification-type" name="notification_type" class="fstu-select" required>
						<option value="membership"><?php esc_html_e( 'Членські внески ФСТУ', 'fstu' ); ?></option>
						<option value="sailing"><?php esc_html_e( 'Вітрильні внески', 'fstu' ); ?></option>
						<option value="combined"><?php esc_html_e( 'Обидва типи внесків', 'fstu' ); ?></option>
					</select>
				</div>
				<div class="fstu-form-group">
					<label class="fstu-label" for="fstu-sailboats-notification-comment"><?php esc_html_e( 'Додатковий коментар', 'fstu' ); ?></label>
					<textarea id="fstu-sailboats-notification-comment" name="comment" class="fstu-textarea" rows="4"></textarea>
				</div>
				<div class="fstu-modal__footer">
					<button type="submit" class="fstu-btn fstu-btn--primary" id="fstu-sailboats-notification-submit"><?php esc_html_e( 'Надіслати повідомлення', 'fstu' ); ?></button>
					<button type="button" class="fstu-btn" data-close-modal="fstu-sailboats-notification-modal"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
				</div>
			</form>
		</div>
	</div>
</div>

