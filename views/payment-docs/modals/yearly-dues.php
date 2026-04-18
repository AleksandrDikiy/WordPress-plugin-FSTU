<?php
/**
 * View: Модальне вікно для сплати щорічних внесків осередку (Portmone).
 *
 * Version:     1.0.0
 * Date_update: 2026-04-18
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="fstu-modal-overlay fstu-hidden" id="fstu-modal-yearly-dues" role="dialog" aria-modal="true">
    <div class="fstu-modal fstu-modal--large" style="max-width: 800px;">
        <div class="fstu-modal__header">
            <h2 class="fstu-modal__title">Внески осередку (Portmone)</h2>
            <button type="button" class="fstu-modal-close-btn" aria-label="Закрити">✕</button>
        </div>
        <div class="fstu-modal__body">

            <div class="fstu-loader fstu-hidden" id="fstu-yearly-dues-loader">
                <span class="fstu-loader__spinner"></span>
                <span class="fstu-loader__text">Завантаження даних...</span>
            </div>

            <div class="fstu-table-wrap" style="margin-top: 0;">
                <table class="fstu-table">
                    <thead class="fstu-thead">
                    <tr>
                        <th class="fstu-th" style="width: 60px; text-align: center;">Рік</th>
                        <th class="fstu-th" style="width: 100px; text-align: right;">Сума</th>
                        <th class="fstu-th" style="width: 140px;">Дата сплати</th>
                        <th class="fstu-th">ПІБ</th>
                        <th class="fstu-th" style="text-align: center;">ОПЛАТА</th>
                    </tr>
                    </thead>
                    <tbody class="fstu-tbody" id="fstu-yearly-dues-tbody">
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>