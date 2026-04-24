<?php
/**
 * View: Головна сторінка модуля Directory (Виконком).
 *
 * Version:     1.2.0
 * Date_update: 2026-04-24
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
?>

<div id="fstu-directory-module" class="fstu-module" data-nonce="<?php echo esc_attr( wp_create_nonce('fstu_module_nonce') ); ?>">

    <div class="fstu-module__header">
        <h1 class="fstu-title"><?php echo esc_html( get_the_title() ); ?></h1>
    </div>

    <div class="fstu-action-bar" style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 10px; padding: 8px 12px; background: #f8f9fa; border: 1px solid #dcead6; border-radius: 6px; margin-bottom: 20px;">
        <div class="fstu-tabs" style="display: flex; gap: 6px; flex-shrink: 0; align-items: center;">
            <button type="button" class="fstu-tab-btn active" data-tab="members" style="height: 28px; padding: 0 12px; font-size: 12px; margin: 0; border-radius: 4px; box-sizing: border-box; display: flex; align-items: center;">СКЛАД ВИКОНКОМУ</button>
            <button type="button" class="fstu-tab-btn" data-tab="polls" style="height: 28px; padding: 0 12px; font-size: 12px; margin: 0; border-radius: 4px; box-sizing: border-box; display: flex; align-items: center;">ОПИТУВАННЯ</button>
            <?php if ( current_user_can('administrator') ) : ?>
                <button type="button" class="fstu-tab-btn fstu-tab-btn--protocol" data-tab="protocol" style="height: 28px; padding: 0 12px; font-size: 12px; margin: 0; border-radius: 4px; box-sizing: border-box; display: flex; align-items: center;">ПРОТОКОЛ</button>
            <?php endif; ?>
        </div>
    </div>

    <div id="fstu-directory-content" class="fstu-module__content">
        <div class="fstu-loader" style="display:none; text-align:center; padding: 20px;">
            <span class="spinner is-active"></span> <?php esc_html_e( 'Завантаження...', 'fstu' ); ?>
        </div>

        <div id="tab-members" class="fstu-tab-content active">
            <div id="fstu-directory-view-container"></div>
        </div>

        <div id="tab-polls" class="fstu-tab-content" style="display: none;">
            <div id="fstu-directory-polls-container"></div>

            <div class="fstu-pagination fstu-pagination--compact">
                <div class="fstu-pagination__left">
                    <label class="fstu-pagination__per-page-label" for="fstu-directory-polls-per-page">Показувати по:</label>
                    <select id="fstu-directory-polls-per-page" class="fstu-select fstu-select--compact" autocomplete="off">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
                <div class="fstu-pagination__controls" id="fstu-directory-polls-pagination"></div>
                <div class="fstu-pagination__info" id="fstu-directory-polls-info"></div>
            </div>
        </div>

        <?php if ( current_user_can('administrator') ) : ?>
            <div id="tab-protocol" class="fstu-tab-content" style="display: none;">
                <div class="fstu-table-responsive">
                    <table class="fstu-table fstu-table--striped">
                        <thead>
                        <tr>
                            <th class="fstu-th fstu-th--date">Дата</th>
                            <th class="fstu-th fstu-th--type" style="text-align: center;">Тип</th>
                            <th class="fstu-th fstu-th--wide-name">
                                <div class="fstu-th-with-search">
                                    <span>Операція / Користувач</span>
                                    <input type="text" id="fstu-directory-search-protocol" class="fstu-input--in-header" placeholder="🔍 Пошук по логам...">
                                </div>
                            </th>
                            <th class="fstu-th fstu-th--status" style="text-align: center;">Статус</th>
                        </tr>
                        </thead>
                        <tbody id="fstu-directory-protocol-tbody"></tbody>
                    </table>
                    <div class="fstu-pagination fstu-pagination--compact">
                        <div class="fstu-pagination__left">
                            <label class="fstu-pagination__per-page-label" for="fstu-directory-protocol-per-page">Показувати по:</label>
                            <select id="fstu-directory-protocol-per-page" class="fstu-select fstu-select--compact" autocomplete="off">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                        <div class="fstu-pagination__controls" id="fstu-directory-protocol-pagination"></div>
                        <div class="fstu-pagination__info" id="fstu-directory-protocol-info"></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>