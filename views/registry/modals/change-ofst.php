<?php
/**
 * View: Модальне вікно "Зміна ОФСТ".
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="fstu-modal-overlay fstu-hidden" id="fstu-modal-change-ofst" role="dialog" aria-modal="true">
    <div class="fstu-modal fstu-modal--small" style="max-width: 400px;">
        <div class="fstu-modal__header">
            <h2 class="fstu-modal__title">Зміна ОФСТ (Осередку)</h2>
            <button type="button" class="fstu-modal-close-btn" aria-label="Закрити">✕</button>
        </div>
        <div class="fstu-modal__body">

            <div class="fstu-loader-inline fstu-hidden" id="fstu-ofst-loader">
                <span class="fstu-loader__spinner"></span> Завантаження поточних даних...
            </div>
            <div class="fstu-alert fstu-hidden" id="fstu-ofst-alert"></div>

            <form id="fstu-change-ofst-form" class="fstu-hidden">
                <input type="hidden" name="user_id" id="ofst_user_id">

                <div class="fstu-form-group" style="margin-bottom: 20px;">
                    <label class="fstu-label" style="margin-bottom: 5px;">Оберіть новий осередок *</label>
                    <select name="unit_id" id="ofst_unit_id" class="fstu-select" required>
                        <option value="">— Оберіть ОФСТ —</option>
                        <?php foreach ( $units as $unit ) : ?>
                            <option value="<?php echo esc_attr( $unit['Unit_ID'] ); ?>">
                                <?php echo esc_html( $unit['Unit_ShortName'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: var(--fstu-text-light); margin-top: 5px; display: block; font-size: 11px;">
                        Попередній запис збережеться в історії. Користувачу автоматично буде надано статус члена ФСТУ.
                    </small>
                </div>

                <div style="text-align: right; border-top: 1px solid var(--fstu-border); padding-top: 15px;">
                    <button type="submit" class="fstu-btn fstu-btn--primary" id="fstu-ofst-submit">
                        <span class="fstu-btn__text">💾 Зберегти зміни</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>