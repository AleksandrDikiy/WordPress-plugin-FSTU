<?php
/**
 * View: Модальне вікно "Додавання членського внеску".
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="fstu-modal-overlay fstu-hidden" id="fstu-modal-add-dues" role="dialog" aria-modal="true">
    <div class="fstu-modal fstu-modal--small" style="max-width: 450px;">
        <div class="fstu-modal__header">
            <h2 class="fstu-modal__title">Додавання членського внеску</h2>
            <button type="button" class="fstu-modal-close-btn" aria-label="Закрити">✕</button>
        </div>
        <div class="fstu-modal__body">

            <div class="fstu-loader-inline fstu-hidden" id="fstu-dues-loader">
                <span class="fstu-loader__spinner"></span> Завантаження доступних років...
            </div>
            <div class="fstu-alert fstu-hidden" id="fstu-dues-alert"></div>

            <form id="fstu-add-dues-form" class="fstu-hidden">
                <input type="hidden" name="user_id" id="add_dues_user_id">

                <div class="fstu-form-group" style="margin-bottom: 12px;">
                    <label class="fstu-label">Рік сплати *</label>
                    <select name="year_id" id="add_dues_year" class="fstu-select" required>
                    </select>
                </div>

                <div class="fstu-form-group" style="margin-bottom: 12px;">
                    <label class="fstu-label">Сума *</label>
                    <input type="number" step="0.01" min="0" name="summa" id="add_dues_summa" class="fstu-input" placeholder="Наприклад: 25.00" required>
                </div>

                <div class="fstu-form-group" style="margin-bottom: 20px;">
                    <label class="fstu-label">Посилання на квитанцію (чек) *</label>
                    <input type="url" name="url" id="add_dues_url" class="fstu-input" placeholder="https://drive.google.com/..." required>
                    <small style="color: var(--fstu-text-light); margin-top: 4px; display: block; font-size: 11px;">
                        Копію квитанції треба викласти на файлообмінник (наприклад, Google Диск) і вставити сюди посилання.
                    </small>
                </div>

                <div style="text-align: right; border-top: 1px solid var(--fstu-border); padding-top: 15px;">
                    <button type="submit" class="fstu-btn fstu-btn--primary" id="fstu-dues-submit">
                        <span class="fstu-btn__text">💾 Зберегти квитанцію</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>