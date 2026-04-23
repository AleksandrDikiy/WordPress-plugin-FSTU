<?php
/**
 * Модальне вікно висунення кандидата.
 * * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div id="fstu-modal-nomination" class="fstu-modal" style="display: none;">
    <div class="fstu-modal-content">
        <div class="fstu-modal-header">
            <h3><?php _e( 'Висунення кандидата', 'fstu' ); ?></h3>
            <button type="button" class="fstu-modal-close" aria-label="<?php _e( 'Закрити', 'fstu' ); ?>">×</button>
        </div>
        <div class="fstu-modal-body">
            <form id="fstu-form-nomination">
                <input type="text" name="fstu_website" class="fstu-hidden-field" style="display:none;" tabindex="-1" autocomplete="off">
                <input type="hidden" name="election_id" id="fstu-nomination-election-id" value="">

                <div class="fstu-form-group">
                    <label class="fstu-label"><?php _e( 'Тип висунення', 'fstu' ); ?> <span class="fstu-required">*</span></label>
                    <div style="display: flex; gap: 15px;">
                        <label><input type="radio" name="nomination_type" value="self" checked> Самовисування</label>
                        <label><input type="radio" name="nomination_type" value="other"> Номінувати іншого</label>
                    </div>
                </div>

                <div class="fstu-form-group" id="fstu-nomination-user-wrapper" style="display: none;">
                    <label for="fstu-nomination-user-id" class="fstu-label"><?php _e( 'Оберіть члена ФСТУ', 'fstu' ); ?> <span class="fstu-required">*</span></label>
                    <select id="fstu-nomination-user-id" name="candidate_user_id" class="fstu-select" style="width: 100%;">
                        <option value=""><?php _e( 'Почніть вводити ПІБ...', 'fstu' ); ?></option>
                    </select>
                    <small class="fstu-text-muted"><?php _e( 'Номінована особа отримає запит і повинна підтвердити згоду у своєму кабінеті.', 'fstu' ); ?></small>
                </div>

                <div class="fstu-form-group">
                    <label for="fstu-nomination-text" class="fstu-label"><?php _e( 'Мотиваційний текст', 'fstu' ); ?></label>
                    <textarea id="fstu-nomination-text" name="motivation_text" class="fstu-input" rows="4" placeholder="Опишіть, чому цей кандидат має бути обраний..."></textarea>
                </div>

                <div class="fstu-form-group">
                    <label for="fstu-nomination-url" class="fstu-label"><?php _e( 'Посилання на програму (URL)', 'fstu' ); ?></label>
                    <input type="url" id="fstu-nomination-url" name="motivation_url" class="fstu-input" placeholder="https://...">
                </div>
            </form>
        </div>
        <div class="fstu-modal-footer">
            <button type="button" class="fstu-btn fstu-btn--cancel fstu-modal-close-btn"><?php _e( 'Скасувати', 'fstu' ); ?></button>
            <button type="button" class="fstu-btn fstu-btn--save" id="fstu-btn-save-nomination"><?php _e( 'Висунути', 'fstu' ); ?></button>
        </div>
    </div>
</div>