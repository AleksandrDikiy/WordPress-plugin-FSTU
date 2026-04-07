<?php
namespace FSTU\Modules\Applications;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Модальне вікно перегляду картки кандидата.
 *
 * Version:     1.2.0
 * Date_update: 2026-04-07
 */
?>

<div id="fstu-applications-modal-view" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
    <div class="fstu-modal fstu-modal--view" role="dialog" aria-labelledby="fstu-applications-modal-view-title">
        <div class="fstu-modal__header">
            <h3 id="fstu-applications-modal-view-title" class="fstu-modal__title">Картка кандидата</h3>
            <button type="button" class="fstu-modal__close" data-close-modal="fstu-applications-modal-view" aria-label="Закрити">×</button>
        </div>
        <div class="fstu-modal__body" id="fstu-applications-modal-view-content">
            <div id="fstu-applications-modal-view-message" class="fstu-form-message fstu-hidden"></div>

            <div class="fstu-app-card">
                <div class="fstu-app-card-tabs" role="tablist" aria-label="Розділи картки кандидата">
                    <button type="button" class="fstu-app-card-tabs__button is-active" id="fstu-app-tab-trigger-main" data-tab="main" role="tab" aria-selected="true" aria-controls="fstu-app-tab-panel-main">Основні дані</button>
                    <button type="button" class="fstu-app-card-tabs__button" id="fstu-app-tab-trigger-contact" data-tab="contact" role="tab" aria-selected="false" aria-controls="fstu-app-tab-panel-contact" tabindex="-1">Контакти та локація</button>
                    <button type="button" class="fstu-app-card-tabs__button" id="fstu-app-tab-trigger-profile" data-tab="profile" role="tab" aria-selected="false" aria-controls="fstu-app-tab-panel-profile" tabindex="-1">Профіль кандидата</button>
                    <button type="button" class="fstu-app-card-tabs__button" id="fstu-app-tab-trigger-payment" data-tab="payment" role="tab" aria-selected="false" aria-controls="fstu-app-tab-panel-payment" tabindex="-1">Платіжні документи</button>
                    <button type="button" class="fstu-app-card-tabs__button" id="fstu-app-tab-trigger-actions" data-tab="actions" role="tab" aria-selected="false" aria-controls="fstu-app-tab-panel-actions" tabindex="-1">Дії</button>
                </div>

                <div class="fstu-app-card__tab-panels">
                    <section class="fstu-app-card__panel is-active" id="fstu-app-tab-panel-main" data-tab-panel="main" role="tabpanel" aria-labelledby="fstu-app-tab-trigger-main">
                        <div class="fstu-app-card__section">
                            <h4 class="fstu-app-card__title">Основні дані</h4>
                            <div class="fstu-app-card__grid">
                                <div class="fstu-app-card__row"><span class="fstu-app-card__label">ПІБ</span><span class="fstu-app-card__value" data-field="fio">—</span></div>
                                <div class="fstu-app-card__row"><span class="fstu-app-card__label">Email</span><span class="fstu-app-card__value" data-field="email">—</span></div>
                                <div class="fstu-app-card__row"><span class="fstu-app-card__label">Дата народження</span><span class="fstu-app-card__value" data-field="birth_date">—</span></div>
                                <div class="fstu-app-card__row"><span class="fstu-app-card__label">Стать</span><span class="fstu-app-card__value" data-field="sex">—</span></div>
                                <div class="fstu-app-card__row"><span class="fstu-app-card__label">Дата заявки</span><span class="fstu-app-card__value" data-field="registered_at">—</span></div>
                                <div class="fstu-app-card__row"><span class="fstu-app-card__label">Номер квитка</span><span class="fstu-app-card__value" data-field="card_number">—</span></div>
                            </div>
                        </div>
                    </section>

                    <section class="fstu-app-card__panel" id="fstu-app-tab-panel-contact" data-tab-panel="contact" role="tabpanel" aria-labelledby="fstu-app-tab-trigger-contact" hidden>
                        <div class="fstu-app-card__section">
                            <h4 class="fstu-app-card__title">Контакти та локація</h4>
                            <div class="fstu-app-card__grid">
                                <div class="fstu-app-card__row"><span class="fstu-app-card__label">ОФСТ</span><span class="fstu-app-card__value" data-field="unit_name">—</span></div>
                                <div class="fstu-app-card__row"><span class="fstu-app-card__label">Область</span><span class="fstu-app-card__value" data-field="region_name">—</span></div>
                                <div class="fstu-app-card__row"><span class="fstu-app-card__label">Місто</span><span class="fstu-app-card__value" data-field="city_name">—</span></div>
                                <div class="fstu-app-card__row"><span class="fstu-app-card__label">Адреса</span><span class="fstu-app-card__value" data-field="address">—</span></div>
                                <div class="fstu-app-card__row"><span class="fstu-app-card__label">Мобільний</span><span class="fstu-app-card__value" data-field="phone">—</span></div>
                                <div class="fstu-app-card__row"><span class="fstu-app-card__label">Телефон</span><span class="fstu-app-card__value" data-field="phone2">—</span></div>
                            </div>
                        </div>
                    </section>

                    <section class="fstu-app-card__panel" id="fstu-app-tab-panel-profile" data-tab-panel="profile" role="tabpanel" aria-labelledby="fstu-app-tab-trigger-profile" hidden>
                        <div class="fstu-app-card__section">
                            <h4 class="fstu-app-card__title">Профіль кандидата</h4>
                            <div class="fstu-app-card__grid">
                                <div class="fstu-app-card__row"><span class="fstu-app-card__label">Вид туризму</span><span class="fstu-app-card__value" data-field="tourism_type">—</span></div>
                                <div class="fstu-app-card__row"><span class="fstu-app-card__label">Клуб</span><span class="fstu-app-card__value" data-field="club_name">—</span></div>
                                <div class="fstu-app-card__row"><span class="fstu-app-card__label">Сайт клубу</span><a href="#" class="fstu-app-card__value fstu-app-card__link fstu-app-card__link--disabled" data-field="club_website" target="_blank" rel="noopener noreferrer" aria-disabled="true">—</a></div>
                                <div class="fstu-app-card__row"><span class="fstu-app-card__label">Місце роботи / навчання</span><span class="fstu-app-card__value" data-field="job">—</span></div>
                                <div class="fstu-app-card__row"><span class="fstu-app-card__label">Посада</span><span class="fstu-app-card__value" data-field="post">—</span></div>
                                <div class="fstu-app-card__row"><span class="fstu-app-card__label">Освіта</span><span class="fstu-app-card__value" data-field="education">—</span></div>
                                <div class="fstu-app-card__row"><span class="fstu-app-card__label">Спортивна кваліфікація</span><span class="fstu-app-card__value" data-field="sailing_experience">—</span></div>
                                <div class="fstu-app-card__row"><span class="fstu-app-card__label">Суддівська категорія</span><span class="fstu-app-card__value" data-field="suddy_category">—</span></div>
                                <div class="fstu-app-card__row"><span class="fstu-app-card__label">Громадські звання</span><span class="fstu-app-card__value" data-field="public_tourism">—</span></div>
                            </div>
                        </div>
                    </section>

                    <section class="fstu-app-card__panel" id="fstu-app-tab-panel-payment" data-tab-panel="payment" role="tabpanel" aria-labelledby="fstu-app-tab-trigger-payment" hidden>
                        <div class="fstu-app-card__section fstu-app-card__section--payment">
                            <h4 class="fstu-app-card__title">Платіжні документи</h4>
                            <div class="fstu-app-card__grid">
                                <div class="fstu-app-card__row"><span class="fstu-app-card__label">Рік внеску</span><span class="fstu-app-card__value" data-field="payment_year">—</span></div>
                                <div class="fstu-app-card__row"><span class="fstu-app-card__label">Сума</span><span class="fstu-app-card__value" data-field="payment_amount">—</span></div>
                                <div class="fstu-app-card__row"><span class="fstu-app-card__label">Дата додавання</span><span class="fstu-app-card__value" data-field="payment_created_at">—</span></div>
                                <div class="fstu-app-card__row"><span class="fstu-app-card__label">Квитанція</span><a href="#" class="fstu-app-card__value fstu-app-card__link fstu-app-card__link--disabled" data-field="payment_receipt_url" target="_blank" rel="noopener noreferrer" aria-disabled="true">—</a></div>
                            </div>
                            <p class="fstu-hint" data-field="payment_message">Оплата має інформаційний характер. Деталі доступні в модулі PaymentDocs.</p>
                            <div class="fstu-form-actions">
                                <a href="#" class="fstu-btn" id="fstu-applications-payment-docs-link" target="_blank" rel="noopener noreferrer">Перейти в PaymentDocs</a>
                            </div>
                        </div>
                    </section>

                    <section class="fstu-app-card__panel" id="fstu-app-tab-panel-actions" data-tab-panel="actions" role="tabpanel" aria-labelledby="fstu-app-tab-trigger-actions" hidden>
                        <div class="fstu-app-card__section">
                            <h4 class="fstu-app-card__title">Дії з кандидатом</h4>
                            <p class="fstu-hint">Швидкі дії відкривають відповідні модальні форми без повернення до таблиці.</p>
                            <div class="fstu-form-actions fstu-form-actions--start">
                                <button type="button" class="fstu-btn" id="fstu-applications-view-change-ofst">Змінити ОФСТ</button>
                                <button type="button" class="fstu-btn fstu-btn--primary" id="fstu-applications-view-accept">Прийняти</button>
                                <button type="button" class="fstu-btn" id="fstu-applications-view-reject">Відхилити</button>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>

