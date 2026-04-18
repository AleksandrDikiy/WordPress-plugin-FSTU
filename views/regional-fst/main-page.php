<?php
/**
 * Головний шаблон модуля "Осередки федерації спортивного туризму".
 *
 * Version:     1.0.0
 * Date_update: 2026-04-18
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="fstu-module-wrapper fstu-regional-fst-module">

    <div id="fstu-regional-fst-map-section" class="fstu-map-section">
        <h2 class="fstu-section-title" style="text-align: center; color: #2c3e50; margin-bottom: 20px;">
            ОБЕРІТЬ РЕГІОН НА КАРТІ
        </h2>
        <?php include __DIR__ . '/map-filter.php'; ?>
    </div>

    <div id="fstu-regional-fst-action-bar" class="fstu-action-bar" style="display: none; justify-content: space-between; align-items: center; background: #f8f9fa; padding: 10px; border: 1px solid #d1d5db; margin-bottom: 15px;">
        <div class="fstu-action-bar__left" style="display: flex; gap: 10px;">
            <button type="button" class="fstu-btn fstu-btn--add" id="fstu-btn-add" style="display: none;">➕ ДОДАТИ</button>
            <a href="#" id="fstu-btn-dues" class="fstu-btn fstu-btn--secondary" target="_blank" style="display: none;">💳 РЕЄСТР ВНЕСКІВ</a>
        </div>
        <div class="fstu-action-bar__right">
            <button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-btn-protocol" style="display: none;">ПРОТОКОЛ</button>
            <button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-btn-back-to-directory" style="display: none;">ДОВІДНИК</button>
        </div>
    </div>

    <div id="fstu-regional-fst-table-section" style="display: none;">
        <h3 id="fstu-regional-current-title" style="margin-top: 0; color: #d9534f;"></h3>
        <?php include __DIR__ . '/table-list.php'; ?>
    </div>

    <div id="fstu-regional-fst-protocol-section" style="display: none;">
        <?php include __DIR__ . '/protocol-table-list.php'; ?>
    </div>

    <?php include __DIR__ . '/modal-form.php'; ?>

</div>