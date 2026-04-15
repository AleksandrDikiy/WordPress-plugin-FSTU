<?php
/**
 * View: Таблиця довідника.
 * * Version: 1.0.0
 * Date_update: 2026-04-15
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="fstu-table-responsive">
    <table class="fstu-table fstu-table--striped fstu-table--hover" id="fstu-type-card-table">
        <thead>
        <tr>
            <th class="fstu-th fstu-th--handle" title="Сортування">⋮⋮</th>
            <th class="fstu-th fstu-th--id">#</th>
            <th class="fstu-th fstu-th--wide-name">
                <div class="fstu-th-with-search">
                    <span>Найменування</span>
                    <input type="text" id="fstu-type-card-search" class="fstu-input--in-header" placeholder="🔍 Пошук..." autocomplete="off">
                </div>
            </th>
            <th class="fstu-th fstu-th--summa">Сума виготовлення</th>
            <th class="fstu-th fstu-th--actions">Дії</th>
        </tr>
        </thead>
        <tbody id="fstu-type-card-tbody">
        <tr>
            <td colspan="5" class="fstu-text-center">Завантаження даних...</td>
        </tr>
        </tbody>
    </table>
</div>

<div class="fstu-pagination fstu-pagination--compact">
    <div class="fstu-pagination__left">
        <label class="fstu-pagination__per-page-label" for="fstu-type-card-per-page">Показувати по:</label>
        <select id="fstu-type-card-per-page" class="fstu-select fstu-select--compact">
            <option value="10" selected>10</option>
            <option value="15">15</option>
            <option value="25">25</option>
            <option value="50">50</option>
        </select>
    </div>
    <div class="fstu-pagination__controls" id="fstu-type-card-pagination"></div>
    <div class="fstu-pagination__info" id="fstu-type-card-info"></div>
</div>