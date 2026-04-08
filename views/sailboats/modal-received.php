<?php
/**
 * Модальне вікно фіксації доставки / вручення документа.
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

<div id="fstu-sailboats-received-modal" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
	<div class="fstu-modal fstu-modal--compact" role="dialog" aria-modal="true" aria-labelledby="fstu-sailboats-received-title">
		<div class="fstu-modal__header">
			<h3 id="fstu-sailboats-received-title" class="fstu-modal__title"><?php esc_html_e( 'Доставлено одержувачу', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-sailboats-received-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal__body">
			<div id="fstu-sailboats-received-message" class="fstu-form-message fstu-hidden"></div>
			<form id="fstu-sailboats-received-form" class="fstu-form-grid" novalidate>
				<input type="hidden" id="fstu-sailboats-received-item-id" name="item_id" value="0">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( \FSTU\Modules\Registry\Sailboats\Sailboats_List::NONCE_ACTION ) ); ?>">
				<div class="fstu-honeypot" aria-hidden="true">
					<label for="fstu-sailboats-received-website">Website</label>
					<input type="text" id="fstu-sailboats-received-website" name="fstu_website" class="fstu-hidden-field" tabindex="-1" autocomplete="off">
				</div>
				<div class="fstu-form-group">
					<label class="fstu-label fstu-label--required" for="fstu-sailboats-received-date"><?php esc_html_e( 'Дата вручення', 'fstu' ); ?></label>
					<input type="date" class="fstu-input" id="fstu-sailboats-received-date" name="received_at" required>
				</div>
				<div class="fstu-form-group">
					<label class="fstu-label" for="fstu-sailboats-received-comment"><?php esc_html_e( 'Коментар', 'fstu' ); ?></label>
					<textarea id="fstu-sailboats-received-comment" name="comment" class="fstu-textarea" rows="4"></textarea>
				</div>
				<div class="fstu-modal__footer">
					<button type="submit" class="fstu-btn fstu-btn--primary" id="fstu-sailboats-received-submit"><?php esc_html_e( 'Підтвердити вручення', 'fstu' ); ?></button>
					<button type="button" class="fstu-btn" data-close-modal="fstu-sailboats-received-modal"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
				</div>
			</form>
		</div>
	</div>
</div>

