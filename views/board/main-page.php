<?php
/**
 * View: Головний HTML-каркас модуля "Board" (Комісії).
 * Шлях: views/board/main-page.php
 *
 * Version:     1.0.1
 * Date_update: 2026-04-18
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Змінна $permissions передається з контролера Commissions_List
?>
<div class="fstu-module-container fstu-board-module">

    <div class="fstu-action-bar">
        <div class="fstu-tabs">
            <button type="button" class="fstu-tab-btn active" data-tab="members">ДОВІДНИК (СКЛАД)</button>
            <button type="button" class="fstu-tab-btn" data-tab="polls">ОПИТУВАННЯ</button>
            <?php if ( $permissions['canProtocol'] ) : ?>
                <button type="button" class="fstu-tab-btn fstu-tab-btn--protocol" data-tab="protocol">ПРОТОКОЛ</button>
            <?php endif; ?>
        </div>

        <div class="fstu-actions-right">
            <?php if ( $permissions['canManage'] ) : ?>
                <button type="button" class="fstu-btn fstu-btn--add" id="fstu-board-add-btn">
                    <span class="fstu-icon fstu-icon-plus"></span> Додати
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="fstu-filter-bar">
        <div class="fstu-filter-group">
            <label for="fstu-board-filter-year">Рік:</label>
            <select id="fstu-board-filter-year" class="fstu-select">
                <?php
                $current_year = (int) date('Y');
                for ( $y = $current_year + 1; $y >= 2010; $y-- ) {
                    $selected = ( $y === $current_year ) ? 'selected' : '';
                    echo "<option value='{$y}' {$selected}>{$y}</option>";
                }
                ?>
            </select>
        </div>

        <div class="fstu-filter-group">
            <label for="fstu-board-filter-type">Тип комісії:</label>
            <select id="fstu-board-filter-type" class="fstu-select">
                <option value="1">Центральна</option>
                <option value="2">Регіональна</option>
            </select>
        </div>

        <div class="fstu-filter-group" id="fstu-board-region-group" style="display: none;">
            <label for="fstu-board-filter-region">Регіон:</label>
            <select id="fstu-board-filter-region" class="fstu-select">
                <?php
                global $wpdb;
                $regions = $wpdb->get_results("SELECT Region_ID, Region_Name FROM S_Region ORDER BY Region_Name");
                if ( $regions ) {
                    foreach ( $regions as $r ) {
                        // За замовчуванням вибираємо 30 (Київ), як було в legacy
                        $selected = ( $r->Region_ID == 30 ) ? 'selected' : '';
                        echo '<option value="' . esc_attr( $r->Region_ID ) . '" ' . $selected . '>' . esc_html( $r->Region_Name ) . '</option>';
                    }
                }
                ?>
            </select>
        </div>

        <div class="fstu-filter-group">
            <label for="fstu-board-filter-commission">Комісія:</label>
            <select id="fstu-board-filter-commission" class="fstu-select">
                <?php
                $commissions = $wpdb->get_results("SELECT Commission_ID, Commission_Name FROM S_Commission ORDER BY Commission_Name");
                if ( $commissions ) {
                    foreach ( $commissions as $c ) {
                        echo '<option value="' . esc_attr( $c->Commission_ID ) . '">' . esc_html( $c->Commission_Name ) . '</option>';
                    }
                }
                ?>
            </select>
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
                            <input type="text" id="fstu-board-search-member" class="fstu-input--in-header" placeholder="🔍 Пошук...">
                        </div>
                    </th>
                    <?php if ( $permissions['canViewContacts'] ) : ?>
                        <th>Телефони</th>
                        <th>e-mail</th>
                    <?php endif; ?>
                    <?php if ( $permissions['canManage'] ) : ?>
                        <th style="width: 60px; text-align: center;">Дії</th>
                    <?php endif; ?>
                </tr>
                </thead>
                <tbody id="fstu-board-members-tbody">
                </tbody>
            </table>

            <div class="fstu-pagination fstu-pagination--compact">
                <div class="fstu-pagination__left">
                    <label class="fstu-pagination__per-page-label" for="fstu-board-members-per-page">Показувати по:</label>
                    <select id="fstu-board-members-per-page" class="fstu-select fstu-select--compact">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="fstu-pagination__controls" id="fstu-board-members-pagination"></div>
                <div class="fstu-pagination__info" id="fstu-board-members-info"></div>
            </div>
        </div>
    </div>

    <div id="tab-polls" class="fstu-tab-content" style="display: none;">
        <div id="fstu-board-polls-container"></div>
    </div>

    <?php if ( $permissions['canProtocol'] ) : ?>
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
                                <input type="text" id="fstu-board-search-protocol" class="fstu-input--in-header" placeholder="🔍 Пошук по логам...">
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
                        <select id="fstu-board-protocol-per-page" class="fstu-select fstu-select--compact">
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