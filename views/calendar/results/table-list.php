<?php
/**
 * Таблиця списку перегонів Calendar_Results.
 *
 * Version: 1.0.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar\CalendarResults
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$permissions          = isset( $permissions ) && is_array( $permissions ) ? $permissions : [];
$results_permissions  = isset( $permissions['results'] ) && is_array( $permissions['results'] ) ? $permissions['results'] : [];
$can_manage_results   = ! empty( $results_permissions['canManage'] );
$can_protocol_results = ! empty( $results_permissions['canProtocol'] );
?>

<div class="fstu-applications-panel-header">
	<div>
		<h3 class="fstu-subpanel-title"><?php esc_html_e( 'Перегони та результати', 'fstu' ); ?></h3>
		<p id="fstu-calendar-results-context" class="fstu-subpanel-context"><?php esc_html_e( 'Оберіть захід у реєстрі, щоб переглянути перегони.', 'fstu' ); ?></p>
	</div>
	<div class="fstu-applications-panel-actions">
		<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-calendar-results-back-to-events"><?php esc_html_e( 'До реєстру', 'fstu' ); ?></button>
		<?php if ( $can_protocol_results ) : ?>
			<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-calendar-results-protocol-btn"><?php esc_html_e( 'ПРОТОКОЛ', 'fstu' ); ?></button>
		<?php else : ?>
			<button type="button" class="fstu-btn fstu-btn--secondary fstu-hidden" id="fstu-calendar-results-protocol-btn"><?php esc_html_e( 'ПРОТОКОЛ', 'fstu' ); ?></button>
		<?php endif; ?>
		<button type="button" class="fstu-btn fstu-btn--secondary fstu-hidden" id="fstu-calendar-results-protocol-back-btn"><?php esc_html_e( 'ПЕРЕГОНИ', 'fstu' ); ?></button>
		<?php if ( $can_manage_results ) : ?>
			<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-calendar-add-race-btn"><?php esc_html_e( 'Додати перегін', 'fstu' ); ?></button>
		<?php else : ?>
			<button type="button" class="fstu-btn fstu-btn--secondary fstu-hidden" id="fstu-calendar-add-race-btn"><?php esc_html_e( 'Додати перегін', 'fstu' ); ?></button>
		<?php endif; ?>
	</div>
</div>

<div id="fstu-calendar-results-main-content">
	<div class="fstu-table-wrap">
		<table class="fstu-table fstu-calendar-results-table">
			<thead class="fstu-thead">
				<tr>
					<th class="fstu-th fstu-th--num">#</th>
					<th class="fstu-th fstu-th--wide-name">
						<div class="fstu-th-with-search">
							<span><?php esc_html_e( 'Перегін', 'fstu' ); ?></span>
							<input type="text" id="fstu-calendar-results-search" class="fstu-input--in-header" placeholder="<?php esc_attr_e( '🔍 Пошук...', 'fstu' ); ?>" autocomplete="off" aria-label="<?php esc_attr_e( 'Пошук по перегонах', 'fstu' ); ?>">
						</div>
					</th>
					<th class="fstu-th fstu-th--date"><?php esc_html_e( 'Дата', 'fstu' ); ?></th>
					<th class="fstu-th"><?php esc_html_e( 'Номер', 'fstu' ); ?></th>
					<th class="fstu-th"><?php esc_html_e( 'Тип', 'fstu' ); ?></th>
					<th class="fstu-th"><?php esc_html_e( 'Протокол / результати', 'fstu' ); ?></th>
					<th class="fstu-th fstu-th--actions"><?php esc_html_e( 'Дії', 'fstu' ); ?></th>
				</tr>
			</thead>
			<tbody id="fstu-calendar-results-tbody" class="fstu-tbody">
				<tr>
					<td colspan="7" class="fstu-no-results"><?php esc_html_e( 'Оберіть захід, щоб завантажити перегони.', 'fstu' ); ?></td>
				</tr>
			</tbody>
		</table>
	</div>

	<div class="fstu-pagination fstu-pagination--compact">
		<div class="fstu-pagination__left">
			<label class="fstu-pagination__per-page-label" for="fstu-calendar-results-per-page"><?php esc_html_e( 'Показувати по:', 'fstu' ); ?></label>
			<select id="fstu-calendar-results-per-page" class="fstu-select fstu-select--compact">
				<option value="10" selected>10</option>
				<option value="15">15</option>
				<option value="25">25</option>
				<option value="50">50</option>
			</select>
		</div>
		<div class="fstu-pagination__controls">
			<button type="button" class="fstu-btn--page" id="fstu-calendar-results-prev-page">«</button>
			<div id="fstu-calendar-results-pagination-pages"></div>
			<button type="button" class="fstu-btn--page" id="fstu-calendar-results-next-page">»</button>
		</div>
		<div class="fstu-pagination__info">
			<span id="fstu-calendar-results-pagination-info" aria-live="polite"></span>
		</div>
	</div>
</div>

