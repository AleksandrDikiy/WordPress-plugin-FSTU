<?php
/**
 * Шаблон головної сторінки виборів.
 * * Version: 1.0.0
 * Date_update: 2026-04-22
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

    <div class="fstu-module fstu-elections" id="fstu-elections-module">

    <div id="fstu-main-section">
    <div class="fstu-action-bar">
        <h2 class="fstu-module-title"><?php _e( 'Електронні вибори STV', 'fstu' ); ?></h2>
        <div class="fstu-action-buttons">
            <?php if ( $permissions['canManage'] ) : ?>
                <button type="button" class="fstu-btn fstu-btn--save" id="fstu-election-create-btn">
                    + <?php _e( 'Створити вибори', 'fstu' ); ?>
                </button>
            <?php endif; ?>
            <?php if ( $permissions['canProtocol'] ) : ?>
                <button type="button" class="fstu-btn fstu-btn--protocol" id="fstu-election-protocol-btn">
                    <?php _e( 'ПРОТОКОЛ', 'fstu' ); ?>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="fstu-module-content">
        <table class="fstu-table fstu-table--zebra">
            <thead>
            <tr class="fstu-tr--header">
                <th class="fstu-th--id">ID</th>
                <th class="fstu-th--wide-name">
                    <div class="fstu-th-with-search">
                        <span><?php _e( 'Назва виборів', 'fstu' ); ?></span>
                        <input type="text" id="fstu-election-search" class="fstu-input--in-header" placeholder="🔍 Пошук...">
                    </div>
                </th>
                <th class="fstu-th--status"><?php _e( 'Статус', 'fstu' ); ?></th>
                <th class="fstu-th--date"><?php _e( 'Голосування', 'fstu' ); ?></th>
                <th class="fstu-th--actions"><?php _e( 'Дії', 'fstu' ); ?></th>
            </tr>
            </thead>
            <tbody id="fstu-elections-tbody">
            <tr>
                <td colspan="5" style="text-align:center; padding: 20px;">
                    <?php _e( 'Завантаження...', 'fstu' ); ?>
                </td>
            </tr>
            </tbody>
        </table>

        <div class="fstu-pagination fstu-pagination--compact">
            <div class="fstu-pagination__left">
                <label class="fstu-pagination__per-page-label" for="fstu-elections-per-page"><?php _e( 'Показувати по:', 'fstu' ); ?></label>
                <select id="fstu-elections-per-page" class="fstu-select fstu-select--compact">
                    <option value="10" selected>10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
            <div class="fstu-pagination__controls" id="fstu-elections-pagination"></div>
            <div class="fstu-pagination__info" id="fstu-elections-info"></div>
        </div>
    </div>
    </div> <div id="fstu-protocol-section" style="display: none;">
        <div class="fstu-action-bar">
            <h2 class="fstu-module-title"><?php _e( 'ПРОТОКОЛ: Електронні вибори STV', 'fstu' ); ?></h2>
            <div class="fstu-action-buttons">
                <button type="button" class="fstu-btn fstu-btn--default" id="fstu-btn-back-to-module">
                    <?php _e( 'ДОВІДНИК', 'fstu' ); ?>
                </button>
            </div>
        </div>
        <div class="fstu-module-content">
            <table class="fstu-table fstu-table--zebra">
                <thead>
                <tr class="fstu-tr--header">
                    <th class="fstu-th fstu-th--date"><?php _e( 'Дата', 'fstu' ); ?></th>
                    <th class="fstu-th fstu-th--type" style="text-align:center;"><?php _e( 'Тип', 'fstu' ); ?></th>
                    <th class="fstu-th fstu-th--wide-name">
                        <div class="fstu-th-with-search">
                            <span><?php _e( 'Операція', 'fstu' ); ?></span>
                            <input type="text" id="fstu-protocol-search" class="fstu-input--in-header" placeholder="🔍 Пошук...">
                        </div>
                    </th>
                    <th class="fstu-th fstu-th--message"><?php _e( 'Повідомлення', 'fstu' ); ?></th>
                    <th class="fstu-th fstu-th--status" style="text-align:center;"><?php _e( 'Статус', 'fstu' ); ?></th>
                    <th class="fstu-th fstu-th--user"><?php _e( 'Користувач', 'fstu' ); ?></th>
                </tr>
                </thead>
                <tbody id="fstu-protocol-tbody">
                <tr><td colspan="6" style="text-align:center; padding: 20px;">Завантаження...</td></tr>
                </tbody>
            </table>

            <div class="fstu-pagination fstu-pagination--compact">
                <div class="fstu-pagination__left">
                    <label class="fstu-pagination__per-page-label" for="fstu-protocol-per-page"><?php _e( 'Показувати по:', 'fstu' ); ?></label>
                    <select id="fstu-protocol-per-page" class="fstu-select fstu-select--compact">
                        <option value="10" selected>10</option>
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
                <div class="fstu-pagination__controls" id="fstu-protocol-pagination"></div>
                <div class="fstu-pagination__info" id="fstu-protocol-info"></div>
            </div>
        </div>
    </div>

    <div id="fstu-modal-phase" class="fstu-modal" style="display: none;">
        <div class="fstu-modal-content" style="max-width: 400px;">
            <div class="fstu-modal-header">
                <h3>⚙ <?php _e( 'Управління фазами', 'fstu' ); ?></h3>
                <button type="button" class="fstu-modal-close" aria-label="<?php _e( 'Закрити', 'fstu' ); ?>">×</button>
            </div>
            <div class="fstu-modal-body">
                <form id="fstu-form-phase">
                    <input type="hidden" name="election_id" id="fstu-phase-election-id" value="">
                    <div class="fstu-form-group">
                        <label class="fstu-label"><?php _e( 'Статус виборів', 'fstu' ); ?></label>
                        <select name="status" id="fstu-phase-status" class="fstu-select">
                            <option value="draft">ЧЕРНЕТКА (draft)</option>
                            <option value="nomination">ВИСУНЕННЯ (nomination)</option>
                            <option value="voting">ГОЛОСУВАННЯ (voting)</option>
                            <option value="calculation">ПІДРАХУНОК (calculation)</option>
                            <option value="completed">ЗАВЕРШЕНО (completed)</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="fstu-modal-footer">
                <button type="button" class="fstu-btn fstu-btn--cancel fstu-modal-close-btn"><?php _e( 'Скасувати', 'fstu' ); ?></button>
                <button type="button" class="fstu-btn fstu-btn--save" id="fstu-btn-save-phase"><?php _e( 'Змінити статус', 'fstu' ); ?></button>
            </div>
        </div>
    </div>

<?php
// Підключення модальних вікон
if ( $permissions['canManage'] ) {
    include FSTU_PLUGIN_DIR . 'views/elections/modal-election.php';
}
include FSTU_PLUGIN_DIR . 'views/elections/modal-nomination.php';
include FSTU_PLUGIN_DIR . 'views/elections/modal-candidates.php';
include FSTU_PLUGIN_DIR . 'views/elections/voting-booth.php';
include FSTU_PLUGIN_DIR . 'views/elections/modal-report.php';
?>