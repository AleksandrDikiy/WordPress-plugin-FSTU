<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>

<div class="fstu-pagination fstu-pagination--compact" style="margin-top: 20px;">
    <div class="fstu-pagination__left">
        <label class="fstu-pagination__per-page-label" for="fstu-veterans-per-page"><?php esc_html_e( 'Показувати по:', 'fstu' ); ?></label>
        <select id="fstu-veterans-per-page" class="fstu-select fstu-select--compact" autocomplete="off">
            <option value="10" selected>10</option>
            <option value="25">25</option>
            <option value="50">50</option>
        </select>
    </div>

    <div class="fstu-pagination__controls" id="fstu-veterans-pagination-controls"></div>
    <div class="fstu-pagination__info" id="fstu-veterans-pagination-info"></div>
</div>