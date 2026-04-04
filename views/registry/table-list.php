<?php
/**
 * View: Таблиця реєстру членів ФСТУ.
 * Містить <thead> та порожній <tbody> (заповнюється через AJAX).
 * Також містить блок пагінації.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-03
 *
 * @package FSTU\Registry\Views
 *
 * @var int  $current_year Поточний рік.
 * @var bool $is_logged_in Чи авторизований.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$prev_year = $current_year - 1;
?>

<div class="fstu-table-wrap" role="region" aria-label="Список членів ФСТУ" aria-live="polite">

	<!-- Індикатор завантаження -->
	<div class="fstu-loader fstu-hidden" id="fstu-loader" aria-hidden="true">
		<span class="fstu-loader__spinner"></span>
		<span class="fstu-loader__text">Завантаження...</span>
	</div>

	<table class="fstu-table" id="fstu-registry-table">
		<thead class="fstu-thead">
			<tr>
				<th class="fstu-th fstu-th--num" scope="col">№</th>
				<th class="fstu-th fstu-th--fstu" scope="col" title="Членство ФСТУ">ФСТУ</th>
				<th class="fstu-th fstu-th--card" scope="col">Чл. квиток</th>
				<th class="fstu-th fstu-th--fio" scope="col">ПІБ</th>
				<th class="fstu-th fstu-th--ofst" scope="col">ОФСТ</th>
				<th class="fstu-th fstu-th--club" scope="col">Клуб</th>

				<?php if ( $is_logged_in ) : ?>
					<th class="fstu-th fstu-th--dues" scope="col"><?php echo esc_html( $prev_year ); ?></th>
					<th class="fstu-th fstu-th--dues" scope="col"><?php echo esc_html( $current_year ); ?></th>
					<th class="fstu-th fstu-th--dues" scope="col">віт-<?php echo esc_html( $prev_year ); ?></th>
					<th class="fstu-th fstu-th--dues" scope="col">віт-<?php echo esc_html( $current_year ); ?></th>
				<?php else : ?>
					<th class="fstu-th fstu-th--dues-locked" scope="col" colspan="4" title="Доступно після входу">
						<span class="fstu-lock-icon" aria-hidden="true">🔒</span>
					</th>
				<?php endif; ?>

				<th class="fstu-th fstu-th--actions" scope="col">
					<span class="fstu-sr-only">Дії</span>
					<button type="button"
					        class="fstu-th-settings-btn"
					        id="fstu-column-settings"
					        title="Налаштування стовпців"
					        aria-label="Налаштування стовпців">⚙</button>
				</th>
			</tr>
		</thead>

		<tbody class="fstu-tbody" id="fstu-registry-tbody">
			<!-- Заповнюється через AJAX -->
			<tr>
				<td colspan="11" class="fstu-no-results">
					<span class="fstu-loader__spinner"></span> Завантаження...
				</td>
			</tr>
		</tbody>
	</table>

</div><!-- .fstu-table-wrap -->

<!-- ── Пагінація ─────────────────────────────────────────────────────────── -->
<div class="fstu-pagination" id="fstu-pagination" role="navigation" aria-label="Навігація по сторінках">

	<div class="fstu-pagination__info" id="fstu-pagination-info" aria-live="polite">
		<!-- Заповнюється JS: "Показано 1–10 з 1234 записів" -->
	</div>

	<div class="fstu-pagination__controls">
		<button type="button"
		        class="fstu-btn fstu-btn--page"
		        id="fstu-page-first"
		        data-page="1"
		        aria-label="Перша сторінка"
		        disabled>«</button>

		<button type="button"
		        class="fstu-btn fstu-btn--page"
		        id="fstu-page-prev"
		        data-action="prev"
		        aria-label="Попередня сторінка"
		        disabled>‹</button>

		<span class="fstu-pagination__pages" id="fstu-page-numbers" aria-label="Сторінки">
			<!-- Номери сторінок генеруються через JS -->
		</span>

		<button type="button"
		        class="fstu-btn fstu-btn--page"
		        id="fstu-page-next"
		        data-action="next"
		        aria-label="Наступна сторінка">›</button>

		<button type="button"
		        class="fstu-btn fstu-btn--page"
		        id="fstu-page-last"
		        data-action="last"
		        aria-label="Остання сторінка">»</button>
	</div>

</div><!-- .fstu-pagination -->
