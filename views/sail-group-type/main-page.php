<?php
/**
 * View: Головна сторінка довідника типів вітрильних залікових груп.
 * * Version: 1.0.0
 * Date_update: 2026-04-15
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="fstu-module-container fstu-sail-group-type-module">

    <div class="fstu-action-bar">
        <div class="fstu-action-bar__left">
            <button type="button" class="fstu-btn fstu-btn--default fstu-action-add-type" id="fstu-sgt-btn-add-type">
                <span class="dashicons dashicons-plus-alt2"></span> Додати тип
            </button>

            <button type="button" class="fstu-btn fstu-btn--default fstu-action-add-group" id="fstu-sgt-btn-add-group" style="display:none;">
                <span class="dashicons dashicons-plus-alt2"></span> Додати залікову групу
            </button>

            <button type="button" class="fstu-btn fstu-btn--default fstu-action-back" id="fstu-sgt-btn-back-to-types" style="display:none;">
                <span class="dashicons dashicons-arrow-left-alt"></span> ДО ТИПІВ ГРУП
            </button>
        </div>
        <div class="fstu-action-bar__right">
            <button type="button" class="fstu-btn fstu-btn--default fstu-action-protocol" id="fstu-sgt-btn-protocol">
                <span class="dashicons dashicons-media-document"></span> ПРОТОКОЛ
            </button>
        </div>
    </div>

    <div id="fstu-sgt-types-section" class="fstu-section--active">
        <?php require FSTU_PLUGIN_DIR . 'views/sail-group-type/table-types.php'; ?>
    </div>

    <div id="fstu-sgt-groups-section" style="display:none;">
        <div class="fstu-sgt-current-type-header" id="fstu-sgt-current-type-name" style="padding: 15px; font-weight: bold; background: #f0f0f1; border: 1px solid #ccd0d4; border-top: none; border-bottom: none;">
            Залікові групи: <span></span>
        </div>
        <?php require FSTU_PLUGIN_DIR . 'views/sail-group-type/table-groups.php'; ?>
    </div>

    <div id="fstu-sgt-protocol-section" style="display:none;">
        <?php require FSTU_PLUGIN_DIR . 'views/sail-group-type/protocol-table.php'; ?>
    </div>

    <?php require FSTU_PLUGIN_DIR . 'views/sail-group-type/modal-type.php'; ?>
    <?php require FSTU_PLUGIN_DIR . 'views/sail-group-type/modal-group.php'; ?>

</div>