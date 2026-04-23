<?php
/**
 * Модальне вікно створення/редагування виборів.
 * * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div id="fstu-modal-election" class="fstu-modal" style="display: none;">
    <div class="fstu-modal-content">
        <div class="fstu-modal-header">
            <h3 id="fstu-modal-election-title"><?php _e( 'Налаштування виборів', 'fstu' ); ?></h3>
            <button type="button" class="fstu-modal-close" aria-label="<?php _e( 'Закрити', 'fstu' ); ?>">×</button>
        </div>
        <div class="fstu-modal-body">
            <form id="fstu-form-election">
                <input type="text" name="fstu_website" class="fstu-hidden-field" style="display:none;" tabindex="-1" autocomplete="off">

                <input type="hidden" name="election_id" id="fstu-election-id" value="0">

                <div class="fstu-form-group">
                    <label for="fstu-election-name" class="fstu-label"><?php _e( 'Назва виборів', 'fstu' ); ?> <span class="fstu-required">*</span></label>
                    <input type="text" id="fstu-election-name" name="election_name" class="fstu-input" required placeholder="Наприклад: Вибори до комісії водного туризму 2026">
                </div>

                <div class="fstu-form-group">
                    <label for="fstu-election-tourism" class="fstu-label"><?php _e( 'Вид туризму (Опціонально)', 'fstu' ); ?></label>
                    <select id="fstu-election-tourism" name="tourism_type_id" class="fstu-select">
                        <option value=""><?php _e( '— Не обрано (Глобальні вибори) —', 'fstu' ); ?></option>
                        <?php
                        global $wpdb;
                        $tourism_types = $wpdb->get_results( "SELECT TourismType_ID, TourismType_Name FROM S_TourismType ORDER BY TourismType_Order ASC", ARRAY_A );
                        if ( ! empty( $tourism_types ) ) {
                            foreach ( $tourism_types as $type ) {
                                echo '<option value="' . esc_attr( $type['TourismType_ID'] ) . '">' . esc_html( $type['TourismType_Name'] ) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <fieldset class="fstu-fieldset">
                    <legend><?php _e( 'Квоти та терміни', 'fstu' ); ?></legend>

                    <div class="fstu-form-row">
                        <div class="fstu-form-col">
                            <label for="fstu-election-count" class="fstu-label" title="Мінімум 3, максимум 15">
                                <?php _e( 'Кількість мандатів', 'fstu' ); ?>
                            </label>
                            <input type="number" id="fstu-election-count" name="candidates_count" class="fstu-input" value="7" min="3" max="15" required>
                        </div>
                        <div class="fstu-form-col">
                            <label for="fstu-election-nom-days" class="fstu-label"><?php _e( 'Дні висунення', 'fstu' ); ?></label>
                            <input type="number" id="fstu-election-nom-days" name="nomination_days" class="fstu-input" value="7" min="1" max="30" required>
                        </div>
                    </div>

                    <div class="fstu-form-row">
                        <div class="fstu-form-col">
                            <label for="fstu-election-ext-days" class="fstu-label" title="Якщо кандидатів менше 10">
                                <?php _e( 'Дні подовження', 'fstu' ); ?>
                            </label>
                            <input type="number" id="fstu-election-ext-days" name="extension_days" class="fstu-input" value="5" min="1" max="30" required>
                        </div>
                        <div class="fstu-form-col">
                            <label for="fstu-election-vote-days" class="fstu-label"><?php _e( 'Дні голосування', 'fstu' ); ?></label>
                            <input type="number" id="fstu-election-vote-days" name="voting_days" class="fstu-input" value="7" min="1" max="30" required>
                        </div>
                    </div>
                </fieldset>
            </form>
        </div>
        <div class="fstu-modal-footer">
            <button type="button" class="fstu-btn fstu-btn--cancel fstu-modal-close-btn"><?php _e( 'Скасувати', 'fstu' ); ?></button>
            <button type="button" class="fstu-btn fstu-btn--save" id="fstu-btn-save-election"><?php _e( 'Зберегти', 'fstu' ); ?></button>
        </div>
    </div>
</div>