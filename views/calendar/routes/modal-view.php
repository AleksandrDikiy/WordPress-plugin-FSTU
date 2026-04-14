<?php
/**
 * Модальне вікно перегляду маршруту Calendar_Routes.
 *
 * Version: 1.0.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar\CalendarRoutes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-calendar-route-view-modal" class="fstu-modal-overlay fstu-hidden">
	<div class="fstu-modal fstu-calendar-modal fstu-calendar-modal--view">
		<div class="fstu-modal-header">
			<h3 id="fstu-calendar-route-view-title"><?php esc_html_e( 'Картка маршруту', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal-close" data-close-modal="fstu-calendar-route-view-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal-body">
			<div id="fstu-calendar-route-view-content" class="fstu-calendar-view-grid"></div>

			<h4><?php esc_html_e( 'Ділянки маршруту', 'fstu' ); ?></h4>
			<div class="fstu-table-wrap fstu-table-wrap--compact">
				<table class="fstu-table fstu-table--compact">
					<thead class="fstu-thead">
						<tr>
							<th class="fstu-th fstu-th--date"><?php esc_html_e( 'Дата', 'fstu' ); ?></th>
							<th class="fstu-th fstu-th--wide-name"><?php esc_html_e( 'Опис', 'fstu' ); ?></th>
							<th class="fstu-th"><?php esc_html_e( 'Відстань', 'fstu' ); ?></th>
							<th class="fstu-th"><?php esc_html_e( 'Транспорт', 'fstu' ); ?></th>
						</tr>
					</thead>
					<tbody id="fstu-calendar-route-view-segments-tbody" class="fstu-tbody">
						<tr>
							<td colspan="4" class="fstu-no-results"><?php esc_html_e( 'Завантаження...', 'fstu' ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>

			<h4><?php esc_html_e( 'Рішення МКК', 'fstu' ); ?></h4>
			<div class="fstu-table-wrap fstu-table-wrap--compact">
				<table class="fstu-table fstu-table--compact">
					<thead class="fstu-thead">
						<tr>
							<th class="fstu-th fstu-th--date"><?php esc_html_e( 'Дата', 'fstu' ); ?></th>
							<th class="fstu-th fstu-th--user"><?php esc_html_e( 'Рецензент', 'fstu' ); ?></th>
							<th class="fstu-th fstu-th--type"><?php esc_html_e( 'Статус', 'fstu' ); ?></th>
							<th class="fstu-th fstu-th--message"><?php esc_html_e( 'Примітка', 'fstu' ); ?></th>
						</tr>
					</thead>
					<tbody id="fstu-calendar-route-view-verifications-tbody" class="fstu-tbody">
						<tr>
							<td colspan="4" class="fstu-no-results"><?php esc_html_e( 'Записи відсутні.', 'fstu' ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>

			<div id="fstu-calendar-route-review-box" class="fstu-route-review-box fstu-hidden">
				<h4><?php esc_html_e( 'Рішення МКК', 'fstu' ); ?></h4>
				<input type="hidden" id="fstu-calendar-route-review-calendar-id" value="0">
				<label class="screen-reader-text" for="fstu-calendar-route-review-honeypot"><?php esc_html_e( 'Website', 'fstu' ); ?></label>
				<input type="text" id="fstu-calendar-route-review-honeypot" name="fstu_website" class="fstu-hidden-field" tabindex="-1" autocomplete="off">
				<div class="fstu-route-review-options">
					<label><input type="radio" name="fstu-calendar-route-review-status" value="1" checked> <?php esc_html_e( 'Погодити', 'fstu' ); ?></label>
					<label><input type="radio" name="fstu-calendar-route-review-status" value="2"> <?php esc_html_e( 'Є помилки', 'fstu' ); ?></label>
				</div>
				<div class="fstu-form-field">
					<label for="fstu-calendar-route-review-note"><?php esc_html_e( 'Примітка МКК', 'fstu' ); ?></label>
					<textarea id="fstu-calendar-route-review-note" class="fstu-input" rows="4" maxlength="250"></textarea>
				</div>
			</div>
		</div>
		<div class="fstu-modal-footer">
			<button type="button" class="fstu-btn fstu-btn--save fstu-hidden" id="fstu-calendar-route-review-submit-btn"><?php esc_html_e( 'Зберегти рішення МКК', 'fstu' ); ?></button>
			<button type="button" class="fstu-btn fstu-btn--cancel" data-close-modal="fstu-calendar-route-view-modal"><?php esc_html_e( 'Закрити', 'fstu' ); ?></button>
		</div>
	</div>
</div>
