<?php
/**
 * Модальне вікно списку кандидатів.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div id="fstu-modal-candidates" class="fstu-modal" style="display: none;">
    <div class="fstu-modal-content" style="max-width: 500px;">
        <div class="fstu-modal-header">
            <h3>👥 <?php _e( 'Список кандидатів', 'fstu' ); ?></h3>
            <button type="button" class="fstu-modal-close" aria-label="<?php _e( 'Закрити', 'fstu' ); ?>">×</button>
        </div>
        <div class="fstu-modal-body" style="padding: 0;">
            <ul id="fstu-candidates-list" style="list-style: none; padding: 0; margin: 0;">
            </ul>
        </div>
        <div class="fstu-modal-footer">
            <button type="button" class="fstu-btn fstu-btn--cancel fstu-modal-close-btn"><?php _e( 'Закрити', 'fstu' ); ?></button>
        </div>
    </div>
</div>