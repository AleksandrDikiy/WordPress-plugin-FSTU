<?php
/**
 * Протокол (журнал операцій) довідника міст.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-07
 *
 * @package FSTU\Dictionaries\City
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="fstu-city-protocol" class="fstu-hidden">
	<div class="fstu-table-wrap fstu-table-wrap--compact">
		<table class="fstu-table fstu-table--compact">
			<thead class="fstu-thead">
				<tr>
					<th class="fstu-th fstu-th--date"><?php esc_html_e( 'Дата', 'fstu' ); ?></th>
					<th class="fstu-th fstu-th--type"><?php esc_html_e( 'Тип', 'fstu' ); ?></th>
					<th class="fstu-th fstu-th--wide-name">
						<div class="fstu-th-with-search">
							<span><?php esc_html_e( 'Операція', 'fstu' ); ?></span>
							<input type="text" id="fstu-city-protocol-search" class="fstu-input--in-header" placeholder="<?php esc_attr_e( '🔍 Пошук...', 'fstu' ); ?>" autocomplete="off">
						</div>
					</th>
					<th class="fstu-th fstu-th--message"><?php esc_html_e( 'Повідомлення', 'fstu' ); ?></th>
					<th class="fstu-th fstu-th--status"><?php esc_html_e( 'Статус', 'fstu' ); ?></th>
					<th class="fstu-th fstu-th--user"><?php esc_html_e( 'Користувач', 'fstu' ); ?></th>
				</tr>
			</thead>
			<tbody class="fstu-tbody" id="fstu-city-protocol-tbody">
				<tr class="fstu-row"><td colspan="6" class="fstu-no-results"><?php esc_html_e( 'Завантаження...', 'fstu' ); ?></td></tr>
			</tbody>
		</table>
	</div>
	<div class="fstu-pagination fstu-pagination--compact">
		<div class="fstu-pagination__left">
			<label class="fstu-pagination__per-page-label" for="fstu-city-protocol-per-page"><?php esc_html_e( 'Показувати по:', 'fstu' ); ?></label>
			<select id="fstu-city-protocol-per-page" class="fstu-select fstu-select--compact">
				<option value="10" selected>10</option>
				<option value="15">15</option>
				<option value="25">25</option>
				<option value="50">50</option>
			</select>
		</div>
		<div class="fstu-pagination__controls">
			<button type="button" class="fstu-btn--page" id="fstu-city-protocol-prev-page">«</button>
			<div id="fstu-city-protocol-pagination-pages"></div>
			<button type="button" class="fstu-btn--page" id="fstu-city-protocol-next-page">»</button>
		</div>
		<div class="fstu-pagination__info"><span id="fstu-city-protocol-pagination-info"></span></div>
	</div>
</div>

