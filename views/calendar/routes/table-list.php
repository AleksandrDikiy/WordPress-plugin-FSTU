<?php
/**
 * Таблиця списку маршрутів Calendar_Routes.
 *
 * Version: 1.0.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar\CalendarRoutes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="fstu-routes-panel-header">
	<div>
		<h3 class="fstu-subpanel-title"><?php esc_html_e( 'Маршрутний контур', 'fstu' ); ?></h3>
		<p id="fstu-calendar-routes-context" class="fstu-subpanel-context"><?php esc_html_e( 'Оберіть захід у реєстрі, щоб переглянути маршрут.', 'fstu' ); ?></p>
		<div id="fstu-calendar-routes-summary" class="fstu-calendar-route-summary fstu-hidden"></div>
	</div>
	<div class="fstu-routes-panel-actions">
		<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-calendar-routes-back-to-events"><?php esc_html_e( 'До реєстру', 'fstu' ); ?></button>
		<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-calendar-route-view-btn"><?php esc_html_e( 'Картка маршруту', 'fstu' ); ?></button>
		<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-calendar-route-send-to-mkk-btn"><?php esc_html_e( 'Відправити до МКК', 'fstu' ); ?></button>
		<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-calendar-routes-protocol-btn"><?php esc_html_e( 'ПРОТОКОЛ', 'fstu' ); ?></button>
		<button type="button" class="fstu-btn fstu-btn--secondary fstu-hidden" id="fstu-calendar-routes-protocol-back-btn"><?php esc_html_e( 'МАРШРУТ', 'fstu' ); ?></button>
		<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-calendar-add-route-btn"><?php esc_html_e( 'Додати ділянку', 'fstu' ); ?></button>
	</div>
</div>

<div id="fstu-calendar-routes-main-content">
	<div class="fstu-table-wrap">
		<table class="fstu-table fstu-calendar-routes-table">
			<thead class="fstu-thead">
				<tr>
					<th class="fstu-th fstu-th--num">#</th>
					<th class="fstu-th fstu-th--date"><?php esc_html_e( 'Дата', 'fstu' ); ?></th>
					<th class="fstu-th fstu-th--wide-name">
						<div class="fstu-th-with-search">
							<span><?php esc_html_e( 'Ділянка маршруту', 'fstu' ); ?></span>
							<input type="text" id="fstu-calendar-routes-search" class="fstu-input--in-header" placeholder="<?php esc_attr_e( '🔍 Пошук...', 'fstu' ); ?>" autocomplete="off">
						</div>
					</th>
					<th class="fstu-th"><?php esc_html_e( 'Відстань, км', 'fstu' ); ?></th>
					<th class="fstu-th"><?php esc_html_e( 'Транспорт', 'fstu' ); ?></th>
					<th class="fstu-th"><?php esc_html_e( 'Трек', 'fstu' ); ?></th>
					<th class="fstu-th fstu-th--actions"><?php esc_html_e( 'Дії', 'fstu' ); ?></th>
				</tr>
			</thead>
			<tbody id="fstu-calendar-routes-tbody" class="fstu-tbody">
				<tr>
					<td colspan="7" class="fstu-no-results"><?php esc_html_e( 'Оберіть захід, щоб завантажити маршрут.', 'fstu' ); ?></td>
				</tr>
			</tbody>
		</table>
	</div>

	<div class="fstu-pagination fstu-pagination--compact">
		<div class="fstu-pagination__left">
			<label class="fstu-pagination__per-page-label" for="fstu-calendar-routes-per-page"><?php esc_html_e( 'Показувати по:', 'fstu' ); ?></label>
			<select id="fstu-calendar-routes-per-page" class="fstu-select fstu-select--compact">
				<option value="10" selected>10</option>
				<option value="15">15</option>
				<option value="25">25</option>
				<option value="50">50</option>
			</select>
		</div>
		<div class="fstu-pagination__controls">
			<button type="button" class="fstu-btn--page" id="fstu-calendar-routes-prev-page">«</button>
			<div id="fstu-calendar-routes-pagination-pages"></div>
			<button type="button" class="fstu-btn--page" id="fstu-calendar-routes-next-page">»</button>
		</div>
		<div class="fstu-pagination__info">
			<span id="fstu-calendar-routes-pagination-info" aria-live="polite"></span>
		</div>
	</div>
</div>
