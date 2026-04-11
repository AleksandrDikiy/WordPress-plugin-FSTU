<?php
/**
 * Модальна форма створення / редагування призначення реєстратора.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-11
 *
 * @package FSTU\Modules\Registry\Recorders
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-recorders-form-modal" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
	<div class="fstu-modal fstu-modal--compact" role="dialog" aria-modal="true" aria-labelledby="fstu-recorders-form-title">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-recorders-form-title"><?php esc_html_e( 'Додавання реєстратора', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-recorders-form-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal__body">
			<div id="fstu-recorders-form-message" class="fstu-form-message fstu-hidden"></div>

			<form id="fstu-recorders-form" class="fstu-recorders-form" novalidate>
				<input type="hidden" id="fstu-recorders-user-region-id" name="user_region_id" value="0">
				<div class="fstu-honeypot" aria-hidden="true">
					<label for="fstu-recorders-website">Website</label>
					<input type="text" id="fstu-recorders-website" name="fstu_website" tabindex="-1" autocomplete="off">
				</div>

				<div class="fstu-form-grid">
					<div class="fstu-form-group">
						<label class="fstu-label fstu-label--required" for="fstu-recorders-unit-id"><?php esc_html_e( 'Осередок ФСТУ', 'fstu' ); ?></label>
						<select id="fstu-recorders-unit-id" name="unit_id" class="fstu-input" required>
							<option value="0"><?php esc_html_e( 'Оберіть осередок', 'fstu' ); ?></option>
						</select>
					</div>

					<div class="fstu-form-group fstu-form-group--autocomplete">
						<label class="fstu-label fstu-label--required" for="fstu-recorders-candidate-input"><?php esc_html_e( 'Реєстратор', 'fstu' ); ?></label>
						<input type="text" id="fstu-recorders-candidate-input" class="fstu-input" autocomplete="off" placeholder="<?php esc_attr_e( 'Введіть ПІБ члена ФСТУ', 'fstu' ); ?>" required>
						<input type="hidden" id="fstu-recorders-user-id" name="user_id" value="0">
						<div id="fstu-recorders-candidate-results" class="fstu-autocomplete-results fstu-hidden"></div>
						<div class="fstu-hint"><?php esc_html_e( 'Після введення оберіть користувача зі списку підказок.', 'fstu' ); ?></div>
					</div>
				</div>
			</form>
		</div>
		<div class="fstu-modal__footer">
			<button type="submit" form="fstu-recorders-form" class="fstu-btn fstu-btn--primary fstu-btn--save" id="fstu-recorders-form-submit"><?php esc_html_e( 'Зберегти', 'fstu' ); ?></button>
			<button type="button" class="fstu-btn fstu-btn--secondary fstu-btn--cancel" data-close-modal="fstu-recorders-form-modal"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
		</div>
	</div>
</div>

