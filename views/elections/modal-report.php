<?php
/**
 * Модальне вікно перегляду аналітичного звіту STV.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div id="fstu-modal-report" class="fstu-modal" style="display: none;">
    <div class="fstu-modal-content" style="max-width: 800px;">
        <div class="fstu-modal-header">
            <h3>📊 <?php _e( 'Аналітичний звіт виборів STV', 'fstu' ); ?></h3>
            <button type="button" class="fstu-modal-close" aria-label="<?php _e( 'Закрити', 'fstu' ); ?>">×</button>
        </div>
        <div class="fstu-modal-body">
            <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                <div style="flex: 1; background: #e9ecef; padding: 15px; border-radius: 6px; text-align: center;">
                    <div style="font-size: 24px; font-weight: bold; color: #d9534f;" id="fstu-report-turnout">0</div>
                    <div style="font-size: 12px; color: #6c757d;">Дійсних бюлетенів</div>
                </div>
                <div style="flex: 1; background: #e9ecef; padding: 15px; border-radius: 6px; text-align: center;">
                    <div style="font-size: 24px; font-weight: bold; color: #d9534f;" id="fstu-report-quota">0</div>
                    <div style="font-size: 12px; color: #6c757d;">Квота Друпа</div>
                </div>
                <div style="flex: 1; background: #e9ecef; padding: 15px; border-radius: 6px; text-align: center;">
                    <div style="font-size: 24px; font-weight: bold; color: #d9534f;" id="fstu-report-exhausted">0</div>
                    <div style="font-size: 12px; color: #6c757d;">Виснажених голосів</div>
                </div>
            </div>

            <h4>🏆 Склад комісії (Переможці)</h4>
            <ul id="fstu-report-winners" class="fstu-table--zebra" style="list-style: decimal inside; padding-left: 20px; line-height: 1.6; margin-bottom: 20px;">
            </ul>

            <h4>📜 Журнал раундів підрахунку</h4>
            <div id="fstu-report-rounds" style="background: #f8f9fa; border: 1px solid #d1d5db; padding: 15px; border-radius: 4px; font-size: 13px; max-height: 250px; overflow-y: auto;">
            </div>
        </div>
        <div class="fstu-modal-footer">
            <a href="#" id="fstu-btn-download-cvr" class="fstu-btn fstu-btn--default" target="_blank">💾 Завантажити CVR (CSV)</a>
            <button type="button" class="fstu-btn fstu-btn--cancel fstu-modal-close-btn"><?php _e( 'Закрити', 'fstu' ); ?></button>
        </div>
    </div>
</div>