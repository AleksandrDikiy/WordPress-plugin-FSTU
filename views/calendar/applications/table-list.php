<?php
/**
 * Таблиця списку заявок Calendar_Applications.
 *
 * Version: 1.1.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar\CalendarApplications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$permissions               = isset( $permissions ) && is_array( $permissions ) ? $permissions : [];
$applications_permissions  = isset( $permissions['applications'] ) && is_array( $permissions['applications'] ) ? $permissions['applications'] : [];
$can_manage_applications   = ! empty( $applications_permissions['canManage'] ) || ! empty( $applications_permissions['canSubmit'] );
$can_protocol_applications = ! empty( $applications_permissions['canProtocol'] );
?>

<div class="fstu-applications-panel-header">
	<div>
		<h3 class="fstu-subpanel-title"><?php esc_html_e( 'Заявки заходу', 'fstu' ); ?></h3>
		<p id="fstu-calendar-applications-context" class="fstu-subpanel-context"><?php esc_html_e( 'Оберіть захід у реєстрі, щоб переглянути заявки.', 'fstu' ); ?></p>
	</div>
	<div class="fstu-applications-panel-actions">
		<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-calendar-applications-back-to-events"><?php esc_html_e( 'До реєстру', 'fstu' ); ?></button>
		<?php if ( $can_protocol_applications ) : ?>
			<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-calendar-applications-protocol-btn"><?php esc_html_e( 'ПРОТОКОЛ', 'fstu' ); ?></button>
		<?php else : ?>
			<button type="button" class="fstu-btn fstu-btn--secondary fstu-hidden" id="fstu-calendar-applications-protocol-btn"><?php esc_html_e( 'ПРОТОКОЛ', 'fstu' ); ?></button>
		<?php endif; ?>
		<button type="button" class="fstu-btn fstu-btn--secondary fstu-hidden" id="fstu-calendar-applications-protocol-back-btn"><?php esc_html_e( 'ЗАЯВКИ', 'fstu' ); ?></button>
		<?php if ( $can_manage_applications ) : ?>
			<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-calendar-add-application-btn"><?php esc_html_e( 'Додати заявку', 'fstu' ); ?></button>
		<?php else : ?>
			<button type="button" class="fstu-btn fstu-btn--secondary fstu-hidden" id="fstu-calendar-add-application-btn"><?php esc_html_e( 'Додати заявку', 'fstu' ); ?></button>
		<?php endif; ?>
	</div>
</div>

<div id="fstu-calendar-applications-main-content">
	<div class="fstu-table-wrap">
		<table class="fstu-table fstu-calendar-applications-table">
			<thead class="fstu-thead">
				<tr>
					<th class="fstu-th fstu-th--num">#</th>
					<th class="fstu-th fstu-th--wide-name">
						<div class="fstu-th-with-search">
							<span><?php esc_html_e( 'Заявка / команда', 'fstu' ); ?></span>
							<label class="screen-reader-text" for="fstu-calendar-applications-search"><?php esc_html_e( 'Пошук по заявках', 'fstu' ); ?></label>
							<input type="text" id="fstu-calendar-applications-search" class="fstu-input--in-header" placeholder="<?php esc_attr_e( '🔍 Пошук...', 'fstu' ); ?>" autocomplete="off" aria-label="<?php esc_attr_e( 'Пошук по заявках', 'fstu' ); ?>">
						</div>
					</th>
					<th class="fstu-th fstu-th--user"><?php esc_html_e( 'Створив', 'fstu' ); ?></th>
					<th class="fstu-th"><?php esc_html_e( 'Статус', 'fstu' ); ?></th>
					<th class="fstu-th"><?php esc_html_e( 'Область', 'fstu' ); ?></th>
					<th class="fstu-th"><?php esc_html_e( 'Телефон', 'fstu' ); ?></th>
					<th class="fstu-th"><?php esc_html_e( 'Учасники', 'fstu' ); ?></th>
					<th class="fstu-th fstu-th--actions"><?php esc_html_e( 'Дії', 'fstu' ); ?></th>
				</tr>
			</thead>
			<tbody id="fstu-calendar-applications-tbody" class="fstu-tbody">
				<tr>
					<td colspan="8" class="fstu-no-results"><?php esc_html_e( 'Оберіть захід, щоб завантажити заявки.', 'fstu' ); ?></td>
				</tr>
			</tbody>
		</table>
	</div>

	<div class="fstu-pagination fstu-pagination--compact">
		<div class="fstu-pagination__left">
			<label class="fstu-pagination__per-page-label" for="fstu-calendar-applications-per-page"><?php esc_html_e( 'Показувати по:', 'fstu' ); ?></label>
			<select id="fstu-calendar-applications-per-page" class="fstu-select fstu-select--compact">
				<option value="10" selected>10</option>
				<option value="15">15</option>
				<option value="25">25</option>
				<option value="50">50</option>
			</select>
		</div>
		<div class="fstu-pagination__controls">
			<button type="button" class="fstu-btn--page" id="fstu-calendar-applications-prev-page">«</button>
			<div id="fstu-calendar-applications-pagination-pages"></div>
			<button type="button" class="fstu-btn--page" id="fstu-calendar-applications-next-page">»</button>
		</div>
		<div class="fstu-pagination__info">
			<span id="fstu-calendar-applications-pagination-info" aria-live="polite"></span>
		</div>
	</div>
</div>

