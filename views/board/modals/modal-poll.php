<?php
/**
 * Модальне вікно: Додавання/Редагування опитування.
 * Шлях: views/board/modals/modal-poll.php
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="fstu-modal-overlay" id="fstu-modal-poll">
    <div class="fstu-modal-content">
        <div class="fstu-modal-header">
            <h3 class="fstu-modal-title">Створення опитування</h3>
            <button type="button" class="fstu-modal-close" aria-label="Закрити">×</button>
        </div>

        <form id="fstu-board-poll-form">
            <div class="fstu-modal-body">
                <input type="hidden" name="action" value="fstu_board_save_poll">
                <input type="hidden" name="nonce" value="">
                <input type="hidden" name="question_id" id="modal-poll-id" value="">

                <div class="fstu-form-group">
                    <label for="modal-poll-name">* Найменування опитування:</label>
                    <input type="text" id="modal-poll-name" name="question_name" class="fstu-input" required maxlength="70" placeholder="Введіть найменування">
                </div>

                <div class="fstu-form-group">
                    <label for="modal-poll-note">Детальний опис:</label>
                    <textarea id="modal-poll-note" name="question_note" class="fstu-input" rows="3" maxlength="250"></textarea>
                </div>

                <div class="fstu-form-group" style="display: flex; gap: 15px;">
                    <div style="flex: 1;">
                        <label for="modal-poll-start">* Дата початку:</label>
                        <input type="date" id="modal-poll-start" name="question_date_begin" class="fstu-input" required>
                    </div>
                    <div style="flex: 1;">
                        <label for="modal-poll-end">* Дата закінчення:</label>
                        <input type="date" id="modal-poll-end" name="question_date_end" class="fstu-input" required>
                    </div>
                </div>

                <div class="fstu-form-group">
                    <label for="modal-poll-state">* Статус:</label>
                    <select id="modal-poll-state" name="question_state" class="fstu-input" required>
                        <option value="0">Публічний</option>
                        <option value="1">Приватний</option>
                    </select>
                </div>

                <div class="fstu-form-group">
                    <label for="modal-poll-url">Посилання на документ:</label>
                    <input type="url" id="modal-poll-url" name="question_url" class="fstu-input" placeholder="https://...">
                </div>
            </div>

            <div class="fstu-modal-footer">
                <button type="button" class="fstu-btn fstu-btn--cancel">Скасувати</button>
                <button type="submit" class="fstu-btn fstu-btn--save">Зберегти</button>
            </div>
        </form>
    </div>
</div>