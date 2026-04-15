<?php
/**
 * View: Головна сторінка довідника типів членських білетів.
 * * Version: 1.0.0
 * Date_update: 2026-04-15
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="fstu-module-container fstu-type-card-module">

    <div class="fstu-action-bar">
        <div class="fstu-action-bar__left">
            <button type="button" class="fstu-btn fstu-btn--default fstu-action-add" id="fstu-type-card-btn-add">
                <span class="dashicons dashicons-plus-alt2"></span> Додати запис
            </button>
            <button type="button" class="fstu-btn fstu-btn--default fstu-action-back" id="fstu-type-card-btn-back" style="display:none;">
                <span class="dashicons dashicons-arrow-left-alt"></span> ДОВІДНИК
            </button>
        </div>
        <div class="fstu-action-bar__right">
            <button type="button" class="fstu-btn fstu-btn--default fstu-action-protocol" id="fstu-type-card-btn-protocol">
                <span class="dashicons dashicons-media-document"></span> ПРОТОКОЛ
            </button>
        </div>
    </div>

    <div id="fstu-type-card-dictionary-section" class="fstu-section--active">
        <?php require FSTU_PLUGIN_DIR . 'views/typecard/table-list.php'; ?>
    </div>

    <div id="fstu-type-card-protocol-section" style="display:none;">
        <?php require FSTU_PLUGIN_DIR . 'views/typecard/protocol-table-list.php'; ?>
    </div>

    <?php require FSTU_PLUGIN_DIR . 'views/typecard/modal-form.php'; ?>

</div>