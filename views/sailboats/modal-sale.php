<?php
/**
 * Модальне вікно продажу / вибуття судна.
 *
 * Version:     1.1.1
 * Date_update: 2026-04-07
 *
 * @package FSTU\Modules\Registry\Sailboats
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-sailboats-sale-modal" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
	<div class="fstu-modal fstu-modal--compact" role="dialog" aria-modal="true" aria-labelledby="fstu-sailboats-sale-title">
		<div class="fstu-modal__header">
			<h3 id="fstu-sailboats-sale-title" class="fstu-modal__title"><?php esc_html_e( 'Продаж / вибуття судна', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-sailboats-sale-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal__body">
			<div id="fstu-sailboats-sale-message" class="fstu-form-message fstu-hidden"></div>
			<form id="fstu-sailboats-sale-form" class="fstu-form-grid" novalidate>
				<input type="hidden" id="fstu-sailboats-sale-item-id" name="item_id" value="0">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( \FSTU\Modules\Registry\Sailboats\Sailboats_List::NONCE_ACTION ) ); ?>">
				<div class="fstu-honeypot" aria-hidden="true">
					<label for="fstu-sailboats-sale-website">Website</label>
					<input type="text" id="fstu-sailboats-sale-website" name="fstu_website" class="fstu-hidden-field" tabindex="-1" autocomplete="off">
				</div>
				<div class="fstu-form-group">
					<label class="fstu-label fstu-label--required" for="fstu-sailboats-sale-date"><?php esc_html_e( 'Дата продажу / вибуття', 'fstu' ); ?></label>
					<input type="date" class="fstu-input" id="fstu-sailboats-sale-date" name="sale_date" required>
				</div>
				<div class="fstu-form-group">
					<label class="fstu-label" for="fstu-sailboats-sale-comment"><?php esc_html_e( 'Коментар', 'fstu' ); ?></label>
					<textarea id="fstu-sailboats-sale-comment" name="comment" class="fstu-textarea" rows="4"></textarea>
				</div>
				<div class="fstu-modal__footer">
					<button type="submit" class="fstu-btn fstu-btn--primary" id="fstu-sailboats-sale-submit"><?php esc_html_e( 'Зафіксувати вибуття', 'fstu' ); ?></button>
					<button type="button" class="fstu-btn" data-close-modal="fstu-sailboats-sale-modal"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
				</div>
			</form>
		</div>
	</div>
</div>

