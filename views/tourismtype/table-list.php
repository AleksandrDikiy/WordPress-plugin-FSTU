<?php
/**
 * View: таблиця довідника "Видів туризму".
 *
 * — Пошук розміщено БЕЗПОСЕРЕДНЬО в <th> колонки «Найменування».
 * — Кількість записів на сторінку — ВИКЛЮЧНО внизу таблиці (pagination--compact).
 * — Дія у рядку — через dropdown-меню (.fstu-dropdown).
 *
 * Version:     1.0.0
 * Date_update: 2026-04-07
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_admin = current_user_can( 'manage_options' );
?>
<div class="fstu-table-wrap" id="fstu-tourismtype-table-wrap">

	<!-- Скелетон / спінер завантаження -->
	<div class="fstu-loading" id="fstu-tourismtype-loading" aria-label="<?php esc_attr_e( 'Завантаження…', 'fstu' ); ?>">
		<span class="fstu-spinner"></span>
	</div>

	<!-- Основна таблиця -->
	<table class="fstu-table fstu-table--zebra" id="fstu-tourismtype-table">
		<thead>
			<tr class="fstu-tr--head">

				<!-- Колонка №  -->
				<th class="fstu-th fstu-th--num" scope="col">#</th>

				<!-- Найменування + пошук у шапці -->
				<th class="fstu-th fstu-th--wide-name" scope="col">
					<div class="fstu-th-with-search">
						<span><?php esc_html_e( 'Найменування', 'fstu' ); ?></span>
						<input type="text"
						       id="fstu-tourismtype-search"
						       class="fstu-input--in-header"
						       placeholder="🔍 <?php esc_attr_e( 'Пошук…', 'fstu' ); ?>"
						       autocomplete="off"
						       maxlength="100"
						       aria-label="<?php esc_attr_e( 'Пошук за найменуванням', 'fstu' ); ?>">
					</div>
				</th>

				<!-- № Статті — видима лише адміну -->
				<?php if ( $is_admin ) : ?>
					<th class="fstu-th fstu-th--center fstu-th--narrow" scope="col">
						<?php esc_html_e( '№ сторінки', 'fstu' ); ?>
					</th>
					<!-- Колонка Дії -->
					<th class="fstu-th fstu-th--actions" scope="col">
						<?php esc_html_e( 'Дії', 'fstu' ); ?>
					</th>
				<?php endif; ?>

			</tr>
		</thead>

		<!-- Тіло таблиці — заповнюється через JS -->
		<tbody id="fstu-tourismtype-tbody">
			<!-- JS вставляє рядки сюди -->
		</tbody>
	</table><!-- /.fstu-table -->

	<!-- ================================================================ -->
	<!-- ПАГІНАЦІЯ — compact: per-page зліва, кнопки по центру, інфо справа -->
	<!-- ================================================================ -->
	<div class="fstu-pagination fstu-pagination--compact" id="fstu-tourismtype-pagination">

		<!-- Вибір кількості записів -->
		<div class="fstu-pagination__left">
			<label class="fstu-pagination__per-page-label"
			       for="fstu-tourismtype-per-page">
				<?php esc_html_e( 'Показувати по:', 'fstu' ); ?>
			</label>
			<select id="fstu-tourismtype-per-page"
			        class="fstu-select fstu-select--compact"
			        aria-label="<?php esc_attr_e( 'Записів на сторінку', 'fstu' ); ?>">
				<option value="10" selected>10</option>
				<option value="15">15</option>
				<option value="25">25</option>
				<option value="50">50</option>
			</select>
		</div>

		<!-- Кнопки навігації по сторінках -->
		<div class="fstu-pagination__controls" id="fstu-tourismtype-page-controls">
			<!-- JS генерує кнопки -->
		</div>

		<!-- Інформаційний рядок -->
		<div class="fstu-pagination__info" id="fstu-tourismtype-page-info">
			<!-- JS вставляє текст -->
		</div>

	</div><!-- /.fstu-pagination -->

</div><!-- /.fstu-table-wrap -->
