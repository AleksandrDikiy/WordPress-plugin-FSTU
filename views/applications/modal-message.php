<?php
namespace FSTU\Modules\Applications;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Модальне вікно відхилення заявки.
 *
 * Version:     1.1.0
 * Date_update: 2026-04-06
 */
?>

<div id="fstu-applications-modal-message" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
    <div class="fstu-modal fstu-modal--compact" role="dialog" aria-labelledby="fstu-applications-modal-message-title">
        <div class="fstu-modal__header">
            <h3 id="fstu-applications-modal-message-title" class="fstu-modal__title">Відхилення заявки</h3>
            <button type="button" class="fstu-modal__close" data-close-modal="fstu-applications-modal-message" aria-label="Закрити">×</button>
        </div>
        <div class="fstu-modal__body">
            <form id="fstu-applications-reject-form" class="fstu-app-form">
                <input type="hidden" name="user_id" id="fstu-applications-reject-user-id" value="0">
                <label for="fstu-applications-reject-honeypot" class="screen-reader-text">Не заповнюйте це поле</label>
                <input type="text" name="fstu_website" id="fstu-applications-reject-honeypot" class="fstu-hidden-field" tabindex="-1" autocomplete="off" value="">

                <div id="fstu-applications-reject-message" class="fstu-form-message fstu-hidden"></div>

                <div class="fstu-app-card__section">
                    <h4 class="fstu-app-card__title">Підтвердження дії</h4>
                    <div class="fstu-app-card__grid">
                        <div class="fstu-app-card__row">
                            <span class="fstu-app-card__label">Кандидат</span>
                            <span class="fstu-app-card__value" id="fstu-applications-reject-candidate-name">—</span>
                        </div>
                    </div>
                    <p class="fstu-hint">Користувач не буде видалений фізично. Заявка буде відхилена через зміну ролі на `Blocked`.</p>
                </div>

                <div class="fstu-form-actions">
                    <button type="button" class="fstu-btn" data-close-modal="fstu-applications-modal-message">Скасувати</button>
                    <button type="submit" class="fstu-btn fstu-btn--primary" id="fstu-applications-reject-submit">Відхилити заявку</button>
                </div>
            </form>
        </div>
    </div>
</div>

