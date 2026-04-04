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

    <?php if ( $is_admin || in_array( 'userregistrar', (array) wp_get_current_user()->roles, true ) ) : ?>
		<!-- Протокол — тільки для авторизованих -->
        <button type="button"
                class="fstu-btn fstu-btn--secondary fstu-btn--open-modal"
                data-modal="fstu-modal-protocol"
                id="fstu-btn-protocol"
                title="Сформувати протокол"
                <?php echo ! $is_admin ? 'disabled aria-disabled="true"' : ''; ?>>
            <span class="fstu-btn__icon" aria-hidden="true">📋</span> Протокол
        </button>
		<!-- Звіт — тільки для адміністраторів та реєстраторам-->
        <button type="button"
                class="fstu-btn fstu-btn--secondary fstu-btn--open-modal"
                data-modal="fstu-modal-report"
                id="fstu-btn-report"
		        title="Сформувати звіт"
		        <?php echo ! $is_admin ? 'disabled aria-disabled="true"' : ''; ?>>
			<span class="fstu-btn__icon" aria-hidden="true">📊</span> Звіт
		</button>
        <!-- Реєстр платежів (Групові) — тільки для адміністраторів та реєстраторам-->
        <a href="/personal/rejestr-platizhok/" class="fstu-btn fstu-btn--primary" style="margin-left: 10px;">
            <span class="fstu-btn__icon">💰</span> Реєстр платежів (Групові)
        </a>
	<?php endif; ?>

	<!-- Зовнішні посилання -->
    <a href="<?php echo esc_url( $link_instruction ); ?>"
       class="fstu-action-link"
       target="_blank"
       rel="noopener noreferrer">Інструкція</a>

    <a href="<?php echo esc_url( $link_postanova ); ?>"
       class="fstu-action-link"
       target="_blank"
       rel="noopener noreferrer"
       title="Протокол №1 від 30.01.2020р.">ПОСТАНОВА та ПОЛОЖЕННЯ</a>

</div><!-- .fstu-action-bar -->
