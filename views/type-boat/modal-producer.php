<div id="fstu-modal-producer" class="fstu-modal fstu-hidden">
    <div class="fstu-modal-overlay"></div>
    <div class="fstu-modal-container">
        <div class="fstu-modal-header">
            <h3 class="fstu-modal-title"><?php esc_html_e( 'Виробник суден', 'fstu' ); ?></h3>
            <button type="button" class="fstu-modal-close">×</button>
        </div>
        <form id="fstu-producer-form">
            <div class="fstu-modal-body">
                <input type="hidden" name="id" value="0">
                <input type="text" name="fstu_website" class="fstu-hidden-field" style="display:none;" tabindex="-1" autocomplete="off">

                <div class="fstu-form-group">
                    <label><?php esc_html_e( 'Назва виробника *', 'fstu' ); ?></label>
                    <input type="text" name="ProducerShips_Name" class="fstu-input" required>
                </div>
                <div class="fstu-form-row">
                    <div class="fstu-form-group">
                        <label><?php esc_html_e( 'Телефон', 'fstu' ); ?></label>
                        <input type="text" name="ProducerShips_Phone" class="fstu-input">
                    </div>
                    <div class="fstu-form-group">
                        <label><?php esc_html_e( 'URL сайту', 'fstu' ); ?></label>
                        <input type="url" name="ProducerShips_URL" class="fstu-input">
                    </div>
                </div>
                <div class="fstu-form-group">
                    <label><?php esc_html_e( 'Поштова адреса', 'fstu' ); ?></label>
                    <input type="text" name="ProducerShips_Adr" class="fstu-input">
                </div>
                <div class="fstu-form-group">
                    <label><?php esc_html_e( 'Опис', 'fstu' ); ?></label>
                    <textarea name="ProducerShips_Notes" class="fstu-textarea"></textarea>
                </div>
            </div>
            <div class="fstu-modal-footer">
                <button type="button" class="fstu-btn fstu-btn--cancel"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
                <button type="submit" class="fstu-btn fstu-btn--save"><?php esc_html_e( 'Зберегти', 'fstu' ); ?></button>
            </div>
        </form>
    </div>
</div>