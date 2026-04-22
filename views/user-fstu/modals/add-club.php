<?php
/**
 * View: Модальне вікно "Додавання клубу".
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="fstu-modal-overlay fstu-hidden" id="fstu-modal-add-club" role="dialog" aria-modal="true">
    <div class="fstu-modal fstu-modal--small" style="max-width: 400px;">
        <div class="fstu-modal__header">
            <h2 class="fstu-modal__title">Додавання клубу</h2>
            <button type="button" class="fstu-modal-close-btn" aria-label="Закрити">✕</button>
        </div>
        <div class="fstu-modal__body">

            <div class="fstu-alert fstu-hidden" id="fstu-add-club-alert"></div>

            <form id="fstu-add-club-form">
                <input type="hidden" name="user_id" id="add_club_user_id">

                <div class="fstu-form-group" style="margin-bottom: 20px;">
                    <label class="fstu-label" style="margin-bottom: 5px;">Оберіть клуб *</label>
                    <select name="club_id" id="add_club_id" class="fstu-select" required>
                        <option value="">— Оберіть клуб —</option>
                        <?php foreach ( $clubs as $club ) : ?>
                            <option value="<?php echo esc_attr( $club['Club_ID'] ); ?>">
                                <?php echo esc_html( $club['Club_Name'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="text-align: right; border-top: 1px solid var(--fstu-border); padding-top: 15px;">
                    <button type="submit" class="fstu-btn fstu-btn--primary" id="fstu-add-club-submit">
                        <span class="fstu-btn__text">💾 Зберегти</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>