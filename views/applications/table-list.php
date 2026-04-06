<?php
namespace FSTU\Modules\Applications;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="fstu-table-wrap">
    <table class="fstu-table" id="fstu-applications-table">
        <thead>
        <tr>
            <th class="fstu-th fstu-th--num">№</th>
            <th class="fstu-th">Дата реєстрації</th>
            <th class="fstu-th fstu-th--wide-name">
                <div class="fstu-th-with-search">
                    <span>ПІБ Кандидата</span>
                    <label for="fstu-applications-search" class="screen-reader-text">Пошук по кандидатах</label>
                    <input type="text" id="fstu-applications-search" class="fstu-input--in-header" placeholder="🔍 Пошук..." autocomplete="off">
                </div>
            </th>
            <th class="fstu-th">Email</th>
            <th class="fstu-th">ОФСТ</th>
            <th class="fstu-th">Область</th>
            <th class="fstu-th fstu-th--center" title="Голосів Президії ОФСТ">Голосів</th>
            <th class="fstu-th fstu-th--actions">Дії</th>
        </tr>
        </thead>
        <tbody id="fstu-applications-tbody">
        <tr>
            <td colspan="8" style="text-align:center; padding: 20px; color: #7f8c8d;">
                Завантаження даних...
            </td>
        </tr>
        </tbody>
    </table>

    <div class="fstu-pagination fstu-pagination--compact">
        <div class="fstu-pagination__left">
            <label class="fstu-pagination__per-page-label" for="fstu-applications-per-page">Показувати по:</label>
            <select id="fstu-applications-per-page" class="fstu-select fstu-select--compact">
                <option value="10" selected>10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </div>

        <div class="fstu-pagination__controls" id="fstu-applications-pages">
        </div>

        <div class="fstu-pagination__info" id="fstu-applications-info">
        </div>
    </div>
</div>