<?php
/**
 * View: Таблиця клубів.
 * <tbody> заповнюється через AJAX.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-05
 *
 * @package FSTU\Clubs\Views
 *
 * @var bool $can_edit   Чи показувати колонку дій.
 * @var bool $is_admin   Чи є адміністратор.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="fstu-table-wrap" role="region" aria-label="Список клубів" aria-live="polite">

	<div class="fstu-loader fstu-hidden" id="fstu-clubs-loader" aria-hidden="true">
		<span class="fstu-loader__spinner"></span>
		<span class="fstu-loader__text">Завантаження...</span>
	</div>

	<table class="fstu-table" id="fstu-clubs-table">
		<thead class="fstu-thead">
			<tr>
				<th class="fstu-th fstu-th--num" scope="col">№</th>
				<th class="fstu-th fstu-th--name" scope="col">Назва клубу</th>
				<th class="fstu-th fstu-th--adr" scope="col">Поштова адреса</th>
				<?php if ( $can_edit ) : ?>
					<th class="fstu-th fstu-th--actions" scope="col">
						<span class="fstu-sr-only">Дії</span>
					</th>
				<?php endif; ?>
			</tr>
		</thead>
		<tbody class="fstu-tbody" id="fstu-clubs-tbody">
			<tr>
				<td colspan="<?php echo $can_edit ? '4' : '3'; ?>" class="fstu-no-results">
					<span class="fstu-loader__spinner"></span> Завантаження...
				</td>
			</tr>
		</tbody>
	</table>

</div>

<!-- Лічильник та пагінація -->
<div class="fstu-pagination" id="fstu-clubs-pagination">
	<div class="fstu-pagination__info" id="fstu-clubs-pag-info" aria-live="polite"></div>
	<div class="fstu-pagination__controls">
		<button type="button" class="fstu-btn fstu-btn--page" id="fstu-clubs-first" aria-label="Перша" disabled>«</button>
		<button type="button" class="fstu-btn fstu-btn--page" id="fstu-clubs-prev"  aria-label="Попередня" disabled>‹</button>
		<span class="fstu-pagination__pages" id="fstu-clubs-pages" role="group" aria-label="Сторінки"></span>
		<button type="button" class="fstu-btn fstu-btn--page" id="fstu-clubs-next"  aria-label="Наступна">›</button>
		<button type="button" class="fstu-btn fstu-btn--page" id="fstu-clubs-last"  aria-label="Остання">»</button>
	</div>
</div>
