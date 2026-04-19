<?php
/**
 * View: Головний HTML-каркас модуля "Board" (Комісії).
 * Дотримано стандартів AGENTS.md: жодних SQL-запитів, префікси .fstu-, flex-панель.
 * Вкладки мають відступи та висоту 28px, як і фільтри.
 *
 * Version:     1.4.1
 * Date_update: 2026-04-19
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * @var array $permissions Передано з Commissions_List
 * @var array $commissions Передано з Commissions_List
 */
?>
<div class="fstu-module-container fstu-board-module">

    <div class="fstu-action-bar">

        <div class="fstu-tabs" style="display: flex; gap: 6px; flex-shrink: 0; align-items: center;">
            <button type="button" class="fstu-tab-btn active" data-tab="members" style="height: 28px; padding: 0 12px; font-size: 12px; margin: 0; border-radius: 4px; box-sizing: border-box; display: flex; align-items: center;">ДОВІДНИК (СКЛАД)</button>
            <button type="button" class="fstu-tab-btn" data-tab="polls" style="height: 28px; padding: 0 12px; font-size: 12px; margin: 0; border-radius: 4px; box-sizing: border-box; display: flex; align-items: center;">ОПИТУВАННЯ</button>
            <?php if ( isset($permissions['canProtocol']) && $permissions['canProtocol'] ) : ?>
                <button type="button" class="fstu-tab-btn fstu-tab-btn--protocol" data-tab="protocol" style="height: 28px; padding: 0 12px; font-size: 12px; margin: 0; border-radius: 4px; box-sizing: border-box; display: flex; align-items: center; border-left: none;">ПРОТОКОЛ</button>
            <?php endif; ?>
        </div>

        <div class="fstu-filters">
            <div class="fstu-filter-group">
                <label for="fstu-board-filter-year">Рік:</label>
                <select id="fstu-board-filter-year" class="fstu-select fstu-select--sm fstu-select--year" autocomplete="off">
                    <option value="0" selected>Поточний</option>
                    <?php
                    $current_year = (int) date('Y');
                    for ( $y = $current_year + 1; $y >= 2010; $y-- ) {
                        echo "<option value='{$y}'>{$y}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="fstu-filter-group">
                <label for="fstu-board-filter-type">Тип:</label>
                <select id="fstu-board-filter-type" class="fstu-select fstu-select--sm fstu-select--type" autocomplete="off">
                    <option value="1" selected>Центральна</option>
                    <option value="2">Регіональна</option>
                </select>
            </div>

            <div class="fstu-filter-group">
                <label for="fstu-board-filter-commission">Комісія:</label>
                <select id="fstu-board-filter-commission" class="fstu-select fstu-select--sm fstu-select--commission" autocomplete="off">
                    <?php
                    if ( ! empty( $commissions ) ) {
                        foreach ( $commissions as $c ) {
                            $selected = ( (int)$c->Commission_ID === 1 ) ? 'selected' : '';
                            echo '<option value="' . esc_attr( $c->Commission_ID ) . '" ' . $selected . '>' . esc_html( $c->Commission_Name ) . '</option>';
                        }
                    } else {
                        global $wpdb;
                        $fallback = $wpdb->get_results("SELECT Commission_ID, Commission_Name FROM S_Commission ORDER BY Commission_Name");
                        foreach ( $fallback as $c ) {
                            $selected = ( (int)$c->Commission_ID === 1 ) ? 'selected' : '';
                            echo '<option value="' . esc_attr( $c->Commission_ID ) . '" ' . $selected . '>' . esc_html( $c->Commission_Name ) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>

            <?php if ( isset($permissions['canManage']) && $permissions['canManage'] ) : ?>
                <button type="button" class="fstu-btn fstu-btn--sm fstu-btn--add" id="fstu-board-add-btn">
                    Додати
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div id="tab-members" class="fstu-tab-content active">

        <div class="fstu-table-responsive">
            <table class="fstu-table fstu-table--striped">
                <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">№</th>
                    <th>Роль у комісії</th>
                    <th class="fstu-th fstu-th--wide-name">
                        <div class="fstu-th-with-search">
                            <span>ПІБ</span>
                            <input type="text" id="fstu-board-search-member" class="fstu-input--in-header" autocomplete="off" placeholder="🔍 Пошук...">
                        </div>
                    </th>
                    <?php if ( isset($permissions['canViewContacts']) && $permissions['canViewContacts'] ) : ?>
                        <th>Телефони</th>
                        <th>e-mail</th>
                    <?php endif; ?>
                    <?php if ( isset($permissions['canManage']) && $permissions['canManage'] ) : ?>
                        <th style="width: 60px; text-align: center;">Дії</th>
                    <?php endif; ?>
                </tr>
                </thead>
                <tbody id="fstu-board-members-tbody"></tbody>
            </table>

            <div class="fstu-pagination fstu-pagination--compact">
                <div class="fstu-pagination__left">
                    <label class="fstu-pagination__per-page-label" for="fstu-board-members-per-page">Показувати по:</label>
                    <select id="fstu-board-members-per-page" class="fstu-select fstu-select--compact" autocomplete="off">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
                <div class="fstu-pagination__controls" id="fstu-board-members-pagination"></div>
                <div class="fstu-pagination__info" id="fstu-board-members-info"></div>
            </div>
        </div>

        <div id="fstu-board-commission-info" class="fstu-board-info-box"></div>

    </div>

    <div id="tab-polls" class="fstu-tab-content" style="display: none;">
        <div id="fstu-board-polls-container"></div>
    </div>

    <?php if ( isset($permissions['canProtocol']) && $permissions['canProtocol'] ) : ?>
        <div id="tab-protocol" class="fstu-tab-content" style="display: none;">
            <div class="fstu-table-responsive">
                <table class="fstu-table fstu-table--striped">
                    <thead>
                    <tr>
                        <th class="fstu-th--date">Дата</th>
                        <th class="fstu-th--type">Тип</th>
                        <th class="fstu-th fstu-th--wide-name">
                            <div class="fstu-th-with-search">
                                <span>Операція / Користувач</span>
                                <input type="text" id="fstu-board-search-protocol" class="fstu-input--in-header" autocomplete="off" placeholder="🔍 Пошук по логам...">
                            </div>
                        </th>
                        <th class="fstu-th--status">Статус</th>
                    </tr>
                    </thead>
                    <tbody id="fstu-board-protocol-tbody"></tbody>
                </table>
                <div class="fstu-pagination fstu-pagination--compact">
                    <div class="fstu-pagination__left">
                        <label class="fstu-pagination__per-page-label" for="fstu-board-protocol-per-page">Показувати по:</label>
                        <select id="fstu-board-protocol-per-page" class="fstu-select fstu-select--compact" autocomplete="off">
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                    <div class="fstu-pagination__controls" id="fstu-board-protocol-pagination"></div>
                    <div class="fstu-pagination__info" id="fstu-board-protocol-info"></div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div id="fstu-board-modals-container"></div>
</div>