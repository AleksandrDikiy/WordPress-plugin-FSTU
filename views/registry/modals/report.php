<?php
/**
 * View: Модальне вікно "Звіт".
 *
 * @package FSTU\Registry\Views\Modals
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="fstu-modal-overlay fstu-hidden" id="fstu-modal-report" role="dialog" aria-modal="true" aria-labelledby="fstu-report-title">
    <div class="fstu-modal fstu-modal--large">
        <div class="fstu-modal__header">
            <h2 class="fstu-modal__title" id="fstu-report-title">Звіт про сплату членських внесків</h2>
            <button type="button" class="fstu-modal-close-btn" aria-label="Закрити">✕</button>
        </div>
        <div class="fstu-modal__body">

            <div class="fstu-loader-inline fstu-hidden" id="fstu-report-loader">
                <span class="fstu-loader__spinner"></span> Формування звіту...
            </div>

            <div class="fstu-alert fstu-hidden" id="fstu-report-alert"></div>

            <div id="fstu-report-content" class="fstu-hidden">
                <div style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; background: #f8f9fa; padding: 10px; border-radius: 4px; border: 1px solid var(--fstu-border);">
                    <h3 style="margin: 0; font-size: 16px; color: var(--fstu-primary);" id="fstu-report-year-title">За 2026 рік</h3>
                    <button type="button" class="fstu-btn fstu-btn--primary" onclick="window.print()">🖨 Друк звіту</button>
                </div>
                <table class="fstu-table" id="fstu-report-table">
                    <thead class="fstu-thead" id="fstu-report-thead">
                    </thead>
                    <tbody class="fstu-tbody" id="fstu-report-tbody">
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>