<?php
/**
 * View: Таблиця підгруп (дочірня).
 * * Version: 1.0.0
 * Date_update: 2026-04-15
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="fstu-table-responsive">
    <table class="fstu-table fstu-table--striped fstu-table--hover" id="fstu-sgt-groups-table">
        <thead>
        <tr>
            <th class="fstu-th fstu-th--id">#</th>
            <th class="fstu-th fstu-th--wide-name">
                <div class="fstu-th-with-search">
                    <span>Найменування групи</span>
                    <input type="text" id="fstu-sgt-groups-search" class="fstu-input--in-header" placeholder="🔍 Пошук..." autocomplete="off">
                </div>
            </th>
            <th class="fstu-th fstu-text-center">Мін. площа</th>
            <th class="fstu-th fstu-text-center">Макс. площа</th>
            <th class="fstu-th fstu-text-center">Формула</th>
            <th class="fstu-th fstu-text-center">Стартова група</th>
            <th class="fstu-th fstu-text-center">Система заліку</th>
            <th class="fstu-th fstu-th--actions">Дії</th>
        </tr>
        </thead>
        <tbody id="fstu-sgt-groups-tbody">
        <tr><td colspan="8" class="fstu-text-center">Завантаження даних...</td></tr>
        </tbody>
    </table>
</div>

<div class="fstu-pagination fstu-pagination--compact">
    <div class="fstu-pagination__left">
        <label class="fstu-pagination__per-page-label" for="fstu-sgt-groups-per-page">Показувати по:</label>
        <select id="fstu-sgt-groups-per-page" class="fstu-select fstu-select--compact">
            <option value="10" selected>10</option>
            <option value="25">25</option>
            <option value="50">50</option>
        </select>
    </div>
    <div class="fstu-pagination__controls" id="fstu-sgt-groups-pagination"></div>
    <div class="fstu-pagination__info" id="fstu-sgt-groups-info"></div>
</div>