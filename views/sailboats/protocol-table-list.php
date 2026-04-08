<?php
/**
 * Протокол (журнал операцій) модуля "Реєстр суден".
 *
 * Version:     1.0.0
 * Date_update: 2026-04-07
 *
 * @package FSTU\Modules\Registry\Sailboats
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-sailboats-protocol" class="fstu-hidden">
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
								id="fstu-sailboats-protocol-search"
								class="fstu-input--in-header"
								placeholder="<?php esc_attr_e( '🔍 Пошук...', 'fstu' ); ?>"
								aria-label="<?php esc_attr_e( 'Пошук по протоколу реєстру суден', 'fstu' ); ?>"
								autocomplete="off"
							>
						</div>
					</th>
					<th class="fstu-th fstu-th--message"><?php esc_html_e( 'Повідомлення', 'fstu' ); ?></th>
					<th class="fstu-th fstu-th--status"><?php esc_html_e( 'Статус', 'fstu' ); ?></th>
					<th class="fstu-th fstu-th--user"><?php esc_html_e( 'Користувач', 'fstu' ); ?></th>
				</tr>
			</thead>
			<tbody class="fstu-tbody" id="fstu-sailboats-protocol-tbody">
				<tr class="fstu-row">
					<td colspan="6" class="fstu-no-results"><?php esc_html_e( 'Завантаження...', 'fstu' ); ?></td>
				</tr>
			</tbody>
		</table>
	</div>

	<div class="fstu-pagination fstu-pagination--compact">
		<div class="fstu-pagination__left">
			<label class="fstu-pagination__per-page-label" for="fstu-sailboats-protocol-per-page"><?php esc_html_e( 'Показувати по:', 'fstu' ); ?></label>
			<select id="fstu-sailboats-protocol-per-page" class="fstu-select fstu-select--compact" aria-label="<?php esc_attr_e( 'Кількість записів протоколу на сторінці', 'fstu' ); ?>">
				<option value="10" selected>10</option>
				<option value="15">15</option>
				<option value="25">25</option>
				<option value="50">50</option>
			</select>
		</div>
		<div class="fstu-pagination__controls">
			<button type="button" class="fstu-btn--page" id="fstu-sailboats-protocol-prev-page">«</button>
			<div id="fstu-sailboats-protocol-pagination-pages"></div>
			<button type="button" class="fstu-btn--page" id="fstu-sailboats-protocol-next-page">»</button>
		</div>
		<div class="fstu-pagination__info">
			<span id="fstu-sailboats-protocol-pagination-info" aria-live="polite"></span>
		</div>
	</div>
</div>

