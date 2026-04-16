<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>

    <div class="fstu-table-wrapper">
        <table class="fstu-table">
            <thead>
            <tr>
                <th class="fstu-th fstu-th--id">#</th>
                <th class="fstu-th fstu-th--date"><?php esc_html_e( 'Період', 'fstu' ); ?></th>
                <th class="fstu-th fstu-th--wide-name">
                    <div class="fstu-th-with-search">
                        <span><?php esc_html_e( 'Найменування опитування', 'fstu' ); ?></span>
                        <input type="text" id="fstu-poll-search" class="fstu-input--in-header" placeholder="🔍 Пошук...">
                    </div>
                </th>
                <th class="fstu-th fstu-th--count"><?php esc_html_e( 'Голосів', 'fstu' ); ?></th>
                <th class="fstu-th fstu-th--status"><?php esc_html_e( 'Статус', 'fstu' ); ?></th>
                <th class="fstu-th fstu-th--actions"><?php esc_html_e( 'Дії', 'fstu' ); ?></th>
            </tr>
            </thead>
            <tbody id="fstu-polls-tbody">
            </tbody>
        </table>
    </div>

<?php include FSTU_PLUGIN_DIR . 'views/directory/pagination.php'; ?>