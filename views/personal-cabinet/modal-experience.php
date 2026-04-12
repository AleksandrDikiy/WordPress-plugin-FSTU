<?php
/**
 * View: Модальне вікно оновлення довідки за похід.
 * Version: 1.0.0
 * Date_update: 2026-04-12
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="fstu-modal-overlay fstu-hidden" id="fstu-personal-experience-modal" style="z-index: 100000;">
    <div class="fstu-modal" style="max-width: 500px;">
        <div class="fstu-modal__header">
            <h3 class="fstu-modal__title">Довідка за похід</h3>
            <button type="button" class="fstu-modal__close" id="fstu-personal-experience-cancel-icon">×</button>
        </div>
        <form id="fstu-personal-experience-form">
            <div class="fstu-modal__body">
                <div id="fstu-personal-experience-alert" class="fstu-alert fstu-hidden" style="margin-bottom: 16px;"></div>
                <input type="hidden" name="experience_id" id="fstu-experience-id">
                <div class="fstu-form-group">
                    <label class="fstu-label">Посилання на довідку (URL)</label>
                    <input type="url" id="fstu-experience-url" class="fstu-input" placeholder="https://drive.google.com/..." required>
                    <p class="fstu-form-help">Скопіюйте сюди посилання на відскановану довідку з файлообмінника.</p>
                </div>
            </div>
            <div class="fstu-modal__footer fstu-personal-form__actions" style="padding: 14px 16px; border-top: 1px solid #e5e7eb;">
                <button type="button" class="fstu-btn fstu-btn--secondary fstu-btn--cancel" id="fstu-personal-experience-cancel">Скасувати</button>
                <button type="submit" class="fstu-btn fstu-btn--primary fstu-btn--save" id="fstu-personal-experience-submit">Зберегти посилання</button>
            </div>
        </form>
    </div>
</div>