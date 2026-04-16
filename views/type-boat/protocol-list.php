<?php
/**
 * View: Таблиця протоколу
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<table class="fstu-table fstu-table--zebra">
    <thead>
    <tr class="fstu-tr--success">
        <th class="fstu-th fstu-th--date"><?php esc_html_e('Дата', 'fstu'); ?></th>
        <th class="fstu-th fstu-th--type"><?php esc_html_e('Тип', 'fstu'); ?></th>
        <th class="fstu-th fstu-th--wide-name">
            <div class="fstu-th-with-search">
                <span><?php esc_html_e('Операція', 'fstu'); ?></span>
                <input type="text" id="fstu-protocol-search" class="fstu-input--in-header" placeholder="🔍 Пошук...">
            </div>
        </th>
        <th class="fstu-th fstu-th--message"><?php esc_html_e('Повідомлення', 'fstu'); ?></th>
        <th class="fstu-th fstu-th--status"><?php esc_html_e('Статус', 'fstu'); ?></th>
        <th class="fstu-th fstu-th--user"><?php esc_html_e('Користувач', 'fstu'); ?></th>
    </tr>
    </thead>
    <tbody id="fstu-protocol-tbody"></tbody>
</table>

<div class="fstu-pagination fstu-pagination--compact">
    <div class="fstu-pagination__left">
        <label class="fstu-pagination__per-page-label" for="fstu-protocol-per-page"><?php esc_html_e( 'Показувати по:', 'fstu' ); ?></label>
        <select id="fstu-protocol-per-page" class="fstu-select fstu-select--compact">
            <option value="10" selected>10</option>
            <option value="15">15</option>
            <option value="25">25</option>
            <option value="50">50</option>
        </select>
    </div>
    <div class="fstu-pagination__controls">
        <button type="button" class="fstu-btn--page fstu-prev-page-btn" id="fstu-protocol-prev">«</button>
        <div id="fstu-protocol-pages" style="display:flex; gap:4px;"></div>
        <button type="button" class="fstu-btn--page fstu-next-page-btn" id="fstu-protocol-next">»</button>
    </div>
    <div class="fstu-pagination__info">
        <span id="fstu-protocol-info"></span>
    </div>
</div>