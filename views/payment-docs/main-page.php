<?php
/**
 * Головний шаблон модуля "Реєстр платіжних документів".
 *  Version:     1.0.0
 *  Date_update: 2026-04-05
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
    <div class="fstu-registry-wrap" id="fstu-payment-docs">

        <h1 class="fstu-registry-title">Реєстр платіжних документів</h1>
        <p class="fstu-registry-role-note">Ви увійшли як: <strong><?php echo esc_html( wp_get_current_user()->display_name ); ?></strong></p>

        <div class="fstu-action-bar">
            <button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-pd-btn-refresh">
                <span class="fstu-btn__icon">↻</span> Оновити
            </button>

            <button type="button" class="fstu-btn fstu-btn--secondary fstu-btn--open-modal" data-modal="fstu-modal-doc-editor" id="fstu-pd-btn-add">
                <span class="fstu-btn__icon">➕</span> Створити документ
            </button>
            <button type="button" class="fstu-btn fstu-btn--primary" id="fstu-pd-btn-yearly-dues" style="display: none; margin-left: 10px;">
                <span class="fstu-btn__icon">💳</span> Внески осередку (Portmone)
            </button>
        </div>

        <div class="fstu-filter-bar" style="margin-bottom: 15px; padding: 10px; background: var(--fstu-secondary); border-radius: var(--fstu-radius);">
            <div class="fstu-filter-row">

                <div class="fstu-filter-item fstu-filter-item--wide">
                    <select id="pd-filter-unit" class="fstu-select fstu-filter-trigger">
                        <option value="0">ВСІ ОСЕРЕДКИ</option>
                        <?php foreach ( $units as $u ) : ?>
                            <option value="<?php echo esc_attr( $u['Unit_ID'] ); ?>"><?php echo esc_html( $u['Unit_ShortName'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="fstu-filter-item fstu-filter-item--wide">
                    <select id="pd-filter-resp" class="fstu-select fstu-filter-trigger">
                        <option value="0">ВСІ ВІДПОВІДАЛЬНІ</option>
                        <?php foreach ( $resp_users as $ru ) : ?>
                            <option value="<?php echo esc_attr( $ru['User_ID'] ); ?>"><?php echo esc_html( $ru['FIO'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="fstu-filter-item fstu-filter-item--year">
                    <select id="pd-filter-year" class="fstu-select fstu-filter-trigger">
                        <option value="0">ВСІ РОКИ</option>
                        <?php foreach ( $years as $y ) : ?>
                            <option value="<?php echo esc_attr( $y ); ?>"><?php echo esc_html( $y ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>
        </div>

        <div class="fstu-table-wrap">
            <div class="fstu-loader fstu-hidden" id="fstu-pd-loader">
                <span class="fstu-loader__spinner"></span>
                <span class="fstu-loader__text">Завантаження...</span>
            </div>

            <table class="fstu-table">
                <thead class="fstu-thead">
                <tr>
                    <th class="fstu-th" style="width: 50px;">№</th>
                    <th class="fstu-th" style="width: 140px;">Дата</th>
                    <th class="fstu-th">Осередок</th>
                    <th class="fstu-th" style="width: 120px; text-align: right;">Сума док.</th>
                    <th class="fstu-th">Відповідальний</th>
                    <th class="fstu-th">Коментар</th>
                    <th class="fstu-th" style="width: 40px;">⚙️</th>
                </tr>
                </thead>
                <tbody class="fstu-tbody" id="fstu-pd-tbody">
                </tbody>
            </table>
        </div>

        <div class="fstu-pagination" id="fstu-pd-pagination" style="margin-top: 15px; border-top: 1px solid var(--fstu-border); padding-top: 15px; display: flex; justify-content: space-between; align-items: center;">

            <div class="fstu-pagination__info" id="fstu-pd-pagination-info" style="flex: 1;"></div>

            <div id="fstu-pd-total-sum-wrap" style="flex: 1; text-align: center; font-size: 14px; font-weight: 600; display: none;">
                Загальна сума: <span id="fstu-pd-total-sum-val" style="color: var(--fstu-primary); font-size: 16px;">0.00</span> ₴
            </div>

            <div style="display: flex; align-items: center; gap: 15px; flex: 1; justify-content: flex-end;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <label for="pd-filter-per-page" style="font-size: 14px; color: var(--fstu-text-light); margin: 0; white-space: nowrap;">Показувати по:</label>
                    <select id="pd-filter-per-page" class="fstu-select" style="width: 70px; height: 32px; padding: 2px 20px 2px 8px;">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="fstu-pagination__controls" id="fstu-pd-page-numbers"></div>
            </div>
        </div>

        <?php
        // Підключаємо модалку редактора
        include __DIR__ . '/modals/edit-doc.php';
        // Підключаємо модалку внесків осередку
        include __DIR__ . '/modals/yearly-dues.php';
        ?>
    </div>