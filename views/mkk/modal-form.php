<?php
/**
 * Модальна форма створення / редагування запису МКК.
 *
 * Version:     1.1.1
 * Date_update: 2026-04-12
 *
 * @package FSTU\Modules\UserFstu\MKK
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-mkk-form-modal" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
	<div class="fstu-modal fstu-modal--compact fstu-modal--mkk-form" role="dialog" aria-modal="true" aria-labelledby="fstu-mkk-form-title">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-mkk-form-title"><?php esc_html_e( 'Додавання запису МКК', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-mkk-form-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal__body">
			<div id="fstu-mkk-form-message" class="fstu-form-message fstu-hidden"></div>

			<form id="fstu-mkk-form" class="fstu-mkk-form" novalidate>
				<input type="hidden" id="fstu-mkk-id" name="mkk_id" value="0">
				<input type="hidden" id="fstu-mkk-form-mode" value="create">
				<div class="fstu-honeypot" aria-hidden="true">
					<label for="fstu-mkk-website">Website</label>
					<input type="text" id="fstu-mkk-website" name="fstu_website" tabindex="-1" autocomplete="off">
				</div>

				<div class="fstu-form-grid">
					<div class="fstu-form-group fstu-form-group--autocomplete fstu-form-group--full">
						<label class="fstu-label fstu-label--required" for="fstu-mkk-user-input"><?php esc_html_e( 'Член ФСТУ', 'fstu' ); ?></label>
						<input type="text" id="fstu-mkk-user-input" class="fstu-input" autocomplete="off" placeholder="<?php esc_attr_e( 'Введіть ПІБ члена ФСТУ', 'fstu' ); ?>" required>
						<input type="hidden" id="fstu-mkk-user-id" name="user_id" value="0">
						<div id="fstu-mkk-user-results" class="fstu-autocomplete-results fstu-hidden"></div>
						<div class="fstu-hint" id="fstu-mkk-user-hint"><?php esc_html_e( 'Після введення оберіть користувача зі списку підказок.', 'fstu' ); ?></div>
					</div>

					<div class="fstu-form-group">
						<label class="fstu-label fstu-label--required" for="fstu-mkk-region-id"><?php esc_html_e( 'Область', 'fstu' ); ?></label>
						<select id="fstu-mkk-region-id" name="region_id" class="fstu-input" required>
							<option value="0"><?php esc_html_e( 'Оберіть область', 'fstu' ); ?></option>
						</select>
					</div>

					<div class="fstu-form-group">
						<label class="fstu-label fstu-label--required" for="fstu-mkk-commission-type-id"><?php esc_html_e( 'Тип комісії', 'fstu' ); ?></label>
						<select id="fstu-mkk-commission-type-id" name="commission_type_id" class="fstu-input" required>
							<option value="0"><?php esc_html_e( 'Оберіть тип', 'fstu' ); ?></option>
						</select>
					</div>

					<div class="fstu-form-group fstu-form-group--full">
						<label class="fstu-label fstu-label--required" for="fstu-mkk-tourism-type-id"><?php esc_html_e( 'Вид туризму', 'fstu' ); ?></label>
						<select id="fstu-mkk-tourism-type-id" name="tourism_type_id" class="fstu-input" required>
							<option value="0"><?php esc_html_e( 'Оберіть вид туризму', 'fstu' ); ?></option>
						</select>
					</div>
				</div>
			</form>
		</div>
		<div class="fstu-modal__footer">
			<button type="submit" form="fstu-mkk-form" class="fstu-btn fstu-btn--primary fstu-btn--save" id="fstu-mkk-form-submit"><?php esc_html_e( 'Зберегти', 'fstu' ); ?></button>
			<button type="button" class="fstu-btn fstu-btn--secondary fstu-btn--cancel" data-close-modal="fstu-mkk-form-modal"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
		</div>
	</div>
</div>

