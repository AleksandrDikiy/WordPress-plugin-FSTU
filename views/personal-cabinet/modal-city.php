<?php
/**
 * View: Модальне вікно додавання міста.
 * Version: 1.0.0
 * Date_update: 2026-04-11
*/
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="fstu-modal-overlay fstu-hidden" id="fstu-personal-city-modal" style="z-index: 100000;">
    <div class="fstu-modal" style="max-width: 500px;">
        <div class="fstu-modal__header">
            <h3 class="fstu-modal__title">Додати місто</h3>
            <button type="button" class="fstu-modal__close" id="fstu-personal-city-cancel-icon">×</button>
        </div>
        <form id="fstu-personal-city-form">
            <div class="fstu-modal__body">
                <div id="fstu-personal-city-alert" class="fstu-alert fstu-hidden" style="margin-bottom: 16px;"></div>
                <div class="fstu-form-group">
                    <label class="fstu-label">Пошук міста</label>
                    <div class="fstu-autocomplete-wrapper" style="position: relative;">
                        <input type="text" id="fstu-city-search" class="fstu-input" placeholder="Введіть назву міста..." autocomplete="off" required>
                        <input type="hidden" name="city_id" id="fstu-city-id">
                        <div id="fstu-city-dropdown" class="fstu-hidden" style="position: absolute; top: 100%; left: 0; right: 0; max-height: 200px; overflow-y: auto; background: #fff; border: 1px solid #d1d5db; z-index: 20; box-shadow: 0 4px 10px rgba(0,0,0,0.1);"></div>
                    </div>
                    <p class="fstu-form-help">Виберіть місто зі списку (вказується разом з областю).</p>
                </div>
            </div>
            <div class="fstu-modal__footer fstu-personal-form__actions" style="padding: 14px 16px; border-top: 1px solid #e5e7eb;">
                <button type="button" class="fstu-btn fstu-btn--secondary fstu-btn--cancel" id="fstu-personal-city-cancel">Скасувати</button>
                <button type="submit" class="fstu-btn fstu-btn--primary fstu-btn--save" id="fstu-personal-city-submit">Додати</button>
            </div>
        </form>
    </div>
</div>