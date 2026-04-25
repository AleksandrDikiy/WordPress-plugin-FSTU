<div class="fstu-table-responsive">
    <table class="fstu-table fstu-table--striped">
        <thead>
        <tr>
            <th class="fstu-th" width="50">№</th>
            <th class="fstu-th" width="100">Дата</th>
            <th class="fstu-th fstu-th--wide-name">
                <div class="fstu-th-with-search">
                    <span>Найменування заходу</span>
                    <input type="text" id="fstu-part-search" class="fstu-input--in-header" placeholder="🔍 Пошук...">
                </div>
            </th>
            <th class="fstu-th">Тип участі</th>
            <th class="fstu-th">Дистанція</th>
            <th class="fstu-th">Трек</th>
            <th class="fstu-th" width="60">Дії</th>
        </tr>
        </thead>
        <tbody id="fstu-part-tbody">
        </tbody>
    </table>
</div>

<div class="fstu-pagination fstu-pagination--compact">
    <div class="fstu-pagination__left">
        <label class="fstu-pagination__per-page-label">Показувати по:</label>
        <select id="fstu-part-per-page" class="fstu-select fstu-select--compact">
            <option value="10" selected>10</option>
            <option value="25">25</option>
            <option value="50">50</option>
        </select>
    </div>
    <div class="fstu-pagination__controls" id="fstu-part-pagination"></div>
    <div class="fstu-pagination__info" id="fstu-part-info"></div>
</div>