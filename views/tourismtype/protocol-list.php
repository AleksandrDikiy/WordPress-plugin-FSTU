<?php
/**
 * View: журнал операцій (ПРОТОКОЛ) довідника "Видів туризму".
 *
 * 6 обов'язкових колонок: Дата | Тип | Операція (+ пошук у th) |
 * Повідомлення | Статус | Користувач.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-07
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="fstu-table-wrap" id="fstu-tourismtype-protocol-wrap">

	<!-- Скелетон / спінер -->
	<div class="fstu-loading fstu-loading--hidden" id="fstu-tourismtype-protocol-loading"
	     aria-label="<?php esc_attr_e( 'Завантаження…', 'fstu' ); ?>">
		<span class="fstu-spinner"></span>
	</div>

	<!-- Таблиця протоколу -->
	<table class="fstu-table fstu-table--zebra fstu-table--protocol" id="fstu-tourismtype-protocol-table">
		<thead>
			<tr class="fstu-tr--head">

				<!-- 1: Дата -->
				<th class="fstu-th fstu-th--date" scope="col">
					<?php esc_html_e( 'Дата', 'fstu' ); ?>
				</th>

				<!-- 2: Тип -->
				<th class="fstu-th fstu-th--type fstu-th--center" scope="col">
					<?php esc_html_e( 'Тип', 'fstu' ); ?>
				</th>

				<!-- 3: Операція (+ пошук) -->
				<th class="fstu-th fstu-th--wide-name" scope="col">
					<div class="fstu-th-with-search">
						<span><?php esc_html_e( 'Операція', 'fstu' ); ?></span>
						<input type="text"
						       id="fstu-tourismtype-protocol-search"
						       class="fstu-input--in-header"
						       placeholder="🔍 <?php esc_attr_e( 'Пошук…', 'fstu' ); ?>"
						       autocomplete="off"
						       maxlength="100"
						       aria-label="<?php esc_attr_e( 'Пошук за операцією або користувачем', 'fstu' ); ?>">
					</div>
				</th>

				<!-- 4: Повідомлення -->
				<th class="fstu-th fstu-th--message" scope="col">
					<?php esc_html_e( 'Повідомлення', 'fstu' ); ?>
				</th>

				<!-- 5: Статус -->
				<th class="fstu-th fstu-th--status fstu-th--center" scope="col">
					<?php esc_html_e( 'Статус', 'fstu' ); ?>
				</th>

				<!-- 6: Користувач -->
				<th class="fstu-th fstu-th--user" scope="col">
					<?php esc_html_e( 'Користувач', 'fstu' ); ?>
				</th>

			</tr>
		</thead>

		<tbody id="fstu-tourismtype-protocol-tbody">
			<!-- JS вставляє рядки сюди -->
		</tbody>
	</table><!-- /.fstu-table--protocol -->

	<!-- Пагінація протоколу -->
	<div class="fstu-pagination fstu-pagination--compact" id="fstu-tourismtype-protocol-pagination">

		<div class="fstu-pagination__left">
			<label class="fstu-pagination__per-page-label"
			       for="fstu-tourismtype-protocol-per-page">
				<?php esc_html_e( 'Показувати по:', 'fstu' ); ?>
			</label>
			<select id="fstu-tourismtype-protocol-per-page"
			        class="fstu-select fstu-select--compact"
			        aria-label="<?php esc_attr_e( 'Записів на сторінку', 'fstu' ); ?>">
				<option value="10" selected>10</option>
				<option value="15">15</option>
				<option value="25">25</option>
				<option value="50">50</option>
			</select>
		</div>

		<div class="fstu-pagination__controls" id="fstu-tourismtype-protocol-page-controls"></div>

		<div class="fstu-pagination__info" id="fstu-tourismtype-protocol-page-info"></div>

	</div><!-- /.fstu-pagination -->

</div><!-- /.fstu-table-wrap -->
