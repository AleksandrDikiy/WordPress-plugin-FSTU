<?php
/**
 * Шаблон виводу таблиці протоколу (Logs) для модуля Статистики.
 * Version: 1.0.0
 * Date_update: 2026-04-25
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="fstu-table-responsive">
    <table class="fstu-table fstu-table--striped">
        <thead>
        <tr>
            <th class="fstu-th fstu-th--date">Дата</th>
            <th class="fstu-th fstu-th--type">Тип</th>
            <th class="fstu-th fstu-th--wide-name">
                <div class="fstu-th-with-search">
                    <span>Операція</span>
                    <input type="text" id="fstu-part-protocol-search" class="fstu-input--in-header" placeholder="🔍 Пошук...">
                </div>
            </th>
            <th class="fstu-th fstu-th--message">Повідомлення</th>
            <th class="fstu-th fstu-th--status">Статус</th>
            <th class="fstu-th fstu-th--user">Користувач</th>
        </tr>
        </thead>
        <tbody id="fstu-part-protocol-tbody">
        </tbody>
    </table>
</div>

<div class="fstu-pagination fstu-pagination--compact">
    <div class="fstu-pagination__left">
        <label class="fstu-pagination__per-page-label" for="fstu-part-protocol-per-page">Показувати по:</label>
        <select id="fstu-part-protocol-per-page" class="fstu-select fstu-select--compact">
            <option value="10" selected>10</option>
            <option value="25">25</option>
            <option value="50">50</option>
        </select>
    </div>
    <div class="fstu-pagination__controls" id="fstu-part-protocol-pagination"></div>
    <div class="fstu-pagination__info" id="fstu-part-protocol-info"></div>
</div>