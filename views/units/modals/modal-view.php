<?php
/**
 * Модальне вікно: Перегляд картки осередку.
 * * Version: 1.0.0
 * Date_update: 2026-04-10
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div id="fstu-modal-unit-view" class="fstu-modal" style="display: none;">
    <div class="fstu-modal-content fstu-modal-content--large">
        <div class="fstu-modal-header">
            <h3 id="fstu-view-title">Перегляд осередку</h3>
            <button type="button" class="fstu-modal-close">&times;</button>
        </div>
        <div class="fstu-modal-body">
            <table class="fstu-table fstu-table--striped">
                <tbody>
                    <tr><td style="width: 40%; text-align: right;">Найменування повне:</td><td><b id="view-Unit_Name"></b></td></tr>
                    <tr><td style="text-align: right;">Найменування скорочене:</td><td><b id="view-Unit_ShortName"></b></td></tr>
                    <tr><td style="text-align: right;">ОПФ:</td><td><b id="view-OPF_Name"></b></td></tr>
                    <tr><td style="text-align: right;">Ранг:</td><td><b id="view-UnitType_Name"></b></td></tr>
                    <tr><td style="text-align: right;">Область:</td><td><b id="view-Region_Name"></b></td></tr>
                    <tr><td style="text-align: right;">Місто:</td><td><b id="view-City_Name"></b></td></tr>
                    <tr><td style="text-align: right;">Адреса:</td><td><b id="view-Unit_Adr"></b></td></tr>
                    <tr><td style="text-align: right;">Код ЄДРПОУ:</td><td><b id="view-Unit_OKPO"></b></td></tr>
                    <tr class="fstu-view-financial"><td style="text-align: right;">Вступний внесок (грн):</td><td><b id="view-Unit_EntranceFee"></b></td></tr>
                    <tr class="fstu-view-financial"><td style="text-align: right;">Річний внесок (грн):</td><td><b id="view-Unit_AnnualFee"></b></td></tr>
                    <tr class="fstu-view-financial"><td style="text-align: right;">Посилання на форму оплати:</td><td><b id="view-Unit_UrlPay"></b></td></tr>
                    <tr class="fstu-view-financial"><td style="text-align: right;">Номер платіжної карти:</td><td><b id="view-Unit_PaymentCard"></b></td></tr>
                </tbody>
            </table>

            <div id="fstu-view-dues-wrapper" style="margin-top: 20px; display: none;">
                <h4>СПЛАТА ЧЛЕНСЬКИХ ВНЕСКІВ ОСЕРЕДКА</h4>
                <div class="fstu-table-responsive">
                    <table class="fstu-table fstu-table--striped">
                        <thead>
                            <tr>
                                <th>Рік</th>
                                <th>Сума</th>
                                <th>Дата</th>
                                <th>Оплата</th>
                            </tr>
                        </thead>
                        <tbody id="view-dues-tbody"></tbody>
                    </table>
                </div>
            </div>

        </div>
        <div class="fstu-modal-footer">
            <button type="button" class="fstu-btn fstu-btn--cancel fstu-modal-close">Закрити</button>
        </div>
    </div>
</div>