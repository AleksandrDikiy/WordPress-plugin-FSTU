<div id="fstu-status-card-modal" class="fstu-modal" style="display: none;">
    <div class="fstu-modal-overlay"></div>
    <div class="fstu-modal-dialog">
        <div class="fstu-modal-header">
            <h3 id="fstu-modal-title">Додати статус</h3>
            <button type="button" class="fstu-modal-close" aria-label="Закрити">×</button>
        </div>
        <div class="fstu-modal-body">
            <form id="fstu-status-card-form">
                <input type="text" name="fstu_website" class="fstu-hidden-field" style="display:none;" tabindex="-1" autocomplete="off">
                <input type="hidden" id="fstu-status-card-id" name="id" value="0">

                <div class="fstu-form-group">
                    <label for="fstu-status-card-name" class="fstu-label">* Найменування:</label>
                    <input type="text" id="fstu-status-card-name" name="name" class="fstu-input" required maxlength="255">
                </div>
            </form>
        </div>
        <div class="fstu-modal-footer">
            <button type="button" class="fstu-btn fstu-btn--cancel fstu-modal-close-btn">Скасувати</button>
            <button type="submit" form="fstu-status-card-form" class="fstu-btn fstu-btn--save">Зберегти</button>
        </div>
    </div>
</div>