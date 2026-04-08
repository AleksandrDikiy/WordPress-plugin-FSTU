<?php
/**
 * Модальне вікно перегляду картки судді.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-08
 *
 * @package FSTU\Modules\Registry\Referees
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-referees-view-modal" class="fstu-modal-overlay fstu-hidden" role="dialog" aria-modal="true" aria-labelledby="fstu-referees-view-modal-title">
	<div class="fstu-modal fstu-modal--wide fstu-modal--referees-view">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-referees-view-modal-title"><?php esc_html_e( 'Картка судді', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-referees-view-modal">×</button>
		</div>
		<div class="fstu-modal__body fstu-modal__body--compact-view">
			<div id="fstu-referees-view-message" class="fstu-form-message fstu-hidden"></div>
			<div class="fstu-view-layout">
				<div class="fstu-view-section">
					<h4 class="fstu-form-section__title"><?php esc_html_e( 'Основні дані', 'fstu' ); ?></h4>
					<div class="fstu-view-grid">
						<div class="fstu-view-grid__row"><span class="fstu-view-grid__label"><?php esc_html_e( 'ПІБ', 'fstu' ); ?></span><span class="fstu-view-grid__value" id="fstu-referees-view-fio">—</span></div>
						<div class="fstu-view-grid__row"><span class="fstu-view-grid__label"><?php esc_html_e( 'Категорія', 'fstu' ); ?></span><span class="fstu-view-grid__value" id="fstu-referees-view-category">—</span></div>
						<div class="fstu-view-grid__row"><span class="fstu-view-grid__label"><?php esc_html_e( 'Регіон', 'fstu' ); ?></span><span class="fstu-view-grid__value" id="fstu-referees-view-region">—</span></div>
						<div class="fstu-view-grid__row"><span class="fstu-view-grid__label"><?php esc_html_e( 'Рег. №', 'fstu' ); ?></span><span class="fstu-view-grid__value" id="fstu-referees-view-card-number">—</span></div>
					</div>
				</div>
				<div class="fstu-view-section">
					<h4 class="fstu-form-section__title"><?php esc_html_e( 'Наказ', 'fstu' ); ?></h4>
					<div class="fstu-view-grid">
						<div class="fstu-view-grid__row"><span class="fstu-view-grid__label"><?php esc_html_e( 'Номер наказу', 'fstu' ); ?></span><span class="fstu-view-grid__value" id="fstu-referees-view-order-number">—</span></div>
						<div class="fstu-view-grid__row"><span class="fstu-view-grid__label"><?php esc_html_e( 'Дата наказу', 'fstu' ); ?></span><span class="fstu-view-grid__value" id="fstu-referees-view-order-date">—</span></div>
						<div class="fstu-view-grid__row"><span class="fstu-view-grid__label"><?php esc_html_e( 'Посилання', 'fstu' ); ?></span><span class="fstu-view-grid__value" id="fstu-referees-view-order-url">—</span></div>
					</div>
				</div>
				<div class="fstu-view-section">
					<h4 class="fstu-form-section__title"><?php esc_html_e( 'Службова інформація', 'fstu' ); ?></h4>
					<div class="fstu-view-grid">
						<div class="fstu-view-grid__row"><span class="fstu-view-grid__label"><?php esc_html_e( 'Дата створення', 'fstu' ); ?></span><span class="fstu-view-grid__value" id="fstu-referees-view-created-date">—</span></div>
						<div class="fstu-view-grid__row"><span class="fstu-view-grid__label"><?php esc_html_e( 'Хто створив', 'fstu' ); ?></span><span class="fstu-view-grid__value" id="fstu-referees-view-created-by">—</span></div>
						<div class="fstu-view-grid__row"><span class="fstu-view-grid__label"><?php esc_html_e( 'Довідок', 'fstu' ); ?></span><span class="fstu-view-grid__value" id="fstu-referees-view-certificates-count">0</span></div>
					</div>
				</div>
			</div>
			<div class="fstu-view-section fstu-view-section--certificates-preview">
				<div class="fstu-view-section__header">
					<h4 class="fstu-form-section__title"><?php esc_html_e( 'Останні довідки за суддівство', 'fstu' ); ?></h4>
					<button type="button" class="fstu-btn fstu-btn--secondary fstu-btn--small" id="fstu-referees-view-open-certificates-btn"><?php esc_html_e( 'УСІ ДОВІДКИ', 'fstu' ); ?></button>
				</div>
				<div id="fstu-referees-view-certificates-preview" class="fstu-referees-certificates-preview">—</div>
			</div>
		</div>
		<div class="fstu-modal__footer">
			<button type="button" class="fstu-btn" data-close-modal="fstu-referees-view-modal"><?php esc_html_e( 'Закрити', 'fstu' ); ?></button>
		</div>
	</div>
</div>

