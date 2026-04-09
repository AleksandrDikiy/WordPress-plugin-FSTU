<?php
/**
 * View: Секція протоколу модуля «Особистий кабінет ФСТУ».
 *
 * Version:     1.0.0
 * Date_update: 2026-04-09
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="fstu-protocol-shell">
	<div class="fstu-protocol-shell__header">
		<h3 class="fstu-protocol-shell__title">ПРОТОКОЛ модуля «Особистий кабінет ФСТУ»</h3>
		<p class="fstu-protocol-shell__subtitle">Журнал власних дій модуля з пошуком і compact-пагінацією.</p>
	</div>

	<div class="fstu-table-wrap">
		<table class="fstu-table">
			<thead class="fstu-thead">
			<tr>
				<th class="fstu-th fstu-th--date">Дата</th>
				<th class="fstu-th fstu-th--type">Тип</th>
				<th class="fstu-th fstu-th--wide-name">
					<div class="fstu-th-with-search">
						<span>Операція</span>
						<input type="text" id="fstu-personal-protocol-search" class="fstu-input--in-header" placeholder="🔍 Пошук...">
					</div>
				</th>
				<th class="fstu-th fstu-th--message">Повідомлення</th>
				<th class="fstu-th fstu-th--status">Статус</th>
				<th class="fstu-th fstu-th--user">Користувач</th>
			</tr>
			</thead>
			<tbody class="fstu-tbody" id="fstu-personal-protocol-body">
			<tr>
				<td colspan="6" class="fstu-td fstu-td--empty">Завантаження протоколу...</td>
			</tr>
			</tbody>
		</table>
	</div>

	<div class="fstu-pagination fstu-pagination--compact" id="fstu-personal-protocol-pagination">
		<div class="fstu-pagination__left">
			<label class="fstu-pagination__per-page-label" for="fstu-personal-protocol-per-page">Показувати по:</label>
			<select id="fstu-personal-protocol-per-page" class="fstu-select fstu-select--compact">
				<option value="10" selected>10</option>
				<option value="15">15</option>
				<option value="25">25</option>
				<option value="50">50</option>
			</select>
		</div>
		<div class="fstu-pagination__controls" id="fstu-personal-protocol-controls"></div>
		<div class="fstu-pagination__info" id="fstu-personal-protocol-info"></div>
	</div>
</div>

