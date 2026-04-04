<?php
/**
 * View: Модальне вікно "Протокол".
 *
 * Version:     1.0.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Registry\Views\Modals
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="fstu-modal-overlay fstu-hidden" id="fstu-modal-protocol" role="dialog" aria-modal="true" aria-labelledby="fstu-protocol-title">
    <div class="fstu-modal fstu-modal--large">
        <div class="fstu-modal__header">
            <h2 class="fstu-modal__title" id="fstu-protocol-title">Протокол</h2>
            <button type="button" class="fstu-modal-close-btn" aria-label="Закрити">✕</button>
        </div>
        <div class="fstu-modal__body">

            <div class="fstu-loader-inline fstu-hidden" id="fstu-protocol-loader">
                <span class="fstu-loader__spinner"></span> Формування протоколу...
            </div>

            <div class="fstu-alert fstu-hidden" id="fstu-protocol-alert"></div>

            <div id="fstu-protocol-content" class="fstu-hidden">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; background: #f8f9fa; padding: 10px; border-radius: 4px; border: 1px solid var(--fstu-border);">
                    <div class="form-inline">
                        <label style="font-size: 12px; margin-right: 5px;">Показувати по:</label>
                        <select id="fstu-protocol-per-page" class="fstu-filter-select" style="width: 70px; padding: 2px;">
                            <option value="10" selected>10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <button type="button" class="fstu-btn fstu-btn--primary" onclick="window.print()">🖨 Друк</button>
                </div>

                <table class="fstu-table" id="fstu-protocol-table">
                    <thead class="fstu-thead" id="fstu-protocol-thead">
                    <tr>
                        <th class="fstu-th">Дата</th>
                        <th class="fstu-th" style="text-align:center;">Тип</th>
                        <th class="fstu-th">Операція</th>
                        <th class="fstu-th">Повідомлення</th>
                        <th class="fstu-th">Статус</th>
                        <th class="fstu-th">Користувач</th>
                    </tr>
                    </thead>
                    <tbody class="fstu-tbody" id="fstu-protocol-tbody"></tbody>
                </table>

                <div class="fstu-pagination" id="fstu-protocol-pagination" style="margin-top: 15px; border-top: 1px solid var(--fstu-border); padding-top: 10px;">
                    <div class="fstu-pagination__info" id="fstu-protocol-pagin-info"></div>
                    <div class="fstu-pagination__controls">
                        <button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-protocol-prev" disabled>« Назад</button>
                        <span id="fstu-protocol-page-nums" style="margin: 0 10px; font-weight: 600; font-size: 12px;"></span>
                        <button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-protocol-next" disabled>Далі »</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>