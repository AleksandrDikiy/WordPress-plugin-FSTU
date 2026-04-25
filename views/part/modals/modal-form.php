<?php
/**
 * Шаблон модального вікна для додавання/редагування персональної статистики.
 * Version: 1.0.0
 * Date_update: 2026-04-25
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div id="fstu-part-modal" class="fstu-modal" style="display: none;" aria-hidden="true">
    <div class="fstu-modal-overlay" tabindex="-1" data-micromodal-close></div>

    <div class="fstu-modal-container" role="dialog" aria-modal="true" aria-labelledby="fstu-part-modal-title">

        <div class="fstu-modal-header">
            <h2 class="fstu-modal-title" id="fstu-part-modal-title">
                Редагування статистики
            </h2>
            <button type="button" class="fstu-modal-close" aria-label="Закрити вікно" data-micromodal-close>&times;</button>
        </div>

        <form id="fstu-part-form">
            <div class="fstu-modal-body">
                <input type="text" name="fstu_website" class="fstu-hidden-field" style="display:none;" tabindex="-1" autocomplete="off">

                <input type="hidden" name="Part_ID" id="fstu-part-id" value="0">
                <input type="hidden" name="Calendar_ID" id="fstu-part-calendar-id" value="0">

                <div class="fstu-form-row fstu-mb-3">
                    <div class="fstu-col-12">
                        <label class="fstu-label">Захід (Назва з Календаря):</label>
                        <div id="fstu-part-calendar-name" class="fstu-readonly-text fstu-fw-bold">
                        </div>
                    </div>
                </div>

                <div class="fstu-form-row fstu-mb-3">
                    <div class="fstu-col-4">
                        <label for="fstu-part-distance" class="fstu-label">Пройдено (км):</label>
                        <input type="number" step="0.01" min="0" name="Part_Distance" id="fstu-part-distance" class="fstu-input" required>
                    </div>
                    <div class="fstu-col-4">
                        <label for="fstu-part-maxspeed" class="fstu-label">Макс. швидкість (км/год):</label>
                        <input type="number" step="0.01" min="0" name="Part_MaxSpeed" id="fstu-part-maxspeed" class="fstu-input">
                    </div>
                    <div class="fstu-col-4">
                        <label for="fstu-part-avgspeed" class="fstu-label">Серед. швидкість (км/год):</label>
                        <input type="number" step="0.01" min="0" name="Part_AverageSpeed" id="fstu-part-avgspeed" class="fstu-input">
                    </div>
                </div>

                <div class="fstu-form-row fstu-mb-3">
                    <div class="fstu-col-12">
                        <label for="fstu-part-url" class="fstu-label">Посилання на трек (Strava, Garmin, GPSies):</label>
                        <input type="url" name="Part_URL" id="fstu-part-url" class="fstu-input" placeholder="https://www...">
                    </div>
                </div>

                <div class="fstu-form-row">
                    <div class="fstu-col-12">
                        <label for="fstu-part-note" class="fstu-label">Опис / Нотатки:</label>
                        <textarea name="Part_Note" id="fstu-part-note" class="fstu-textarea" rows="3" placeholder="Додайте враження або важливі деталі..."></textarea>
                    </div>
                </div>
            </div>

            <div class="fstu-modal-footer">
                <button type="button" class="fstu-btn fstu-btn--cancel" data-micromodal-close>Скасувати</button>
                <button type="submit" class="fstu-btn fstu-btn--save">Зберегти</button>
            </div>
        </form>
    </div>
</div>