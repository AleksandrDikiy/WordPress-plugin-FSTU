<?php
namespace FSTU\Modules\Applications;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div id="fstu-applications-modal-change-ofst" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
    <div class="fstu-modal fstu-modal--compact" role="dialog" aria-labelledby="fstu-applications-modal-change-ofst-title">
        <div class="fstu-modal__header">
            <h3 id="fstu-applications-modal-change-ofst-title" class="fstu-modal__title">Зміна ОФСТ</h3>
            <button type="button" class="fstu-modal__close" data-close-modal="fstu-applications-modal-change-ofst" aria-label="Закрити">×</button>
        </div>
        <div class="fstu-modal__body">
            <form id="fstu-applications-change-ofst-form" class="fstu-app-form">
                <input type="hidden" name="user_id" id="fstu-applications-change-ofst-user-id" value="0">
                <label for="fstu-applications-change-ofst-honeypot" class="screen-reader-text">Не заповнюйте це поле</label>
                <input type="text" name="fstu_website" id="fstu-applications-change-ofst-honeypot" class="fstu-hidden-field" tabindex="-1" autocomplete="off" value="">

                <div id="fstu-applications-change-ofst-message" class="fstu-form-message fstu-hidden"></div>

                <div class="fstu-app-card__section">
                    <h4 class="fstu-app-card__title">Поточний стан</h4>
                    <div class="fstu-app-card__grid">
                        <div class="fstu-app-card__row">
                            <span class="fstu-app-card__label">Кандидат</span>
                            <span class="fstu-app-card__value" id="fstu-applications-change-ofst-candidate-name">—</span>
                        </div>
                        <div class="fstu-app-card__row">
                            <span class="fstu-app-card__label">Поточний осередок</span>
                            <span class="fstu-app-card__value" id="fstu-applications-change-ofst-current-unit">—</span>
                        </div>
                    </div>
                </div>

                <div class="fstu-form-group">
                    <label class="fstu-label" for="fstu-applications-change-ofst-unit">Оберіть осередок</label>
                    <select id="fstu-applications-change-ofst-unit" name="unit_id" class="fstu-input">
                        <option value="0">— оберіть осередок —</option>
                    </select>
                    <p class="fstu-hint">Операція оновить актуальний запис та збереже історію змін у `UserRegistationOFST`.</p>
                </div>

                <div class="fstu-form-actions">
                    <button type="button" class="fstu-btn" data-close-modal="fstu-applications-modal-change-ofst">Скасувати</button>
                    <button type="submit" class="fstu-btn fstu-btn--primary">Зберегти</button>
                </div>
            </form>
        </div>
    </div>
</div>

