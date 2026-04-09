<?php
/**
 * Модальне вікно перегляду картки стернового.
 *
 * Version:     1.5.0
 * Date_update: 2026-04-08
 *
 * @package FSTU\Modules\Registry\Steering
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-steering-view-modal" class="fstu-modal-overlay fstu-hidden" role="dialog" aria-modal="true" aria-labelledby="fstu-steering-view-modal-title">
	<div class="fstu-modal fstu-modal--wide fstu-modal--steering-view">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-steering-view-modal-title"><?php esc_html_e( 'Картка стернового', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-steering-view-modal">×</button>
		</div>
		<div class="fstu-modal__body fstu-modal__body--compact-view">
			<div id="fstu-steering-view-message" class="fstu-form-message fstu-hidden"></div>
			<div class="fstu-view-layout">
				<div class="fstu-view-section">
					<h4 class="fstu-form-section__title"><?php esc_html_e( 'Основні дані', 'fstu' ); ?></h4>
					<div class="fstu-view-grid">
						<div class="fstu-view-grid__row"><span class="fstu-view-grid__label"><?php esc_html_e( 'ПІБ', 'fstu' ); ?></span><span class="fstu-view-grid__value" id="fstu-steering-view-fio">—</span></div>
						<div class="fstu-view-grid__row"><span class="fstu-view-grid__label"><?php esc_html_e( 'ПІБ (ENG)', 'fstu' ); ?></span><span class="fstu-view-grid__value" id="fstu-steering-view-fio-eng">—</span></div>
						<div class="fstu-view-grid__row"><span class="fstu-view-grid__label"><?php esc_html_e( 'Підстава', 'fstu' ); ?></span><span class="fstu-view-grid__value" id="fstu-steering-view-type-app">—</span></div>
						<div class="fstu-view-grid__row"><span class="fstu-view-grid__label"><?php esc_html_e( '№ посвідчення', 'fstu' ); ?></span><span class="fstu-view-grid__value" id="fstu-steering-view-number">—</span></div>
						<div class="fstu-view-grid__row"><span class="fstu-view-grid__label"><?php esc_html_e( 'Статус', 'fstu' ); ?></span><span class="fstu-view-grid__value" id="fstu-steering-view-status">—</span></div>
						<div class="fstu-view-grid__row"><span class="fstu-view-grid__label"><?php esc_html_e( 'Дата документа', 'fstu' ); ?></span><span class="fstu-view-grid__value" id="fstu-steering-view-date-pay">—</span></div>
						<div class="fstu-view-grid__row"><span class="fstu-view-grid__label"><?php esc_html_e( 'Дата народження', 'fstu' ); ?></span><span class="fstu-view-grid__value" id="fstu-steering-view-birth-date">—</span></div>
					</div>
				</div>
				<div class="fstu-view-section">
					<h4 class="fstu-form-section__title"><?php esc_html_e( 'Поштова і службова інформація', 'fstu' ); ?></h4>
					<div class="fstu-view-grid">
						<div class="fstu-view-grid__row"><span class="fstu-view-grid__label"><?php esc_html_e( 'Місто НП', 'fstu' ); ?></span><span class="fstu-view-grid__value" id="fstu-steering-view-city-np">—</span></div>
						<div class="fstu-view-grid__row"><span class="fstu-view-grid__label"><?php esc_html_e( '№ НП', 'fstu' ); ?></span><span class="fstu-view-grid__value" id="fstu-steering-view-number-np">—</span></div>
						<div class="fstu-view-grid__row"><span class="fstu-view-grid__label"><?php esc_html_e( 'Посилання', 'fstu' ); ?></span><span class="fstu-view-grid__value" id="fstu-steering-view-url">—</span></div>
						<div class="fstu-view-grid__row fstu-steering-admin-row"><span class="fstu-view-grid__label"><?php esc_html_e( 'Дата створення', 'fstu' ); ?></span><span class="fstu-view-grid__value" id="fstu-steering-view-date-create">—</span></div>
						<div class="fstu-view-grid__row fstu-steering-admin-row"><span class="fstu-view-grid__label"><?php esc_html_e( 'Дата відправки', 'fstu' ); ?></span><span class="fstu-view-grid__value" id="fstu-steering-view-date-delivery">—</span></div>
						<div class="fstu-view-grid__row"><span class="fstu-view-grid__label"><?php esc_html_e( 'Підтверджень', 'fstu' ); ?></span><span class="fstu-view-grid__value" id="fstu-steering-view-verifications">0</span></div>
					</div>
				</div>
				<div class="fstu-view-section">
					<h4 class="fstu-form-section__title"><?php esc_html_e( 'Фото', 'fstu' ); ?></h4>
					<div class="fstu-steering-photo-wrap">
						<img id="fstu-steering-view-photo" class="fstu-steering-photo" src="" alt="<?php esc_attr_e( 'Фото стернового', 'fstu' ); ?>">
					</div>
				</div>
				<div class="fstu-view-section fstu-view-section--verify">
					<h4 class="fstu-form-section__title"><?php esc_html_e( 'Підтвердження кваліфікації', 'fstu' ); ?></h4>
					<div class="fstu-view-grid">
						<div class="fstu-view-grid__row"><span class="fstu-view-grid__label"><?php esc_html_e( 'Прогрес', 'fstu' ); ?></span><span class="fstu-view-grid__value" id="fstu-steering-view-verification-progress">0 / 3</span></div>
						<div class="fstu-view-grid__row"><span class="fstu-view-grid__label"><?php esc_html_e( 'Стан підтвердження', 'fstu' ); ?></span><span class="fstu-view-grid__value" id="fstu-steering-view-verification-state">—</span></div>
					</div>
					<div class="fstu-steering-verify-actions">
						<button type="button" id="fstu-steering-confirm-btn" class="fstu-btn fstu-btn--primary fstu-hidden"><?php esc_html_e( 'ПІДТВЕРДИТИ', 'fstu' ); ?></button>
					</div>
					<div class="fstu-steering-verifiers">
						<div class="fstu-steering-verifiers__title"><?php esc_html_e( 'Особи, які підтвердили кваліфікацію', 'fstu' ); ?></div>
						<div id="fstu-steering-view-verifiers-list" class="fstu-steering-verifiers__list"></div>
					</div>
				</div>
				<div class="fstu-view-section fstu-view-section--status-actions">
					<h4 class="fstu-form-section__title"><?php esc_html_e( 'Статусні дії', 'fstu' ); ?></h4>
					<div class="fstu-steering-status-actions">
						<button type="button" id="fstu-steering-register-btn" class="fstu-btn fstu-hidden"><?php esc_html_e( 'ЗАРЕЄСТРУВАТИ', 'fstu' ); ?></button>
						<button type="button" id="fstu-steering-send-post-btn" class="fstu-btn fstu-hidden"><?php esc_html_e( 'ВІДПРАВЛЕНО ПОШТОЮ', 'fstu' ); ?></button>
						<button type="button" id="fstu-steering-received-btn" class="fstu-btn fstu-hidden"><?php esc_html_e( 'ДОСТАВЛЕНО ОДЕРЖУВАЧУ', 'fstu' ); ?></button>
					</div>
				</div>
			</div>
		</div>
		<div class="fstu-modal__footer">
			<button type="button" class="fstu-btn fstu-hidden" id="fstu-steering-edit-btn"><?php esc_html_e( 'РЕДАГУВАТИ', 'fstu' ); ?></button>
			<button type="button" class="fstu-btn fstu-hidden" id="fstu-steering-delete-btn"><?php esc_html_e( 'ВИДАЛИТИ', 'fstu' ); ?></button>
			<button type="button" class="fstu-btn" data-close-modal="fstu-steering-view-modal"><?php esc_html_e( 'Закрити', 'fstu' ); ?></button>
		</div>
	</div>
</div>

