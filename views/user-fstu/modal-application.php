<?php
/**
 * View: Модальне вікно "Заявка вступу до ФСТУ" (ПОВНА ВЕРСІЯ).
 *
 * Version:     1.2.0
 * Date_update: 2026-04-07
 *
 * @package FSTU\UserFstu\Views
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

// Завантажуємо довідники
// phpcs:disable WordPress.DB.DirectDatabaseQuery
$regions       = $wpdb->get_results( "SELECT Region_ID, Region_Name FROM S_Region ORDER BY Region_Order ASC", ARRAY_A ) ?? [];
$tourism_types = $wpdb->get_results( "SELECT TourismType_ID, TourismType_Name FROM S_TourismType ORDER BY TourismType_Name ASC", ARRAY_A ) ?? [];
$clubs         = $wpdb->get_results( "SELECT Club_ID, Club_Name FROM S_Club ORDER BY Club_Name ASC", ARRAY_A ) ?? [];

// Використовуємо правильні таблиці ФСТУ для кваліфікації та суддів
$sports_categories  = $wpdb->get_results( "SELECT SportsCategories_ID, SportsCategories_Name FROM S_SportsCategories ORDER BY SportsCategories_Order ASC", ARRAY_A ) ?? [];
$referee_categories = $wpdb->get_results( "
	SELECT 0 as RefereeCategory_ID, 'немає суддівської категорії' as RefereeCategory_Name, 0 as RefereeCategory_Order
	UNION SELECT RefereeCategory_ID, RefereeCategory_Name, RefereeCategory_Order FROM vRefereeCategory
	ORDER BY RefereeCategory_Order ASC
", ARRAY_A ) ?? [];
// phpcs:enable
?>

<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

<div class="fstu-modal-overlay fstu-hidden" id="fstu-modal-application" role="dialog" aria-modal="true" aria-labelledby="fstu-modal-title">
    <div class="fstu-modal">
        <div class="fstu-modal__header">
            <h2 class="fstu-modal__title" id="fstu-modal-title">Заявка вступу до ФСТУ</h2>
            <button type="button" class="fstu-modal__close" id="fstu-modal-close" aria-label="Закрити">✕</button>
        </div>

        <div class="fstu-modal__body">
            <div class="fstu-app-step fstu-app-step--compact" id="fstu-app-step-terms">
                <label class="fstu-terms-label fstu-terms-label--top" for="fstu-terms-agree-top">
                    <input type="checkbox" id="fstu-terms-agree-top" class="fstu-checkbox">
                    <span>
						Прошу прийняти мене до Федерації спортивного туризму України.<br>
						Зі <a href="https://www.fstu.com.ua/statut/" target="_blank" rel="noopener noreferrer">Статутом Федерації спортивного туризму України</a> ознайомлений(-а), визнаю і зобов'язуюся виконувати.
					</span>
                </label>
            </div>

            <form class="fstu-app-form fstu-hidden" id="fstu-application-form" novalidate>
                <?php wp_nonce_field( \FSTU\UserFstu\User_Fstu_List::NONCE_ACTION, 'fstu_app_nonce' ); ?>
                <div class="fstu-honeypot" aria-hidden="true">
                    <input type="text" name="fstu_website" tabindex="-1" autocomplete="off" value="">
                </div>

                <div class="fstu-form-row fstu-form-row--3col">
                    <div class="fstu-form-group">
                        <label class="fstu-label fstu-label--required">Прізвище</label>
                        <input type="text" name="last_name" class="fstu-input" required>
                    </div>
                    <div class="fstu-form-group">
                        <label class="fstu-label fstu-label--required">Ім'я</label>
                        <input type="text" name="first_name" class="fstu-input" required>
                    </div>
                    <div class="fstu-form-group">
                        <label class="fstu-label">По батькові</label>
                        <input type="text" name="patronymic" class="fstu-input">
                    </div>
                </div>

                <div class="fstu-form-row fstu-form-row--2col">
                    <div class="fstu-form-group">
                        <label class="fstu-label fstu-label--required">Дата народження</label>
                        <input type="date" name="birth_date" class="fstu-input" required>
                    </div>
                    <div class="fstu-form-group">
                        <label class="fstu-label fstu-label--required">Стать</label>
                        <div style="display:flex; gap: 15px; margin-top: 5px;">
                            <label><input type="radio" name="sex" value="M" checked> ЧОЛОВІК</label>
                            <label><input type="radio" name="sex" value="F"> ЖІНКА</label>
                        </div>
                    </div>
                </div>

                <div class="fstu-form-row fstu-form-row--2col">
                    <div class="fstu-form-group">
                        <label class="fstu-label fstu-label--required">Телефон</label>
                        <input type="tel" name="phone" class="fstu-input" placeholder="+380XXXXXXXXX" required>
                    </div>
                    <div class="fstu-form-group">
                        <label class="fstu-label">Додатковий телефон</label>
                        <input type="tel" name="phone_alt" class="fstu-input" placeholder="+380XXXXXXXXX">
                    </div>
                </div>

                <div class="fstu-form-row fstu-form-row--2col">
                    <div class="fstu-form-group">
                        <label class="fstu-label fstu-label--required">Область</label>
                        <select id="fstu-app-region" name="region_id" class="fstu-select" required>
                            <option value="">— оберіть область —</option>
                            <?php foreach ( $regions as $r ) : ?>
                                <option value="<?php echo absint( $r['Region_ID'] ); ?>"><?php echo esc_html( $r['Region_Name'] ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fstu-form-group">
                        <label class="fstu-label fstu-label--required">Місто проживання</label>
                        <select id="fstu-app-city" name="city_id" class="fstu-select" required disabled>
                            <option value="">— оберіть область —</option>
                        </select>
                    </div>
                </div>

                <div class="fstu-form-row fstu-form-row--2col">
                    <div class="fstu-form-group">
                        <label class="fstu-label fstu-label--required">Основний вид туризму</label>
                        <select name="tourism_type_id" class="fstu-select" required>
                            <option value="">— оберіть вид туризму —</option>
                            <?php foreach ( $tourism_types as $tt ) : ?>
                                <option value="<?php echo absint( $tt['TourismType_ID'] ); ?>"><?php echo esc_html( $tt['TourismType_Name'] ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fstu-form-group">
                        <label class="fstu-label">Участь у клубі</label>
                        <select name="club_id" class="fstu-select">
                            <option value="0">не є учасником клубу</option>
                            <?php foreach ( $clubs as $club ) : ?>
                                <option value="<?php echo absint( $club['Club_ID'] ); ?>"><?php echo esc_html( $club['Club_Name'] ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="fstu-form-row fstu-form-row--2col">
                    <div class="fstu-form-group">
                        <label class="fstu-label">Спортивна кваліфікація</label>
                        <select name="sports_category_id" class="fstu-select">
                            <option value="0">без розряду</option>
                            <?php foreach ( $sports_categories as $sc ) : ?>
                                <option value="<?php echo absint( $sc['SportsCategories_ID'] ); ?>"><?php echo esc_html( $sc['SportsCategories_Name'] ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fstu-form-group">
                        <label class="fstu-label">Суддівська категорія</label>
                        <select name="referee_category_id" class="fstu-select">
                            <?php foreach ( $referee_categories as $rc ) : ?>
                                <option value="<?php echo absint( $rc['RefereeCategory_ID'] ); ?>"><?php echo esc_html( $rc['RefereeCategory_Name'] ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="fstu-form-group fstu-form-group--compact-gap">
                    <label class="fstu-label">Громадські туристські звання</label>
                    <textarea name="public_titles" class="fstu-input fstu-textarea fstu-textarea--compact"></textarea>
                </div>

                <div class="fstu-form-group fstu-form-group--compact-gap">
                    <label class="fstu-label fstu-label--required">Реєстрація в ОФСТ</label>
                    <select id="fstu-app-unit" name="unit_id" class="fstu-select" required disabled>
                        <option value="">— оберіть область —</option>
                    </select>
                </div>

                <div class="fstu-form-group fstu-form-group--compact-gap">
                    <label class="fstu-label fstu-label--required">e-mail (login)</label>
                    <input type="email" id="fstu-app-email" name="email" class="fstu-input" required>
                    <small id="fstu-email-hint" class="fstu-hint fstu-hidden">Перевірка...</small>
                </div>

                <div class="fstu-form-row fstu-form-row--2col">
                    <div class="fstu-form-group">
                        <label class="fstu-label fstu-label--required">Пароль</label>
                        <input type="password" id="fstu-app-pass" name="password" class="fstu-input" required>
                    </div>
                    <div class="fstu-form-group">
                        <label class="fstu-label fstu-label--required">Підтвердіть пароль</label>
                        <input type="password" id="fstu-app-pass-confirm" class="fstu-input" required>
                    </div>
                </div>

                <div class="fstu-form-message fstu-hidden" id="fstu-app-message" role="alert"></div>

                <div class="fstu-app-bottom-bar">
                    <div class="fstu-app-bottom-bar__agree">
                        <label class="fstu-terms-label fstu-terms-label--bottom" for="fstu-terms-agree-bottom">
                            <input type="checkbox" id="fstu-terms-agree-bottom" class="fstu-checkbox">
                            <span>Я згоден(-а) з <a href="https://www.fstu.com.ua/zgoda/" target="_blank" rel="noopener noreferrer">умовами</a>.</span>
                        </label>
                    </div>
                    <div class="fstu-app-bottom-bar__right">
                        <div class="fstu-form-group fstu-form-group--turnstile">
                            <div class="cf-turnstile" data-sitekey="<?php echo esc_attr( defined( 'FSTU_TURNSTILE_SITE_KEY' ) ? FSTU_TURNSTILE_SITE_KEY : '' ); ?>" data-callback="fstuOnTurnstileSuccess" data-expired-callback="fstuOnTurnstileExpired"></div>
                        </div>
                        <div class="fstu-form-actions fstu-form-actions--compact">
                            <button type="submit" class="fstu-btn fstu-btn--primary fstu-btn--submit" id="fstu-app-submit" disabled>
                                <span class="fstu-btn__text">Надіслати заявку</span>
                                <span class="fstu-btn__loader fstu-hidden"></span>
                            </button>
                            <button type="button" class="fstu-btn fstu-btn--text" id="fstu-app-cancel">Скасувати</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>