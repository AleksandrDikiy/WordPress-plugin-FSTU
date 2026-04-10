<?php
/**
 * Шаблон таблиці протоколу (Logs).
 * * Version: 1.0.0
 * Date_update: 2026-04-10
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="fstu-table-responsive">
	<table class="fstu-table fstu-table--striped">
		<thead>
			<tr>
				<th class="fstu-th fstu-th--date">Дата</th>
				<th class="fstu-th fstu-th--type">Тип</th>
				<th class="fstu-th fstu-th--wide-name">
					<div class="fstu-th-with-search">
						<span>Операція / Повідомлення</span>
						<input type="text" id="fstu-protocol-search" class="fstu-input--in-header" placeholder="🔍 Пошук...">
					</div>
				</th>
				<th class="fstu-th fstu-th--status">Статус</th>
				<th class="fstu-th fstu-th--user">Користувач</th>
			</tr>
		</thead>
		<tbody id="fstu-units-protocol-tbody">
			</tbody>
	</table>
</div>

<div class="fstu-pagination fstu-pagination--compact">
	<div class="fstu-pagination__left">
		<label class="fstu-pagination__per-page-label" for="fstu-protocol-per-page">Показувати по:</label>
		<select id="fstu-protocol-per-page" class="fstu-select fstu-select--compact" style="width: 70px !important;">
			<option value="10" selected>10</option>
			<option value="25">25</option>
			<option value="50">50</option>
		</select>
	</div>
	<div class="fstu-pagination__controls" id="fstu-protocol-pagination-controls"></div>
	<div class="fstu-pagination__info" id="fstu-protocol-pagination-info"></div>
</div>