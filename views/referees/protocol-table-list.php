<?php
/**
 * Таблиця протоколу модуля суддів.
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

<div id="fstu-referees-protocol" class="fstu-hidden">
	<div class="fstu-table-wrap fstu-table-wrap--compact">
		<table class="fstu-table fstu-table--protocol">
			<thead class="fstu-thead">
				<tr>
					<th class="fstu-th fstu-th--date"><?php esc_html_e( 'Дата', 'fstu' ); ?></th>
					<th class="fstu-th fstu-th--type"><?php esc_html_e( 'Тип', 'fstu' ); ?></th>
					<th class="fstu-th fstu-th--wide-name">
						<div class="fstu-th-with-search">
							<span><?php esc_html_e( 'Операція', 'fstu' ); ?></span>
							<input
								type="text"
								id="fstu-referees-protocol-search"
								class="fstu-input--in-header"
								placeholder="<?php esc_attr_e( '🔍 Пошук...', 'fstu' ); ?>"
								aria-label="<?php esc_attr_e( 'Пошук по протоколу', 'fstu' ); ?>"
								autocomplete="off"
							>
						</div>
					</th>
					<th class="fstu-th fstu-th--message"><?php esc_html_e( 'Повідомлення', 'fstu' ); ?></th>
					<th class="fstu-th fstu-th--status"><?php esc_html_e( 'Статус', 'fstu' ); ?></th>
					<th class="fstu-th fstu-th--user"><?php esc_html_e( 'Користувач', 'fstu' ); ?></th>
				</tr>
			</thead>
			<tbody class="fstu-tbody" id="fstu-referees-protocol-tbody">
				<tr class="fstu-row">
					<td colspan="6" class="fstu-no-results"><?php esc_html_e( 'Записи протоколу відсутні.', 'fstu' ); ?></td>
				</tr>
			</tbody>
		</table>
	</div>

	<div class="fstu-pagination fstu-pagination--compact">
		<div class="fstu-pagination__left">
			<label class="fstu-pagination__per-page-label" for="fstu-referees-protocol-per-page"><?php esc_html_e( 'Показувати по:', 'fstu' ); ?></label>
			<select id="fstu-referees-protocol-per-page" class="fstu-select fstu-select--compact" aria-label="<?php esc_attr_e( 'Кількість записів протоколу на сторінці', 'fstu' ); ?>">
				<option value="10" selected>10</option>
				<option value="15">15</option>
				<option value="25">25</option>
				<option value="50">50</option>
			</select>
		</div>
		<div class="fstu-pagination__controls">
			<button type="button" class="fstu-btn--page" id="fstu-referees-protocol-prev-page">«</button>
			<div id="fstu-referees-protocol-pagination-pages"></div>
			<button type="button" class="fstu-btn--page" id="fstu-referees-protocol-next-page">»</button>
		</div>
		<div class="fstu-pagination__info">
			<span id="fstu-referees-protocol-pagination-info" aria-live="polite"></span>
		</div>
	</div>
</div>

