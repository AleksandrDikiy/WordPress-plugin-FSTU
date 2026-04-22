<?php
/**
 * Модальне вікно внесення оплати.
 *
 * Version:     1.1.1
 * Date_update: 2026-04-07
 *
 * @package FSTU\Modules\UserFstu\Sailboats
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-sailboats-payment-modal" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
	<div class="fstu-modal fstu-modal--compact" role="dialog" aria-modal="true" aria-labelledby="fstu-sailboats-payment-title">
		<div class="fstu-modal__header">
			<h3 id="fstu-sailboats-payment-title" class="fstu-modal__title"><?php esc_html_e( 'Внесення оплати', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-sailboats-payment-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal__body">
			<div id="fstu-sailboats-payment-message" class="fstu-form-message fstu-hidden"></div>
			<form id="fstu-sailboats-payment-form" class="fstu-form-grid" novalidate>
				<input type="hidden" id="fstu-sailboats-payment-item-id" name="item_id" value="0">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( \FSTU\Modules\Registry\Sailboats\Sailboats_List::NONCE_ACTION ) ); ?>">
				<div class="fstu-honeypot" aria-hidden="true">
					<label for="fstu-sailboats-payment-website">Website</label>
					<input type="text" id="fstu-sailboats-payment-website" name="fstu_website" class="fstu-hidden-field" tabindex="-1" autocomplete="off">
				</div>
				<div class="fstu-form-group">
					<label class="fstu-label fstu-label--required" for="fstu-sailboats-payment-slot"><?php esc_html_e( 'Тип внеску', 'fstu' ); ?></label>
					<select id="fstu-sailboats-payment-slot" name="payment_slot" class="fstu-select" required>
						<option value="V1"><?php esc_html_e( 'V1', 'fstu' ); ?></option>
						<option value="V2"><?php esc_html_e( 'V2', 'fstu' ); ?></option>
						<option value="F1"><?php esc_html_e( 'F1', 'fstu' ); ?></option>
						<option value="F2"><?php esc_html_e( 'F2', 'fstu' ); ?></option>
					</select>
				</div>
				<div class="fstu-form-group">
					<label class="fstu-label fstu-label--required" for="fstu-sailboats-payment-amount"><?php esc_html_e( 'Сума', 'fstu' ); ?></label>
					<input type="number" class="fstu-input" id="fstu-sailboats-payment-amount" name="payment_amount" min="0" step="0.01" required>
				</div>
				<div class="fstu-form-group">
					<label class="fstu-label fstu-label--required" for="fstu-sailboats-payment-date"><?php esc_html_e( 'Дата оплати', 'fstu' ); ?></label>
					<input type="date" class="fstu-input" id="fstu-sailboats-payment-date" name="payment_date" required>
				</div>
				<div class="fstu-form-group">
					<label class="fstu-label" for="fstu-sailboats-payment-comment"><?php esc_html_e( 'Коментар', 'fstu' ); ?></label>
					<textarea id="fstu-sailboats-payment-comment" name="comment" class="fstu-textarea" rows="4"></textarea>
				</div>
				<div class="fstu-modal__footer">
					<button type="submit" class="fstu-btn fstu-btn--primary" id="fstu-sailboats-payment-submit"><?php esc_html_e( 'Зберегти оплату', 'fstu' ); ?></button>
					<button type="button" class="fstu-btn" data-close-modal="fstu-sailboats-payment-modal"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
				</div>
			</form>
		</div>
	</div>
</div>

