<?php
/**
 * View: Модальне вікно додавання вітрильного внеску.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="fstu-modal-overlay fstu-hidden" id="fstu-personal-dues-sail-modal" style="z-index: 100000;">
    <div class="fstu-modal" style="max-width: 400px;">
        <div class="fstu-modal__header">
            <h3 class="fstu-modal__title">Додати оплату (вітрильництво)</h3>
            <button type="button" class="fstu-modal__close" id="fstu-personal-dues-sail-cancel-icon">×</button>
        </div>
        <form id="fstu-personal-dues-sail-form">
            <div class="fstu-modal__body">
                <div id="fstu-personal-dues-sail-alert" class="fstu-alert fstu-hidden" style="margin-bottom: 16px;"></div>

                <div class="fstu-form-group">
                    <label class="fstu-label">Рік сплати</label>
                    <input type="number" id="fstu-dues-sail-year" class="fstu-input" required min="2000" max="2100">
                </div>

                <div class="fstu-form-group">
                    <label class="fstu-label">Сума (грн)</label>
                    <input type="number" step="0.01" id="fstu-dues-sail-sum" class="fstu-input" required>
                </div>
            </div>
            <div class="fstu-modal__footer fstu-personal-form__actions" style="padding: 14px 16px; border-top: 1px solid #e5e7eb;">
                <button type="button" class="fstu-btn fstu-btn--secondary fstu-btn--cancel" id="fstu-personal-dues-sail-cancel">Скасувати</button>
                <button type="submit" class="fstu-btn fstu-btn--primary fstu-btn--save" id="fstu-personal-dues-sail-submit">Зберегти</button>
            </div>
        </form>
    </div>
</div>