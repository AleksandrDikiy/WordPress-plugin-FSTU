<?php
/**
 * View: Модальне вікно "Інформація про клуб".
 *
 * Version:     1.0.0
 * Date_update: 2026-04-04
 *
 * @package FSTU\UserFstu\Views\Modals
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="fstu-modal-overlay fstu-hidden" id="fstu-modal-club-info" role="dialog" aria-modal="true" aria-labelledby="fstu-club-title">
    <div class="fstu-modal fstu-modal--small">
        <div class="fstu-modal__header">
            <h2 class="fstu-modal__title" id="fstu-club-title">Інформація про клуб</h2>
            <button type="button" class="fstu-modal__close fstu-modal-close-btn" aria-label="Закрити">✕</button>
        </div>
        <div class="fstu-modal__body">

            <div class="fstu-loader-inline fstu-hidden" id="fstu-club-loader">
                <span class="fstu-loader__spinner"></span> Завантаження...
            </div>

            <div class="fstu-alert fstu-hidden" id="fstu-club-alert"></div>

            <table class="fstu-info-table" id="fstu-club-data">
                <tbody>
                <tr><th>Назва:</th><td id="club-val-name">—</td></tr>
                <tr><th>Місто:</th><td id="club-val-city">—</td></tr>
                </tbody>
            </table>

        </div>
    </div>
</div>