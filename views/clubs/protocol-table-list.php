<?php
/**
 * Протокол (журнал операцій) довідника клубів ФСТУ.
 * Колонки: Дата, Тип, Операція, Повідомлення, Статус, Користувач.
 * Дані з таблиці Logs.
 *
 * Version:     1.0.1
 * Date_update: 2026-04-13
 *
 * @package FSTU\Dictionaries\Clubs\Views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-clubs-protocol" class="fstu-hidden">
	<div class="fstu-table-wrap fstu-table-wrap--compact">
		<table class="fstu-table fstu-table--compact">
			<thead class="fstu-thead">
				<tr>
					<th class="fstu-th fstu-th--date"><?php esc_html_e( 'Дата', 'fstu' ); ?></th>
					<th class="fstu-th fstu-th--type"><?php esc_html_e( 'Тип', 'fstu' ); ?></th>
					<th class="fstu-th fstu-th--wide-name">
						<div class="fstu-th-with-search">
							<span><?php esc_html_e( 'Операція', 'fstu' ); ?></span>
							<input
								type="text"
								id="fstu-clubs-protocol-filter-name"
								class="fstu-input--in-header"
								placeholder="<?php esc_attr_e( '🔍 Пошук...', 'fstu' ); ?>"
								aria-label="<?php esc_attr_e( 'Пошук за повідомленням або користувачем', 'fstu' ); ?>"
								autocomplete="off"
							>
						</div>
					</th>
					<th class="fstu-th fstu-th--message"><?php esc_html_e( 'Повідомлення', 'fstu' ); ?></th>
					<th class="fstu-th fstu-th--status"><?php esc_html_e( 'Статус', 'fstu' ); ?></th>
					<th class="fstu-th fstu-th--user"><?php esc_html_e( 'Користувач', 'fstu' ); ?></th>
				</tr>
			</thead>
			<tbody class="fstu-tbody" id="fstu-clubs-protocol-tbody">
				<tr class="fstu-row">
					<td colspan="6" class="fstu-no-results"><?php esc_html_e( 'Завантаження...', 'fstu' ); ?></td>
				</tr>
			</tbody>
		</table>
	</div>

	<div class="fstu-pagination fstu-pagination--compact" id="fstu-clubs-protocol-pagination">
		<div class="fstu-pagination__left">
			<label class="fstu-pagination__per-page-label" for="fstu-clubs-protocol-per-page"><?php esc_html_e( 'Записів:', 'fstu' ); ?></label>
			<select id="fstu-clubs-protocol-per-page" class="fstu-select fstu-select--compact" aria-label="<?php esc_attr_e( 'Кількість записів протоколу на сторінці', 'fstu' ); ?>">
				<option value="10" selected>10</option>
				<option value="20">20</option>
				<option value="50">50</option>
			</select>
		</div>
		<div class="fstu-pagination__controls">
			<button type="button" class="fstu-btn--page" id="fstu-clubs-protocol-prev-page" aria-label="<?php esc_attr_e( 'Попередня сторінка протоколу', 'fstu' ); ?>">«</button>
			<div class="fstu-pagination__pages" id="fstu-clubs-protocol-pages"></div>
			<button type="button" class="fstu-btn--page" id="fstu-clubs-protocol-next-page" aria-label="<?php esc_attr_e( 'Наступна сторінка протоколу', 'fstu' ); ?>">»</button>
		</div>
		<div class="fstu-pagination__info" id="fstu-clubs-protocol-info" aria-live="polite"></div>
	</div>
</div>

