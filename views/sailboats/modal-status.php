<?php
/**
 * Модальне вікно зміни статусу судна / заявки.
 *
 * Version:     1.0.1
 * Date_update: 2026-04-07
 *
 * @package FSTU\Modules\Registry\Sailboats
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-sailboats-status-modal" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
	<div class="fstu-modal fstu-modal--compact" role="dialog" aria-modal="true" aria-labelledby="fstu-sailboats-status-title">
		<div class="fstu-modal__header">
			<h3 id="fstu-sailboats-status-title" class="fstu-modal__title"><?php esc_html_e( 'Зміна статусу', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-sailboats-status-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal__body">
			<div id="fstu-sailboats-status-message" class="fstu-form-message fstu-hidden"></div>
			<form id="fstu-sailboats-status-form" class="fstu-form-grid" novalidate>
				<input type="hidden" id="fstu-sailboats-status-item-id" name="item_id" value="0">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( \FSTU\Modules\Registry\Sailboats\Sailboats_List::NONCE_ACTION ) ); ?>">
				<div class="fstu-honeypot" aria-hidden="true">
					<label for="fstu-sailboats-status-website">Website</label>
					<input type="text" id="fstu-sailboats-status-website" name="fstu_website" class="fstu-hidden-field" tabindex="-1" autocomplete="off">
				</div>
				<div class="fstu-form-group">
					<label class="fstu-label fstu-label--required" for="fstu-sailboats-status-verification-id"><?php esc_html_e( 'Новий статус', 'fstu' ); ?></label>
					<select id="fstu-sailboats-status-verification-id" name="verification_id" class="fstu-select" required>
						<option value="0"><?php esc_html_e( 'Оберіть статус', 'fstu' ); ?></option>
					</select>
				</div>
				<div class="fstu-form-group">
					<label class="fstu-label" for="fstu-sailboats-status-comment"><?php esc_html_e( 'Коментар', 'fstu' ); ?></label>
					<textarea id="fstu-sailboats-status-comment" name="comment" class="fstu-textarea" rows="4"></textarea>
				</div>
				<div class="fstu-modal__footer">
					<button type="submit" class="fstu-btn fstu-btn--primary" id="fstu-sailboats-status-submit"><?php esc_html_e( 'Зберегти статус', 'fstu' ); ?></button>
					<button type="button" class="fstu-btn" data-close-modal="fstu-sailboats-status-modal"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
				</div>
			</form>
		</div>
	</div>
</div>

