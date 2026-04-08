<?php 
/**
 * Таблиця списку "Довідник типів заходів".
 * Пошук вбудовано у шапку таблиці (колонка «Найменування»).
 *
 * Version:     1.0.0
 * Date_update: 2026-04-07
 *
 * @package FSTU\Dictionaries\EventType
 */

namespace FSTU\Dictionaries\EventType; 

if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="fstu-table-wrap">
	<table class="fstu-table" id="fstu-eventtype-table">
		<thead>
			<tr>
				<th class="fstu-th fstu-th--num">№</th>
				<th class="fstu-th fstu-th--wide-name">
					<div class="fstu-th-with-search">
						<span>Найменування</span>
						<input type="text" id="fstu-eventtype-search" class="fstu-input--in-header" placeholder="🔍 Пошук..." autocomplete="off">
					</div>
				</th>
				<th class="fstu-th fstu-th--actions">Дії</th>
			</tr>
		</thead>
		<tbody id="fstu-eventtype-tbody"><tr><td colspan="3" style="text-align:center;">Завантаження...</td></tr></tbody>
	</table>

	<div class="fstu-pagination fstu-pagination--compact">
		<div class="fstu-pagination__left">
			<label class="fstu-pagination__per-page-label" for="fstu-eventtype-per-page">Показувати по:</label>
			<select id="fstu-eventtype-per-page" class="fstu-select fstu-select--compact">
				<option value="10" selected>10</option><option value="15">15</option><option value="25">25</option><option value="50">50</option>
			</select>
		</div>
		<div class="fstu-pagination__controls" id="fstu-eventtype-pages"></div>
		<div class="fstu-pagination__info" id="fstu-eventtype-info"></div>
	</div>
</div>