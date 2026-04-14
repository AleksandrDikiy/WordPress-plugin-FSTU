<?php
/**
 * Модальне вікно перегляду сучасної картки стернового.
 *
 * Version:     2.0.0
 * Date_update: 2026-04-14
 *
 * @package FSTU\Modules\Registry\Steering
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div id="fstu-steering-view-modal" class="fstu-modal-overlay fstu-hidden" role="dialog" aria-modal="true" aria-labelledby="fstu-steering-view-modal-title">
    <div class="fstu-modal fstu-modal--modern-card">
        <div class="fstu-modal__header">
            <h3 class="fstu-modal__title" id="fstu-steering-view-modal-title"><?php esc_html_e( 'Картка стернового', 'fstu' ); ?></h3>
            <button type="button" class="fstu-modal__close" data-close-modal="fstu-steering-view-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">&times;</button>
        </div>

        <div class="fstu-modal__tabs-nav">
            <button type="button" class="fstu-tab-btn is-active" data-target="fstu-tab-public"><?php esc_html_e( 'Публічна', 'fstu' ); ?></button>
            <button type="button" class="fstu-tab-btn fstu-hidden" id="fstu-steering-tab-service-btn" data-target="fstu-tab-service"><?php esc_html_e( 'Службова', 'fstu' ); ?></button>
        </div>

        <div class="fstu-modal__body fstu-modal__body--tabs">
            <div id="fstu-steering-view-message" class="fstu-form-message fstu-hidden"></div>

            <div id="fstu-tab-public" class="fstu-tab-pane is-active">
                <div class="fstu-card-grid">
                    <div class="fstu-card-grid__col fstu-card-grid__col--photo">
                        <div class="fstu-steering-photo-wrap">
                            <img id="fstu-steering-view-photo" class="fstu-steering-photo fstu-hidden" src="" alt="<?php esc_attr_e( 'Фото', 'fstu' ); ?>">
                        </div>
                    </div>

                    <div class="fstu-card-grid__col fstu-card-grid__col--info">
                        <h4 class="fstu-form-section__title"><?php esc_html_e( 'Основні дані', 'fstu' ); ?></h4>
                        <div class="fstu-view-data-list">
                            <div class="fstu-view-data-row"><span class="fstu-view-data-label"><?php esc_html_e( 'ПІБ', 'fstu' ); ?></span><span class="fstu-view-data-value fstu-text-bold" id="fstu-steering-view-fio">—</span></div>
                            <div class="fstu-view-data-row"><span class="fstu-view-data-label"><?php esc_html_e( 'ПІБ (ENG)', 'fstu' ); ?></span><span class="fstu-view-data-value" id="fstu-steering-view-fio-eng">—</span></div>
                            <div class="fstu-view-data-row"><span class="fstu-view-data-label"><?php esc_html_e( '№ посвідчення', 'fstu' ); ?></span><span class="fstu-view-data-value" id="fstu-steering-view-number">—</span></div>
                            <div class="fstu-view-data-row"><span class="fstu-view-data-label"><?php esc_html_e( 'Статус', 'fstu' ); ?></span><span class="fstu-view-data-value" id="fstu-steering-view-status">—</span></div>
                            <div class="fstu-view-data-row"><span class="fstu-view-data-label"><?php esc_html_e( 'Дата документа', 'fstu' ); ?></span><span class="fstu-view-data-value" id="fstu-steering-view-date-pay">—</span></div>
                            <div class="fstu-view-data-row"><span class="fstu-view-data-label"><?php esc_html_e( 'Дата народження', 'fstu' ); ?></span><span class="fstu-view-data-value" id="fstu-steering-view-birth-date">—</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="fstu-tab-service" class="fstu-tab-pane fstu-hidden">
                <div class="fstu-card-grid">
                    <div class="fstu-card-grid__col">
                        <h4 class="fstu-form-section__title"><?php esc_html_e( 'Поштова і службова інформація', 'fstu' ); ?></h4>
                        <div class="fstu-view-data-list">
                            <div class="fstu-view-data-row"><span class="fstu-view-data-label"><?php esc_html_e( 'Місто НП', 'fstu' ); ?></span><span class="fstu-view-data-value" id="fstu-steering-view-city-np">—</span></div>
                            <div class="fstu-view-data-row"><span class="fstu-view-data-label"><?php esc_html_e( '№ НП', 'fstu' ); ?></span><span class="fstu-view-data-value" id="fstu-steering-view-number-np">—</span></div>
                            <div class="fstu-view-data-row"><span class="fstu-view-data-label"><?php esc_html_e( 'Посилання', 'fstu' ); ?></span><span class="fstu-view-data-value" id="fstu-steering-view-url">—</span></div>
                            <div class="fstu-view-data-row fstu-steering-admin-row"><span class="fstu-view-data-label"><?php esc_html_e( 'Дата створення', 'fstu' ); ?></span><span class="fstu-view-data-value" id="fstu-steering-view-date-create">—</span></div>
                            <div class="fstu-view-data-row fstu-steering-admin-row"><span class="fstu-view-data-label"><?php esc_html_e( 'Дата відправки', 'fstu' ); ?></span><span class="fstu-view-data-value" id="fstu-steering-view-date-delivery">—</span></div>

                            <hr class="fstu-view-data-divider">

                            <div class="fstu-view-data-row"><span class="fstu-view-data-label"><?php esc_html_e( 'Підтверджень', 'fstu' ); ?></span><span class="fstu-view-data-value" id="fstu-steering-view-verifications">0</span></div>

                            <hr class="fstu-view-data-divider">

                            <div class="fstu-view-data-row fstu-view-data-row--stacked">
                                <span class="fstu-view-data-label"><?php esc_html_e( 'Підстава', 'fstu' ); ?></span>
                                <span class="fstu-view-data-value fstu-text-small" id="fstu-steering-view-type-app">—</span>
                            </div>
                        </div>
                    </div>

                    <div class="fstu-card-grid__col">
                        <h4 class="fstu-form-section__title"><?php esc_html_e( 'Підтвердження кваліфікації', 'fstu' ); ?></h4>
                        <div class="fstu-view-data-list">
                            <div class="fstu-view-data-row"><span class="fstu-view-data-label"><?php esc_html_e( 'Прогрес', 'fstu' ); ?></span><span class="fstu-view-data-value fstu-text-bold" id="fstu-steering-view-verification-progress">0 / 3</span></div>
                            <div class="fstu-view-data-row"><span class="fstu-view-data-label"><?php esc_html_e( 'Стан', 'fstu' ); ?></span><span class="fstu-view-data-value" id="fstu-steering-view-verification-state">—</span></div>
                        </div>

                        <div class="fstu-steering-verify-actions">
                            <button type="button" id="fstu-steering-confirm-btn" class="fstu-btn fstu-btn--primary fstu-hidden"><?php esc_html_e( 'ПІДТВЕРДИТИ', 'fstu' ); ?></button>
                        </div>

                        <div class="fstu-steering-verifiers">
                            <div class="fstu-steering-verifiers__title"><?php esc_html_e( 'Особи, які підтвердили кваліфікацію', 'fstu' ); ?></div>
                            <div id="fstu-steering-view-verifiers-list" class="fstu-steering-verifiers__list"></div>
                        </div>

                        <div class="fstu-steering-status-actions fstu-mt-4">
                            <button type="button" id="fstu-steering-register-btn" class="fstu-btn fstu-btn--small fstu-hidden"><?php esc_html_e( 'ЗАРЕЄСТРУВАТИ', 'fstu' ); ?></button>
                            <button type="button" id="fstu-steering-send-post-btn" class="fstu-btn fstu-btn--small fstu-hidden"><?php esc_html_e( 'ВІДПРАВЛЕНО ПОШТОЮ', 'fstu' ); ?></button>
                            <button type="button" id="fstu-steering-received-btn" class="fstu-btn fstu-btn--small fstu-hidden"><?php esc_html_e( 'ДОСТАВЛЕНО', 'fstu' ); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="fstu-modal__footer">
            <button type="button" class="fstu-btn fstu-hidden" id="fstu-steering-edit-btn"><?php esc_html_e( 'РЕДАГУВАТИ', 'fstu' ); ?></button>
            <button type="button" class="fstu-btn fstu-btn--danger fstu-hidden" id="fstu-steering-delete-btn"><?php esc_html_e( 'ВИДАЛИТИ', 'fstu' ); ?></button>
            <button type="button" class="fstu-btn fstu-btn--secondary" data-close-modal="fstu-steering-view-modal"><?php esc_html_e( 'Закрити', 'fstu' ); ?></button>
        </div>
    </div>
</div>