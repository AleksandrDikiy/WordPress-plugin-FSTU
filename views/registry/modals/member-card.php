<?php
/**
 * View: Модальне вікно "Картка члена ФСТУ".
 * Містить структуру вкладок. Дані завантажуються через AJAX.
 *
 * Version:     1.2.1
 * Date_update: 2024-07-29
 *
 * @package FSTU\Registry\Views\Modals
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="fstu-modal-overlay fstu-hidden" id="fstu-modal-member-card" role="dialog" aria-modal="true" aria-labelledby="fstu-mc-title">
	<div class="fstu-modal fstu-modal--large">
		<div class="fstu-modal__header">
			<h2 class="fstu-modal__title" id="fstu-mc-title">Картка члена ФСТУ</h2>
			<button type="button" class="fstu-modal-close-btn" aria-label="Закрити">✕</button>
		</div>
		<div class="fstu-modal__body fstu-modal__body--nopad">

			<div class="fstu-tabs">
				<div class="fstu-tabs__nav" role="tablist">
					<button class="fstu-tabs__btn fstu-tabs__btn--active" role="tab" aria-selected="true" data-tab="mc-general">Загальні</button>
					<button class="fstu-tabs__btn" role="tab" aria-selected="false" data-tab="mc-clubs">Клуби</button>
					<button class="fstu-tabs__btn" role="tab" aria-selected="false" data-tab="mc-cities">Міста</button>
					<button class="fstu-tabs__btn" role="tab" aria-selected="false" data-tab="mc-tourism">Види туризму</button>
					<button class="fstu-tabs__btn" role="tab" aria-selected="false" data-tab="mc-experience">Досвід</button>
					<button class="fstu-tabs__btn" role="tab" aria-selected="false" data-tab="mc-ranks">Розряди</button>
					<button class="fstu-tabs__btn" role="tab" aria-selected="false" data-tab="mc-judging">Суддівство</button>
					<button class="fstu-tabs__btn" role="tab" aria-selected="false" data-tab="mc-dues">Внески</button>
				</div>

				<div class="fstu-tabs__content-wrap">
					<div class="fstu-loader-inline fstu-hidden" id="fstu-mc-loader">
						<span class="fstu-loader__spinner"></span> Завантаження даних...
					</div>

					<div class="fstu-alert fstu-hidden" id="fstu-mc-alert"></div>

					<div class="fstu-tabs__pane fstu-tabs__pane--active" id="mc-general" role="tabpanel">
						<div class="fstu-mc-general-grid">
							<div class="fstu-mc-photo-wrap">
								<img src="" alt="Фото" class="fstu-mc-photo" id="mc-photo">
								<p class="fstu-mc-pd-note">
									<span id="mc-pd-icon-ok" class="fstu-hidden">✔</span>
									<span id="mc-pd-icon-no">✖</span>
									<span id="mc-pd-text">Згода на показ персональних даних</span>
								</p>
							</div>
							<div class="fstu-mc-info-wrap">
								<table class="fstu-info-table">
									<tbody>
									<tr><th>ПІБ:</th><td id="mc-val-name">—</td></tr>
									<tr><th>Дата народження:</th><td id="mc-val-birth">—</td></tr>
									<tr><th>Email:</th><td id="mc-val-email">—</td></tr>
									<tr><th>Телефон:</th><td id="mc-val-phone">—</td></tr>
									<tr><th>Skype:</th><td id="mc-val-skype">—</td></tr>
									<tr><th>Facebook:</th><td id="mc-val-facebook">—</td></tr>
									</tbody>
								</table>
							</div>
						</div>
					</div>

					<div class="fstu-tabs__pane" id="mc-clubs" role="tabpanel">
						<div class="fstu-placeholder">Дані про клуби (в розробці...)</div>
					</div>
					<div class="fstu-tabs__pane" id="mc-cities" role="tabpanel">
						<div class="fstu-placeholder">Дані про міста (в розробці...)</div>
					</div>
					<div class="fstu-tabs__pane" id="mc-tourism" role="tabpanel">
						<div class="fstu-placeholder">Види туризму (в розробці...)</div>
					</div>
					<div class="fstu-tabs__pane" id="mc-experience" role="tabpanel">
						<div class="fstu-placeholder">Туристський досвід (в розробці...)</div>
					</div>
					<div class="fstu-tabs__pane" id="mc-ranks" role="tabpanel">
						<div class="fstu-placeholder">Спортивні розряди (в розробці...)</div>
					</div>
					<div class="fstu-tabs__pane" id="mc-judging" role="tabpanel">
						<div class="fstu-placeholder">Суддівські категорії (в розробці...)</div>
					</div>
					<div class="fstu-tabs__pane" id="mc-dues" role="tabpanel">
						<div class="fstu-placeholder">Історія внесків (в розробці...)</div>
					</div>
				</div>
			</div>

		</div>
	</div>
</div>
