<?php
/**
 * View: Панель дій (кнопки) реєстру членів ФСТУ.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-03
 *
 * @package FSTU\Registry\Views
 *
 * @var bool $is_admin     Чи є поточний користувач адміністратором.
 * @var bool $is_logged_in Чи авторизований поточний користувач.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="fstu-action-bar">

	<?php if ( ! $is_logged_in ) : ?>
		<!-- Кнопка заявки — тільки для незареєстрованих -->
		<button type="button"
		        class="fstu-btn fstu-btn--primary fstu-btn--open-modal"
		        data-modal="fstu-modal-application"
		        aria-haspopup="dialog">
			<span class="fstu-btn__icon" aria-hidden="true">+</span> Заявка
		</button>
	<?php endif; ?>

	<!-- Оновити список -->
	<button type="button"
	        class="fstu-btn fstu-btn--secondary"
	        id="fstu-btn-refresh"
	        title="Оновити список">
		<span class="fstu-btn__icon" aria-hidden="true">↻</span> Оновити
	</button>

	<?php if ( $is_logged_in ) : ?>
		<!-- Протокол — тільки для авторизованих -->
		<button type="button"
		        class="fstu-btn fstu-btn--secondary"
		        id="fstu-btn-protocol"
		        title="Сформувати протокол"
		        <?php echo ! $is_admin ? 'disabled aria-disabled="true"' : ''; ?>>
			<span class="fstu-btn__icon" aria-hidden="true">📋</span> Протокол
		</button>

		<!-- Звіт — тільки для адміністраторів -->
		<button type="button"
		        class="fstu-btn fstu-btn--secondary"
		        id="fstu-btn-report"
		        title="Сформувати звіт"
		        <?php echo ! $is_admin ? 'disabled aria-disabled="true"' : ''; ?>>
			<span class="fstu-btn__icon" aria-hidden="true">📊</span> Звіт
		</button>
	<?php endif; ?>

	<!-- Зовнішні посилання -->
	<a href="https://www.fstu.com.ua/instrukciya/"
	   class="fstu-action-link"
	   target="_blank"
	   rel="noopener noreferrer">Інструкція</a>

	<a href="https://www.fstu.com.ua/postanova/"
	   class="fstu-action-link"
	   target="_blank"
	   rel="noopener noreferrer">ПОСТАНОВА та ПОЛОЖЕННЯ</a>

</div><!-- .fstu-action-bar -->
