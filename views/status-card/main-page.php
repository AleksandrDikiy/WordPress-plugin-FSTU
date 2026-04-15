<div class="fstu-wrapper fstu-status-card-module">
    <h2>Довідник статусів карток та квитків</h2>

    <div class="fstu-action-bar">
        <button type="button" id="fstu-btn-add" class="fstu-btn fstu-btn--add">
            <span class="fstu-icon">+</span> Додати запис
        </button>
        <button type="button" id="fstu-btn-protocol" class="fstu-btn fstu-btn--protocol">
            <span class="fstu-icon">📄</span> ПРОТОКОЛ
        </button>
        <button type="button" id="fstu-btn-dictionary" class="fstu-btn fstu-btn--dictionary" style="display: none;">
            ← ДОВІДНИК
        </button>
    </div>

    <div id="fstu-dictionary-container">
        <table class="fstu-table">
            <thead>
            <tr>
                <th class="fstu-th fstu-th--drag-handle" title="Сортування">#</th>
                <th class="fstu-th fstu-th--wide-name">
                    <div class="fstu-th-with-search">
                        <span>Найменування</span>
                        <input type="text" id="fstu-status-card-search" class="fstu-input--in-header" placeholder="🔍 Пошук...">
                    </div>
                </th>
                <th class="fstu-th fstu-th--actions">Дії</th>
            </tr>
            </thead>
            <tbody id="fstu-status-card-tbody">
            </tbody>
        </table>

        <div class="fstu-pagination fstu-pagination--compact">
            <div class="fstu-pagination__left">
                <label class="fstu-pagination__per-page-label" for="fstu-status-card-per-page">Показувати по:</label>
                <select id="fstu-status-card-per-page" class="fstu-select fstu-select--compact">
                    <option value="10" selected>10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
            <div class="fstu-pagination__controls" id="fstu-status-card-pagination"></div>
            <div class="fstu-pagination__info" id="fstu-status-card-info"></div>
        </div>
    </div>

    <div id="fstu-protocol-container" style="display: none;">
        <?php include FSTU_PLUGIN_DIR . 'views/status-card/protocol-list.php'; ?>
    </div>

</div>

<?php include FSTU_PLUGIN_DIR . 'views/status-card/modal-form.php'; ?>