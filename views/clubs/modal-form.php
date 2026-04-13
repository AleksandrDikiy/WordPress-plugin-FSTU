<?php
/**
 * View: Модальне вікно форми «Додавання / Редагування клубу».
 * Використовується для обох операцій (заголовок та club_id змінюються через JS).
 * Містить: nonce (через wp_localize_script), honeypot поле.
 *
 * Version:     1.0.2
 * Date_update: 2026-04-13
 *
 * @package FSTU\Dictionaries\Clubs\Views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Показуємо форму тільки тим, хто має права.
$user     = wp_get_current_user();
$roles    = (array) $user->roles;
$can_edit = current_user_can( 'manage_options' )
	|| in_array( 'administrator', $roles, true )
	|| in_array( 'userregistrar', $roles, true );

if ( ! $can_edit ) {
	return;
}
?>

<div class="fstu-modal-overlay fstu-hidden"
     id="fstu-modal-club-form"
     role="dialog"
     aria-modal="true"
     aria-labelledby="fstu-club-form-title"
     aria-hidden="true">

	<div class="fstu-modal fstu-modal--compact">

		<div class="fstu-modal__header">
			<h2 class="fstu-modal__title" id="fstu-club-form-title">Додавання клубу</h2>
			<button type="button"
			        class="fstu-modal__close"
			        data-close-modal="fstu-modal-club-form"
			        aria-label="Закрити форму">✕</button>
		</div>

		<div class="fstu-modal__body">

			<form id="fstu-club-form" novalidate aria-label="Форма клубу">

				<!-- Honeypot (захист від ботів) -->
				<div class="fstu-honeypot" aria-hidden="true">
					<label for="fstu_club_website">Website</label>
					<input type="text"
					       id="fstu_club_website"
					       name="fstu_website"
					       tabindex="-1"
					       autocomplete="off"
					       value="">
				</div>

				<!-- Прихований ID для режиму редагування -->
				<input type="hidden" id="fstu-club-form-id" value="0">

				<!-- Назва клубу (обов'язкове) -->
				<div class="fstu-form-group">
					<label class="fstu-label fstu-label--required" for="fstu-club-name">
						Назва клубу
					</label>
					<input type="text"
					       id="fstu-club-name"
					       class="fstu-input"
					       name="club_name"
					       autocomplete="organization"
					       maxlength="100"
					       required
					       aria-required="true"
					       aria-describedby="fstu-club-name-error">
					<span id="fstu-club-name-error" class="fstu-field-error fstu-hidden" role="alert"></span>
				</div>

				<!-- Поштова адреса -->
				<div class="fstu-form-group">
					<label class="fstu-label" for="fstu-club-adr">
						Поштова адреса
					</label>
					<input type="text"
					       id="fstu-club-adr"
					       class="fstu-input"
					       name="club_adr"
					       autocomplete="street-address"
					       maxlength="200">
				</div>

				<!-- Посилання на сайт клубу -->
				<div class="fstu-form-group">
					<label class="fstu-label" for="fstu-club-www">
						Сайт клубу
					</label>
					<input type="url"
					       id="fstu-club-www"
					       class="fstu-input"
					       name="club_www"
					       autocomplete="url"
					       placeholder="https://"
					       maxlength="250"
					       aria-describedby="fstu-club-www-hint">
					<small id="fstu-club-www-hint" class="fstu-hint">
						Наприклад: https://myklub.com.ua
					</small>
				</div>

				<!-- Повідомлення форми -->
				<div class="fstu-form-message fstu-hidden"
				     id="fstu-club-form-message"
				     role="alert"></div>

				<!-- Кнопки -->
				<div class="fstu-form-actions">
					<button type="submit"
					        class="fstu-btn fstu-btn--primary"
					        id="fstu-club-form-submit">
						<span class="fstu-btn__text">Зберегти</span>
						<span class="fstu-btn__loader fstu-hidden" aria-hidden="true"></span>
					</button>
					<button type="button"
					        class="fstu-btn fstu-btn--text"
					        data-close-modal="fstu-modal-club-form">
						Скасувати
					</button>
				</div>

			</form>

		</div><!-- .fstu-modal__body -->
	</div><!-- .fstu-modal -->
</div><!-- #fstu-modal-club-form -->
