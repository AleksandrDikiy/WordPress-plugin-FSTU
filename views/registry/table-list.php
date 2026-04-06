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
<div class="fstu-pagination" id="fstu-pagination" style="margin-top: 15px; border-top: 1px solid var(--fstu-border); padding-top: 15px; display: flex; justify-content: space-between; align-items: center;">

    <div class="fstu-pagination__info" id="fstu-pagination-info" style="flex: 1;"></div>

    <div id="fstu-stat-wrap" style="flex: 1; text-align: center; font-size: 13px; font-weight: 500; display: none;">
        Сплатили членські внески: <span id="fstu-stat-paid" style="color: var(--fstu-success); font-weight: bold; font-size: 15px;">0</span>
    </div>

    <div style="display: flex; align-items: center; gap: 15px; flex: 1; justify-content: flex-end;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <label for="fstu-filter-per-page" style="font-size: 14px; color: var(--fstu-text-light); margin: 0; white-space: nowrap;">Показувати по:</label>
            <select id="fstu-filter-per-page" class="fstu-select fstu-filter-trigger" data-filter="per_page" style="width: 70px; height: 32px; padding: 2px 20px 2px 8px;">
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>

        <div class="fstu-pagination__controls" id="fstu-pagination-controls" style="display: flex; gap: 4px;">
            <button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-page-first" disabled aria-label="Перша">«</button>
            <button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-page-prev" disabled aria-label="Попередня">‹</button>

            <span id="fstu-page-numbers" class="fstu-pagination__numbers" style="display: flex; gap: 4px; margin: 0 5px;"></span>

            <button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-page-next" disabled aria-label="Наступна">›</button>
            <button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-page-last" disabled aria-label="Остання">»</button>
        </div>
    </div>
</div>
<!-- .fstu-pagination -->
