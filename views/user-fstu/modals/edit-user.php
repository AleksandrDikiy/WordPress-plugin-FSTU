<?php
/**
 * View: Модальне вікно "Редагування користувача".
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<style>
    .fstu-edit-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px 12px; margin-bottom: 15px; }
    .fstu-edit-grid .col-span-3 { grid-column: span 3; }
    .fstu-edit-grid .col-span-2 { grid-column: span 2; }
    .fstu-edit-grid label { font-size: 11px; margin-bottom: 2px; display: block; color: var(--fstu-text-light); font-weight: 600; }
    .fstu-edit-grid input, .fstu-edit-grid select { padding: 4px 8px; font-size: 13px; width: 100%; border: 1px solid var(--fstu-border); border-radius: 3px; height: 28px; }
</style>

<div class="fstu-modal-overlay fstu-hidden" id="fstu-modal-edit-user" role="dialog" aria-modal="true">
    <div class="fstu-modal fstu-modal--large">
        <div class="fstu-modal__header">
            <h2 class="fstu-modal__title">Редагування персональних даних</h2>
            <button type="button" class="fstu-modal-close-btn" aria-label="Закрити">✕</button>
        </div>
        <div class="fstu-modal__body">

            <div class="fstu-loader-inline fstu-hidden" id="fstu-edit-loader">
                <span class="fstu-loader__spinner"></span> Завантаження даних...
            </div>

            <div class="fstu-alert fstu-hidden" id="fstu-edit-alert"></div>

            <form id="fstu-edit-user-form" class="fstu-hidden">
                <input type="hidden" name="edit_user_id" id="edit_user_id">

                <div class="fstu-edit-grid">
                    <div><label>Прізвище *</label><input type="text" name="last_name" id="edit_last_name" required></div>
                    <div><label>Ім'я *</label><input type="text" name="first_name" id="edit_first_name" required></div>
                    <div><label>По батькові *</label><input type="text" name="patronymic" id="edit_patronymic" required></div>

                    <div><label>Дата народження *</label><input type="date" name="birth_date" id="edit_birth_date" required></div>
                    <div>
                        <label>Стать *</label>
                        <select name="sex" id="edit_sex" required>
                            <option value="Ч">Чоловік</option>
                            <option value="Ж">Жінка</option>
                        </select>
                    </div>
                    <div><label>Email *</label><input type="email" name="email" id="edit_email" required></div>

                    <div><label>Мобільний</label><input type="text" name="phone_mobile" id="edit_phone_mobile"></div>
                    <div><label>Дод. телефон</label><input type="text" name="phone2" id="edit_phone2"></div>
                    <div><label>Телефон родичів</label><input type="text" name="phone_family" id="edit_phone_family"></div>

                    <div><label>Skype</label><input type="text" name="skype" id="edit_skype"></div>
                    <div class="col-span-2"><label>Посилання на Facebook</label><input type="url" name="facebook" id="edit_facebook"></div>

                    <div><label>Нік</label><input type="text" name="nickname" id="edit_nickname"></div>
                    <div class="col-span-2"><label>Адреса проживання</label><input type="text" name="adr" id="edit_adr"></div>

                    <div class="col-span-3"><label>Місце роботи, посада</label><input type="text" name="job" id="edit_job"></div>
                    <div class="col-span-3"><label>Освіта, фах, вчена ступінь</label><input type="text" name="education" id="edit_education"></div>
                </div>

                <div style="text-align: right; border-top: 1px solid var(--fstu-border); padding-top: 15px;">
                    <button type="submit" class="fstu-btn fstu-btn--primary" id="fstu-edit-submit">
                        <span class="fstu-btn__text">💾 Зберегти зміни</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>