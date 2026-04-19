<?php
/**
 * Модальне вікно: Додавання кандидата до опитування.
 * Шлях: views/board/modals/modal-candidate.php
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="fstu-modal-overlay" id="fstu-modal-candidate">
    <div class="fstu-modal-content">
        <div class="fstu-modal-header">
            <h3 class="fstu-modal-title">Додавання кандидата</h3>
            <button type="button" class="fstu-modal-close" aria-label="Закрити">×</button>
        </div>

        <form id="fstu-board-candidate-form">
            <div class="fstu-modal-body">
                <input type="hidden" name="action" value="fstu_board_save_candidate">
                <input type="hidden" name="nonce" value="">
                <input type="hidden" name="question_id" id="modal-candidate-question-id" value="">

                <div class="fstu-form-group">
                    <label for="modal-candidate-user-search">* Кандидат (ПІБ):</label>
                    <div class="fstu-autocomplete-wrapper">
                        <input type="text" id="modal-candidate-user-search" class="fstu-input" placeholder="Почніть вводити ПІБ (мінімум 3 літери)..." autocomplete="off" required>
                        <input type="hidden" name="user_id" id="modal-candidate-user-id" required>
                        <ul id="modal-candidate-user-results" class="fstu-autocomplete-list" style="display:none;"></ul>
                    </div>
                </div>

                <div class="fstu-form-group">
                    <label for="modal-candidate-direction">* Напрямок роботи в комісії:</label>
                    <input type="text" id="modal-candidate-direction" name="direction" class="fstu-input" required maxlength="250" placeholder="Наприклад: Секретар, Голова...">
                </div>

                <div class="fstu-form-group">
                    <label for="modal-candidate-development">* Програма розвитку:</label>
                    <textarea id="modal-candidate-development" name="development" class="fstu-input" rows="3" required maxlength="500" placeholder="Опишіть програму розвитку..."></textarea>
                </div>

                <div class="fstu-form-group">
                    <label for="modal-candidate-url">Посилання на програму/документ:</label>
                    <input type="url" id="modal-candidate-url" name="url" class="fstu-input" placeholder="https://...">
                </div>
            </div>

            <div class="fstu-modal-footer">
                <button type="button" class="fstu-btn fstu-btn--cancel">Скасувати</button>
                <button type="submit" class="fstu-btn fstu-btn--save">Зберегти</button>
            </div>
        </form>
    </div>
</div>