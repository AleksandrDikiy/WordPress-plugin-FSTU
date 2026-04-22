<?php
/**
 * Модальна форма створення / редагування запису Guidance.
 *
 * Version:     1.0.1
 * Date_update: 2026-04-12
 *
 * @package FSTU\Modules\UserFstu\Guidance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-guidance-form-modal" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
	<div class="fstu-modal fstu-modal--compact fstu-modal--guidance-form" role="dialog" aria-modal="true" aria-labelledby="fstu-guidance-form-title">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-guidance-form-title"><?php esc_html_e( 'Додавання запису Guidance', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-guidance-form-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal__body">
			<div id="fstu-guidance-form-message" class="fstu-form-message fstu-hidden"></div>
			<form id="fstu-guidance-form" class="fstu-guidance-form" novalidate>
				<input type="hidden" id="fstu-guidance-id" name="guidance_id" value="0">
				<input type="hidden" id="fstu-guidance-form-mode" value="create">
				<input type="hidden" id="fstu-guidance-form-nonce" name="nonce" value="<?php echo esc_attr( wp_create_nonce( \FSTU\Modules\Registry\Guidance\Guidance_List::NONCE_ACTION ) ); ?>">
				<div class="fstu-honeypot" aria-hidden="true">
					<label for="fstu-guidance-website">Website</label>
					<input type="text" id="fstu-guidance-website" name="fstu_website" tabindex="-1" autocomplete="off">
				</div>

				<div class="fstu-form-grid">
					<div class="fstu-form-group">
						<label class="fstu-label fstu-label--required" for="fstu-guidance-typeguidance-id"><?php esc_html_e( 'Керівний орган', 'fstu' ); ?></label>
						<select id="fstu-guidance-typeguidance-id" name="typeguidance_id" class="fstu-input" required>
							<option value="1"><?php esc_html_e( 'Виконком', 'fstu' ); ?></option>
						</select>
					</div>

					<div class="fstu-form-group">
						<label class="fstu-label fstu-label--required" for="fstu-guidance-member-guidance-id"><?php esc_html_e( 'Посада', 'fstu' ); ?></label>
						<select id="fstu-guidance-member-guidance-id" name="member_guidance_id" class="fstu-input" required>
							<option value="0"><?php esc_html_e( 'Оберіть посаду', 'fstu' ); ?></option>
						</select>
					</div>

					<div class="fstu-form-group fstu-form-group--autocomplete fstu-form-group--full">
						<label class="fstu-label fstu-label--required" for="fstu-guidance-user-input"><?php esc_html_e( 'Користувач / член ФСТУ', 'fstu' ); ?></label>
						<input type="text" id="fstu-guidance-user-input" class="fstu-input" autocomplete="off" placeholder="<?php esc_attr_e( 'Введіть ПІБ члена ФСТУ', 'fstu' ); ?>" required>
						<input type="hidden" id="fstu-guidance-user-id" name="user_id" value="0">
						<div id="fstu-guidance-user-results" class="fstu-autocomplete-results fstu-hidden"></div>
						<div class="fstu-hint" id="fstu-guidance-user-hint"><?php esc_html_e( 'Після введення оберіть користувача зі списку підказок.', 'fstu' ); ?></div>
					</div>

					<div class="fstu-form-group fstu-form-group--full">
						<label class="fstu-label" for="fstu-guidance-notes"><?php esc_html_e( 'Примітка', 'fstu' ); ?></label>
						<textarea id="fstu-guidance-notes" name="guidance_notes" class="fstu-input fstu-textarea" rows="3"></textarea>
					</div>
				</div>
			</form>
		</div>
		<div class="fstu-modal__footer">
			<button type="submit" form="fstu-guidance-form" class="fstu-btn fstu-btn--primary fstu-btn--save" id="fstu-guidance-form-submit"><?php esc_html_e( 'Зберегти', 'fstu' ); ?></button>
			<button type="button" class="fstu-btn fstu-btn--secondary fstu-btn--cancel" data-close-modal="fstu-guidance-form-modal"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
		</div>
	</div>
</div>

