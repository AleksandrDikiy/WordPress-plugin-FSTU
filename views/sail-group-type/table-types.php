<?php
/**
 * View: Таблиця типів залікових груп (батьківська).
 * * Version: 1.0.0
 * Date_update: 2026-04-15
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="fstu-table-responsive">
    <table class="fstu-table fstu-table--striped fstu-table--hover" id="fstu-sgt-types-table">
        <thead>
        <tr>
            <th class="fstu-th fstu-th--handle" title="Сортування">⋮⋮</th>
            <th class="fstu-th fstu-th--id">#</th>
            <th class="fstu-th fstu-th--wide-name">
                <div class="fstu-th-with-search">
                    <span>Найменування типу</span>
                    <input type="text" id="fstu-sgt-types-search" class="fstu-input--in-header" placeholder="🔍 Пошук..." autocomplete="off">
                </div>
            </th>
            <th class="fstu-th fstu-th--actions">Дії</th>
        </tr>
        </thead>
        <tbody id="fstu-sgt-types-tbody">
        <tr><td colspan="4" class="fstu-text-center">Завантаження даних...</td></tr>
        </tbody>
    </table>
</div>

<div class="fstu-pagination fstu-pagination--compact">
    <div class="fstu-pagination__left">
        <label class="fstu-pagination__per-page-label" for="fstu-sgt-types-per-page">Показувати по:</label>
        <select id="fstu-sgt-types-per-page" class="fstu-select fstu-select--compact">
            <option value="10" selected>10</option>
            <option value="25">25</option>
            <option value="50">50</option>
        </select>
    </div>
    <div class="fstu-pagination__controls" id="fstu-sgt-types-pagination"></div>
    <div class="fstu-pagination__info" id="fstu-sgt-types-info"></div>
</div>