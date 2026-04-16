<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<table class="fstu-table fstu-table--zebra">
    <thead>
    <tr class="fstu-tr--success">
        <th class="fstu-th fstu-th--id">#</th>
        <th class="fstu-th fstu-th--wide-name">
            <div class="fstu-th-with-search">
                <span><?php esc_html_e( 'Найменування виробника', 'fstu' ); ?></span>
                <input type="text" id="fstu-producer-search" class="fstu-input--in-header" placeholder="🔍 Пошук...">
            </div>
        </th>
        <th class="fstu-th"><?php esc_html_e( 'Телефон / Адреса', 'fstu' ); ?></th>
        <th class="fstu-th fstu-th--center"><?php esc_html_e( 'К-сть типів', 'fstu' ); ?></th>
        <th class="fstu-th fstu-th--actions"><?php esc_html_e( 'Дії', 'fstu' ); ?></th>
    </tr>
    </thead>
    <tbody id="fstu-producers-tbody"></tbody>
</table>

<div class="fstu-pagination fstu-pagination--compact">
    <div class="fstu-pagination__left">
        <label class="fstu-pagination__per-page-label" for="fstu-producers-per-page"><?php esc_html_e( 'Показувати по:', 'fstu' ); ?></label>
        <select id="fstu-producers-per-page" class="fstu-select fstu-select--compact">
            <option value="10" selected>10</option>
            <option value="15">15</option>
            <option value="25">25</option>
            <option value="50">50</option>
        </select>
    </div>
    <div class="fstu-pagination__controls">
        <button type="button" class="fstu-btn--page fstu-prev-page-btn" id="fstu-producers-prev">«</button>
        <div id="fstu-producers-pages" style="display:flex; gap:4px;"></div>
        <button type="button" class="fstu-btn--page fstu-next-page-btn" id="fstu-producers-next">»</button>
    </div>
    <div class="fstu-pagination__info">
        <span id="fstu-producers-info"></span>
    </div>
</div>