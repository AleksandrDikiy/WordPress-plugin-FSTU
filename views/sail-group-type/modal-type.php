<?php
/**
 * View: Модальне вікно для додавання/редагування Типу залікової групи.
 * * Version: 1.0.0
 * Date_update: 2026-04-15
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="fstu-modal-overlay" id="fstu-sgt-modal-type" style="display: none;">
    <div class="fstu-modal">
        <div class="fstu-modal-header">
            <h3 class="fstu-modal-title" id="fstu-sgt-modal-type-title">Додати тип</h3>
            <button type="button" class="fstu-modal-close" aria-label="Закрити">×</button>
        </div>
        <div class="fstu-modal-body">
            <form id="fstu-sgt-type-form">
                <input type="text" name="fstu_website" class="fstu-hidden-field" style="display:none;" tabindex="-1" autocomplete="off">

                <input type="hidden" name="type_id" id="fstu-sgt-type-id" value="0">

                <div class="fstu-form-group">
                    <label for="fstu-sgt-type-name" class="fstu-form-label">Найменування типу <span class="fstu-required">*</span></label>
                    <input type="text" name="name" id="fstu-sgt-type-name" class="fstu-form-control" required maxlength="255" autocomplete="off">
                </div>
            </form>
        </div>
        <div class="fstu-modal-footer">
            <button type="button" class="fstu-btn fstu-btn--cancel fstu-modal-close-btn">Скасувати</button>
            <button type="submit" form="fstu-sgt-type-form" class="fstu-btn fstu-btn--save" id="fstu-sgt-btn-save-type">Зберегти</button>
        </div>
    </div>
</div>