<?php
/**
 * Модальне вікно форми редагування осередку.
 * * Version: 1.1.0
 * Date_update: 2026-04-10
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div id="fstu-modal-unit-edit" class="fstu-modal" style="display: none;">
    <div class="fstu-modal-content fstu-modal-content--large">
        <div class="fstu-modal-header">
            <h3 id="fstu-modal-title">Додати/Редагувати осередок</h3>
            <button type="button" class="fstu-modal-close">&times;</button>
        </div>
        <div class="fstu-modal-body">
            <form id="fstu-unit-form" method="post">
                <input type="hidden" name="action" value="fstu_units_save">
                <input type="hidden" name="Unit_ID" id="Unit_ID" value="0">
                <input type="text" name="fstu_website" class="fstu-hidden-field" style="display:none;" tabindex="-1" autocomplete="off">

                <div class="fstu-form-row">
                    <div class="fstu-form-group fstu-col-8">
                        <label for="Unit_Name">Найменування повне <span class="fstu-required">*</span></label>
                        <input type="text" id="Unit_Name" name="Unit_Name" class="fstu-input" required>
                    </div>
                    <div class="fstu-form-group fstu-col-4">
                        <label for="Unit_ShortName">Скорочене <span class="fstu-required">*</span></label>
                        <input type="text" id="Unit_ShortName" name="Unit_ShortName" class="fstu-input" required>
                    </div>
                </div>

                <div class="fstu-form-row">
                    <div class="fstu-form-group fstu-col-4">
                        <label for="OPF_ID">ОПФ</label>
                        <select id="OPF_ID" name="OPF_ID" class="fstu-select"></select>
                    </div>
                    <div class="fstu-form-group fstu-col-4">
                        <label for="UnitType_ID">Ранг</label>
                        <select id="UnitType_ID" name="UnitType_ID" class="fstu-select"></select>
                    </div>
                    <div class="fstu-form-group fstu-col-4">
                        <label for="Unit_Parent">Вищий осередок</label>
                        <select id="Unit_Parent" name="Unit_Parent" class="fstu-select">
                            <option value="0">-- Немає --</option>
                        </select>
                    </div>
                </div>

                <div class="fstu-form-row">
                    <div class="fstu-form-group fstu-col-4">
                        <label for="Region_ID">Регіон</label>
                        <select id="Region_ID" name="Region_ID" class="fstu-select"></select>
                    </div>
                    <div class="fstu-form-group fstu-col-4">
                        <label for="City_ID">Місто</label>
                        <select id="City_ID" name="City_ID" class="fstu-select">
                            <option value="">-- Оберіть регіон --</option>
                        </select>
                    </div>
                    <div class="fstu-form-group fstu-col-4">
                        <label for="Unit_Adr">Місцезнаходження</label>
                        <input type="text" id="Unit_Adr" name="Unit_Adr" class="fstu-input">
                    </div>
                </div>

                <fieldset class="fstu-fieldset">
                    <legend>Фінансові дані</legend>
                    <div class="fstu-form-row">
                        <div class="fstu-form-group fstu-col-4">
                            <label for="Unit_OKPO">Код ЄДРПОУ</label>
                            <input type="text" id="Unit_OKPO" name="Unit_OKPO" class="fstu-input">
                        </div>
                        <div class="fstu-form-group fstu-col-4">
                            <label for="Unit_EntranceFee">Вступний внесок (грн)</label>
                            <input type="number" id="Unit_EntranceFee" name="Unit_EntranceFee" class="fstu-input" step="0.01">
                        </div>
                        <div class="fstu-form-group fstu-col-4">
                            <label for="Unit_AnnualFee">Річний внесок (грн)</label>
                            <input type="number" id="Unit_AnnualFee" name="Unit_AnnualFee" class="fstu-input" step="0.01">
                        </div>
                    </div>
                    <div class="fstu-form-row" style="margin-bottom: 0;">
                        <div class="fstu-form-group fstu-col-12">
                            <label for="Unit_PaymentCard">Номер платіжної карти для оплати</label>
                            <input type="text" id="Unit_PaymentCard" name="Unit_PaymentCard" class="fstu-input">
                        </div>
                    </div>
                </fieldset>

                <div class="fstu-modal-footer">
                    <button type="submit" class="fstu-btn fstu-btn--save">Зберегти</button>
                    <button type="button" class="fstu-btn fstu-btn--cancel fstu-modal-close">Скасувати</button>
                </div>
            </form>
        </div>
    </div>
</div>