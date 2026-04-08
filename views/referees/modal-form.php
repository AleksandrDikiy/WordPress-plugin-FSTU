<?php
/**
 * Модальне вікно форми судді.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-08
 *
 * @package FSTU\Modules\Registry\Referees
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-referees-form-modal" class="fstu-modal-overlay fstu-hidden" role="dialog" aria-modal="true" aria-labelledby="fstu-referees-form-modal-title">
	<div class="fstu-modal fstu-modal--compact">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-referees-form-modal-title"><?php esc_html_e( 'Додавання судді', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-referees-form-modal">×</button>
		</div>
		<div class="fstu-modal__body">
			<div id="fstu-referees-form-message" class="fstu-form-message fstu-hidden"></div>
			<form id="fstu-referees-form" class="fstu-form-grid">
				<input type="hidden" name="referee_id" id="fstu-referees-form-referee-id" value="0">
				<label for="fstu-referees-form-website" class="fstu-hidden-field" aria-hidden="true"><?php esc_html_e( 'Службове поле', 'fstu' ); ?></label>
				<input type="text" name="fstu_website" id="fstu-referees-form-website" class="fstu-hidden-field" tabindex="-1" autocomplete="off">
				<div class="fstu-form-group" id="fstu-referees-user-group">
					<label class="fstu-label fstu-label--required" for="fstu-referees-form-user-id"><?php esc_html_e( 'Користувач', 'fstu' ); ?></label>
					<select name="user_id" id="fstu-referees-form-user-id" class="fstu-select">
						<option value="0"><?php esc_html_e( 'Оберіть користувача', 'fstu' ); ?></option>
					</select>
				</div>
				<div class="fstu-form-group fstu-hidden" id="fstu-referees-user-name-group">
					<label class="fstu-label"><?php esc_html_e( 'Користувач', 'fstu' ); ?></label>
					<div class="fstu-view-grid__value" id="fstu-referees-form-user-name">—</div>
				</div>
				<div class="fstu-form-group">
					<label class="fstu-label fstu-label--required" for="fstu-referees-form-category-id"><?php esc_html_e( 'Суддівська категорія', 'fstu' ); ?></label>
					<select name="referee_category_id" id="fstu-referees-form-category-id" class="fstu-select">
						<option value="0"><?php esc_html_e( 'Оберіть категорію', 'fstu' ); ?></option>
					</select>
				</div>
				<div class="fstu-form-group">
					<label class="fstu-label" for="fstu-referees-form-num-order"><?php esc_html_e( 'Номер наказу', 'fstu' ); ?></label>
					<input type="text" name="num_order" id="fstu-referees-form-num-order" class="fstu-input" maxlength="120">
				</div>
				<div class="fstu-form-group">
					<label class="fstu-label" for="fstu-referees-form-date-order"><?php esc_html_e( 'Дата наказу', 'fstu' ); ?></label>
					<input type="date" name="date_order" id="fstu-referees-form-date-order" class="fstu-input">
				</div>
				<div class="fstu-form-group">
					<label class="fstu-label" for="fstu-referees-form-url-order"><?php esc_html_e( 'Посилання на наказ', 'fstu' ); ?></label>
					<input type="url" name="url_order" id="fstu-referees-form-url-order" class="fstu-input" maxlength="300" placeholder="https://...">
				</div>
			</form>
		</div>
		<div class="fstu-modal__footer">
			<button type="button" class="fstu-btn" data-close-modal="fstu-referees-form-modal"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
			<button type="submit" form="fstu-referees-form" class="fstu-btn fstu-btn--primary" id="fstu-referees-form-submit"><?php esc_html_e( 'ЗБЕРЕГТИ', 'fstu' ); ?></button>
		</div>
	</div>
</div>

