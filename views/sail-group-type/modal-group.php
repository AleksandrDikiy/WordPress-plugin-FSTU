<?php
/**
 * View: Модальне вікно для додавання/редагування конкретної Залікової групи.
 * * Version: 1.0.0
 * Date_update: 2026-04-15
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="fstu-modal-overlay" id="fstu-sgt-modal-group" style="display: none;">
    <div class="fstu-modal">
        <div class="fstu-modal-header">
            <h3 class="fstu-modal-title" id="fstu-sgt-modal-group-title">Додати залікову групу</h3>
            <button type="button" class="fstu-modal-close" aria-label="Закрити">×</button>
        </div>
        <div class="fstu-modal-body">
            <form id="fstu-sgt-group-form">
                <input type="text" name="fstu_website" class="fstu-hidden-field" style="display:none;" tabindex="-1" autocomplete="off">

                <input type="hidden" name="group_id" id="fstu-sgt-group-id" value="0">
                <input type="hidden" name="parent_type_id" id="fstu-sgt-parent-type-id" value="0">

                <div class="fstu-form-group">
                    <label for="fstu-sgt-group-name" class="fstu-form-label">Найменування групи <span class="fstu-required">*</span></label>
                    <input type="text" name="name" id="fstu-sgt-group-name" class="fstu-form-control" required maxlength="255" autocomplete="off">
                </div>

                <div class="fstu-form-group" style="display:flex; gap: 10px;">
                    <div style="flex:1;">
                        <label for="fstu-sgt-group-code-min" class="fstu-form-label">Мін. площа</label>
                        <input type="text" name="code_min" id="fstu-sgt-group-code-min" class="fstu-form-control" autocomplete="off">
                    </div>
                    <div style="flex:1;">
                        <label for="fstu-sgt-group-code-max" class="fstu-form-label">Макс. площа</label>
                        <input type="text" name="code_max" id="fstu-sgt-group-code-max" class="fstu-form-control" autocomplete="off">
                    </div>
                </div>

                <div class="fstu-form-group">
                    <label for="fstu-sgt-group-formula" class="fstu-form-label">Формула (1=IOR-TOTD / 2=по приходу)</label>
                    <input type="text" name="formula" id="fstu-sgt-group-formula" class="fstu-form-control" autocomplete="off">
                </div>

                <div class="fstu-form-group" style="display:flex; gap: 10px;">
                    <div style="flex:1;">
                        <label for="fstu-sgt-group-starting" class="fstu-form-label">Стартова група</label>
                        <input type="text" name="starting_group" id="fstu-sgt-group-starting" class="fstu-form-control" autocomplete="off">
                    </div>
                    <div style="flex:1;">
                        <label for="fstu-sgt-group-scoring" class="fstu-form-label">Система заліку</label>
                        <input type="text" name="scoring_system" id="fstu-sgt-group-scoring" class="fstu-form-control" autocomplete="off">
                    </div>
                </div>
            </form>
        </div>
        <div class="fstu-modal-footer">
            <button type="button" class="fstu-btn fstu-btn--cancel fstu-modal-close-btn">Скасувати</button>
            <button type="submit" form="fstu-sgt-group-form" class="fstu-btn fstu-btn--save" id="fstu-sgt-btn-save-group">Зберегти</button>
        </div>
    </div>
</div>