<?php
/**
 * View: Модальне вікно додавання клубу.
 * Version: 1.0.0
  * Date_update: 2026-04-11
*/
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="fstu-modal-overlay fstu-hidden" id="fstu-personal-club-modal" style="z-index: 100000;">
    <div class="fstu-modal" style="max-width: 450px;">
        <div class="fstu-modal__header">
            <h3 class="fstu-modal__title">Додати клуб</h3>
            <button type="button" class="fstu-modal__close" id="fstu-personal-club-cancel-icon">×</button>
        </div>
        <form id="fstu-personal-club-form">
            <div class="fstu-modal__body">
                <div id="fstu-personal-club-alert" class="fstu-alert fstu-hidden" style="margin-bottom: 16px;"></div>
                <div class="fstu-form-group">
                    <label class="fstu-label">Назва клубу</label>
                    <div class="fstu-autocomplete-wrapper" style="position: relative;">
                        <input type="text" id="fstu-club-search" class="fstu-input" placeholder="Почніть вводити назву клубу..." autocomplete="off" required>
                        <input type="hidden" name="club_id" id="fstu-club-id">
                        
                        <div id="fstu-club-dropdown" class="fstu-hidden" style="position: absolute; top: 100%; left: 0; right: 0; max-height: 220px; overflow-y: auto; background: #ffffff; border: 1px solid #d1d5db; border-top: none; z-index: 10; border-radius: 0 0 6px 6px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);"></div>
                    </div>
                    <p class="fstu-form-help">Виберіть клуб зі списку підказок.</p>
                </div>
            </div>
            <div class="fstu-modal__footer fstu-personal-form__actions" style="padding: 14px 16px; border-top: 1px solid #e5e7eb;">
                <button type="button" class="fstu-btn fstu-btn--secondary fstu-btn--cancel" id="fstu-personal-club-cancel">Скасувати</button>
                <button type="submit" class="fstu-btn fstu-btn--primary fstu-btn--save" id="fstu-personal-club-submit">Додати</button>
            </div>
        </form>
    </div>
</div>