<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="fstu-table-responsive">
    <table class="fstu-table fstu-table--striped fstu-regional-table">
        <thead>
        <tr>
            <th class="fstu-th fstu-th--num" style="width: 50px;">№</th>
            <th class="fstu-th">Осередок</th>
            <th class="fstu-th">Посада</th>
            <th class="fstu-th fstu-th--wide-name">
                <div class="fstu-th-with-search">
                    <span>ПІБ</span>
                    <input type="text" id="fstu-regional-search" class="fstu-input--in-header" placeholder="🔍 Пошук..." autocomplete="off">
                </div>
            </th>
            <th class="fstu-th">Телефони</th>
            <th class="fstu-th">Email</th>
            <th class="fstu-th fstu-th--actions" style="width: 32px; text-align: center;">Дії</th>
        </tr>
        </thead>
        <tbody id="fstu-regional-tbody">
        </tbody>
    </table>
</div>

<div class="fstu-pagination fstu-pagination--compact" id="fstu-regional-pagination-wrap" style="display: none;">
    <div class="fstu-pagination__left">
        <label class="fstu-pagination__per-page-label" for="fstu-regional-per-page">Показувати по:</label>
        <select id="fstu-regional-per-page" class="fstu-select fstu-select--compact" style="width: 70px !important;">
            <option value="10" selected>10</option>
            <option value="15">15</option>
            <option value="25">25</option>
            <option value="50">50</option>
        </select>
    </div>
    <div class="fstu-pagination__controls" id="fstu-regional-pagination"></div>
    <div class="fstu-pagination__info" id="fstu-regional-info"></div>
</div>