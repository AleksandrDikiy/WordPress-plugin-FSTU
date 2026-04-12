<?php
/**
 * View: Модальне вікно редагування профілю.
 * Version: 1.0.0
 * Date_update: 2026-04-12
*/
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="fstu-modal-overlay fstu-hidden" id="fstu-personal-edit-modal" style="z-index: 100000;">
    <div class="fstu-modal" style="max-width: 900px;">
        <div class="fstu-modal__header">
            <h3 class="fstu-modal__title">Редагування профілю</h3>
            <button type="button" class="fstu-modal__close" id="fstu-personal-edit-modal-close">×</button>
        </div>
        <div class="fstu-modal__body" style="max-height: 70vh; overflow-y: auto; padding: 0;">
            <div id="fstu-personal-edit-alert" class="fstu-alert fstu-hidden" style="margin: 16px 16px 0;"></div>
            
            <div class="fstu-personal-tabs">
                <div class="fstu-personal-tabs__nav" style="padding: 16px 16px 0; border-bottom: 1px solid #e5e7eb;" role="tablist">
                    <button type="button" class="fstu-personal-tabs__btn fstu-personal-tabs__btn--active" data-edit-tab="general">Загальні</button>
                    <button type="button" class="fstu-personal-tabs__btn" data-edit-tab="private">Приватне</button>
                    <button type="button" class="fstu-personal-tabs__btn" data-edit-tab="service">Службове</button>
                </div>
                <div class="fstu-personal-tabs__content" style="border: none; border-radius: 0;">
                    <form id="fstu-personal-edit-form" class="fstu-personal-form fstu-personal-form--compact" style="padding: 16px;">
                        </form>
                </div>
            </div>
        </div>
        <div class="fstu-modal__footer fstu-personal-form__actions" style="padding: 14px 16px; border-top: 1px solid #e5e7eb;">
            <button type="button" class="fstu-btn fstu-btn--secondary fstu-btn--cancel" id="fstu-personal-edit-cancel">Скасувати</button>
            <button type="submit" form="fstu-personal-edit-form" class="fstu-btn fstu-btn--primary fstu-btn--save" id="fstu-personal-edit-submit">Зберегти зміни</button>
        </div>
    </div>
</div>