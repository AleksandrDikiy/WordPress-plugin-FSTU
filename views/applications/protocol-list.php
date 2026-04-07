<?php
namespace FSTU\Modules\Applications;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="fstu-table-wrap">
    <table class="fstu-table" id="fstu-protocol-table">
        <thead>
        <tr>
            <th class="fstu-th fstu-th--date">Дата</th>
            <th class="fstu-th fstu-th--type">Тип</th>
            <th class="fstu-th fstu-th--wide-name">
                <div class="fstu-th-with-search">
                    <span>Операція</span>
                    <label for="fstu-protocol-filter-name" class="screen-reader-text">Пошук у протоколі</label>
                    <input type="text" id="fstu-protocol-filter-name" class="fstu-input--in-header" placeholder="🔍 Пошук по логах..." autocomplete="off">
                </div>
            </th>
            <th class="fstu-th fstu-th--message">Повідомлення</th>
            <th class="fstu-th fstu-th--status">Статус</th>
            <th class="fstu-th fstu-th--user">Користувач</th>
        </tr>
        </thead>
        <tbody id="fstu-protocol-tbody">
        <tr>
            <td colspan="6" class="fstu-table-state fstu-table-state--loading">
                Завантаження протоколу...
            </td>
        </tr>
        </tbody>
    </table>

    <div class="fstu-pagination fstu-pagination--compact">
        <div class="fstu-pagination__left">
            <label class="fstu-pagination__per-page-label" for="fstu-protocol-per-page">Показувати по:</label>
            <select id="fstu-protocol-per-page" class="fstu-select fstu-select--compact">
                <option value="10" selected>10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </div>

        <div class="fstu-pagination__controls" id="fstu-protocol-pages">
        </div>

        <div class="fstu-pagination__info" id="fstu-protocol-info">
        </div>
    </div>
</div>