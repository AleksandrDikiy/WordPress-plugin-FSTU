<?php
/**
 * View: Модальне вікно додавання спортивного розряду.
 * Version: 1.0.0
 * Date_update: 2026-04-12
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="fstu-modal-overlay fstu-hidden" id="fstu-personal-rank-modal" style="z-index: 100000;">
    <div class="fstu-modal" style="max-width: 500px;">
        <div class="fstu-modal__header">
            <h3 class="fstu-modal__title">Додати спортивний розряд (звання)</h3>
            <button type="button" class="fstu-modal__close" id="fstu-personal-rank-cancel-icon">×</button>
        </div>
        <form id="fstu-personal-rank-form">
            <div class="fstu-modal__body">
                <div id="fstu-personal-rank-alert" class="fstu-alert fstu-hidden" style="margin-bottom: 16px;"></div>

                <div class="fstu-form-group">
                    <label class="fstu-label">Спортивний розряд (звання)</label>
                    <select id="fstu-rank-id" name="rank_id" class="fstu-input" required>
                        <option value="">Завантаження...</option>
                    </select>
                </div>

                <div class="fstu-form-group" style="margin-top: 15px;">
                    <label class="fstu-switch-wrapper" style="justify-content: flex-start; cursor: pointer; border: none; padding: 0;">
                        <label class="fstu-switch">
                            <input type="checkbox" id="fstu-rank-show-tourism">
                            <span class="fstu-slider"></span>
                        </label>
                        <span style="margin-left: 10px; font-size: 14px; font-weight: 500; color: #374151;">Вказати вид туризму</span>
                    </label>
                </div>

                <div class="fstu-form-group fstu-hidden" id="fstu-rank-tourism-group">
                    <label class="fstu-label">Вид туризму</label>
                    <select id="fstu-rank-tourism-id" name="tourism_id" class="fstu-input">
                        <option value="">Оберіть зі списку...</option>
                    </select>
                </div>

                <div class="fstu-form-group">
                    <label class="fstu-label">Номер наказу (без символа "№")</label>
                    <input type="text" id="fstu-rank-prikaz-num" class="fstu-input">
                </div>

                <div class="fstu-form-group">
                    <label class="fstu-label">Дата наказу</label>
                    <input type="date" id="fstu-rank-prikaz-date" class="fstu-input">
                </div>

                <div class="fstu-form-group">
                    <label class="fstu-label">Посилання на наказ</label>
                    <input type="url" id="fstu-rank-prikaz-url" class="fstu-input" placeholder="https://drive.google.com/..." data-toggle="tooltip" title="Копію наказу треба викласти на будь-який файлообмінник.">
                </div>
            </div>
            <div class="fstu-modal__footer fstu-personal-form__actions" style="padding: 14px 16px; border-top: 1px solid #e5e7eb;">
                <button type="button" class="fstu-btn fstu-btn--secondary fstu-btn--cancel" id="fstu-personal-rank-cancel">Скасувати</button>
                <button type="submit" class="fstu-btn fstu-btn--primary fstu-btn--save" id="fstu-personal-rank-submit">Зберегти</button>
            </div>
        </form>
    </div>
</div>