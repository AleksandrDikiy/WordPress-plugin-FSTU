<?php
/**
 * Модальне вікно: Додавання/Редагування члена комісії.
 * Шлях: views/board/modals/modal-member.php
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
global $wpdb;
?>
<div class="fstu-modal-overlay" id="fstu-modal-member">
    <div class="fstu-modal-content">
        <div class="fstu-modal-header">
            <h3 class="fstu-modal-title">Редагування члена комісії</h3>
            <button type="button" class="fstu-modal-close" aria-label="Закрити">×</button>
        </div>

        <form id="fstu-board-member-form">
            <div class="fstu-modal-body">
                <input type="hidden" name="action" value="fstu_board_save_member">
                <input type="hidden" name="nonce" value="">
                <input type="hidden" name="commission_id" id="modal-member-id" value="">

                <input type="hidden" name="year_id" id="modal-member-year" value="">
                <input type="hidden" name="commission_type_id" id="modal-member-type" value="">
                <input type="hidden" name="region_id" id="modal-member-region" value="">
                <input type="hidden" name="s_commission_id" id="modal-member-s-commission" value="">

                <div class="fstu-form-group">
                    <label for="modal-member-user-search">* Користувач (ПІБ):</label>
                    <div class="fstu-autocomplete-wrapper">
                        <input type="text" id="modal-member-user-search" class="fstu-input" placeholder="Почніть вводити ПІБ (мінімум 3 літери)..." autocomplete="off" required>
                        <input type="hidden" name="user_id" id="modal-member-user-id" required>
                        <ul id="modal-member-user-results" class="fstu-autocomplete-list" style="display:none;"></ul>
                    </div>
                </div>

                <div class="fstu-form-group">
                    <label for="modal-member-role">* Роль у комісії:</label>
                    <select id="modal-member-role" name="member_id" class="fstu-input" required>
                        <option value="">-- Оберіть роль --</option>
                        <?php
                        // Підвантажуємо ролі з довідника
                        $roles = $wpdb->get_results("SELECT Member_ID, Member_Name FROM S_Member ORDER BY Member_Order");
                        if ($roles) {
                            foreach ($roles as $r) {
                                echo '<option value="' . esc_attr($r->Member_ID) . '">' . esc_html($r->Member_Name) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="fstu-modal-footer">
                <button type="button" class="fstu-btn fstu-btn--cancel">Скасувати</button>
                <button type="submit" class="fstu-btn fstu-btn--save">Зберегти</button>
            </div>
        </form>
    </div>
</div>