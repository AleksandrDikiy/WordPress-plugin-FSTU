<?php
/**
 * View: Модальне вікно додавання суддівства.
 * Version: 1.0.0
 * Date_update: 2026-04-12
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="fstu-modal-overlay fstu-hidden" id="fstu-personal-judging-modal" style="z-index: 100000;">
    <div class="fstu-modal" style="max-width: 500px;">
        <div class="fstu-modal__header">
            <h3 class="fstu-modal__title">Додати суддівську категорію</h3>
            <button type="button" class="fstu-modal__close" id="fstu-personal-judging-cancel-icon">×</button>
        </div>
        <form id="fstu-personal-judging-form">
            <div class="fstu-modal__body">
                <div id="fstu-personal-judging-alert" class="fstu-alert fstu-hidden" style="margin-bottom: 16px;"></div>

                <div class="fstu-form-group">
                    <label class="fstu-label">Категорія</label>
                    <select id="fstu-judging-category-id" name="category_id" class="fstu-input" required>
                        <option value="">Завантаження...</option>
                    </select>
                </div>

                <div class="fstu-form-group">
                    <label class="fstu-label">Номер наказу</label>
                    <input type="text" id="fstu-judging-order-num" class="fstu-input">
                </div>

                <div class="fstu-form-group">
                    <label class="fstu-label">Дата наказу</label>
                    <input type="date" id="fstu-judging-order-date" class="fstu-input">
                </div>

                <div class="fstu-form-group">
                    <label class="fstu-label">Посилання на наказ</label>
                    <input type="url" id="fstu-judging-order-url" class="fstu-input" placeholder="https://drive.google.com/..." data-toggle="tooltip" title="Копію наказу треба викласти на файлообмінник.">
                </div>
            </div>
            <div class="fstu-modal__footer fstu-personal-form__actions" style="padding: 14px 16px; border-top: 1px solid #e5e7eb;">
                <button type="button" class="fstu-btn fstu-btn--secondary fstu-btn--cancel" id="fstu-personal-judging-cancel">Скасувати</button>
                <button type="submit" class="fstu-btn fstu-btn--primary fstu-btn--save" id="fstu-personal-judging-submit">Додати</button>
            </div>
        </form>
    </div>
</div>