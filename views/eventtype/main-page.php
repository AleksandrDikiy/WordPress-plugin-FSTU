<?php 
/**
 * Основний шаблон модуля "Довідник типів заходів".
 *
 * Version:     1.0.0
 * Date_update: 2026-04-07
 *
 * @package FSTU\Dictionaries\EventType
 */

namespace FSTU\Dictionaries\EventType; 

if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="fstu-eventtype-wrap" id="fstu-eventtype-app">
	<div class="fstu-action-bar">
		<div id="fstu-actions-directory" style="display: flex; gap: 10px;">
			<button type="button" class="fstu-btn" id="fstu-btn-add"><span aria-hidden="true">➕</span> Додати</button>
			<button type="button" class="fstu-btn" id="fstu-btn-refresh"><span aria-hidden="true">↻</span> Оновити</button>
			<button type="button" class="fstu-btn" id="fstu-btn-protocol"><span aria-hidden="true">📋</span> ПРОТОКОЛ</button>
		</div>
		<div id="fstu-actions-protocol" class="fstu-hidden" style="display: flex; gap: 10px;">
			<button type="button" class="fstu-btn" id="fstu-btn-back"><span aria-hidden="true">🔙</span> ДОВІДНИК</button>
			<button type="button" class="fstu-btn" id="fstu-btn-refresh-protocol"><span aria-hidden="true">↻</span> Оновити протокол</button>
		</div>
	</div>
	<div id="fstu-view-directory"><?php include __DIR__ . '/table-list.php'; ?></div>
	<div id="fstu-view-protocol" class="fstu-hidden"><?php include __DIR__ . '/protocol-list.php'; ?></div>
	<?php include __DIR__ . '/modal-form.php'; ?>
</div>