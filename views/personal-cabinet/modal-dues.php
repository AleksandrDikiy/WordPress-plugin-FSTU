<?php
/**
 * View: Модальне вікно додавання квитанції про членський внесок.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-09
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="fstu-modal-overlay fstu-hidden" id="fstu-personal-dues-modal" role="dialog" aria-modal="true" aria-labelledby="fstu-personal-dues-modal-title">
	<div class="fstu-modal fstu-personal-modal">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-personal-dues-modal-title">Додати квитанцію про оплату</h3>
			<button type="button" class="fstu-modal__close" id="fstu-personal-dues-modal-close" aria-label="Закрити">×</button>
		</div>
		<div class="fstu-modal__body">
			<div class="fstu-alert fstu-alert--error fstu-hidden" id="fstu-personal-dues-modal-message"></div>
			<form id="fstu-personal-dues-form" class="fstu-personal-form">
				<label class="fstu-hidden" for="fstu-personal-dues-honeypot">Службове поле захисту від ботів</label>
				<input type="text" id="fstu-personal-dues-honeypot" name="fstu_website" class="fstu-hidden-field" value="" tabindex="-1" autocomplete="off">
				<div class="fstu-form-group">
					<label class="fstu-label" for="fstu-personal-dues-year">Рік *</label>
					<input type="number" id="fstu-personal-dues-year" name="year_id" class="fstu-input" min="2000" max="2100" required>
				</div>
				<div class="fstu-form-group">
					<label class="fstu-label" for="fstu-personal-dues-summa">Сума, грн *</label>
					<input type="number" id="fstu-personal-dues-summa" name="summa" class="fstu-input" min="0.01" step="0.01" required>
				</div>
				<div class="fstu-form-group">
					<label class="fstu-label" for="fstu-personal-dues-url">Посилання на квитанцію *</label>
					<input type="url" id="fstu-personal-dues-url" name="url" class="fstu-input" placeholder="https://..." required>
					<p class="fstu-form-help">Розмістіть чек у безпечному файлообміннику або хмарному сховищі та вставте повне посилання.</p>
				</div>
				<div class="fstu-personal-form__actions">
					<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-personal-dues-cancel">Скасувати</button>
					<button type="submit" class="fstu-btn fstu-btn--primary" id="fstu-personal-dues-submit">Зберегти квитанцію</button>
				</div>
			</form>
		</div>
	</div>
</div>

