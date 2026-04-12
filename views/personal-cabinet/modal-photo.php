<?php
/**
 * View: Модальне вікно оновлення фото профілю.
 * Version: 1.0.0
 * Date_update: 2026-04-11
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="fstu-modal-overlay fstu-hidden" id="fstu-personal-photo-modal" style="z-index: 100001;">
    <div class="fstu-modal" style="max-width: 400px;">
        <div class="fstu-modal__header">
            <h3 class="fstu-modal__title">Оновити фото профілю</h3>
            <button type="button" class="fstu-modal__close" id="fstu-personal-photo-modal-close">×</button>
        </div>
        <div class="fstu-modal__body">
            <div id="fstu-personal-photo-alert" class="fstu-alert fstu-alert--error fstu-hidden" style="margin-bottom: 12px;"></div>
            <form id="fstu-personal-photo-form" enctype="multipart/form-data">
                <div class="fstu-form-group">
                    <label class="fstu-label">Виберіть файл (JPG, max 2MB)</label>
                    <input type="file" name="profile_photo" id="fstu-personal-photo-input" class="fstu-input" accept="image/jpeg" required>
                </div>
                <div class="fstu-personal-form__actions" style="margin-top: 16px;">
                    <button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-personal-photo-cancel">Скасувати</button>
                    <button type="submit" class="fstu-btn fstu-btn--primary" id="fstu-personal-photo-submit">Завантажити</button>
                </div>
            </form>
        </div>
    </div>
</div>