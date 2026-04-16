<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$permissions = \FSTU\Core\Capabilities::get_type_boat_permissions();
?>
<div class="fstu-detail-header">
    <button type="button" class="fstu-btn fstu-btn--grey fstu-btn--back-to-producers">
        <span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e( 'Назад', 'fstu' ); ?>
    </button>
    <h3 id="fstu-selected-producer-name"></h3>

    <?php if ( ! empty( $permissions['canManage'] ) ) : ?>
        <button type="button" class="fstu-btn fstu-btn--grey fstu-btn--add-boat">
            <span class="dashicons dashicons-plus"></span> <?php esc_html_e( 'Додати тип судна', 'fstu' ); ?>
        </button>
    <?php endif; ?>
</div>

<table class="fstu-table fstu-table--zebra">
    <thead>
    <tr class="fstu-tr--success">
        <th class="fstu-th">#</th>
        <th class="fstu-th">
            <div class="fstu-th-with-search">
                <span><?php esc_html_e( 'Тип судна', 'fstu' ); ?></span>
                <input type="text" id="fstu-boat-search" class="fstu-input--in-header" placeholder="🔍 Пошук...">
            </div>
        </th>
        <th class="fstu-th"><?php esc_html_e( 'Характеристики (S/L/B/W)', 'fstu' ); ?></th>
        <th class="fstu-th"><?php esc_html_e( 'Опис / URL', 'fstu' ); ?></th>
        <th class="fstu-th fstu-th--actions"><?php esc_html_e( 'Дії', 'fstu' ); ?></th>
    </tr>
    </thead>
    <tbody id="fstu-boats-tbody"></tbody>
</table>

<div class="fstu-pagination fstu-pagination--compact">
    <div class="fstu-pagination__left">
        <label class="fstu-pagination__per-page-label" for="fstu-boats-per-page"><?php esc_html_e( 'Показувати по:', 'fstu' ); ?></label>
        <select id="fstu-boats-per-page" class="fstu-select fstu-select--compact">
            <option value="10" selected>10</option>
            <option value="15">15</option>
            <option value="25">25</option>
            <option value="50">50</option>
        </select>
    </div>
    <div class="fstu-pagination__controls">
        <button type="button" class="fstu-btn--page fstu-prev-page-btn" id="fstu-boats-prev">«</button>
        <div id="fstu-boats-pages" style="display:flex; gap:4px;"></div>
        <button type="button" class="fstu-btn--page fstu-next-page-btn" id="fstu-boats-next">»</button>
    </div>
    <div class="fstu-pagination__info">
        <span id="fstu-boats-info"></span>
    </div>
</div>