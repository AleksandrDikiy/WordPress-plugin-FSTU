<?php
/**
 * View: Модальне вікно створення/редагування платіжного документа.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-05
 *
 * @package FSTU\PaymentDocs\Views
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="fstu-modal-overlay fstu-hidden" id="fstu-modal-doc-editor" role="dialog" aria-modal="true">
    <div class="fstu-modal fstu-modal--large" style="max-width: 900px;">
        <div class="fstu-modal__header">
            <h2 class="fstu-modal__title" id="pd-modal-title">Створення документу про оплату</h2>
            <button type="button" class="fstu-modal-close-btn" aria-label="Закрити">✕</button>
        </div>
        <div class="fstu-modal__body">

            <div class="fstu-alert fstu-hidden" id="pd-editor-alert"></div>

            <form id="fstu-pd-editor-form">
                <input type="hidden" name="doc_id" id="pd-edit-doc-id" value="0">

                <div class="fstu-form-row fstu-form-row--3col">
                    <div class="fstu-form-group">
                        <label class="fstu-label">Дата *</label>
                        <input type="datetime-local" name="doc_date" id="pd-edit-date" class="fstu-input" required>
                    </div>
                    <div class="fstu-form-group">
                        <label class="fstu-label">ОФСТ *</label>
                        <select name="unit_id" id="pd-edit-unit" class="fstu-select" required>
                            <option value="">— Оберіть ОФСТ —</option>
                            <?php foreach ( $units as $u ) : ?>
                                <option value="<?php echo esc_attr( $u['Unit_ID'] ); ?>"><?php echo esc_html( $u['Unit_ShortName'] ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fstu-form-group">
                        <label class="fstu-label">Відповідальна особа *</label>
                        <select name="resp_id" id="pd-edit-resp" class="fstu-select" required>
                            <option value="">— Оберіть особу —</option>
                            <?php foreach ( $resp_users as $ru ) : ?>
                                <option value="<?php echo esc_attr( $ru['User_ID'] ); ?>"><?php echo esc_html( $ru['FIO'] ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="fstu-form-row fstu-form-row--2col" style="margin-top: 10px;">
                    <div class="fstu-form-group">
                        <label class="fstu-label">Посилання на квитанцію *</label>
                        <input type="url" name="doc_url" id="pd-edit-url" class="fstu-input" placeholder="https://..." required>
                    </div>
                    <div class="fstu-form-group">
                        <label class="fstu-label">Коментар</label>
                        <input type="text" name="doc_comment" id="pd-edit-comment" class="fstu-input" maxlength="150" placeholder="не більше 150 символів">
                    </div>
                </div>

                <hr style="border: 0; border-top: 1px solid var(--fstu-border); margin: 20px 0;">

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <h3 class="fstu-registry-title" style="font-size: 16px; margin: 0;">Таблична частина</h3>
                    <button type="button" class="fstu-btn fstu-btn--secondary" id="pd-btn-add-row" style="color: var(--fstu-success); border-color: var(--fstu-success);">
                        <span class="fstu-btn__icon">➕</span> Додати рядок
                    </button>
                </div>

                <div class="fstu-table-wrap" style="margin-top: 0; overflow: visible;">
                    <table class="fstu-table" id="pd-tp-table">
                        <thead class="fstu-thead">
                        <tr>
                            <th class="fstu-th" style="width: 40px; text-align: center;">№</th>
                            <th class="fstu-th">ПІБ</th>
                            <th class="fstu-th" style="width: 150px;">Тип</th>
                            <th class="fstu-th" style="width: 100px;">Сума</th>
                            <th class="fstu-th" style="width: 90px;">Рік</th>
                            <th class="fstu-th" style="width: 40px; text-align: center;">✖</th>
                        </tr>
                        </thead>
                        <tbody class="fstu-tbody" id="pd-tp-tbody">
                        </tbody>
                        <tfoot>
                        <tr style="background: var(--fstu-bg-header); font-weight: bold;">
                            <td colspan="3" class="fstu-td" style="text-align: right;">ЗАГАЛЬНА СУМА ДОКУМЕНТА:</td>
                            <td class="fstu-td" style="text-align: right; color: var(--fstu-primary); font-size: 16px;">
                                <span id="pd-total-sum">0.00</span>
                                <input type="hidden" name="doc_sum" id="pd-input-total-sum" value="0">
                            </td>
                            <td colspan="2" class="fstu-td"></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>

                <div style="text-align: right; margin-top: 20px;">
                    <button type="submit" class="fstu-btn fstu-btn--primary" id="pd-btn-save-doc">
                        <span class="fstu-btn__text">💾 Зберегти документ</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<template id="pd-row-template">
    <tr class="fstu-row pd-tp-row">
        <td class="fstu-td pd-row-num" style="text-align: center; color: var(--fstu-text-light);">1</td>
        <td class="fstu-td">
            <select name="tp_user_id[]" class="fstu-select pd-tp-user" required style="width: 100%;">
                <option value="">— Оберіть члена ФСТУ —</option>
                <?php foreach ( $fstu_users as $u ) : ?>
                    <option value="<?php echo esc_attr( $u['User_ID'] ); ?>" data-unit="<?php echo esc_attr( $u['Unit_ID'] ); ?>">
                        <?php echo esc_html( $u['FIO'] ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td class="fstu-td">
            <select name="tp_dues_type[]" class="fstu-select pd-tp-type" required>
                <?php foreach ( $dues_types as $dt ) : ?>
                    <option value="<?php echo esc_attr( $dt['DuesType_ID'] ); ?>" <?php selected( $dt['DuesType_ID'], 1 ); ?>>
                        <?php echo esc_html( $dt['DuesType_Name'] ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td class="fstu-td">
            <input type="number" step="0.01" min="0" name="tp_sum[]" class="fstu-input pd-tp-sum" value="<?php echo esc_attr( $default_sum ); ?>" required style="text-align: right;">
        </td>
        <td class="fstu-td">
            <input type="number" name="tp_year[]" class="fstu-input pd-tp-year" value="<?php echo gmdate('Y'); ?>" required style="text-align: center;">
        </td>
        <td class="fstu-td" style="text-align: center;">
            <button type="button" class="fstu-btn-action pd-btn-remove-row" style="background: var(--fstu-error) !important;" title="Видалити рядок">✖</button>
        </td>
    </tr>
</template>