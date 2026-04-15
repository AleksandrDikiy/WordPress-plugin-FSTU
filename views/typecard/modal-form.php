<?php
/**
 * View: Модальне вікно форми.
 * * Version: 1.0.0
 * Date_update: 2026-04-15
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="fstu-modal-overlay" id="fstu-type-card-modal" style="display: none;">
    <div class="fstu-modal">
        <div class="fstu-modal-header">
            <h3 class="fstu-modal-title" id="fstu-type-card-modal-title">Додати запис</h3>
            <button type="button" class="fstu-modal-close" aria-label="Закрити">×</button>
        </div>
        <div class="fstu-modal-body">
            <form id="fstu-type-card-form">
                <input type="text" name="fstu_website" class="fstu-hidden-field" style="display:none;" tabindex="-1" autocomplete="off">

                <input type="hidden" name="id" id="fstu-tc-id" value="0">

                <div class="fstu-form-group">
                    <label for="fstu-tc-name" class="fstu-form-label">Найменування <span class="fstu-required">*</span></label>
                    <input type="text" name="name" id="fstu-tc-name" class="fstu-form-control" required maxlength="255" autocomplete="off">
                </div>

                <div class="fstu-form-group">
                    <label for="fstu-tc-summa" class="fstu-form-label">Сума виготовлення (₴)</label>
                    <input type="number" name="summa" id="fstu-tc-summa" class="fstu-form-control" min="0" step="1" autocomplete="off" value="0">
                </div>

            </form>
        </div>
        <div class="fstu-modal-footer">
            <button type="button" class="fstu-btn fstu-btn--cancel fstu-modal-close-btn">Скасувати</button>
            <button type="submit" form="fstu-type-card-form" class="fstu-btn fstu-btn--save" id="fstu-type-card-btn-save">Зберегти</button>
        </div>
    </div>
</div>