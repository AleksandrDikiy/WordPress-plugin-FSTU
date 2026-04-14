<?php
/**
 * Протокол підмодуля Calendar_Results.
 *
 * Version: 1.0.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar\CalendarResults
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-calendar-results-protocol" class="fstu-hidden">
	<div class="fstu-table-wrap fstu-table-wrap--compact">
		<table class="fstu-table fstu-table--compact">
			<thead class="fstu-thead">
				<tr>
					<th class="fstu-th fstu-th--date"><?php esc_html_e( 'Дата', 'fstu' ); ?></th>
					<th class="fstu-th fstu-th--type"><?php esc_html_e( 'Тип', 'fstu' ); ?></th>
					<th class="fstu-th fstu-th--wide-name">
						<div class="fstu-th-with-search">
							<span><?php esc_html_e( 'Операція', 'fstu' ); ?></span>
							<input type="text" id="fstu-calendar-results-protocol-search" class="fstu-input--in-header" placeholder="<?php esc_attr_e( '🔍 Пошук...', 'fstu' ); ?>" autocomplete="off" aria-label="<?php esc_attr_e( 'Пошук у протоколі результатів', 'fstu' ); ?>">
						</div>
					</th>
					<th class="fstu-th fstu-th--message"><?php esc_html_e( 'Повідомлення', 'fstu' ); ?></th>
					<th class="fstu-th fstu-th--status"><?php esc_html_e( 'Статус', 'fstu' ); ?></th>
					<th class="fstu-th fstu-th--user"><?php esc_html_e( 'Користувач', 'fstu' ); ?></th>
				</tr>
			</thead>
			<tbody id="fstu-calendar-results-protocol-tbody" class="fstu-tbody">
				<tr>
					<td colspan="6" class="fstu-no-results"><?php esc_html_e( 'Оберіть перегін або відкрийте розділ результатів.', 'fstu' ); ?></td>
				</tr>
			</tbody>
		</table>
	</div>

	<div class="fstu-pagination fstu-pagination--compact">
		<div class="fstu-pagination__left">
			<label class="fstu-pagination__per-page-label" for="fstu-calendar-results-protocol-per-page"><?php esc_html_e( 'Показувати по:', 'fstu' ); ?></label>
			<select id="fstu-calendar-results-protocol-per-page" class="fstu-select fstu-select--compact">
				<option value="10" selected>10</option>
				<option value="15">15</option>
				<option value="25">25</option>
				<option value="50">50</option>
			</select>
		</div>
		<div class="fstu-pagination__controls">
			<button type="button" class="fstu-btn--page" id="fstu-calendar-results-protocol-prev-page">«</button>
			<div id="fstu-calendar-results-protocol-pagination-pages"></div>
			<button type="button" class="fstu-btn--page" id="fstu-calendar-results-protocol-next-page">»</button>
		</div>
		<div class="fstu-pagination__info">
			<span id="fstu-calendar-results-protocol-pagination-info" aria-live="polite"></span>
		</div>
	</div>
</div>

