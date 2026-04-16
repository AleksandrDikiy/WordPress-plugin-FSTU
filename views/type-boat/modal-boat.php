<div id="fstu-modal-boat" class="fstu-modal fstu-hidden">
    <div class="fstu-modal-overlay"></div>
    <div class="fstu-modal-container">
        <div class="fstu-modal-header">
            <h3 class="fstu-modal-title"><?php esc_html_e( 'Параметри типу судна', 'fstu' ); ?></h3>
            <button type="button" class="fstu-modal-close">×</button>
        </div>
        <form id="fstu-boat-form">
            <div class="fstu-modal-body">
                <input type="hidden" name="id" value="0">
                <input type="hidden" name="ProducerShips_ID" value="0">

                <div class="fstu-form-group">
                    <label><?php esc_html_e( 'Найменування типу *', 'fstu' ); ?></label>
                    <input type="text" name="TypeBoat_Name" class="fstu-input" required>
                </div>
                <div class="fstu-form-row fstu-form-row--4col">
                    <div class="fstu-form-group"><label>S (кв.м)</label><input type="number" step="0.01" name="TypeBoat_SailArea" class="fstu-input"></div>
                    <div class="fstu-form-group"><label>L (м)</label><input type="number" step="0.01" name="TypeBoat_HillLength" class="fstu-input"></div>
                    <div class="fstu-form-group"><label>B (м)</label><input type="number" step="0.01" name="TypeBoat_WidthOverall" class="fstu-input"></div>
                    <div class="fstu-form-group"><label>W (кг)</label><input type="number" step="0.1" name="TypeBoat_Weight" class="fstu-input"></div>
                </div>
                <div class="fstu-form-group">
                    <label><?php esc_html_e( 'Посилання на сторінку/креслення', 'fstu' ); ?></label>
                    <input type="url" name="TypeBoat_URL" class="fstu-input">
                </div>
                <div class="fstu-form-group">
                    <label><?php esc_html_e( 'Додаткові примітки', 'fstu' ); ?></label>
                    <textarea name="TypeBoat_Notes" class="fstu-textarea"></textarea>
                </div>
            </div>
            <div class="fstu-modal-footer">
                <button type="button" class="fstu-btn fstu-btn--cancel"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
                <button type="submit" class="fstu-btn fstu-btn--save"><?php esc_html_e( 'Зберегти', 'fstu' ); ?></button>
            </div>
        </form>
    </div>
</div>