<?php
/**
 * View: Модальне вікно "Картка члена ФСТУ".
 * Містить структуру вкладок. Дані завантажуються через AJAX.
 *
 * Version:     1.2.0
 * Date_update: 2026-04-04
 *
 * @package FSTU\UserFstu\Views\Modals
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
                    <button class="fstu-tabs__btn fstu-hidden" role="tab" aria-selected="false" data-tab="mc-private" id="mc-tab-private">Приватне</button>
                    <button class="fstu-tabs__btn fstu-hidden" role="tab" aria-selected="false" data-tab="mc-service" id="mc-tab-service">Службове</button>
                    <button class="fstu-tabs__btn" role="tab" aria-selected="false" data-tab="mc-ofst">Осередки</button>
					<button class="fstu-tabs__btn" role="tab" aria-selected="false" data-tab="mc-clubs">Клуби</button>
					<button class="fstu-tabs__btn" role="tab" aria-selected="false" data-tab="mc-cities">Міста</button>
					<button class="fstu-tabs__btn" role="tab" aria-selected="false" data-tab="mc-tourism">Види туризму</button>
					<button class="fstu-tabs__btn" role="tab" aria-selected="false" data-tab="mc-experience">Досвід</button>
					<button class="fstu-tabs__btn" role="tab" aria-selected="false" data-tab="mc-ranks">Розряди</button>
					<button class="fstu-tabs__btn" role="tab" aria-selected="false" data-tab="mc-judging">Суддівство</button>
					<button class="fstu-tabs__btn" role="tab" aria-selected="false" data-tab="mc-dues">Внески</button>
                    <button class="fstu-tabs__btn fstu-hidden" role="tab" aria-selected="false" data-tab="mc-sailing" id="mc-tab-sailing">Вітрильництво</button>
                    <button class="fstu-tabs__btn fstu-hidden" role="tab" aria-selected="false" data-tab="mc-dues-sail" id="mc-tab-dues-sail">Внески (вітр.)</button>
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
									<tr><th>Народження:</th><td id="mc-val-birth">—</td></tr>
                                    <tr><th>Стать:</th><td id="mc-val-sex">—</td></tr>
									<tr><th>Email:</th><td id="mc-val-email">—</td></tr>
									<tr><th>Телефон:</th><td id="mc-val-phone">—</td></tr>
									<tr><th>Skype:</th><td id="mc-val-skype">—</td></tr>
									<tr><th>Facebook:</th><td id="mc-val-facebook">—</td></tr>
									</tbody>
								</table>
							</div>
						</div>
					</div>
                    <div class="fstu-tabs__pane" id="mc-private" role="tabpanel">
                        <table class="fstu-info-table">
                            <tbody>
                            <tr><th>Адреса проживання:</th><td id="mc-val-address">—</td></tr>
                            <tr><th>Місце роботи, посада:</th><td id="mc-val-job">—</td></tr>
                            <tr><th>Освіта:</th><td id="mc-val-edu">—</td></tr>
                            <tr><th>Телефон родичів:</th><td id="mc-val-family-ph">—</td></tr>
                            </tbody>
                        </table>
                        <p style="margin-top: 15px; font-size: 11px; color: var(--fstu-text-light); border-top: 1px dashed var(--fstu-border); padding-top: 5px;">
                            <i>* Для оформлення документів (доступна тільки Реєстраторам, Адміністраторам та власнику)</i>
                        </p>
                    </div>

                    <div class="fstu-tabs__pane" id="mc-service" role="tabpanel">
                        <table class="fstu-info-table">
                            <tbody>
                            <tr><th>ID:</th><td id="mc-val-id">—</td></tr>
                            <tr><th>Login:</th><td id="mc-val-login">—</td></tr>
                            <tr><th>Останній вхід:</th><td id="mc-val-lastlog">—</td></tr>
                            <tr><th>Реєстрація:</th><td id="mc-val-regdate">—</td></tr>
                            <tr><th>Активація Telegram:</th><td id="mc-val-tgact">—</td></tr>
                            <tr><th>VerificationCode:</th><td id="mc-val-tgcode">—</td></tr>
                            <tr><th>Telegram ID:</th><td id="mc-val-tgid">—</td></tr>
                            <tr><th>ІПН:</th><td id="mc-val-ipn">—</td></tr>
                            <tr><th>Назва банку:</th><td id="mc-val-bank">—</td></tr>
                            <tr><th>IBAN:</th><td id="mc-val-iban">—</td></tr>
                            </tbody>
                        </table>
                        <p style="margin-top: 15px; font-size: 11px; color: var(--fstu-text-light); border-top: 1px dashed var(--fstu-border); padding-top: 5px;">
                            <i>* Технічна інформація (доступна лише Адміністраторам)</i>
                        </p>
                    </div>
                    <div class="fstu-tabs__pane" id="mc-ofst" role="tabpanel">
                        <h3 class="fstu-modal__title" style="margin-bottom: 12px;">Історія членства в осередках ФСТУ</h3>
                        <table class="fstu-table fstu-info-table fstu-hidden" id="mc-ofst-table">
                            <thead>
                            <tr style="border-bottom: 2px solid var(--fstu-border);">
                                <th>Осередок (ОФСТ)</th>
                                <th>Область</th>
                                <th>Дата додавання</th>
                            </tr>
                            </thead>
                            <tbody id="mc-val-ofst-list"></tbody>
                        </table>
                        <div id="mc-val-ofst-empty" class="fstu-placeholder fstu-hidden">Немає записів про членство в ОФСТ</div>
                    </div>
                    <div class="fstu-tabs__pane" id="mc-clubs" role="tabpanel">
                        <table class="fstu-table fstu-hidden" id="mc-clubs-table">
                            <thead class="fstu-thead">
                            <tr>
                                <th class="fstu-th" style="text-align:left;">Клуб</th>
                                <th class="fstu-th" style="text-align:left;">Адреса</th>
                            </tr>
                            </thead>
                            <tbody class="fstu-tbody" id="mc-val-clubs-list">
                            </tbody>
                        </table>
                        <div id="mc-val-clubs-empty" class="fstu-placeholder fstu-hidden">
                            Не є учасником жодного клубу
                        </div>
                    </div>
                    <div class="fstu-tabs__pane" id="mc-cities" role="tabpanel">
                        <table class="fstu-table fstu-hidden" id="mc-cities-table">
                            <thead class="fstu-thead">
                            <tr>
                                <th class="fstu-th" style="text-align:left;">Місто</th>
                                <th class="fstu-th" style="text-align:left;">Область</th>
                                <th class="fstu-th" style="text-align:left;">Дата додавання</th>
                            </tr>
                            </thead>
                            <tbody class="fstu-tbody" id="mc-val-cities-list">
                            </tbody>
                        </table>
                        <div id="mc-val-cities-empty" class="fstu-placeholder fstu-hidden">
                            Не вказано жодного міста проживання
                        </div>
                    </div>
                    <div class="fstu-tabs__pane" id="mc-tourism" role="tabpanel">
                        <table class="fstu-table fstu-hidden" id="mc-tourism-table">
                            <thead class="fstu-thead">
                            <tr>
                                <th class="fstu-th" style="text-align:left;">Вид туризму</th>
                                <th class="fstu-th" style="text-align:left; width: 140px;">Дата додавання</th>
                            </tr>
                            </thead>
                            <tbody class="fstu-tbody" id="mc-val-tourism-list">
                            </tbody>
                        </table>
                        <div id="mc-val-tourism-empty" class="fstu-placeholder fstu-hidden">
                            Не вказано жодного виду туризму
                        </div>
                    </div>
                    <div class="fstu-tabs__pane" id="mc-experience" role="tabpanel">
                        <table class="fstu-table fstu-hidden" id="mc-experience-table">
                            <thead class="fstu-thead">
                            <tr>
                                <th class="fstu-th" style="text-align:left;">Категорія</th>
                                <th class="fstu-th" style="text-align:center;">Роль</th>
                                <th class="fstu-th" style="text-align:left;">Захід</th>
                                <th class="fstu-th" style="text-align:left;">Вид туризму</th>
                                <th class="fstu-th" style="text-align:center;">Терміни</th>
                                <th class="fstu-th" style="text-align:center;">Довідка</th>
                            </tr>
                            </thead>
                            <tbody class="fstu-tbody" id="mc-val-experience-list">
                            </tbody>
                        </table>
                        <div class="fstu-tabs__pane" id="mc-experience" role="tabpanel">
                            <table class="fstu-table fstu-hidden" id="mc-experience-table">
                                <thead class="fstu-thead">
                                <tr>
                                    <th class="fstu-th" style="text-align:left;">Категорія</th>
                                    <th class="fstu-th" style="text-align:center;">Роль</th>
                                    <th class="fstu-th" style="text-align:left;">Захід</th>
                                    <th class="fstu-th" style="text-align:left;">Вид туризму</th>
                                    <th class="fstu-th" style="text-align:center;">Терміни</th>
                                    <th class="fstu-th" style="text-align:center;">Довідка</th>
                                </tr>
                                </thead>
                                <tbody class="fstu-tbody" id="mc-val-experience-list">
                                </tbody>
                            </table>
                            <div class="fstu-pagination fstu-hidden" id="mc-experience-pagination" style="border-top: 1px solid var(--fstu-border); padding-top: 10px; margin-top: 10px;">
                                <div class="fstu-pagination__info" id="mc-exp-pagin-info"></div>
                                <div class="fstu-pagination__controls">
                                    <button type="button" class="fstu-btn fstu-btn--secondary" style="padding: 2px 8px; font-size: 12px;" id="mc-exp-prev" disabled>« Назад</button>
                                    <span id="mc-exp-page-nums" style="margin: 0 8px; font-size: 12px; font-weight: 600;"></span>
                                    <button type="button" class="fstu-btn fstu-btn--secondary" style="padding: 2px 8px; font-size: 12px;" id="mc-exp-next" disabled>Далі »</button>
                                </div>
                            </div>
                            <div id="mc-val-experience-empty" class="fstu-placeholder fstu-hidden">
                                Немає даних про туристський досвід
                            </div>
                        </div>
                        <div id="mc-val-experience-empty" class="fstu-placeholder fstu-hidden">
                            Немає даних про туристський досвід
                        </div>
                    </div>
                    <div class="fstu-tabs__pane" id="mc-ranks" role="tabpanel">
                        <table class="fstu-table fstu-hidden" id="mc-ranks-table">
                            <thead class="fstu-thead">
                            <tr>
                                <th class="fstu-th" style="text-align:left;">Розряд / звання</th>
                                <th class="fstu-th" style="text-align:center;">Дата</th>
                                <th class="fstu-th" style="text-align:left;">Вид туризму</th>
                                <th class="fstu-th" style="text-align:left;">Захід (наказ)</th>
                                <th class="fstu-th" style="text-align:center;">Терміни</th>
                            </tr>
                            </thead>
                            <tbody class="fstu-tbody" id="mc-val-ranks-list">
                            </tbody>
                        </table>

                        <div class="fstu-pagination fstu-hidden" id="mc-ranks-pagination" style="border-top: 1px solid var(--fstu-border); padding-top: 10px; margin-top: 10px;">
                            <div class="fstu-pagination__info" id="mc-rank-pagin-info"></div>
                            <div class="fstu-pagination__controls">
                                <button type="button" class="fstu-btn fstu-btn--secondary" style="padding: 2px 8px; font-size: 12px;" id="mc-rank-prev" disabled>« Назад</button>
                                <span id="mc-rank-page-nums" style="margin: 0 8px; font-size: 12px; font-weight: 600;"></span>
                                <button type="button" class="fstu-btn fstu-btn--secondary" style="padding: 2px 8px; font-size: 12px;" id="mc-rank-next" disabled>Далі »</button>
                            </div>
                        </div>

                        <div id="mc-val-ranks-empty" class="fstu-placeholder fstu-hidden">
                            Не вказано жодного спортивного розряду чи звання
                        </div>
                    </div>
                    <div class="fstu-tabs__pane" id="mc-judging" role="tabpanel">
                        <table class="fstu-table fstu-hidden" id="mc-judging-table">
                            <thead class="fstu-thead">
                            <tr>
                                <th class="fstu-th" style="text-align:left;">Категорія</th>
                                <th class="fstu-th" style="text-align:left; width: 140px;">Дата додавання</th>
                            </tr>
                            </thead>
                            <tbody class="fstu-tbody" id="mc-val-judging-list">
                            </tbody>
                        </table>
                        <div id="mc-val-judging-empty" class="fstu-placeholder fstu-hidden">
                            Не вказана суддівська категорія
                        </div>
                    </div>
                    <div class="fstu-tabs__pane" id="mc-dues" role="tabpanel">
                        <table class="fstu-table fstu-hidden" id="mc-dues-table">
                            <thead class="fstu-thead">
                            <tr>
                                <th class="fstu-th" style="text-align:center; width:60px;">Рік</th>
                                <th class="fstu-th" style="text-align:right; width:80px;">Сума</th>
                                <th class="fstu-th" style="text-align:center;">Тип</th>
                                <th class="fstu-th" style="text-align:center;">Документ</th>
                                <th class="fstu-th" style="text-align:left;">Фінансист</th>
                            </tr>
                            </thead>
                            <tbody class="fstu-tbody" id="mc-val-dues-list"></tbody>
                        </table>

                        <div class="fstu-pagination fstu-hidden" id="mc-dues-pagination" style="border-top: 1px solid var(--fstu-border); padding-top: 10px; margin-top: 10px;">
                            <div class="fstu-pagination__info" id="mc-dues-pagin-info"></div>
                            <div class="fstu-pagination__controls">
                                <button type="button" class="fstu-btn fstu-btn--secondary" style="padding: 2px 8px; font-size: 12px;" id="mc-dues-prev" disabled>« Назад</button>
                                <span id="mc-dues-page-nums" style="margin:0 8px; font-size:12px; font-weight:600;"></span>
                                <button type="button" class="fstu-btn fstu-btn--secondary" style="padding: 2px 8px; font-size: 12px;" id="mc-dues-next" disabled>Далі »</button>
                            </div>
                        </div>
                        <div id="mc-val-dues-empty" class="fstu-placeholder fstu-hidden">Внески не знайдені</div>
                    </div>

                    <div class="fstu-tabs__pane" id="mc-dues-sail" role="tabpanel">
                        <table class="fstu-table fstu-hidden" id="mc-dues-sail-table">
                            <thead class="fstu-thead">
                            <tr style="background:#fff4e5;">
                                <th class="fstu-th" style="text-align:center; width:60px;">Рік</th>
                                <th class="fstu-th" style="text-align:right; width:80px;">Сума</th>
                                <th class="fstu-th" style="text-align:center;">Дата</th>
                                <th class="fstu-th" style="text-align:left;">Отримувач</th>
                            </tr>
                            </thead>
                            <tbody class="fstu-tbody" id="mc-val-dues-sail-list"></tbody>
                        </table>

                        <div class="fstu-pagination fstu-hidden" id="mc-dues-sail-pagination" style="border-top: 1px solid var(--fstu-border); padding-top: 10px; margin-top: 10px;">
                            <div class="fstu-pagination__info" id="mc-dues-sail-pagin-info"></div>
                            <div class="fstu-pagination__controls">
                                <button type="button" class="fstu-btn fstu-btn--secondary" style="padding: 2px 8px; font-size: 12px;" id="mc-dues-sail-prev" disabled>« Назад</button>
                                <span id="mc-dues-sail-page-nums" style="margin:0 8px; font-size:12px; font-weight:600;"></span>
                                <button type="button" class="fstu-btn fstu-btn--secondary" style="padding: 2px 8px; font-size: 12px;" id="mc-dues-sail-next" disabled>Далі »</button>
                            </div>
                        </div>
                        <div id="mc-val-dues-sail-empty" class="fstu-placeholder fstu-hidden">Внески вітрильників відсутні</div>
                    </div>

                    <div class="fstu-tabs__pane" id="mc-sailing" role="tabpanel">
                        <h4 class="fstu-modal__subtitle">Вітрильні судна</h4>
                        <table class="fstu-table fstu-hidden" id="mc-vessels-table">
                            <thead class="fstu-thead">
                            <tr>
                                <th class="fstu-th">Назва / Реєстраційний №</th>
                                <th class="fstu-th" style="text-align:center;">№ на вітрилі</th>
                                <th class="fstu-th">Статус</th>
                                <th class="fstu-th" style="text-align:right;">Сума</th>
                            </tr>
                            </thead>
                            <tbody class="fstu-tbody" id="mc-val-vessels-list"></tbody>
                        </table>
                        <div id="mc-val-vessels-empty" class="fstu-placeholder fstu-hidden">Судна не зареєстровані</div>

                        <h4 class="fstu-modal__subtitle" style="margin-top:25px;">Вітрильні посвідчення (Капітан / Стерновий)</h4>
                        <table class="fstu-table fstu-hidden" id="mc-certs-table">
                            <thead class="fstu-thead">
                            <tr>
                                <th class="fstu-th">Тип / Номер</th>
                                <th class="fstu-th">Статус</th>
                                <th class="fstu-th" style="text-align:center;">Дата видачі / оплати</th>
                            </tr>
                            </thead>
                            <tbody class="fstu-tbody" id="mc-val-certs-list"></tbody>
                        </table>
                        <div id="mc-val-certs-empty" class="fstu-placeholder fstu-hidden">Посвідчення відсутні</div>
                    </div>
				</div>
			</div>

		</div>
	</div>
</div>
