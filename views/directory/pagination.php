<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>

<div class="fstu-pagination fstu-pagination--compact">
    <div class="fstu-pagination__left">
        <label class="fstu-pagination__per-page-label" for="fstu-directory-per-page"><?php esc_html_e( 'Показувати по:', 'fstu' ); ?></label>
        <select id="fstu-directory-per-page" class="fstu-select fstu-select--compact">
            <option value="10" selected>10</option>
            <option value="25">25</option>
            <option value="50">50</option>
        </select>
    </div>

    <div class="fstu-pagination__controls" id="fstu-directory-pagination-controls">
    </div>

    <div class="fstu-pagination__info" id="fstu-directory-pagination-info">
    </div>
</div>