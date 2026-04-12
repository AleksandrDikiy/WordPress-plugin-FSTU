<?php
/**
 * Модальна форма додавання / редагування виду участі.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-12
 *
 * @package FSTU\Dictionaries\ParticipationType
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-participationtype-form-modal" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
	<div class="fstu-modal fstu-modal--compact fstu-modal--participationtype-form" role="dialog" aria-modal="true" aria-labelledby="fstu-participationtype-form-title">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-participationtype-form-title"><?php esc_html_e( 'Додавання запису', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-participationtype-form-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal__body">
			<div id="fstu-participationtype-form-message" class="fstu-form-message fstu-hidden"></div>

			<form id="fstu-participationtype-form" class="fstu-app-form" novalidate>
				<input type="hidden" id="fstu-participationtype-id" name="participation_type_id" value="0">
				<div class="fstu-honeypot" aria-hidden="true">
					<label for="fstu-participationtype-website">Website</label>
					<input type="text" id="fstu-participationtype-website" name="fstu_website" tabindex="-1" autocomplete="off">
				</div>

				<div class="fstu-form-grid">
					<div class="fstu-form-group fstu-form-group--wide">
						<label class="fstu-label fstu-label--required" for="fstu-participationtype-name"><?php esc_html_e( 'Найменування', 'fstu' ); ?></label>
						<input type="text" class="fstu-input" id="fstu-participationtype-name" name="participation_type_name" maxlength="255" required>
					</div>

					<div class="fstu-form-group fstu-form-group--wide">
						<span class="fstu-label fstu-label--required"><?php esc_html_e( 'Тип', 'fstu' ); ?></span>
						<div class="fstu-segmented-control" role="radiogroup" aria-label="<?php esc_attr_e( 'Тип участі', 'fstu' ); ?>">
							<label class="fstu-segmented-control__item">
								<input type="radio" name="participation_type_type" value="1" checked>
								<span><?php esc_html_e( 'Змагання / походи', 'fstu' ); ?></span>
							</label>
							<label class="fstu-segmented-control__item">
								<input type="radio" name="participation_type_type" value="2">
								<span><?php esc_html_e( 'Інше', 'fstu' ); ?></span>
							</label>
						</div>
					</div>

					<div class="fstu-form-group">
						<label class="fstu-label" for="fstu-participationtype-order"><?php esc_html_e( 'Сортування', 'fstu' ); ?></label>
						<input type="number" class="fstu-input" id="fstu-participationtype-order" name="participation_type_order" min="0" step="1" inputmode="numeric">
						<div class="fstu-hint"><?php esc_html_e( 'Якщо поле пусте або 0, порядок буде визначено автоматично.', 'fstu' ); ?></div>
					</div>
				</div>
			</form>
		</div>
		<div class="fstu-modal__footer">
			<button type="submit" form="fstu-participationtype-form" class="fstu-btn fstu-btn--primary" id="fstu-participationtype-form-submit"><?php esc_html_e( 'Зберегти', 'fstu' ); ?></button>
			<button type="button" class="fstu-btn fstu-btn--secondary" data-close-modal="fstu-participationtype-form-modal"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
		</div>
	</div>
</div>

