<?php
namespace FSTU\Modules\Applications;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div id="fstu-applications-modal-accept" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
    <div class="fstu-modal fstu-modal--compact" role="dialog" aria-labelledby="fstu-applications-modal-accept-title">
        <div class="fstu-modal__header">
            <h3 id="fstu-applications-modal-accept-title" class="fstu-modal__title">Прийняття в члени ФСТУ</h3>
            <button type="button" class="fstu-modal__close" data-close-modal="fstu-applications-modal-accept" aria-label="Закрити">×</button>
        </div>
        <div class="fstu-modal__body">
            <form id="fstu-applications-accept-form" class="fstu-app-form">
                <input type="hidden" name="user_id" id="fstu-applications-accept-user-id" value="0">

                <div id="fstu-applications-accept-message" class="fstu-form-message fstu-hidden"></div>

                <div class="fstu-app-card__section">
                    <h4 class="fstu-app-card__title">Підтвердження дії</h4>
                    <div class="fstu-app-card__grid">
                        <div class="fstu-app-card__row">
                            <span class="fstu-app-card__label">Кандидат</span>
                            <span class="fstu-app-card__value" id="fstu-applications-accept-candidate-name">—</span>
                        </div>
                    </div>
                    <p class="fstu-hint">Після підтвердження користувачу буде присвоєно роль члена ФСТУ та створено членський квиток.</p>
                </div>

                <div class="fstu-app-card__section fstu-app-card__section--payment">
                    <h4 class="fstu-app-card__title">Платіжні документи</h4>
                    <p class="fstu-hint">Наявність оплати має інформаційний характер і не блокує прийняття заявки. Перевірка здійснюється у модулі PaymentDocs.</p>
                    <div class="fstu-form-actions">
                        <a href="#" class="fstu-btn" id="fstu-applications-accept-payment-docs-link" target="_blank" rel="noopener noreferrer">Перейти в PaymentDocs</a>
                    </div>
                </div>

                <div class="fstu-form-actions">
                    <button type="button" class="fstu-btn" data-close-modal="fstu-applications-modal-accept">Скасувати</button>
                    <button type="submit" class="fstu-btn fstu-btn--primary" id="fstu-applications-accept-submit">Прийняти в члени ФСТУ</button>
                </div>
            </form>
        </div>
    </div>
</div>

