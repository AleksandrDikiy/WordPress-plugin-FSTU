<?php
/**
 * Шаблон таблиці осередків.
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
				<th class="fstu-th fstu-th--num">№</th>
				<th class="fstu-th fstu-th--wide-name">
					<div class="fstu-th-with-search">
						<span>Найменування повне</span>
						<input type="text" id="fstu-units-search" class="fstu-input--in-header" placeholder="🔍 Пошук...">
					</div>
				</th>
				<th class="fstu-th">Скорочене</th>
				<th class="fstu-th">ОПФ</th>
				<th class="fstu-th">Ранг</th>
				<th class="fstu-th">Область</th>
				<th class="fstu-th">Місто</th>
				<th class="fstu-th fstu-th--actions" title="Дії" style="width: 40px; text-align: center;">
					ДІЇ
				</th>
				</th>
			</tr>
		</thead>
		<tbody id="fstu-units-tbody">
			<tr><td colspan="8" style="text-align:center;">Завантаження...</td></tr>
		</tbody>
	</table>
</div>

<div class="fstu-pagination fstu-pagination--compact">
	<div class="fstu-pagination__left">
		<label class="fstu-pagination__per-page-label" for="fstu-units-per-page">Показувати по:</label>
		<select id="fstu-units-per-page" class="fstu-select fstu-select--compact" style="width: 70px !important;">
			<option value="10" selected>10</option>
			<option value="15">15</option>
			<option value="25">25</option>
			<option value="50">50</option>
		</select>
	</div>
	<div class="fstu-pagination__controls" id="fstu-units-pagination-controls">
		</div>
	<div class="fstu-pagination__info" id="fstu-units-pagination-info">
		</div>
</div>