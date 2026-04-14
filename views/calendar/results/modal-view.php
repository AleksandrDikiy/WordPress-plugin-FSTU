<?php
/**
 * Модальне вікно перегляду перегону Calendar_Results.
 *
 * Version: 1.1.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar\CalendarResults
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-calendar-race-view-modal" class="fstu-modal-overlay fstu-hidden">
	<div class="fstu-modal fstu-calendar-modal fstu-calendar-modal--view">
		<div class="fstu-modal-header">
			<h3 id="fstu-calendar-race-view-title"><?php esc_html_e( 'Картка перегону', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal-close" data-close-modal="fstu-calendar-race-view-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal-body">
			<div id="fstu-calendar-race-view-content" class="fstu-calendar-view-grid"></div>
			<input type="text" id="fstu-calendar-race-protocol-honeypot" name="fstu_website" class="fstu-hidden-field" tabindex="-1" autocomplete="off">

			<h4><?php esc_html_e( 'Фінішний протокол', 'fstu' ); ?></h4>
			<div class="fstu-table-wrap fstu-table-wrap--compact">
				<table class="fstu-table fstu-table--compact">
					<thead class="fstu-thead">
						<tr>
							<th class="fstu-th"><?php esc_html_e( 'Місце', 'fstu' ); ?></th>
							<th class="fstu-th"><?php esc_html_e( 'Судно', 'fstu' ); ?></th>
							<th class="fstu-th"><?php esc_html_e( 'Старт', 'fstu' ); ?></th>
							<th class="fstu-th"><?php esc_html_e( 'Фініш', 'fstu' ); ?></th>
							<th class="fstu-th"><?php esc_html_e( 'Результат', 'fstu' ); ?></th>
							<th class="fstu-th"><?php esc_html_e( 'Примітка', 'fstu' ); ?></th>
						</tr>
						</thead>
					<tbody id="fstu-calendar-race-protocol-tbody" class="fstu-tbody">
						<tr>
							<td colspan="6" class="fstu-no-results"><?php esc_html_e( 'Записи відсутні.', 'fstu' ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>

			<h4><?php esc_html_e( 'Результати', 'fstu' ); ?></h4>
			<div class="fstu-table-wrap fstu-table-wrap--compact">
				<table class="fstu-table fstu-table--compact">
					<thead class="fstu-thead">
						<tr>
							<th class="fstu-th"><?php esc_html_e( 'Місце', 'fstu' ); ?></th>
							<th class="fstu-th"><?php esc_html_e( 'Судно', 'fstu' ); ?></th>
							<th class="fstu-th"><?php esc_html_e( 'Результат', 'fstu' ); ?></th>
							<th class="fstu-th"><?php esc_html_e( 'Очки', 'fstu' ); ?></th>
						</tr>
					</thead>
					<tbody id="fstu-calendar-race-results-tbody" class="fstu-tbody">
						<tr>
							<td colspan="4" class="fstu-no-results"><?php esc_html_e( 'Записи відсутні.', 'fstu' ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<div class="fstu-modal-footer">
			<button type="button" class="fstu-btn fstu-btn--secondary fstu-hidden" id="fstu-calendar-race-edit-protocol-btn"><?php esc_html_e( 'Редагувати протокол', 'fstu' ); ?></button>
			<button type="button" class="fstu-btn fstu-btn--save fstu-hidden" id="fstu-calendar-race-save-protocol-btn"><?php esc_html_e( 'Зберегти протокол', 'fstu' ); ?></button>
			<button type="button" class="fstu-btn fstu-btn--cancel fstu-hidden" id="fstu-calendar-race-cancel-protocol-btn"><?php esc_html_e( 'Скасувати редагування', 'fstu' ); ?></button>
			<button type="button" class="fstu-btn fstu-btn--save" id="fstu-calendar-race-recalculate-btn"><?php esc_html_e( 'Перерахувати результати', 'fstu' ); ?></button>
			<button type="button" class="fstu-btn fstu-btn--cancel" data-close-modal="fstu-calendar-race-view-modal"><?php esc_html_e( 'Закрити', 'fstu' ); ?></button>
		</div>
	</div>
</div>

