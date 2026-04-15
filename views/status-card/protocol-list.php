<?php
/**
 * View: Таблиця протоколу довідника статусів карток.
 * Version: 1.0.0
 * Date_update: 2026-04-15
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<table class="fstu-table fstu-table--protocol">
    <thead>
    <tr>
        <th class="fstu-th fstu-th--date">Дата</th>
        <th class="fstu-th fstu-th--type">Тип</th>
        <th class="fstu-th fstu-th--wide-name">
            <div class="fstu-th-with-search">
                <span>Операція</span>
                <input type="text" id="fstu-protocol-filter-text" class="fstu-input--in-header" placeholder="🔍 Пошук (повідомлення/ФІО)...">
            </div>
        </th>
        <th class="fstu-th fstu-th--status">Статус</th>
        <th class="fstu-th fstu-th--user">Користувач</th>
    </tr>
    </thead>
    <tbody id="fstu-status-card-protocol-tbody">
    </tbody>
</table>

<div class="fstu-pagination fstu-pagination--compact fstu-pagination--protocol">
    <div class="fstu-pagination__left">
        <label class="fstu-pagination__per-page-label" for="fstu-status-card-protocol-per-page">Показувати по:</label>
        <select id="fstu-status-card-protocol-per-page" class="fstu-select fstu-select--compact">
            <option value="10" selected>10</option>
            <option value="25">25</option>
            <option value="50">50</option>
        </select>
    </div>
    <div class="fstu-pagination__controls" id="fstu-status-card-protocol-pagination"></div>
    <div class="fstu-pagination__info" id="fstu-status-card-protocol-info"></div>
</div>