<?php
/**
 * Інтерфейс кабінки для голосування.
 * * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div id="fstu-voting-booth" class="fstu-modal" style="display: none;">
    <div class="fstu-modal-content" style="max-width: 900px; height: 85vh;">
        <div class="fstu-modal-header">
            <h3>🗳 <?php _e( 'Кабінка для голосування', 'fstu' ); ?></h3>
            <button type="button" class="fstu-modal-close btn-close-booth" aria-label="<?php _e( 'Закрити', 'fstu' ); ?>">×</button>
        </div>
        <div class="fstu-modal-body" style="background: #f3f4f6; display: flex; flex-direction: column; height: 100%;">

            <input type="hidden" id="fstu-vote-election-id" value="">

            <div id="fstu-voting-debt-alert" class="fstu-alert fstu-alert--danger" style="display: none; margin-bottom: 20px;">
                <strong><?php _e( 'Увага! Блокування голосування.', 'fstu' ); ?></strong><br>
                <?php _e( 'Ви не маєте права голосувати через заборгованість по членських внесках за поточний або попередній рік.', 'fstu' ); ?>
            </div>

            <div id="fstu-voting-done-alert" class="fstu-alert fstu-alert--success" style="display: none; margin-bottom: 20px;">
                <strong><?php _e( 'Ви вже проголосували!', 'fstu' ); ?></strong><br>
                <?php _e( 'Ваш голос було враховано. Змінити вибір неможливо.', 'fstu' ); ?>
            </div>

            <div id="fstu-voting-workspace" style="display: none; flex: 1; display: flex; gap: 20px; overflow: hidden;">
                <div class="fstu-election-column" style="flex: 1; display: flex; flex-direction: column; background: #fff; border: 1px solid #d1d5db; border-radius: 6px;">
                    <div style="padding: 10px; border-bottom: 1px solid #e5e7eb; background: #f8f9fa;">
                        <strong><?php _e( 'Кандидати', 'fstu' ); ?></strong>
                    </div>
                    <ul id="fstu-candidates-source" class="fstu-sortable-list" style="flex: 1; overflow-y: auto; padding: 10px; margin: 0; list-style: none;">
                    </ul>
                </div>

                <div class="fstu-election-column" style="flex: 1; display: flex; flex-direction: column; background: #fff; border: 2px dashed #86b7fe; border-radius: 6px;">
                    <div style="padding: 10px; border-bottom: 1px solid #e5e7eb; background: #e9f2ff;">
                        <strong><?php _e( 'Мій бюлетень (Ранжування)', 'fstu' ); ?></strong><br>
                        <small><?php _e( 'Перетягніть сюди та відсортуйте (№1 - найвищий пріоритет)', 'fstu' ); ?></small>
                    </div>
                    <ul id="fstu-candidates-target" class="fstu-sortable-list" style="flex: 1; overflow-y: auto; padding: 10px; margin: 0; list-style: none; min-height: 200px;">
                    </ul>
                </div>
            </div>

            <div id="fstu-voting-legend" style="display: none; margin-top: 15px; font-size: 12px; color: #6b7280;">
                <span style="display: inline-block; width: 12px; height: 12px; background: #f8d7da; border: 1px solid #dc3545; border-radius: 2px; vertical-align: middle;"></span>
                — <?php _e( 'Кандидати, виділені червоним кольором, мають заборгованість по сплаті членських внесків.', 'fstu' ); ?>
            </div>

        </div>
        <div class="fstu-modal-footer">
            <button type="button" class="fstu-btn fstu-btn--cancel btn-close-booth"><?php _e( 'Закрити', 'fstu' ); ?></button>
            <button type="button" class="fstu-btn fstu-btn--save" id="fstu-btn-submit-ballot" style="display: none;"><?php _e( '🗳 Відправити бюлетень', 'fstu' ); ?></button>
        </div>
    </div>
</div>