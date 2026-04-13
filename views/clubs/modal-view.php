<?php
/**
 * View: Модальне вікно перегляду клубу (read-only).
 * Заповнюється через AJAX після кліку на назву клубу.
 *
 * Version:     1.0.1
 * Date_update: 2026-04-13
 *
 * @package FSTU\Dictionaries\Clubs\Views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="fstu-modal-overlay fstu-hidden"
     id="fstu-modal-club-view"
     role="dialog"
     aria-modal="true"
     aria-labelledby="fstu-club-view-title"
     aria-hidden="true">

	<div class="fstu-modal fstu-modal--compact">

		<div class="fstu-modal__header">
			<h2 class="fstu-modal__title" id="fstu-club-view-title">Клуб</h2>
			<button type="button"
			        class="fstu-modal__close"
			        data-close-modal="fstu-modal-club-view"
			        aria-label="Закрити">✕</button>
		</div>

		<div class="fstu-modal__body" id="fstu-club-view-body">
			<div class="fstu-tab-loader" aria-hidden="true">
				<span class="fstu-loader__spinner"></span>
			</div>
		</div>

		<div class="fstu-modal__footer" id="fstu-club-view-footer">
			<!-- Кнопка "Редагувати" показується динамічно через JS для авторизованих -->
		</div>

	</div>
</div>
